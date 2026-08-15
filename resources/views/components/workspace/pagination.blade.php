@props(['paginator'])
@if($paginator->hasPages())
    <div {{ $attributes->class(['border-t border-slate-200 px-4 py-3 dark:border-slate-700']) }}>{{ $paginator->onEachSide(1)->links() }}</div>
@endif
