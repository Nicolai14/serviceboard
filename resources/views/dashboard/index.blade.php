<x-layouts.app title="{{ isset($viewing_public_user) ? $viewing_public_user->name . ' — Public Dashboard' : 'Übersicht' }}">

@isset($viewing_public_user)
    <div class="mb-6 flex items-center gap-3 rounded-xl border border-purple-700/40 bg-purple-900/15 px-4 py-3">
        <svg class="h-5 w-5 text-purple-400 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
            <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
            <path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
        </svg>
        <div class="min-w-0 flex-1">
            <p class="text-sm font-semibold text-purple-200">Read-only Ansicht</p>
            <p class="text-xs text-purple-400/70">Du siehst das öffentliche Dashboard von <strong>{{ $viewing_public_user->name }}</strong></p>
        </div>
        <a href="{{ route('dashboard') }}" class="text-xs font-medium text-purple-300 hover:text-purple-200 transition-colors">
            ← Zurück zu meinem Dashboard
        </a>
    </div>
@endisset

{{-- Hero stat strip --}}
<div class="grid grid-cols-2 xl:grid-cols-4 gap-4 mb-8">

    {{-- Servers Online --}}
    <div class="relative rounded-2xl border border-zinc-800 bg-zinc-900 p-4 sm:p-5 overflow-hidden group hover:border-zinc-700 transition-colors">
        <div class="absolute inset-0 bg-gradient-to-br from-green-500/5 to-transparent pointer-events-none"></div>
        <div class="flex items-start justify-between mb-4">
            <p class="text-xs font-semibold uppercase tracking-wider text-zinc-500">Server Online</p>
            <div class="flex h-8 w-8 items-center justify-center rounded-lg bg-green-500/10 border border-green-500/20">
                <svg class="h-4 w-4 text-green-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M5 12h14M5 12a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v4a2 2 0 01-2 2M5 12a2 2 0 00-2 2v4a2 2 0 002 2h14a2 2 0 002-2v-4a2 2 0 00-2-2m-2-4h.01M17 16h.01"/>
                </svg>
            </div>
        </div>
        <div class="flex items-end justify-between gap-2">
            <div class="flex items-baseline gap-1.5">
                <span class="text-4xl font-bold text-white">{{ $server_stats['online'] }}</span>
                <span class="text-lg font-medium text-zinc-600">/ {{ $server_stats['total'] }}</span>
            </div>
            @if ($server_stats['offline'] > 0)
                <a href="{{ route('servers.index', ['status' => 'offline']) }}"
                   class="mb-0.5 rounded-full bg-red-500/10 border border-red-500/20 px-2.5 py-0.5 text-xs font-medium text-red-400 hover:bg-red-500/20 transition-colors whitespace-nowrap">
                    {{ $server_stats['offline'] }} offline
                </a>
            @else
                <span class="mb-0.5 rounded-full bg-green-500/10 border border-green-500/20 px-2.5 py-0.5 text-xs font-medium text-green-400 whitespace-nowrap">
                    Alle online
                </span>
            @endif
        </div>
    </div>

    {{-- Running Containers --}}
    <div class="relative rounded-2xl border border-zinc-800 bg-zinc-900 p-4 sm:p-5 overflow-hidden group hover:border-zinc-700 transition-colors">
        <div class="absolute inset-0 bg-gradient-to-br from-blue-500/5 to-transparent pointer-events-none"></div>
        <div class="flex items-start justify-between mb-4">
            <p class="text-xs font-semibold uppercase tracking-wider text-zinc-500">Container Running</p>
            <div class="flex h-8 w-8 items-center justify-center rounded-lg bg-blue-500/10 border border-blue-500/20">
                <svg class="h-4 w-4 text-blue-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M21 7.5l-9-5.25L3 7.5m18 0l-9 5.25m9-5.25v9l-9 5.25M3 7.5l9 5.25M3 7.5v9l9 5.25m0-9v9"/>
                </svg>
            </div>
        </div>
        <div class="flex items-end justify-between gap-2">
            <div class="flex items-baseline gap-1.5">
                <span class="text-4xl font-bold text-white">{{ $docker_stats['running'] }}</span>
                <span class="text-lg font-medium text-zinc-600">/ {{ $docker_stats['total'] }}</span>
            </div>
            @if ($docker_stats['stopped'] > 0)
                <span class="mb-0.5 rounded-full bg-zinc-800 border border-zinc-700 px-2.5 py-0.5 text-xs font-medium text-zinc-400 whitespace-nowrap">
                    {{ $docker_stats['stopped'] }} stopped
                </span>
            @else
                <span class="mb-0.5 rounded-full bg-blue-500/10 border border-blue-500/20 px-2.5 py-0.5 text-xs font-medium text-blue-400 whitespace-nowrap">
                    Alle laufen
                </span>
            @endif
        </div>
    </div>

    {{-- Alerts --}}
    <div class="relative rounded-2xl border border-zinc-800 bg-zinc-900 p-4 sm:p-5 overflow-hidden group hover:border-zinc-700 transition-colors">
        @if ($unread_alerts > 0)
            <div class="absolute inset-0 bg-gradient-to-br from-amber-500/5 to-transparent pointer-events-none"></div>
        @endif
        <div class="flex items-start justify-between mb-4">
            <p class="text-xs font-semibold uppercase tracking-wider text-zinc-500">Offene Alerts</p>
            <div class="flex h-8 w-8 items-center justify-center rounded-lg {{ $unread_alerts > 0 ? 'bg-amber-500/10 border border-amber-500/20' : 'bg-zinc-800 border border-zinc-700' }}">
                <svg class="h-4 w-4 {{ $unread_alerts > 0 ? 'text-amber-400' : 'text-zinc-500' }}" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M14.857 17.082a23.848 23.848 0 005.454-1.31A8.967 8.967 0 0118 9.75v-.7V9A6 6 0 006 9v.75a8.967 8.967 0 01-2.312 6.022c1.733.64 3.56 1.085 5.455 1.31m5.714 0a24.255 24.255 0 01-5.714 0m5.714 0a3 3 0 11-5.714 0"/>
                </svg>
            </div>
        </div>
        <div class="flex items-end justify-between gap-2">
            <span class="text-4xl font-bold {{ $unread_alerts > 0 ? 'text-amber-400' : 'text-white' }}">{{ $unread_alerts }}</span>
            <a href="{{ route('alerts.index') }}"
               class="mb-0.5 text-xs {{ $unread_alerts > 0 ? 'text-amber-400 hover:text-amber-300' : 'text-zinc-600 hover:text-zinc-400' }} transition-colors">
                Alle anzeigen →
            </a>
        </div>
    </div>

    {{-- DNS Records --}}
    <div class="relative rounded-2xl border border-zinc-800 bg-zinc-900 p-4 sm:p-5 overflow-hidden group hover:border-zinc-700 transition-colors">
        <div class="absolute inset-0 bg-gradient-to-br from-orange-500/5 to-transparent pointer-events-none"></div>
        <div class="flex items-start justify-between mb-4">
            <p class="text-xs font-semibold uppercase tracking-wider text-zinc-500">DNS Records</p>
            <div class="flex h-8 w-8 items-center justify-center rounded-lg bg-orange-500/10 border border-orange-500/20">
                <svg class="h-4 w-4 text-orange-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M8.288 15.038a5.25 5.25 0 017.424 0M5.106 11.856c3.807-3.808 9.98-3.808 13.788 0M1.924 8.674c5.565-5.565 14.587-5.565 20.152 0M12.53 18.22l-.53.53-.53-.53a.75.75 0 011.06 0z"/>
                </svg>
            </div>
        </div>
        <div class="flex items-end justify-between gap-2">
            <span class="text-4xl font-bold text-white">{{ number_format($cloudflare_stats['dns_total']) }}</span>
            <a href="{{ route('cloudflare.index') }}"
               class="mb-0.5 rounded-full bg-orange-500/10 border border-orange-500/20 px-2.5 py-0.5 text-xs font-medium text-orange-400 hover:bg-orange-500/20 transition-colors whitespace-nowrap">
                {{ $cloudflare_stats['zones'] }} Zonen
            </a>
        </div>
    </div>
