@props(['title', 'description' => null])
<div {{ $attributes->class(['flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between']) }}>
    <div class="min-w-0">
        <h2 class="text-xl font-bold tracking-tight text-slate-950 dark:text-white sm:text-2xl">{{ $title }}</h2>
        @if($description)<p class="mt-1 max-w-3xl text-sm leading-6 text-slate-500 dark:text-slate-400">{{ $description }}</p>@endif
    </div>
    @if($slot->isNotEmpty())<div class="flex shrink-0 flex-wrap items-center gap-2">{{ $slot }}</div>@endif
</div>
