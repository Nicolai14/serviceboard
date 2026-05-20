<x-layouts.app :title="$server->name . ' — Docker'">

    <div class="mb-6 flex items-start justify-between gap-4">
        <div>
            <a href="{{ route('servers.show', $server) }}" class="text-sm text-zinc-500 hover:text-zinc-300 transition-colors">
                ← {{ $server->name }}
            </a>
            <div class="mt-2 flex items-center gap-3">
                <svg class="h-5 w-5 text-blue-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M21 7.5l-9-5.25L3 7.5m18 0l-9 5.25m9-5.25v9l-9 5.25M3 7.5l9 5.25M3 7.5v9l9 5.25m0-9v9"/>
                </svg>
                <h1 class="text-xl font-bold text-white">Docker Container</h1>
            </div>
            <p class="mt-0.5 text-sm text-zinc-500 font-mono">{{ $server->hostname }}</p>
        </div>
    </div>

    <div
        x-data="{
            containers: {{ Js::from($containers->map(fn($c) => [
                'id'              => $c->id,
                'container_id'    => $c->container_id,
                'name'            => $c->name,
                'image'           => $c->image,
                'state'           => $c->state,
                'status_text'     => $c->status_text,
                'cpu_percent'     => $c->cpu_percent,
                'memory_usage_mb' => $c->memory_usage_mb,
                'memory_limit_mb' => $c->memory_limit_mb,
                'memory_percent'  => $c->memory_percent,
                'port_summary'    => $c->port_summary,
                'ports'           => $c->ports ?? [],
                'synced_at'       => $c->synced_at?->diffForHumans(),
            ])->values()) }},
            syncing: false,
            syncMsg: null,
            csrfToken: document.querySelector('meta[name=csrf-token]').content,

            get running()    { return this.containers.filter(c => c.state === 'running').length },
            get notRunning() { return this.containers.filter(c => c.state !== 'running').length },

            async refresh() {
                try {
                    const r = await fetch('{{ route('servers.docker.status-json', $server) }}', {
                        headers: { 'Accept': 'application/json' }
                    });
                    const d = await r.json();
                    this.containers = d.containers;
                } catch {}
            },

            async syncNow() {
                this.syncing = true;
                this.syncMsg = null;
                try {
                    const r = await fetch('{{ route('servers.docker.sync', $server) }}', {
                        method: 'POST',
                        headers: { 'X-CSRF-TOKEN': this.csrfToken, 'Accept': 'application/json' }
                    });
                    const d = await r.json();
                    this.syncMsg = d.message;
                    if (d.dispatched) { setTimeout(() => { this.refresh(); this.syncing = false; }, 3500); }
                    else { this.syncing = false; }
                } catch { this.syncing = false; }
            },

            stateColor(state) {
                return {
                    running:    'bg-green-900/30 text-green-400 border-green-800',
                    exited:     'bg-red-900/30 text-red-400 border-red-800',
                    paused:     'bg-yellow-900/30 text-yellow-400 border-yellow-800',
                    restarting: 'bg-blue-900/30 text-blue-400 border-blue-800',
                    dead:       'bg-red-900/40 text-red-500 border-red-900',
                    created:    'bg-zinc-800 text-zinc-400 border-zinc-700',
                }[state] ?? 'bg-zinc-800 text-zinc-400 border-zinc-700';
            },
        }"
        x-init="setInterval(() => refresh(), 20000)"
    >
        {{-- Top stats + sync button --}}
        <div class="mb-6 flex items-center gap-4">
            <div class="flex items-center gap-2 rounded-lg border border-zinc-800 bg-zinc-900 px-4 py-2.5">
                <span class="h-2 w-2 rounded-full bg-green-500 shadow-[0_0_4px_theme(colors.green.500)]"></span>
                <span class="text-sm text-zinc-400">Running: <strong class="text-green-400" x-text="running"></strong></span>
            </div>
            <div class="flex items-center gap-2 rounded-lg border border-zinc-800 bg-zinc-900 px-4 py-2.5">
                <span class="h-2 w-2 rounded-full bg-red-500"></span>
                <span class="text-sm text-zinc-400">Stopped: <strong class="text-red-400" x-text="notRunning"></strong></span>
            </div>

            <div class="ml-auto flex items-center gap-3">
                <template x-if="syncMsg">
                    <span class="text-xs text-blue-400" x-text="syncMsg"></span>
                </template>
                <button @click="syncNow()"
                        :disabled="syncing"
                        class="inline-flex items-center gap-2 rounded-lg bg-blue-600 px-4 py-2 text-sm font-medium text-white
                               hover:bg-blue-500 disabled:opacity-50 disabled:cursor-not-allowed transition-colors">
                    <svg class="h-4 w-4" :class="{'animate-spin': syncing}" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0l3.181 3.183a8.25 8.25 0 0013.803-3.7M4.031 9.865a8.25 8.25 0 0113.803-3.7l3.181 3.182m0-4.991v4.99"/>
                    </svg>
                    <span x-text="syncing ? 'Syncing…' : 'Jetzt synchronisieren'">Jetzt synchronisieren</span>
                </button>
            </div>
        </div>

        {{-- Container grid --}}
        <template x-if="containers.length === 0">
            <div class="flex flex-col items-center justify-center rounded-xl border border-dashed border-zinc-800 py-16 text-center">
                <svg class="h-12 w-12 text-zinc-700 mb-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M21 7.5l-9-5.25L3 7.5m18 0l-9 5.25m9-5.25v9l-9 5.25M3 7.5l9 5.25M3 7.5v9l9 5.25m0-9v9"/>
                </svg>
                <p class="text-sm text-zinc-500">Keine Container gefunden.</p>
                <p class="text-xs text-zinc-700 mt-1">Klicke auf „Jetzt synchronisieren" um Docker abzufragen.</p>
            </div>
        </template>

        <template x-if="containers.length > 0">
            <div class="space-y-3">
                <template x-for="c in containers" :key="c.container_id">
                    <div class="rounded-xl border border-zinc-800 bg-zinc-900 p-5">
                        <div class="flex flex-wrap items-start justify-between gap-4 mb-4">
                            {{-- Identity --}}
                            <div class="min-w-0">
                                <div class="flex items-center gap-2.5 mb-1">
                                    <span class="h-2 w-2 rounded-full shrink-0"
                                          :class="c.state === 'running' ? 'bg-green-500 shadow-[0_0_5px_theme(colors.green.500)]' : 'bg-zinc-600'">
                                    </span>
                                    <h3 class="text-sm font-semibold text-zinc-100 font-mono" x-text="c.name"></h3>
                                    <span class="inline-flex items-center rounded-full border px-2 py-0.5 text-xs font-medium capitalize"
                                          :class="stateColor(c.state)"
                                          x-text="c.state">
                                    </span>
                                </div>
                                <p class="text-xs text-zinc-600 font-mono" x-text="c.image"></p>
                                <p class="text-xs text-zinc-700 mt-0.5" x-text="c.status_text"></p>
                            </div>

                            {{-- Ports --}}
                            <div class="shrink-0">
                                <template x-if="c.ports && c.ports.length > 0">
                                    <div class="flex flex-wrap gap-1 justify-end">
                                        <template x-for="p in c.ports" :key="p.host + p.container">
                                            <span class="rounded-md border border-zinc-700 bg-zinc-800 px-2 py-1 text-xs font-mono text-zinc-300">
                                                <span x-text="p.host"></span>:<span x-text="p.container"></span>
                                                <span class="text-zinc-600" x-text="'/' + p.proto"></span>
                                            </span>
                                        </template>
                                    </div>
                                </template>
                                <template x-if="!c.ports || c.ports.length === 0">
                                    <span class="text-xs text-zinc-700">Keine Ports</span>
                                </template>
                            </div>
                        </div>

                        {{-- Metrics --}}
                        <template x-if="c.cpu_percent !== null || c.memory_usage_mb !== null">
                            <div class="grid grid-cols-2 gap-4 pt-4 border-t border-zinc-800">
                                {{-- CPU --}}
                                <div>
                                    <div class="flex justify-between text-xs mb-1.5">
                                        <span class="text-zinc-500">CPU</span>
                                        <span :class="c.cpu_percent >= 80 ? 'text-red-400' : c.cpu_percent >= 50 ? 'text-yellow-400' : 'text-zinc-300'"
                                              x-text="c.cpu_percent + '%'"></span>
                                    </div>
                                    <div class="h-2 w-full rounded-full bg-zinc-800 overflow-hidden">
                                        <div class="h-2 rounded-full transition-all duration-500"
                                             :class="c.cpu_percent >= 80 ? 'bg-red-500' : c.cpu_percent >= 50 ? 'bg-yellow-500' : 'bg-blue-500'"
                                             :style="`width:${Math.min(100, c.cpu_percent)}%`"></div>
                                    </div>
                                </div>

                                {{-- RAM --}}
                                <div>
                                    <div class="flex justify-between text-xs mb-1.5">
                                        <span class="text-zinc-500">RAM</span>
                                        <span :class="c.memory_percent >= 85 ? 'text-red-400' : c.memory_percent >= 60 ? 'text-yellow-400' : 'text-zinc-300'">
                                            <span x-text="c.memory_usage_mb + 'MB'"></span>
                                            <span class="text-zinc-600" x-if="c.memory_limit_mb"> / <span x-text="c.memory_limit_mb + 'MB'"></span></span>
                                        </span>
                                    </div>
                                    <div class="h-2 w-full rounded-full bg-zinc-800 overflow-hidden">
                                        <div class="h-2 rounded-full transition-all duration-500"
                                             :class="c.memory_percent >= 85 ? 'bg-red-500' : c.memory_percent >= 60 ? 'bg-yellow-500' : 'bg-green-500'"
                                             :style="`width:${Math.min(100, c.memory_percent ?? 0)}%`"></div>
                                    </div>
                                </div>
                            </div>
                        </template>

                        <template x-if="c.cpu_percent === null && c.memory_usage_mb === null">
                            <p class="pt-3 border-t border-zinc-800 text-xs text-zinc-700">
                                Keine Metriken — Container läuft nicht oder wurde noch nicht gepollt.
                            </p>
                        </template>
                    </div>
                </template>
            </div>
        </template>
    </div>
</x-layouts.app>
