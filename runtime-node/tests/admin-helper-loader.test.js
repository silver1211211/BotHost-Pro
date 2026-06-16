'use strict';

const assert = require('node:assert/strict');
const fs = require('node:fs');
const os = require('node:os');
const path = require('node:path');
const test = require('node:test');
const vm = require('node:vm');
const { createAdminHelperLoader } = require('../admin-helper-loader');

function makeTempBundle(source) {
  const dir = fs.mkdtempSync(path.join(os.tmpdir(), 'bothost-admin-helper-'));
  const bundlePath = path.join(dir, 'admin-helpers-generated.js');
  fs.writeFileSync(bundlePath, source, 'utf8');

  return { dir, bundlePath };
}

function quietLogger() {
  const messages = [];

  return {
    messages,
    info: (...args) => messages.push(['info', ...args]),
    warn: (...args) => messages.push(['warn', ...args]),
    error: (...args) => messages.push(['error', ...args]),
  };
}

test('generated admin helper is exposed in the command sandbox', async () => {
  const logger = quietLogger();
  const { dir, bundlePath } = makeTempBundle(`
    module.exports = {
      buildAdminHelpers(systemHelpers) {
        return {
          isValidFaucetPayEmail: async (params) => ({ ok: String(params.email || '').includes('@') })
        };
      }
    };
  `);

  try {
    const loader = createAdminHelperLoader({ bundlePath, logger });
    const sandbox = loader.createCommandSandbox({
      sendMessage: async () => ({ ok: true }),
    });

    assert.equal(typeof sandbox.sendMessage, 'function');
    assert.equal(typeof sandbox.isValidFaucetPayEmail, 'function');

    const context = vm.createContext(sandbox);
    const result = await new vm.Script(`
      (async () => await isValidFaucetPayEmail({ email: 'user@gmail.com' }))()
    `).runInContext(context);

    assert.deepEqual(result, { ok: true });
  } finally {
    fs.rmSync(dir, { recursive: true, force: true });
  }
});

test('admin helper cannot override protected system helper names', () => {
  const logger = quietLogger();
  const { dir, bundlePath } = makeTempBundle(`
    module.exports = {
      buildAdminHelpers() {
        return {
          sendMessage: async () => ({ ok: false }),
          customHelper: async () => ({ ok: true })
        };
      }
    };
  `);

  try {
    const systemSendMessage = async () => ({ ok: true, system: true });
    const loader = createAdminHelperLoader({ bundlePath, logger });
    const sandbox = loader.createCommandSandbox({
      sendMessage: systemSendMessage,
    });

    assert.equal(sandbox.sendMessage, systemSendMessage);
    assert.equal(typeof sandbox.customHelper, 'function');
    assert.equal(
      logger.messages.some((entry) => entry.join(' ').includes('collides with system helper')),
      true,
    );
  } finally {
    fs.rmSync(dir, { recursive: true, force: true });
  }
});

test('invalid admin helper bundle logs warning and keeps system helpers usable', () => {
  const logger = quietLogger();
  const { dir, bundlePath } = makeTempBundle('module.exports = { nope: true };');

  try {
    const loader = createAdminHelperLoader({ bundlePath, logger });
    const sandbox = loader.createCommandSandbox({
      getUserData: async () => 'system-value',
    });

    assert.equal(typeof sandbox.getUserData, 'function');
    assert.equal(Object.prototype.hasOwnProperty.call(sandbox, 'nope'), false);
    assert.equal(
      logger.messages.some((entry) => entry.join(' ').includes('missing buildAdminHelpers export')),
      true,
    );
  } finally {
    fs.rmSync(dir, { recursive: true, force: true });
  }
});
