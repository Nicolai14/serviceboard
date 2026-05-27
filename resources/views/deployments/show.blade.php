<x-layouts.app :title="'Deployment — ' . $deployment->name">
    <div class="max-w-3xl"
         x-data="{
            status: @js($deployment->deployment_status->value),
            label: @js($deployment->deployment_status->label()),
            color: @js($deployment->deployment_status->color()),
            active: {{ $deployment->isActive() ? 'true' : 'false' }},
            log: @js($deployment->log ?? ''),
            duration: @js($deployment->duration),
            statusUrl: @js(route('servers.deployments.status-json', [$server, $deployment])),
            badges: {
                yellow: 'bg-yellow-900/30 text-yellow-400',
                blue:   'bg-blue-900/30 text-blue-400',
                green:  'bg-green-900/30 text-green-400',
                red:    'bg-red-900/30 text-red-400',
                zinc:   'bg-zinc-800 text-zinc-400',
            },
            init() { if (this.active) this.poll(); },
            poll() {
                const timer = setInterval(async () => {
                    try {
                        const res = await fetch(this.statusUrl, { headers: { Accept: 'application/json' } });
                        if (!res.ok) return;
                        const data = await res.json();
                        this.status = data.status;
                        this.label = data.label;
                        this.color = data.color;
                        this.log = data.log;
                        this.duration = data.duration;
                        if (!data.active) { this.active = false; clearInterval(timer); }
                    } catch (e) { /* keep polling */ }
                }, 2000);
            },
         }">

        <div class="mb-6 flex items-start justify-between gap-4">
            <div>
                <a href="{{ route('servers.deployments.index', $server) }}" class="text-sm text-zinc-500 hover:text-zinc-300 transition-colors">
                    ← Deployments
                </a>
                <div class="mt-2 flex items-center gap-3">
                    <h1 class="text-xl font-bold text-white">{{ $deployment->name }}</h1>
                    <span class="text-xs px-2 py-0.5 rounded-full" :class="badges[color] || badges.zinc" x-text="label"></span>
                </div>
                <p class="mt-0.5 text-sm text-zinc-500">
                    {{ $deployment->deployment_type->label() }} · {{ ucfirst($deployment->trigger) }} ·
                    {{ $server->name }}
                </p>
            </div>

            <div class="flex items-center gap-2 shrink-0" x-show="!active" x-cloak>
                <form method="POST" action="{{ route('servers.deployments.retry', [$server, $deployment]) }}">
                    @csrf
                    <button type="submit"
                            class="inline-flex items-center gap-1.5 rounded-lg border border-zinc-700 bg-zinc-800 px-3 py-2 text-sm font-medium text-zinc-300 hover:bg-zinc-700 transition-colors">
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0l3.181 3.183a8.25 8.25 0 0013.803-3.7M4.031 9.865a8.25 8.25 0 0113.803-3.7l3.181 3.182m0-4.991v4.99"/>
                        </svg>
                        Erneut ausführen
                    </button>
                </form>
                <form method="POST" action="{{ route('servers.deployments.destroy', [$server, $deployment]) }}"
                      onsubmit="return confirm('Diesen Deployment-Eintrag löschen?')">
                    @csrf
                    @method('DELETE')
                    <button type="submit"
                            class="inline-flex items-center rounded-lg border border-zinc-800 px-3 py-2 text-sm font-medium text-red-400 hover:bg-red-900/20 transition-colors">
                        Löschen
                    </button>
                </form>
            </div>
        </div>

        {{-- Meta --}}
        <div class="grid grid-cols-3 gap-4 mb-6">
            <div class="rounded-xl border border-zinc-800 bg-zinc-900 p-4">
                <p class="text-xs font-semibold uppercase tracking-wider text-zinc-500">Gestartet</p>
                <p class="mt-1 text-sm text-zinc-200">{{ $deployment->started_at?->diffForHumans() ?? '—' }}</p>
            </div>
            <div class="rounded-xl border border-zinc-800 bg-zinc-900 p-4">
                <p class="text-xs font-semibold uppercase tracking-wider text-zinc-500">Beendet</p>
                <p class="mt-1 text-sm text-zinc-200">{{ $deployment->finished_at?->diffForHumans() ?? '—' }}</p>
            </div>
            <div class="rounded-xl border border-zinc-800 bg-zinc-900 p-4">
                <p class="text-xs font-semibold uppercase tracking-wider text-zinc-500">Dauer</p>
                <p class="mt-1 text-sm text-zinc-200"><span x-text="duration !== null ? duration + 's' : '—'"></span></p>
            </div>
        </div>

        {{-- Log --}}
        <div class="rounded-xl border border-zinc-800 bg-zinc-900 overflow-hidden">
            <div class="flex items-center justify-between px-5 py-3 border-b border-zinc-800">
                <h2 class="text-sm font-semibold text-zinc-100">Log</h2>
                <span x-show="active" x-cloak class="flex items-center gap-1.5 text-xs text-blue-400">
                    <span class="block h-1.5 w-1.5 rounded-full bg-blue-400 animate-pulse"></span>
                    Läuft…
                </span>
            </div>
            <pre class="max-h-[28rem] overflow-auto px-5 py-4 text-xs font-mono text-zinc-300 whitespace-pre-wrap"
                 x-text="log || 'Noch keine Ausgabe…'"></pre>
        </div>
    </div>
</x-layouts.app>
