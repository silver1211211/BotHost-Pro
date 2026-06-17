# BotHost Pro Runtime Helpers Production Guide

Admin-managed runtime helpers let trusted administrators add reusable JavaScript helper functions to the bot runtime without editing `runtime-node/server.js`. Helpers are stored in the Laravel database, safety checked, bundled into `runtime-node/admin-helpers-generated.js`, and loaded by both local and Docker runtime entrypoints.

## Admin Workflow

1. Create a helper category from Admin > Runtime Helper Categories.
2. Create a helper from Admin > Runtime Helpers.
3. Use a unique JavaScript identifier for the helper name.
4. Avoid protected runtime helper names such as `sendMessage`, `getUserData`, `setBotData`, `delay`, and payment bridge helpers.
5. Write helper code as the body of an async helper function.
6. Run the helper test before activation.
7. Payment helpers are dry-run only during private beta. Real payment tests are blocked.
8. Activate the helper only after safety, syntax, and test checks pass.
9. Publish the helper bundle from Admin > Runtime Helper Bundle.
10. Reload/recreate runtime only after reviewing the Docker dry-run plan.

Helper activation does not publish the live bundle automatically. It marks the helper as requiring runtime reload so an operator can publish and refresh deliberately.

## Safety Rules

The safety scanner blocks dangerous runtime access patterns including `process`, `require`, `eval`, `Function`, `global`, `globalThis`, `fs`, `child_process`, internal runtime bridge names, bridge secrets, `constructor`, `prototype`, and `__proto__` tricks.

Normal helper calls such as `sendMessage`, `getUserData`, `setBotData`, `delay`, and `formatNumber` are allowed inside helper code, but those names cannot be used as custom helper names because they are protected system helpers.

Generated bundles are syntax checked before activation. If syntax checking fails, the live bundle is not replaced.

## Bundle Publishing

Publishing writes `runtime-node/admin-helpers-generated.js` only after generation and syntax checks pass. Missing or broken helper bundles should not crash the Node runtime. Admin helpers cannot override system helpers; collisions, unsafe names, and non-function exports are skipped by the runtime loader.

Publishing records a SHA-256 hash of the generated helper bundle. Docker runtime containers created by BotHost Pro store that hash in container metadata. Publish & Apply Helpers compares the current helper bundle hash against running containers and recreates affected containers when the helper bundle changed, even if the runtime source hash did not change.

## Docker Refresh

Existing Docker containers must be recreated once before they can receive the admin helper bundle bind mount. The Docker dry-run inspects containers and reports:

- ready
- would recreate
- not running
- not found
- unknown
- skipped

The report includes `helper_bundle_changed`, the expected helper bundle hash, recreated/skipped counts, and a reason for each container.

Dry-run is the default. Live Docker refresh requires the exact confirmation text:

```text
YES_RECREATE_DOCKER_CONTAINERS
```

Do not run live Docker refresh unless the dry-run output looks correct and there are no unknown containers.

Live refresh only recreates containers in the `would_recreate` category. It does not touch ready, not running, not found, unknown, or non-Docker bots.

## Reload Logs And Exports

Reload logs show task status, progress, helper compile results, and per-bot Docker refresh results. Logs can be exported as JSON or text reports. Exports are read-only and should not contain helper code, generated bundle content, secrets, raw Docker inspect output, environment values, bot tokens, or API keys.

Async reload tasks may become stale. Pending tasks are stale after 5 minutes; running tasks are stale after 30 minutes. Stale tasks are marked failed automatically during polling or before another task starts.

Cancel / Mark Failed only marks the database log as cancelled. It does not kill an operating system process.

## VPS Checklist Before Live Use

- `APP_ENV=production`
- `APP_DEBUG=false`
- `NODE_RUNTIME_SECRET` is set
- `NODE_RUNTIME_SECRET` matches between Laravel and the Node runtime supervisor environment
- `NODE_BINARY` is set only if PHP cannot resolve `node`
- `php artisan migrate --force` has run
- `php artisan db:seed --class=RuntimeHelperCategorySeeder --force` has run
- `php artisan config:cache` has run
- `php artisan route:cache` has run if supported
- Frontend assets are built with `npm ci` and `npm run build`
- Supervisor or process manager is configured for the normal runtime process
- Restart `bothostpro-runtime:*` after publishing a helper bundle for local runtime mode
- Restart queue workers after deployment if queues are enabled
- `storage/logs` is writable
- `proc_open` is available
- Docker is installed
- Web user is allowed to run Docker only if intended
- `runtime-node/admin-helpers-generated.js` is writable by the deployment user
- `runtime-node` directory is readable
- Take a backup before first live refresh
- Run Docker dry-run first
- Confirm no unknown containers before live refresh

## Operator Warning

Live Docker refresh can cause short bot downtime while containers are recreated. Always publish the bundle first, run Docker dry-run, inspect the log, export the report if needed, and only then run live refresh with explicit confirmation.

## Telegram Helper Signatures

Use the live runtime signatures below in helper examples and bot command code:

| Helper | Signature |
| --- | --- |
| `sendMessage` | `await sendMessage(chatId, text, options)` or `await sendMessage(text, options)` for the current chat |
| `sendPhoto` | `await sendPhoto(chatId, photoUrlOrFileId, options)` |
| `editMessageText` | `await editMessageText(chatId, messageId, text, options)` or `await editMessageText(text, options)` for the current callback message |
| `answerCallbackQuery` | `await answerCallbackQuery(text, options)` |
| `notifyUser` | `await notifyUser(userId, text, options)` |

For photos, pass the caption in `options`:

```js
await sendPhoto(chat.id, photoUrl, { caption: 'Photo' });
```

Admin helper dry-runs use isolated stubs and do not send real Telegram messages. `sendMessage`/`sendPhoto` dry-run success only verifies helper code shape; publish and test inside a live bot command to verify real Telegram delivery.
