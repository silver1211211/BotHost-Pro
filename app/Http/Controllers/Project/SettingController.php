<?php

namespace App\Http\Controllers\Project;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Project;
use App\Services\ProjectAccessService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class SettingController extends Controller
{
    public function __construct(private readonly ProjectAccessService $access) {}

    public function update(Request $request, Project $project): RedirectResponse
    {
        $this->access->authorize($request, $project);

        $data = $request->validate([
            'bot_token' => ['nullable', 'string', 'max:255'],
            'admin_id' => ['nullable', 'string', 'max:80'],
            'oxapay_api_key' => ['nullable', 'string', 'max:255'],
            'external_apis' => ['nullable', 'string', 'max:3000'],
            'ram_limit' => ['required', 'integer', 'min:128', 'max:1024'],
            'cpu_limit' => ['required', 'numeric', 'min:0.1', 'max:2'],
            'timezone' => ['required', 'timezone'],
            'auto_restart' => ['nullable', 'boolean'],
            'webhook_enabled' => ['nullable', 'boolean'],
        ]);

        $setting = $project->setting()->firstOrCreate([]);

        $payload = [
            'admin_id' => $data['admin_id'] ?? null,
            'external_apis' => $this->parseExternalApis($data['external_apis'] ?? null),
            'ram_limit' => $data['ram_limit'],
            'cpu_limit' => $data['cpu_limit'],
            'timezone' => $data['timezone'],
            'auto_restart' => $request->boolean('auto_restart'),
            'webhook_enabled' => $request->boolean('webhook_enabled'),
        ];

        if ($request->filled('bot_token')) {
            $payload['bot_token'] = $data['bot_token'];
        }

        if ($request->filled('oxapay_api_key')) {
            $payload['oxapay_api_key'] = $data['oxapay_api_key'];
        }

        $setting->update($payload);

        ActivityLog::create([
            'user_id' => $request->user()->id,
            'action' => 'Updated settings for project: '.$project->name,
            'ip_address' => $request->ip(),
            'created_at' => now(),
        ]);

        return redirect()->route('projects.show', $project)->with('status', 'Settings updated.');
    }

    private function parseExternalApis(?string $value): array
    {
        if (! $value) {
            return [];
        }

        return collect(preg_split('/\r\n|\r|\n/', $value))
            ->map(fn (string $line) => trim($line))
            ->filter()
            ->values()
            ->all();
    }
}