</div>

{{-- Aggregate Resource Bar --}}
@if (!empty($resource_stats['cpu_avg']) || !empty($resource_stats['ram_used_gb']))
    @php
        $cpu = $resource_stats['cpu_avg'] ?? 0;
        $ramPct = $resource_stats['ram_pct'] ?? 0;
        $cpuBar = $cpu >= 80 ? 'bg-red-500' : ($cpu >= 50 ? 'bg-yellow-500' : 'bg-green-500');
        $ramBar = $ramPct >= 85 ? 'bg-red-500' : ($ramPct >= 60 ? 'bg-yellow-500' : 'bg-green-500');
    @endphp
    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-8">

        {{-- Aggregate CPU --}}
        <div class="rounded-2xl border border-zinc-800 bg-zinc-900 p-5">
            <div class="flex items-center justify-between mb-3">
                <p class="text-xs font-semibold uppercase tracking-wider text-zinc-500">Ø CPU (alle Server)</p>
                <span class="text-xs text-zinc-600">{{ $resource_stats['server_count'] }} Server</span>
            </div>
            <div class="flex items-baseline gap-2 mb-3">
                <span class="text-4xl font-bold text-white">{{ $cpu }}<span class="text-2xl text-zinc-500">%</span></span>
            </div>
            <div class="h-2 w-full rounded-full bg-zinc-800 overflow-hidden">
                <div class="h-2 rounded-full {{ $cpuBar }} transition-all duration-500"
                     style="width: {{ min(100, $cpu) }}%"></div>
            </div>
        </div>

        {{-- Aggregate RAM --}}
        <div class="rounded-2xl border border-zinc-800 bg-zinc-900 p-5">
            <div class="flex items-center justify-between mb-3">
                <p class="text-xs font-semibold uppercase tracking-wider text-zinc-500">Gesamt RAM</p>
                <span class="text-xs text-zinc-600">{{ $ramPct }}% in Use</span>
            </div>
            <div class="flex items-baseline gap-2 mb-3">
                <span class="text-4xl font-bold text-white">{{ $resource_stats['ram_used_gb'] }}<span class="text-2xl text-zinc-500"> / {{ $resource_stats['ram_total_gb'] }} GB</span></span>
            </div>
            <div class="h-2 w-full rounded-full bg-zinc-800 overflow-hidden">
                <div class="h-2 rounded-full {{ $ramBar }} transition-all duration-500"
                     style="width: {{ min(100, $ramPct) }}%"></div>
            </div>
        </div>

    </div>
