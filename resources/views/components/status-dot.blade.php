@props(['status' => 'unknown', 'pulse' => true])

@php
    $dot = match ($status) {
        'online'      => 'bg-green-500',
        'offline'     => 'bg-red-500',
        'maintenance' => 'bg-yellow-500',
        default       => 'bg-zinc-500',
    };
    $glow = $status === 'online' && $pulse
        ? 'shadow-[0_0_6px_theme(colors.green.500)]'
        : '';
    $animate = $status === 'online' && $pulse ? 'animate-pulse' : '';
@endphp

<span class="relative inline-flex h-2.5 w-2.5">
    @if ($status === 'online' && $pulse)
        <span class="absolute inline-flex h-full w-full animate-ping rounded-full bg-green-400 opacity-40"></span>
    @endif
    <span class="relative inline-flex h-2.5 w-2.5 rounded-full {{ $dot }} {{ $glow }}"></span>
</span>
