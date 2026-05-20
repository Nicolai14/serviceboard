<x-layouts.app title="Server">

{{-- Header --}}
<div class="flex items-start justify-between gap-3 mb-6">
    <div>
        <h1 class="text-xl font-bold text-white">Server</h1>
        <p class="text-sm text-zinc-500 mt-0.5">{{ $servers->total() }} Server verwaltet</p>
    </div>
    <a href="{{ route('servers.create') }}"
       class="inline-flex items-center gap-2 rounded-xl bg-blue-600 px-3 sm:px-4 py-2.5 text-sm font-semibold text-white hover:bg-blue-500 transition-colors shadow-[0_0_16px_theme(colors.blue.600/25%)] shrink-0">
        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
            <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/>
        </svg>
        <span class="hidden sm:inline">Server hinzufügen</span>
        <span class="sm:hidden">Neu</span>
    </a>
</div>

{{-- Filter bar --}}
<form method="GET" class="mb-6 flex flex-wrap gap-2 sm:gap-3">
    <div class="relative w-full sm:flex-1 sm:min-w-48 sm:max-w-xs">
        <svg class="pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 h-4 w-4 text-zinc-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
        </svg>
        <input type="text" name="search" value="{{ request('search') }}"
               placeholder="Name, Hostname oder IP…"
               class="w-full rounded-xl border border-zinc-700 bg-zinc-900 pl-9 pr-3 py-2.5 text-sm text-zinc-100 placeholder-zinc-600 focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500">
    </div>

    <div class="flex gap-2 sm:gap-3">
        <select name="status"
                class="flex-1 sm:flex-none rounded-xl border border-zinc-700 bg-zinc-900 px-3 py-2.5 text-sm text-zinc-300 focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500">
            <option value="">Alle Status</option>
            <option value="online"      @selected(request('status') === 'online')>Online</option>
            <option value="offline"     @selected(request('status') === 'offline')>Offline</option>
            <option value="maintenance" @selected(request('status') === 'maintenance')>Wartung</option>
        </select>

        <button type="submit"
                class="rounded-xl border border-zinc-700 bg-zinc-800 px-4 py-2.5 text-sm font-medium text-zinc-300 hover:bg-zinc-700 hover:text-zinc-100 transition-colors">
            Filtern
        </button>
        @if (request('search') || request('status'))
            <a href="{{ route('servers.index') }}"
               class="rounded-xl border border-zinc-800 px-3 py-2.5 text-sm text-zinc-600 hover:text-zinc-400 transition-colors">
                ✕
            </a>
        @endif
    </div>
</form>

{{-- Empty state --}}
@if ($servers->isEmpty())
    <div class="flex flex-col items-center justify-center rounded-2xl border border-dashed border-zinc-800 py-20 text-center">
        <div class="flex h-14 w-14 items-center justify-center rounded-full bg-zinc-800 mb-4">
            <svg class="h-7 w-7 text-zinc-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1">
                <path stroke-linecap="round" stroke-linejoin="round" d="M5 12h14M5 12a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v4a2 2 0 01-2 2M5 12a2 2 0 00-2 2v4a2 2 0 002 2h14a2 2 0 002-2v-4a2 2 0 00-2-2"/>
            </svg>
        </div>
        <h3 class="text-base font-medium text-zinc-400 mb-1">Keine Server gefunden</h3>
        <p class="text-sm text-zinc-600 mb-5">
            @if (request('search') || request('status'))
                Keine Server entsprechen dem aktuellen Filter.
            @else
                Füge deinen ersten Server hinzu um zu starten.
            @endif
        </p>
        @unless (request('search') || request('status'))
            <a href="{{ route('servers.create') }}"
               class="inline-flex items-center gap-2 rounded-xl bg-blue-600 px-4 py-2 text-sm font-medium text-white hover:bg-blue-500 transition-colors">
                Server hinzufügen
            </a>
        @endunless
    </div>

