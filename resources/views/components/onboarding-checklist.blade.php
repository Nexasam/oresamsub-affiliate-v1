<div class="space-y-5">
    <section class="rounded-2xl bg-slate-950 p-6 text-white shadow-sm">
        <div class="flex items-end justify-between"><div><p class="text-xs font-bold uppercase tracking-[.18em] text-blue-300">Onboarding progress</p><h2 class="mt-2 text-2xl font-bold">{{ $checklist['completed'] }} of {{ $checklist['total'] }} steps complete</h2></div><span class="text-3xl font-black">{{ $checklist['percentage'] }}%</span></div>
        <div class="mt-5 h-2 overflow-hidden rounded-full bg-white/15"><div class="h-full rounded-full bg-blue-400" style="width: {{ $checklist['percentage'] }}%"></div></div>
    </section>
    <div class="grid gap-3">
        @foreach($checklist['steps'] as $step)
        <a href="{{ $step['url'] }}" class="flex items-center gap-4 rounded-2xl border bg-white p-5 shadow-sm transition hover:border-blue-300">
            <span class="grid h-10 w-10 shrink-0 place-items-center rounded-full {{ $step['complete'] ? 'bg-emerald-100 text-emerald-700' : 'bg-amber-100 text-amber-700' }}">{{ $step['complete'] ? '✓' : ($loop->iteration) }}</span>
            <span class="min-w-0 flex-1"><strong class="block">{{ $step['name'] }}</strong><span class="text-sm text-slate-500">{{ $step['description'] }}</span></span><span class="text-slate-400">→</span>
        </a>
        @endforeach
    </div>
</div>
