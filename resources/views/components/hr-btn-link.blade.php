@props([
    'variant' => 'primary',
])

@php
    $classes = match ($variant) {
        'approve' => 'inline-flex items-center justify-center gap-1.5 px-3 py-1.5 rounded-lg bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-semibold transition-colors',
        'decline' => 'inline-flex items-center justify-center gap-1.5 px-3 py-1.5 rounded-lg border border-red-200 bg-white hover:bg-red-50 text-red-700 text-xs font-semibold transition-colors',
        'danger' => 'inline-flex items-center justify-center gap-1.5 px-3 py-1.5 rounded-lg bg-red-600 hover:bg-red-700 text-white text-xs font-semibold transition-colors',
        'view' => 'inline-flex items-center justify-center gap-1.5 px-3 py-1.5 rounded-lg bg-blue-600 hover:bg-blue-700 text-white text-xs font-semibold transition-colors',
        'secondary' => 'inline-flex items-center justify-center gap-1.5 px-3 py-1.5 rounded-lg border border-slate-200 bg-white hover:bg-slate-50 text-slate-700 text-xs font-semibold transition-colors',
        default => 'inline-flex items-center justify-center gap-1.5 px-3 py-1.5 rounded-lg bg-[#FA9800] hover:bg-[#e8870a] text-white text-xs font-semibold transition-colors',
    };
@endphp

<a {{ $attributes->merge(['class' => $classes]) }}>
    {{ $slot }}
</a>
