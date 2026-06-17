<?php

namespace App\Http\Controllers;

use App\Jobs\RecheckBotTemplatePurchase;
use App\Models\BotTemplate;
use App\Models\PaymentInvoice;
use App\Services\BotTemplatePurchaseService;
use App\Services\PlanAccessService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class TemplateMarketplaceController extends Controller
{
    public function index(Request $request): View
    {
        $purchasedIds = $request->user()->templatePurchases()->where('status', 'completed')->pluck('bot_template_id')->all();

        $templates = $this->marketplaceQuery($request)
            ->when(! empty($purchasedIds), fn ($q) => $q->whereNotIn('id', $purchasedIds))
            ->paginate(20)
            ->withQueryString();

        return view('templates.index', [
            'templates' => $templates,
            'categories' => BotTemplate::query()
                ->where('status', 'published')
                ->whereIn('marketplace_status', ['listed', 'featured'])
                ->whereNotNull('category')
                ->when(! empty($purchasedIds), fn ($q) => $q->whereNotIn('id', $purchasedIds))
                ->selectRaw('category, COUNT(*) as count')
                ->groupBy('category')
                ->orderBy('category')
                ->get()
                ->mapWithKeys(fn ($row) => [$row->category => $row->count]),
            'purchasedIds' => $purchasedIds,
            'pendingInvoices' => $request->user()
                ->paymentInvoices()
                ->where('type', PaymentInvoice::TYPE_TEMPLATE_PURCHASE)
                ->whereIn('status', ['pending', 'waiting', 'paying', 'confirming'])
                ->latest()
                ->get()
                ->keyBy('reference_id'),
        ]);
    }

    public function show(Request $request, BotTemplate $template): View
    {
        $user = $request->user();
        $canAccess = $template->status === 'published' && (
            in_array($template->marketplace_status, ['listed', 'featured'], true)
            || $template->isPurchasedBy($user)
            || $template->isIncludedFor($user)
            || $user?->isAdmin()
        );
        abort_unless($canAccess, 404);
        $template->loadCount('commands');

        return view('templates.show', [
            'template' => $template,
            'bots' => $request->user()->bots()->latest()->get(),
            'purchased' => $template->isPurchasedBy($request->user()),
            'canImport' => $template->canBeImportedBy($request->user()),
            'pendingInvoice' => $template->paymentInvoices()
                ->where('user_id', $request->user()->id)
                ->where('type', PaymentInvoice::TYPE_TEMPLATE_PURCHASE)
                ->whereIn('status', ['pending', 'waiting', 'paying', 'confirming'])
                ->latest()
                ->first(),
        ]);
    }

    public function purchase(Request $request, BotTemplate $template, BotTemplatePurchaseService $purchases): RedirectResponse
    {
        return back()->withErrors(['purchase' => 'Please use the crypto invoice flow to purchase this template.']);
    }

    public function unlockFree(Request $request, BotTemplate $template, BotTemplatePurchaseService $purchases, PlanAccessService $planAccess): RedirectResponse
    {
        abort_unless(
            $template->status === 'published'
            && in_array($template->marketplace_status, ['listed', 'featured'], true)
            && ($template->isFree() || $template->isIncludedFor($request->user())),
            404,
        );

        if ($template->isPurchasedBy($request->user())) {
            return back()->with('status', 'Template already added to your library.');
        }

        if (! $planAccess->userHasFeature($request->user(), 'template_marketplace')) {
            return back()->withErrors(['template' => 'Your current plan does not include template marketplace access. Upgrade to unlock templates.']);
        }

        if (! $planAccess->canUnlockFreeTemplate($request->user())) {
            return back()->withErrors(['template' => 'You have reached your monthly free template unlock limit. Upgrade your plan or purchase paid templates.']);
        }

        $purchases->unlockFree($request->user(), $template);

        return back()->with('status', 'Template unlocked successfully.');
    }

    public function statusCheck(Request $request): JsonResponse
    {
        $ids = array_filter(array_map('intval', explode(',', (string) $request->input('ids', ''))));

        if (empty($ids)) {
            return response()->json([]);
        }

        $user = $request->user();

        foreach ($ids as $id) {
            RecheckBotTemplatePurchase::dispatch($user->id, $id);
        }

        $purchasedIds = $user->templatePurchases()
            ->where('status', 'completed')
            ->whereIn('bot_template_id', $ids)
            ->pluck('bot_template_id')
            ->flip()
            ->all();

        $pendingInvoices = $user->paymentInvoices()
            ->where('type', PaymentInvoice::TYPE_TEMPLATE_PURCHASE)
            ->whereIn('reference_id', $ids)
            ->whereIn('status', ['pending', 'waiting', 'paying', 'confirming'])
            ->latest()
            ->get(['id', 'reference_id'])
            ->keyBy('reference_id');

        $result = [];
        foreach ($ids as $id) {
            $result[$id] = [
                'purchased'  => isset($purchasedIds[$id]),
                'invoice_id' => $pendingInvoices->get($id)?->id,
            ];
        }

        return response()->json($result);
    }

    public function purchased(Request $request): View
    {
        return view('templates.purchased', [
            'purchases' => $request->user()
                ->templatePurchases()
                ->with('template')
                ->where('status', 'completed')
                ->latest('purchased_at')
                ->paginate(20),
            'bots' => $request->user()->bots()->latest()->get(),
        ]);
    }

    private function marketplaceQuery(Request $request)
    {
        $query = BotTemplate::query()
            ->where('status', 'published')
            ->whereIn('marketplace_status', ['listed', 'featured'])
            ->withCount('commands');

        if ($request->filled('search')) {
            $search = '%'.$request->string('search')->toString().'%';
            $query->where(fn ($q) => $q->where('name', 'like', $search)->orWhere('description', 'like', $search));
        }
        if ($request->filled('category')) {
            $query->where('category', $request->string('category'));
        }
        if ($request->filled('level')) {
            $query->where('level', $request->string('level'));
        }
        if (in_array($request->input('access_type'), ['free', 'paid'], true)) {
            $query->where('access_type', $request->input('access_type'));
        }
        if ($request->boolean('featured')) {
            $query->where(fn ($q) => $q->where('is_featured', true)->orWhere('marketplace_status', 'featured'));
        }

        match ($request->input('sort')) {
            'popular' => $query->orderByDesc('sales_count')->orderByDesc('import_count'),
            'price_low' => $query->orderBy('price'),
            'price_high' => $query->orderByDesc('price'),
            default => $query->orderByDesc('is_featured')->latest('published_at'),
        };

        return $query;
    }
}
