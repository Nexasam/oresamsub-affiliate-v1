@extends('platform-admin.layouts.app')
@section('title', 'Maintenance')
@section('heading', 'Maintenance')
@section('content')
<div class="space-y-5">
    @if(session('success'))<div class="rounded-xl border border-emerald-200 bg-emerald-50 p-3 text-sm text-emerald-800">{{ session('success') }}</div>@endif
    @if(session('failure'))<div class="rounded-xl border border-red-200 bg-red-50 p-3 text-sm text-red-800">{{ session('failure') }}</div>@endif
    @if(session('command_output'))<section class="rounded-2xl border bg-white p-5 shadow-sm"><h2 class="font-semibold">Command output</h2><pre class="mt-3 max-h-80 overflow-auto rounded-xl bg-slate-950 p-4 text-xs leading-5 text-slate-100">{{ session('command_output') }}</pre></section>@endif

    <section class="rounded-2xl border bg-white p-5 shadow-sm">
        <h2 class="font-semibold">Server actions</h2>
        <div class="mt-4 flex flex-wrap gap-3">
            <form method="POST" action="{{ route('platform-admin.maintenance.git-pull') }}">@csrf<button class="rounded-xl bg-slate-950 px-4 py-2.5 text-sm font-semibold text-white" onclick="return confirm('Run git pull origin main on this server?')">Git pull origin main</button></form>
            <form method="POST" action="{{ route('platform-admin.maintenance.optimize-clear') }}">@csrf<button class="rounded-xl border px-4 py-2.5 text-sm font-semibold">php artisan optimize:clear</button></form>
            <form method="POST" action="{{ route('platform-admin.maintenance.clear-logs') }}">@csrf<button class="rounded-xl border border-red-200 px-4 py-2.5 text-sm font-semibold text-red-700" onclick="return confirm('Clear all Laravel log files? This cannot be undone.')">Clear Laravel logs</button></form>
        </div>
    </section>

    <section class="rounded-2xl border bg-white p-5 shadow-sm">
        <div class="flex flex-wrap items-center justify-between gap-3"><h2 class="font-semibold">Laravel log tail</h2><a href="{{ route('platform-admin.maintenance.index') }}" class="rounded-lg border px-3 py-2 text-sm font-semibold">Refresh</a></div>
        <pre class="mt-3 max-h-[650px] overflow-auto rounded-xl bg-slate-950 p-4 text-xs leading-5 text-slate-100">{{ $log }}</pre>
    </section>
</div>
@endsection
