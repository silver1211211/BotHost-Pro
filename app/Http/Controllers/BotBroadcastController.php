<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\Bot;
use App\Models\BotBroadcast;
use App\Jobs\ProcessBotBroadcastBatch;
use App\Services\BotAccessService;
use App\Services\BotBroadcastSenderService;
use App\Services\BotBroadcastTargetingService;
use App\Services\PlanAccessService;
use App\Services\UserStorageService;
use App\Services\AuditLogService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Illuminate\Validation\Rule;
use Throwable;

class BotBroadcastController extends Controller
{
    public function __construct(
        private readonly BotAccessService $access,
        private readonly BotBroadcastTargetingService $targeting,
        private readonly BotBroadcastSenderService $sender,
        private readonly PlanAccessService $planAccess,
        private readonly UserStorageService $storageService,
    ) {}

    public function globalIndex(Request $request): View
    {
        $bots = Bot::withTrashed()
            ->where('user_id', $request->user()->id)
            ->latest('updated_at')
            ->get()
            ->filter(fn (Bot $bot) => $this->sender->botCanBroadcast($bot))
            ->values();

        return view('broadcasts.index', [
            'eligibleBots' => $bots,
            'broadcasts' => BotBroadcast::query()
                ->with('bot')
                ->where('user_id', $request->user()->id)
                ->latest()
                ->paginate(20),
        ]);
    }

    public function index(Request $request, Bot $bot): View
    {
        $this->access->authorize($request, $bot);

        return view('bots.show', [
            'bot' => $bot->load(['setting', 'commands', 'logs', 'commandLogs.command']),
            'activeTab' => 'admin',
            'botUsers' => $bot->botUsers()->latest('last_active_at')->limit(50)->get(),
            'botUserAnalytics' => [],
            'botUserLanguages' => [],
            'botUserFilters' => ['search' => '', 'status' => 'all'],
            'botBroadcasts' => $bot->broadcasts()->withCount([
                'recipients as pending_count' => fn ($query) => $query->where('status', 'pending'),
            ])->latest()->paginate(25),
            'broadcastTargetCounts' => $this->targetCounts($bot),
            'adminSubTab' => 'broadcasts',
        ]);
    }

