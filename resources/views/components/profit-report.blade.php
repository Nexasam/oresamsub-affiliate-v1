@props(['scope', 'summary', 'services', 'transactions'])
<div class="space-y-5" x-data="{ selected: null, money(value) { return `₦${Number(value || 0).toLocaleString('en-NG', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}` }, discountLabel(discount) { if (!discount || discount.amount === null) return 'Discount: not applicable'; const label = Number(discount.amount) >= 0 ? 'Discount' : 'Markup'; return `${label}: ${this.money(Math.abs(Number(discount.amount)))} (${Math.abs(Number(discount.percent)).toFixed(2)}%)`; } }">
<form class="grid gap-3 rounded-2xl border bg-white p-4 md:grid-cols-4"><input type="date" name="date_from" value="{{ request('date_from') }}" class="rounded-xl border-slate-200"><input type="date" name="date_to" value="{{ request('date_to') }}" class="rounded-xl border-slate-200"><select name="service" class="rounded-xl border-slate-200"><option value="">All services</option>@foreach(['data','airtime','cable_subscription','utility_bills'] as $service)<option value="{{ $service }}" @selected(request('service')===$service)>{{ str($service)->replace('_',' ')->title() }}</option>@endforeach</select><div class="flex gap-2"><button class="rounded-xl bg-slate-900 px-4 py-2 text-white">Filter</button><a href="{{ url()->current().'/export?'.http_build_query(request()->query()) }}" class="rounded-xl border px-4 py-2">Export CSV</a></div></form>
<div class="grid gap-3 md:grid-cols-4">@foreach([['Successful sales',$summary['transactions']],[$scope==='parent'?'Affiliate charges':'Customer sales','₦'.number_format($summary['sales'],2)],[$scope==='parent'?'Provider cost':'Acquisition cost','₦'.number_format($summary['cost'],2)],['Realised profit','₦'.number_format($summary['profit'],2)]] as [$label,$value])<div class="rounded-2xl border bg-white p-4"><p class="text-xs text-slate-500">{{ $label }}</p><p class="mt-1 text-2xl font-bold">{{ $value }}</p></div>@endforeach</div>
<div class="grid gap-5 lg:grid-cols-3"><section class="rounded-2xl border bg-white p-5"><h2 class="font-semibold">Profit by service</h2><div class="mt-3 space-y-3">@forelse($services as $service)<div class="flex justify-between border-b pb-2"><span>{{ str($service->transaction_category)->replace('_',' ')->title() }} <small class="text-slate-400">({{ $service->transactions }})</small></span><strong>₦{{ number_format($service->profit,2) }}</strong></div>@empty<p class="text-sm text-slate-500">No realised profit in this period.</p>@endforelse</div></section>
<section class="overflow-hidden rounded-2xl border bg-white lg:col-span-2">
    <div class="border-b p-5"><h2 class="font-semibold">Realised profit transactions</h2><p class="mt-1 text-xs text-slate-500">Open a row to review the complete financial breakdown.</p></div>
    <div class="overflow-x-auto">
        <table class="w-full min-w-[720px] text-left text-sm">
            <thead class="bg-slate-50 text-xs uppercase text-slate-500"><tr><th class="p-3">Transaction</th><th>{{ $scope === 'parent' ? 'Affiliate / customer' : 'Customer' }}</th><th>Service</th><th>Face value</th><th>Realised profit</th><th class="pr-3 text-right">Action</th></tr></thead>
            <tbody class="divide-y">
            @forelse($transactions as $transaction)
                @php
                    $face = is_numeric($transaction->face_value_snapshot) ? (float) $transaction->face_value_snapshot : null;
                    $customerPaid = (float) $transaction->customer_price_snapshot;
                    $affiliateCharge = (float) $transaction->affiliate_cost_snapshot;
                    $providerCharge = (float) $transaction->provider_cost_snapshot;
                    $discount = static function (?float $faceValue, float $charged): array {
                        if ($faceValue === null || $faceValue <= 0) return ['amount' => null, 'percent' => null];
                        $amount = $faceValue - $charged;
                        return ['amount' => $amount, 'percent' => ($amount / $faceValue) * 100];
                    };
                    $customerDiscount = $discount($face, $customerPaid);
                    $affiliateDiscount = $discount($face, $affiliateCharge);
                    $providerDiscount = $discount($face, $providerCharge);
                    $customerName = trim(($transaction->user?->first_name ?? '').' '.($transaction->user?->last_name ?? '')) ?: ($transaction->user?->email ?? 'Customer');
                    $details = [
                        'reference' => $transaction->txn_reference,
                        'date' => \Carbon\Carbon::parse($transaction->created_at)->format('d M Y, H:i'),
                        'affiliate' => $transaction->affiliate?->name ?: '—',
                        'customer' => $customerName,
                        'customer_email' => $transaction->user?->email ?: '—',
                        'service' => str($transaction->transaction_category)->headline()->toString(),
                        'provider_reference' => $transaction->provider_reference ?: '—',
                        'face_value' => $face,
                        'customer_paid' => $customerPaid,
                        'affiliate_charge' => $affiliateCharge,
                        'provider_charge' => $providerCharge,
                        'customer_discount' => $customerDiscount,
                        'affiliate_discount' => $affiliateDiscount,
                        'provider_discount' => $providerDiscount,
                        'affiliate_profit' => (float) $transaction->affiliate_profit_snapshot,
                        'parent_profit' => (float) $transaction->parent_profit_snapshot,
                    ];
                @endphp
                <tr class="hover:bg-slate-50">
                    <td class="p-3"><p class="font-semibold text-slate-800">{{ str($transaction->txn_reference)->limit(28) }}</p><p class="mt-1 text-xs text-slate-500">{{ \Carbon\Carbon::parse($transaction->created_at)->format('d M Y, H:i') }}</p></td>
                    <td><p class="font-medium">{{ $scope === 'parent' ? ($transaction->affiliate?->name ?: '—') : $customerName }}</p><p class="mt-1 text-xs text-slate-500">{{ $scope === 'parent' ? $customerName : ($transaction->user?->email ?: '—') }}</p></td>
                    <td>{{ str($transaction->transaction_category)->headline() }}</td>
                    <td class="font-medium">{{ $face !== null ? '₦'.number_format($face, 2) : 'Not recorded' }}</td>
                    <td class="font-semibold text-emerald-700">₦{{ number_format($scope === 'parent' ? $transaction->parent_profit_snapshot : $transaction->affiliate_profit_snapshot, 2) }}</td>
                    <td class="pr-3 text-right"><button type="button" class="rounded-lg border border-blue-200 bg-blue-50 px-3 py-2 text-xs font-semibold text-blue-700 hover:bg-blue-100" @click="selected = {{ Js::from($details) }}">View details</button></td>
                </tr>
            @empty
                <tr><td colspan="6" class="p-8 text-center text-slate-500">No successful snapshot transactions found.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
    <div class="p-4">{{ $transactions->links() }}</div>