@else
    {{-- Server table --}}
    <div class="rounded-2xl border border-zinc-800 bg-zinc-900 overflow-hidden">
        <table class="w-full">
            <thead>
                <tr class="border-b border-zinc-800/60 bg-zinc-950/40">
                    <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wider text-zinc-500">Server</th>
                    <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wider text-zinc-500 hidden md:table-cell">Adresse</th>
                    <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wider text-zinc-500">Status</th>
                    <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wider text-zinc-500 hidden lg:table-cell">Services</th>
                    <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wider text-zinc-500 hidden lg:table-cell">Alerts</th>
                    <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wider text-zinc-500 hidden xl:table-cell">Zuletzt gesehen</th>
                    <th class="px-5 py-3"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-zinc-800/60">
                @foreach ($servers as $server)
                    @php
                        $statusMap = [
                            'online'      => ['ring' => 'bg-green-500/10 border-green-500/20', 'badge' => 'border-green-800/60 bg-green-900/30 text-green-400', 'dot' => 'bg-green-500 shadow-[0_0_6px_theme(colors.green.500)]'],
                            'offline'     => ['ring' => 'bg-red-500/10 border-red-500/20',     'badge' => 'border-red-800/60 bg-red-900/30 text-red-400',     'dot' => 'bg-red-500'],
                            'maintenance' => ['ring' => 'bg-yellow-500/10 border-yellow-500/20','badge' => 'border-yellow-800/60 bg-yellow-900/30 text-yellow-400','dot' => 'bg-yellow-500'],
                            'unknown'     => ['ring' => 'bg-zinc-800 border-zinc-700',          'badge' => 'border-zinc-700 bg-zinc-800 text-zinc-400',          'dot' => 'bg-zinc-500'],
                        ];
                        $s = $statusMap[$server->status] ?? $statusMap['unknown'];
                    @endphp
                    <tr class="hover:bg-zinc-800/30 transition-colors">
                        <td class="px-5 py-3.5">
                            <div class="flex items-center gap-3">
                                <div class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg border {{ $s['ring'] }}">
                                    <svg class="h-3.5 w-3.5 {{ $server->status === 'online' ? 'text-green-400' : ($server->status === 'offline' ? 'text-red-400' : 'text-zinc-500') }}" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M5 12h14M5 12a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v4a2 2 0 01-2 2M5 12a2 2 0 00-2 2v4a2 2 0 002 2h14a2 2 0 002-2v-4a2 2 0 00-2-2m-2-4h.01M17 16h.01"/>
                                    </svg>
                                </div>
                                <div class="min-w-0">
                                    <a href="{{ route('servers.show', $server) }}"
                                       class="block text-sm font-semibold text-zinc-100 hover:text-blue-400 transition-colors truncate">
                                        {{ $server->name }}
                                    </a>
                                    @if ($server->os)
                                        <p class="text-xs text-zinc-600 truncate">{{ $server->os }}</p>
                                    @endif
                                </div>
                            </div>
                        </td>
                        <td class="px-5 py-3.5 hidden md:table-cell">
                            <span class="font-mono text-xs text-zinc-400">{{ $server->ip_address ?? $server->hostname }}</span>
                        </td>
                        <td class="px-5 py-3.5">
                            <span class="inline-flex items-center gap-1.5 rounded-full border px-2.5 py-0.5 text-xs font-medium {{ $s['badge'] }}">
                                <span class="block h-1.5 w-1.5 rounded-full {{ $s['dot'] }}"></span>
                                {{ ucfirst($server->status) }}
                            </span>
                        </td>
                        <td class="px-5 py-3.5 hidden lg:table-cell">
                            <span class="text-sm text-zinc-400">{{ $server->services_count ?? $server->services->count() }}</span>
                        </td>
                        <td class="px-5 py-3.5 hidden lg:table-cell">
                            @php $ac = $server->alerts->count(); @endphp
                            @if ($ac > 0)
                                <span class="inline-flex items-center gap-1 text-xs font-medium text-amber-400">
                                    <span class="block h-1.5 w-1.5 rounded-full bg-amber-500"></span>{{ $ac }}
                                </span>
                            @else
                                <span class="text-xs text-zinc-700">—</span>
                            @endif
                        </td>
                        <td class="px-5 py-3.5 hidden xl:table-cell">
                            <span class="text-xs text-zinc-600">{{ $server->last_seen_at?->diffForHumans() ?? '—' }}</span>
                        </td>
                        <td class="px-5 py-3.5 text-right">
                            <div class="flex items-center justify-end gap-2">
                                <a href="{{ route('servers.edit', $server) }}"
                                   class="rounded-lg border border-zinc-800 bg-zinc-900 p-1.5 text-zinc-600 hover:text-zinc-300 hover:border-zinc-700 transition-colors">
                                    <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L10.582 16.07a4.5 4.5 0 01-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 011.13-1.897l8.932-8.931z"/>
                                    </svg>
                                </a>
                                <a href="{{ route('servers.show', $server) }}"
                                   class="rounded-lg border border-zinc-800 bg-zinc-900 p-1.5 text-zinc-600 hover:text-blue-400 hover:border-blue-800 transition-colors">
                                    <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 4.5l7.5 7.5-7.5 7.5"/>
                                    </svg>
                                </a>
                            </div>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    @if ($servers->hasPages())
        <div class="mt-5">
            {{ $servers->withQueryString()->links() }}
        </div>
    @endif
@endif

</x-layouts.app>