    public function store(Request $request, Bot $bot): RedirectResponse
    {
        $this->access->authorize($request, $bot);

        if (! $this->planAccess->userHasFeature($request->user(), 'broadcasts')) {
            return back()->withErrors(['broadcast' => 'This feature is not available on your current plan.']);
        }

        $messageType = $request->input('message_type', 'text');
        $messageRules = $messageType === 'image'
            ? ['nullable', 'string', 'max:1024']
            : ['required', 'string', 'max:4096'];

        $data = $request->validate([
            'title' => ['nullable', 'string', 'max:100'],
            'message_type' => ['nullable', Rule::in(['text', 'image'])],
            'message' => $messageRules,
            'target_type' => ['required', Rule::in(BotBroadcast::TARGET_TYPES)],
            'image' => [
                'required_if:message_type,image',
                'file',
                'mimes:'.implode(',', config('broadcasts.image.allowed_mimes', ['jpg', 'jpeg', 'png', 'webp'])),
                'max:'.config('broadcasts.image.max_size_kb', 10240),
            ],
            'cta_text' => ['nullable', 'required_with:cta_url', 'string', 'max:40'],
            'cta_url' => ['nullable', 'required_with:cta_text', 'url', 'max:2048', 'starts_with:https://'],
            'parse_mode' => ['nullable', Rule::in(['HTML', 'Markdown'])],
            'disable_web_page_preview' => ['nullable', 'boolean'],
            'custom_recipient_limit' => ['nullable', 'integer', 'min:1', 'max:10000000'],
            'scheduled_at' => ['nullable', 'date', 'after:now'],
            'recipient_file' => ['nullable', 'file', 'mimes:csv,txt', 'max:5120'],
            'recipient_text' => ['nullable', 'string', 'max:200000'],
            'send_now' => ['nullable', 'boolean'],
        ]);

        if (! $this->sender->botCanBroadcast($bot)) {
            return back()->withErrors(['broadcast' => 'This bot cannot send broadcasts until a verified token is available.']);
        }

        if ($request->boolean('send_now') && ! $this->planAccess->canSendBroadcast($request->user())) {
            return back()->withErrors(['broadcast' => $this->planAccess->userHasFeature($request->user(), 'broadcasts')
                ? 'You have reached your monthly broadcast limit. Upgrade your plan to send more.'
                : 'This feature is not available on your current plan.']);
        }

        $specificIdentifiers = [];

        if (($data['target_type'] ?? null) === 'specific_users') {
            $specificIdentifiers = $this->uploadedRecipientIdentifiers($request);

            if ($specificIdentifiers === []) {
                return back()
                    ->withInput($request->except(['image', 'recipient_file']))
                    ->withErrors(['recipient_file' => 'Upload or paste at least one phone number, username, or Telegram ID.']);
            }
        }

        $customRecipientLimit = $request->user()?->isAdmin()
            ? ($data['custom_recipient_limit'] ?? null)
            : null;
        $preview = $this->sender->previewTarget($bot, $data['target_type'], $request->user(), $customRecipientLimit);

        if (($data['target_type'] ?? null) === 'specific_users') {
            $matchedCount = $this->countSpecificMatches($bot, $specificIdentifiers);

            if ($matchedCount === 0) {
                return back()
                    ->withInput($request->except(['image', 'recipient_file']))
                    ->withErrors(['recipient_file' => 'None of the uploaded recipients match tracked users for this bot yet.']);
            }

            $preview['eligible_count'] = $matchedCount;
            $preview['count'] = min($matchedCount, $customRecipientLimit ?: $matchedCount);
            $preview['estimated_seconds'] = $this->sender->estimateSeconds($preview['count']);
            $preview['custom_limit_applied'] = is_int($customRecipientLimit) && $matchedCount > $customRecipientLimit;
        }

        $imageData = [];

        if ($request->hasFile('image')) {
            $file = $request->file('image');

            if (! $this->storageService->canStore($request->user(), $file->getSize())) {
                return back()
                    ->withInput($request->except('image'))
                    ->withErrors(['image' => 'You have reached your storage limit. Delete files or upgrade your plan.']);
            }

            $imageData = [
                'image_path' => $file->store("broadcasts/{$bot->id}", 'public'),
                'image_original_name' => $file->getClientOriginalName(),
                'image_mime' => $file->getMimeType(),
                'image_size' => $file->getSize(),
            ];
        }

        $broadcast = $bot->broadcasts()->create([
            'user_id' => $request->user()->id,
            'title' => $data['title'] ?? null,
            'message' => $data['message'] ?? '',
            'message_type' => $data['message_type'] ?? 'text',
            ...$imageData,
            'cta_text' => $data['cta_text'] ?? null,
            'cta_url' => $data['cta_url'] ?? null,
            'parse_mode' => $data['parse_mode'] ?? null,
            'disable_web_page_preview' => (bool) ($data['disable_web_page_preview'] ?? false),
            'target_type' => $data['target_type'],
            'target_count' => $preview['count'],
            'estimated_seconds' => $preview['estimated_seconds'],
            'status' => 'draft',
            'scheduled_at' => $data['scheduled_at'] ?? null,
            'metadata' => [
                'eligible_count' => $preview['eligible_count'],
                'applied_limit' => $preview['applied_limit'],
                'limit_applied' => $preview['limit_applied'],
                'plan_at_send' => $preview['plan_at_send'],
                'custom_recipient_limit' => $preview['custom_recipient_limit'],
                'custom_limit_applied' => $preview['custom_limit_applied'],
                    'set_by_admin_id' => $customRecipientLimit ? $request->user()->id : null,
                    'specific_recipient_identifiers' => $specificIdentifiers,
                    'specific_uploaded_count' => count($specificIdentifiers),
            ],
        ]);

        $this->log($request, 'bot_broadcast_draft_created', "Created broadcast draft {$broadcast->id} for bot {$bot->id}.");
        app(AuditLogService::class)->log('broadcast', 'broadcast.created', 'Broadcast draft created.', [
            'broadcast_id' => $broadcast->id,
            'bot_id' => $bot->id,
            'total_recipients' => $preview['count'],
        ], $request->user(), 'success', BotBroadcast::class, $broadcast->id);

        if ($request->boolean('send_now')) {
            $result = $this->sender->start($broadcast);

            if (! $result['ok']) {
                return back()->withErrors(['broadcast' => $result['message'] ?? 'Unable to start broadcast.']);
            }

            $summary = $this->sender->processNextBatch($broadcast->fresh(), $this->sender->batchSize());
            $broadcast->refresh();

            if (
                Config::get('queue.default') !== 'sync'
                && in_array($broadcast->status, ['queued', 'running', 'sending'], true)
                && $broadcast->recipients()->where('status', 'pending')->exists()
            ) {
                ProcessBotBroadcastBatch::dispatch($broadcast->id)->delay(now()->addSecond());
            }

            $notice = $this->sender->limitNotice($broadcast);

            return $this->redirectToBroadcasts($bot)
                ->with('status', trim("Broadcast sent now. Processed {$summary['processed']} recipients. Sent {$summary['sent']}, failed {$summary['failed']}. ".($notice ?? '')));
        }

        return $this->redirectToBroadcasts($bot)->with('status', 'Broadcast draft created.');
    }

