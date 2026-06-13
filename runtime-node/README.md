# BotHost Pro Node Runtime

Development runtime for executing saved BotHost Pro command JavaScript through a controlled helper API.

Start it locally:

```bash
npm install
npm run dev
```

Default URL:

```text
http://127.0.0.1:8787
```

Supported command code:

```js
const name = user.first_name || "there";
await reply(`Hello ${name}!`);

await replyHTML("<b>Welcome!</b>");

const number = random(1, 100);
await reply(`Your number is ${number}`);
```

Available globals:

- `user`
- `chat`
- `message`
- `update`
- `bot`
- `command`
- `args`

Available helpers:

- `reply(text, options)`
- `replyHTML(html, options)`
- `replyMarkdown(text, options)`
- `sendMessage(chatId, text, options)`
- `sendPhoto(chatId, photoUrl, options)`
- `checkChannelMember(channelUsernameOrId, userId = currentUserId)`
- `verifyTelegramChannel(channelUsernameOrId, userId = currentUserId)`
- `isChannelMember(channelUsernameOrId, userId = currentUserId)`
- `delay(ms)`
- `now()`
- `random(min, max)`
- `httpsRequest(url, options)`

Security note: command code receives safe metadata only. Bot tokens, server files, environment variables, and secrets are not exposed to user code.

## Telegram Channel Membership

Use `checkChannelMember(channelUsernameOrId, userId)` to call Telegram `getChatMember` through BotHost's runtime bridge. It returns `{ ok, is_member, status, message }` and never exposes the bot token. `verifyTelegramChannel(...)` is an alias. `isChannelMember(...)` returns only `true` or `false`.

The bot must be added to the channel/group before checking membership. Public channels can use `@username`; private channels usually need a numeric chat ID. For private channels and some groups, grant the bot admin access. Telegram user IDs are numeric and are not the same as usernames.

Local test command:

```js
const channel = await getBotData("test_verify_channel", "@YOUR_TEST_CHANNEL_USERNAME");
const result = await checkChannelMember(channel);

await replyHTML(
  `Channel checked: <code>${safeHTML(channel)}</code>\n` +
  `User Telegram ID: <code>${safeHTML(userId)}</code>\n` +
  `Membership status: <code>${safeHTML(result.status)}</code>\n\n` +
  (result.is_member ? `You are a member of ${safeHTML(channel)}` : `You have not joined ${safeHTML(channel)} yet`)
);
```

## Menu / UI Helpers

Build menus, buttons, and clean UI messages faster.

**Inline buttons:**

```js
await replyHTML("Choose an option:", inlineMenu([
  row(button("Back", "/main_menu"), cancelButton()),
  row(urlButton("Open Channel", "https://t.me/example"))
]));
```

**Confirm/cancel prompt:**

```js
await replyHTML("Are you sure?", {
  reply_markup: inlineKeyboard(confirmButtons("/withdraw_confirm", "/withdraw_cancel"))
});
```

**Bottom keyboard menu:**

```js
await replyHTML("Main menu:", { reply_markup: mainMenuKeyboard() });

// Custom bottom menu:
await replyHTML("Pick one:", bottomMenu([
  ["💳 Balance"],
  ["🔗 Ref Stats", "🎁 Bonus"]
]));
```

**Clean menu text:**

```js
await replyHTML(menuText("Account Overview", [
  statusLine("Balance", "500 PEPE"),
  statusLine("Wallet", "Not set"),
  `Progress: ${progressBar(5, 10)}`
]));
```

**Channel join flow:**

```js
const channels = [
  { name: "Main Channel", url: "https://t.me/channel1" },
  { name: "Updates", url: "https://t.me/channel2" }
];
await replyHTML("Join to continue:", {
  reply_markup: inlineKeyboard(channelJoinButtons(channels, "/verify"))
});
```

**All 20 helpers:**

