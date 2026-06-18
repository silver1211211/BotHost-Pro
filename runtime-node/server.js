require('dotenv').config();

const express = require('express');
const crypto = require('node:crypto');
const dns = require('node:dns').promises;
const net = require('node:net');
const vm = require('node:vm');
const { createCommandSandbox } = require('./admin-helper-loader');

const app = express();
const host = process.env.HOST || '127.0.0.1';
const port = Number(process.env.PORT || 8787);
const runtimeSecret = process.env.NODE_RUNTIME_SECRET || '';
const executionTimeoutMs = Number(process.env.COMMAND_TIMEOUT_MS || 30000);
const maxDelayMs = Number(process.env.COMMAND_MAX_DELAY_MS || 10000);
const requestTimeoutMs = Number(process.env.HTTPS_REQUEST_TIMEOUT_MS || 5000);
const bridgeTimeoutMs = Number(process.env.BRIDGE_TIMEOUT_MS || 15000);
const maxResponseBytes = Number(process.env.HTTPS_REQUEST_MAX_BYTES || 262144);

process.on('uncaughtException', (error) => {
  console.error('Uncaught runtime exception:', safeErrorLog(error));
});

process.on('unhandledRejection', (reason) => {
  console.error('Unhandled runtime rejection:', safeErrorLog(reason));
});

app.use(express.json({ limit: '256kb' }));

app.get('/health', (req, res) => {
  res.json({
    ok: true,
    runtime: 'node',
    status: 'ready',
    service: 'BotHost Pro Node Runtime',
    version: 'v2',
  });
});

app.post('/execute', executeCommandHandler);
app.post('/execute-command', executeCommandHandler);

async function executeCommandHandler(req, res) {
  const startedAt = Date.now();
  const executionId = fallbackExecutionId();
  let redactions = [];
  let runtime = null;
  let actions = [];

  try {
    if (runtimeSecret && req.header('X-Runtime-Secret') !== runtimeSecret) {
      return sendRuntimeError(res, startedAt, executionId, 'Unauthorized runtime request.', 'UnauthorizedRuntimeRequest', 401);
    }

    const payload = req.body || {};
    redactions = sensitiveValues(payload);
    const code = payload.command && typeof payload.command.code === 'string'
      ? payload.command.code
      : '';
    const executableCode = normalizeCommandCode(code);

    if (!executableCode.trim()) {
      return sendRuntimeError(res, startedAt, executionId, 'Command code is required.', 'ValidationError');
    }

    const restrictedCode = findRestrictedCode(executableCode);

    if (restrictedCode) {
      return sendRuntimeError(
        res,
        startedAt,
        executionId,
        `${restrictedCode} is not available in command code.`,
        'RestrictedCodeError',
      );
    }

    const requestSettings = normalizeRuntimeSettings(payload.settings || {});
    actions = [];
    runtime = buildRuntimeHelpers(payload, actions);
    const helpers = runtime.helpers;
    const sandbox = createCommandSandbox(helpers);

    const context = vm.createContext(sandbox, {
      name: 'bothost-command-runtime',
      codeGeneration: { strings: false, wasm: false },
    });

    const script = new vm.Script(`"use strict";\n(async () => {\n${executableCode}\n})()`, {
      timeout: requestSettings.command_timeout_ms,
      displayErrors: true,
    });

    const result = script.runInContext(context, { timeout: requestSettings.command_timeout_ms });
    await promiseWithTimeout(Promise.resolve(result), requestSettings.command_timeout_ms);

    return res.json({
      ok: true,
      execution_id: executionId,
      execution_time_ms: Date.now() - startedAt,
      replies: actions,
      storage: runtime.storageMutations(),
      error: null,
      error_type: null,
    });
  } catch (error) {
    const isTimeout = isTimeoutError(error);
    const errorType = isTimeout ? 'TimeoutError' : 'RuntimeError';

    console.error('Runtime command execution failed:', {
      execution_id: executionId,
      error_type: errorType,
      ...safeErrorLog(error, redactions),
    });

    return sendRuntimeError(
      res,
      startedAt,
      executionId,
      isTimeout ? 'Execution timed out.' : publicErrorMessage(error, redactions),
      errorType,
      200,
      error,
      redactions,
      runtime && typeof runtime.storageMutations === 'function' ? runtime.storageMutations() : null,
      actions,
    );
  }
}

app.use((error, req, res, next) => {
  console.error('Runtime HTTP error:', safeErrorLog(error));

  if (res.headersSent) {
    return next(error);
  }

  return res.status(400).json({
    ok: false,
    execution_id: null,
    execution_time_ms: null,
    replies: [],
    error: 'Invalid runtime request.',
    error_type: 'InvalidRuntimeRequest',
  });
});

app.listen(port, host, () => {
  console.log(`BotHost Pro Node Runtime listening on http://${host}:${port}`);
});

