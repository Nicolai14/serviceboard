@props(['server'])

@php
    $metric = $server->metrics->first();
@endphp

<div
    x-data="{
        status: '{{ $server->status }}',
        cpu: {{ $metric?->cpu_usage ?? 'null' }},
        mem: {{ $metric?->memory_percent ?? 'null' }},
        disk: {{ $metric?->disk_percent ?? 'null' }},
        load: {{ $metric?->load_average ?? 'null' }},
        uptime: {{ $metric?->uptime_seconds ?? 'null' }},
        lastSeen: '{{ $server->last_polled_at?->diffForHumans() ?? '—' }}',
        loading: false,

        async refresh() {
            try {
                const res = await fetch('{{ route('servers.status-json', $server) }}', {
                    headers: { 'X-Requested-With': 'XMLHttpRequest' }
                });
                const d = await res.json();
                this.status   = d.status;
                this.cpu      = d.cpu;
                this.mem      = d.memory_percent;
                this.disk     = d.disk_percent;
                this.load     = d.load;
                this.uptime   = d.uptime_seconds;
                this.lastSeen = d.last_polled_at ?? '—';
            } catch {}
        },

        formatUptime(s) {
            if (!s) return '—';
            const d = Math.floor(s / 86400);
            const h = Math.floor((s % 86400) / 3600);
            const m = Math.floor((s % 3600) / 60);
            if (d > 0) return d + 'd ' + h + 'h';
            if (h > 0) return h + 'h ' + m + 'm';
            return m + 'm';
        },

        barColor(v) {
            if (v === null) return 'bg-zinc-700';
            if (v >= 90) return 'bg-red-500';
            if (v >= 75) return 'bg-yellow-500';
            return 'bg-green-500';
        },
    }"
    x-init="setInterval(() => refresh(), 30000)"
    class="rounded-xl border border-zinc-800 bg-zinc-900 p-5 flex flex-col gap-4 hover:border-zinc-700 transition-colors"
>
    {{-- Header --}}
    <div class="flex items-start justify-between gap-3">
        <div class="min-w-0">
            <a href="{{ route('servers.show', $server) }}"
               class="text-sm font-semibold text-zinc-100 hover:text-blue-400 transition-colors block truncate">
                {{ $server->name }}
            </a>
            <p class="text-xs text-zinc-600 font-mono truncate">{{ $server->hostname }}</p>
        </div>
        <div class="flex items-center gap-2 shrink-0">
            <x-status-dot :status="$server->status" x-bind:class="{
                'bg-green-500':  status === 'online',
                'bg-red-500':    status === 'offline',
                'bg-yellow-500': status === 'maintenance',
                'bg-zinc-500':   status === 'unknown',
            }" />
            <span class="text-xs capitalize"
                  :class="{
                    'text-green-400':  status === 'online',
                    'text-red-400':    status === 'offline',
                    'text-yellow-400': status === 'maintenance',
                    'text-zinc-500':   status === 'unknown',
                  }"
                  x-text="status">{{ $server->status }}</span>
        </div>
    </div>

    {{-- Metric bars — shown only when data exists --}}
    <template x-if="cpu !== null">
        <div class="space-y-2.5">
            {{-- CPU --}}
            <div class="space-y-1">
                <div class="flex justify-between text-xs">
                    <span class="text-zinc-500">CPU</span>
                    <span :class="cpu >= 90 ? 'text-red-400' : cpu >= 75 ? 'text-yellow-400' : 'text-zinc-300'"
                          x-text="cpu + '%'"></span>
                </div>
                <div class="h-1.5 w-full rounded-full bg-zinc-800 overflow-hidden">
                    <div class="h-1.5 rounded-full transition-all duration-500"
                         :class="barColor(cpu)"
                         :style="`width:${Math.min(100,cpu)}%`"></div>
                </div>
            </div>
            {{-- RAM --}}
            <div class="space-y-1">
                <div class="flex justify-between text-xs">
                    <span class="text-zinc-500">RAM</span>
                    <span :class="mem >= 90 ? 'text-red-400' : mem >= 75 ? 'text-yellow-400' : 'text-zinc-300'"
                          x-text="mem + '%'"></span>
                </div>
                <div class="h-1.5 w-full rounded-full bg-zinc-800 overflow-hidden">
                    <div class="h-1.5 rounded-full transition-all duration-500"
                         :class="barColor(mem)"
                         :style="`width:${Math.min(100,mem)}%`"></div>
                </div>
            </div>
            {{-- Disk --}}
            <div class="space-y-1">
                <div class="flex justify-between text-xs">
                    <span class="text-zinc-500">Disk</span>
                    <span :class="disk >= 85 ? 'text-red-400' : disk >= 70 ? 'text-yellow-400' : 'text-zinc-300'"
                          x-text="disk + '%'"></span>
                </div>
                <div class="h-1.5 w-full rounded-full bg-zinc-800 overflow-hidden">
                    <div class="h-1.5 rounded-full transition-all duration-500"
                         :class="barColor(disk)"
                         :style="`width:${Math.min(100,disk)}%`"></div>
                </div>
            </div>
        </div>
    </template>

    <template x-if="cpu === null">
        <p class="text-xs text-zinc-700 italic">Noch keine Metriken vorhanden</p>
    </template>

    {{-- Footer --}}
    <div class="flex items-center justify-between pt-1 border-t border-zinc-800/60">
        <div class="flex items-center gap-3 text-xs text-zinc-600">
            <template x-if="uptime !== null">
                <span>Uptime: <span class="text-zinc-400" x-text="formatUptime(uptime)"></span></span>
            </template>
            <template x-if="load !== null">
                <span>Load: <span class="text-zinc-400" x-text="load"></span></span>
            </template>
        </div>
        <a href="{{ route('servers.show', $server) }}"
           class="text-zinc-700 hover:text-zinc-400 transition-colors">
            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 4.5l7.5 7.5-7.5 7.5"/>
            </svg>
        </a>
    </div>
</div>