@endif

{{-- Server Grid --}}
<div class="flex items-center justify-between mb-4">
    <div>
        <h2 class="text-base font-semibold text-zinc-100">Server</h2>
        <p class="text-xs text-zinc-600 mt-0.5">Live · alle 30 s aktualisiert</p>
    </div>
    <a href="{{ route('servers.create') }}"
       class="inline-flex items-center gap-1.5 rounded-xl bg-blue-600 px-3.5 py-2 text-xs font-semibold text-white hover:bg-blue-500 transition-colors">
        <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
            <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/>
        </svg>
        Server hinzufügen
    </a>
</div>

@if ($servers->isEmpty())
    <div class="mb-8 flex flex-col items-center justify-center rounded-2xl border border-dashed border-zinc-800 py-16 text-center">
        <div class="flex h-14 w-14 items-center justify-center rounded-full bg-zinc-800 mb-4">
            <svg class="h-7 w-7 text-zinc-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1">
                <path stroke-linecap="round" stroke-linejoin="round" d="M5 12h14M5 12a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v4a2 2 0 01-2 2M5 12a2 2 0 00-2 2v4a2 2 0 002 2h14a2 2 0 002-2v-4a2 2 0 00-2-2"/>
            </svg>
        </div>
        <p class="text-sm font-medium text-zinc-400 mb-1">Noch keine Server</p>
        <p class="text-xs text-zinc-600 mb-5">Fuege deinen ersten Server hinzu um loszulegen.</p>
        <a href="{{ route('servers.create') }}"
           class="inline-flex items-center gap-2 rounded-xl bg-blue-600 px-4 py-2 text-sm font-medium text-white hover:bg-blue-500 transition-colors">
            Ersten Server hinzufuegen
        </a>
    </div>