    public function show(Request $request, Bot $bot, BotBroadcast $broadcast): View
    {
        $this->access->authorize($request, $bot);
        abort_unless($request->user()?->isAdmin(), 403);
        $this->authorizeBroadcast($bot, $broadcast);
        $broadcast->loadCount([
            'recipients as pending_count' => fn ($query) => $query->where('status', 'pending'),
        ]);

        return view('bots.show', [
            'bot' => $bot->load(['setting', 'commands', 'logs', 'commandLogs.command']),
            'activeTab' => 'admin',
            'botUsers' => $bot->botUsers()->latest('last_active_at')->limit(50)->get(),
            'botUserAnalytics' => [],
            'botUserLanguages' => [],
            'botUserFilters' => ['search' => '', 'status' => 'all'],
            'botBroadcasts' => $bot->broadcasts()->withCount([
                'recipients as pending_count' => fn ($query) => $query->where('status', 'pending'),
            ])->latest()->limit(25)->get(),
            'selectedBroadcast' => $broadcast,
            'broadcastTargetCounts' => $this->targetCounts($bot),
            'adminSubTab' => 'broadcasts',
        ]);
    }

    public function destroy(Request $request, Bot $bot, BotBroadcast $broadcast): RedirectResponse
    {
        $this->access->authorize($request, $bot);
        $this->authorizeBroadcast($bot, $broadcast);

        $this->deleteBroadcastImageIfUnused($broadcast);
        $broadcast->delete();
        $this->log($request, 'bot_broadcast_draft_deleted', "Deleted broadcast draft {$broadcast->id} for bot {$bot->id}.");

        return $this->redirectToBroadcasts($bot)->with('status', 'Broadcast draft deleted.');
    }

    public function start(Request $request, Bot $bot, BotBroadcast $broadcast): RedirectResponse
    {
        $this->access->authorize($request, $bot);
        $this->authorizeBroadcast($bot, $broadcast);

        if (! $this->planAccess->canSendBroadcast($request->user())) {
            return back()->withErrors(['broadcast' => $this->planAccess->userHasFeature($request->user(), 'broadcasts')
                ? 'You have reached your monthly broadcast limit. Upgrade your plan to send more.'
                : 'This feature is not available on your current plan.']);
        }

        if (! $this->sender->botCanBroadcast($bot)) {
            return back()->withErrors(['broadcast' => 'This bot cannot send broadcasts until a verified token is available.']);
        }

        $result = $this->sender->start($broadcast);

        if (! $result['ok']) {
            return back()->withErrors(['broadcast' => $result['message'] ?? 'Unable to start broadcast.']);
        }

        $summary = $this->sender->processNextBatch($broadcast->fresh(), $this->sender->batchSize());
        $broadcast->refresh();

        if (
            Config::get('queue.default') !== 'sync'
            && in_array($broadcast->status, ['queued', 'running', 'sending'], true)
            && $broadcast->recipients()->where('status', 'pending')->exists()
        ) {
            ProcessBotBroadcastBatch::dispatch($broadcast->id)->delay(now()->addSecond());
        }

        $notice = $this->sender->limitNotice($broadcast);
        $message = "Broadcast started. Processed {$summary['processed']} recipients. Sent {$summary['sent']}, failed {$summary['failed']}.";

        return $this->redirectToBroadcasts($bot)->with('status', trim($message.' '.($notice ?? '')));
    }

