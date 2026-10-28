@props(['color' => 'slate'])
@php
    $classes = [
        'green' => 'bg-green-100 text-green-800',
        'red' => 'bg-red-100 text-red-800',
        'amber' => 'bg-amber-100 text-amber-800',
        'orange' => 'bg-orange-100 text-orange-800',
        'blue' => 'bg-brand-100 text-brand-800',
        'slate' => 'bg-slate-100 text-slate-700',
    ][$color] ?? 'bg-slate-100 text-slate-700';
@endphp
<span {{ $attributes->merge(['class' => 'badge '.$classes]) }}>{{ $slot }}</span>
