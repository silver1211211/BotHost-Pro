'use strict';

const fs = require('node:fs');
const path = require('node:path');

function createAdminHelperLoader(options = {}) {
  const bundlePath = options.bundlePath || path.join(__dirname, 'admin-helpers-generated.js');
  const logger = options.logger || console;
  let cachedBundle = null;
  let cachedMtimeMs = null;
  let warnedMissing = false;

  const loadBundle = () => {
    let stat = null;

    try {
      stat = fs.statSync(bundlePath);
    } catch (error) {
      cachedBundle = null;
      cachedMtimeMs = null;

      if (!warnedMissing && error && error.code !== 'ENOENT') {
        warn(logger, '[BotHost] Failed to inspect admin helper bundle:', error.message || error);
        warnedMissing = true;
      }

      return null;
    }

    warnedMissing = false;

    if (cachedBundle && cachedMtimeMs === stat.mtimeMs) {
      return cachedBundle;
    }

    try {
      const resolved = require.resolve(bundlePath);
      delete require.cache[resolved];
      const loaded = require(resolved);

      if (!loaded || typeof loaded.buildAdminHelpers !== 'function') {
        cachedBundle = null;
        cachedMtimeMs = stat.mtimeMs;
        warn(logger, '[BotHost] Admin helper bundle missing buildAdminHelpers export.');

        return null;
      }

      cachedBundle = loaded;
      cachedMtimeMs = stat.mtimeMs;
      info(logger, '[BotHost] Admin helper bundle loaded.');

      return cachedBundle;
    } catch (error) {
      cachedBundle = null;
      cachedMtimeMs = stat.mtimeMs;
      warn(logger, '[BotHost] Failed to load admin helper bundle:', error && error.message ? error.message : error);

      return null;
    }
  };

  const buildAdminHelpers = (systemHelpers = {}) => {
    const adminHelpers = Object.create(null);
    const bundle = loadBundle();

    if (!bundle) {
      return adminHelpers;
    }

    try {
      const builtAdminHelpers = bundle.buildAdminHelpers(systemHelpers);

      if (!builtAdminHelpers || typeof builtAdminHelpers !== 'object') {
        warn(logger, '[BotHost] Admin helper bundle build did not return an object.');
        return adminHelpers;
      }

      for (const [name, fn] of Object.entries(builtAdminHelpers)) {
        if (Object.prototype.hasOwnProperty.call(systemHelpers, name)) {
          warn(logger, '[BotHost] Admin helper skipped because it collides with system helper:', name);
          continue;
        }

        if (!isSafeAdminHelperName(name)) {
          warn(logger, '[BotHost] Admin helper skipped because it has an unsafe name:', name);
          continue;
        }

        if (typeof fn !== 'function') {
          warn(logger, '[BotHost] Admin helper skipped because it is not a function:', name);
          continue;
        }

        adminHelpers[name] = fn;
      }
    } catch (error) {
      warn(logger, '[BotHost] Admin helper bundle build failed:', error && error.message ? error.message : error);
    }

    return adminHelpers;
  };

  const createCommandSandbox = (systemHelpers = {}) => {
    const sandbox = Object.create(null);
    Object.assign(sandbox, systemHelpers, buildAdminHelpers(systemHelpers));

    return sandbox;
  };

  return {
    buildAdminHelpers,
    createCommandSandbox,
    loadBundle,
  };
}

function isSafeAdminHelperName(name) {
  return /^[A-Za-z_$][A-Za-z0-9_$]{0,99}$/.test(String(name || ''))
    && !['__proto__', 'prototype', 'constructor'].includes(name);
}

function warn(logger, ...args) {
  if (logger && typeof logger.warn === 'function') {
    logger.warn(...args);
    return;
  }

  if (logger && typeof logger.error === 'function') {
    logger.error(...args);
  }
}

function info(logger, ...args) {
  if (logger && typeof logger.info === 'function') {
    logger.info(...args);
    return;
  }

  if (logger && typeof logger.log === 'function') {
    logger.log(...args);
  }
}

const defaultLoader = createAdminHelperLoader();

module.exports = {
  buildAdminHelpers: defaultLoader.buildAdminHelpers,
  createAdminHelperLoader,
  createCommandSandbox: defaultLoader.createCommandSandbox,
  isSafeAdminHelperName,
};
