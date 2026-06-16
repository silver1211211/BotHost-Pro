<?php

namespace App\Http\Controllers;

use App\Models\Bot;
use App\Models\BotTemplate;
use App\Services\BotAccessService;
use App\Services\BotTemplateImporter;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class BotTemplateImportController extends Controller
{
    public function __construct(
        private readonly BotAccessService $access,
        private readonly BotTemplateImporter $importer,
    ) {}

    public function index(Request $request, Bot $bot): View
    {
        $this->access->authorize($request, $bot);

        $user = $request->user();
        $templates = BotTemplate::query()
            ->where('status', 'published')
            ->where(function ($query) use ($user): void {
                if ($user?->isAdmin()) {
                    return;
                }

                $query->whereHas('purchases', fn ($purchaseQuery) => $purchaseQuery
                    ->where('user_id', $user->id)
                    ->where('status', 'completed'))
                    ->orWhere(function ($includedQuery) use ($user): void {
                        $plan = $user->subscription_plan ?? 'free';
                        $plans = match ($plan) {
                            'business' => ['free', 'pro', 'business'],
                            'pro' => ['free', 'pro'],
                            default => ['free'],
                        };

                        $includedQuery->whereIn('included_plan', $plans);
                    });
            })
            ->withCount('commands')
            ->when($request->filled('search'), function ($query) use ($request): void {
                $search = '%'.$request->string('search')->toString().'%';
                $query->where(fn ($q) => $q->where('name', 'like', $search)->orWhere('description', 'like', $search));
            })
            ->when($request->filled('category'), fn ($query) => $query->where('category', $request->string('category')))
            ->when($request->filled('level'), fn ($query) => $query->where('level', $request->string('level')))
            ->orderByDesc('is_featured')
            ->latest('published_at')
            ->paginate(20)
            ->withQueryString();

        return view('bots.templates.index', [
            'bot' => $bot,
            'templates' => $templates,
            'categories' => BotTemplate::query()->where('status', 'published')->whereNotNull('category')->distinct()->pluck('category'),
        ]);
    }

    public function show(Request $request, Bot $bot, BotTemplate $template): View
    {
        $this->access->authorize($request, $bot);
        abort_unless($template->status === 'published', 404);
        abort_unless($template->canBeImportedBy($request->user()), 403);

        $template->loadCount('commands');

        return view('bots.templates.show', [
            'bot' => $bot,
            'template' => $template,
            'conflicts' => $this->importer->conflicts($bot, $template),
        ]);
    }

    public function import(Request $request, Bot $bot, BotTemplate $template): RedirectResponse
    {
        $this->access->authorize($request, $bot);
        abort_unless($template->status === 'published', 404);

        if (! $template->canBeImportedBy($request->user())) {
            return back()->withErrors(['template' => 'Please purchase this template before importing.']);
        }

        $data = $request->validate([
            'conflict_strategy' => ['nullable', Rule::in(['skip', 'rename', 'replace', 'replace_all', 'cancel'])],
        ]);

        $conflicts = $this->importer->conflicts($bot, $template);
        $strategy = $data['conflict_strategy'] ?? null;

        if ($conflicts !== [] && $strategy === null) {
            return redirect()
                ->route('bots.templates.show', [$bot, $template])
                ->with('status', 'This bot already has '.count($conflicts).' matching '.str('command')->plural(count($conflicts)).'. Choose how to continue.');
        }

        if ($strategy === 'cancel') {
            return back()->with('status', 'Template import cancelled.');
        }

        $import = $this->importer->import(
            $bot,
            $template,
            $request->user(),
            $strategy ?? 'skip',
        );

        if ($import->status === 'failed') {
            return back()->withErrors(['template' => 'Template import failed.']);
        }

        $summary = $import->summary ?? [];
        $replaced = count($summary['replaced'] ?? []);

        $message = ($summary['cleared_all'] ?? false)
            ? "All existing commands replaced. {$import->imported_commands_count} commands imported from template."
            : "Template imported: {$import->imported_commands_count} commands added, {$import->skipped_commands_count} skipped, {$replaced} replaced.";

        return redirect()
            ->route('bots.show', ['bot' => $bot, 'tab' => 'commands'])
            ->with('status', $message);
    }
}
