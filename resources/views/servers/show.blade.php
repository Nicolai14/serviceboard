<x-layouts.app :title="$server->name">
    {{-- Page header --}}
    <div class="mb-6 flex items-start justify-between gap-4">
        <div>
            <a href="{{ route('servers.index') }}" class="text-sm text-zinc-500 hover:text-zinc-300 transition-colors">
                ← Server
            </a>
            <div class="mt-2 flex items-center gap-3">
                <x-status-dot :status="$server->status" />
                <h1 class="text-xl font-bold text-white">{{ $server->name }}</h1>
                <span class="rounded-full border px-2.5 py-0.5 text-xs font-medium
                    @if($server->status === 'online') border-green-800 bg-green-900/30 text-green-400
                    @elseif($server->status === 'offline') border-red-800 bg-red-900/30 text-red-400
                    @elseif($server->status === 'maintenance') border-yellow-800 bg-yellow-900/30 text-yellow-400
                    @else border-zinc-700 bg-zinc-800 text-zinc-400 @endif">
                    {{ ucfirst($server->status) }}
                </span>
            </div>
            <p class="mt-0.5 text-sm text-zinc-500 font-mono">
                {{ $server->ssh_user . '@' . ($server->ip_address ?: $server->hostname) . ':' . $server->ssh_port }}
            </p>
        </div>

        <div class="flex items-center gap-2 shrink-0">
            <a href="{{ route('servers.edit', $server) }}"
               class="inline-flex items-center gap-1.5 rounded-lg border border-zinc-700 bg-zinc-800 px-3 py-2 text-sm font-medium text-zinc-300 hover:bg-zinc-700 transition-colors">
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L10.582 16.07a4.5 4.5 0 01-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 011.13-1.897l8.932-8.931z"/>
                </svg>
                Bearbeiten
            </a>
        </div>
    </div>

    {{-- SSH Control Panel --}}
    <div
        x-data="{
            sshResult: null,
            onlineResult: null,
            loadingSSH: false,
            loadingOnline: false,
            loadingPoll: false,
            pollResult: null,
            csrfToken: document.querySelector('meta[name=csrf-token]').content,

            async checkOnline() {
                this.loadingOnline = true;
                this.onlineResult = null;
                try {
                    const r = await fetch('{{ route('servers.check-online', $server) }}', {
                        method: 'POST',
                        headers: { 'X-CSRF-TOKEN': this.csrfToken, 'Accept': 'application/json' }
                    });
                    this.onlineResult = await r.json();
                } catch { this.onlineResult = { online: false, message: 'Netzwerkfehler' }; }
                this.loadingOnline = false;
            },

            async testSSH() {
                this.loadingSSH = true;
                this.sshResult = null;
                try {
                    const r = await fetch('{{ route('servers.test-ssh', $server) }}', {
                        method: 'POST',
                        headers: { 'X-CSRF-TOKEN': this.csrfToken, 'Accept': 'application/json' }
                    });
                    this.sshResult = await r.json();
                } catch { this.sshResult = { success: false, message: 'Netzwerkfehler' }; }
                this.loadingSSH = false;
            },

            async pollNow() {
                this.loadingPoll = true;
                this.pollResult = null;
                try {
                    const r = await fetch('{{ route('servers.poll-now', $server) }}', {
                        method: 'POST',
                        headers: { 'X-CSRF-TOKEN': this.csrfToken, 'Accept': 'application/json' }
                    });
                    this.pollResult = await r.json();
                } catch { this.pollResult = { dispatched: false, message: 'Netzwerkfehler' }; }
                this.loadingPoll = false;
            },
        }"
        class="mb-6 rounded-xl border border-zinc-800 bg-zinc-900 p-5"
    >
        <h2 class="text-sm font-semibold text-zinc-100 mb-4">SSH Verbindung testen</h2>

        <div class="flex flex-wrap gap-2 mb-4">
            {{-- Check Online --}}
            <button @click="checkOnline()"
                    :disabled="loadingOnline"
                    class="inline-flex items-center gap-2 rounded-lg border border-zinc-700 bg-zinc-800 px-4 py-2 text-sm font-medium text-zinc-300
                           hover:bg-zinc-700 disabled:opacity-50 disabled:cursor-not-allowed transition-colors">
                <svg class="h-4 w-4" :class="{ 'animate-spin': loadingOnline }" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75m-3-7.036A11.959 11.959 0 013.598 6 11.955 11.955 0 01.06 8.834a12 12 0 0012 12c2.505 0 4.834-.745 6.777-2.025A11.952 11.952 0 0021.5 12c0-.057 0-.115-.002-.172"/>
                </svg>
                <span x-text="loadingOnline ? 'Prüfe…' : 'Online-Check (TCP)'">Online-Check (TCP)</span>
            </button>

            {{-- Test SSH --}}
            <button @click="testSSH()"
                    :disabled="loadingSSH"
                    class="inline-flex items-center gap-2 rounded-lg border border-zinc-700 bg-zinc-800 px-4 py-2 text-sm font-medium text-zinc-300
                           hover:bg-zinc-700 disabled:opacity-50 disabled:cursor-not-allowed transition-colors">
                <svg class="h-4 w-4" :class="{ 'animate-spin': loadingSSH }" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 3v1.5M4.5 8.25H3m18 0h-1.5M4.5 12H3m18 0h-1.5m-15 3.75H3m18 0h-1.5M8.25 19.5V21M12 3v1.5m0 15V21m3.75-18v1.5m0 15V21m-9-1.5h10.5a2.25 2.25 0 002.25-2.25V6.75a2.25 2.25 0 00-2.25-2.25H6.75A2.25 2.25 0 004.5 6.75v10.5a2.25 2.25 0 002.25 2.25zm.75-12h9v9h-9v-9z"/>
                </svg>
                <span x-text="loadingSSH ? 'Verbinde…' : 'SSH Auth testen'">SSH Auth testen</span>
            </button>

            {{-- Poll Now --}}
            @if ($server->hasSSHCredentials())
                <button @click="pollNow()"
                        :disabled="loadingPoll"
                        class="inline-flex items-center gap-2 rounded-lg bg-blue-600 px-4 py-2 text-sm font-medium text-white
                               hover:bg-blue-500 disabled:opacity-50 disabled:cursor-not-allowed transition-colors">
                    <svg class="h-4 w-4" :class="{ 'animate-spin': loadingPoll }" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0l3.181 3.183a8.25 8.25 0 0013.803-3.7M4.031 9.865a8.25 8.25 0 0113.803-3.7l3.181 3.182m0-4.991v4.99"/>
                    </svg>
                    <span x-text="loadingPoll ? 'Dispatching…' : 'Metriken jetzt abrufen'">Metriken jetzt abrufen</span>
                </button>
            @else
                <div class="inline-flex items-center gap-2 rounded-lg border border-dashed border-zinc-700 px-4 py-2 text-sm text-zinc-600">
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M16.5 10.5V6.75a4.5 4.5 0 10-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 002.25-2.25v-6.75a2.25 2.25 0 00-2.25-2.25H6.75a2.25 2.25 0 00-2.25 2.25v6.75a2.25 2.25 0 002.25 2.25z"/>
                    </svg>
                    Keine SSH-Zugangsdaten —
                    <a href="{{ route('servers.edit', $server) }}" class="text-blue-400 hover:text-blue-300">jetzt konfigurieren</a>
                </div>
            @endif
        </div>

        {{-- Result areas --}}
        <div class="space-y-2">
            <template x-if="onlineResult !== null">
                <div class="flex items-center gap-2.5 rounded-lg px-3.5 py-2.5 text-sm"
                     :class="onlineResult.online ? 'bg-green-900/20 border border-green-800 text-green-400' : 'bg-red-900/20 border border-red-800 text-red-400'">
                    <svg class="h-4 w-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <template x-if="onlineResult.online">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/>
                        </template>
                        <template x-if="!onlineResult.online">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
                        </template>
                    </svg>
                    <span x-text="onlineResult.message"></span>
                </div>
            </template>

            <template x-if="sshResult !== null">
                <div class="flex items-center gap-2.5 rounded-lg px-3.5 py-2.5 text-sm"
                     :class="sshResult.success ? 'bg-green-900/20 border border-green-800 text-green-400' : 'bg-red-900/20 border border-red-800 text-red-400'">
                    <svg class="h-4 w-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <template x-if="sshResult.success">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/>
                        </template>
                        <template x-if="!sshResult.success">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
                        </template>
                    </svg>
                    <span x-text="sshResult.message + (sshResult.latency_ms ? ' (' + sshResult.latency_ms + 'ms)' : '')"></span>
                </div>
            </template>

            <template x-if="pollResult !== null">
                <div class="flex items-center gap-2.5 rounded-lg px-3.5 py-2.5 text-sm"
                     :class="pollResult.dispatched ? 'bg-blue-900/20 border border-blue-800 text-blue-400' : 'bg-red-900/20 border border-red-800 text-red-400'">
                    <svg class="h-4 w-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/>
                    </svg>
                    <span x-text="pollResult.message"></span>
                </div>
            </template>
        </div>
    </div>

    {{-- Metric gauges --}}
    @if ($latestMetric)
        <div class="mb-6 grid grid-cols-2 sm:grid-cols-4 gap-4">
            @php
                $gauges = [
                    ['label' => 'CPU', 'value' => $latestMetric->cpu_usage, 'unit' => '%', 'sub' => ''],
                    ['label' => 'RAM', 'value' => $latestMetric->memory_percent, 'unit' => '%',
                     'sub' => number_format($latestMetric->memory_usage / 1024, 1) . ' / ' . number_format($latestMetric->memory_total / 1024, 1) . ' GB'],
                    ['label' => 'Disk', 'value' => $latestMetric->disk_percent, 'unit' => '%',
                     'sub' => number_format($latestMetric->disk_usage, 1) . ' / ' . number_format($latestMetric->disk_total, 1) . ' GB'],
                    ['label' => 'Load Avg', 'value' => null, 'unit' => '', 'sub' => number_format($latestMetric->load_average, 2)],
                ];
            @endphp

            @foreach ($gauges as $g)
                @php
                    $v = $g['value'];
                    $barColor = match(true) {
                        $v === null  => 'bg-blue-500',
                        $v >= 90     => 'bg-red-500',
                        $v >= 75     => 'bg-yellow-500',
                        default      => 'bg-green-500',
                    };
                    $textColor = match(true) {
                        $v === null  => 'text-blue-300',
                        $v >= 90     => 'text-red-400',
                        $v >= 75     => 'text-yellow-400',
                        default      => 'text-green-400',
                    };
                @endphp
                <div class="rounded-xl border border-zinc-800 bg-zinc-900 p-5">
                    <p class="text-xs text-zinc-500 mb-1">{{ $g['label'] }}</p>
                    <p class="text-2xl font-bold {{ $textColor }} mb-3">
                        @if ($v !== null)
                            {{ number_format($v, 1) }}{{ $g['unit'] }}
                        @else
                            {{ $g['sub'] }}
                        @endif
                    </p>
                    @if ($v !== null)
                        <div class="h-1.5 w-full rounded-full bg-zinc-800 overflow-hidden">
                            <div class="{{ $barColor }} h-1.5 rounded-full transition-all"
                                 style="width: {{ min(100, $v) }}%"></div>
                        </div>
                        @if ($g['sub'])
                            <p class="mt-1.5 text-xs text-zinc-600">{{ $g['sub'] }}</p>
                        @endif
                    @endif
                </div>
            @endforeach
        </div>

        {{-- Uptime badge --}}
        <div class="mb-6 flex items-center gap-6 rounded-xl border border-zinc-800 bg-zinc-900 px-5 py-4">
            <div>
                <p class="text-xs text-zinc-500">Uptime</p>
                <p class="text-lg font-semibold text-zinc-100">
                    @php
                        $s = $latestMetric->uptime_seconds;
                        $d = intdiv($s, 86400);
                        $h = intdiv($s % 86400, 3600);
                        $m = intdiv($s % 3600, 60);
                    @endphp
                    @if ($d > 0) {{ $d }}d {{ $h }}h
                    @elseif ($h > 0) {{ $h }}h {{ $m }}m
                    @else {{ $m }}m
                    @endif
                </p>
            </div>
            <div class="w-px h-8 bg-zinc-800"></div>
            <div>
                <p class="text-xs text-zinc-500">Letztes Polling</p>
                <p class="text-sm text-zinc-300">{{ $server->last_polled_at?->diffForHumans() ?? '—' }}</p>
            </div>
            <div class="w-px h-8 bg-zinc-800"></div>
            <div>
                <p class="text-xs text-zinc-500">Zuletzt gesehen</p>
                <p class="text-sm text-zinc-300">{{ $server->last_seen_at?->diffForHumans() ?? '—' }}</p>
            </div>
        </div>
    @else
        <div class="mb-6 rounded-xl border border-dashed border-zinc-800 p-8 text-center">
            <p class="text-sm text-zinc-600">Noch keine Metriken — klicke auf <strong class="text-zinc-400">„Metriken jetzt abrufen"</strong> um die erste Messung zu starten.</p>
        </div>
    @endif

    {{-- Docker quick-view --}}
    @php
        $dockerContainers = $server->dockerContainers()->orderByRaw("state = 'running' DESC")->orderBy('name')->get();
        $dockerRunning    = $dockerContainers->where('state', 'running')->count();
        $dockerTotal      = $dockerContainers->count();
    @endphp
    <div class="mb-6 rounded-xl border border-zinc-800 bg-zinc-900 overflow-hidden">
        <div class="flex items-center justify-between px-5 py-4 border-b border-zinc-800">
            <div class="flex items-center gap-3">
                <svg class="h-4 w-4 text-blue-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M21 7.5l-9-5.25L3 7.5m18 0l-9 5.25m9-5.25v9l-9 5.25M3 7.5l9 5.25M3 7.5v9l9 5.25m0-9v9"/>
                </svg>
                <h2 class="text-sm font-semibold text-zinc-100">Docker Container</h2>
                @if ($dockerTotal > 0)
                    <span class="rounded-full bg-zinc-800 border border-zinc-700 px-2 py-0.5 text-xs text-zinc-400">
                        <span class="text-green-400 font-medium">{{ $dockerRunning }}</span> / {{ $dockerTotal }} running
                    </span>
                @endif
            </div>
            <a href="{{ route('servers.docker.index', $server) }}"
               class="text-xs text-blue-400 hover:text-blue-300 transition-colors">
                Alle anzeigen →
            </a>
        </div>

        @if ($dockerTotal === 0)
            <div class="py-8 text-center">
                <p class="text-sm text-zinc-600">Keine Container gefunden.</p>
                <p class="text-xs text-zinc-700 mt-1">
                    <a href="{{ route('servers.docker.index', $server) }}" class="text-blue-500 hover:text-blue-400">Sync starten →</a>
                </p>
            </div>
        @else
            <div class="divide-y divide-zinc-800/60">
                @foreach ($dockerContainers->take(6) as $dc)
                    <div class="flex items-center gap-3 px-5 py-2.5">
                        <span class="h-1.5 w-1.5 rounded-full shrink-0 {{ $dc->state === 'running' ? 'bg-green-500' : 'bg-zinc-600' }}"></span>
                        <span class="text-xs font-mono text-zinc-200 flex-1 truncate">{{ $dc->name }}</span>
                        <span class="text-xs text-zinc-600 font-mono truncate max-w-48 hidden sm:block">{{ Str::limit($dc->image, 40) }}</span>
                        <span class="rounded-full border px-2 py-0.5 text-xs font-medium capitalize shrink-0
                            {{ $dc->state === 'running' ? 'border-green-800 bg-green-900/30 text-green-400' :
                               ($dc->state === 'exited' ? 'border-red-800 bg-red-900/30 text-red-400' : 'border-zinc-700 bg-zinc-800 text-zinc-400') }}">
                            {{ $dc->state }}
                        </span>
                        @if ($dc->cpu_percent !== null)
                            <span class="text-xs text-zinc-500 shrink-0 hidden sm:block">
                                CPU {{ round($dc->cpu_percent, 1) }}%
                            </span>
                        @endif
                    </div>
                @endforeach
                @if ($dockerTotal > 6)
                    <div class="px-5 py-2.5 text-center">
                        <a href="{{ route('servers.docker.index', $server) }}" class="text-xs text-zinc-500 hover:text-zinc-300 transition-colors">
                            + {{ $dockerTotal - 6 }} weitere Container anzeigen
                        </a>
                    </div>
                @endif
            </div>
        @endif
    </div>

    {{-- Services + Alerts --}}
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">

        {{-- Services --}}
        <div class="rounded-xl border border-zinc-800 bg-zinc-900">
            <div class="flex items-center justify-between p-5 border-b border-zinc-800">
                <h2 class="text-sm font-semibold text-zinc-100">Services ({{ $server->services->count() }})</h2>
                <a href="{{ route('servers.services.create', $server) }}"
                   class="inline-flex items-center gap-1 rounded-lg bg-zinc-800 border border-zinc-700 px-2.5 py-1.5 text-xs font-medium text-zinc-300 hover:bg-zinc-700 transition-colors">
                    <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/>
                    </svg>
                    Hinzufügen
                </a>
            </div>
            @if ($server->services->isEmpty())
                <div class="py-10 text-center">
                    <p class="text-sm text-zinc-600">Noch keine Services konfiguriert</p>
                </div>
            @else
                <ul class="divide-y divide-zinc-800">
                    @foreach ($server->services as $service)
                        <li class="flex items-center gap-4 px-5 py-3">
                            <div class="flex-1 min-w-0">
                                <p class="text-sm font-medium text-zinc-100">{{ $service->name }}</p>
                                <p class="text-xs text-zinc-500">
                                    {{ ucfirst($service->type) }}@if ($service->port) · Port {{ $service->port }} @endif
                                </p>
                            </div>
                            <span class="text-xs px-2 py-0.5 rounded-full
                                {{ $service->status === 'running' ? 'bg-green-900/40 text-green-400' : 'bg-red-900/40 text-red-400' }}">
                                {{ ucfirst($service->status) }}
                            </span>
                        </li>
                    @endforeach
                </ul>
            @endif
        </div>

        {{-- Recent Alerts --}}
        <div class="rounded-xl border border-zinc-800 bg-zinc-900">
            <div class="flex items-center justify-between p-5 border-b border-zinc-800">
                <h2 class="text-sm font-semibold text-zinc-100">Letzte Alerts</h2>
                <a href="{{ route('alerts.index', ['server_id' => $server->id]) }}" class="text-xs text-blue-400 hover:text-blue-300 transition-colors">
                    Alle anzeigen →
                </a>
            </div>
            @if ($server->alerts->isEmpty())
                <div class="py-10 text-center">
                    <p class="text-sm text-zinc-600">Keine Alerts für diesen Server</p>
                </div>
            @else
                <ul class="divide-y divide-zinc-800">
                    @foreach ($server->alerts as $alert)
                        <li class="px-5 py-3">
                            <div class="flex items-start gap-2">
                                <span class="mt-1 block h-1.5 w-1.5 shrink-0 rounded-full
                                    {{ $alert->severity === 'critical' ? 'bg-red-500' : ($alert->severity === 'warning' ? 'bg-yellow-500' : 'bg-blue-500') }}">
                                </span>
                                <div class="min-w-0">
                                    <p class="text-xs text-zinc-300 leading-snug">{{ Str::limit($alert->message, 80) }}</p>
                                    <p class="text-xs text-zinc-600 mt-0.5">{{ $alert->created_at->diffForHumans() }}</p>
                                </div>
                            </div>
                        </li>
                    @endforeach
                </ul>
            @endif
        </div>
    </div>

    {{-- Danger zone --}}
    <div class="mt-6 rounded-xl border border-zinc-800 bg-zinc-900 p-5">
        <h3 class="text-xs font-semibold text-zinc-500 uppercase tracking-wider mb-3">Server-Informationen</h3>
        <dl class="grid grid-cols-2 sm:grid-cols-4 gap-4 text-sm">
            <div><dt class="text-xs text-zinc-600">OS</dt><dd class="text-zinc-300 mt-0.5">{{ $server->os ?: '—' }}</dd></div>
            <div><dt class="text-xs text-zinc-600">IP</dt><dd class="text-zinc-300 mt-0.5 font-mono">{{ $server->ip_address ?: '—' }}</dd></div>
            <div><dt class="text-xs text-zinc-600">Auth</dt><dd class="text-zinc-300 mt-0.5 capitalize">{{ $server->ssh_auth_method }}</dd></div>
            <div><dt class="text-xs text-zinc-600">Hinzugefügt</dt><dd class="text-zinc-300 mt-0.5">{{ $server->created_at->format('d.m.Y') }}</dd></div>
        </dl>

        <div class="mt-4 pt-4 border-t border-zinc-800">
            <form method="POST" action="{{ route('servers.destroy', $server) }}"
                  onsubmit="return confirm('Server wirklich löschen? Alle Metriken und Alerts werden entfernt.')">
                @csrf
                @method('DELETE')
                <button type="submit" class="text-xs text-red-600 hover:text-red-400 transition-colors">
                    Server löschen
                </button>
            </form>
        </div>
    </div>
</x-layouts.app>