| Helper | Returns |
|---|---|
| `button(text, callbackData)` | Inline callback button object |
| `urlButton(text, url)` | Inline URL button object |
| `row(...buttons)` | One inline keyboard row (array) |
| `inlineMenu(rows)` | `{ reply_markup: inlineKeyboard(rows) }` |
| `bottomMenu(rows, options)` | `{ reply_markup: keyboard(rows, ...) }` |
| `removeKeyboard()` | `{ remove_keyboard: true }` |
| `backButton(target, text)` | Back callback button |
| `cancelButton(target, text)` | Cancel callback button |
| `confirmButtons(confirmTarget, cancelTarget)` | Two-button confirm row |
| `channelJoinButtons(channels, verifyCommand)` | URL rows + verify button |
| `mainMenuKeyboard()` | Standard faucet bot bottom keyboard |
| `adminBackMenu()` | Back-to-admin inline row |
| `pageButtons(prevCmd, nextCmd)` | Prev/Next inline row |
| `menuText(title, lines)` | HTML `<b>Title</b>\n\n• line...` |
| `section(title, lines)` | HTML section block |
| `divider()` | `━━━━━━━━━━━━` |
| `statusLine(label, value)` | `• <b>Label:</b> value` |
| `alertBox(type, title, message)` | Icon + bold title + message |
| `progressBar(value, total, length)` | `█████░░░░░ 50%` |
| `listItems(items)` | `• A\n• B\n• C` |

## Messaging Helpers

Send notifications, admin alerts, payout messages, and safe small broadcasts.

**Admin notification:**

```js
await notifyAdmin("New withdrawal request.", { adminId: 7701909986 });
```

**Payout channel notice:**

```js
await sendPayoutNotice("@GrapesFinanceEditionV2", {
  user_id: user.id,
  amount: 1000,
  currency: "PEPE",
  wallet: "john@gmail.com",
  bot: bot.username
});
```

**Template rendering:**

```js
const msg = renderTemplate("Hello {{name}}, your balance is {{balance}} PEPE.", {
  name: user.first_name,
  balance: 500
});
await replyHTML(msg);
```

**Safe send (never throws):**

```js
const result = await safeSendMessage(userId, "Hello!");
if (!result.ok) { /* handle gracefully */ }
```

**Small broadcast (max 50 users):**

```js
const result = await broadcastToUsers([id1, id2, id3], "Hello everyone!");
// result: { ok: true, sent: 3, failed: 0, total: 3 }
```

**All 15 helpers:**

| Helper | Purpose |
|---|---|
| `notifyAdmin(text, options)` | Send to admin — uses `adminId` option or `admin_owner_id` bot data |
| `notifyUser(userId, text, options)` | Send to any user |
| `sendPayoutNotice(channel, data)` | Send masked payout message to channel |
| `buildPayoutMessage(data)` | Build payout HTML text without sending |
| `sendAdminLog(text, options)` | Send to admin log channel or admin |
| `sendToChannel(channel, text, options)` | Send to any channel/group |
| `safeSendMessage(chatId, text, options)` | Like `sendMessage` but returns `{ ok, error }` |
| `safeReply(text, options)` | Like `reply` but returns `{ ok, error }` |
| `broadcastToUsers(userIds, text, options)` | Send to up to 50 users, returns `{ sent, failed, total }` |
| `queueBroadcast(segment, text, options)` | Stub — use Broadcasts panel for full broadcast |
| `getBroadcastStatus(broadcastId)` | Stub — use Broadcasts panel |
| `previewBroadcast(text, options)` | Return preview object without sending |
| `saveNotificationTemplate(name, text)` | Save template to bot data |
| `getNotificationTemplate(name, fallback)` | Retrieve saved template |
| `renderTemplate(template, data)` | Replace `{{key}}` placeholders safely |

**Test commands:**

`/notifytest`:
```js
await notifyAdmin("Admin notification test.", { adminId: 7701909986 });
await replyHTML("Notification sent to admin.");
```

`/payouttest`:
```js
await sendPayoutNotice("@GrapesFinanceEditionV2", {
  user_id: user.id, amount: 1000, currency: "PEPE",
  wallet: "johnsmith@gmail.com", bot: bot.username
});
await replyHTML("Payout notice sent.");
```

`/templatetest`:
```js
const msg = renderTemplate("Hello {{name}}, your balance is {{balance}} PEPE.", {
  name: user.first_name, balance: 500
});
await replyHTML(msg);
```

**Test command `/uitest`:**

```js
await replyHTML(
  menuText("UI Helper Test", [
    statusLine("Balance", "500 PEPE"),
    statusLine("Status", "Active"),
    `Progress: ${progressBar(5, 10)}`
  ]),
  {
    reply_markup: inlineKeyboard([
      row(button("Back", "/main_menu"), cancelButton()),
      row(urlButton("Open Channel", "https://t.me/GrapesFinanceEditionV2"))
    ])
  }
);
```

**Test command `/menutest`:**

```js
await replyHTML("Bottom menu loaded.", { reply_markup: mainMenuKeyboard() });
```