</section></div>

<div x-cloak x-show="selected" class="fixed inset-0 z-[120]" @keydown.escape.window="selected = null">
    <div class="absolute inset-0 bg-slate-950/50" @click="selected = null"></div>
    <aside x-show="selected" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="translate-x-full" x-transition:enter-end="translate-x-0" x-transition:leave="transition ease-in duration-150" x-transition:leave-start="translate-x-0" x-transition:leave-end="translate-x-full" class="absolute inset-y-0 right-0 w-full max-w-xl overflow-y-auto bg-white shadow-2xl">
        <div class="sticky top-0 flex items-start justify-between border-b bg-white p-5"><div><p class="text-xs font-semibold uppercase tracking-wide text-blue-600">Transaction details</p><h2 class="mt-1 text-lg font-bold">Financial breakdown</h2><p class="mt-1 break-all text-xs text-slate-500" x-text="selected?.reference"></p></div><button type="button" class="rounded-lg border px-3 py-2 text-sm font-semibold" @click="selected = null">Close</button></div>
        <div class="space-y-5 p-5">
            <div class="grid gap-3 rounded-2xl bg-slate-50 p-4 sm:grid-cols-2"><div><p class="text-xs text-slate-500">Date</p><p class="font-semibold" x-text="selected?.date"></p></div><div><p class="text-xs text-slate-500">Service</p><p class="font-semibold" x-text="selected?.service"></p></div>@if($scope === 'parent')<div><p class="text-xs text-slate-500">Affiliate</p><p class="font-semibold" x-text="selected?.affiliate"></p></div>@endif<div><p class="text-xs text-slate-500">Customer</p><p class="font-semibold" x-text="selected?.customer"></p><p class="text-xs text-slate-500" x-text="selected?.customer_email"></p></div></div>
            <div><h3 class="font-semibold">Money flow</h3><div class="mt-3 divide-y rounded-2xl border">
                <div class="grid grid-cols-2 gap-3 p-4"><span class="text-sm text-slate-500">Face value purchased</span><strong class="text-right" x-text="selected?.face_value === null ? 'Not recorded' : money(selected.face_value)"></strong></div>
                <div class="grid grid-cols-2 gap-3 p-4"><span><strong class="block text-sm">Customer paid</strong><small class="text-slate-500" x-text="discountLabel(selected?.customer_discount)"></small></span><strong class="text-right" x-text="money(selected?.customer_paid)"></strong></div>
                <div class="grid grid-cols-2 gap-3 p-4"><span><strong class="block text-sm">Affiliate acquisition charge</strong><small class="text-slate-500" x-text="discountLabel(selected?.affiliate_discount)"></small></span><strong class="text-right" x-text="money(selected?.affiliate_charge)"></strong></div>
                @if($scope === 'parent')<div class="grid grid-cols-2 gap-3 p-4"><span><strong class="block text-sm">Actual provider charge</strong><small class="text-slate-500" x-text="discountLabel(selected?.provider_discount)"></small></span><strong class="text-right" x-text="money(selected?.provider_charge)"></strong></div>@endif
            </div></div>
            <div class="grid gap-3 sm:grid-cols-2"><div class="rounded-2xl bg-emerald-50 p-4"><p class="text-xs text-emerald-700">Affiliate profit</p><p class="mt-1 text-2xl font-bold text-emerald-800" x-text="money(selected?.affiliate_profit)"></p></div>@if($scope === 'parent')<div class="rounded-2xl bg-blue-50 p-4"><p class="text-xs text-blue-700">Parent profit</p><p class="mt-1 text-2xl font-bold text-blue-800" x-text="money(selected?.parent_profit)"></p></div>@endif</div>
            <div><p class="text-xs text-slate-500">Provider reference</p><p class="mt-1 break-all font-medium" x-text="selected?.provider_reference"></p></div>
        </div>
    </aside>
</div>
</div>