    public function cancel(Request $request, Bot $bot, BotBroadcast $broadcast): RedirectResponse
    {
        $this->access->authorize($request, $bot);
        $this->authorizeBroadcast($bot, $broadcast);

        if (! in_array($broadcast->status, ['scheduled', 'queued', 'running', 'sending'], true)) {
            return back()->withErrors(['broadcast' => 'Only scheduled, queued, or running broadcasts can be cancelled.']);
        }

        $broadcast->recipients()->where('status', 'pending')->update([
            'status' => 'skipped',
            'updated_at' => now(),
        ]);

        $metadata = $broadcast->metadata ?? [];
        $metadata['completed_reason'] = 'Cancelled by owner.';

        $broadcast->forceFill([
            'status' => 'cancelled',
            'completed_at' => now(),
            'cancelled_at' => now(),
            'metadata' => $metadata,
        ])->save();

        $this->log($request, 'broadcast_cancelled', "Cancelled broadcast {$broadcast->id} for bot {$bot->id}.");
        app(AuditLogService::class)->log('broadcast', 'broadcast.cancelled', 'Broadcast cancelled.', [
            'broadcast_id' => $broadcast->id,
            'bot_id' => $bot->id,
            'total_recipients' => $broadcast->target_count,
            'sent_count' => $broadcast->sent_count,
            'failed_count' => $broadcast->failed_count,
        ], $request->user(), 'success', BotBroadcast::class, $broadcast->id);

        return $this->redirectToBroadcasts($bot)->with('status', 'Broadcast cancelled.');
    }

    public function processNextBatch(Request $request, Bot $bot, BotBroadcast $broadcast): RedirectResponse
    {
        $this->access->authorize($request, $bot);
        $this->authorizeBroadcast($bot, $broadcast);

        if (! $this->sender->botCanBroadcast($bot)) {
            return back()->withErrors(['broadcast' => 'This bot cannot send broadcasts until a verified token is available.']);
        }

        $summary = $this->sender->processNextBatch($broadcast, $this->sender->batchSize());

        return $this->redirectToBroadcasts($bot)->with('status', "Processed {$summary['processed']} recipients. Sent {$summary['sent']}, failed {$summary['failed']}.");
    }

    public function testSend(Request $request, Bot $bot, BotBroadcast $broadcast): RedirectResponse
    {
        $this->access->authorize($request, $bot);
        $this->authorizeBroadcast($bot, $broadcast);

        if (! $this->sender->botCanBroadcast($bot)) {
            return back()->withErrors(['broadcast' => 'This bot cannot send broadcasts until a verified token is available.']);
        }

        $result = $this->sender->sendTest($broadcast);

        if (! $result['ok']) {
            return back()->withErrors(['broadcast' => $result['message'] ?? 'Test send failed.']);
        }

        return $this->redirectToBroadcasts($bot)->with('status', 'Test broadcast sent to the most recent active Telegram user.');
    }

    public function retryFailed(Request $request, Bot $bot, BotBroadcast $broadcast): RedirectResponse
    {
        $this->access->authorize($request, $bot);
        $this->authorizeBroadcast($bot, $broadcast);

        $result = $this->sender->retryFailed($broadcast);

        if (! $result['ok']) {
            return back()->withErrors(['broadcast' => $result['message'] ?? 'Unable to retry failed recipients.']);
        }

        $summary = $this->sender->processNextBatch($broadcast->fresh(), $this->sender->batchSize());
        $broadcast->refresh();

        if (
            Config::get('queue.default') !== 'sync'
            && in_array($broadcast->status, ['queued', 'running', 'sending'], true)
            && $broadcast->recipients()->where('status', 'pending')->exists()
        ) {
            ProcessBotBroadcastBatch::dispatch($broadcast->id)->delay(now()->addSecond());
        }

        return $this->redirectToBroadcasts($bot)->with('status', "Retry queued for {$result['retried']} failed recipients. Processed {$summary['processed']} now.");
    }

    public function status(Request $request, Bot $bot, BotBroadcast $broadcast): JsonResponse
    {
        $this->access->authorize($request, $bot);
        $this->authorizeBroadcast($bot, $broadcast);

        $broadcast->refresh();
        $pendingCount = $broadcast->recipients()->where('status', 'pending')->count();
        $processedCount = $broadcast->sent_count + $broadcast->failed_count;
        $progress = $broadcast->target_count > 0
            ? (int) floor(($processedCount / $broadcast->target_count) * 100)
            : 0;

        return response()->json([
            'ok' => true,
            'broadcast' => [
                'id' => $broadcast->id,
                'status' => $broadcast->status,
                'target_count' => $broadcast->target_count,
                'sent_count' => $broadcast->sent_count,
                'failed_count' => $broadcast->failed_count,
                'pending_count' => $pendingCount,
                'progress' => min(100, $progress),
                'estimated_seconds' => $broadcast->estimated_seconds,
                'estimated_human' => $broadcast->estimated_send_time_human,
                'started_at' => $broadcast->started_at?->toISOString(),
                'completed_at' => $broadcast->completed_at?->toISOString(),
            ],
        ]);
    }