@else
    <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-3 2xl:grid-cols-4 gap-4 mb-8">
        @foreach ($servers as $server)
            <x-widgets.server-card :server="$server" />
        @endforeach
    </div>
@endif

{{-- Bottom row: Top Containers + Recent Alerts --}}
<div class="grid grid-cols-1 lg:grid-cols-2 gap-6">

    {{-- Top Containers by CPU --}}
    <div class="rounded-2xl border border-zinc-800 bg-zinc-900 overflow-hidden">
        <div class="flex items-center justify-between px-5 py-4 border-b border-zinc-800/60">
            <div class="flex items-center gap-2.5">
                <div class="flex h-7 w-7 items-center justify-center rounded-lg bg-blue-500/10">
                    <svg class="h-3.5 w-3.5 text-blue-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M21 7.5l-9-5.25L3 7.5m18 0l-9 5.25m9-5.25v9l-9 5.25M3 7.5l9 5.25M3 7.5v9l9 5.25m0-9v9"/>
                    </svg>
                </div>
                <h2 class="text-sm font-semibold text-zinc-100">Top Container</h2>
                <span class="text-xs text-zinc-600">nach CPU</span>
            </div>
            <a href="{{ route('docker.index') }}" class="text-xs text-blue-400 hover:text-blue-300 transition-colors">Alle →</a>
        </div>
        @if ($top_containers->isEmpty())
            <div class="flex items-center justify-center py-12">
                <p class="text-sm text-zinc-600">Keine Container-Metriken vorhanden.</p>
            </div>
        @else
            <div class="divide-y divide-zinc-800/60">
                @foreach ($top_containers as $c)
                    @php $pct = min(100, round($c->cpu_percent, 1)); @endphp
                    <div class="flex items-center gap-4 px-5 py-3">
                        <span class="h-1.5 w-1.5 rounded-full shrink-0 {{ $c->state === 'running' ? 'bg-green-500 shadow-[0_0_4px_theme(colors.green.500)]' : 'bg-zinc-600' }}"></span>
                        <div class="min-w-0 flex-1">
                            <p class="text-xs font-mono font-medium text-zinc-200 truncate">{{ $c->name }}</p>
                            <p class="text-xs text-zinc-600 truncate">{{ $c->server->name ?? '—' }}</p>
                        </div>
                        <div class="w-28 shrink-0">
                            <div class="flex justify-between text-xs mb-1">
                                <span class="text-zinc-600">CPU</span>
                                <span class="{{ $pct >= 80 ? 'text-red-400' : ($pct >= 50 ? 'text-yellow-400' : 'text-zinc-400') }}">{{ $pct }}%</span>
                            </div>
                            <div class="h-1 w-full rounded-full bg-zinc-800 overflow-hidden">
                                <div class="h-1 rounded-full {{ $pct >= 80 ? 'bg-red-500' : ($pct >= 50 ? 'bg-yellow-500' : 'bg-blue-500') }}"
                                     style="width:{{ $pct }}%"></div>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    </div>

    {{-- Recent Alerts --}}
    <div class="rounded-2xl border border-zinc-800 bg-zinc-900 overflow-hidden">
        <div class="flex items-center justify-between px-5 py-4 border-b border-zinc-800/60">
            <div class="flex items-center gap-2.5">
                <div class="flex h-7 w-7 items-center justify-center rounded-lg bg-amber-500/10">
                    <svg class="h-3.5 w-3.5 text-amber-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M14.857 17.082a23.848 23.848 0 005.454-1.31A8.967 8.967 0 0118 9.75v-.7V9A6 6 0 006 9v.75a8.967 8.967 0 01-2.312 6.022c1.733.64 3.56 1.085 5.455 1.31m5.714 0a24.255 24.255 0 01-5.714 0m5.714 0a3 3 0 11-5.714 0"/>
                    </svg>
                </div>
                <h2 class="text-sm font-semibold text-zinc-100">Letzte Alerts</h2>
                @if ($unread_alerts > 0)
                    <span class="flex items-center justify-center h-5 min-w-[20px] px-1.5 rounded-full bg-red-500 text-xs font-bold text-white">{{ min($unread_alerts, 9) }}</span>
                @endif
            </div>
            <a href="{{ route('alerts.index') }}" class="text-xs text-zinc-500 hover:text-zinc-300 transition-colors">Alle →</a>
        </div>
        @if ($recent_alerts->isEmpty())
            <div class="flex flex-col items-center justify-center py-12">
                <svg class="h-9 w-9 text-zinc-800 mb-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                <p class="text-sm text-zinc-500">Alles in Ordnung</p>
            </div>
        @else
            <ul class="divide-y divide-zinc-800/60">
                @foreach ($recent_alerts as $alert)
                    @php
                        $sev = $alert->severity;
                        [$dotCls, $bgCls] = match($sev) {
                            'critical' => ['bg-red-500', 'bg-red-500/10'],
                            'warning'  => ['bg-amber-500', 'bg-amber-500/10'],
                            default    => ['bg-blue-500', 'bg-blue-500/10'],
                        };
                    @endphp
                    <li class="flex items-start gap-3 px-5 py-3 hover:bg-zinc-800/30 transition-colors">
                        <div class="mt-1 shrink-0 flex h-5 w-5 items-center justify-center rounded-full {{ $bgCls }}">
                            <span class="block h-1.5 w-1.5 rounded-full {{ $dotCls }}"></span>
                        </div>
                        <div class="flex-1 min-w-0">
                            <p class="text-sm text-zinc-300 leading-snug">{{ Str::limit($alert->message, 80) }}</p>
                            <p class="mt-0.5 text-xs text-zinc-600">{{ $alert->server->name ?? '—' }} · {{ $alert->created_at->diffForHumans() }}</p>
                        </div>
                        @if (!$alert->is_read)
                            <form method="POST" action="{{ route('alerts.read', $alert) }}" class="shrink-0 mt-0.5">
                                @csrf
                                <button type="submit" class="flex h-6 w-6 items-center justify-center rounded-md border border-zinc-700 bg-zinc-800 text-zinc-600 hover:text-green-400 hover:border-green-800 transition-colors">
                                    <svg class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                                </button>
                            </form>
                        @endif
                    </li>
                @endforeach
            </ul>
        @endif
    </div>
