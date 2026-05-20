@props([
    'label',
    'value'  => 0,       // numeric percent (0–100)
    'unit'   => '%',
    'size'   => 'md',    // sm | md
])

@php
    $v     = (float) $value;
    $color = match (true) {
        $v >= 90 => 'bg-red-500',
        $v >= 75 => 'bg-yellow-500',
        $v >= 50 => 'bg-blue-500',
        default  => 'bg-green-500',
    };
    $textColor = match (true) {
        $v >= 90 => 'text-red-400',
        $v >= 75 => 'text-yellow-400',
        default  => 'text-zinc-300',
    };
    $height = $size === 'sm' ? 'h-1' : 'h-1.5';
@endphp

<div class="space-y-1">
    <div class="flex items-center justify-between">
        <span class="text-xs text-zinc-500">{{ $label }}</span>
        <span class="text-xs font-medium {{ $textColor }}">
            {{ number_format($v, 1) }}{{ $unit }}
        </span>
    </div>
    <div class="w-full rounded-full bg-zinc-800 {{ $height }} overflow-hidden">
        <div class="{{ $color }} {{ $height }} rounded-full transition-all duration-500"
             style="width: {{ min(100, $v) }}%"></div>
    </div>
</div>
