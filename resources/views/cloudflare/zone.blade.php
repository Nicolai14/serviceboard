<x-layouts.app :title="$zone->name . ' — DNS'">

    {{-- Page header --}}
    <div class="mb-6 flex items-start justify-between gap-4">
        <div>
            <a href="{{ route('cloudflare.index') }}" class="text-sm text-zinc-500 hover:text-zinc-300 transition-colors">
                ← Cloudflare
            </a>
            <div class="mt-2 flex items-center gap-3">
                <div class="flex h-8 w-8 items-center justify-center rounded-lg bg-orange-500/10 border border-orange-500/20 shrink-0">
                    <svg class="h-4 w-4 text-orange-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 21a9.004 9.004 0 008.716-6.747M12 21a9.004 9.004 0 01-8.716-6.747M12 21c2.485 0 4.5-4.03 4.5-9S14.485 3 12 3m0 18c-2.485 0-4.5-4.03-4.5-9S9.515 3 12 3m0 0a8.997 8.997 0 017.843 4.582M12 3a8.997 8.997 0 00-7.843 4.582"/>
                    </svg>
                </div>
                <h1 class="text-xl font-bold text-white">{{ $zone->name }}</h1>
                @php
                    $zoneStatusColor = match($zone->status) {
                        'active'   => 'border-green-800 bg-green-900/30 text-green-400',
                        'pending'  => 'border-yellow-800 bg-yellow-900/30 text-yellow-400',
                        default    => 'border-zinc-700 bg-zinc-800 text-zinc-400',
                    };
                @endphp
                <span class="inline-flex items-center rounded-full border px-2.5 py-0.5 text-xs font-medium {{ $zoneStatusColor }}">
                    {{ $zone->status }}{{ $zone->paused ? ' · paused' : '' }}
                </span>
            </div>
            <p class="mt-0.5 text-sm text-zinc-500">
                {{ $zone->plan_name ?? 'Free Plan' }} · {{ ucfirst($zone->type) }} Zone
                · Token: {{ $zone->cloudflareToken->name }}
            </p>
        </div>
    </div>

    {{-- Zone info + nameservers --}}
    <div class="mb-6 grid grid-cols-1 sm:grid-cols-3 gap-4">
        <div class="rounded-xl border border-zinc-800 bg-zinc-900 p-5">
            <p class="text-xs text-zinc-500 mb-2">DNS Records</p>
            <p class="text-3xl font-bold text-orange-400">{{ number_format($summary['total']) }}</p>
            <p class="text-xs text-zinc-600 mt-1">{{ $summary['proxied'] }} proxied</p>
        </div>

        <div class="rounded-xl border border-zinc-800 bg-zinc-900 p-5">
            <p class="text-xs text-zinc-500 mb-2">Record-Typen</p>
            <div class="flex flex-wrap gap-1.5">
                @foreach ($summary['by_type'] as $type => $count)
                    <span class="rounded-md px-2 py-0.5 text-xs font-mono font-medium
                        @if($type === 'A' || $type === 'AAAA') bg-blue-900/30 border border-blue-800 text-blue-400
                        @elseif($type === 'CNAME') bg-purple-900/30 border border-purple-800 text-purple-400
                        @elseif($type === 'MX') bg-orange-900/30 border border-orange-800 text-orange-400
                        @elseif($type === 'TXT') bg-green-900/30 border border-green-800 text-green-400
                        @elseif($type === 'NS') bg-zinc-800 border border-zinc-700 text-zinc-400
                        @else bg-zinc-800 border border-zinc-700 text-zinc-400 @endif">
                        {{ $type }} <span class="opacity-60">{{ $count }}</span>
                    </span>
                @endforeach
            </div>
        </div>

        <div class="rounded-xl border border-zinc-800 bg-zinc-900 p-5">
            <p class="text-xs text-zinc-500 mb-2">Nameserver</p>
            @if ($zone->name_servers)
                <ul class="space-y-0.5">
                    @foreach (array_slice($zone->name_servers, 0, 3) as $ns)
                        <li class="text-xs font-mono text-zinc-300">{{ $ns }}</li>
                    @endforeach
                </ul>
            @else
                <p class="text-xs text-zinc-600">Nicht verfügbar</p>
            @endif
        </div>
    </div>

    {{-- DNS Records table (Alpine.js live) --}}
    <div
        x-data="{
            records: {{ Js::from($records->map(fn($r) => [
                'id'          => $r->id,
                'type'        => $r->type,
                'name'        => $r->name,
                'content'     => $r->content,
                'proxied'     => $r->proxied,
                'proxiable'   => $r->proxiable,
                'ttl_label'   => $r->ttl === 1 ? 'Auto' : $r->ttl . 's',
                'priority'    => $r->priority,
                'comment'     => $r->comment,
                'modified_on' => $r->modified_on?->diffForHumans(),
            ])->values()) }},
            syncing: false,
            syncMsg: null,
            search: '',
            filterType: 'all',
            csrfToken: document.querySelector('meta[name=csrf-token]').content,

            get filtered() {
                let r = this.records;
                if (this.filterType !== 'all') r = r.filter(x => x.type === this.filterType);
                if (this.search.trim()) {
                    const q = this.search.toLowerCase();
                    r = r.filter(x => x.name.toLowerCase().includes(q) || x.content.toLowerCase().includes(q));
                }
                return r;
            },

            get types() {
                return [...new Set(this.records.map(r => r.type))].sort();
            },

            async refresh() {
                try {
                    const res  = await fetch('{{ route('cloudflare.zones.status-json', $zone) }}', { headers:{'Accept':'application/json'} });
                    const data = await res.json();
                    this.records = data.records;
                } catch {}
            },

            async syncNow() {
                this.syncing = true; this.syncMsg = null;
                try {
                    const res  = await fetch('{{ route('cloudflare.zones.sync-dns', $zone) }}', {
                        method: 'POST',
                        headers: { 'X-CSRF-TOKEN': this.csrfToken, 'Accept': 'application/json' }
                    });
                    const data = await res.json();
                    this.syncMsg = data.message;
                    if (data.dispatched) { setTimeout(() => { this.refresh(); this.syncing = false; }, 3500); }
                    else { this.syncing = false; }
                } catch { this.syncing = false; }
            },

            typeColor(type) {
                const map = {
                    A:     'bg-blue-900/30 border-blue-800 text-blue-400',
                    AAAA:  'bg-blue-900/30 border-blue-800 text-blue-400',
                    CNAME: 'bg-purple-900/30 border-purple-800 text-purple-400',
                    MX:    'bg-orange-900/30 border-orange-800 text-orange-400',
                    TXT:   'bg-green-900/30 border-green-800 text-green-400',
                    NS:    'bg-zinc-800 border-zinc-700 text-zinc-400',
                    SRV:   'bg-yellow-900/30 border-yellow-800 text-yellow-400',
                    CAA:   'bg-pink-900/30 border-pink-800 text-pink-400',
                };
                return map[type] ?? 'bg-zinc-800 border-zinc-700 text-zinc-400';
            },
        }"
        x-init="setInterval(() => refresh(), 30000)"
    >
        {{-- Toolbar --}}
        <div class="mb-4 flex flex-wrap items-center gap-3">
            {{-- Search --}}
            <div class="relative flex-1 min-w-48">
                <svg class="pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 h-4 w-4 text-zinc-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                </svg>
                <input x-model="search" type="text" placeholder="Name oder Inhalt suchen…"
                       class="w-full rounded-lg border border-zinc-700 bg-zinc-900 pl-9 pr-3 py-2 text-sm text-zinc-100 placeholder-zinc-600
                              focus:border-orange-500 focus:outline-none focus:ring-1 focus:ring-orange-500">
            </div>

            {{-- Type filter --}}
            <div class="flex gap-1 flex-wrap">
                <button @click="filterType = 'all'"
                        :class="filterType === 'all' ? 'bg-orange-600 text-white border-orange-600' : 'bg-zinc-900 text-zinc-400 border-zinc-700 hover:bg-zinc-800'"
                        class="rounded-lg border px-3 py-2 text-xs font-medium transition-colors">
                    Alle <span x-text="'(' + records.length + ')'"></span>
                </button>
                <template x-for="t in types" :key="t">
                    <button @click="filterType = t"
                            :class="filterType === t ? 'bg-orange-600 text-white border-orange-600' : 'bg-zinc-900 text-zinc-400 border-zinc-700 hover:bg-zinc-800'"
                            class="rounded-lg border px-3 py-2 text-xs font-mono font-medium transition-colors"
                            x-text="t">
                    </button>
                </template>
            </div>

            <div class="ml-auto flex items-center gap-3">
                <template x-if="syncMsg">
                    <span class="text-xs text-blue-400" x-text="syncMsg"></span>
                </template>
                <button @click="syncNow()" :disabled="syncing"
                        class="inline-flex items-center gap-2 rounded-lg bg-orange-600 px-4 py-2 text-sm font-medium text-white
                               hover:bg-orange-500 disabled:opacity-50 disabled:cursor-not-allowed transition-colors">
                    <svg class="h-4 w-4" :class="{'animate-spin': syncing}" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0l3.181 3.183a8.25 8.25 0 0013.803-3.7M4.031 9.865a8.25 8.25 0 0113.803-3.7l3.181 3.182m0-4.991v4.99"/>
                    </svg>
                    <span x-text="syncing ? 'Sync…' : 'DNS Sync'">DNS Sync</span>
                </button>
            </div>
        </div>

        {{-- Empty state --}}
        <template x-if="records.length === 0">
            <div class="flex flex-col items-center justify-center rounded-xl border border-dashed border-zinc-800 py-16 text-center">
                <p class="text-sm text-zinc-500">Keine DNS-Records gefunden.</p>
                <p class="text-xs text-zinc-700 mt-1">Klicke auf „DNS Sync" um Records von Cloudflare abzurufen.</p>
            </div>
        </template>

        {{-- DNS table --}}
        <template x-if="records.length > 0">
            <div class="rounded-xl border border-zinc-800 bg-zinc-900 overflow-hidden">
                {{-- No results after filter --}}
                <template x-if="filtered.length === 0">
                    <div class="py-12 text-center">
                        <p class="text-sm text-zinc-600">Keine Records für diesen Filter.</p>
                    </div>
                </template>

                <template x-if="filtered.length > 0">
                    <div class="overflow-x-auto">
                        <table class="w-full text-sm">
                            <thead>
                                <tr class="border-b border-zinc-800/60">
                                    <th class="px-5 py-2.5 text-left text-xs font-medium text-zinc-500 w-20">Typ</th>
                                    <th class="px-4 py-2.5 text-left text-xs font-medium text-zinc-500">Name</th>
                                    <th class="px-4 py-2.5 text-left text-xs font-medium text-zinc-500">Inhalt</th>
                                    <th class="px-4 py-2.5 text-left text-xs font-medium text-zinc-500 w-20">Proxy</th>
                                    <th class="px-4 py-2.5 text-left text-xs font-medium text-zinc-500 w-16">TTL</th>
                                    <th class="px-4 py-2.5 text-left text-xs font-medium text-zinc-500 w-16">Prio</th>
                                    <th class="px-4 py-2.5 text-left text-xs font-medium text-zinc-500">Geändert</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-zinc-800/60">
                                <template x-for="r in filtered" :key="r.id">
                                    <tr class="hover:bg-zinc-800/30 transition-colors group">
                                        {{-- Type badge --}}
                                        <td class="px-5 py-3">
                                            <span class="inline-flex items-center rounded-md border px-2 py-0.5 text-xs font-mono font-semibold"
                                                  :class="typeColor(r.type)"
                                                  x-text="r.type">
                                            </span>
                                        </td>
                                        {{-- Name --}}
                                        <td class="px-4 py-3 max-w-48">
                                            <span class="font-mono text-xs text-zinc-200 truncate block" x-text="r.name" :title="r.name"></span>
                                            <template x-if="r.comment">
                                                <span class="text-xs text-zinc-600 truncate block" x-text="r.comment"></span>
                                            </template>
                                        </td>
                                        {{-- Content --}}
                                        <td class="px-4 py-3 max-w-xs">
                                            <span class="font-mono text-xs text-zinc-400 break-all" x-text="r.content"></span>
                                        </td>
                                        {{-- Proxy status --}}
                                        <td class="px-4 py-3">
                                            <template x-if="r.proxiable">
                                                <div class="flex items-center gap-1.5">
                                                    {{-- Orange cloud = proxied, gray = DNS only --}}
                                                    <template x-if="r.proxied">
                                                        <span title="Proxied (Cloudflare)" class="flex items-center gap-1 text-xs text-orange-400 font-medium">
                                                            <svg class="h-4 w-4" viewBox="0 0 24 24" fill="currentColor">
                                                                <path d="M18.75 12.75h1.5a.75.75 0 000-1.5h-1.5a.75.75 0 000 1.5zM12 6a.75.75 0 01.75-.75h7.5a.75.75 0 010 1.5h-7.5A.75.75 0 0112 6zM12 18a.75.75 0 01.75-.75h7.5a.75.75 0 010 1.5h-7.5A.75.75 0 0112 18zM3.75 6.75h1.5a.75.75 0 100-1.5h-1.5a.75.75 0 000 1.5zM5.25 18.75h-1.5a.75.75 0 010-1.5h1.5a.75.75 0 010 1.5zM3 12a.75.75 0 01.75-.75h7.5a.75.75 0 010 1.5h-7.5A.75.75 0 013 12zM9 3.75a2.25 2.25 0 100 4.5 2.25 2.25 0 000-4.5zM12.75 12a2.25 2.25 0 114.5 0 2.25 2.25 0 01-4.5 0zM9 15.75a2.25 2.25 0 100 4.5 2.25 2.25 0 000-4.5z"/>
                                                            </svg>
                                                            On
                                                        </span>
                                                    </template>
                                                    <template x-if="!r.proxied">
                                                        <span title="DNS Only" class="flex items-center gap-1 text-xs text-zinc-500">
                                                            <svg class="h-4 w-4" viewBox="0 0 24 24" fill="currentColor">
                                                                <path d="M18.75 12.75h1.5a.75.75 0 000-1.5h-1.5a.75.75 0 000 1.5zM12 6a.75.75 0 01.75-.75h7.5a.75.75 0 010 1.5h-7.5A.75.75 0 0112 6zM12 18a.75.75 0 01.75-.75h7.5a.75.75 0 010 1.5h-7.5A.75.75 0 0112 18zM3.75 6.75h1.5a.75.75 0 100-1.5h-1.5a.75.75 0 000 1.5zM5.25 18.75h-1.5a.75.75 0 010-1.5h1.5a.75.75 0 010 1.5zM3 12a.75.75 0 01.75-.75h7.5a.75.75 0 010 1.5h-7.5A.75.75 0 013 12zM9 3.75a2.25 2.25 0 100 4.5 2.25 2.25 0 000-4.5zM12.75 12a2.25 2.25 0 114.5 0 2.25 2.25 0 01-4.5 0zM9 15.75a2.25 2.25 0 100 4.5 2.25 2.25 0 000-4.5z"/>
                                                            </svg>
                                                            Off
                                                        </span>
                                                    </template>
                                                </div>
                                            </template>
                                            <template x-if="!r.proxiable">
                                                <span class="text-xs text-zinc-700">—</span>
                                            </template>
                                        </td>
                                        {{-- TTL --}}
                                        <td class="px-4 py-3">
                                            <span class="text-xs text-zinc-500 font-mono" x-text="r.ttl_label"></span>
                                        </td>
                                        {{-- Priority --}}
                                        <td class="px-4 py-3">
                                            <template x-if="r.priority !== null">
                                                <span class="text-xs text-zinc-400 font-mono" x-text="r.priority"></span>
                                            </template>
                                            <template x-if="r.priority === null">
                                                <span class="text-xs text-zinc-700">—</span>
                                            </template>
                                        </td>
                                        {{-- Modified --}}
                                        <td class="px-4 py-3">
                                            <span class="text-xs text-zinc-600" x-text="r.modified_on ?? '—'"></span>
                                        </td>
                                    </tr>
                                </template>
                            </tbody>
                        </table>
                    </div>
                </template>

                {{-- Footer with total --}}
                <div class="flex items-center justify-between border-t border-zinc-800/60 px-5 py-3">
                    <span class="text-xs text-zinc-600">
                        <span x-text="filtered.length"></span> von <span x-text="records.length"></span> Records
                    </span>
                    <span class="text-xs text-zinc-700">Zuletzt sync: {{ $zone->synced_at?->diffForHumans() ?? '—' }}</span>
                </div>
            </div>
        </template>
    </div>
</x-layouts.app>
