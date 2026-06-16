<?php

use App\Http\Controllers\Admin\BotController as AdminBotController;
use App\Http\Controllers\Admin\BroadcastController as AdminBroadcastController;
use App\Http\Controllers\Admin\DashboardController as AdminDashboardController;
use App\Http\Controllers\Admin\LogController as AdminLogController;
use App\Http\Controllers\Admin\RuntimeHelperCategoryController as AdminRuntimeHelperCategoryController;
use App\Http\Controllers\Admin\RuntimeHelperController as AdminRuntimeHelperController;
use App\Http\Controllers\Admin\RuntimeHelperTestController as AdminRuntimeHelperTestController;
use App\Http\Controllers\Admin\RuntimeHelperVersionController as AdminRuntimeHelperVersionController;
use App\Http\Controllers\Admin\RuntimeReloadController as AdminRuntimeReloadController;
use App\Http\Controllers\Admin\SecurityController as AdminSecurityController;
use App\Http\Controllers\Admin\TemplateCommandController as AdminTemplateCommandController;
use App\Http\Controllers\Admin\TemplateController as AdminTemplateController;
use App\Http\Controllers\Admin\UserController as AdminUserController;
use App\Http\Controllers\Admin\PaymentSettingsController as AdminPaymentSettingsController;
use App\Http\Controllers\Admin\PlanController as AdminPlanController;
use App\Http\Controllers\Admin\SettingsController as AdminSettingsController;
use App\Http\Controllers\Admin\Auth\AuthenticatedSessionController as AdminAuthenticatedSessionController;
use App\Http\Controllers\BotCommandController;
use App\Http\Controllers\BotController;
use App\Http\Controllers\BotBroadcastController;
use App\Http\Controllers\BotExportImportController;
use App\Http\Controllers\BotLogController;
use App\Http\Controllers\BotRuntimeController;
use App\Http\Controllers\BotSettingsController;
use App\Http\Controllers\BotTemplateImportController;
use App\Http\Controllers\BotUserController;
use App\Http\Controllers\BotUserWebhookController;
use App\Http\Controllers\BotWebhookController;
use App\Http\Controllers\TransferController;
use App\Http\Controllers\UserBotWebhookController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DocsController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\RecycleBinController;
use App\Http\Controllers\Project\FileController;
use App\Http\Controllers\Project\SettingController;
use App\Http\Controllers\Project\VariableController;
use App\Http\Controllers\ProjectController;
use App\Http\Controllers\TelegramWebhookController;
use App\Http\Controllers\TemplateMarketplaceController;
use App\Http\Controllers\TemplatePaymentController;
use App\Http\Controllers\OxaPayWebhookController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\RuntimeOxaPayController;
use App\Http\Controllers\RuntimeStorageController;
use App\Http\Controllers\RuntimeTelegramController;
use App\Http\Controllers\UpgradeController;
use App\Models\Bot;
use App\Models\PlatformSetting;
use Illuminate\Support\Facades\Route;

Route::bind('bot', fn (string $value) => Bot::withTrashed()->findOrFail($value));

Route::get('/', function () {
    return view('welcome', ['branding' => \App\Support\Branding::assets()]);
})->name('home');
Route::view('/privacy-policy', 'legal.privacy-policy')->name('legal.privacy');
Route::view('/terms', 'legal.terms')->name('legal.terms');
Route::view('/cookie-policy', 'legal.cookie-policy')->name('legal.cookies');
Route::view('/refund-policy', 'legal.refund-policy')->name('legal.refunds');
Route::view('/acceptable-use', 'legal.acceptable-use')->name('legal.acceptable-use');

