@props(['type' => 'neutral'])
@php($styles = match($type) {
    'success' => 'bg-emerald-100 text-emerald-800 dark:bg-emerald-950 dark:text-emerald-200',
    'failed', 'danger' => 'bg-rose-100 text-rose-800 dark:bg-rose-950 dark:text-rose-200',
    'pending', 'warning' => 'bg-amber-100 text-amber-800 dark:bg-amber-950 dark:text-amber-200',
    'review' => 'bg-violet-100 text-violet-800 dark:bg-violet-950 dark:text-violet-200',
    default => 'bg-slate-100 text-slate-700 dark:bg-slate-700 dark:text-slate-200',
})
<span {{ $attributes->class("inline-flex whitespace-nowrap rounded-full px-2.5 py-1 text-[11px] font-semibold {$styles}") }}>{{ $slot }}</span>