function buildRuntimeHelpers(payload, actions) {
  const telegram = payload.telegram || {};
  const runtime = payload.runtime || {};
  const requestSettings = normalizeRuntimeSettings(payload.settings || {});
  const safeMessage = plainObject(telegram.message || { text: telegram.message_text || '' });
  const safeUpdate = plainObject(telegram.update || {});

  // For callback_query updates the clicker is in callback_query.from.
  // message.from is the BOT (who sent the keyboard), never the user — so we must not use it.
  const callbackQueryObj = telegram.callback_query && typeof telegram.callback_query === 'object'
    ? plainObject(telegram.callback_query)
    : null;
  const callbackFromObj = callbackQueryObj && callbackQueryObj.from
    ? plainObject(callbackQueryObj.from)
    : null;

  const safeUser = (callbackFromObj && callbackFromObj.id != null)
    ? {
        id: callbackFromObj.id,
        first_name: callbackFromObj.first_name ?? null,
        last_name: callbackFromObj.last_name ?? null,
        username: callbackFromObj.username ?? null,
        language_code: callbackFromObj.language_code ?? null,
      }
    : userFromMessage(safeMessage, telegram);

  const safeChat = chatFromMessage(safeMessage, telegram);

  if (callbackQueryObj) {
    console.log('[BotHost] callback_from_user_id', JSON.stringify({
      bot_id: (payload.bot || {}).id || null,
      callback_from_user_id: safeUser.id,
      callback_data: telegram.callback_data || null,
      owner_match: String(safeUser.id) === '7701909986',
    }));
  }
  const safeBot = safeBotMetadata(payload.bot || {});
  const safeCommand = safeCommandMetadata(payload.command || {});
  const commandFlow = normalizeCommandFlow(telegram.command_flow, safeCommand);
  const commandStep = commandFlow.step;
  const commandData = freezePlainObject(commandFlow.data || {});
  const args = Array.isArray(telegram.args) ? telegram.args.map((arg) => String(arg)) : [];
  const botToken = '';
  const defaultBridgeBaseUrl = runtimeBridgeBaseUrl();
  const telegramBridgeUrl = firstNonEmptyString(
    runtime.telegram_bridge_url,
    process.env.TELEGRAM_BRIDGE_URL,
    defaultBridgeBaseUrl ? `${defaultBridgeBaseUrl}/runtime/telegram` : '',
  );
  const telegramBridgeSecret = firstNonEmptyString(
    runtime.telegram_bridge_secret,
    process.env.TELEGRAM_BRIDGE_SECRET,
    process.env.NODE_RUNTIME_SECRET,
  );
  const storageBridgeUrl = firstNonEmptyString(
    runtime.storage_bridge_url,
    process.env.STORAGE_BRIDGE_URL,
  );
  const storageBridgeSecret = firstNonEmptyString(
    runtime.storage_bridge_secret,
    process.env.STORAGE_BRIDGE_SECRET,
    process.env.NODE_RUNTIME_SECRET,
  );
  const faucetPayApiKey = typeof runtime.faucetpay_api_key === 'string' ? runtime.faucetpay_api_key : '';
  const oxapayBridgeUrl = typeof runtime.oxapay_bridge_url === 'string' ? runtime.oxapay_bridge_url : '';
  const oxapayBridgeSecret = typeof runtime.oxapay_bridge_secret === 'string' ? runtime.oxapay_bridge_secret : '';
  const runtimeSecrets = new Map(Object.entries(plainObject(runtime.secrets || {})));
  const botData = new Map(Object.entries(plainObject((payload.storage || {}).bot || {})));
  const userData = new Map(Object.entries(plainObject((payload.storage || {}).user || {})));
  const botMutations = [];
  const userMutations = [];
  const crossUsersRaw = plainObject((payload.storage || {}).cross_users || {});
  const crossUsersData = {};
  for (const [uid, data] of Object.entries(crossUsersRaw)) {
    const normalizedUid = normalizeCrossUserId(uid, 'cross user preload');
    crossUsersData[normalizedUid] = new Map(Object.entries(plainObject(data || {})));
  }
  const crossUserMutations = {};

  const setStorageValue = (map, mutations, key, value) => {
    const normalizedKey = requireStorageKey(key);
    const normalizedValue = jsonSafeValue(value);

    map.set(normalizedKey, normalizedValue);
    mutations.push({ op: 'set', key: normalizedKey, value: normalizedValue });

    return normalizedValue;
  };

  const clearStorageValue = (map, mutations, key) => {
    const normalizedKey = requireStorageKey(key);

    map.delete(normalizedKey);
    mutations.push({ op: 'clear', key: normalizedKey });
  };

  const telegramRuntimeAction = async (action, options = {}) => {
    if (!telegramBridgeUrl || !telegramBridgeSecret) {
      return { ok: false, error: 'Telegram runtime bridge is not configured.' };
    }

    const result = await internalRuntimePost(telegramBridgeUrl, {
      bot_id: safeBot.id,
      action,
      options: normalizeObject(options, 'Telegram helper options'),
    }, telegramBridgeSecret, bridgeTimeoutMs, 'Telegram bridge', 'telegram');

    if (result && result.error_type === 'TelegramBridgeTimeout') {
      console.error('[BotHost] telegram_bridge_timeout', JSON.stringify({
        bot_id: safeBot.id, command_id: safeCommand.id, command_name: safeCommand.name,
        action, telegram_user_id: safeUser.id ?? null,
      }));
    }

    return result;
  };

  const storageRuntimeGet = async (action, key, defaultValue = null, targetTelegramUserId = null) => {
    const normalizedKey = requireStorageKey(key);
    if (!storageBridgeUrl || !storageBridgeSecret) {
      return jsonSafeValue(defaultValue);
    }

    const response = await internalRuntimePost(storageBridgeUrl, {
      bot_id: safeBot.id,
      telegram_user_id: targetTelegramUserId !== null && targetTelegramUserId !== undefined
        ? String(targetTelegramUserId)
        : (safeUser.id != null ? String(safeUser.id) : null),
      action,
      key: normalizedKey,
    }, storageBridgeSecret, 500, 'storage bridge');

    if (response && response.ok && response.found) {
      return jsonSafeValue(response.value);
    }

    return jsonSafeValue(defaultValue);
  };

  const storageRuntimeSet = async (action, key, value, targetTelegramUserId = null) => {
    const normalizedKey = requireStorageKey(key);
    if (!storageBridgeUrl || !storageBridgeSecret) {
      return { ok: false, error: 'Storage bridge is not configured.' };
    }

    return internalRuntimePost(storageBridgeUrl, {
      bot_id: safeBot.id,
      telegram_user_id: targetTelegramUserId !== null && targetTelegramUserId !== undefined
        ? String(targetTelegramUserId)
        : (safeUser.id != null ? String(safeUser.id) : null),
      action,
      key: normalizedKey,
      value: jsonSafeValue(value),
    }, storageBridgeSecret, 500, 'storage bridge');
  };

  const storageRuntimeFindUser = async (key, value) => {
    const normalizedKey = requireStorageKey(key);
    const normalizedValue = jsonSafeValue(value);

    if (!storageBridgeUrl || !storageBridgeSecret) {
      const wanted = canonicalFindValue(normalizedValue);
      if (wanted !== null && userData.has(normalizedKey) && canonicalFindValue(userData.get(normalizedKey)) === wanted) {
        return { ok: true, found: true, user_id: String(safeUser.id), value: userData.get(normalizedKey) };
      }
      for (const [uid, data] of Object.entries(crossUsersData)) {
        if (data && data.has(normalizedKey) && canonicalFindValue(data.get(normalizedKey)) === wanted) {
          return { ok: true, found: true, user_id: String(uid), value: data.get(normalizedKey) };
        }
      }
      return { ok: true, found: false, value: null };
    }

    return internalRuntimePost(storageBridgeUrl, {
      bot_id: safeBot.id,
      telegram_user_id: safeUser.id != null ? String(safeUser.id) : null,
      action: 'user.find',
      key: normalizedKey,
      value: normalizedValue,
    }, storageBridgeSecret, 1000, 'storage bridge');
  };

  const sendMessage = async (chatIdOrText, textOrOptions = undefined, opts = {}) => {
    try {
      let chatId, text, options, explicitTarget = false;
      if (typeof textOrOptions === 'string') {
        // sendMessage(chatId, text [, options])
        chatId = chatIdOrText;
        text = textOrOptions;
        options = opts;
        explicitTarget = true;
      } else {
        // sendMessage(text [, options]) — use current chat
        chatId = safeChat.id;
        text = chatIdOrText;
        options = (textOrOptions !== null && typeof textOrOptions === 'object') ? textOrOptions : opts;
      }
      const normalizedText = requireString(text, 'sendMessage text');
      const targetChatId = requireChatId(chatId, 'sendMessage chatId');
      const normalizedOptions = normalizeMessageOptions(options);
      const currentChatId = safeChat.id !== null && safeChat.id !== undefined ? String(safeChat.id) : null;
      const isCurrentChatTarget = currentChatId !== null && String(targetChatId) === currentChatId;
      if (explicitTarget && !isCurrentChatTarget && telegramBridgeUrl && telegramBridgeSecret) {
        const result = await telegramRuntimeAction('telegram.sendMessage', { chat_id: targetChatId, text: normalizedText, ...normalizedOptions });
        return result && result.ok
          ? { ...result, queued: false }
          : result;
      }

      actions.push({
        type: 'text',
        chat_id: targetChatId,
        text: normalizedText,
        ...normalizedOptions,
      });
      return { ok: true, result: null, queued: true };
    } catch (err) {
      return { ok: false, error: String((err && err.message) || err) };
    }
  };

  const reply = async (text, options = {}) => sendMessage(text, options);

  const replyHTML = async (html, options = {}) => {
    return reply(html, { ...normalizeObject(options, 'replyHTML options'), parse_mode: 'HTML' });
  };

  const replyMarkdown = async (text, options = {}) => {
    return reply(text, { ...normalizeObject(options, 'replyMarkdown options'), parse_mode: 'Markdown' });
  };

  const getMessageText = (fallback = '') => {
    const value = safeMessage.text ?? safeMessage.caption ?? telegram.message_text ?? null;
    return value === null || value === undefined ? String(fallback ?? '') : String(value);
  };

  const getCallbackData = (fallback = '') => {
    const value = telegram.callback_data ?? (callbackQueryObj ? callbackQueryObj.data : null);
    return value === null || value === undefined ? String(fallback ?? '') : String(value);
  };

  const isCallback = () => callbackQueryObj !== null;
  const isCommandText = (value = null) => String(value ?? getMessageText('')).trim().startsWith('/');
  const getCommandName = () => {
    const source = isCallback() && getCallbackData('') ? getCallbackData('') : getMessageText('');
    return String(source).trim().split(/\s+/u)[0] || '';
  };
  const getCommandArgs = () => [...args];
  const getChatId = () => safeChat.id ?? null;
  const getUserId = () => safeUser.id ?? null;
  const getMessageId = () => safeMessage.message_id ?? null;
  const getUsername = (fallback = '') => safeUser.username ? String(safeUser.username) : String(fallback ?? '');
  const hasPhoto = () => Array.isArray(safeMessage.photo) && safeMessage.photo.length > 0;
  const getPhotoFileId = () => hasPhoto() ? safeMessage.photo[safeMessage.photo.length - 1].file_id ?? null : null;
  const getPhotoCaption = (fallback = '') => safeMessage.caption ? String(safeMessage.caption) : String(fallback ?? '');
  const hasDocument = () => !!(safeMessage.document && typeof safeMessage.document === 'object');
  const getDocumentFileId = () => hasDocument() ? safeMessage.document.file_id ?? null : null;
  const getDocumentName = () => hasDocument() ? safeMessage.document.file_name ?? '' : '';
  const getMediaType = () => {
    if (hasPhoto()) return 'photo';
    if (hasDocument()) return 'document';
    if (safeMessage.video) return 'video';
    if (safeMessage.animation) return 'animation';
    if (safeMessage.audio) return 'audio';
    if (safeMessage.voice) return 'voice';
    if (safeMessage.sticker) return 'sticker';
    return null;
  };
  const getIncomingMedia = () => {
    const type = getMediaType();
    if (!type) return null;
    const media = type === 'photo' ? safeMessage.photo[safeMessage.photo.length - 1] : safeMessage[type];
    return {
      type,
      file_id: media && typeof media === 'object' ? media.file_id ?? null : null,
      file_name: media && typeof media === 'object' ? media.file_name ?? null : null,
      mime_type: media && typeof media === 'object' ? media.mime_type ?? null : null,
      caption: safeMessage.caption ?? null,
      message_id: safeMessage.message_id ?? null,
    };
  };

  const safeCaption = (text, limit = 1024) => sanitizeText(String(text ?? ''), Math.min(Math.max(0, toNumber(limit, 1024)), 1024));

  const runCommand = async (commandName, commandArgs = []) => {
    const normalizedArgs = Array.isArray(commandArgs) ? commandArgs.map((arg) => String(arg)) : [];

    actions.push({
      type: 'run_command',
      command_name: requireString(commandName, 'runCommand(commandName)'),
      args: normalizedArgs,
    });

    return true;
  };

  const sendPhoto = async (chatId, photoUrl, options = {}) => {
    try {
      const targetChatId = requireChatId(chatId, 'sendPhoto(chatId, photoUrl)');
      const src = String(photoUrl || '').trim();
      if (!src) throw new Error('sendPhoto: photo URL or file_id is required.');
      const safeSrc = (/^https?:\/\//.test(src)) ? requireHttpsUrl(src, 'sendPhoto photoUrl') : src;
      const normalizedOptions = normalizePhotoOptions(options);

      if (!telegramBridgeUrl || !telegramBridgeSecret) {
        actions.push({
          type: 'photo',
          chat_id: targetChatId,
          photo_url: safeSrc,
          ...normalizedOptions,
        });

        return { ok: true, result: null, queued: true };
      }

      return telegramRuntimeAction('telegram.sendPhoto', { chat_id: targetChatId, photo: safeSrc, ...normalizedOptions });
    } catch (err) {
      return { ok: false, error: String((err && err.message) || err) };
    }
  };

  const replyPhoto = async (photo, options = {}) => sendPhoto(safeChat.id, photo, options);

  const sendDocument = async (chatId, document, options = {}) => {
    try {
      const targetChatId = requireChatId(chatId, 'sendDocument(chatId, document)');
      const src = String(document || '').trim();
      if (!src) throw new Error('sendDocument: document URL or file_id is required.');
      const safeSrc = (/^https?:\/\//.test(src)) ? requireHttpsUrl(src, 'sendDocument') : src;
      const normalizedOptions = normalizePhotoOptions(options);

      if (!telegramBridgeUrl || !telegramBridgeSecret) {
        actions.push({
          type: 'document',
          chat_id: targetChatId,
          document_url: safeSrc,
          ...normalizedOptions,
        });

        return { ok: true, result: null, queued: true };
      }

      return telegramRuntimeAction('telegram.sendDocument', { chat_id: targetChatId, document: safeSrc, ...normalizedOptions });
    } catch (err) { return { ok: false, error: String((err && err.message) || err) }; }
  };

  const replyDocument = async (document, options = {}) => sendDocument(safeChat.id, document, options);

  const sendVideo = async (chatId, video, options = {}) => {
    try {
      const targetChatId = requireChatId(chatId, 'sendVideo(chatId, video)');
      const src = String(video || '').trim();
      if (!src) throw new Error('sendVideo: video URL or file_id is required.');
      const safeSrc = (/^https?:\/\//.test(src)) ? requireHttpsUrl(src, 'sendVideo') : src;
      actions.push({ type: 'video', chat_id: targetChatId, video: safeSrc, ...normalizePhotoOptions(options) });
      return { ok: true };
    } catch (err) { return { ok: false, error: String((err && err.message) || err) }; }
  };

  const sendAudio = async (chatId, audio, options = {}) => {
    try {
      const targetChatId = requireChatId(chatId, 'sendAudio(chatId, audio)');
      const src = String(audio || '').trim();
      if (!src) throw new Error('sendAudio: audio URL or file_id is required.');
      const safeSrc = (/^https?:\/\//.test(src)) ? requireHttpsUrl(src, 'sendAudio') : src;
      actions.push({ type: 'audio', chat_id: targetChatId, audio: safeSrc, ...normalizePhotoOptions(options) });
      return { ok: true };
    } catch (err) { return { ok: false, error: String((err && err.message) || err) }; }
  };

  const sendAnimation = async (chatId, animation, options = {}) => {
    try {
      const targetChatId = requireChatId(chatId, 'sendAnimation(chatId, animation)');
      const src = String(animation || '').trim();
      if (!src) throw new Error('sendAnimation: animation URL or file_id is required.');
      const safeSrc = (/^https?:\/\//.test(src)) ? requireHttpsUrl(src, 'sendAnimation') : src;
      actions.push({ type: 'animation', chat_id: targetChatId, animation: safeSrc, ...normalizePhotoOptions(options) });
      return { ok: true };
    } catch (err) { return { ok: false, error: safeTelegramError(err) }; }
  };

  const sendSticker = async (chatId, sticker) => {
    try {
      const targetChatId = requireChatId(chatId, 'sendSticker(chatId, sticker)');
      const src = String(sticker || '').trim();
      if (!src) throw new Error('sendSticker: sticker file_id or URL is required.');
      actions.push({ type: 'sticker', chat_id: targetChatId, sticker: src });
      return { ok: true };
    } catch (err) { return { ok: false, error: String((err && err.message) || err) }; }
  };

  const sendLocation = async (chatId, latitude, longitude) => {
    try {
      const targetChatId = requireChatId(chatId, 'sendLocation(chatId, lat, lng)');
      const lat = Number(latitude);
      const lng = Number(longitude);
      if (!Number.isFinite(lat) || !Number.isFinite(lng)) throw new Error('sendLocation: latitude and longitude must be numbers.');
      actions.push({ type: 'location', chat_id: targetChatId, latitude: lat, longitude: lng });
      return { ok: true };
    } catch (err) { return { ok: false, error: String((err && err.message) || err) }; }
  };

  const sendContact = async (chatId, phoneNumber, firstName, options = {}) => {
    try {
      const targetChatId = requireChatId(chatId, 'sendContact(chatId, phone, firstName)');
      const phone = String(phoneNumber || '').trim();
      const name = String(firstName || '').trim();
      if (!phone) throw new Error('sendContact: phoneNumber is required.');
      if (!name) throw new Error('sendContact: firstName is required.');
      const opts = normalizeObject(options, 'sendContact options');
      actions.push({
        type: 'contact',
        chat_id: targetChatId,
        phone_number: phone,
        first_name: name,
        last_name: typeof opts.last_name === 'string' ? opts.last_name : null,
      });
      return { ok: true };
    } catch (err) { return { ok: false, error: String((err && err.message) || err) }; }
  };

  const copyMessage = async (chatId, fromChatId, messageId, options = {}) => {
    try {
      const opts = normalizeObject(options, 'copyMessage options');
      actions.push({
        type: 'copy_message',
        chat_id: requireChatId(chatId, 'copyMessage chatId'),
        from_chat_id: requireChatId(fromChatId, 'copyMessage fromChatId'),
        message_id: requireMessageId(messageId, 'copyMessage messageId'),
        caption: typeof opts.caption === 'string' ? opts.caption : null,
        parse_mode: typeof opts.parse_mode === 'string' ? opts.parse_mode : null,
        reply_markup: isPlainObject(opts.reply_markup) ? plainObject(opts.reply_markup) : null,
      });
      return { ok: true };
    } catch (err) { return { ok: false, error: String((err && err.message) || err) }; }
  };

  const forwardMessage = async (chatId, fromChatId, messageId, options = {}) => {
    try {
      const opts = normalizeObject(options, 'forwardMessage options');
      actions.push({
        type: 'forward_message',
        chat_id: requireChatId(chatId, 'forwardMessage chatId'),
        from_chat_id: requireChatId(fromChatId, 'forwardMessage fromChatId'),
        message_id: requireMessageId(messageId, 'forwardMessage messageId'),
        disable_notification: Boolean(opts.disable_notification),
        protect_content: Boolean(opts.protect_content),
      });
      return { ok: true };
    } catch (err) { return { ok: false, error: String((err && err.message) || err) }; }
  };

  // editMessageText supports both signatures:
  //   editMessageText(chatId, messageId, text, options)  — new, explicit
  //   editMessageText(text, options)                     — old, backward-compat
  const editMessageText = async (...args) => {
    try {
      let chatId, messageId, text, options;
      if (args.length >= 3 && typeof args[2] === 'string') {
        [chatId, messageId, text, options = {}] = args;
      } else {
        [text, options = {}] = args;
        chatId = null;
        messageId = null;
      }
      const optionObject = normalizeObject(options, 'editMessageText options');
      return telegramRuntimeAction('telegram.editMessageText', {
        chat_id: chatId || optionObject.chat_id || safeChat.id,
        message_id: messageId || optionObject.message_id || safeMessage.message_id,
        text: requireString(text, 'editMessageText'),
        ...normalizeMessageOptions(options),
      });
    } catch (err) { return { ok: false, error: String((err && err.message) || err) }; }
  };

  const editMessageCaption = async (chatId, messageId, caption, options = {}) => {
    try {
      return telegramRuntimeAction('telegram.editMessageCaption', {
        chat_id: chatId || safeChat.id,
        message_id: messageId || safeMessage.message_id,
        caption: String(caption ?? ''),
        ...normalizePhotoOptions(options),
      });
    } catch (err) { return { ok: false, error: String((err && err.message) || err) }; }
  };

  const editMessageReplyMarkup = async (chatId, messageId, replyMarkup = null) => {
    try {
      return telegramRuntimeAction('telegram.editMessageReplyMarkup', {
        chat_id: chatId || safeChat.id,
        message_id: messageId || safeMessage.message_id,
        reply_markup: replyMarkup || null,
      });
    } catch (err) { return { ok: false, error: String((err && err.message) || err) }; }
  };

  // deleteMessage supports both signatures:
  //   deleteMessage(chatId, messageId)  — new, explicit
  //   deleteMessage(messageId)          — old, backward-compat
  const deleteMessage = async (...args) => {
    try {
      let chatId, messageId;
      if (args.length >= 2) {
        [chatId, messageId] = args;
      } else {
        chatId = null;
        messageId = args[0];
      }
      return telegramRuntimeAction('telegram.deleteMessage', {
        chat_id: chatId || safeChat.id,
        message_id: requireMessageId(messageId || safeMessage.message_id, 'deleteMessage'),
      });
    } catch (err) { return { ok: false, error: String((err && err.message) || err) }; }
  };

  const answerCallbackQuery = async (text = '', options = {}) => {
    if (!telegram.callback_query_id) return { ok: false, error: 'No callback query in progress.' };
    try {
      const opts = normalizeObject(options, 'answerCallbackQuery options');
      return telegramRuntimeAction('telegram.answerCallbackQuery', {
        callback_query_id: String(opts.callback_query_id || telegram.callback_query_id),
        text: String(text || ''),
        show_alert: opts.show_alert === true,
        url: typeof opts.url === 'string' ? opts.url : null,
        cache_time: typeof opts.cache_time === 'number' ? Math.floor(opts.cache_time) : 0,
      });
    } catch (err) { return { ok: false, error: String((err && err.message) || err) }; }
  };

  const getChatMember = async (channelUsernameOrId, userId = safeUser.id) => {
    const startedAt = Date.now();
    try {
      const args = normalizeChannelMemberArgs(channelUsernameOrId, userId, 'getChatMember(channel, userId)');
      const chatId = args.chat_id;
      const targetUserId = args.user_id;

      console.log('[BotHost] getChatMember_request', JSON.stringify({
        bot_id: safeBot.id,
        channel: String(channelUsernameOrId),
        target_user_id: targetUserId,
      }));

      const response = await telegramRuntimeAction('telegram.getChatMember', {
        chat_id: chatId,
        user_id: targetUserId,
      });

      console.log('[BotHost] getChatMember_result', JSON.stringify({
        bot_id: safeBot.id,
        channel: String(channelUsernameOrId),
        target_user_id: targetUserId,
        ok: !!response.ok,
        status: (response.result && response.result.status) ? response.result.status : null,
        error: !response.ok ? (response.error || response.description || 'unknown') : null,
        elapsed_ms: Date.now() - startedAt,
      }));

      if (!response || !response.ok) {
        return {
          ok: false,
          is_member: false,
          status: 'unknown',
          error: String((response && (response.error || response.description)) || 'Telegram getChatMember failed.'),
          elapsed_ms: Date.now() - startedAt,
        };
      }

      const member = plainObject(response.result || {});
      const status = String(member.status || 'unknown');
      const isMember = isTelegramMembershipStatus(status) || (status === 'restricted' && !!member.is_member);
      const resolvedUserId = (member.user && member.user.id != null) ? String(member.user.id) : String(targetUserId);
      return {
        ok: true,
        is_member: isMember,
        status,
        user_id: resolvedUserId,
        channel: String(chatId),
        member,
        elapsed_ms: Date.now() - startedAt,
      };
    } catch (error) {
      return { ok: false, is_member: false, status: 'unknown', error: String((error && error.message) || error || 'Telegram getChatMember failed.'), elapsed_ms: Date.now() - startedAt };
    }
  };

  const isChannelMember = async (channelUsernameOrId, userId = safeUser.id) => {
    const result = await checkChannelMember(channelUsernameOrId, userId);
    return !!result.is_member;
  };

  const checkChannelMember = async (channelUsernameOrId, userId = safeUser.id) => {
    const startedAt = Date.now();
    try {
      const args = normalizeChannelMemberArgs(channelUsernameOrId, userId, 'checkChannelMember(channel, userId)');
      const chatId = args.chat_id;
      const targetUserId = args.user_id;

      const response = await telegramRuntimeAction('telegram.checkChannelMember', {
        chat_id: chatId,
        user_id: targetUserId,
      });

      const elapsedMs = Date.now() - startedAt;
      const isOk = !!response.ok;
      const isBridgeTimeout = !isOk && response.error_type === 'TelegramBridgeTimeout';

      let result;
      if (isBridgeTimeout) {
        result = {
          ok: false,
          is_member: false,
          status: 'unknown',
          user_id: String(targetUserId),
          channel: String(chatId),
          error: 'Telegram getChatMember request timed out. The Telegram API did not respond in time.',
          stage: 'telegram_get_chat_member',
          elapsed_ms: elapsedMs,
        };
      } else {
        const msgText = String(
          response.message || response.error ||
          (response.result && response.result.message) ||
          (isOk ? 'Telegram membership check completed.' : 'Telegram membership check failed.')
        );
        const status = String(response.status ?? (response.result && response.result.status) ?? 'unknown');
        result = isOk
          ? {
              ok: true,
              is_member: isTelegramMembershipStatus(status) || !!(response.is_member ?? (response.result && response.result.is_member)),
              status,
              user_id: String(targetUserId),
              channel: String(chatId),
              message: msgText,
              elapsed_ms: elapsedMs,
            }
          : { ok: false, is_member: false, status: String(response.status ?? 'unknown'), user_id: String(targetUserId), channel: String(chatId), error: msgText, elapsed_ms: elapsedMs };
      }

      console.log('[BotHost] checkChannelMember_result', JSON.stringify({
        helper: 'checkChannelMember',
        bot_id: safeBot.id,
        command_id: safeCommand.id,
        command_name: safeCommand.name,
        channel: String(channelUsernameOrId),
        user_id: targetUserId,
        ok: result.ok,
        status: result.status,
        is_member: result.is_member,
        stage: result.stage || (result.ok ? 'done' : 'telegram_api'),
        error: result.ok ? null : (result.error || null),
        elapsed_ms: elapsedMs,
      }));

      return result;
    } catch (error) {
      return {
        ok: false,
        is_member: false,
        status: 'unknown',
        error: String((error && error.message) || error || 'Telegram membership check failed.'),
        stage: 'input_validation',
        elapsed_ms: Date.now() - startedAt,
      };
    }
  };

  const verifyTelegramChannel = checkChannelMember;

  const getUserData = async (key, defaultValue = null) => {
    const normalizedKey = requireStorageKey(key);

    if (userData.has(normalizedKey)) {
      return userData.get(normalizedKey);
    }

    const value = await storageRuntimeGet('user.get', normalizedKey, defaultValue);
    userData.set(normalizedKey, value);
    return value;
  };

  const setUserData = async (key, value) => {
    const normalizedKey = requireStorageKey(key);
    const normalizedValue = setStorageValue(userData, userMutations, normalizedKey, value);

    if (isImmediateSupportUserStorageKey(normalizedKey)) {
      await storageRuntimeSet('user.set', normalizedKey, normalizedValue);
    }

    return normalizedValue;
  };

  const incrementUserData = async (key, amount = 1) => {
    try {
      const normalizedKey = requireStorageKey(key);
      const current = Number(userData.has(normalizedKey) ? userData.get(normalizedKey) : 0);
      const delta = Number(amount);

      if (!Number.isFinite(current) || !Number.isFinite(delta)) {
        return { ok: false, error: 'incrementUserData(key, amount) requires numeric values.' };
      }

      return setStorageValue(userData, userMutations, normalizedKey, current + delta);
    } catch (err) {
      return { ok: false, error: String((err && err.message) || err) };
    }
  };

  const clearUserData = async (key) => clearStorageValue(userData, userMutations, key);
  const removeUserData = clearUserData;

  const pushUserData = async (key, item, limit = 50) => {
    try {
      const normalizedKey = requireStorageKey(key);
      const safeLimit = Math.max(1, Math.min(Math.floor(toNumber(limit, 50)), 500));
      const current = userData.has(normalizedKey) ? userData.get(normalizedKey) : [];
      const updated = [...(Array.isArray(current) ? current : []), jsonSafeValue(item)].slice(-safeLimit);
      setStorageValue(userData, userMutations, normalizedKey, updated);
      return { ok: true, value: updated };
    } catch (err) {
      return { ok: false, error: String((err && err.message) || err) };
    }
  };

  const getBotData = async (key, defaultValue = null) => {
    const normalizedKey = requireStorageKey(key);

    if (botData.has(normalizedKey)) {
      return botData.get(normalizedKey);
    }

    const value = await storageRuntimeGet('bot.get', normalizedKey, defaultValue);
    botData.set(normalizedKey, value);
    return value;
  };

  const setBotData = async (key, value) => {
    const normalizedKey = requireStorageKey(key);
    const normalizedValue = jsonSafeValue(value);
    if (isSecretStorageKey(normalizedKey)) {
      const secretValue = String(normalizedValue || '').trim();
      if (secretValue) runtimeSecrets.set(normalizedKey, secretValue);
      else runtimeSecrets.delete(normalizedKey);
    }
    botData.set(normalizedKey, isSecretStorageKey(normalizedKey) ? maskSecretValue(String(normalizedValue || '')) : normalizedValue);
    botMutations.push({ op: 'set', key: normalizedKey, value: normalizedValue });
    console.log('[BotHost] bot_data_mutation_created', JSON.stringify({
      bot_id: safeBot.id,
      key: normalizedKey,
      operation: 'set',
    }));
    if (isImmediateSupportBotStorageKey(normalizedKey)) {
      await storageRuntimeSet('bot.set', normalizedKey, normalizedValue);
    }

    return botData.get(normalizedKey);
  };

  const incrementBotData = async (key, amount = 1) => {
    try {
      const normalizedKey = requireStorageKey(key);
      const current = Number(botData.has(normalizedKey) ? botData.get(normalizedKey) : 0);
      const delta = Number(amount);

      if (!Number.isFinite(current) || !Number.isFinite(delta)) {
        return { ok: false, error: 'incrementBotData(key, amount) requires numeric values.' };
      }

      return setStorageValue(botData, botMutations, normalizedKey, current + delta);
    } catch (err) {
      return { ok: false, error: String((err && err.message) || err) };
    }
  };

  const clearBotData = async (key) => clearStorageValue(botData, botMutations, key);
  const removeBotData = clearBotData;

  const pushBotData = async (key, item, limit = 100) => {
    try {
      const normalizedKey = requireStorageKey(key);
      const safeLimit = Math.max(1, Math.min(Math.floor(toNumber(limit, 100)), 1000));
      const current = botData.has(normalizedKey) ? botData.get(normalizedKey) : [];
      const updated = [...(Array.isArray(current) ? current : []), jsonSafeValue(item)].slice(-safeLimit);
      setStorageValue(botData, botMutations, normalizedKey, updated);
      return { ok: true, value: updated };
    } catch (err) {
      return { ok: false, error: String((err && err.message) || err) };
    }
  };

  const generateCaptcha = async () => {
    const left = random(1, 9);
    const right = random(1, 9);
    const answer = left + right;

    await setUserData('captcha_answer', answer);

    return {
      question: `${left} + ${right} = ?`,
      answer,
    };
  };

  const verifyCaptcha = async (input) => {
    const expected = await getUserData('captcha_answer', null);

    return expected !== null && String(input).trim() === String(expected).trim();
  };

  // ── WORKFLOW / STATE HELPERS ────────────────────────────────────────────────
  const setState = async (state, data = {}) => {
    await setUserData('workflow_state', state !== null ? String(state) : null);
    await setUserData('workflow_state_data', data && typeof data === 'object' ? jsonSafeValue(data) : {});
    await setUserData('workflow_state_started_at', new Date().toISOString());
    await clearUserData('workflow_state_expires_at');
  };

  const getState = async (defaultValue = null) => {
    return getUserData('workflow_state', defaultValue);
  };

  const clearState = async () => {
    await clearUserData('workflow_state');
    await clearUserData('workflow_state_data');
    await clearUserData('workflow_state_started_at');
    await clearUserData('workflow_state_expires_at');
  };

  const setStateData = async (key, value) => {
    const k = requireStorageKey(key);
    const current = plainObject(await getUserData('workflow_state_data', null) || {});
    current[k] = jsonSafeValue(value);
    await setUserData('workflow_state_data', current);
  };

  const getStateData = async (key, fallback = null) => {
    const k = requireStorageKey(key);
    const data = plainObject(await getUserData('workflow_state_data', null) || {});
    return k in data ? data[k] : jsonSafeValue(fallback);
  };

  const clearStateData = async (key = null) => {
    if (key === null) {
      await setUserData('workflow_state_data', {});
    } else {
      const k = requireStorageKey(key);
      const data = plainObject(await getUserData('workflow_state_data', null) || {});
      delete data[k];
      await setUserData('workflow_state_data', data);
    }
  };

  const hasState = async (state = null) => {
    const current = await getState(null);
    if (state === null || state === undefined) return current !== null && current !== '';
    return String(current) === String(state);
  };

  const expireStateAfter = async (minutes) => {
    const mins = Math.max(0, toNumber(minutes, 0));
    const expiresAt = new Date(Date.now() + mins * 60000).toISOString();
    await setUserData('workflow_state_expires_at', expiresAt);
    return { ok: true, expires_at: expiresAt };
  };

  const isStateExpired = async (minutes = 30) => {
    const explicit = await getUserData('workflow_state_expires_at', null);
    if (explicit) {
      const explicitMs = new Date(String(explicit)).getTime();
      return !Number.isFinite(explicitMs) || Date.now() > explicitMs;
    }

    const startedAt = await getUserData('workflow_state_started_at', null);
    if (!startedAt) return false;
    const startedMs = new Date(String(startedAt)).getTime();
    if (!Number.isFinite(startedMs)) return true;
    return Date.now() - startedMs > Math.max(1, toNumber(minutes, 30)) * 60000;
  };

  const ask = async (question, nextState, options = {}) => {
    await setState(nextState);
    return sendMessage(safeChat.id, String(question || ''), options);
  };

  const askInCommand = async (question, step, data = {}, options = {}) => {
    const nextStep = requireStorageKey(step);
    const flowData = data && typeof data === 'object' ? jsonSafeValue(data) : {};

    await setUserData('awaiting_command_id', safeCommand.id ?? null);
    await setUserData('awaiting_command_name', safeCommand.trigger ?? safeCommand.name ?? null);
    await setUserData('awaiting_command_step', nextStep);
    await setUserData('awaiting_command_data', flowData);
    await setUserData('awaiting_command_started_at', new Date().toISOString());

    return sendMessage(safeChat.id, String(question || ''), options);
  };

  const getCommandStep = async () => getUserData('awaiting_command_step', null);

  const getCommandData = async (key = null, fallback = null) => {
    const data = plainObject(await getUserData('awaiting_command_data', null) || {});

    if (key === null) {
      return data;
    }

    const k = requireStorageKey(key);
    return k in data ? data[k] : jsonSafeValue(fallback);
  };

  const setCommandData = async (key, value) => {
    const k = requireStorageKey(key);
    const data = plainObject(await getUserData('awaiting_command_data', null) || {});
    data[k] = jsonSafeValue(value);
    await setUserData('awaiting_command_data', data);
    return data[k];
  };

  const clearCommandFlow = async () => {
    await clearUserData('awaiting_command_id');
    await clearUserData('awaiting_command_name');
    await clearUserData('awaiting_command_step');
    await clearUserData('awaiting_command_data');
    await clearUserData('awaiting_command_started_at');
  };

  const hasCommandFlow = async () => {
    const commandId = await getUserData('awaiting_command_id', null);
    const commandName = await getUserData('awaiting_command_name', null);
    return commandId !== null || !!commandName;
  };

  const isCommandFlowExpired = async (minutes = 30) => {
    const startedAt = await getUserData('awaiting_command_started_at', null);
    if (!startedAt) return false;
    const startedMs = new Date(String(startedAt)).getTime();
    if (!Number.isFinite(startedMs)) return true;
    const limitMs = Math.max(1, toNumber(minutes, 30)) * 60000;
    return Date.now() - startedMs > limitMs;
  };

  const continueCommand = async (commandName = null) => {
    const target = commandName !== null ? String(commandName) : await getUserData('awaiting_command_name', safeCommand.trigger ?? safeCommand.name ?? null);
    if (!target) return false;
    return runCommand(target);
  };

  const cancelAllFlows = async () => {
    await clearCommandFlow();
    await clearState();
    await clearUserData('admin_state');
    await clearUserData('awaiting_wallet');
    await clearUserData('awaiting_withdraw_amount');
  };

  const cancelWaitingStates = async () => {
    await clearCommandFlow();
    await clearState();
    return { ok: true };
  };

  const requireState = async (expectedState) => {
    const current = await getUserData('workflow_state', null);
    return current === String(expectedState);
  };

  const normalizeIdList = (ids = []) => {
    if (ids === null || ids === undefined) return [];
    const list = Array.isArray(ids) ? ids : String(ids).split(',');
    return list.map((id) => String(id).trim()).filter(Boolean);
  };

  const isOwner = (ownerId) => ownerId !== null && ownerId !== undefined && String(safeUser.id) === String(ownerId);
  const isAdmin = (adminIds = []) => normalizeIdList(adminIds).includes(String(safeUser.id));
  const requireOwner = (ownerId) => isOwner(ownerId);
  const requireAdmin = (adminIds = []) => isAdmin(adminIds) || isOwner(adminIds);

  const requireVerified = async () => {
    const captchaPassed = await getUserData('captcha_passed', false);
    const channelsVerified = await getUserData('channels_verified', false);
    return captchaPassed === true && channelsVerified === true;
  };
  // ── END WORKFLOW HELPERS ────────────────────────────────────────────────────

  // ── USER / BOT MANAGEMENT HELPERS ──────────────────────────────────────────
  const _isSelf = (uid) => !uid || String(uid) === String(safeUser.id);

  const getBalance = async (userId = null) => {
    return toNumber(_isSelf(userId) ? await getUserData('balance', 0) : await getUserDataFor(String(userId), 'balance', 0), 0);
  };

  const setBalance = async (amount, userId = null) => {
    const val = Math.max(0, toNumber(amount, 0));
    const previous = await getBalance(userId);
    if (_isSelf(userId)) {
      await setUserData('balance', val);
    } else {
      await setUserDataFor(String(userId), 'balance', val);
    }
    return { ok: true, previous_balance: previous, amount: val, new_balance: val };
  };

  const addBalance = async (amount, userId = null) => {
    const delta = toNumber(amount, 0);
    if (delta <= 0) return { ok: false, error: 'Amount must be positive.' };
    const previous = await getBalance(userId);
    if (_isSelf(userId)) {
      await incrementUserData('balance', delta);
    } else {
      await incrementUserDataFor(String(userId), 'balance', delta);
    }
    return { ok: true, previous_balance: previous, amount: delta, new_balance: previous + delta };
  };

  const removeBalance = async (amount, userId = null) => {
    const delta = Math.abs(toNumber(amount, 0));
    if (delta <= 0) return { ok: false, error: 'Amount must be positive.' };
    if (_isSelf(userId)) {
      const current = toNumber(await getUserData('balance', 0), 0);
      const removed = Math.min(current, delta);
      const next = Math.max(0, current - delta);
      await setUserData('balance', next);
      return { ok: true, previous_balance: current, amount: delta, removed_amount: removed, new_balance: next };
    }
    const uid = String(userId);
    const current = toNumber(await getUserDataFor(uid, 'balance', 0), 0);
    const removed = Math.min(current, delta);
    const next = Math.max(0, current - delta);
    await setUserDataFor(uid, 'balance', next);
    return { ok: true, previous_balance: current, amount: delta, removed_amount: removed, new_balance: next };
  };

  const transferBalance = async (fromUserId, toUserId, amount, note = '') => {
    try {
      const from = normalizeCrossUserId(fromUserId, 'transferBalance(fromUserId, toUserId, amount)');
      const to = normalizeCrossUserId(toUserId, 'transferBalance(fromUserId, toUserId, amount)');
      const delta = Math.abs(toNumber(amount, 0));
      if (delta <= 0) return { ok: false, error: 'Amount must be positive.' };
      if (from === to) return { ok: false, error: 'From and to users must be different.' };

      const fromBalance = await getBalance(from);
      const moved = Math.min(fromBalance, delta);
      const fromNext = Math.max(0, fromBalance - delta);
      const toBalance = await getBalance(to);
      const toNext = toBalance + moved;

      await setBalance(fromNext, from);
      await setBalance(toNext, to);

      return {
        ok: true,
        from_user_id: from,
        to_user_id: to,
        requested_amount: delta,
        transferred_amount: moved,
        from_previous_balance: fromBalance,
        from_new_balance: fromNext,
        to_previous_balance: toBalance,
        to_new_balance: toNext,
        note: safeText(note, ''),
      };
    } catch (err) {
      return { ok: false, error: String((err && err.message) || err) };
    }
  };

  const getWallet = async (userId = null) => {
    return _isSelf(userId) ? getUserData('wallet', null) : getUserDataFor(String(userId), 'wallet', null);
  };

  const setWallet = async (wallet, userId = null) => {
    const val = sanitizeText(String(wallet || ''), 200).trim() || null;
    return _isSelf(userId) ? setUserData('wallet', val) : setUserDataFor(String(userId), 'wallet', val);
  };

  const isBanned = async (userId = null) => {
    const val = _isSelf(userId) ? await getUserData('banned', false) : await getUserDataFor(String(userId), 'banned', false);
    return val === true;
  };

  const banUser = async (userId, reason = null) => {
    const uid = String(requireChatId(userId, 'banUser(userId)'));
    const reasonVal = reason ? sanitizeText(String(reason), 200) : null;
    const banTime = new Date().toISOString();
    if (_isSelf(uid)) {
      await setUserData('banned', true);
      await setUserData('ban_reason', reasonVal);
      await setUserData('banned_at', banTime);
    } else {
      await setUserDataFor(uid, 'banned', true);
      await setUserDataFor(uid, 'ban_reason', reasonVal);
      await setUserDataFor(uid, 'banned_at', banTime);
    }
    return true;
  };

  const unbanUser = async (userId) => {
    const uid = String(requireChatId(userId, 'unbanUser(userId)'));
    if (_isSelf(uid)) {
      await setUserData('banned', false);
      await setUserData('ban_reason', null);
    } else {
      await setUserDataFor(uid, 'banned', false);
      await setUserDataFor(uid, 'ban_reason', null);
    }
    return true;
  };

  const getReferrer = async (userId = null) => {
    return _isSelf(userId) ? getUserData('referred_by', null) : getUserDataFor(String(userId), 'referred_by', null);
  };

  const setReferrer = async (referrerId, userId = null) => {
    const refId = String(referrerId || '').trim();
    if (!refId) return false;
    const uid = userId !== null ? String(userId) : String(safeUser.id);
    if (refId === uid) return false; // self-referral
    const existing = _isSelf(uid) ? await getUserData('referred_by', null) : await getUserDataFor(uid, 'referred_by', null);
    if (existing) return false;
    if (_isSelf(uid)) { await setUserData('referred_by', refId); } else { await setUserDataFor(uid, 'referred_by', refId); }
    return true;
  };

  const rewardReferrer = async (amount = null, userId = null) => {
    const uid = userId !== null ? String(userId) : String(safeUser.id);
    const isSelfUser = _isSelf(uid);
    const referrerId = isSelfUser ? await getUserData('referred_by', null) : await getUserDataFor(uid, 'referred_by', null);
    console.log('[BotHost] referral_reward_start', JSON.stringify({ bot_id: safeBot.id, user_id: uid, referrer_id: referrerId }));
    if (!referrerId) {
      console.log('[BotHost] referral_reward_failed', JSON.stringify({ bot_id: safeBot.id, user_id: uid, reason: 'no_referrer' }));
      return { ok: false, reason: 'no_referrer' };
    }
    if (referrerId === uid) {
      console.log('[BotHost] referral_reward_failed', JSON.stringify({ bot_id: safeBot.id, user_id: uid, reason: 'self_referral' }));
      return { ok: false, reason: 'self_referral' };
    }
    const alreadyRewarded = isSelfUser ? await getUserData('ref_rewarded', false) : await getUserDataFor(uid, 'ref_rewarded', false);
    if (alreadyRewarded === true) {
      console.log('[BotHost] referral_reward_failed', JSON.stringify({ bot_id: safeBot.id, user_id: uid, referrer_id: referrerId, reason: 'already_rewarded' }));
      return { ok: false, reason: 'already_rewarded' };
    }
    const reward = amount !== null ? toNumber(amount, 0) : toNumber(await getBotData('referral_reward', 500), 500);
    if (reward <= 0) {
      console.log('[BotHost] referral_reward_failed', JSON.stringify({ bot_id: safeBot.id, user_id: uid, referrer_id: referrerId, reason: 'invalid_amount', reward }));
      return { ok: false, reason: 'invalid_amount' };
    }
    const beforeBalance = toNumber(await getUserDataFor(referrerId, 'balance', 0), 0);
    console.log('[BotHost] referral_reward_before_balance', JSON.stringify({ bot_id: safeBot.id, user_id: uid, referrer_id: referrerId, reward, before_balance: beforeBalance }));
    await incrementUserDataFor(referrerId, 'balance', reward);
    await incrementUserDataFor(referrerId, 'referrals', 1);
    if (isSelfUser) { await setUserData('ref_rewarded', true); } else { await setUserDataFor(uid, 'ref_rewarded', true); }
    const afterBalance = toNumber(await getUserDataFor(referrerId, 'balance', 0), 0);
    console.log('[BotHost] referral_reward_saved', JSON.stringify({ bot_id: safeBot.id, user_id: uid, referrer_id: referrerId, reward, before_balance: beforeBalance, after_balance: afterBalance, ref_rewarded: true }));
    return { ok: true, referrer_id: referrerId, amount: reward, before_balance: beforeBalance, after_balance: afterBalance };
  };

  const generateId = (prefix = '') => {
    const ts = Date.now().toString(36);
    const rand = Math.random().toString(36).slice(2, 8);
    const p = String(prefix || '').replace(/[^a-z0-9_]/gi, '').toLowerCase().slice(0, 12);
    return p ? `${p}_${ts}${rand}` : `${ts}${rand}`;
  };

  const normalizeHistoryRecord = (type, data = {}) => {
    const raw = isPlainObject(data) ? plainObject(data) : {};
    return jsonSafeValue({
      id: raw.id || generateId(type || 'rec'),
      type: raw.type || type || 'history',
      status: raw.status || 'success',
      date: raw.date || raw.created_at || new Date().toISOString(),
      ...raw,
    });
  };

  const addHistory = async (userId, key, item, limit = 50) => {
    try {
      const uid = userId !== null && userId !== undefined ? String(userId) : String(safeUser.id);
      const storageKey = requireStorageKey(key);
      const safeLimit = Math.max(1, Math.min(toInteger(limit, 50), 500));
      const raw = _isSelf(uid) ? await getUserData(storageKey, []) : await getUserDataFor(uid, storageKey, []);
      const history = Array.isArray(raw) ? raw : [];
      const record = normalizeHistoryRecord(storageKey.replace(/_history$/u, ''), item);
      const next = [...history, record].slice(-safeLimit);
      if (_isSelf(uid)) await setUserData(storageKey, next);
      else await setUserDataFor(uid, storageKey, next);
      return { ok: true, user_id: uid, key: storageKey, record, count: next.length };
    } catch (err) {
      return { ok: false, error: String((err && err.message) || err) };
    }
  };

  const getHistory = async (userId, key, limit = 10) => {
    try {
      const uid = userId !== null && userId !== undefined ? String(userId) : String(safeUser.id);
      const storageKey = requireStorageKey(key);
      const safeLimit = Math.max(1, Math.min(toInteger(limit, 10), 500));
      const raw = _isSelf(uid) ? await getUserData(storageKey, []) : await getUserDataFor(uid, storageKey, []);
      const history = Array.isArray(raw) ? raw : [];
      return history.slice(-safeLimit);
    } catch (_) {
      return [];
    }
  };

  const addTransaction = async (userId = null, transaction = {}, limit = 50) => {
    const uid = userId !== null ? String(userId) : String(safeUser.id);
    const tx = normalizeHistoryRecord((transaction && transaction.type) || 'transaction', transaction);
    return addHistory(uid, 'transaction_history', tx, limit);
  };

  const getTransactions = async (userId = null, limit = 10) => getHistory(userId, 'transaction_history', limit);

  const getUserSummary = async (userId = null) => {
    const uid = userId !== null ? String(userId) : String(safeUser.id);
    const get = async (key, fb) => _isSelf(uid) ? getUserData(key, fb) : getUserDataFor(uid, key, fb);
    const wallet = await get('wallet', null);
    return {
      user_id: uid,
      balance: toNumber(await get('balance', 0), 0),
      wallet,
      wallet_masked: wallet ? maskEmail(wallet) : null,
      referrals: toNumber(await get('referrals', 0), 0),
      referred_by: await get('referred_by', null),
      banned: (await get('banned', false)) === true,
      captcha_passed: (await get('captcha_passed', false)) === true,
      channels_verified: (await get('channels_verified', false)) === true,
      joined_at: await get('joined_at', null),
    };
  };

  const incrementStat = async (key, amount = 1) => incrementBotData(key, amount);
  const getStat = async (key, fallback = 0) => getBotData(key, fallback);

  // ── CROSS-USER REFERRAL HELPERS ─────────────────────────────────────────────

  // addReferralReward(referrerId, reward, referredUserId, options)
  // Increments referrer balance + referrals count + pushes referral_history.
  // Uses atomic increment mutations so Laravel does read-add-save from DB, never stale data.
  const addReferralReward = async (referrerId, reward, referredUserId, options = {}) => {
    const rid   = String(referrerId   || '').trim();
    const refId = String(referredUserId || String(safeUser.id)).trim();
    if (!rid)                  return { ok: false, reason: 'no_referrer_id' };
    if (rid === refId)         return { ok: false, reason: 'self_referral' };
    const rewardAmt = toNumber(reward, 0);
    if (rewardAmt <= 0)        return { ok: false, reason: 'invalid_reward_amount' };
    const opts     = normalizeObject(options, 'addReferralReward options');
    const currency = typeof opts.currency === 'string' ? opts.currency : 'USDT';
    const alreadyRewarded = _isSelf(refId) ? await getUserData('ref_rewarded', false) : await getUserDataFor(refId, 'ref_rewarded', false);
    if (alreadyRewarded === true) return { ok: false, reason: 'already_rewarded' };
    const beforeBalance = toNumber(
      crossUsersData[rid] && crossUsersData[rid].has('balance') ? crossUsersData[rid].get('balance') : 0, 0,
    );
    console.log('[BotHost] cross_user_helper_called', JSON.stringify({
      bot_id: safeBot.id, helper: 'addReferralReward',
      referrer_id: rid, referred_user_id: refId, reward: rewardAmt, currency,
    }));
    const balResult = await incrementUserDataFor(rid, 'balance', rewardAmt);
    if (!balResult.ok) return { ok: false, reason: 'balance_increment_failed', error: balResult.error };
    await incrementUserDataFor(rid, 'referrals', 1);
    await incrementUserDataFor(rid, 'total_referral_rewards', rewardAmt);
    if (_isSelf(refId)) await setUserData('ref_rewarded', true);
    else await setUserDataFor(refId, 'ref_rewarded', true);
    await pushUserDataFor(rid, 'referral_history', {
      user_id: refId, reward: rewardAmt, currency,
      date: new Date().toISOString(),
      referred_name:     typeof opts.referred_name     === 'string' ? opts.referred_name     : null,
      referred_username: typeof opts.referred_username === 'string' ? opts.referred_username : null,
    }, 100);
    const afterBalance = toNumber(
      crossUsersData[rid] && crossUsersData[rid].has('balance') ? crossUsersData[rid].get('balance') : beforeBalance + rewardAmt,
      beforeBalance + rewardAmt,
    );
    console.log('[BotHost] cross_user_balance_after', JSON.stringify({
      bot_id: safeBot.id, referrer_id: rid, reward: rewardAmt,
      before: beforeBalance, after: afterBalance,
    }));
    return { ok: true, referrer_id: rid, reward: rewardAmt, currency, before_balance: beforeBalance, after_balance: afterBalance };
  };

  // recordReferral(referrerId, referredUserId, data)
  // Low-level helper: just pushes a referral_history entry without modifying balance.
  const recordReferral = async (referrerId, referredUserId, data = {}) => {
    const rid   = String(referrerId   || '').trim();
    const refId = String(referredUserId || '').trim();
    if (!rid || !refId) return { ok: false, error: 'Invalid referrer or referred user ID.' };
    const extra = isPlainObject(data) ? plainObject(data) : {};
    return pushUserDataFor(rid, 'referral_history', { user_id: refId, date: new Date().toISOString(), ...extra }, 100);
  };

  // getReferralStats(userId)
  // Returns referral count, history, balance, and total_referral_rewards for
  // the current user or a specified target (uses cross-user preloaded data).
  const getReferralStats = async (userId = null) => {
    const isTargetSelf = userId === null || userId === undefined || _isSelf(String(userId));
    if (!isTargetSelf) {
      const uid = String(userId);
      return {
        user_id: uid,
        referrals:          toNumber(await getUserDataFor(uid, 'referrals',              0), 0),
        referral_history:   await getUserDataFor(uid, 'referral_history',   []),
        balance:            await getBalance(uid),
        total_referral_rewards: toNumber(await getUserDataFor(uid, 'total_referral_rewards', 0), 0),
      };
    }
    return {
      user_id: String(safeUser.id),
      referrals:          toNumber(await getUserData('referrals',              0), 0),
      referral_history:   await getUserData('referral_history',   []),
      balance:            await getBalance(),
      total_referral_rewards: toNumber(await getUserData('total_referral_rewards', 0), 0),
    };
  };
  // ── END CROSS-USER REFERRAL HELPERS ────────────────────────────────────────

  const isUserBanned = async (userId = null) => isBanned(userId);
  const checkUser = async (userId) => getUserSummary(userId);
  const updateUserStatus = async (userId, status) => {
    const uid = String(requireChatId(userId, 'updateUserStatus(userId, status)'));
    const value = sanitizeText(String(status || ''), 50).trim() || 'active';
    if (_isSelf(uid)) await setUserData('account_status', value);
    else await setUserDataFor(uid, 'account_status', value);
    return { ok: true, user_id: uid, status: value };
  };

  const recordAdminCredit = async (userId, amount, note = '') => {
    const uid = String(requireChatId(userId, 'recordAdminCredit(userId, amount)'));
    const before = await getBalance(uid);
    const result = await addBalance(amount, uid);
    const record = normalizeHistoryRecord('admin_credit', {
      amount: toNumber(amount, 0), status: result.ok ? 'success' : 'failed', note: safeText(note, ''),
      before_balance: before, after_balance: result.new_balance ?? before,
    });
    await addTransaction(uid, record);
    return { ...result, user_id: uid, transaction: record };
  };

  const recordAdminDebit = async (userId, amount, note = '') => {
    const uid = String(requireChatId(userId, 'recordAdminDebit(userId, amount)'));
    const before = await getBalance(uid);
    const result = await removeBalance(amount, uid);
    const record = normalizeHistoryRecord('admin_debit', {
      amount: toNumber(amount, 0), status: result.ok ? 'success' : 'failed', note: safeText(note, ''),
      before_balance: before, after_balance: result.new_balance ?? before,
    });
    await addTransaction(uid, record);
    return { ...result, user_id: uid, transaction: record };
  };

  const recordWithdrawal = async (userId, amount, status, data = {}) => addTransaction(userId, normalizeHistoryRecord('withdrawal', { amount: toNumber(amount, 0), status: String(status || 'pending'), ...plainObject(data || {}) }));
  const recordDeposit = async (userId, amount, status, data = {}) => addTransaction(userId, normalizeHistoryRecord('deposit', { amount: toNumber(amount, 0), status: String(status || 'pending'), ...plainObject(data || {}) }));
  const recordBonus = async (userId, amount, data = {}) => addTransaction(userId, normalizeHistoryRecord('bonus', { amount: toNumber(amount, 0), status: 'success', ...plainObject(data || {}) }));
  const recordSupportMessage = async (userId, data = {}) => addHistory(userId, 'support_history', normalizeHistoryRecord('support_message', plainObject(data || {})), 50);

  const preventSelfReferral = (referrerId, userId) => String(referrerId || '').trim() !== String(userId || safeUser.id).trim();
  const hasReferralRewarded = async (userId = null) => (_isSelf(userId) ? await getUserData('ref_rewarded', false) : await getUserDataFor(String(userId), 'ref_rewarded', false)) === true;
  const markReferralRewarded = async (userId = null) => {
    const uid = userId !== null && userId !== undefined ? String(userId) : String(safeUser.id);
    if (_isSelf(uid)) await setUserData('ref_rewarded', true);
    else await setUserDataFor(uid, 'ref_rewarded', true);
    return { ok: true, user_id: uid };
  };
  const getReferralLink = (botUsername, userId = null) => {
    const username = String(botUsername || safeBot.username || '').replace(/^@/u, '').trim();
    const uid = userId !== null && userId !== undefined ? String(userId) : String(safeUser.id || '');
    return username && uid ? `https://t.me/${username}?start=${encodeURIComponent(uid)}` : '';
  };
  const getReferralHistory = async (userId = null, limit = 10) => getHistory(userId, 'referral_history', limit);
  const incrementReferralCount = async (userId = null) => {
    const uid = userId !== null && userId !== undefined ? String(userId) : String(safeUser.id);
    if (_isSelf(uid)) await incrementUserData('referrals', 1);
    else await incrementUserDataFor(uid, 'referrals', 1);
    return { ok: true, user_id: uid };
  };
  const addReferralEarning = async (userId, amount) => {
    const uid = userId !== null && userId !== undefined ? String(userId) : String(safeUser.id);
    const delta = toNumber(amount, 0);
    if (_isSelf(uid)) await incrementUserData('total_referral_rewards', delta);
    else await incrementUserDataFor(uid, 'total_referral_rewards', delta);
    return { ok: true, user_id: uid, amount: delta };
  };

  const calculateInvestmentProfit = (amount, percentValue) => toNumber(amount, 0) * toNumber(percentValue, 0) / 100;
  const calculateInvestmentReturn = (amount, percentValue) => toNumber(amount, 0) + calculateInvestmentProfit(amount, percentValue);
  const findInvestmentPlan = (amount, plans) => {
    const value = toNumber(amount, 0);
    const list = Array.isArray(plans) ? plans : [];
    return list.find((plan) => value >= toNumber(plan.min ?? plan.minimum ?? 0, 0) && (plan.max === undefined || plan.max === null || value <= toNumber(plan.max, Infinity))) || null;
  };
  const formatInvestmentPlans = (plans, currency = 'USDT') => (Array.isArray(plans) ? plans : []).map((plan, index) => {
    const name = plan.name || plan.plan_name || `Plan ${index + 1}`;
    return `${name}: ${formatMoney(plan.min ?? 0, currency)} - ${plan.max ? formatMoney(plan.max, currency) : 'no max'} | ${toNumber(plan.percent ?? plan.profit_percent ?? 0, 0)}%`;
  }).join('\n');
  const addInvestmentHistory = async (userId, data = {}) => addHistory(userId, 'investment_history', normalizeHistoryRecord('investment', plainObject(data || {})), 50);
  const createInvestment = async (userId, data = {}) => {
    const uid = userId !== null && userId !== undefined ? String(userId) : String(safeUser.id);
    const input = plainObject(data || {});
    const amount = toNumber(input.amount, 0);
    const percentValue = toNumber(input.percent ?? input.profit_percent, 0);
    const startedAt = input.started_at || new Date().toISOString();
    const durationHours = toNumber(input.duration_hours ?? input.hours, 24);
    const maturesAt = input.matures_at || new Date(Date.parse(startedAt) + Math.max(0, durationHours) * 3600000).toISOString();
    const investment = normalizeHistoryRecord('investment', {
      id: input.id || generateId('inv'), amount, plan_id: input.plan_id ?? null,
      plan_name: input.plan_name || input.name || null, percent: percentValue,
      profit: calculateInvestmentProfit(amount, percentValue),
      total_return: calculateInvestmentReturn(amount, percentValue),
      started_at: startedAt, matures_at: maturesAt, status: 'active', ...input,
    });
    const active = _isSelf(uid) ? await getUserData('active_investments', []) : await getUserDataFor(uid, 'active_investments', []);
    const next = [...(Array.isArray(active) ? active : []), investment].slice(-100);
    if (_isSelf(uid)) await setUserData('active_investments', next);
    else await setUserDataFor(uid, 'active_investments', next);
    await addInvestmentHistory(uid, investment);
    return { ok: true, user_id: uid, investment };
  };
  const getActiveInvestments = async (userId = null) => {
    const raw = _isSelf(userId) ? await getUserData('active_investments', []) : await getUserDataFor(String(userId), 'active_investments', []);
    return (Array.isArray(raw) ? raw : []).filter((inv) => String(inv.status || 'active') === 'active');
  };
  const getMaturedInvestments = async (userId = null) => {
    const nowMs = Date.now();
    return (await getActiveInvestments(userId)).filter((inv) => Date.parse(inv.matures_at || '') <= nowMs);
  };
  const claimMaturedInvestments = async (userId = null) => {
    const uid = userId !== null && userId !== undefined ? String(userId) : String(safeUser.id);
    const raw = _isSelf(uid) ? await getUserData('active_investments', []) : await getUserDataFor(uid, 'active_investments', []);
    const list = Array.isArray(raw) ? raw : [];
    const nowMs = Date.now();
    let total = 0;
    const claimed = [];
    const updated = list.map((inv) => {
      if (String(inv.status || 'active') === 'active' && Date.parse(inv.matures_at || '') <= nowMs) {
        const amount = toNumber(inv.total_return, 0);
        total += amount;
        const next = { ...inv, status: 'claimed', claimed_at: new Date().toISOString() };
        claimed.push(next);
        return next;
      }
      return inv;
    });
    if (total > 0) {
      if (_isSelf(uid)) {
        await incrementUserData('balance', total);
        await setUserData('active_investments', updated);
      } else {
        await incrementUserDataFor(uid, 'balance', total);
        await setUserDataFor(uid, 'active_investments', updated);
      }
      await addInvestmentHistory(uid, { type: 'investment_claim', total_return: total, count: claimed.length, claimed });
    }
    return { ok: true, user_id: uid, claimed_count: claimed.length, total_return: total, claimed };
  };
  const getInvestmentStats = async (userId = null) => {
    const active = await getActiveInvestments(userId);
    const history = await getHistory(userId, 'investment_history', 100);
    return {
      active_count: active.length,
      active_amount: active.reduce((sum, inv) => sum + toNumber(inv.amount, 0), 0),
      total_history: history.length,
      matured_count: (await getMaturedInvestments(userId)).length,
    };
  };

  const createTask = async (data = {}) => {
    const source = plainObject(data || {});
    const task = normalizeHistoryRecord('task', { status: 'active', ...source, id: source.id || generateId('task') });
    const tasks = await getBotData('tasks', []);
    const next = [...(Array.isArray(tasks) ? tasks : []), task].slice(-500);
    await setBotData('tasks', next);
    return { ok: true, task };
  };
  const getTasks = async (filters = {}) => {
    const opts = normalizeObject(filters, 'getTasks filters');
    const tasks = await getBotData('tasks', []);
    return (Array.isArray(tasks) ? tasks : []).filter((task) => !opts.status || String(task.status) === String(opts.status));
  };
  const submitTaskProof = async (userId, taskId, proof = {}) => addHistory(userId, 'task_submissions', normalizeHistoryRecord('task_submission', { task_id: String(taskId || ''), status: 'pending', proof: plainObject(proof || {}) }), 100);
  const approveTaskSubmission = async (submissionId) => ({ ok: false, error: 'Task submission approval requires a task review command or panel.', submission_id: String(submissionId || '') });
  const rejectTaskSubmission = async (submissionId, reason = '') => ({ ok: false, error: 'Task submission rejection requires a task review command or panel.', submission_id: String(submissionId || ''), reason: safeText(reason, '') });

  const getBotStats = async () => ({
    bot_id: safeBot.id,
    total_users: toNumber(await getBotData('total_users', 0), 0),
    total_deposits: toNumber(await getBotData('total_deposits', 0), 0),
    total_withdrawals: toNumber(await getBotData('total_withdrawals', 0), 0),
    total_invested: toNumber(await getBotData('total_invested', 0), 0),
    total_profit_paid: toNumber(await getBotData('total_profit_paid', 0), 0),
  });
  const getActiveUsers = async (hours = 24) => ({ ok: false, error: 'Active user query is not available inside command runtime.', hours: Math.max(1, toInteger(hours, 24)) });
  const scheduleJob = async (_name, _payload = {}, _runAt = null) => ({ ok: false, error: 'Scheduler not configured' });

  // getArg/hasArgs need access to the closure `args` array
  const getArg = (index, fallback = null) => {
    const i = Math.floor(toNumber(index, 0));
    return i >= 0 && i < args.length ? args[i] : jsonSafeValue(fallback);
  };
  const hasArgs = (count = 1) => args.length >= Math.max(1, Math.floor(toNumber(count, 1)));

  // Atomically increments a numeric key for another user in the same bot.
  // Returns {ok:true} on success; {ok:false, error} instead of throwing so
  // callers that pass a null referrer never crash the runtime.
  const incrementUserDataFor = async (telegramUserId, key, amount) => {
    try {
      const uid = normalizeCrossUserId(telegramUserId, 'incrementUserDataFor(userId, key, amount)');
      const k = requireStorageKey(key);
      const delta = toNumber(amount, 0);
      if (_isSelf(uid)) {
        await incrementUserData(k, delta);
        return { ok: true };
      }
      if (!crossUsersData[uid]) crossUsersData[uid] = new Map();
      const current = Number(crossUsersData[uid].has(k) ? crossUsersData[uid].get(k) : 0);
      crossUsersData[uid].set(k, current + delta);
      if (!crossUserMutations[uid]) crossUserMutations[uid] = [];
      // op:increment → Laravel reads current DB value and adds delta atomically
      crossUserMutations[uid].push({ op: 'increment', key: k, amount: delta });
      console.log('[BotHost] cross_user_mutation_created', JSON.stringify({
        bot_id: safeBot.id, target_user_id: uid, key: k,
        operation: 'increment', amount: delta, before: current, after: current + delta,
      }));
      return { ok: true };
    } catch (err) {
      return { ok: false, error: String((err && err.message) || err) };
    }
  };

  const setUserDataFor = async (telegramUserId, key, value) => {
    try {
      const uid = normalizeCrossUserId(telegramUserId, 'setUserDataFor(userId, key, value)');
      const k = requireStorageKey(key);
      const v = jsonSafeValue(value);
      if (_isSelf(uid)) {
        return setUserData(k, v);
      }
      if (!crossUsersData[uid]) crossUsersData[uid] = new Map();
      crossUsersData[uid].set(k, v);
      if (!crossUserMutations[uid]) crossUserMutations[uid] = [];
      crossUserMutations[uid].push({ op: 'set', key: k, value: v });
      console.log('[BotHost] cross_user_mutation_created', JSON.stringify({
        bot_id: safeBot.id, target_user_id: uid, key: k, operation: 'set',
      }));
      return { ok: true };
    } catch (err) {
      return { ok: false, error: String((err && err.message) || err) };
    }
  };

  const getUserDataFor = async (telegramUserId, key, defaultValue = null) => {
    const uid = normalizeCrossUserId(telegramUserId, 'getUserDataFor(userId, key, defaultValue)');
    const k = requireStorageKey(key);
    if (_isSelf(uid)) return getUserData(k, defaultValue);
    if (crossUsersData[uid] && crossUsersData[uid].has(k)) return crossUsersData[uid].get(k);
    if (!crossUsersData[uid]) crossUsersData[uid] = new Map();
    const value = await storageRuntimeGet('user.get', k, defaultValue, uid);
    crossUsersData[uid].set(k, value);
    return value;
  };

  const findUserByData = async (key, value) => {
    try {
      const result = await storageRuntimeFindUser(key, value);
      if (result && result.ok && result.found && result.user_id !== undefined && result.user_id !== null) {
        return { ok: true, found: true, user_id: String(result.user_id), value: jsonSafeValue(result.value) };
      }
      return { ok: true, found: false, user_id: null, value: null };
    } catch (err) {
      return { ok: false, found: false, user_id: null, value: null, error: String((err && err.message) || err || 'User lookup failed.') };
    }
  };

  // ── CURRENT-BOT USER DATA SEARCH HELPERS ───────────────────────────────────
  // All wrappers below delegate to findUserByData which already enforces bot_id
  // scoping at the storage bridge level. These are convenience aliases only.

  const findFirstUserByDataInCurrentBot = findUserByData;

  const userDataExistsInCurrentBot = async (key, value) => {
    const result = await findUserByData(key, value);
    return !!(result && result.ok === true && result.found === true);
  };

  const isUserDataTakenInCurrentBot = userDataExistsInCurrentBot;

  const isUserDataTakenByOtherUserInCurrentBot = async (key, value, currentUserId) => {
    const result = await findUserByData(key, value);
    if (!result || !result.ok || !result.found) return false;
    const uid = currentUserId !== null && currentUserId !== undefined ? String(currentUserId) : String(safeUser.id);
    return String(result.user_id) !== uid;
  };

  const findUserByFaucetPayEmailInCurrentBot = async (email) => findUserByData('faucetpay_email', email);
  const faucetPayEmailExistsInCurrentBot = async (email) => userDataExistsInCurrentBot('faucetpay_email', email);
  const faucetPayEmailTakenByOtherUserInCurrentBot = async (email, currentUserId) =>
    isUserDataTakenByOtherUserInCurrentBot('faucetpay_email', email, currentUserId);

  const findUserByWalletInCurrentBot = async (wallet) => findUserByData('wallet', wallet);
  const walletExistsInCurrentBot = async (wallet) => userDataExistsInCurrentBot('wallet', wallet);
  const walletTakenByOtherUserInCurrentBot = async (wallet, currentUserId) =>
    isUserDataTakenByOtherUserInCurrentBot('wallet', wallet, currentUserId);
  // ── END CURRENT-BOT USER DATA SEARCH HELPERS ───────────────────────────────

  const removeUserDataFor = async (telegramUserId, key) => {
    try {
      const uid = normalizeCrossUserId(telegramUserId, 'removeUserDataFor(userId, key)');
      const k = requireStorageKey(key);
      if (_isSelf(uid)) {
        await clearUserData(k);
        return { ok: true };
      }
      if (!crossUsersData[uid]) crossUsersData[uid] = new Map();
      crossUsersData[uid].delete(k);
      if (!crossUserMutations[uid]) crossUserMutations[uid] = [];
      crossUserMutations[uid].push({ op: 'clear', key: k });
      return { ok: true };
    } catch (err) {
      return { ok: false, error: String((err && err.message) || err) };
    }
  };

  // Appends an item to another user's array key and trims to limit.
  // op:push → Laravel reads array from DB, appends, trims, saves atomically.
  const pushUserDataFor = async (telegramUserId, key, item, limit = 50) => {
    try {
      const uid = normalizeCrossUserId(telegramUserId, 'pushUserDataFor(userId, key, item)');
      const k = requireStorageKey(key);
      const normalizedItem = jsonSafeValue(item);
      const safeLimit = Math.max(1, Math.min(Math.floor(toNumber(limit, 50)), 500));
      if (_isSelf(uid)) {
        const current = await getUserData(k, []);
        const updated = [...(Array.isArray(current) ? current : []), normalizedItem].slice(-safeLimit);
        await setUserData(k, updated);
        return { ok: true };
      }
      if (!crossUsersData[uid]) crossUsersData[uid] = new Map();
      const current = crossUsersData[uid].has(k) ? crossUsersData[uid].get(k) : [];
      const updated = [...(Array.isArray(current) ? current : []), normalizedItem].slice(-safeLimit);
      crossUsersData[uid].set(k, updated);
      if (!crossUserMutations[uid]) crossUserMutations[uid] = [];
      crossUserMutations[uid].push({ op: 'push', key: k, item: normalizedItem, limit: safeLimit });
      console.log('[BotHost] cross_user_mutation_created', JSON.stringify({
        bot_id: safeBot.id, target_user_id: uid, key: k, operation: 'push', limit: safeLimit,
      }));
      return { ok: true };
    } catch (err) {
      return { ok: false, error: String((err && err.message) || err) };
    }
  };

  const helperFaucetPayBalance = async (currency = 'USDT') => {
    const sym = String(currency || 'USDT').toUpperCase();
    console.log('[BotHost] faucetpay_balance_request', JSON.stringify({ bot_id: safeBot.id, currency: sym }));
    const savedKey = savedFaucetPayKey();
    const raw = savedKey
      ? await safeFaucetPayCall(() => faucetPayBalance(savedKey, sym))
      : { ok: false, error: 'FaucetPay API key not configured', currency: sym };
    const ok = raw && raw.ok === true;
    const balance = normalizeDecimalAmount(raw && raw.balance !== undefined ? raw.balance : 0, true);
    const displayedBalance = formatCryptoAmount(balance, sym, 8);
    console.log('[BotHost] faucetpay_amount_debug', JSON.stringify({
      bot_id: safeBot.id,
      currency: sym,
      api_balance_received: balance,
      displayed_balance: displayedBalance,
    }));
    console.log('[BotHost] faucetpay_balance_result', JSON.stringify({ bot_id: safeBot.id, ok, status: raw && raw.status, balance }));
    return {
      ok: !!ok,
      status: (raw && raw.status) || 0,
      message: (raw && (raw.message || raw.error)) || '',
      error: ok ? null : ((raw && (raw.error || raw.message)) || 'FaucetPay balance failed.'),
      currency: sym,
      balance,
      balance_number: Number(balance) || 0,
      balance_display: displayedBalance,
      raw: plainObject(raw || {}),
    };
  };

  const paymentRuntimeAction = async (action, options = {}, extra = {}) => {
    if (!oxapayBridgeUrl || !oxapayBridgeSecret) {
      return { ok: false, error: 'Runtime payment bridge is not configured.' };
    }
    const isFaucetPay = String(action).startsWith('faucetpay.');
    const bridgeName = isFaucetPay ? 'faucetpay' : 'secret';
    const bridgeLabel = isFaucetPay ? 'FaucetPay runtime bridge' : 'payment secret runtime bridge';
    const result = await internalRuntimePost(oxapayBridgeUrl, {
      bot_id: safeBot.id,
      action,
      options: normalizeObject(options, 'payment helper options'),
      ...plainObject(extra || {}),
    }, oxapayBridgeSecret, requestTimeoutMs, bridgeLabel, bridgeName);
    if (result && result.error_type === 'PaymentBridgeTimeout') {
      console.error('[BotHost] payment_bridge_timeout', JSON.stringify({
        bot_id: safeBot.id, command_id: safeCommand.id, command_name: safeCommand.name,
        provider: bridgeName, action, telegram_user_id: safeUser.id ?? null,
      }));
    }
    return result;
  };

  const maskSecret = (value) => maskSecretValue(value);
  const saveSecret = async (key, value) => setBotData(requireStorageKey(key), requireString(value, 'saveSecret value'));
  const getMaskedSecret = async (key) => {
    const normalizedKey = requireStorageKey(key);
    if (runtimeSecrets.has(normalizedKey)) {
      return maskSecretValue(String(runtimeSecrets.get(normalizedKey) || ''));
    }
    const result = await paymentRuntimeAction('secret.status', { key: normalizedKey });
    return result && result.ok ? (result.masked || null) : null;
  };
  const getBotSecret = async (key) => {
    const normalizedKey = requireStorageKey(key);
    if (runtimeSecrets.has(normalizedKey)) {
      return runtimeSecrets.get(normalizedKey) || null;
    }
    return null;
  };
  const hasSecret = async (key) => {
    const normalizedKey = requireStorageKey(key);
    if (runtimeSecrets.has(normalizedKey)) {
      return !!runtimeSecrets.get(normalizedKey);
    }
    const result = await paymentRuntimeAction('secret.status', { key: normalizedKey });
    return !!(result && result.ok && result.configured);
  };
  const removeSecret = async (key) => clearBotData(requireStorageKey(key));
  const validateApiKeyFormat = (key, options = {}) => {
    const opts = normalizeObject(options, 'validateApiKeyFormat options');
    const value = String(key || '').trim();
    const min = Math.max(1, toInteger(opts.minLength ?? opts.min ?? 16, 16));
    const max = Math.max(min, toInteger(opts.maxLength ?? opts.max ?? 256, 256));
    const pattern = opts.pattern ? new RegExp(String(opts.pattern)) : /^[A-Za-z0-9._:-]+$/;
    return value.length >= min && value.length <= max && pattern.test(value);
  };
  const safeApiError = (error) => safeTelegramError(error);
  const safeJsonParse = (value, fallback = null) => {
    try { return JSON.parse(String(value)); } catch (_) { return fallback; }
  };
  const hashValue = (value) => crypto.createHash('sha256').update(String(value ?? '')).digest('hex');
  const webhookVerifySignature = (payloadValue, signature, secret) => {
    const body = typeof payloadValue === 'string' ? payloadValue : safeJsonStringify(payloadValue);
    const expected = crypto.createHmac('sha256', String(secret || '')).update(body).digest('hex');
    const provided = String(signature || '').toLowerCase().replace(/^sha256=/, '');
    return provided.length === expected.length && crypto.timingSafeEqual(Buffer.from(expected), Buffer.from(provided));
  };
  const buildQuery = (params) => new URLSearchParams(plainObject(params || {})).toString();
  const normalizeAddress = (value) => String(value || '').trim();
  const isTronAddress = (value) => /^T[1-9A-HJ-NP-Za-km-z]{33}$/.test(normalizeAddress(value));
  const isEvmAddress = (value) => /^0x[a-fA-F0-9]{40}$/.test(normalizeAddress(value));
  const isSolanaAddress = (value) => /^[1-9A-HJ-NP-Za-km-z]{32,44}$/.test(normalizeAddress(value));
  const isWalletAddress = (value) => isTronAddress(value) || isEvmAddress(value) || isSolanaAddress(value);
  const toSmallestUnit = (amount, decimals = 8) => decimalToSmallestUnit(amount, decimals);
  const fromSmallestUnit = (amount, decimals = 8) => smallestUnitToDecimal(amount, decimals);
  const toFaucetPaySatoshis = (amount) => toSmallestUnit(amount, 8);
  const fromFaucetPaySatoshis = (amount) => fromSmallestUnit(amount, 8);
  const normalizePaymentAmount = (amount, decimals = 8) => smallestUnitToDecimal(decimalToSmallestUnit(amount, decimals), decimals);
  const validatePaymentAmount = (amount, min = 0, max = null) => {
    const value = Number(normalizePaymentAmount(amount));
    return Number.isFinite(value) && value >= Number(min || 0) && (max === null || value <= Number(max));
  };
  const calculateFee = (amount, percentValue = 0, fixed = 0) => roundAmount(toNumber(amount, 0) * (toNumber(percentValue, 0) / 100) + toNumber(fixed, 0), 8);
  const calculateNetAmount = (amount, percentValue = 0, fixed = 0) => roundAmount(Math.max(0, toNumber(amount, 0) - calculateFee(amount, percentValue, fixed)), 8);
  const generatePaymentRef = (prefix = 'pay') => `${String(prefix || 'pay')}_${Date.now().toString(36)}_${Math.random().toString(36).slice(2, 10)}`;
  const generateWithdrawalRef = (prefix = 'wd') => generatePaymentRef(prefix);
  const faucetPayCurrencyList = Object.freeze(['BTC', 'ETH', 'DOGE', 'LTC', 'BCH', 'DASH', 'DGB', 'TRX', 'USDT', 'FEY', 'ZEC', 'BNB', 'SOL', 'XRP', 'MATIC', 'ADA', 'TON', 'USDC']);
  const httpRequest = async (method, url, options = {}) => httpsRequest(url, { ...normalizeObject(options, 'httpRequest options'), method });
  const httpGet = async (url, options = {}) => httpRequest('GET', url, options);
  const httpPost = async (url, body = {}, options = {}) => httpRequest('POST', url, { ...normalizeObject(options, 'httpPost options'), json: body });
  const faucetPayKeyNames = Object.freeze(['faucetpay_api_key', 'faucetpay_key', 'fp_api_key', 'faucetpayApiKey']);
  const savedFaucetPayKey = () => {
    for (const key of faucetPayKeyNames) {
      const value = runtimeSecrets.get(key);
      if (value && !isMaskedSecretValue(value)) return String(value).trim();
    }
    if (faucetPayApiKey && !isMaskedSecretValue(faucetPayApiKey)) return String(faucetPayApiKey).trim();
    return '';
  };
  const safeFaucetPayCall = async (fn) => {
    try {
      return await fn();
    } catch (err) {
      return { ok: false, error: String((err && err.message) || err || 'FaucetPay request failed.') };
    }
  };

  const helperGetSavedFaucetPayApiKey = async (_botId = null) => {
    const direct = savedFaucetPayKey();
    if (direct) return direct;

    for (const key of faucetPayKeyNames) {
      const value = await getBotData(key, null);
      const text = String(value || '').trim();
      if (text.length >= 10 && !isMaskedSecretValue(text)) return text;
    }

    return null;
  };

  const helperSaveFaucetPayApiKey = async (botIdOrApiKey, apiKey = undefined) => {
    const cleanKey = String(apiKey === undefined ? botIdOrApiKey : apiKey || '').trim();
    if (!cleanKey || cleanKey.length < 10) {
      return { ok: false, message: 'Invalid FaucetPay API key.', error: 'Invalid FaucetPay API key.' };
    }

    const masked = maskSecretValue(cleanKey);
    await setBotData('faucetpay_api_key', cleanKey);
    await setBotData('faucetpay_key', masked);
    await setBotData('fp_api_key', masked);
    await setBotData('faucetpayApiKey', masked);
    await setBotData('faucetpay_api_key_masked', masked);
    await setBotData('faucetpay_api_key_status', 'saved');
    await setBotData('faucetpay_enabled', true);

    return { ok: true, masked, message: 'FaucetPay API key saved.' };
  };

  const helperFaucetPaySend = async (...args) => {
    let explicitKey = null;
    let toEmail;
    let amount;
    let currency = 'USDT';
    if (args.length >= 4) {
      [explicitKey, toEmail, amount, currency = 'USDT'] = args;
    } else {
      [toEmail, amount, currency = 'USDT'] = args;
    }

    const safeEmail = String(toEmail || '').toLowerCase().trim();
    if (!safeEmail) return { ok: false, status: 0, message: 'FaucetPay email missing.', error: 'FaucetPay email missing.', raw: {} };
    const safeCurrency = requireString(String(currency || 'USDT'), 'faucetPaySend currency').toUpperCase();
    const safeAmount = normalizeDecimalAmount(amount, false);
    if (!Number.isFinite(Number(safeAmount)) || Number(safeAmount) <= 0) {
      return { ok: false, status: 0, message: 'Invalid payout amount.', error: 'Invalid payout amount.', raw: {} };
    }
    console.log('[BotHost] faucetpay_amount_debug', JSON.stringify({
      bot_id: safeBot.id,
      input_amount: amount,
      normalized_amount: safeAmount,
      currency: safeCurrency,
      api_amount_sent: safeAmount,
    }));
    console.log('[BotHost] faucetpay_send_attempt', JSON.stringify({ bot_id: safeBot.id, currency: safeCurrency, amount: safeAmount }));
    const savedKey = explicitKey && !isMaskedSecretValue(explicitKey)
      ? String(explicitKey).trim()
      : await helperGetSavedFaucetPayApiKey();
    const raw = savedKey
      ? await safeFaucetPayCall(() => faucetPaySend(savedKey, safeEmail, safeAmount, safeCurrency))
      : { ok: false, error: 'FaucetPay API key not configured', currency: safeCurrency, amount: safeAmount };
    const ok = raw && raw.ok === true;
    if (ok) {
      console.log('[BotHost] faucetpay_send_success', JSON.stringify({ bot_id: safeBot.id, currency: safeCurrency, amount: safeAmount }));
    } else {
      console.log('[BotHost] faucetpay_send_failed', JSON.stringify({ bot_id: safeBot.id, currency: safeCurrency, amount: safeAmount, message: raw && raw.message }));
    }
    return { ok: !!ok, status: (raw && raw.status) || 0, message: (raw && (raw.message || raw.error)) || 'Unknown error', error: ok ? null : ((raw && (raw.error || raw.message)) || 'FaucetPay send failed.'), data: plainObject((raw && (raw.data || raw.raw)) || raw || {}), raw: plainObject(raw || {}) };
  };

  const helperFaucetPayGetBalance = async (apiKey = null, currency = 'USDT') => {
    let explicitKey = apiKey;
    let requestedCurrency = currency;
    if (currency === 'USDT' && isLikelyFaucetPayCurrency(apiKey)) {
      explicitKey = null;
      requestedCurrency = apiKey;
    }
    const sym = String(requestedCurrency || 'USDT').toUpperCase();
    const options = { currency: sym };
    if (explicitKey !== null && explicitKey !== undefined && String(explicitKey).trim() !== '' && !isMaskedSecretValue(explicitKey)) {
      options.api_key = String(explicitKey);
    }
    const savedKey = savedFaucetPayKey();
    const result = (options.api_key || savedKey)
      ? await safeFaucetPayCall(() => faucetPayBalance(options.api_key || savedKey, sym))
      : { ok: false, error: 'FaucetPay API key not configured', currency: sym };
    const ok = result && result.ok === true;
    return ok
      ? {
          ok: true,
          balance: result.balance ?? 0,
          currency: result.currency || sym,
          message: result.message || 'FaucetPay balance loaded.',
          data: plainObject(result.data || result.raw || {}),
        }
      : { ok: false, error: String((result && (result.error || result.message)) || 'FaucetPay getbalance failed.') };
  };

  const helperFaucetPayCheckAddress = async (currency, address) => {
    console.log('[BotHost] faucetpay_checkaddress', JSON.stringify({ bot_id: safeBot.id, currency: String(currency).toUpperCase() }));
    const sym = String(currency || 'USDT').toUpperCase();
    const savedKey = savedFaucetPayKey();
    const result = savedKey
      ? await safeFaucetPayCall(() => faucetPayCheckAddress(savedKey, sym, String(address || '')))
      : { ok: false, error: 'FaucetPay API key not configured', currency: sym };
    const ok = result && result.ok === true;
    return { ok: !!ok, status: (result && result.status) || 0, message: (result && result.message) || 'Unknown', raw: plainObject(result || {}) };
  };

  const helperFaucetPayValidateKey = async (apiKey = null) => {
    const key = apiKey && !isMaskedSecretValue(apiKey)
      ? String(apiKey)
      : savedFaucetPayKey();
    if (!key) return { ok: false, valid: false, error: 'FaucetPay API key not configured' };
    const result = await safeFaucetPayCall(() => faucetPayBalance(key, 'USDT'));
    return result && result.ok
      ? { ok: true, valid: true, status: result.status || 200, message: 'FaucetPay API key is valid.', data: plainObject(result.data || result.raw || result || {}) }
      : { ok: false, valid: false, error: String((result && (result.error || result.message)) || 'FaucetPay API key is invalid.') };
  };

  const helperFaucetPayCheckEmail = async (email, currency = 'USDT') => {
    const savedKey = savedFaucetPayKey();
    const sym = String(currency || 'USDT').toUpperCase();
    if (!savedKey) return { ok: false, error: 'FaucetPay API key not configured', currency: sym };
    return safeFaucetPayCall(() => faucetPayCheckAddress(savedKey, sym, String(email || '')));
  };

  const helperFaucetPayGetCurrencies = async (apiKey = null) => {
    const key = apiKey && !isMaskedSecretValue(apiKey)
      ? String(apiKey)
      : savedFaucetPayKey();
    if (!key) return { ok: false, error: 'FaucetPay API key not configured' };
    return safeFaucetPayCall(() => faucetPayCurrencies(key));
  };

  const helperSendWithdrawalChannelNotice = async (_botId, userId, amount, currency = 'USDT', botUsername = null) => {
    const payoutChannel = await getBotData('payout_channel', null);
    if (!payoutChannel) {
      return { ok: false, message: 'Withdrawal channel not set.', error: 'Withdrawal channel not set.' };
    }

    const username = String(botUsername || safeBot.username || safeBot.name || '').replace(/^@+/, '').trim();
    const botLink = username
      ? `<a href="https://t.me/${escapeHTML(username)}">@${escapeHTML(username)}</a>`
      : 'Bot link unavailable';
    const coin = String(currency || 'USDT').toUpperCase();
    const message =
      `\u2705 <b>New withdrawal sent successfully</b>\n\n` +
      `<b>User id:</b> <code>${escapeHTML(userId)}</code>\n` +
      `<b>Network:</b> FaucetPay\n` +
      `<b>Amount:</b> ${escapeHTML(amount)} ${escapeHTML(coin)}\n` +
      `<b>Bot:</b> ${botLink}`;

    const result = await sendMessage(payoutChannel, message, { parse_mode: 'HTML' });
    return { ok: true, result: plainObject(result || {}) };
  };

  const oxapayRuntimeAction = async (action, options = {}, extra = {}) => {
    if (!oxapayBridgeUrl || !oxapayBridgeSecret) {
      return { ok: false, error: 'OxaPay runtime bridge is not configured.' };
    }

    const normalizedOptions = normalizeObject(options, 'OxaPay options');
    if (normalizedOptions.amount !== undefined) {
      normalizedOptions.amount = normalizeDecimalAmount(normalizedOptions.amount, false);
    }

    if (normalizedOptions.from_amount !== undefined) {
      normalizedOptions.from_amount = normalizeDecimalAmount(normalizedOptions.from_amount, false);
    }

    if (normalizedOptions.to_amount !== undefined) {
      normalizedOptions.to_amount = normalizeDecimalAmount(normalizedOptions.to_amount, false);
    }

    console.log('[BotHost] oxapay_amount_debug', JSON.stringify({
      bot_id: safeBot.id,
      action,
      amount: normalizedOptions.amount ?? null,
      currency: normalizedOptions.currency ?? null,
      pay_currency: normalizedOptions.pay_currency ?? normalizedOptions.payCurrency ?? null,
    }));

    const result = await internalRuntimePost(oxapayBridgeUrl, {
      bot_id: safeBot.id,
      action,
      options: normalizedOptions,
      ...plainObject(extra || {}),
    }, oxapayBridgeSecret, requestTimeoutMs, 'OxaPay runtime bridge', 'oxapay');
    if (result && result.error_type === 'PaymentBridgeTimeout') {
      console.error('[BotHost] payment_bridge_timeout', JSON.stringify({
        bot_id: safeBot.id, command_id: safeCommand.id, command_name: safeCommand.name,
        provider: 'oxapay', action, telegram_user_id: safeUser.id ?? null,
      }));
    }
    return result;
  };

  const oxapayCreateInvoice = async (options = {}) => oxapayRuntimeAction('oxapay.createInvoice', options);
  const oxapayCreateWhiteLabel = async (options = {}) => oxapayRuntimeAction('oxapay.createWhiteLabel', options);
  const oxapayCreateStaticAddress = async (options = {}) => oxapayRuntimeAction('oxapay.createStaticAddress', options);
  const oxapayGetPayment = async (trackId) => oxapayRuntimeAction('oxapay.getPayment', {}, { track_id: requireString(trackId, 'oxapayGetPayment(trackId)') });
  const oxapayValidateWebhook = async (payload, headers = {}) => oxapayRuntimeAction('oxapay.validateWebhook', { payload: jsonSafeValue(payload), headers: normalizeObject(headers, 'OxaPay webhook headers') });
  const oxapayPayout = async (options = {}) => oxapayRuntimeAction('oxapay.payout', options);

  const delay = async (milliseconds) => {
    const ms = Number(milliseconds);

    if (!Number.isFinite(ms) || ms < 0) {
      throw new Error('delay(ms) expects a non-negative number.');
    }

    if (ms > requestSettings.max_delay_ms) {
      throw new Error(`delay(ms) cannot exceed ${requestSettings.max_delay_ms}ms.`);
    }

    await new Promise((resolve) => setTimeout(resolve, ms));
  };

  const notifyAdmin = async (text, options = {}) => {
    const opts = normalizeObject(options);
    let adminId = opts.adminId ?? null;
    if (!adminId) adminId = await getBotData('admin_owner_id', null);
    if (!adminId) return { ok: false, error: 'Admin ID not found. Pass adminId in options or set admin_owner_id in bot data.' };
    try { await sendMessage(adminId, requireString(text, 'notifyAdmin(text)'), opts); return { ok: true }; }
    catch (err) { return { ok: false, error: String((err && err.message) || err) }; }
  };

  const notifyUser = async (userId, text, options = {}) => {
    const opts = normalizeObject(options, 'notifyUser options');
    // Auto-apply HTML parse_mode when message contains HTML tags and caller didn't override
    if (!opts.parse_mode && typeof text === 'string' && /<[a-z][\s\S]*>/i.test(text)) {
      opts.parse_mode = 'HTML';
    }
    try { return await sendMessage(requireChatId(userId, 'notifyUser(userId, text)'), requireString(text, 'notifyUser(userId, text)'), opts); }
    catch (err) { return { ok: false, error: String((err && err.message) || err) }; }
  };

  // Send a photo to a specific Telegram user ID (safe wrapper around sendPhoto).
  const notifyUserPhoto = async (userId, photo, options = {}) => {
    try {
      const result = await sendPhoto(requireChatId(userId, 'notifyUserPhoto(userId, photo)'), photo, normalizeObject(options, 'notifyUserPhoto options'));
      if (result && result.ok === false) {
        return { ok: false, error: safeTelegramError(result.error || 'Photo delivery failed.') };
      }
      return { ok: true, result };
    } catch (err) {
      return { ok: false, error: safeTelegramError(err) };
    }
  };

  // Extract the best (largest) photo file_id from an incoming Telegram message.
  // Accepts the message object, the full update, or nothing (defaults to current message).
  // Returns null when no photo is present.
  const getIncomingPhoto = (msgOrUpdate = null) => {
    let src;
    if (msgOrUpdate !== null) {
      const raw = plainObject(msgOrUpdate || {});
      // Support passing the full update or just the message object
      src = Array.isArray(raw.photo) ? raw : (raw.message && Array.isArray(raw.message.photo) ? raw.message : raw);
    } else {
      src = safeMessage;
    }
    const photos = Array.isArray(src.photo) ? src.photo : [];
    if (photos.length === 0) return null;
    // Telegram sends PhotoSize array sorted ascending by size; last is largest.
    const best = photos[photos.length - 1];
    return {
      file_id: typeof best.file_id === 'string' ? best.file_id : null,
      file_unique_id: typeof best.file_unique_id === 'string' ? best.file_unique_id : null,
      width: typeof best.width === 'number' ? best.width : null,
      height: typeof best.height === 'number' ? best.height : null,
      file_size: typeof best.file_size === 'number' ? best.file_size : null,
      caption: typeof src.caption === 'string' ? src.caption : null,
    };
  };

  const sendPayoutNotice = async (channel, data = {}) => {
    const target = String(channel || '').trim();
    if (!target) return { ok: false, error: 'sendPayoutNotice: channel is required.' };
    const safeData = isPlainObject(data) ? plainObject(data) : {};
    console.log('[BotHost] payout_channel_notice_start', JSON.stringify({
      bot_id: safeBot.id,
      payout_channel: target,
      user_id: safeData.user_id || null,
      amount: safeData.amount || null,
      currency: safeData.currency || null,
    }));
    console.log('[BotHost] payout_channel_notice_target', JSON.stringify({ bot_id: safeBot.id, payout_channel: target }));
    try {
      await sendMessage(target, buildPayoutMessage(data), { parse_mode: 'HTML' });
      console.log('[BotHost] payout_channel_notice_sent', JSON.stringify({ bot_id: safeBot.id, payout_channel: target }));
      return { ok: true };
    } catch (err) {
      const errMsg = String((err && err.message) || err);
      console.log('[BotHost] payout_channel_notice_failed', JSON.stringify({ bot_id: safeBot.id, payout_channel: target, error: errMsg }));
      return { ok: false, error: errMsg + ' — Make sure bot is admin in the channel and has permission to send messages.' };
    }
  };

  const sendAdminLog = async (text, options = {}) => {
    const opts = normalizeObject(options);
    let target = opts.adminId ?? null;
    if (!target) target = await getBotData('admin_log_channel', null);
    if (!target) target = await getBotData('admin_owner_id', null);
    if (!target) return { ok: false, error: 'Admin log target not found. Pass adminId or set admin_log_channel/admin_owner_id in bot data.' };
    try { await sendMessage(target, requireString(text, 'sendAdminLog(text)'), opts); return { ok: true }; }
    catch (err) { return { ok: false, error: String((err && err.message) || err) }; }
  };

  const sendToChannel = async (channel, text, options = {}) => {
    const target = String(channel || '').trim();
    if (!target) return { ok: false, error: 'sendToChannel: channel is required.' };
    try { await sendMessage(target, requireString(text, 'sendToChannel(channel, text)'), normalizeObject(options)); return { ok: true }; }
    catch (err) { return { ok: false, error: String((err && err.message) || err) }; }
  };

  const safeSendMessage = async (chatId, text, options = {}) => {
    try { await sendMessage(chatId, text, options); return { ok: true }; }
    catch (err) { return { ok: false, error: String((err && err.message) || err) }; }
  };

  const safeReply = async (text, options = {}) => {
    try { await reply(text, options); return { ok: true }; }
    catch (err) { return { ok: false, error: String((err && err.message) || err) }; }
  };

  const broadcastToUsers = async (userIds, text, options = {}) => {
    const opts = normalizeObject(options);
    const max = Math.min(Math.max(Number(opts.max) || 50, 1), 50);
    if (!Array.isArray(userIds)) return { ok: false, error: 'broadcastToUsers: userIds must be an array.' };
    if (userIds.length > max) return { ok: false, error: 'Too many users for direct broadcast. Use queueBroadcast instead.' };
    const msgText = requireString(text, 'broadcastToUsers(userIds, text)');
    const msgOpts = plainObject({ ...opts, max: undefined, delayMs: undefined });
    let sent = 0;
    let failed = 0;
    for (const uid of userIds) {
      try { await sendMessage(uid, msgText, msgOpts); sent++; }
      catch (_) { failed++; }
    }
    return { ok: true, sent, failed, total: userIds.length };
  };

  const broadcastMessage = async (segment, text, options = {}) => {
    const opts = normalizeObject(options, 'broadcastMessage options');
    const dryRun = opts.dry_run !== false;
    const seg = String(segment || 'all');
    if (Array.isArray(opts.user_ids)) {
      if (dryRun) return { ok: true, dry_run: true, segment: seg, total: opts.user_ids.length };
      return broadcastToUsers(opts.user_ids, requireString(text, 'broadcastMessage(segment, text)'), opts);
    }
    return {
      ok: false,
      error: 'Broadcast queue service not available from runtime. Use queueBroadcast or the Broadcasts panel.',
      segment: seg,
      dry_run: true,
    };
  };

  const queueBroadcast = async (segment, payload = {}, options = {}) => {
    return {
      ok: false,
      error: 'Broadcast queue service not available from runtime. Use the Broadcasts panel in your bot admin.',
      segment: String(segment || 'all'),
      payload_preview: isPlainObject(payload) ? Object.keys(payload).slice(0, 10) : [],
      options: plainObject(options || {}),
    };
  };

  const getBroadcastStatus = async (_broadcastId) => {
    return { ok: false, error: 'Broadcast status not available from runtime. Use the Broadcasts panel in your bot admin.' };
  };

  const previewBroadcast = (text, options = {}) => {
    const opts = normalizeObject(options);
    return { preview: String(text || ''), segment: String(opts.segment || 'all'), note: 'Preview only. Use queueBroadcast or the Broadcasts panel to send.' };
  };

  const saveNotificationTemplate = async (name, text) => {
    if (typeof name !== 'string' || !name.trim()) throw new Error('saveNotificationTemplate: name must be a non-empty string.');
    const key = requireStorageKey(`ntpl_${name.trim()}`.slice(0, 100));
    await setBotData(key, requireString(text, 'saveNotificationTemplate(name, text)'));
    return { ok: true };
  };

  const getNotificationTemplate = async (name, fallback = null) => {
    if (typeof name !== 'string' || !name.trim()) throw new Error('getNotificationTemplate: name must be a non-empty string.');
    const key = requireStorageKey(`ntpl_${name.trim()}`.slice(0, 100));
    const value = await getBotData(key, null);
    return value !== null ? String(value) : (fallback !== null ? String(fallback) : null);
  };

  const supportTicketId = (ticket) => String((ticket && (ticket.id || ticket.ticket_id || ticket.ticketId || ticket.support_ticket_id)) || '').trim();
  const normalizeSupportTicketRecord = (ticket, fallbackId = null, fallbackUserId = null) => {
    if (!ticket || typeof ticket !== 'object') return null;
    const id = supportTicketId(ticket) || String(fallbackId || '').trim();
    if (!id) return null;
    const userId = String(ticket.user_id || ticket.userId || ticket.target_user_id || ticket.support_target_user || fallbackUserId || '').trim();
    return { ...ticket, id, ticket_id: ticket.ticket_id || id, user_id: userId || ticket.user_id || '' };
  };
  const supportTicketList = (tickets) => {
    if (Array.isArray(tickets)) return tickets;
    if (isPlainObject(tickets)) return Object.values(tickets);
    return [];
  };
  const supportTickets = async () => supportTicketList(await getBotData('support_tickets', []))
    .map((ticket) => normalizeSupportTicketRecord(ticket))
    .filter(Boolean);
  const saveSupportTickets = async (tickets) => setBotData('support_tickets', supportTicketList(tickets).map((ticket) => normalizeSupportTicketRecord(ticket)).filter(Boolean).slice(-500));
  const supportTicketKey = (ticketId) => requireStorageKey(`support_ticket_${String(ticketId || '').trim()}`);
  const saveSupportTicketRecord = async (ticket) => {
    const normalized = normalizeSupportTicketRecord(ticket);
    if (!normalized) return null;
    await setBotData(supportTicketKey(supportTicketId(normalized)), normalized);
    return normalized;
  };
  const getSupportTicket = async (ticketId) => {
    const wanted = String(ticketId || '').trim();
    if (!wanted) return null;
    const direct = normalizeSupportTicketRecord(await getBotData(supportTicketKey(wanted), null), wanted);
    if (direct) return direct;
    return (await supportTickets()).find((ticket) => supportTicketId(ticket) === wanted) || null;
  };
  const updateSupportTicket = async (ticketId, updates = {}) => {
    const wanted = String(ticketId || '').trim();
    const list = await supportTickets();
    const index = list.findIndex((ticket) => supportTicketId(ticket) === wanted);
    const existing = index >= 0 ? list[index] : await getSupportTicket(wanted);
    if (!existing) return { ok: false, error: 'Ticket not found.' };
    const ticket = normalizeSupportTicketRecord({ ...existing, ...plainObject(updates || {}) }, wanted);
    if (index >= 0) {
      list[index] = ticket;
      await saveSupportTickets(list);
    }
    await saveSupportTicketRecord(ticket);
    return { ok: true, ticket };
  };
  const createSupportTicket = async (data = {}) => {
    const ticket = { id: generateId('support'), ticket_id: null, status: 'open', created_at: new Date().toLocaleString(), ...plainObject(data || {}) };
    ticket.ticket_id = ticket.ticket_id || ticket.id;
    const normalized = await saveSupportTicketRecord(ticket);
    const list = await supportTickets();
    list.push(normalized || ticket);
    await saveSupportTickets(list);
    return { ok: true, ticket: normalized || ticket };
  };
  const saveAdminMessageRef = async (ticketId, adminId, chatId, messageId) => {
    const ticket = await getSupportTicket(ticketId);
    if (!ticket) return { ok: false, error: 'Ticket not found.' };
    const refs = Array.isArray(ticket.admin_messages) ? ticket.admin_messages : [];
    refs.push({ admin_id: String(adminId), chat_id: String(chatId), message_id: messageId });
    return updateSupportTicket(ticketId, { admin_messages: refs });
  };
  const getAdminMessageRefs = async (ticketId) => {
    const ticket = await getSupportTicket(ticketId);
    return ticket && Array.isArray(ticket.admin_messages) ? ticket.admin_messages : [];
  };
  const clearAdminMessageRefs = async (ticketId) => updateSupportTicket(ticketId, { admin_messages: [] });
  const closeSupportTicket = async (ticketId, adminId, replyData = {}) => updateSupportTicket(ticketId, { status: 'closed', replied_by: String(adminId), replied_at: new Date().toLocaleString(), ...plainObject(replyData || {}) });
  const lockSupportTicket = async (ticketId, adminId, minutes = 15) => updateSupportTicket(ticketId, { status: 'in_progress', assigned_admin: String(adminId), locked_at: Date.now(), lock_expires_at: Date.now() + Math.max(1, toInteger(minutes, 15)) * 60000 });
  const unlockSupportTicket = async (ticketId) => updateSupportTicket(ticketId, { assigned_admin: null, locked_at: null, lock_expires_at: null, status: 'open' });
  const isTicketClosed = async (ticketId) => {
    const ticket = await getSupportTicket(ticketId);
    return !!ticket && ['closed', 'replied'].includes(String(ticket.status));
  };
  const isTicketLocked = async (ticketId) => {
    const ticket = await getSupportTicket(ticketId);
    if (!ticket || ticket.status !== 'in_progress') return false;
    const expires = Number(ticket.lock_expires_at || 0);
    return !expires || expires > Date.now();
  };
  const getOpenSupportTickets = async (limit = 20) => (await supportTickets()).filter((ticket) => ['open', 'in_progress'].includes(String(ticket.status))).slice(-Math.max(1, toInteger(limit, 20)));
  const searchSupportTicket = async (ticketId) => getSupportTicket(ticketId);
  const formatSupportTicketPreview = (ticket) => String((ticket && (ticket.preview || ticket.text || ticket.caption)) || 'No text').replace(/\s+/g, ' ').trim();
  const formatSupportTicketOpen = (ticket) => `📩 <b>New BotHost Pro Support Message</b>\n\n• <b>Ticket:</b> <code>${escapeHTML(ticket.id || ticket.ticket_id || '')}</code>\n• <b>User ID:</b> <code>${escapeHTML(ticket.user_id || '')}</code>\n• <b>Name:</b> ${escapeHTML(ticket.name || 'Unknown')}\n• <b>Username:</b> ${ticket.username ? '@' + escapeHTML(ticket.username) : 'None'}\n• <b>Type:</b> ${escapeHTML(ticket.type || 'text')}\n• <b>Status:</b> 🟢 Open\n\n<u>Message</u>\n${escapeHTML(formatSupportTicketPreview(ticket))}`;
  const formatSupportTicketClosed = (ticket) => `📩 <b>New BotHost Pro Support Message</b>\n\n• <b>Ticket:</b> <code>${escapeHTML(ticket.id || ticket.ticket_id || '')}</code>\n• <b>User ID:</b> <code>${escapeHTML(ticket.user_id || '')}</code>\n• <b>Name:</b> ${escapeHTML(ticket.name || 'Unknown')}\n• <b>Username:</b> ${ticket.username ? '@' + escapeHTML(ticket.username) : 'None'}\n• <b>Type:</b> ${escapeHTML(ticket.type || 'text')}\n• <b>Status:</b> ✅ Closed\n• <b>Replied By:</b> <code>${escapeHTML(ticket.replied_by || '')}</code>\n• <b>Closed At:</b> ${escapeHTML(ticket.replied_at || ticket.closed_at || '')}\n\n<u>Message</u>\n${escapeHTML(formatSupportTicketPreview(ticket))}`;
  const buildSupportReplyKeyboard = (ticket) => inlineKeyboard([[button('↩️ Reply User', `/admin reply ${ticket.id || ticket.ticket_id} ${ticket.user_id}`)]]);
  const buildClosedTicketKeyboard = (ticket) => inlineKeyboard([[button('✅ Closed', `/admin already_closed ${ticket.id || ticket.ticket_id}`)]]);
  const getSupportAdmins = async () => {
    const admins = await getBotData('support_admin_ids', []);
    return Array.isArray(admins) ? admins.map(String) : [];
  };
  const addSupportAdmin = async (userId) => {
    const admins = await getSupportAdmins();
    if (!admins.includes(String(userId))) admins.push(String(userId));
    await setBotData('support_admin_ids', admins);
    return { ok: true, admins };
  };
  const removeSupportAdmin = async (userId) => {
    const admins = (await getSupportAdmins()).filter((id) => id !== String(userId));
    await setBotData('support_admin_ids', admins);
    return { ok: true, admins };
  };
  const isSupportAdmin = async (userId) => (await getSupportAdmins()).includes(String(userId));
  const notifySupportAdmins = async (ticket, options = {}) => {
    const admins = await getSupportAdmins();
    const refs = [];
    for (const adminId of admins) {
      const result = await sendMessage(adminId, formatSupportTicketOpen(ticket), { parse_mode: 'HTML', reply_markup: buildSupportReplyKeyboard(ticket), ...normalizeObject(options) });
      if (result && result.ok && result.result && result.result.message_id) refs.push({ admin_id: String(adminId), chat_id: String(adminId), message_id: result.result.message_id });
    }
    if (ticket && (ticket.id || ticket.ticket_id)) await updateSupportTicket(ticket.id || ticket.ticket_id, { admin_messages: refs });
    return { ok: true, refs };
  };
  const safeEditMessage = async (chatId, messageId, text, options = {}) => editMessageText(chatId, messageId, text, options);
  const safeEditReplyMarkup = async (chatId, messageId, replyMarkup = null) => editMessageReplyMarkup(chatId, messageId, replyMarkup);
  const editTicketMessage = async (ticketId, text, options = {}) => updateSavedAdminMessages(ticketId, text, options);
  const updateSavedAdminMessages = async (ticketId, text, options = {}) => {
    const refs = await getAdminMessageRefs(ticketId);
    const results = [];
    for (const ref of refs) results.push(await safeEditMessage(ref.chat_id, ref.message_id, text, options));
    return { ok: results.every((r) => r && r.ok), results };
  };
  const safeHTML = escapeHTML;
  const safeTelegramResult = (result) => result && result.ok ? { ok: true, result: result.result ?? result.data ?? null } : { ok: false, error: safeTelegramError(result && (result.error || result.message)) };
  const getLargestPhotoFileId = getPhotoFileId;
  const getIncomingMediaType = getMediaType;
  const requestContext = freezePlainObject({
    message: plainObject(safeMessage),
    chat: plainObject(safeChat),
    user: plainObject(safeUser),
    update: plainObject(safeUpdate),
    args: [...args],
    callback_data: telegram.callback_data ?? null,
    callback_query: callbackQueryObj,
  });

  return {
    helpers: {
      user: freezePlainObject(safeUser),
      chat: freezePlainObject(safeChat),
      chatId: safeChat.id,
      message: freezePlainObject(safeMessage),
      messageText: safeMessage.text ?? telegram.message_text ?? null,
      update: freezePlainObject(safeUpdate),
      request: requestContext,
      setTimeout,
      clearTimeout,
      bot: freezePlainObject(safeBot),
      command: freezePlainObject(safeCommand),
      commandFlow: freezePlainObject(commandFlow),
      commandStep,
      commandData,
      callbackData: telegram.callback_data ?? null,
      callbackQuery: callbackQueryObj,
      args: Object.freeze(args),
      userId: safeUser.id,
      reply,
      replyHTML,
      replyMarkdown,
      replyPhoto,
      replyDocument,
      runCommand,
      sendMessage,
      sendPhoto,
      sendDocument,
      sendVideo,
      sendAudio,
      sendAnimation,
      sendSticker,
      sendLocation,
      sendContact,
      copyMessage,
      forwardMessage,
      editMessageText,
      editMessageCaption,
      editMessageReplyMarkup,
      deleteMessage,
      answerCallbackQuery,
      keyboard,
      replyKeyboard,
      inlineKeyboard,
      forceReply,
      confirmCancelKeyboard,
      paginateKeyboard,
      getMessageText,
      getCallbackData,
      isCallback,
      isCommandText,
      getCommandName,
      getCommandArgs,
      getChatId,
      getUserId,
      getMessageId,
      getLargestPhotoFileId,
      getUsername,
      hasPhoto,
      getPhotoFileId,
      getPhotoCaption,
      hasDocument,
      getDocumentFileId,
      getDocumentName,
      getMediaType,
      getIncomingMediaType,
      getIncomingMedia,
      safeHTML,
      safeTelegramResult,
      safeCaption,
      safeTelegramError,
      getChatMember,
      getTelegramChatMember: getChatMember,
      getChannelMember: getChatMember,
      checkChannelMember,
      checkTelegramChannelMember: checkChannelMember,
      checkTelegramChannel: checkChannelMember,
      checkChannel: checkChannelMember,
      verifyTelegramChannel,
      verifyChannel: verifyTelegramChannel,
      verifyChannelMember: verifyTelegramChannel,
      isChannelMember,
      isTelegramChannelMember: isChannelMember,
      getUserData,
      setUserData,
      incrementUserData,
      pushUserData,
      clearUserData,
      removeUserData,
      getBotData,
      setBotData,
      incrementBotData,
      pushBotData,
      clearBotData,
      removeBotData,
      generateCaptcha,
      verifyCaptcha,
      delay,
      now,
      nowMs,
      random,
      httpsRequest,
      httpGet,
      httpPost,
      httpRequest,
      maskSecret,
      saveSecret,
      getMaskedSecret,
      getBotSecret,
      hasSecret,
      removeSecret,
      validateApiKeyFormat,
      safeApiError,
      safeJsonParse,
      safeJsonStringify,
      hashValue,
      webhookVerifySignature,
      buildQuery,
      normalizeAddress,
      isWalletAddress,
      isTronAddress,
      isEvmAddress,
      isSolanaAddress,
      toSmallestUnit,
      fromSmallestUnit,
      toFaucetPaySatoshis,
      fromFaucetPaySatoshis,
      normalizePaymentAmount,
      validatePaymentAmount,
      calculateFee,
      calculateNetAmount,
      generatePaymentRef,
      generateWithdrawalRef,
      getSavedFaucetPayApiKey: helperGetSavedFaucetPayApiKey,
      saveFaucetPayApiKey: helperSaveFaucetPayApiKey,
      faucetPayBalance: helperFaucetPayBalance,
      faucetPayGetBalance: helperFaucetPayGetBalance,
      getFaucetPayBalance: helperFaucetPayGetBalance,
      faucetPaySend: helperFaucetPaySend,
      sendFaucetPay: helperFaucetPaySend,
      faucetPayWithdraw: helperFaucetPaySend,
      faucetPayWithdrawal: helperFaucetPaySend,
      faucetPayPayout: helperFaucetPaySend,
      faucetPayCheckAddress: helperFaucetPayCheckAddress,
      checkFaucetPayAddress: helperFaucetPayCheckAddress,
      faucetPayValidateKey: helperFaucetPayValidateKey,
      validateFaucetPayKey: helperFaucetPayValidateKey,
      faucetPayValidateApiKey: helperFaucetPayValidateKey,
      faucetPayValidateAPIKey: helperFaucetPayValidateKey,
      faucetPayCheckEmail: helperFaucetPayCheckEmail,
      checkFaucetPayEmail: helperFaucetPayCheckEmail,
      faucetPayGetCurrencies: helperFaucetPayGetCurrencies,
      getFaucetPayCurrencies: helperFaucetPayGetCurrencies,
      faucetPayCurrencies: helperFaucetPayGetCurrencies,
      faucetPayGetSupportedCurrencies: helperFaucetPayGetCurrencies,
      isFaucetPayCurrencySupported: (currency) => faucetPayCurrencyList.includes(String(currency || '').toUpperCase()),
      faucetPaySupportedCurrencies: () => [...faucetPayCurrencyList],
      faucetPayFormatAmount: (amount) => normalizePaymentAmount(amount, 8),
      faucetPayParseBalance: (rawBalance) => extractFaucetPayBalance(rawBalance, ''),
      faucetPaySafeResponse: (response) => plainObject(response || {}),
      faucetPayErrorMessage: (response) => String((response && (response.error || response.message)) || 'FaucetPay request failed'),
      sendWithdrawalChannelNotice: helperSendWithdrawalChannelNotice,
      findUserByData,
      findUserByDataInCurrentBot: findUserByData,
      findFirstUserByDataInCurrentBot,
      userDataExistsInCurrentBot,
      isUserDataTakenInCurrentBot,
      isUserDataTakenByOtherUserInCurrentBot,
      findUserByFaucetPayEmailInCurrentBot,
      faucetPayEmailExistsInCurrentBot,
      faucetPayEmailTakenByOtherUserInCurrentBot,
      findUserByWalletInCurrentBot,
      walletExistsInCurrentBot,
      walletTakenByOtherUserInCurrentBot,
      oxapayCreateInvoice,
      oxapayCreateWhiteLabel,
      oxapayCreateStaticAddress,
      oxapayGetPayment,
      oxapayValidateWebhook,
      oxapayPayout,
      oxapayValidateKeys: async (options = {}) => oxapayRuntimeAction('oxapay.validateKeys', options),
      oxapaySupportedCurrencies: async () => {
        const result = await oxapayRuntimeAction('oxapay.supportedCurrencies', {});
        return result && Array.isArray(result.currencies) ? result.currencies : [];
      },
      oxapaySafeResponse: (response) => plainObject(response || {}),
      oxapayErrorMessage: (response) => String((response && (response.error || response.message)) || 'OxaPay request failed'),
      fpDecimals,
      incrementUserDataFor,
      setUserDataFor,
      getUserDataFor,
      pushUserDataFor,
      removeUserDataFor,
      addReferralReward,
      recordReferral,
      getReferralStats,
      maskEmail,
      formatNumber,
      formatMoney,
      formatCryptoAmount,
      isEmail,
      isNumber,
      toNumber,
      toInteger,
      clamp,
      clampNumber,
      roundAmount,
      normalizeCurrency,
      percent,
      today,
      timeNow,
      addMinutes,
      addHours,
      addDays,
      timeLeft,
      randomChoice,
      shuffleArray,
      sanitizeText,
      safeText,
      escapeHTML,
      makeRefLink,
      maskUserId,
      statusBadge,
      shortText,
      setState,
      getState,
      clearState,
      setStateData,
      getStateData,
      clearStateData,
      hasState,
      expireStateAfter,
      isStateExpired,
      ask,
      askInCommand,
      getCommandStep,
      getCommandData,
      setCommandData,
      clearCommandFlow,
      hasCommandFlow,
      continueCommand,
      cancelAllFlows,
      cancelWaitingStates,
      isCommandFlowExpired,
      requireState,
      isOwner,
      isAdmin,
      requireOwner,
      requireAdmin,
      requireVerified,
      getBalance,
      setBalance,
      addBalance,
      removeBalance,
      transferBalance,
      getWallet,
      setWallet,
      isBanned,
      isUserBanned,
      banUser,
      unbanUser,
      checkUser,
      updateUserStatus,
      getReferrer,
      setReferrer,
      rewardReferrer,
      generateId,
      addTransaction,
      getTransactions,
      addHistory,
      getHistory,
      recordAdminCredit,
      recordAdminDebit,
      recordWithdrawal,
      recordDeposit,
      recordBonus,
      recordSupportMessage,
      getUserSummary,
      incrementStat,
      getStat,
      preventSelfReferral,
      hasReferralRewarded,
      markReferralRewarded,
      getReferralLink,
      getReferralHistory,
      incrementReferralCount,
      addReferralEarning,
      calculateInvestmentProfit,
      calculateInvestmentReturn,
      findInvestmentPlan,
      formatInvestmentPlans,
      createInvestment,
      getActiveInvestments,
      getMaturedInvestments,
      claimMaturedInvestments,
      addInvestmentHistory,
      getInvestmentStats,
      createTask,
      getTasks,
      submitTaskProof,
      approveTaskSubmission,
      rejectTaskSubmission,
      broadcastMessage,
      getBotStats,
      getActiveUsers,
      scheduleJob,
      parseAmount,
      isPositiveAmount,
      isMinAmount,
      isTelegramUserId,
      isValidUrl,
      normalizeTelegramUsername,
      isTelegramUsername,
      telegramLink,
      parseCommandArgs,
      getArg,
      hasArgs,
      isPhoneNumber,
      normalizePhone,
      onlyDigits,
      onlyLetters,
      slugify,
      containsBadWords,
      startsWithAny,
      includesAny,
      validateRequired,
      validateLength,
      button,
      urlButton,
      row,
      inlineMenu,
      bottomMenu,
      removeKeyboard,
      backButton,
      cancelButton,
      confirmButtons,
      channelJoinButtons,
      mainMenuKeyboard,
      adminBackMenu,
      pageButtons,
      menuText,
      section,
      divider,
      statusLine,
      alertBox,
      progressBar,
      listItems,
      notifyAdmin,
      notifyUser,
      notifyUserPhoto,
      notifyUserDocument: async (userId, document, options = {}) => sendDocument(userId, document, options),
      getIncomingPhoto,
      sendPayoutNotice,
      buildPayoutMessage,
      sendAdminLog,
      sendToChannel,
      safeSendMessage,
      safeEditMessage,
      safeEditReplyMarkup,
      createSupportTicket,
      getSupportTicket,
      updateSupportTicket,
      closeSupportTicket,
      lockSupportTicket,
      unlockSupportTicket,
      isTicketLocked,
      isTicketClosed,
      getOpenSupportTickets,
      searchSupportTicket,
      formatSupportTicketOpen,
      formatSupportTicketClosed,
      formatSupportTicketPreview,
      buildSupportReplyKeyboard,
      buildClosedTicketKeyboard,
      getSupportAdmins,
      addSupportAdmin,
      removeSupportAdmin,
      isSupportAdmin,
      notifySupportAdmins,
      editTicketMessage,
      updateSavedAdminMessages,
      saveAdminMessageRef,
      getAdminMessageRefs,
      clearAdminMessageRefs,
      safeReply,
      broadcastToUsers,
      queueBroadcast,
      getBroadcastStatus,
      previewBroadcast,
      saveNotificationTemplate,
      getNotificationTemplate,
      renderTemplate,
      preciseAmount,
    },
    storageMutations: () => ({
      bot: botMutations,
      user: userMutations,
      cross_users: crossUserMutations,
    }),
  };
}

function safeBotMetadata(bot) {
  return {
    id: bot.id ?? null,
    name: bot.name ?? null,
    username: bot.username ?? null,
    language: bot.language ?? null,
    status: bot.status ?? null,
  };
}

function safeCommandMetadata(command) {
  return {
    id: command.id ?? null,
    name: command.name ?? command.trigger ?? null,
    trigger: command.trigger ?? command.name ?? null,
    type: command.type ?? null,
  };
}

function normalizeCommandFlow(flow, command) {
  const source = plainObject(flow || {});
  const active = source.active === true;

  return {
    active,
    step: source.step ?? null,
    data: plainObject(source.data || {}),
    command_name: source.command_name ?? command.trigger ?? command.name ?? null,
    command_id: source.command_id ?? command.id ?? null,
  };
}

function userFromMessage(message, telegram) {
  const from = plainObject(message.from || {});

  return {
    id: from.id ?? telegram.user_id ?? null,
    first_name: from.first_name ?? telegram.first_name ?? null,
    last_name: from.last_name ?? telegram.last_name ?? null,
    username: from.username ?? telegram.username ?? null,
    language_code: from.language_code ?? telegram.language_code ?? null,
  };
}

function chatFromMessage(message, telegram) {
  const chat = plainObject(message.chat || {});

  return {
    id: chat.id ?? telegram.chat_id ?? null,
    type: chat.type ?? null,
    title: chat.title ?? null,
    username: chat.username ?? null,
  };
}

function normalizeMessageOptions(options) {
  const value = normalizeObject(options, 'message options');
  const normalized = {};

  if (typeof value.parse_mode === 'string') {
    normalized.parse_mode = value.parse_mode;
  }

  if (typeof value.disable_web_page_preview === 'boolean') {
    normalized.disable_web_page_preview = value.disable_web_page_preview;
  }

  if (typeof value.protect_content === 'boolean') {
    normalized.protect_content = value.protect_content;
  }

  if (value.reply_to_message_id !== undefined && value.reply_to_message_id !== null) {
    normalized.reply_to_message_id = requireMessageId(value.reply_to_message_id, 'reply_to_message_id');
  }

  if (isPlainObject(value.reply_markup)) {
    normalized.reply_markup = plainObject(value.reply_markup);
  } else if (Array.isArray(value.inline_keyboard)) {
    normalized.reply_markup = inlineKeyboard(value.inline_keyboard);
  }

  return normalized;
}

function normalizePhotoOptions(options) {
  const value = normalizeObject(options, 'photo options');
  const normalized = {};

  if (typeof value.caption === 'string') {
    normalized.caption = value.caption;
  }

  if (typeof value.parse_mode === 'string') {
    normalized.parse_mode = value.parse_mode;
  }

  if (typeof value.protect_content === 'boolean') {
    normalized.protect_content = value.protect_content;
  }

  if (value.reply_to_message_id !== undefined && value.reply_to_message_id !== null) {
    normalized.reply_to_message_id = requireMessageId(value.reply_to_message_id, 'reply_to_message_id');
  }

  if (isPlainObject(value.reply_markup)) {
    normalized.reply_markup = plainObject(value.reply_markup);
  } else if (Array.isArray(value.inline_keyboard)) {
    normalized.reply_markup = inlineKeyboard(value.inline_keyboard);
  }

  return normalized;
}

function normalizeRuntimeSettings(settings) {
  const value = isPlainObject(settings) ? settings : {};

  return {
    command_timeout_ms: clampNumber(value.command_timeout_ms, executionTimeoutMs, 1000, 30000),
    max_delay_ms: clampNumber(value.max_delay_ms, maxDelayMs, 0, 30000),
  };
}

function normalizeCommandCode(code) {
  return String(code || '')
    .replace(/^\uFEFF/, '')
    .split(/\r?\n/)
    .filter((line) => !/^\s*```[A-Za-z0-9_-]*\s*$/.test(line))
    .join('\n');
}

function clampNumber(value, fallback, min = null, max = null) {
  if (max === null) {
    return clamp(value, fallback, min);
  }

  const numeric = Number(value);

  if (!Number.isFinite(numeric)) {
    return fallback;
  }

  return Math.min(Math.max(Math.floor(numeric), min), max);
}

function fallbackExecutionId() {
  return `runtime-${Date.now().toString(36)}-${Math.random().toString(36).slice(2, 12)}`;
}

function keyboard(rows, options = {}) {
  if (!Array.isArray(rows)) {
    throw new Error('keyboard(rows) expects an array of rows.');
  }

  const normalizedRows = rows.map((row) => {
    if (!Array.isArray(row)) {
      throw new Error('keyboard rows must be arrays.');
    }

    return row.map((button) => {
      if (typeof button === 'string') {
        return { text: button };
      }

      if (isPlainObject(button) && typeof button.text === 'string') {
        return plainObject(button);
      }

      throw new Error('keyboard buttons must be strings or button objects.');
    });
  });

  const value = normalizeObject(options, 'keyboard options');

  return {
    keyboard: normalizedRows,
    resize_keyboard: value.resize_keyboard !== undefined ? Boolean(value.resize_keyboard) : true,
    one_time_keyboard: Boolean(value.one_time_keyboard),
    selective: Boolean(value.selective),
  };
}

function inlineKeyboard(rows) {
  if (!Array.isArray(rows)) {
    throw new Error('inlineKeyboard(rows) expects an array of rows.');
  }

  return {
    inline_keyboard: rows.map((row) => {
      if (!Array.isArray(row)) {
        throw new Error('inlineKeyboard rows must be arrays.');
      }

      return row.map((button) => {
        if (!isPlainObject(button) || typeof button.text !== 'string') {
          throw new Error('inlineKeyboard buttons must be objects with text.');
        }

        const out = { text: button.text };

        if (typeof button.url === 'string') {
          out.url = requireHttpsUrl(button.url, 'inlineKeyboard button url');
        }

        if (typeof button.callback_data === 'string') {
          out.callback_data = button.callback_data.slice(0, 64);
        }

        return out;
      });
    }),
  };
}

function button(text, callbackData) {
  return { text: String(text ?? ''), callback_data: String(callbackData ?? '').slice(0, 64) };
}

function replyKeyboard(rows, options = {}) {
  return keyboard(rows, options);
}

function urlButton(text, url) {
  return { text: String(text ?? ''), url: requireHttpsUrl(url, 'urlButton url') };
}

function row(...buttons) {
  return buttons.flat();
}

function inlineMenu(rows) {
  return { reply_markup: inlineKeyboard(rows) };
}

function bottomMenu(rows, options = {}) {
  return { reply_markup: keyboard(rows, { resize_keyboard: true, ...normalizeObject(options) }) };
}

function removeKeyboard() {
  return { remove_keyboard: true };
}

function forceReply(options = {}) {
  const value = normalizeObject(options, 'forceReply options');
  const out = {
    force_reply: true,
    selective: Boolean(value.selective),
  };
  if (typeof value.input_field_placeholder === 'string') {
    out.input_field_placeholder = value.input_field_placeholder.slice(0, 64);
  }
  return out;
}

function backButton(target = '/main_menu', text = '⬅️ Back') {
  return button(text, target);
}

function cancelButton(target = '/cancel', text = '❌ Cancel') {
  return button(text, target);
}

function confirmButtons(confirmTarget, cancelTarget = '/cancel') {
  return [[button('✅ Confirm', confirmTarget), button('❌ Cancel', cancelTarget)]];
}

function confirmCancelKeyboard(confirmData, cancelData) {
  return inlineKeyboard([[button('Confirm', confirmData), button('Cancel', cancelData)]]);
}

function paginateKeyboard(items, page, perPage, callbackPrefix) {
  const list = Array.isArray(items) ? items : [];
  const safePerPage = Math.max(1, Math.floor(toNumber(perPage, 10)));
  const totalPages = Math.max(1, Math.ceil(list.length / safePerPage));
  const currentPage = Math.min(Math.max(1, Math.floor(toNumber(page, 1))), totalPages);
  const prefix = String(callbackPrefix || '').trim();
  const nav = [];

  if (currentPage > 1) nav.push(button('Prev', `${prefix} ${currentPage - 1}`.trim()));
  nav.push(button(`${currentPage}/${totalPages}`, `${prefix} ${currentPage}`.trim()));
  if (currentPage < totalPages) nav.push(button('Next', `${prefix} ${currentPage + 1}`.trim()));

  return inlineKeyboard([nav]);
}

function channelJoinButtons(channels, verifyCommand = '/verify') {
  if (!Array.isArray(channels)) throw new Error('channelJoinButtons expects an array of channels.');
  const rows = channels.map((ch) => [{ text: String(ch.name ?? 'Join'), url: requireHttpsUrl(ch.url, 'channelJoinButtons url') }]);
  rows.push([button('✅ Verify Joined', verifyCommand)]);
  return rows;
}

function mainMenuKeyboard() {
  return keyboard([['💳 Balance'], ['🔗 Ref Stats', '🎁 Bonus'], ['💰 FaucetPay Instant Send']]);
}

function adminBackMenu() {
  return [[{ text: '⬅️ Back to Admin', callback_data: '/admin' }]];
}

function pageButtons(prevCommand, nextCommand) {
  return [[button('⬅️ Prev', prevCommand), button('Next ➡️', nextCommand)]];
}

function menuText(title, lines) {
  const safeTitle = escapeHTML(String(title ?? ''));
  const items = Array.isArray(lines) ? lines : [];
  if (items.length === 0) return `<b>${safeTitle}</b>`;
  return `<b>${safeTitle}</b>\n\n${items.map((l) => `• ${String(l ?? '')}`).join('\n')}`;
}

function section(title, lines) {
  const safeTitle = escapeHTML(String(title ?? ''));
  const items = Array.isArray(lines) ? lines : [];
  if (items.length === 0) return `<b>${safeTitle}</b>`;
  return `<b>${safeTitle}</b>\n${items.map((l) => `• ${String(l ?? '')}`).join('\n')}`;
}

function divider() {
  return '━━━━━━━━━━━━';
}

function statusLine(label, value) {
  return `• <b>${escapeHTML(String(label ?? ''))}:</b> ${escapeHTML(String(value ?? ''))}`;
}

function alertBox(type, title, message) {
  const icons = { success: '✅', error: '❌', warning: '⚠️', info: 'ℹ️' };
  const icon = icons[String(type)] || 'ℹ️';
  return `${icon} <b>${escapeHTML(String(title ?? ''))}</b>\n${escapeHTML(String(message ?? ''))}`;
}

function progressBar(value, total, length) {
  const len = Math.max(1, Math.floor(Number(length) || 10));
  const t = toNumber(total, 0);
  const v = toNumber(value, 0);
  const pct = t > 0 ? Math.min(1, Math.max(0, v / t)) : 0;
  const filled = Math.round(pct * len);
  return `${'█'.repeat(filled)}${'░'.repeat(len - filled)} ${Math.round(pct * 100)}%`;
}

function listItems(items) {
  if (!Array.isArray(items)) return '';
  return items.map((item) => `• ${String(item ?? '')}`).join('\n');
}

function preciseAmount(value) {
  const n = Number(value);
  if (!Number.isFinite(n)) return '0';
  const s = n.toFixed(8).replace(/\.?0+$/, '') || '0';
  const [int, dec = ''] = s.split('.');
  const intFmt = Math.abs(parseInt(int, 10)).toLocaleString('en-US');
  const sign = n < 0 ? '-' : '';
  const decDisplay = dec.length >= 2 ? dec : dec.padEnd(2, '0');
  return `${sign}${intFmt}.${decDisplay}`;
}

function buildPayoutMessage(data) {
  const d = isPlainObject(data) ? plainObject(data) : {};
  const userId = d.user_id ?? '';
  const amount = d.amount ?? 0;
  const currency = String(d.currency || 'USDT');
  const wallet = String(d.wallet || '');
  const botName = String(d.bot || '');
  const maskedId = maskUserId(userId);
  const maskedWallet = wallet.includes('@') ? maskEmail(wallet) : (wallet ? maskUserId(wallet) : '—');
  const formattedAmount = formatNumber(amount, 2);
  return (
    `✅ <b>Withdrawal Completed</b>\n\n` +
    `<u>Transaction Details</u>\n` +
    `• <b>User ID:</b> ${escapeHTML(maskedId)}\n` +
    `• <b>Amount:</b> ${escapeHTML(formattedAmount)} ${escapeHTML(currency)}\n` +
    `• <b>FaucetPay Email:</b> ${escapeHTML(maskedWallet)}\n` +
    `• <b>Bot:</b> @${escapeHTML(botName)}\n` +
    `• <b>Status:</b> Completed`
  );
}

function renderTemplate(template, data) {
  const str = String(template ?? '');
  const vals = isPlainObject(data) ? plainObject(data) : {};
  return str.replace(/\{\{(\w+)\}\}/g, (_, key) => {
    const val = vals[key];
    return val !== undefined && val !== null ? String(val) : '';
  });
}

async function telegramApiRequest(token, method, payload = {}) {
  const controller = new AbortController();
  const timeout = setTimeout(() => controller.abort(), requestTimeoutMs);

  try {
    const response = await fetch(`https://api.telegram.org/bot${token}/${method}`, {
      method: 'POST',
      headers: { 'content-type': 'application/json' },
      body: JSON.stringify(payload),
      redirect: 'error',
      signal: controller.signal,
    });

    const text = await response.text();
    const data = parseJsonResponse(text);

    if (!data || typeof data !== 'object') {
      return { ok: false, description: 'Telegram returned an invalid response.' };
    }

    return data;
  } catch (error) {
    return { ok: false, description: 'Telegram request failed.' };
  } finally {
    clearTimeout(timeout);
  }
}

async function faucetPayBalance(apiKey, currency) {
  const data = await faucetPayPost('https://faucetpay.io/api/v1/getbalance', {
    api_key: requireString(apiKey, 'faucetPayBalance apiKey'),
    currency: requireString(currency, 'faucetPayBalance currency').toUpperCase(),
  });
  const sym = requireString(currency, 'faucetPayBalance currency').toUpperCase();
  return faucetPayResult(data, sym, { balance: extractFaucetPayBalance(data, sym) });
}

async function faucetPaySend(apiKey, toEmailOrAddress, amount, currency) {
  const safeAmount = normalizeDecimalAmount(amount, false);
  const sym = requireString(currency, 'faucetPaySend currency').toUpperCase();
  const data = await faucetPayPost('https://faucetpay.io/api/v1/send', {
    api_key: requireString(apiKey, 'faucetPaySend apiKey'),
    to: requireString(toEmailOrAddress, 'faucetPaySend recipient'),
    amount: safeAmount,
    currency: sym,
  });
  return faucetPayResult(data, sym, { amount: safeAmount, amount_smallest_unit: safeAmount });
}

async function faucetPayCheckAddress(apiKey, currency, address) {
  const sym = requireString(currency, 'faucetPayCheckAddress currency').toUpperCase();
  const data = await faucetPayPost('https://faucetpay.io/api/v1/checkaddress', {
    api_key: requireString(apiKey, 'faucetPayCheckAddress apiKey'),
    currency: sym,
    address: requireString(address, 'faucetPayCheckAddress address'),
  });
  return faucetPayResult(data, sym);
}

async function faucetPayCurrencies(apiKey) {
  const data = await faucetPayPost('https://faucetpay.io/api/v1/currencies', {
    api_key: requireString(apiKey, 'faucetPayCurrencies apiKey'),
  });
  return faucetPayResult(data, null, { currencies: extractFaucetPayCurrencies(data) });
}

function faucetPayResult(data, currency = null, extra = {}) {
  const raw = plainObject(data || {});
  const ok = Number(raw.status || 0) === 200;
  const message = String(raw.message || (ok ? 'OK' : 'FaucetPay request failed.'));
  return {
    ok,
    status: Number(raw.status || 0),
    message,
    error: ok ? null : friendlyFaucetPayError(message),
    ...(currency ? { currency } : {}),
    ...extra,
    data: raw,
    raw,
  };
}

function extractFaucetPayCurrencies(raw) {
  const candidates = [raw && raw.currencies, raw && raw.data && raw.data.currencies, raw && raw.data];
  for (const candidate of candidates) {
    if (candidate && typeof candidate === 'object') {
      const values = Array.isArray(candidate) ? candidate : Object.keys(candidate);
      const symbols = [...new Set(values.map((value) => String(value || '').toUpperCase().replace(/[^A-Z0-9]/g, '')).filter(Boolean))];
      if (symbols.length > 0) return symbols;
    }
  }
  return [];
}

function friendlyFaucetPayError(message) {
  const text = String(message || '').trim();
  const lower = text.toLowerCase();
  if (lower.includes('invalid') && lower.includes('api')) return 'FaucetPay API key is invalid.';
  if (lower.includes('address') || lower.includes('linked') || lower.includes('payout')) return 'FaucetPay email/address is not linked.';
  return text || 'FaucetPay request failed.';
}

function normalizeDecimalAmount(value, allowZero = false) {
  let input = String(value === null || value === undefined ? '' : value).replace(/,/g, '').trim();
  if (input === '') throw new Error('Amount is required.');

  if (/^[+-]?\d+(?:\.\d+)?e[+-]?\d+$/i.test(input)) {
    input = expandExponentialDecimal(input);
  }

  if (!/^\+?(?:\d+|\d*\.\d+)$/.test(input)) {
    throw new Error('Invalid amount.');
  }

  input = input.replace(/^\+/, '');
  let [whole, fraction = ''] = input.split('.');
  whole = whole.replace(/^0+(?=\d)/, '') || '0';
  fraction = fraction.replace(/0+$/, '');

  if (!allowZero && whole === '0' && fraction === '') {
    throw new Error('Amount must be greater than zero.');
  }

  return fraction ? `${whole}.${fraction}` : whole;
}

function decimalToSmallestUnit(value, decimals = 8) {
  const places = Math.max(0, Math.min(18, toInteger(decimals, 8)));
  const decimal = normalizeDecimalAmount(value, true);
  let [whole, fraction = ''] = decimal.split('.');
  fraction = (fraction + '0'.repeat(places)).slice(0, places);
  return (BigInt(whole || '0') * (10n ** BigInt(places)) + BigInt(fraction || '0')).toString();
}

function smallestUnitToDecimal(value, decimals = 8) {
  const places = Math.max(0, Math.min(18, toInteger(decimals, 8)));
  const negative = String(value || '').trim().startsWith('-');
  const digits = String(value || '0').replace(/\D/g, '') || '0';
  if (places === 0) return `${negative ? '-' : ''}${digits}`;
  const padded = digits.padStart(places + 1, '0');
  const whole = padded.slice(0, -places) || '0';
  const fraction = padded.slice(-places).replace(/0+$/, '');
  const out = fraction ? `${whole}.${fraction}` : whole;
  return negative && out !== '0' ? `-${out}` : out;
}

function expandExponentialDecimal(value) {
  const num = Number(value);
  if (!Number.isFinite(num)) return String(value);
  return num.toLocaleString('en-US', {
    useGrouping: false,
    maximumFractionDigits: 20,
  }).replace(/0+$/, '').replace(/\.$/, '');
}

function extractFaucetPayBalance(raw, currency) {
  const sym = String(currency || '').toUpperCase();
  const candidates = [
    raw && raw.balance,
    raw && raw.balances && raw.balances[sym],
    raw && raw.data && raw.data.balance,
    raw && raw.data && raw.data.balances && raw.data.balances[sym],
    raw && raw[sym],
  ];

  for (const candidate of candidates) {
    if (candidate === null || candidate === undefined || candidate === '') continue;
    if (typeof candidate === 'object') {
      const nested = candidate.balance ?? candidate.available ?? candidate.amount;
      if (nested !== null && nested !== undefined && nested !== '') {
        return normalizeDecimalAmount(nested, true);
      }
      continue;
    }
    return normalizeDecimalAmount(candidate, true);
  }

  return '0';
}

function fpDecimals(currency) {
  const map = {
    BTC: 8, LTC: 8, DOGE: 8, BCH: 8, DASH: 8, ZEC: 8, DGB: 8, VIA: 8,
    ETH: 8, BNB: 8, MATIC: 8, AVAX: 8, FTM: 8,
    XRP: 6, TRX: 6,
    USDT: 8, USDC: 8, BUSD: 8, TUSD: 8, DAI: 8,
    SOL: 9,
    ADA: 6,
    XLM: 7,
    PEPE: 8, SHIB: 8, FLOKI: 8, BABYDOGE: 8,
  };
  return map[String(currency || '').toUpperCase()] ?? 8;
}

async function faucetPayPost(url, form) {
  const response = await httpsRequest(url, {
    method: 'POST',
    form,
    timeout_ms: requestTimeoutMs,
  });

  if (!response || typeof response !== 'object') {
    throw new Error('FaucetPay returned an invalid response.');
  }

  return response.data;
}

function maskEmail(email) {
  const value = String(email || '').trim();
  const at = value.indexOf('@');

  if (at <= 0) {
    return value.length <= 2 ? '*'.repeat(value.length) : `${value.slice(0, 2)}${'*'.repeat(Math.max(value.length - 2, 0))}`;
  }

  const local = value.slice(0, at);
  const domain = value.slice(at);
  const visible = local.slice(0, Math.min(2, local.length));

  return `${visible}${'*'.repeat(Math.max(local.length - visible.length, 4))}${domain}`;
}

function formatNumber(value, decimals = 0) {
  const num = Number(value);
  if (!Number.isFinite(num)) return '0';
  const d = Math.max(0, Math.floor(Number(decimals) || 0));
  return num.toLocaleString('en-US', { minimumFractionDigits: 0, maximumFractionDigits: d });
}

function formatMoney(value, symbol = '', decimals = 2) {
  const formatted = formatNumber(value, decimals);
  const sym = String(symbol || '').trim();
  return sym ? `${formatted} ${sym}` : formatted;
}

function formatCryptoAmount(value, currency = '', maxDecimals = 8) {
  return formatMoney(value, currency, maxDecimals);
}

function normalizeCurrency(value, fallback = 'USDT') {
  const normalized = String(value || '').trim().toUpperCase().replace(/[^A-Z0-9_]/g, '');
  return normalized || String(fallback || 'USDT').trim().toUpperCase() || 'USDT';
}

function isEmail(value) {
  return typeof value === 'string' && /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(value.trim());
}

function isValidUrl(value) {
  try {
    const url = new URL(String(value || '').trim());
    return url.protocol === 'http:' || url.protocol === 'https:';
  } catch (_) {
    return false;
  }
}

function isNumber(value) {
  if (value === null || value === undefined || value === '') return false;
  return Number.isFinite(Number(value));
}

function toNumber(value, fallback = 0) {
  const num = Number(value);
  if (Number.isFinite(num)) return num;
  const fb = Number(fallback);
  return Number.isFinite(fb) ? fb : 0;
}

function toInteger(value, fallback = 0) {
  return Math.trunc(toNumber(value, fallback));
}

function clamp(value, min, max) {
  return Math.min(Math.max(toNumber(value, 0), toNumber(min, 0)), toNumber(max, 0));
}

function roundAmount(value, decimals = 8) {
  const d = Math.max(0, Math.min(18, toInteger(decimals, 8)));
  const factor = 10 ** d;
  return Math.round(toNumber(value, 0) * factor) / factor;
}

function percent(value, total, decimals = 2) {
  const t = toNumber(total, 0);
  if (t === 0) return 0;
  const d = Math.max(0, Math.floor(toNumber(decimals, 2)));
  return parseFloat(((toNumber(value, 0) / t) * 100).toFixed(d));
}

function today() {
  return new Date().toISOString().slice(0, 10);
}

function timeNow() {
  return new Date().toISOString().slice(11, 19);
}

function now() {
  return new Date().toISOString();
}

function nowMs() {
  return Date.now();
}

function addMinutes(timestamp, minutes) {
  const ms = new Date(timestamp).getTime();
  if (isNaN(ms)) return null;
  return new Date(ms + toNumber(minutes, 0) * 60000).toISOString();
}

function addHours(timestamp, hours) {
  const ms = new Date(timestamp).getTime();
  if (isNaN(ms)) return null;
  return new Date(ms + toNumber(hours, 0) * 3600000).toISOString();
}

function addDays(timestamp, days) {
  const ms = new Date(timestamp).getTime();
  if (isNaN(ms)) return null;
  return new Date(ms + toNumber(days, 0) * 86400000).toISOString();
}

function shuffleArray(array) {
  if (!Array.isArray(array)) return [];
  const copy = [...array];
  for (let i = copy.length - 1; i > 0; i--) {
    const j = Math.floor(Math.random() * (i + 1));
    [copy[i], copy[j]] = [copy[j], copy[i]];
  }
  return copy;
}

function timeLeft(targetTimestamp) {
  const now = Date.now();
  const target = new Date(targetTimestamp).getTime();
  if (isNaN(target) || target <= now) return 'Expired';
  const diff = target - now;
  const h = Math.floor(diff / 3600000);
  const m = Math.floor((diff % 3600000) / 60000);
  if (h > 0) return `${h}h ${m}m`;
  const s = Math.floor((diff % 60000) / 1000);
  return m > 0 ? `${m}m ${s}s` : `${s}s`;
}

function randomChoice(array) {
  if (!Array.isArray(array) || array.length === 0) return null;
  return array[Math.floor(Math.random() * array.length)];
}

function sanitizeText(text, maxLength = 500) {
  const max = Math.min(Math.max(1, toNumber(maxLength, 500)), 10000);
  return String(text ?? '').replace(/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/g, '').trim().slice(0, max);
}

function safeText(value, fallback = '') {
  if (value === null || value === undefined) return String(fallback ?? '');
  return String(value);
}

function safeTelegramError(error) {
  const raw = typeof error === 'string' ? error : String((error && error.message) || error || 'Telegram API error.');
  return raw
    .replace(/bot\d+:[A-Za-z0-9_-]+/g, 'bot[redacted]')
    .replace(/(token|secret|api[_-]?key|password)=\S+/gi, '$1=[redacted]')
    .slice(0, 300);
}

function escapeHTML(text) {
  return String(text ?? '')
    .replace(/&/g, '&amp;')
    .replace(/</g, '&lt;')
    .replace(/>/g, '&gt;')
    .replace(/"/g, '&quot;')
    .replace(/'/g, '&#39;');
}

function makeRefLink(botUsername, userId) {
  return `https://t.me/${String(botUsername || '').replace(/^@/, '')}?start=${String(userId || '')}`;
}

function maskUserId(userId) {
  const s = String(userId ?? '');
  if (s.length <= 6) return s;
  return `${s.slice(0, 4)}***${s.slice(-4)}`;
}

function statusBadge(status) {
  const badges = {
    active: '✅ Active', pending: '⏳ Pending', blocked: '⛔ Blocked',
    verified: '✅ Verified', unverified: '❌ Unverified', banned: '🚫 Banned', expired: '❌ Expired',
  };
  const key = String(status ?? '').toLowerCase().trim();
  return badges[key] || `${key.charAt(0).toUpperCase()}${key.slice(1)}`;
}

function shortText(text, length = 100) {
  const max = Math.max(1, toNumber(length, 100));
  const str = String(text ?? '');
  return str.length > max ? `${str.slice(0, max - 3)}...` : str;
}

// ── INPUT VALIDATION & PARSING HELPERS ──────────────────────────────────────

function parseAmount(value, fallback = 0) {
  const cleaned = String(value === null || value === undefined ? '' : value).replace(/,/g, '').trim();
  const num = Number(cleaned);
  return Number.isFinite(num) ? num : fallback;
}

function isPositiveAmount(value) {
  const num = parseAmount(value, NaN);
  return Number.isFinite(num) && num > 0;
}

function isMinAmount(value, minimum) {
  const num = parseAmount(value, NaN);
  return Number.isFinite(num) && num >= toNumber(minimum, 0);
}

function isTelegramUserId(value) {
  return /^\d{5,20}$/.test(String(value || '').trim());
}

function normalizeTelegramUsername(value) {
  const s = String(value || '').trim();
  const m = s.match(/(?:https?:\/\/)?t\.me\/([a-zA-Z0-9_]{3,})/);
  if (m) return '@' + m[1];
  const clean = s.replace(/^@/, '');
  return /^[a-zA-Z0-9_]{3,}$/.test(clean) ? '@' + clean : s;
}

function isTelegramUsername(value) {
  const clean = String(value || '').trim().replace(/^@/, '');
  return /^[a-zA-Z0-9_]{3,32}$/.test(clean);
}

function telegramLink(usernameOrUrl) {
  const s = String(usernameOrUrl || '').trim();
  const m = s.match(/(?:https?:\/\/)?t\.me\/([a-zA-Z0-9_]{3,})/);
  if (m) return 'https://t.me/' + m[1];
  return 'https://t.me/' + s.replace(/^@/, '');
}

function parseCommandArgs(text) {
  const parts = String(text || '').trim().split(/\s+/).filter(Boolean);
  return parts[0] && parts[0].startsWith('/') ? parts.slice(1) : parts;
}

function isPhoneNumber(value) {
  return /^\+?\d{7,15}$/.test(String(value || '').replace(/[\s\-(). ]/g, ''));
}

function normalizePhone(value) {
  return String(value || '').replace(/[\s\-(). ]/g, '');
}

function onlyDigits(value) {
  return String(value || '').replace(/\D/g, '');
}

function onlyLetters(value) {
  return String(value || '').replace(/[^a-zA-Z ]/g, '');
}

function slugify(text) {
  return String(text || '').toLowerCase().trim()
    .replace(/[^a-z0-9\s-]/g, '').replace(/\s+/g, '-').replace(/-+/g, '-').replace(/^-|-$/g, '');
}

function containsBadWords(text, badWords = []) {
  if (!Array.isArray(badWords) || badWords.length === 0) return false;
  const lower = String(text || '').toLowerCase();
  return badWords.some((w) => lower.includes(String(w || '').toLowerCase()));
}

function startsWithAny(text, list) {
  if (!Array.isArray(list)) return false;
  const s = String(text || '');
  return list.some((item) => s.startsWith(String(item || '')));
}

function includesAny(text, list) {
  if (!Array.isArray(list)) return false;
  const s = String(text || '');
  return list.some((item) => s.includes(String(item || '')));
}

function validateRequired(value) {
  if (value === null || value === undefined) return false;
  if (typeof value === 'string') return value.trim().length > 0;
  return true;
}

function validateLength(value, min = 0, max = 500) {
  const s = String(value === null || value === undefined ? '' : value);
  return s.length >= min && s.length <= max;
}

// ── END INPUT HELPERS ────────────────────────────────────────────────────────

async function httpsRequest(url, options = {}) {
  const target = requireHttpsUrl(url, 'httpsRequest url');
  const parsed = new URL(target);

  await assertPublicHostname(parsed.hostname);

  const requestOptions = normalizeHttpsRequestOptions(options);
  const controller = new AbortController();
  const timeout = setTimeout(() => controller.abort(), requestOptions.timeout_ms);

  try {
    const response = await fetch(parsed.toString(), {
      method: requestOptions.method,
      headers: requestOptions.headers,
      body: requestOptions.body,
      signal: controller.signal,
      redirect: 'error',
    });

    const contentLength = Number(response.headers.get('content-length') || 0);

    if (contentLength > maxResponseBytes) {
      throw new Error(`httpsRequest response is larger than ${maxResponseBytes} bytes.`);
    }

    const text = await readLimitedResponse(response);
    const contentType = response.headers.get('content-type') || '';
    const data = contentType.includes('application/json') ? parseJsonResponse(text) : text;

    return {
      ok: response.ok,
      status: response.status,
      statusText: response.statusText,
      headers: safeResponseHeaders(response.headers),
      data,
      text,
    };
  } catch (error) {
    if (error && error.name === 'AbortError') {
      throw new Error('httpsRequest timed out.');
    }

    throw error;
  } finally {
    clearTimeout(timeout);
  }
}

async function internalRuntimePost(url, payload, secret, timeoutMs = requestTimeoutMs, label = 'runtime bridge', paymentBridge = null) {
  let parsed;

  try {
    parsed = new URL(String(url || ''));
  } catch (error) {
    return { ok: false, error: `${label} URL is invalid.` };
  }

  if (!['http:', 'https:'].includes(parsed.protocol)) {
    return { ok: false, error: `${label} URL must use HTTP or HTTPS.` };
  }

  if (parsed.protocol === 'http:' && !isLoopbackHostname(parsed.hostname)) {
    return { ok: false, error: `${label} URL must use HTTPS for public hosts.` };
  }

  const controller = new AbortController();
  const timeout = setTimeout(() => controller.abort(), Math.max(1, Number(timeoutMs) || requestTimeoutMs));

  try {
    const response = await fetch(parsed.toString(), {
      method: 'POST',
      headers: {
        'content-type': 'application/json',
        'accept': 'application/json',
        'x-runtime-secret': String(secret || ''),
      },
      body: safeJsonStringify(payload),
      signal: controller.signal,
      redirect: 'follow',
    });

    const text = await response.text();
    const data = parseJsonResponse(text);

    if (!data || typeof data !== 'object') {
      return { ok: false, error: `${label} returned an invalid response (HTTP ${response.status}).` };
    }

    return data;
  } catch (error) {
    if (error && error.name === 'AbortError') {
      if (paymentBridge === 'telegram') {
        return { ok: false, error: 'Telegram did not respond in time.', error_type: 'TelegramBridgeTimeout', bridge: 'telegram' };
      }
      if (paymentBridge) {
        return { ok: false, error: 'Payment service timed out. Please try again.', error_type: 'PaymentBridgeTimeout', bridge: paymentBridge };
      }
      return { ok: false, error: `${label} timed out.` };
    }
    const msg = (error && error.message) ? String(error.message) : String(error || 'unknown');
    return { ok: false, error: `${label} request failed: ${msg}` };
  } finally {
    clearTimeout(timeout);
  }
}

function normalizeHttpsRequestOptions(options) {
  const value = normalizeObject(options, 'httpsRequest options');
  const method = String(value.method || 'GET').toUpperCase();

  if (!['GET', 'POST'].includes(method)) {
    throw new Error('httpsRequest only supports GET and POST.');
  }

  const timeoutMs = Math.min(Math.max(Number(value.timeout_ms || value.timeout || requestTimeoutMs), 1), requestTimeoutMs);
  const headers = normalizeHeaders(value.headers || {});
  let body = undefined;

  if (method === 'POST') {
    if (value.json !== undefined) {
      body = safeJsonStringify(value.json);
      headers['content-type'] = headers['content-type'] || 'application/json';
    } else if (value.form !== undefined) {
      if (!isPlainObject(value.form)) {
        throw new Error('httpsRequest form must be an object.');
      }

      const params = new URLSearchParams();

      for (const [key, formValue] of Object.entries(value.form)) {
        if (formValue !== undefined && formValue !== null) {
          params.append(key, String(formValue));
        }
      }

      body = params.toString();
      headers['content-type'] = headers['content-type'] || 'application/x-www-form-urlencoded';
    } else if (typeof value.body === 'string') {
      body = value.body;
    } else if (value.body !== undefined) {
      body = safeJsonStringify(value.body);
      headers['content-type'] = headers['content-type'] || 'application/json';
    }
  }

  return {
    method,
    headers,
    body,
    timeout_ms: timeoutMs,
  };
}

function normalizeHeaders(headers) {
  if (!isPlainObject(headers)) {
    throw new Error('httpsRequest headers must be an object.');
  }

  const blocked = new Set(['host', 'connection', 'content-length', 'transfer-encoding', 'upgrade', 'cookie', 'authorization']);
  const normalized = {};

  for (const [key, value] of Object.entries(headers)) {
    const header = String(key).trim().toLowerCase();

    if (!header || blocked.has(header)) {
      continue;
    }

    if (typeof value === 'string' || typeof value === 'number' || typeof value === 'boolean') {
      normalized[header] = String(value);
    }
  }

  return normalized;
}

async function readLimitedResponse(response) {
  const reader = response.body && response.body.getReader ? response.body.getReader() : null;

  if (!reader) {
    const text = await response.text();

    if (Buffer.byteLength(text, 'utf8') > maxResponseBytes) {
      throw new Error(`httpsRequest response is larger than ${maxResponseBytes} bytes.`);
    }

    return text;
  }

  const chunks = [];
  let total = 0;

  while (true) {
    const { done, value } = await reader.read();

    if (done) {
      break;
    }

    total += value.byteLength;

    if (total > maxResponseBytes) {
      throw new Error(`httpsRequest response is larger than ${maxResponseBytes} bytes.`);
    }

    chunks.push(Buffer.from(value));
  }

  return Buffer.concat(chunks).toString('utf8');
}

function safeResponseHeaders(headers) {
  const allowed = ['content-type', 'cache-control', 'etag', 'last-modified'];
  const out = {};

  for (const key of allowed) {
    const value = headers.get(key);

    if (value !== null) {
      out[key] = value;
    }
  }

  return out;
}

async function assertPublicHostname(hostname) {
  const normalized = hostname.toLowerCase();

  if (normalized === 'localhost' || normalized.endsWith('.localhost')) {
    throw new Error('httpsRequest cannot access localhost.');
  }

  if (net.isIP(normalized)) {
    if (isPrivateAddress(normalized)) {
      throw new Error('httpsRequest cannot access internal network addresses.');
    }

    return;
  }

  const records = await dns.lookup(normalized, { all: true, verbatim: true });

  if (!records.length) {
    throw new Error('httpsRequest could not resolve hostname.');
  }

  for (const record of records) {
    if (isPrivateAddress(record.address)) {
      throw new Error('httpsRequest cannot access internal network addresses.');
    }
  }
}

function isPrivateAddress(address) {
  if (net.isIPv4(address)) {
    const parts = address.split('.').map((part) => Number(part));
    const [a, b] = parts;

    return a === 10
      || a === 127
      || (a === 169 && b === 254)
      || (a === 172 && b >= 16 && b <= 31)
      || (a === 192 && b === 168)
      || (a === 100 && b >= 64 && b <= 127)
      || a === 0;
  }

  if (net.isIPv6(address)) {
    const value = address.toLowerCase();

    return value === '::1'
      || value === '::'
      || value.startsWith('fe80:')
      || value.startsWith('fc')
      || value.startsWith('fd');
  }

  return true;
}

function isLoopbackHostname(hostname) {
  const value = String(hostname || '').toLowerCase();
  return value === 'localhost' || value === '127.0.0.1' || value === '::1' || value.endsWith('.test');
}

function requireHttpsUrl(value, label) {
  if (typeof value !== 'string') {
    throw new Error(`${label} must be an HTTPS URL.`);
  }

  let parsed;

  try {
    parsed = new URL(value);
  } catch (error) {
    throw new Error(`${label} must be a valid HTTPS URL.`);
  }

  if (parsed.protocol !== 'https:') {
    throw new Error(`${label} must use https://.`);
  }

  if (parsed.username || parsed.password) {
    throw new Error(`${label} cannot include credentials.`);
  }

  return parsed.toString();
}

function requireString(value, label) {
  if (typeof value !== 'string') {
    throw new Error(`${label} expects a string.`);
  }

  return value;
}

function isSecretStorageKey(key) {
  return ['oxapay_merchant_api_key', 'oxapay_payout_api_key', 'faucetpay_api_key'].includes(String(key || ''));
}

function isImmediateSupportBotStorageKey(key) {
  const normalizedKey = String(key || '');
  return normalizedKey === 'support_tickets' || normalizedKey.startsWith('support_ticket_');
}

function isImmediateSupportUserStorageKey(key) {
  return [
    'admin_state',
    'support_reply_ticket_id',
    'support_target_user',
    'admin_reply_ticket_id',
  ].includes(String(key || ''));
}

function maskSecretValue(value) {
  const s = String(value || '').trim();
  if (!s) return '';
  if (s.length <= 8) return `${s.slice(0, 2)}***`;
  return `${s.slice(0, 5)}***${s.slice(-3)}`;
}

function isMaskedSecretValue(value) {
  return /^\S{1,8}\*{3}\S{0,8}$/.test(String(value || '').trim());
}

function isLikelyFaucetPayCurrency(value) {
  return ['BTC', 'ETH', 'DOGE', 'LTC', 'BCH', 'DASH', 'DGB', 'TRX', 'USDT', 'FEY', 'ZEC', 'BNB', 'SOL', 'XRP', 'MATIC', 'ADA', 'TON', 'USDC']
    .includes(String(value || '').trim().toUpperCase());
}

function normalizeChannelMemberArgs(channelUsernameOrId, userId, label) {
  let chatId = channelUsernameOrId;
  let targetUserId = userId;

  if (looksLikeTelegramUserId(channelUsernameOrId) && looksLikeTelegramChannel(userId)) {
    chatId = userId;
    targetUserId = channelUsernameOrId;
  }

  return {
    chat_id: requireChatId(normalizeTelegramChannelId(chatId), label),
    user_id: requireChatId(targetUserId, label),
  };
}

function normalizeTelegramChannelId(value) {
  if (typeof value !== 'string') {
    return value;
  }

  const text = value.trim();
  if (/^-?\d+$/.test(text)) {
    return text;
  }

  const linked = text.match(/(?:https?:\/\/)?t\.me\/([a-zA-Z0-9_]{3,})/);
  if (linked) {
    return `@${linked[1]}`;
  }

  return text.startsWith('@') ? text : `@${text}`;
}

function looksLikeTelegramUserId(value) {
  return /^\d{5,20}$/.test(String(value || '').trim());
}

function looksLikeTelegramChannel(value) {
  const text = String(value || '').trim();
  return /^@?[a-zA-Z0-9_]{3,32}$/.test(text)
    || /^-100\d{5,20}$/.test(text)
    || /(?:https?:\/\/)?t\.me\/[a-zA-Z0-9_]{3,}/.test(text);
}

function isTelegramMembershipStatus(status) {
  return ['member', 'administrator', 'creator'].includes(String(status || '').toLowerCase());
}

function runtimeBridgeBaseUrl() {
  return firstNonEmptyString(
    process.env.NODE_RUNTIME_INTERNAL_URL,
    process.env.APP_INTERNAL_URL,
    process.env.APP_PUBLIC_URL,
    process.env.APP_URL,
  ).replace(/\/+$/, '');
}

function firstNonEmptyString(...values) {
  for (const value of values) {
    if (typeof value !== 'string') {
      continue;
    }

    const text = value.trim();
    if (text !== '') {
      return text;
    }
  }

  return '';
}

function requireChatId(value, label) {
  if (typeof value !== 'string' && typeof value !== 'number') {
    throw new Error(`${label} requires a chat id.`);
  }

  if (String(value).trim() === '') {
    throw new Error(`${label} requires a chat id.`);
  }

  return value;
}

function normalizeCrossUserId(value, label) {
  return String(requireChatId(value, label)).trim();
}

function requireMessageId(value, label) {
  if (typeof value !== 'string' && typeof value !== 'number') {
    throw new Error(`${label} requires a message id.`);
  }

  if (String(value).trim() === '') {
    throw new Error(`${label} requires a message id.`);
  }

  return value;
}

function requireStorageKey(value) {
  if (typeof value !== 'string') {
    throw new Error('Storage key must be a string.');
  }

  const key = value.trim();

  if (!key || key.length > 100) {
    throw new Error('Storage key must be 1-100 characters.');
  }

  return key;
}

function jsonSafeValue(value) {
  const normalized = JSON.parse(safeJsonStringify(value));

  if (normalized === undefined) {
    return null;
  }

  return normalized;
}

function canonicalFindValue(value) {
  if (value === null || value === undefined) {
    return null;
  }

  if (typeof value === 'string') {
    return value.trim().toLowerCase();
  }

  return safeJsonStringify(jsonSafeValue(value));
}

function random(min = 0, max = 1) {
  const lower = Number(min);
  const upper = Number(max);

  if (!Number.isFinite(lower) || !Number.isFinite(upper) || upper < lower) {
    throw new Error('random(min, max) expects valid numeric bounds.');
  }

  return Math.floor(Math.random() * (upper - lower + 1)) + lower;
}

function promiseWithTimeout(promise, timeoutMs) {
  let timeout;

  const timeoutPromise = new Promise((resolve, reject) => {
    timeout = setTimeout(() => reject(new Error('Execution timed out.')), timeoutMs);
  });

  return Promise.race([promise, timeoutPromise]).finally(() => clearTimeout(timeout));
}

function freezePlainObject(value) {
  return Object.freeze(plainObject(value));
}

function plainObject(value) {
  return JSON.parse(safeJsonStringify(value || {}));
}

function normalizeObject(value, label) {
  if (value === undefined || value === null) {
    return {};
  }

  if (!isPlainObject(value)) {
    throw new Error(`${label} must be an object.`);
  }

  return plainObject(value);
}

function isPlainObject(value) {
  return value !== null
    && typeof value === 'object'
    && !Array.isArray(value);
}

function safeJsonStringify(value) {
  try {
    return JSON.stringify(value);
  } catch (error) {
    return JSON.stringify({ error: 'Unable to serialize value.' });
  }
}

function parseJsonResponse(text) {
  try {
    return JSON.parse(text);
  } catch (error) {
    return null;
  }
}

function isTimeoutError(error) {
  const message = error && typeof error.message === 'string' ? error.message : '';

  return message === 'Execution timed out.'
    || /script execution timed out/i.test(message);
}

function findRestrictedCode(code) {
  const restrictedPatterns = [
    ['require()', /\brequire\s*\(/],
    ['process', /\bprocess\b/],
    ['child_process', /\bchild_process\b/],
    ['fs', /\bfs\b/],
    ['global', /\bglobal\b/],
    ['eval()', /\beval\s*\(/],
    ['Function()', /\bFunction\s*\(/],
    ['import', /\bimport\b/],
    ['__dirname', /\b__dirname\b/],
    ['__filename', /\b__filename\b/],
  ];

  const match = restrictedPatterns.find(([, pattern]) => pattern.test(code));

  return match ? match[0] : null;
}

function publicErrorMessage(error, redactions = []) {
  const message = error && typeof error.message === 'string' ? error.message : '';

  if (!message) {
    return 'Command execution failed.';
  }

  return redactSecrets(message, redactions).slice(0, 500);
}

function safeErrorLog(error, redactions = []) {
  if (!error || typeof error !== 'object') {
    return { error: redactSecrets(String(error || 'Unknown error'), redactions) };
  }

  return {
    error: redactSecrets(String(error.message || 'Unknown error'), redactions).slice(0, 500),
    stack: typeof error.stack === 'string' ? sanitizeStack(error.stack, redactions) : null,
  };
}

function sanitizeStack(stack, redactions = []) {
  return redactSecrets(stack, redactions)
    .split('\n')
    .filter((line) => !line.includes('node_modules/dotenv'))
    .slice(0, 12)
    .join('\n')
    .slice(0, 4000);
}

function sendRuntimeError(res, startedAt, executionId, error, errorType, status = 200, originalError = null, redactions = [], storage = null, replies = []) {
  return res.status(status).json({
    ok: false,
    execution_id: executionId,
    execution_time_ms: Date.now() - startedAt,
    replies: Array.isArray(replies) ? replies : [],
    storage,
    error: redactSecrets(error, redactions),
    error_type: errorType,
    error_stack: originalError && originalError.stack ? sanitizeStack(originalError.stack, redactions) : null,
  });
}

function sensitiveValues(payload) {
  const values = [];
  const token = payload && payload.runtime && typeof payload.runtime.telegram_token === 'string'
    ? payload.runtime.telegram_token
    : '';

  if (token) {
    values.push(token);
  }

  const fpKey = payload && payload.runtime && typeof payload.runtime.faucetpay_api_key === 'string'
    ? payload.runtime.faucetpay_api_key
    : '';

  if (fpKey) {
    values.push(fpKey);
  }

  const telegramBridgeSecret = payload && payload.runtime && typeof payload.runtime.telegram_bridge_secret === 'string'
    ? payload.runtime.telegram_bridge_secret
    : '';

  if (telegramBridgeSecret) {
    values.push(telegramBridgeSecret);
  }

  const bridgeSecret = payload && payload.runtime && typeof payload.runtime.oxapay_bridge_secret === 'string'
    ? payload.runtime.oxapay_bridge_secret
    : '';

  if (bridgeSecret) {
    values.push(bridgeSecret);
  }

  const botStorage = payload && payload.storage && isPlainObject(payload.storage.bot)
    ? payload.storage.bot
    : {};

  for (const [key, value] of Object.entries(botStorage)) {
    if (!/(api[_-]?key|secret|token|password|private)/i.test(key)) {
      continue;
    }

    if (typeof value === 'string' && value.length >= 6) {
      values.push(value);
    }
  }

  return values;
}

function redactSecrets(text, redactions = []) {
  let output = String(text || '');

  for (const secret of redactions) {
    if (typeof secret !== 'string' || secret.length < 6) {
      continue;
    }

    output = output.split(secret).join('[redacted]');
  }

  return output;
}