Route::post('/telegram/webhook/{webhookBot}/{secret}', [TelegramWebhookController::class, 'handle'])->name('telegram.webhook');
Route::post('/webhooks/bot/{botId}/{secret}', [UserBotWebhookController::class, 'handle'])->name('webhooks.user-bot');
Route::post('/webhooks/custom/{botId}/{secret}', [UserBotWebhookController::class, 'handle'])->name('webhooks.custom');
Route::post('/webhooks/oxapay', [OxaPayWebhookController::class, 'handle'])->name('webhooks.oxapay');
Route::post('/webhooks/oxapay/template-payments', [OxaPayWebhookController::class, 'handle'])->name('webhooks.oxapay.template-payments');
Route::post('/webhooks/oxapay/subscription-payments', [OxaPayWebhookController::class, 'handle'])->name('webhooks.oxapay.subscription-payments');
Route::post('/runtime/oxapay', RuntimeOxaPayController::class)->name('runtime.oxapay');
Route::post('/runtime/telegram', RuntimeTelegramController::class)->name('runtime.telegram');
Route::post('/runtime/storage', RuntimeStorageController::class)->name('runtime.storage');

Route::prefix('admin')->name('admin.')->group(function () {
    Route::get('/login', [AdminAuthenticatedSessionController::class, 'create'])->name('login');
    Route::post('/login', [AdminAuthenticatedSessionController::class, 'store'])->name('login.store');
    Route::post('/logout', [AdminAuthenticatedSessionController::class, 'destroy'])
        ->middleware(['auth', 'admin'])
        ->name('logout');
});

// Locked screen — auth required, but NOT the 'active' middleware (to prevent redirect loop)
Route::middleware('auth')->get('/account/locked', function () {
    $user = auth()->user();

    // Auto-lift if timed suspension has expired
    if ($user->isSuspended() && $user->suspensionExpired()) {
        $user->update([
            'status'               => 'active',
            'suspended_until'      => null,
            'suspension_message'   => null,
            'suspension_cta_label' => null,
            'suspension_cta_url'   => null,
        ]);
        return redirect()->route('dashboard');
    }

    // If admin has already reactivated the account, redirect to dashboard
    if (! $user->isSuspended() && ! $user->isBanned()) {
        return redirect()->route('dashboard');
    }

    return view('account.locked');
})->name('account.locked');

