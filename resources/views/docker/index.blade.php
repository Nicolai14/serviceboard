<x-layouts.app title="Container">

{{-- Header --}}
<div class="flex items-start justify-between gap-4 mb-6">
    <div>
        <h1 class="text-xl font-bold text-white">Container</h1>
        <p class="text-sm text-zinc-500 mt-0.5">Docker-Container aller Server</p>
    </div>
</div>

{{-- Stats strip --}}
<div class="grid grid-cols-2 sm:grid-cols-4 gap-4 mb-8">
    <div class="rounded-2xl border border-zinc-800 bg-zinc-900 p-5">
        <p class="text-xs font-semibold uppercase tracking-wider text-zinc-500 mb-3">Gesamt</p>
        <p class="text-3xl font-bold text-white">{{ $stats['total'] }}</p>
    </div>
    <div class="relative rounded-2xl border border-zinc-800 bg-zinc-900 p-5 overflow-hidden">
        <div class="absolute inset-0 bg-gradient-to-br from-green-500/5 to-transparent pointer-events-none"></div>
        <div class="flex items-center justify-between mb-3">
            <p class="text-xs font-semibold uppercase tracking-wider text-zinc-500">Running</p>
            <span class="flex h-2 w-2 rounded-full bg-green-500 shadow-[0_0_6px_theme(colors.green.500)]"></span>
        </div>
        <p class="text-3xl font-bold text-green-400">{{ $stats['running'] }}</p>
    </div>
    <div class="rounded-2xl border border-zinc-800 bg-zinc-900 p-5">
        <div class="flex items-center justify-between mb-3">
            <p class="text-xs font-semibold uppercase tracking-wider text-zinc-500">Stopped</p>
            <span class="flex h-2 w-2 rounded-full bg-zinc-600"></span>
        </div>
        <p class="text-3xl font-bold text-zinc-400">{{ $stats['stopped'] }}</p>
    </div>
    <div class="rounded-2xl border border-zinc-800 bg-zinc-900 p-5">
        <p class="text-xs font-semibold uppercase tracking-wider text-zinc-500 mb-3">Server mit Docker</p>
        <p class="text-3xl font-bold text-blue-400">{{ $stats['servers'] }}</p>
    </div>
</div>

@php $byServer = $containers->groupBy('server_id'); @endphp

@if ($containers->isEmpty())
    <div class="flex flex-col items-center justify-center rounded-2xl border border-dashed border-zinc-800 py-20 text-center">
        <div class="flex h-14 w-14 items-center justify-center rounded-full bg-zinc-800 mb-4">
            <svg class="h-7 w-7 text-zinc-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1">
                <path stroke-linecap="round" stroke-linejoin="round" d="M21 7.5l-9-5.25L3 7.5m18 0l-9 5.25m9-5.25v9l-9 5.25M3 7.5l9 5.25M3 7.5v9l9 5.25m0-9v9"/>
            </svg>
        </div>
        <h3 class="text-base font-medium text-zinc-400 mb-1">Keine Container gefunden</h3>
        <p class="text-sm text-zinc-600 mb-4">SSH-Zugangsdaten konfigurieren und ersten Sync starten.</p>
        <a href="{{ route('servers.index') }}" class="text-sm text-blue-400 hover:text-blue-300 transition-colors">Zu den Servern →</a>
    </div>
