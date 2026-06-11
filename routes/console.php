<?php

use Illuminate\Foundation\Inspiring;
use App\Models\PlatformSetting;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::command('bots:purge-recycled')->daily();

Schedule::command('broadcasts:process')
    ->everyMinute()
    ->when(fn () => filter_var(PlatformSetting::getValue('automation_process_broadcasts_enabled', '1'), FILTER_VALIDATE_BOOLEAN));

Schedule::command('webhooks:reconnect-telegram')
    ->everyFiveMinutes()
    ->withoutOverlapping()
    ->when(fn () => filter_var(PlatformSetting::getValue(
        'automation_reconnect_webhooks_enabled',
        PlatformSetting::getValue('automation_webhook_health_check_enabled', '0'),
    ), FILTER_VALIDATE_BOOLEAN));

Schedule::command('runtime:warm-active-bots')
    ->everyFiveMinutes()
    ->withoutOverlapping()
    ->when(fn () => PlatformSetting::getValue('runtime_mode', env('RUNTIME_MODE', 'local')) === 'docker');

Schedule::command('runtime:health-check')
    ->everyMinute()
    ->withoutOverlapping()
    ->when(fn () => PlatformSetting::getValue('runtime_mode', env('RUNTIME_MODE', 'local')) === 'docker');

Schedule::command('runtime:cleanup')
    ->dailyAt('03:20')
    ->withoutOverlapping()
    ->when(fn () => PlatformSetting::getValue('runtime_mode', env('RUNTIME_MODE', 'local')) === 'docker');

Schedule::command('logs:prune-plan-retention')
    ->dailyAt('02:10')
    ->when(fn () => filter_var(PlatformSetting::getValue('automation_prune_logs_enabled', '1'), FILTER_VALIDATE_BOOLEAN));

Schedule::command('audit-logs:prune')
    ->dailyAt('02:30');
