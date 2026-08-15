@props(['active'])

@php
$classes = ($active ?? false)
            ? 'relative flex items-center gap-3 px-4 py-3 rounded-r-xl bg-gradient-to-r from-primary/10 to-transparent border-l-2 border-primary text-primary font-medium group transition-all duration-300'
            : 'relative flex items-center gap-3 px-4 py-3 rounded-r-xl border-l-2 border-transparent text-slate-500 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white hover:bg-slate-100/50 dark:hover:bg-slate-800/50 font-medium group hover:translate-x-1 transition-all duration-300';

$iconClasses = ($active ?? false)
            ? 'w-5 h-5 text-primary drop-shadow-[0_0_8px_rgba(21,87,255,0.5)] transition-all duration-300'
            : 'w-5 h-5 text-slate-400 group-hover:text-slate-600 dark:group-hover:text-slate-300 transition-all duration-300';
@endphp

<a {{ $attributes->merge(['class' => $classes]) }}>
    @if(isset($icon))
        <svg class="{{ $iconClasses }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            {{ $icon }}
        </svg>
    @endif
    <span class="tracking-wide">{{ $slot }}</span>
</a>