</div>

{{-- Activity Feed --}}
@if (!empty($activity_feed))
    <div class="rounded-2xl border border-zinc-800 bg-zinc-900 overflow-hidden mt-6">
        <div class="flex items-center justify-between px-5 py-4 border-b border-zinc-800/60">
            <div class="flex items-center gap-2.5">
                <div class="flex h-7 w-7 items-center justify-center rounded-lg bg-purple-500/10">
                    <svg class="h-3.5 w-3.5 text-purple-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
                <h2 class="text-sm font-semibold text-zinc-100">Aktivität</h2>
                <span class="text-xs text-zinc-600">letzte 8 Events</span>
            </div>
        </div>
        <ul class="divide-y divide-zinc-800/60">
            @foreach ($activity_feed as $event)
                @php
                    $sev = $event['severity'];
                    $dotClass = match(true) {
                        $event['resolved']     => 'bg-zinc-600',
                        $sev === 'critical'    => 'bg-red-500 shadow-[0_0_5px_theme(colors.red.500)]',
                        $sev === 'warning'     => 'bg-yellow-500 shadow-[0_0_5px_theme(colors.yellow.500)]',
                        $sev === 'info'        => 'bg-blue-500 shadow-[0_0_5px_theme(colors.blue.500)]',
                        default                => 'bg-zinc-500',
                    };
                @endphp
                <li class="flex items-start gap-3 px-5 py-3 text-sm">
                    <span class="mt-1.5 h-1.5 w-1.5 rounded-full shrink-0 {{ $dotClass }}"></span>
                    <div class="min-w-0 flex-1">
                        <p class="text-zinc-200 {{ $event['resolved'] ? 'line-through text-zinc-500' : '' }}">{{ $event['message'] }}</p>
                        <p class="text-xs text-zinc-600 mt-0.5">
                            <span class="font-mono">{{ $event['server'] }}</span> · {{ $event['time_ago'] }}
                            @if ($event['resolved']) · <span class="text-green-500">resolved</span> @endif
                        </p>
                    </div>
                </li>
            @endforeach
        </ul>
    </div>
@endif

</x-layouts.app>