@else
    <div class="space-y-5">
        @foreach ($byServer as $serverId => $serverContainers)
            @php $server = $serverContainers->first()->server; @endphp
            <div
                x-data="{
                    containers: {{ Js::from($serverContainers->map(fn($c) => [
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
                        'ports'           => $c->ports ?? [],
                    ])->values()) }},
                    openGroups: {},
                    syncing: false,
                    csrfToken: document.querySelector('meta[name=csrf-token]').content,

                    get running()    { return this.containers.filter(c => c.state === 'running').length },

                    get groups() {
                        const map = {};
                        for (const c of this.containers) {
                            const key = c.name.match(/^([^_-]+)/)?.[1] ?? c.name;
                            if (!map[key]) map[key] = [];
                            map[key].push(c);
                        }
                        return Object.entries(map)
                            .sort(([a], [b]) => a.localeCompare(b))
                            .map(([name, items]) => ({
                                name,
                                containers: items,
                                running: items.filter(c => c.state === 'running').length,
                                total: items.length,
                            }));
                    },

                    isOpen(name) { return this.openGroups[name] !== false; },
                    toggle(name) { this.openGroups[name] = !this.isOpen(name); },

                    async refresh() {
                        try {
                            const r = await fetch('{{ route('servers.docker.status-json', $server) }}', { headers:{'Accept':'application/json'} });
                            const d = await r.json();
                            this.containers = d.containers;
                        } catch {}
                    },
                    async syncNow() {
                        this.syncing = true;
                        try {
                            await fetch('{{ route('servers.docker.sync', $server) }}', { method:'POST', headers:{'X-CSRF-TOKEN':this.csrfToken,'Accept':'application/json'} });
                            setTimeout(() => { this.refresh(); this.syncing = false; }, 3000);
                        } catch { this.syncing = false; }
                    },
                    stateColor(s) {
                        return { running:'border-green-800/60 bg-green-900/30 text-green-400', exited:'border-red-800/60 bg-red-900/30 text-red-400', paused:'border-yellow-800/60 bg-yellow-900/30 text-yellow-400', restarting:'border-blue-800/60 bg-blue-900/30 text-blue-400', dead:'border-red-900 bg-red-900/40 text-red-500', created:'bg-zinc-800 border-zinc-700 text-zinc-400' }[s] ?? 'bg-zinc-800 border-zinc-700 text-zinc-400';
                    },
                }"
                x-init="setInterval(() => refresh(), 30000)"
                class="rounded-2xl border border-zinc-800 bg-zinc-900 overflow-hidden"
            >
                {{-- Panel header --}}
                <div class="flex items-center justify-between px-5 py-4 border-b border-zinc-800/60 bg-zinc-950/30">
                    <div class="flex items-center gap-3">
                        <x-status-dot :status="$server->status" />
                        <div>
                            <a href="{{ route('servers.show', $server) }}"
                               class="text-sm font-semibold text-zinc-100 hover:text-blue-400 transition-colors">
                                {{ $server->name }}
                            </a>
                            <p class="text-xs text-zinc-600 font-mono">{{ $server->hostname }}</p>
                        </div>
                    </div>
                    <div class="flex items-center gap-3">
                        <span class="text-xs text-zinc-600">
                            <span class="text-green-400 font-medium" x-text="running"></span>
                            / <span x-text="containers.length"></span> running
                        </span>
                        <button @click="syncNow()" :disabled="syncing"
                                class="inline-flex items-center gap-1.5 rounded-lg border border-zinc-700 bg-zinc-800 px-3 py-1.5 text-xs font-medium text-zinc-300 hover:bg-zinc-700 disabled:opacity-50 disabled:cursor-not-allowed transition-colors">
                            <svg class="h-3.5 w-3.5" :class="{'animate-spin':syncing}" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0l3.181 3.183a8.25 8.25 0 0013.803-3.7M4.031 9.865a8.25 8.25 0 0113.803-3.7l3.181 3.182m0-4.991v4.99"/>
                            </svg>
                            <span x-text="syncing ? 'Sync…' : 'Sync'">Sync</span>
                        </button>
                        <a href="{{ route('servers.docker.index', $server) }}"
                           class="text-xs text-blue-400 hover:text-blue-300 transition-colors">Details →</a>
                    </div>
                </div>

                {{-- Grouped container list --}}
                <template x-if="containers.length > 0">
                    <div class="divide-y divide-zinc-800/40">
                        <template x-for="group in groups" :key="group.name">
                            <div>
                                {{-- Group row --}}
                                <button
                                    @click="toggle(group.name)"
                                    class="w-full flex items-center justify-between px-5 py-3 hover:bg-zinc-800/40 transition-colors"
                                >
                                    <div class="flex items-center gap-2.5">
                                        <span class="h-1.5 w-1.5 rounded-full shrink-0"
                                              :class="group.running === group.total
                                                  ? 'bg-green-500 shadow-[0_0_4px_theme(colors.green.500)]'
                                                  : group.running > 0 ? 'bg-yellow-500' : 'bg-zinc-600'">
                                        </span>
                                        <span class="text-xs font-semibold font-mono text-zinc-200" x-text="group.name"></span>
                                        <span class="rounded-full border border-zinc-700 bg-zinc-800 px-1.5 py-0.5 text-xs text-zinc-500"
                                              x-text="group.total + ' Container'"></span>
                                    </div>
                                    <div class="flex items-center gap-3">
                                        <span class="text-xs text-zinc-600">
                                            <span class="text-green-400" x-text="group.running"></span>/<span x-text="group.total"></span> running
                                        </span>
                                        <svg class="h-3.5 w-3.5 text-zinc-600 transition-transform duration-200"
                                             :class="isOpen(group.name) ? 'rotate-180' : ''"
                                             fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/>
                                        </svg>
                                    </div>
                                </button>

                                {{-- Container rows inside group --}}
                                <div x-show="isOpen(group.name)" x-collapse>
                                    <table class="w-full text-sm">
                                        <tbody class="divide-y divide-zinc-800/30">
                                            <template x-for="c in group.containers" :key="c.container_id">
                                                <tr class="hover:bg-zinc-800/20 transition-colors">
                                                    <td class="pl-10 pr-4 py-2.5">
                                                        <div class="flex items-center gap-2">
                                                            <span class="h-1.5 w-1.5 rounded-full shrink-0"
                                                                  :class="c.state==='running'?'bg-green-500 shadow-[0_0_4px_theme(colors.green.500)]':'bg-zinc-600'"></span>
                                                            <span class="text-xs font-mono text-zinc-300" x-text="c.name"></span>
                                                        </div>
                                                    </td>
                                                    <td class="px-4 py-2.5 hidden md:table-cell">
                                                        <span class="text-xs font-mono text-zinc-600 truncate max-w-40 block" x-text="c.image"></span>
                                                    </td>
                                                    <td class="px-4 py-2.5">
                                                        <span class="inline-flex items-center rounded-full border px-2 py-0.5 text-xs font-medium capitalize"
                                                              :class="stateColor(c.state)" x-text="c.state"></span>
                                                    </td>
                                                    <td class="px-4 py-2.5 hidden sm:table-cell w-32">
                                                        <template x-if="c.cpu_percent !== null">
                                                            <div class="space-y-0.5">
                                                                <div class="flex justify-between text-xs">
                                                                    <span class="text-zinc-600">CPU</span>
                                                                    <span :class="c.cpu_percent>=80?'text-red-400':c.cpu_percent>=50?'text-yellow-400':'text-zinc-400'" x-text="c.cpu_percent+'%'"></span>
                                                                </div>
                                                                <div class="h-1 w-full rounded-full bg-zinc-800 overflow-hidden">
                                                                    <div class="h-1 rounded-full" :class="c.cpu_percent>=80?'bg-red-500':c.cpu_percent>=50?'bg-yellow-500':'bg-blue-500'" :style="`width:${Math.min(100,c.cpu_percent)}%`"></div>
                                                                </div>
                                                            </div>
                                                        </template>
                                                        <template x-if="c.cpu_percent===null"><span class="text-xs text-zinc-700">—</span></template>
                                                    </td>
                                                    <td class="px-4 py-2.5 hidden sm:table-cell w-32">
                                                        <template x-if="c.memory_usage_mb !== null">
                                                            <div class="space-y-0.5">
                                                                <div class="flex justify-between text-xs">
                                                                    <span class="text-zinc-600">RAM</span>
                                                                    <span class="text-zinc-400" x-text="c.memory_usage_mb+'MB'"></span>
                                                                </div>
                                                                <div class="h-1 w-full rounded-full bg-zinc-800 overflow-hidden">
                                                                    <div class="h-1 rounded-full" :class="(c.memory_percent??0)>=85?'bg-red-500':(c.memory_percent??0)>=60?'bg-yellow-500':'bg-green-500'" :style="`width:${Math.min(100,c.memory_percent??0)}%`"></div>
                                                                </div>
                                                            </div>
                                                        </template>
                                                        <template x-if="c.memory_usage_mb===null"><span class="text-xs text-zinc-700">—</span></template>
                                                    </td>
                                                    <td class="px-4 py-2.5 hidden lg:table-cell">
                                                        <template x-if="c.ports && c.ports.length > 0">
                                                            <div class="flex flex-wrap gap-1">
                                                                <template x-for="p in c.ports.slice(0,3)" :key="p.host+p.container">
                                                                    <span class="rounded-md border border-zinc-700 bg-zinc-800 px-1.5 py-0.5 text-xs font-mono text-zinc-400" x-text="`${p.host}:${p.container}`"></span>
                                                                </template>
                                                                <template x-if="c.ports.length>3">
                                                                    <span class="rounded-md border border-zinc-700 bg-zinc-800 px-1.5 py-0.5 text-xs text-zinc-600" x-text="`+${c.ports.length-3}`"></span>
                                                                </template>
                                                            </div>
                                                        </template>
                                                        <template x-if="!c.ports||c.ports.length===0"><span class="text-xs text-zinc-700">—</span></template>
                                                    </td>
                                                </tr>
                                            </template>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </template>
                    </div>
                </template>

                <template x-if="containers.length===0">
                    <div class="py-8 text-center">
                        <p class="text-sm text-zinc-600">Keine Container. Docker läuft auf diesem Server möglicherweise nicht.</p>
                    </div>
                </template>
            </div>
        @endforeach
    </div>
@endif

</x-layouts.app>
