@props([
    'bank',
    'number',
    'name',
    'charges',
    'bg' => 'blue',
])

@php
    $colors = match ($bg) {
        'yellow' => 'from-yellow-500 to-amber-600',
        'green' => 'from-green-500 to-emerald-700',
        'red' => 'from-red-500 to-rose-700',
        default => 'from-blue-500 to-indigo-700',
    };
@endphp

<div {{ $attributes->class("rounded-2xl bg-gradient-to-br {$colors} p-5 text-white shadow-md") }}>
    <div class="flex items-start justify-between gap-3">
        <div>
            <p class="text-xs font-medium uppercase tracking-wider text-white/75">{{ $bank }}</p>
            <p class="mt-3 font-mono text-lg font-semibold tracking-wider">{{ $number }}</p>
        </div>
        <svg class="h-7 w-7 text-white/80" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M2.25 8.25h19.5m-18-3h16.5a1.5 1.5 0 0 1 1.5 1.5v10.5a1.5 1.5 0 0 1-1.5 1.5H3.75a1.5 1.5 0 0 1-1.5-1.5V6.75a1.5 1.5 0 0 1 1.5-1.5Z" />
        </svg>
    </div>

    <p class="mt-4 text-sm font-medium">{{ $name }}</p>
    <p class="mt-1 text-xs text-white/75">{{ $charges }}</p>
</div>
