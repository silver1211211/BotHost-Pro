# Runtime Helpers Private Beta Checklist

Use this checklist before enabling admin-managed runtime helpers for private beta.

- [ ] Runtime helper migrations applied
- [ ] Runtime helper category seeder run
- [ ] Admin middleware verified for all `admin.runtime.*` routes
- [ ] Test helper category created
- [ ] Test helper created
- [ ] Unsafe helper code rejected
- [ ] Protected helper name rejected
- [ ] Helper test passed
- [ ] Payment helper tests verified as dry-run only
- [ ] Helper activated
- [ ] Helper bundle published
- [ ] `node --check runtime-node/server.js` passed
- [ ] `node --check runtime-node/execute-once.js` passed
- [ ] `NODE_BINARY` configured only if production PHP cannot resolve `node`
- [ ] Local runtime sees helper after runtime restart
- [ ] Docker dry-run completed
- [ ] No unknown Docker containers in dry-run report
- [ ] Live Docker refresh performed only after exact confirmation `YES_RECREATE_DOCKER_CONTAINERS`
- [ ] Reload JSON report exported
- [ ] Reload text report exported
- [ ] Audit logs checked for helper/reload/export actions
- [ ] No helper code exposed in reload logs or exports
- [ ] No generated bundle content exposed in reload logs or exports
- [ ] No secrets, bot tokens, API keys, or environment values exposed in logs or exports
- [ ] `storage/logs` writable by the web/PHP user
- [ ] `proc_open` available for async reload launcher
- [ ] `NODE_RUNTIME_SECRET` configured on production runtime
- [ ] `NODE_RUNTIME_SECRET` matches between Laravel and the Node runtime supervisor environment
- [ ] `php artisan config:cache` and `php artisan route:cache` run after deployment
- [ ] `sudo supervisorctl restart bothostpro-runtime:*` run after publishing a helper bundle for local runtime mode
- [ ] `php artisan queue:restart` run after deployment if queue workers are enabled
- [ ] Backup taken before first live Docker refresh