    public function targetCount(Request $request, Bot $bot): JsonResponse
    {
        $this->access->authorize($request, $bot);

        $data = $request->validate([
            'target_type' => ['required', Rule::in(BotBroadcast::TARGET_TYPES)],
            'custom_recipient_limit' => ['nullable', 'integer', 'min:1', 'max:10000000'],
        ]);

        if (! $this->sender->botCanBroadcast($bot)) {
            return response()->json([
                'ok' => false,
                'message' => 'This bot cannot send broadcasts until a verified token is available.',
            ], 422);
        }
        $customRecipientLimit = $request->user()?->isAdmin()
            ? ($data['custom_recipient_limit'] ?? null)
            : null;

        return response()->json([
            'ok' => true,
            ...$this->sender->previewTarget($bot, $data['target_type'], $request->user(), $customRecipientLimit),
        ]);
    }

    private function targetCounts(Bot $bot): array
    {
        $counts = [];

        foreach (BotBroadcast::TARGET_TYPES as $targetType) {
            $counts[$targetType] = $this->sender->previewTarget($bot, $targetType, request()->user())['count'];
        }

        return $counts;
    }

    private function authorizeBroadcast(Bot $bot, BotBroadcast $broadcast): void
    {
        abort_unless($broadcast->bot_id === $bot->id, 403);
    }

    private function redirectToBroadcasts(Bot $bot): RedirectResponse
    {
        return redirect()->route('bots.show', [
            'bot' => $bot,
            'tab' => 'admin',
            'admin_tab' => 'broadcasts',
        ]);
    }

    private function log(Request $request, string $action, string $description): void
    {
        try {
            ActivityLog::create([
                'user_id' => $request->user()->id,
                'action' => $action,
                'description' => $description,
                'ip_address' => $request->ip(),
                'created_at' => now(),
            ]);
        } catch (Throwable $exception) {
            Log::warning('Failed to write broadcast activity log', [
                'action' => $action,
                'error' => $exception->getMessage(),
            ]);
        }
    }

    private function deleteBroadcastImageIfUnused(BotBroadcast $broadcast): void
    {
        if (! filled($broadcast->image_path)) {
            return;
        }

        $inUse = BotBroadcast::query()
            ->where('image_path', $broadcast->image_path)
            ->whereKeyNot($broadcast->id)
            ->exists();

        if (! $inUse) {
            Storage::disk('public')->delete($broadcast->image_path);
        }
    }

    private function uploadedRecipientIdentifiers(Request $request): array
    {
        $raw = (string) $request->input('recipient_text', '');

        if ($request->hasFile('recipient_file')) {
            $raw .= "\n".file_get_contents($request->file('recipient_file')->getRealPath());
        }

        preg_match_all('/@[\w_]{3,}|[+]?\d[\d\s().-]{4,}\d|\b\d{5,}\b/', $raw, $matches);

        return collect($matches[0] ?? [])
            ->map(fn ($value) => $this->normalizeRecipientIdentifier($value))
            ->filter()
            ->unique()
            ->values()
            ->all();
    }

    private function countSpecificMatches(Bot $bot, array $identifiers): int
    {
        $identifiers = collect($identifiers);

        return $bot->botUsers()
            ->where('status', '!=', 'deleted')
            ->whereNotNull('telegram_user_id')
            ->where('telegram_user_id', '!=', '')
            ->get()
            ->filter(function ($botUser) use ($identifiers): bool {
                $values = collect([
                    $botUser->telegram_user_id,
                    $botUser->telegram_username,
                    data_get($botUser->metadata, 'phone'),
                    data_get($botUser->metadata, 'phone_number'),
                    data_get($botUser->metadata, 'telegram_phone'),
                    data_get($botUser->metadata, 'contact.phone_number'),
                ])->map(fn ($value) => $this->normalizeRecipientIdentifier($value))->filter();

                return $values->intersect($identifiers)->isNotEmpty();
            })
            ->count();
    }

    private function normalizeRecipientIdentifier(mixed $value): ?string
    {
        $value = trim((string) $value);

        if ($value === '') {
            return null;
        }

        if (str_starts_with($value, '@')) {
            return strtolower($value);
        }

        $digits = preg_replace('/\D+/', '', $value);

        return $digits !== '' ? $digits : strtolower($value);
    }
}
