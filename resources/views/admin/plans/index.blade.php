<x-admin-layout title="Plans" subtitle="Manage subscription plans, features, and limits">

    @php $activeTab = request('tab', 'plans'); @endphp

    {{-- Flash --}}
    @if(session('status'))
        <div class="mb-4 rounded border border-[#22C55E]/30 bg-[#22C55E]/10 p-3 text-sm font-semibold text-[#22C55E]">{{ session('status') }}</div>
    @endif
    @if($errors->any())
        <div class="mb-4 rounded border border-[#EF4444]/30 bg-[#EF4444]/10 p-3 text-sm text-[#EF4444]">{{ $errors->first() }}</div>
    @endif

    {{-- Tabs --}}
    <div class="mb-5 flex gap-1 overflow-x-auto rounded-xl border border-[#27213D] bg-[#090713] p-1">
        @foreach([
            'plans'           => 'Subscription Plans',
            'limits'          => 'Plan Limits',
            'features'        => 'Feature Access',
            'template-access' => 'Template Access',
            'broadcast-limits'=> 'Broadcast Limits',
        ] as $tabKey => $tabLabel)
            <a href="{{ route('admin.plans.index', ['tab' => $tabKey]) }}"
               class="shrink-0 rounded-lg px-4 py-2 text-sm font-semibold transition
                      {{ $activeTab === $tabKey ? 'bg-[#27213D] text-white' : 'text-[#71717A] hover:text-white' }}">
                {{ $tabLabel }}
            </a>
        @endforeach
    </div>

    {{-- ===================== TAB 1: SUBSCRIPTION PLANS ===================== --}}
    @if($activeTab === 'plans')
        <div class="space-y-4">
            @foreach($plans as $plan)
                <form method="POST" action="{{ route('admin.plans.update', $plan) }}"
                      class="rounded-2xl border border-[#27213D] bg-[#0F0D1A] p-5 space-y-4">
                    @csrf @method('PATCH')
                    <div class="flex items-center justify-between">
                        <h2 class="text-lg font-black text-white">{{ $plan->name }}
                            <span class="ml-2 text-xs font-normal text-[#71717A]">slug: {{ $plan->slug }}</span>
                        </h2>
                        <span class="rounded-full px-2 py-0.5 text-xs font-bold
                            {{ $plan->status === 'active' ? 'bg-[#22C55E]/15 text-[#22C55E]' : 'bg-[#EF4444]/15 text-[#EF4444]' }}">
                            {{ ucfirst($plan->status) }}
                        </span>
                    </div>
                    <div class="grid gap-4 md:grid-cols-2 lg:grid-cols-3">
                        <label class="grid gap-1 text-xs font-bold text-[#A1A1AA]">
                            Name
                            <input name="name" value="{{ old('name', $plan->name) }}"
                                   class="rounded border border-[#27213D] bg-[#090713] px-3 py-2 text-sm text-white">
                        </label>
                        <label class="grid gap-1 text-xs font-bold text-[#A1A1AA]">
                            Price
                            <input name="price" type="number" step="0.01" min="0" value="{{ old('price', $plan->price) }}"
                                   class="rounded border border-[#27213D] bg-[#090713] px-3 py-2 text-sm text-white">
                        </label>
                        <label class="grid gap-1 text-xs font-bold text-[#A1A1AA]">
                            Currency
                            <input name="currency" value="{{ old('currency', $plan->currency) }}"
                                   class="rounded border border-[#27213D] bg-[#090713] px-3 py-2 text-sm text-white">
                        </label>
                        <div class="grid gap-1">
                            <span class="text-xs font-bold text-[#A1A1AA]">Billing Period</span>
                            <div class="relative" x-data="{ open: false, val: '{{ old('billing_period', $plan->billing_period) }}', get label() { return { 'monthly': 'Monthly', 'yearly': 'Yearly' }[this.val] || 'Monthly' } }" @click.away="open = false">
                                <input type="hidden" name="billing_period" :value="val">
                                <button type="button" @click="open = !open" class="flex w-full items-center justify-between rounded-xl border border-[#27213D] bg-[#090713] px-3 py-2 text-sm text-[#F8FAFC] transition focus:outline-none" :class="open ? 'border-[#8B5CF6]/60 ring-1 ring-[#8B5CF6]/15' : ''">
                                    <span x-text="label"></span>
                                    <svg class="ml-2 h-3.5 w-3.5 shrink-0 text-[#71717A] transition-transform" :class="open ? 'rotate-180' : ''" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="m19.5 8.25-7.5 7.5-7.5-7.5"/></svg>
                                </button>
                                <div x-show="open" x-cloak x-transition:enter="transition ease-out duration-100" x-transition:enter-start="opacity-0 -translate-y-1" x-transition:enter-end="opacity-100 translate-y-0" x-transition:leave="transition ease-in duration-75" x-transition:leave-start="opacity-100 translate-y-0" x-transition:leave-end="opacity-0 -translate-y-1"
                                    class="absolute z-50 mt-1.5 w-full overflow-hidden rounded-xl border border-[#27213D] bg-[#151225] shadow-[0_16px_48px_rgba(0,0,0,0.55)]">
                                    @foreach (['monthly' => 'Monthly', 'yearly' => 'Yearly'] as $bpv => $bpl)
                                    <button type="button" @click="val = '{{ $bpv }}'; open = false" class="flex w-full items-center gap-3 px-4 py-2.5 text-left text-sm transition hover:bg-[#1D1930]" :class="val === '{{ $bpv }}' ? 'text-[#A855F7] bg-[#8B5CF6]/8' : 'text-[#A1A1AA]'">
                                        <svg :class="val === '{{ $bpv }}' ? 'opacity-100 text-[#A855F7]' : 'opacity-0'" class="h-3.5 w-3.5 shrink-0 transition-opacity" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5"/></svg>
                                        {{ $bpl }}
                                    </button>
                                    @endforeach
                                </div>
                            </div>
                        </div>
                        <div class="grid gap-1">
                            <span class="text-xs font-bold text-[#A1A1AA]">Status</span>
                            <div class="relative" x-data="{ open: false, val: '{{ old('status', $plan->status) }}', get label() { return { 'active': 'Active', 'inactive': 'Inactive' }[this.val] || 'Active' } }" @click.away="open = false">
                                <input type="hidden" name="status" :value="val">
                                <button type="button" @click="open = !open" class="flex w-full items-center justify-between rounded-xl border border-[#27213D] bg-[#090713] px-3 py-2 text-sm text-[#F8FAFC] transition focus:outline-none" :class="open ? 'border-[#8B5CF6]/60 ring-1 ring-[#8B5CF6]/15' : ''">
                                    <span x-text="label"></span>
                                    <svg class="ml-2 h-3.5 w-3.5 shrink-0 text-[#71717A] transition-transform" :class="open ? 'rotate-180' : ''" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="m19.5 8.25-7.5 7.5-7.5-7.5"/></svg>
                                </button>
                                <div x-show="open" x-cloak x-transition:enter="transition ease-out duration-100" x-transition:enter-start="opacity-0 -translate-y-1" x-transition:enter-end="opacity-100 translate-y-0" x-transition:leave="transition ease-in duration-75" x-transition:leave-start="opacity-100 translate-y-0" x-transition:leave-end="opacity-0 -translate-y-1"
                                    class="absolute z-50 mt-1.5 w-full overflow-hidden rounded-xl border border-[#27213D] bg-[#151225] shadow-[0_16px_48px_rgba(0,0,0,0.55)]">
                                    @foreach (['active' => 'Active', 'inactive' => 'Inactive'] as $stv => $stl)
                                    <button type="button" @click="val = '{{ $stv }}'; open = false" class="flex w-full items-center gap-3 px-4 py-2.5 text-left text-sm transition hover:bg-[#1D1930]" :class="val === '{{ $stv }}' ? 'text-[#A855F7] bg-[#8B5CF6]/8' : 'text-[#A1A1AA]'">
                                        <svg :class="val === '{{ $stv }}' ? 'opacity-100 text-[#A855F7]' : 'opacity-0'" class="h-3.5 w-3.5 shrink-0 transition-opacity" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5"/></svg>
                                        {{ $stl }}
                                    </button>
                                    @endforeach
                                </div>
                            </div>
                        </div>
                        <label class="grid gap-1 text-xs font-bold text-[#A1A1AA]">
                            Sort Order
                            <input name="sort_order" type="number" min="0" value="{{ old('sort_order', $plan->sort_order) }}"
                                   class="rounded border border-[#27213D] bg-[#090713] px-3 py-2 text-sm text-white">
                        </label>
                    </div>
                    <label class="grid gap-1 text-xs font-bold text-[#A1A1AA]">
                        Description
                        <textarea name="description" rows="2"
                                  class="rounded border border-[#27213D] bg-[#090713] px-3 py-2 text-sm text-white">{{ old('description', $plan->description) }}</textarea>
                    </label>
                    <div>
                        <button class="w-full sm:w-auto rounded-xl bg-[#8B5CF6] px-4 py-2 text-sm font-bold text-white hover:bg-[#7C3AED]">
                            Save {{ $plan->name }}
                        </button>
                    </div>
                </form>
            @endforeach
        </div>

    {{-- ===================== TAB 2: PLAN LIMITS ===================== --}}
    @elseif($activeTab === 'limits')
        <form method="POST" action="{{ route('admin.plans.limits.update') }}"
              class="rounded-2xl border border-[#27213D] bg-[#0F0D1A] p-5">
            @csrf @method('PATCH')
            <div class="overflow-x-auto">
                <table class="w-full min-w-[700px] text-sm">
                    <thead>
                        <tr class="border-b border-[#27213D]">
                            <th class="p-3 text-left text-xs font-bold uppercase text-[#71717A]">Limit</th>
                            @foreach($plans as $plan)
                                <th class="p-3 text-left text-xs font-bold uppercase text-[#71717A]">{{ $plan->name }}</th>
                            @endforeach
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($limitKeys as $limitKey => $limitLabel)
                            <tr class="border-b border-[#27213D]/60">
                                <td class="p-3 font-semibold text-white">{{ $limitLabel }}</td>
                                @foreach($plans as $plan)
                                    @php
                                        $existing = $limitsMatrix->get($plan->id)?->firstWhere('key', $limitKey);
                                        $isUnlimited = $existing?->is_unlimited ?? false;
                                        $value = $existing?->value ?? '';
                                        $visible = $existing?->visible_on_upgrade ?? true;
                                    @endphp
                                    <td class="p-3">
                                        <div class="space-y-1">
                                            <input type="number" min="0" name="limits[{{ $plan->id }}][{{ $limitKey }}][value]"
                                                   value="{{ $value }}"
                                                   class="w-28 rounded border border-[#27213D] bg-[#090713] px-2 py-1 text-sm text-white
                                                          {{ $isUnlimited ? 'opacity-40' : '' }}"
                                                   {{ $isUnlimited ? 'disabled' : '' }}>
                                            <label class="flex items-center gap-1 text-xs text-[#A1A1AA] cursor-pointer">
                                                <input type="checkbox" name="limits[{{ $plan->id }}][{{ $limitKey }}][unlimited]" value="1"
                                                       {{ $isUnlimited ? 'checked' : '' }}
                                                       onchange="this.closest('td').querySelector('input[type=number]').disabled=this.checked; this.closest('td').querySelector('input[type=number]').classList.toggle('opacity-40',this.checked)">
                                                Unlimited
                                            </label>
                                            <label class="flex items-center gap-1 text-xs text-[#71717A] cursor-pointer">
                                                <input type="checkbox" name="limits[{{ $plan->id }}][{{ $limitKey }}][visible]" value="1"
                                                       {{ $visible ? 'checked' : '' }}>
                                                Show on upgrade page
                                            </label>
                                        </div>
                                    </td>
                                @endforeach
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="mt-5">
                <button class="w-full sm:w-auto rounded-xl bg-[#8B5CF6] px-5 py-2 text-sm font-bold text-white hover:bg-[#7C3AED]">
                    Save Limits
                </button>
            </div>
        </form>

    {{-- ===================== TAB 3: FEATURE ACCESS ===================== --}}
    @elseif($activeTab === 'features')
        <form method="POST" action="{{ route('admin.plans.features.update') }}"
              class="rounded-2xl border border-[#27213D] bg-[#0F0D1A] p-5">
            @csrf @method('PATCH')
            <p class="mb-4 text-xs text-[#71717A]">
                <strong class="text-[#A1A1AA]">Enabled</strong> — feature is available to that plan.
                <strong class="text-[#A1A1AA]">Visible</strong> — feature shows on the upgrade page card.
            </p>
            <div class="overflow-x-auto">
                <table class="w-full min-w-[800px] text-sm">
                    <thead>
                        <tr class="border-b border-[#27213D]">
                            <th class="p-3 text-left text-xs font-bold uppercase text-[#71717A]">Feature</th>
                            <th class="p-3 text-xs font-bold uppercase text-[#71717A]">Category</th>
                            @foreach($plans as $plan)
                                <th class="p-3 text-xs font-bold uppercase text-[#71717A]">
                                    {{ $plan->name }}<br>
                                    <span class="font-normal normal-case text-[#4D4868]">enabled / visible</span>
                                </th>
                            @endforeach
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($features as $feature)
                            <tr class="border-b border-[#27213D]/60">
                                <td class="p-3 font-semibold text-white">
                                    {{ $feature->name }}
                                    <div class="text-xs text-[#71717A]">{{ $feature->key }}</div>
                                </td>
                                <td class="p-3 text-center text-xs text-[#71717A]">{{ $feature->category }}</td>
                                @foreach($plans as $plan)
                                    @php
                                        $access = $accessMatrix->get($plan->id)?->firstWhere('plan_feature_id', $feature->id);
                                        $enabled = $access?->enabled ?? false;
                                        $visible = $access?->visible_on_upgrade ?? true;
                                    @endphp
                                    <td class="p-3 text-center">
                                        <div class="flex flex-col items-center gap-2">
                                            <label class="flex items-center gap-1 text-xs text-[#22C55E] cursor-pointer">
                                                <input type="checkbox"
                                                       name="enabled[{{ $plan->id }}][{{ $feature->id }}]"
                                                       value="1"
                                                       {{ $enabled ? 'checked' : '' }}>
                                                On
                                            </label>
                                            <label class="flex items-center gap-1 text-xs text-[#71717A] cursor-pointer">
                                                <input type="checkbox"
                                                       name="visible[{{ $plan->id }}][{{ $feature->id }}]"
                                                       value="1"
                                                       {{ $visible ? 'checked' : '' }}>
                                                Visible
                                            </label>
                                        </div>
                                    </td>
                                @endforeach
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="mt-5">
                <button class="w-full sm:w-auto rounded-xl bg-[#8B5CF6] px-5 py-2 text-sm font-bold text-white hover:bg-[#7C3AED]">
                    Save Feature Access
                </button>
            </div>
        </form>

    {{-- ===================== TAB 4: TEMPLATE ACCESS ===================== --}}
    @elseif($activeTab === 'template-access')
        @php
            $templateFeatureKeys = ['template_marketplace', 'paid_templates', 'pro_templates', 'business_templates'];
            $templateFeatures = $features->whereIn('key', $templateFeatureKeys);
        @endphp
        <form method="POST" action="{{ route('admin.plans.template-access.update') }}"
              class="rounded-2xl border border-[#27213D] bg-[#0F0D1A] p-5 space-y-6">
            @csrf @method('PATCH')

            <div>
                <h3 class="text-base font-black text-white mb-1">Template Feature Access</h3>
                <p class="text-xs text-[#71717A] mb-4">Control which plans can access free/paid/included templates.</p>
                <div class="overflow-x-auto">
                    <table class="w-full min-w-[700px] text-sm">
                        <thead>
                            <tr class="border-b border-[#27213D]">
                                <th class="p-3 text-left text-xs font-bold uppercase text-[#71717A]">Feature</th>
                                @foreach($plans as $plan)
                                    <th class="p-3 text-center text-xs font-bold uppercase text-[#71717A]">{{ $plan->name }}</th>
                                @endforeach
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($templateFeatures as $feature)
                                <tr class="border-b border-[#27213D]/60">
                                    <td class="p-3 font-semibold text-white">
                                        {{ $feature->name }}
                                        <div class="text-xs text-[#71717A]">{{ $feature->key }}</div>
                                    </td>
                                    @foreach($plans as $plan)
                                        @php
                                            $access = $accessMatrix->get($plan->id)?->firstWhere('plan_feature_id', $feature->id);
                                            $enabled = $access?->enabled ?? false;
                                            $visible = $access?->visible_on_upgrade ?? true;
                                        @endphp
                                        <td class="p-3 text-center">
                                            <div class="flex flex-col items-center gap-2">
                                                <label class="flex items-center gap-1 text-xs text-[#22C55E] cursor-pointer">
                                                    <input type="checkbox"
                                                           name="enabled[{{ $plan->id }}][{{ $feature->id }}]"
                                                           value="1"
                                                           {{ $enabled ? 'checked' : '' }}>
                                                    Enabled
                                                </label>
                                                <label class="flex items-center gap-1 text-xs text-[#71717A] cursor-pointer">
                                                    <input type="checkbox"
                                                           name="visible[{{ $plan->id }}][{{ $feature->id }}]"
                                                           value="1"
                                                           {{ $visible ? 'checked' : '' }}>
                                                    Visible
                                                </label>
                                            </div>
                                        </td>
                                    @endforeach
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>

            <div>
                <h3 class="text-base font-black text-white mb-1">Free Templates Unlocked Per Month</h3>
                <p class="text-xs text-[#71717A] mb-4">How many free/included templates a user can unlock per month without paying.</p>
                <div class="grid gap-4 md:grid-cols-3">
                    @foreach($plans as $plan)
                        @php
                            $existing = $limitsMatrix->get($plan->id)?->firstWhere('key', 'free_templates_unlocked_per_month');
                            $isUnlimited = $existing?->is_unlimited ?? false;
                            $value = $existing?->value ?? '';
                        @endphp
                        <div class="rounded border border-[#27213D] bg-[#090713] p-4">
                            <div class="mb-2 text-sm font-bold text-white">{{ $plan->name }}</div>
                            <input type="number" min="0" name="limits[{{ $plan->id }}][free_templates_unlocked_per_month][value]"
                                   value="{{ $value }}"
                                   class="w-full rounded border border-[#27213D] bg-[#0F0D1A] px-3 py-2 text-sm text-white mb-2
                                          {{ $isUnlimited ? 'opacity-40' : '' }}"
                                   {{ $isUnlimited ? 'disabled' : '' }}>
                            <label class="flex items-center gap-2 text-xs text-[#A1A1AA] cursor-pointer">
                                <input type="checkbox" name="limits[{{ $plan->id }}][free_templates_unlocked_per_month][unlimited]" value="1"
                                       {{ $isUnlimited ? 'checked' : '' }}>
                                Unlimited
                            </label>
                        </div>
                    @endforeach
                </div>
            </div>

            <div>
                <h3 class="text-base font-black text-white mb-1">Paid Templates Purchase Limit</h3>
                <p class="text-xs text-[#71717A] mb-4">Maximum paid template purchases allowed. Unlimited by default for all plans.</p>
                <div class="grid gap-4 md:grid-cols-3">
                    @foreach($plans as $plan)
                        @php
                            $existing = $limitsMatrix->get($plan->id)?->firstWhere('key', 'paid_templates_purchase_limit');
                            $isUnlimited = $existing?->is_unlimited ?? true;
                            $value = $existing?->value ?? '';
                        @endphp
                        <div class="rounded border border-[#27213D] bg-[#090713] p-4">
                            <div class="mb-2 text-sm font-bold text-white">{{ $plan->name }}</div>
                            <input type="number" min="0" name="limits[{{ $plan->id }}][paid_templates_purchase_limit][value]"
                                   value="{{ $value }}"
                                   class="w-full rounded border border-[#27213D] bg-[#0F0D1A] px-3 py-2 text-sm text-white mb-2
                                          {{ $isUnlimited ? 'opacity-40' : '' }}"
                                   {{ $isUnlimited ? 'disabled' : '' }}>
                            <label class="flex items-center gap-2 text-xs text-[#A1A1AA] cursor-pointer">
                                <input type="checkbox" name="limits[{{ $plan->id }}][paid_templates_purchase_limit][unlimited]" value="1"
                                       {{ $isUnlimited ? 'checked' : '' }}>
                                Unlimited
                            </label>
                        </div>
                    @endforeach
                </div>
            </div>

            <div class="rounded border border-[#27213D] bg-[#090713] p-4 text-xs text-[#71717A]">
                <p class="font-bold text-[#A1A1AA] mb-1">Included Plan Rules (template level):</p>
                <ul class="list-disc list-inside space-y-0.5">
                    <li>Template <code>included_plan = free</code>: all plans can unlock without payment.</li>
                    <li>Template <code>included_plan = pro</code>: Pro and Business can unlock free; Free must buy.</li>
                    <li>Template <code>included_plan = business</code>: Business only unlocks free; Free &amp; Pro must buy.</li>
                    <li>If user already purchased a template, they always have access regardless of plan.</li>
                </ul>
            </div>

            <div>
                <button class="w-full sm:w-auto rounded-xl bg-[#8B5CF6] px-5 py-2 text-sm font-bold text-white hover:bg-[#7C3AED]">
                    Save Template Access
                </button>
            </div>
        </form>

    {{-- ===================== TAB 5: BROADCAST LIMITS ===================== --}}
    @elseif($activeTab === 'broadcast-limits')
        <form method="POST" action="{{ route('admin.plans.broadcast-limits.update') }}"
              class="rounded-2xl border border-[#27213D] bg-[#0F0D1A] p-5 space-y-5">
            @csrf @method('PATCH')
            <p class="text-xs text-[#71717A]">
                These limits control how many bot users can receive a broadcast per send, and how many broadcasts a user can create per month.
                Set a value or check Unlimited.
            </p>
            <div class="overflow-x-auto">
                <table class="w-full min-w-[600px] text-sm">
                    <thead>
                        <tr class="border-b border-[#27213D]">
                            <th class="p-3 text-left text-xs font-bold uppercase text-[#71717A]">Limit</th>
                            @foreach($plans as $plan)
                                <th class="p-3 text-left text-xs font-bold uppercase text-[#71717A]">{{ $plan->name }}</th>
                            @endforeach
                        </tr>
                    </thead>
                    <tbody>
                        @foreach([
                            'broadcast_recipients_per_send' => 'Recipients Per Send',
                            'broadcasts_per_month'          => 'Broadcasts Per Month',
                        ] as $limitKey => $limitLabel)
                            <tr class="border-b border-[#27213D]/60">
                                <td class="p-3 font-semibold text-white">{{ $limitLabel }}</td>
                                @foreach($plans as $plan)
                                    @php
                                        $existing = $limitsMatrix->get($plan->id)?->firstWhere('key', $limitKey);
                                        $isUnlimited = $existing?->is_unlimited ?? false;
                                        $value = $existing?->value ?? '';
                                        $visible = $existing?->visible_on_upgrade ?? true;
                                    @endphp
                                    <td class="p-3">
                                        <div class="space-y-1">
                                            <input type="number" min="0" name="limits[{{ $plan->id }}][{{ $limitKey }}][value]"
                                                   value="{{ $value }}"
                                                   class="w-32 rounded border border-[#27213D] bg-[#090713] px-2 py-1 text-sm text-white
                                                          {{ $isUnlimited ? 'opacity-40' : '' }}"
                                                   {{ $isUnlimited ? 'disabled' : '' }}>
                                            <label class="flex items-center gap-1 text-xs text-[#A1A1AA] cursor-pointer">
                                                <input type="checkbox" name="limits[{{ $plan->id }}][{{ $limitKey }}][unlimited]" value="1"
                                                       {{ $isUnlimited ? 'checked' : '' }}
                                                       onchange="this.closest('td').querySelector('input[type=number]').disabled=this.checked; this.closest('td').querySelector('input[type=number]').classList.toggle('opacity-40',this.checked)">
                                                Unlimited
                                            </label>
                                            <label class="flex items-center gap-1 text-xs text-[#71717A] cursor-pointer">
                                                <input type="checkbox" name="limits[{{ $plan->id }}][{{ $limitKey }}][visible]" value="1"
                                                       {{ $visible ? 'checked' : '' }}>
                                                Show on upgrade page
                                            </label>
                                        </div>
                                    </td>
                                @endforeach
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div>
                <button class="w-full sm:w-auto rounded-xl bg-[#8B5CF6] px-5 py-2 text-sm font-bold text-white hover:bg-[#7C3AED]">
                    Save Broadcast Limits
                </button>
            </div>
        </form>
    @endif

</x-admin-layout>