Route::middleware(['auth', 'active', 'verified.required'])->group(function () {
    Route::get('/dashboard', DashboardController::class)->name('dashboard');
    Route::get('/broadcasts', [BotBroadcastController::class, 'globalIndex'])->name('broadcasts.index');
    Route::get('/dashboard/templates', [TemplateMarketplaceController::class, 'index'])->name('dashboard.templates.index');
    Route::get('/dashboard/templates/purchased', [TemplateMarketplaceController::class, 'purchased'])->name('dashboard.templates.purchased');
    Route::get('/dashboard/templates/status', [TemplateMarketplaceController::class, 'statusCheck'])->name('dashboard.templates.status');
    Route::get('/dashboard/templates/{template}', [TemplateMarketplaceController::class, 'show'])->name('dashboard.templates.show');
    Route::post('/dashboard/templates/{template}/unlock-free', [TemplateMarketplaceController::class, 'unlockFree'])->name('dashboard.templates.unlock-free');
    Route::post('/dashboard/templates/{template}/purchase', [TemplateMarketplaceController::class, 'purchase'])->name('dashboard.templates.purchase');
    Route::post('/dashboard/templates/{template}/purchase/crypto-invoice', [TemplatePaymentController::class, 'createCryptoInvoice'])->name('dashboard.templates.crypto-invoice');
    Route::get('/dashboard/payments/{invoice}', [PaymentController::class, 'show'])->name('dashboard.payments.show');
    Route::post('/dashboard/payments/{invoice}/generate', [PaymentController::class, 'generate'])->name('dashboard.payments.generate');
    Route::post('/dashboard/payments/{invoice}/check', [PaymentController::class, 'check'])->name('dashboard.payments.check');
    Route::post('/dashboard/payments/{invoice}/cancel', [PaymentController::class, 'cancel'])->name('dashboard.payments.cancel');
    Route::post('/dashboard/payments/{invoice}/keep-active', [PaymentController::class, 'keepActive'])->name('dashboard.payments.keep-active');
    Route::get('/dashboard/payments/{invoice}/poll', [PaymentController::class, 'poll'])->name('dashboard.payments.poll');
    Route::get('/dashboard/template-invoices/{invoice}', [TemplatePaymentController::class, 'show'])->name('dashboard.template-invoices.show');
    Route::post('/dashboard/template-invoices/{invoice}/check', [TemplatePaymentController::class, 'check'])->name('dashboard.template-invoices.check');
    Route::get('/dashboard/upgrade', [UpgradeController::class, 'index'])->name('dashboard.upgrade');
    Route::post('/dashboard/upgrade/{plan}/crypto-invoice', [UpgradeController::class, 'createCryptoInvoice'])->name('dashboard.upgrade.crypto-invoice');

    Route::get('/dashboard/docs/webhooks', [DocsController::class, 'webhooks'])->name('docs.webhooks');

    Route::get('/dashboard/bots', [BotController::class, 'index'])->name('bots.index');
    Route::get('/dashboard/bots/create', [BotController::class, 'create'])->name('bots.create');
    Route::post('/dashboard/bots', [BotController::class, 'store'])->name('bots.store');
    Route::get('/dashboard/bots/{bot}', [BotController::class, 'show'])->name('bots.show');
    Route::delete('/dashboard/bots/{bot}', [BotController::class, 'destroy'])->name('bots.destroy');
    Route::get('/dashboard/bots/{bot}/settings', [BotController::class, 'settings'])->name('bots.settings.show');
    Route::patch('/dashboard/bots/{bot}/settings', [BotSettingsController::class, 'update'])->name('bots.settings.update');
    Route::post('/dashboard/bots/{bot}/webhook/set', [BotWebhookController::class, 'set'])->name('bots.webhook.set');
    Route::post('/dashboard/bots/{bot}/webhook/delete', [BotWebhookController::class, 'delete'])->name('bots.webhook.delete');
    Route::post('/dashboard/bots/{bot}/user-webhook/regenerate', [BotUserWebhookController::class, 'regenerate'])->name('bots.user-webhook.regenerate');
    Route::post('/dashboard/bots/{bot}/custom-webhook/regenerate', [BotUserWebhookController::class, 'regenerate'])->name('bots.custom-webhook.regenerate');
    Route::post('/dashboard/bots/{bot}/user-webhook/disable', [BotUserWebhookController::class, 'disable'])->name('bots.user-webhook.disable');
    Route::post('/dashboard/bots/{bot}/custom-webhook/disable', [BotUserWebhookController::class, 'disable'])->name('bots.custom-webhook.disable');
    Route::post('/dashboard/bots/{bot}/user-webhook/save', [BotUserWebhookController::class, 'saveSettings'])->name('bots.user-webhook.save');
    Route::post('/dashboard/bots/{bot}/user-webhook/test', [BotUserWebhookController::class, 'test'])->name('bots.user-webhook.test');
    Route::post('/dashboard/bots/{bot}/custom-webhook/test', [BotUserWebhookController::class, 'test'])->name('bots.custom-webhook.test');
    Route::post('/dashboard/bots/{bot}/activate', [BotRuntimeController::class, 'activate'])->name('bots.activate');
    Route::post('/dashboard/bots/{bot}/stop', [BotRuntimeController::class, 'stop'])->name('bots.stop');
    Route::post('/dashboard/bots/{bot}/verify-token', [BotRuntimeController::class, 'verifyToken'])->name('bots.verify-token');
    Route::delete('/dashboard/bots/{bot}/logs/errors', [BotLogController::class, 'clearErrors'])->name('bots.logs.errors.clear');
    Route::get('/dashboard/bots/{bot}/users', [BotUserController::class, 'index'])->name('bots.users.index');
    Route::patch('/dashboard/bots/{bot}/users/{botUser}/status', [BotUserController::class, 'updateStatus'])->name('bots.users.status');
    Route::patch('/dashboard/bots/{bot}/users/{botUser}/block', [BotUserController::class, 'block'])->name('bots.users.block');
    Route::patch('/dashboard/bots/{bot}/users/{botUser}/unblock', [BotUserController::class, 'unblock'])->name('bots.users.unblock');
    Route::patch('/dashboard/bots/{bot}/users/{botUser}/pause', [BotUserController::class, 'pause'])->name('bots.users.pause');
    Route::patch('/dashboard/bots/{bot}/users/{botUser}/resume', [BotUserController::class, 'resume'])->name('bots.users.resume');
    Route::patch('/dashboard/bots/{bot}/users/{botUser}/delete-status', [BotUserController::class, 'markDeleted'])->name('bots.users.delete-status');
    Route::get('/dashboard/bots/{bot}/broadcasts', [BotBroadcastController::class, 'index'])->name('bots.broadcasts.index');
    Route::post('/dashboard/bots/{bot}/broadcasts', [BotBroadcastController::class, 'store'])->name('bots.broadcasts.store');
    Route::get('/dashboard/bots/{bot}/broadcasts/target-count', [BotBroadcastController::class, 'targetCount'])->name('bots.broadcasts.target-count');
    Route::post('/dashboard/bots/{bot}/broadcasts/{broadcast}/start', [BotBroadcastController::class, 'start'])->name('bots.broadcasts.start');
    Route::post('/dashboard/bots/{bot}/broadcasts/{broadcast}/cancel', [BotBroadcastController::class, 'cancel'])->name('bots.broadcasts.cancel');
    Route::post('/dashboard/bots/{bot}/broadcasts/{broadcast}/retry-failed', [BotBroadcastController::class, 'retryFailed'])->name('bots.broadcasts.retry-failed');
    Route::post('/dashboard/bots/{bot}/broadcasts/{broadcast}/process-next-batch', [BotBroadcastController::class, 'processNextBatch'])->name('bots.broadcasts.process-next-batch');
    Route::post('/dashboard/bots/{bot}/broadcasts/{broadcast}/test-send', [BotBroadcastController::class, 'testSend'])->name('bots.broadcasts.test-send');
    Route::get('/dashboard/bots/{bot}/broadcasts/{broadcast}/status', [BotBroadcastController::class, 'status'])->name('bots.broadcasts.status');
    Route::get('/dashboard/bots/{bot}/broadcasts/{broadcast}', [BotBroadcastController::class, 'show'])->name('bots.broadcasts.show');
    Route::delete('/dashboard/bots/{bot}/broadcasts/{broadcast}', [BotBroadcastController::class, 'destroy'])->name('bots.broadcasts.destroy');
    Route::get('/dashboard/bots/{bot}/commands', [BotCommandController::class, 'index'])->name('bots.commands.index');
    Route::get('/dashboard/bots/{bot}/commands/create', [BotCommandController::class, 'create'])->name('bots.commands.create');
    Route::post('/dashboard/bots/{bot}/commands', [BotCommandController::class, 'store'])->name('bots.commands.store');
    Route::get('/dashboard/bots/{bot}/commands/{command}/edit', [BotCommandController::class, 'edit'])->name('bots.commands.edit');
    Route::get('/dashboard/bots/{bot}/commands/{command}/code', [BotCommandController::class, 'code'])->name('bots.commands.code');
    Route::put('/dashboard/bots/{bot}/commands/{command}/code', [BotCommandController::class, 'updateCode'])->name('bots.commands.code.update');
    Route::put('/dashboard/bots/{bot}/commands/{command}', [BotCommandController::class, 'update'])->name('bots.commands.update');
    Route::patch('/dashboard/bots/{bot}/commands/{command}', [BotCommandController::class, 'update']);
    Route::delete('/dashboard/bots/{bot}/commands/{command}', [BotCommandController::class, 'destroy'])->name('bots.commands.destroy');
    Route::get('/dashboard/bots/{bot}/templates', [BotTemplateImportController::class, 'index'])->name('bots.templates.index');
    Route::get('/dashboard/bots/{bot}/templates/{template}', [BotTemplateImportController::class, 'show'])->name('bots.templates.show');
    Route::post('/dashboard/bots/{bot}/templates/{template}/import', [BotTemplateImportController::class, 'import'])->name('bots.templates.import');
    Route::get('/dashboard/bots/{bot}/export', [BotExportImportController::class, 'export'])->name('bots.export');
    Route::post('/dashboard/bots/{bot}/import', [BotExportImportController::class, 'importIntoBot'])->name('bots.import.current');
    Route::post('/dashboard/bots/{bot}/clone', [BotExportImportController::class, 'clone'])->name('bots.clone');
    Route::post('/dashboard/bots/{bot}/transfer', [BotExportImportController::class, 'transfer'])->name('bots.transfer');
    Route::post('/dashboard/bots/import', [BotExportImportController::class, 'import'])->name('bots.import');

    Route::resource('projects', ProjectController::class);
    Route::get('/projects/{project}/files/{file}', [FileController::class, 'show'])->name('projects.files.show');
    Route::post('/projects/{project}/files', [FileController::class, 'store'])->name('projects.files.store');
    Route::patch('/projects/{project}/files/{file}', [FileController::class, 'update'])->name('projects.files.update');
    Route::patch('/projects/{project}/files/{file}/rename', [FileController::class, 'rename'])->name('projects.files.rename');
    Route::delete('/projects/{project}/files/{file}', [FileController::class, 'destroy'])->name('projects.files.destroy');
    Route::post('/projects/{project}/variables', [VariableController::class, 'store'])->name('projects.variables.store');
    Route::patch('/projects/{project}/variables/{variable}', [VariableController::class, 'update'])->name('projects.variables.update');
    Route::delete('/projects/{project}/variables/{variable}', [VariableController::class, 'destroy'])->name('projects.variables.destroy');
    Route::patch('/projects/{project}/settings', [SettingController::class, 'update'])->name('projects.settings.update');

    Route::view('/templates', 'pages.placeholder', [
        'title' => 'Templates',
        'description' => 'Template browsing and cloning will be added in a later phase.',
    ])->name('templates.index');
    Route::get('/recycle-bin', [RecycleBinController::class, 'index'])->name('recycle-bin.index');
    Route::post('/recycle-bin/bots/{bot}/restore', [RecycleBinController::class, 'restore'])->name('recycle-bin.bots.restore');
    Route::delete('/recycle-bin/bots/{bot}/force-delete', [RecycleBinController::class, 'forceDelete'])->name('recycle-bin.bots.force-delete');
    Route::get('/transfers', [TransferController::class, 'index'])->name('transfers.index');
    Route::post('/transfers/{transfer}/import', [TransferController::class, 'importTransfer'])->name('transfers.import');
    Route::post('/transfers/{transfer}/cancel', [TransferController::class, 'cancelTransfer'])->name('transfers.cancel');
    Route::get('/notifications', [NotificationController::class, 'index'])->name('notifications.index');
    Route::get('/help', fn () => view('help.index', [
        'supportUrl' => PlatformSetting::getValue('support_url'),
    ]))->name('help.index');
    Route::get('/support', fn () => view('support.index', [
        'supportUrl' => PlatformSetting::getValue('support_url'),
    ]))->name('support.index');
    Route::view('/ai-help', 'ai-help.index')->name('ai-help.index');
    Route::view('/billing', 'pages.placeholder', [
        'title' => 'Billing',
        'description' => 'Subscription and deposit management will be connected in a later phase.',
    ])->name('billing.index');
    Route::view('/logs', 'pages.placeholder', [
        'title' => 'Logs',
        'description' => 'Bot runtime logs will appear after the runtime manager phase.',
    ])->name('logs.index');

    Route::redirect('/settings', '/profile')->name('settings.index');
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

Route::middleware(['auth', 'active', 'admin'])
    ->prefix('admin')
    ->name('admin.')
    ->group(function () {
        Route::get('/dashboard', AdminDashboardController::class)->name('dashboard');

        // Users
        Route::get('/users', [AdminUserController::class, 'index'])->name('users.index');
        Route::patch('/users/{user}/status', [AdminUserController::class, 'updateStatus'])->name('users.status');
        Route::post('/users/{user}/suspend', [AdminUserController::class, 'suspend'])->name('users.suspend');
        Route::post('/users/{user}/activate', [AdminUserController::class, 'activate'])->name('users.activate');
        Route::patch('/users/{user}/role', [AdminUserController::class, 'updateRole'])->name('users.role');
        Route::patch('/users/{user}/plan', [AdminUserController::class, 'updatePlan'])->name('users.plan');

        // Bots
        Route::get('/bots', [AdminBotController::class, 'index'])->name('bots.index');
        Route::post('/bots/set-all-webhooks', [AdminBotController::class, 'setAllWebhooks'])->name('bots.set-all-webhooks');
        Route::patch('/bots/{bot}/status', [AdminBotController::class, 'updateStatus'])->name('bots.status');

        // Templates
        Route::get('/templates', [AdminTemplateController::class, 'index'])->name('templates.index');
        Route::get('/templates/create', [AdminTemplateController::class, 'create'])->name('templates.create');
        Route::post('/templates', [AdminTemplateController::class, 'store'])->name('templates.store');
        Route::get('/templates/{template}/edit', [AdminTemplateController::class, 'edit'])->name('templates.edit');
        Route::patch('/templates/{template}', [AdminTemplateController::class, 'update'])->name('templates.update');
        Route::delete('/templates/{template}', [AdminTemplateController::class, 'destroy'])->name('templates.destroy');
        Route::post('/templates/{template}/publish', [AdminTemplateController::class, 'publish'])->name('templates.publish');
        Route::post('/templates/{template}/archive', [AdminTemplateController::class, 'archive'])->name('templates.archive');
        Route::post('/templates/{template}/commands', [AdminTemplateCommandController::class, 'store'])->name('templates.commands.store');
        Route::patch('/templates/{template}/commands/{command}', [AdminTemplateCommandController::class, 'update'])->name('templates.commands.update');
        Route::delete('/templates/{template}/commands/{command}', [AdminTemplateCommandController::class, 'destroy'])->name('templates.commands.destroy');

        // Placeholder pages
        Route::get('/deposits', [AdminPaymentSettingsController::class, 'edit'])->name('deposits.index');
        Route::get('/deposits/settings', [AdminPaymentSettingsController::class, 'edit'])->name('deposits.settings');
        Route::post('/deposits/settings', [AdminPaymentSettingsController::class, 'update']);
        Route::patch('/deposits/settings', [AdminPaymentSettingsController::class, 'update'])->name('deposits.settings.update');
        Route::get('/plans', [AdminPlanController::class, 'index'])->name('plans.index');
        Route::patch('/plans/{plan}', [AdminPlanController::class, 'update'])->name('plans.update');
        Route::patch('/plans-features', [AdminPlanController::class, 'updateFeatures'])->name('plans.features.update');
        Route::patch('/plans-limits', [AdminPlanController::class, 'updateLimits'])->name('plans.limits.update');
        Route::patch('/plans-template-access', [AdminPlanController::class, 'updateTemplateAccess'])->name('plans.template-access.update');
        Route::patch('/plans-broadcast-limits', [AdminPlanController::class, 'updateBroadcastLimits'])->name('plans.broadcast-limits.update');
        Route::get('/broadcasts', [AdminBroadcastController::class, 'index'])->name('broadcasts.index');
        Route::post('/broadcasts', [AdminBroadcastController::class, 'store'])->name('broadcasts.store');
        Route::get('/logs', [AdminLogController::class, 'index'])->name('logs.index');
        Route::get('/logs/{auditLog}', [AdminLogController::class, 'show'])->name('logs.show');
        Route::get('/security', [AdminSecurityController::class, 'index'])->name('security.index');

        Route::get('/runtime/helper-categories', [AdminRuntimeHelperCategoryController::class, 'index'])->name('runtime.helper-categories.index');
        Route::get('/runtime/helper-categories/create', [AdminRuntimeHelperCategoryController::class, 'create'])->name('runtime.helper-categories.create');
        Route::post('/runtime/helper-categories', [AdminRuntimeHelperCategoryController::class, 'store'])->name('runtime.helper-categories.store');
        Route::get('/runtime/helper-categories/{category}/edit', [AdminRuntimeHelperCategoryController::class, 'edit'])->name('runtime.helper-categories.edit');
        Route::patch('/runtime/helper-categories/{category}', [AdminRuntimeHelperCategoryController::class, 'update'])->name('runtime.helper-categories.update');
        Route::patch('/runtime/helper-categories/{category}/toggle', [AdminRuntimeHelperCategoryController::class, 'toggle'])->name('runtime.helper-categories.toggle');
        Route::delete('/runtime/helper-categories/{category}', [AdminRuntimeHelperCategoryController::class, 'destroy'])->name('runtime.helper-categories.destroy');

        Route::get('/runtime/helpers', [AdminRuntimeHelperController::class, 'index'])->name('runtime.helpers.index');
        Route::get('/runtime/helpers/create', [AdminRuntimeHelperController::class, 'create'])->name('runtime.helpers.create');
        Route::post('/runtime/helpers', [AdminRuntimeHelperController::class, 'store'])->name('runtime.helpers.store');
        Route::post('/runtime/helpers/test', [AdminRuntimeHelperTestController::class, 'run'])->name('runtime.helpers.test');
        Route::get('/runtime/helpers/{helper}/edit', [AdminRuntimeHelperController::class, 'edit'])->name('runtime.helpers.edit');
        Route::patch('/runtime/helpers/{helper}', [AdminRuntimeHelperController::class, 'update'])->name('runtime.helpers.update');
        Route::delete('/runtime/helpers/{helper}', [AdminRuntimeHelperController::class, 'destroy'])->name('runtime.helpers.destroy');
        Route::post('/runtime/helpers/{helper}/activate', [AdminRuntimeHelperController::class, 'activate'])->name('runtime.helpers.activate');
        Route::post('/runtime/helpers/{helper}/deactivate', [AdminRuntimeHelperController::class, 'deactivate'])->name('runtime.helpers.deactivate');
        Route::get('/runtime/helpers/{helper}/versions', [AdminRuntimeHelperVersionController::class, 'index'])->name('runtime.helpers.versions.index');
        Route::post('/runtime/helpers/{helper}/versions/{version}/restore', [AdminRuntimeHelperVersionController::class, 'restore'])->name('runtime.helpers.versions.restore');

        Route::get('/runtime/reload', [AdminRuntimeReloadController::class, 'index'])->name('runtime.reload.index');
        Route::post('/runtime/reload/publish-bundle', [AdminRuntimeReloadController::class, 'publishBundle'])->name('runtime.reload.publish-bundle');
        Route::post('/runtime/reload/publish-and-apply', [AdminRuntimeReloadController::class, 'publishAndApply'])->name('runtime.reload.publish-and-apply');
        Route::post('/runtime/reload/docker-refresh-plan', [AdminRuntimeReloadController::class, 'dockerRefreshPlan'])->name('runtime.reload.docker-refresh-plan');
        Route::post('/runtime/reload/docker-refresh-live', [AdminRuntimeReloadController::class, 'dockerRefreshLive'])->name('runtime.reload.docker-refresh-live');
        Route::get('/runtime/reload/status/{log}', [AdminRuntimeReloadController::class, 'status'])->name('runtime.reload.status');
        Route::get('/runtime/reload/logs', [AdminRuntimeReloadController::class, 'logs'])->name('runtime.reload.logs');
        Route::get('/runtime/reload/logs/{log}/export-json', [AdminRuntimeReloadController::class, 'exportJson'])->name('runtime.reload.logs.export-json');
        Route::get('/runtime/reload/logs/{log}/export-text', [AdminRuntimeReloadController::class, 'exportText'])->name('runtime.reload.logs.export-text');
        Route::post('/runtime/reload/logs/{log}/cancel', [AdminRuntimeReloadController::class, 'cancel'])->name('runtime.reload.logs.cancel');
        Route::post('/runtime/reload/logs/{log}/retry', [AdminRuntimeReloadController::class, 'retry'])->name('runtime.reload.logs.retry');
        Route::get('/runtime/reload/logs/{log}', [AdminRuntimeReloadController::class, 'show'])->name('runtime.reload.show');

        // Settings — full platform settings center
        Route::get('/settings', [AdminSettingsController::class, 'index'])->name('settings.index');
        Route::post('/settings/general', [AdminSettingsController::class, 'saveGeneral'])->name('settings.general.save');
        Route::post('/settings/branding', [AdminSettingsController::class, 'saveBranding'])->name('settings.branding.save');
        Route::post('/settings/payments', [AdminSettingsController::class, 'savePayments'])->name('settings.payments.save');
        Route::post('/settings/trigger-webhooks', [AdminSettingsController::class, 'saveTriggerWebhooks'])->name('settings.triggers.save');
        Route::post('/settings/storage', [AdminSettingsController::class, 'saveStorage'])->name('settings.storage.save');
        Route::post('/settings/security', [AdminSettingsController::class, 'saveSecurity'])->name('settings.security.save');
        Route::post('/settings/notifications', [AdminSettingsController::class, 'saveNotifications'])->name('settings.notifications.save');
        Route::post('/settings/test-email', [AdminSettingsController::class, 'testEmail'])->name('settings.test-email');
        Route::post('/settings/links', [AdminSettingsController::class, 'saveLinks'])->name('settings.links.save');
        Route::post('/settings/automations', [AdminSettingsController::class, 'saveAutomations'])->name('settings.automations.save');
        Route::post('/settings/runtime-performance', [AdminSettingsController::class, 'saveRuntimePerformance'])->name('settings.runtime-performance.save');
        Route::post('/settings/test-redis', [AdminSettingsController::class, 'testRedis'])->name('settings.test-redis');
        Route::post('/settings/test-runtime', [AdminSettingsController::class, 'testRuntime'])->name('settings.test-runtime');
        Route::post('/settings/runtime-urls/reset', [AdminSettingsController::class, 'resetRuntimeUrls'])->name('settings.runtime-urls.reset');
        Route::post('/settings/test-docker', [AdminSettingsController::class, 'testDocker'])->name('settings.test-docker');
        Route::post('/settings/check-runtime-image', [AdminSettingsController::class, 'checkRuntimeImage'])->name('settings.check-runtime-image');
        Route::post('/settings/build-runtime-image', [AdminSettingsController::class, 'buildRuntimeImage'])->name('settings.build-runtime-image');
        Route::post('/settings/runtime-health-check', [AdminSettingsController::class, 'runtimeHealthCheck'])->name('settings.runtime-health-check');
        Route::post('/settings/maintenance/clear-cache', [AdminSettingsController::class, 'clearCache'])->name('settings.maintenance.clear-cache');
        Route::post('/settings/maintenance/clear-views', [AdminSettingsController::class, 'clearViews'])->name('settings.maintenance.clear-views');
        Route::post('/settings/maintenance/clear-routes', [AdminSettingsController::class, 'clearRoutes'])->name('settings.maintenance.clear-routes');
        Route::post('/settings/maintenance/storage-link', [AdminSettingsController::class, 'storageLink'])->name('settings.maintenance.storage-link');
        Route::post('/settings/maintenance/reset-webhooks', [AdminSettingsController::class, 'resetAllWebhooks'])->name('settings.maintenance.reset-webhooks');
        Route::post('/settings/webhooks/public-url', [AdminSettingsController::class, 'saveWebhookPublicUrl'])->name('settings.webhooks.public-url.save');
        Route::post('/settings/webhooks/reset-telegram', [AdminSettingsController::class, 'resetAllWebhooks'])->name('settings.webhooks.reset-telegram');
        Route::patch('/settings/maintenance-mode', [AdminSettingsController::class, 'updateMaintenanceMode'])->name('settings.maintenance-mode');
        Route::post('/settings/danger/enable-maintenance', [AdminSettingsController::class, 'enableMaintenance'])->name('settings.danger.enable-maintenance');
        Route::post('/settings/danger/disable-maintenance', [AdminSettingsController::class, 'disableMaintenance'])->name('settings.danger.disable-maintenance');
        Route::post('/settings/danger/disable-registrations', [AdminSettingsController::class, 'disableRegistrations'])->name('settings.danger.disable-registrations');
        Route::post('/settings/danger/enable-registrations', [AdminSettingsController::class, 'enableRegistrations'])->name('settings.danger.enable-registrations');
    });

require __DIR__.'/auth.php';
