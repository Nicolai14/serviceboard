<x-layouts.app title="DNS Records">

{{-- Header --}}
<div class="flex items-start justify-between gap-4 mb-6">
    <div>
        <a href="{{ route('cloudflare.index') }}" class="text-xs text-zinc-600 hover:text-zinc-400 transition-colors">← Domains</a>
        <h1 class="text-xl font-bold text-white mt-1">DNS Records</h1>
        <p class="text-sm text-zinc-500 mt-0.5">{{ number_format($totalRecords) }} Records über alle Zonen</p>
    </div>
</div>

{{-- Filter toolbar --}}
<form method="GET" class="mb-6 flex flex-wrap gap-3">
    {{-- Search --}}
    <div class="relative flex-1 min-w-52">
        <svg class="pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 h-4 w-4 text-zinc-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
        </svg>
        <input type="text" name="search" value="{{ request('search') }}"
               placeholder="Name oder Inhalt suchen…"
               class="w-full rounded-xl border border-zinc-700 bg-zinc-900 pl-9 pr-3 py-2.5 text-sm text-zinc-100 placeholder-zinc-600 focus:border-orange-500 focus:outline-none focus:ring-1 focus:ring-orange-500">
    </div>

    {{-- Zone filter --}}
    <select name="zone_id"
            class="rounded-xl border border-zinc-700 bg-zinc-900 px-3 py-2.5 text-sm text-zinc-300 focus:border-orange-500 focus:outline-none focus:ring-1 focus:ring-orange-500">
        <option value="">Alle Zonen</option>
        @foreach ($zones as $zone)
            <option value="{{ $zone->id }}" @selected(request('zone_id') == $zone->id)>{{ $zone->name }}</option>
        @endforeach
    </select>

    {{-- Type filter --}}
    <select name="type"
            class="rounded-xl border border-zinc-700 bg-zinc-900 px-3 py-2.5 text-sm text-zinc-300 font-mono focus:border-orange-500 focus:outline-none focus:ring-1 focus:ring-orange-500">
        <option value="">Alle Typen</option>
        @foreach ($types as $t)
            <option value="{{ $t }}" @selected(request('type') === $t)>{{ $t }}</option>
        @endforeach
    </select>

    {{-- Proxy filter --}}
    <select name="proxied"
            class="rounded-xl border border-zinc-700 bg-zinc-900 px-3 py-2.5 text-sm text-zinc-300 focus:border-orange-500 focus:outline-none focus:ring-1 focus:ring-orange-500">
        <option value="">Proxy: Alle</option>
        <option value="1" @selected(request('proxied') === '1')>Proxied</option>
        <option value="0" @selected(request('proxied') === '0')>DNS only</option>
    </select>

    <button type="submit"
            class="rounded-xl border border-zinc-700 bg-zinc-800 px-4 py-2.5 text-sm font-medium text-zinc-300 hover:bg-zinc-700 hover:text-zinc-100 transition-colors">
        Filtern
    </button>

    @if (request('search') || request('zone_id') || request('type') || request('proxied') !== null && request('proxied') !== '')
        <a href="{{ route('cloudflare.dns') }}"
           class="rounded-xl border border-zinc-800 px-3 py-2.5 text-sm text-zinc-600 hover:text-zinc-400 transition-colors">
            Zurücksetzen
        </a>
    @endif
</form>

{{-- Empty state --}}
@if ($records->isEmpty())
    <div class="flex flex-col items-center justify-center rounded-2xl border border-dashed border-zinc-800 py-20 text-center">
        <div class="flex h-14 w-14 items-center justify-center rounded-full bg-zinc-800 mb-4">
            <svg class="h-7 w-7 text-zinc-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1">
                <path stroke-linecap="round" stroke-linejoin="round" d="M8.288 15.038a5.25 5.25 0 017.424 0M5.106 11.856c3.807-3.808 9.98-3.808 13.788 0M1.924 8.674c5.565-5.565 14.587-5.565 20.152 0M12.53 18.22l-.53.53-.53-.53a.75.75 0 011.06 0z"/>
            </svg>
        </div>
        <p class="text-base font-medium text-zinc-400 mb-1">Keine DNS Records gefunden</p>
        <p class="text-sm text-zinc-600">
            @if (request('search') || request('zone_id') || request('type'))
                Keine Records entsprechen dem Filter.
            @else
                Zonen synchronisieren um DNS Records abzurufen.
            @endif
        </p>
    </div>

@else
    {{-- DNS table --}}
    <div class="rounded-2xl border border-zinc-800 bg-zinc-900 overflow-hidden">
        <div class="flex items-center justify-between px-5 py-4 border-b border-zinc-800/60 bg-zinc-950/30">
            <span class="text-sm font-semibold text-zinc-100">
                {{ number_format($records->total()) }} Records
                @if (request('zone_id') || request('type') || request('search') || request('proxied') !== '')
                    <span class="text-zinc-600 font-normal">(gefiltert)</span>
                @endif
            </span>
            <span class="text-xs text-zinc-600">Seite {{ $records->currentPage() }} / {{ $records->lastPage() }}</span>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="border-b border-zinc-800/40">
                        <th class="px-5 py-2.5 text-left text-xs font-semibold uppercase tracking-wider text-zinc-500 w-20">Typ</th>
                        <th class="px-4 py-2.5 text-left text-xs font-semibold uppercase tracking-wider text-zinc-500">Name</th>
                        <th class="px-4 py-2.5 text-left text-xs font-semibold uppercase tracking-wider text-zinc-500">Inhalt</th>
                        <th class="px-4 py-2.5 text-left text-xs font-semibold uppercase tracking-wider text-zinc-500 hidden md:table-cell w-24">Proxy</th>
                        <th class="px-4 py-2.5 text-left text-xs font-semibold uppercase tracking-wider text-zinc-500 hidden sm:table-cell w-16">TTL</th>
                        <th class="px-4 py-2.5 text-left text-xs font-semibold uppercase tracking-wider text-zinc-500 hidden lg:table-cell">Zone</th>
                        <th class="px-4 py-2.5 text-left text-xs font-semibold uppercase tracking-wider text-zinc-500 hidden xl:table-cell">Geändert</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-800/40">
                    @foreach ($records as $record)
                        @php
                            $typeCls = match($record->type) {
                                'A', 'AAAA' => 'border-blue-800/60 bg-blue-900/30 text-blue-400',
                                'CNAME'     => 'border-purple-800/60 bg-purple-900/30 text-purple-400',
                                'MX'        => 'border-orange-800/60 bg-orange-900/30 text-orange-400',
                                'TXT'       => 'border-green-800/60 bg-green-900/30 text-green-400',
                                'NS'        => 'border-zinc-700 bg-zinc-800 text-zinc-400',
                                'SRV'       => 'border-yellow-800/60 bg-yellow-900/30 text-yellow-400',
                                'CAA'       => 'border-pink-800/60 bg-pink-900/30 text-pink-400',
                                default     => 'border-zinc-700 bg-zinc-800 text-zinc-400',
                            };
                        @endphp
                        <tr class="hover:bg-zinc-800/30 transition-colors">
                            <td class="px-5 py-3">
                                <span class="inline-flex items-center rounded-md border px-2 py-0.5 text-xs font-mono font-semibold {{ $typeCls }}">
                                    {{ $record->type }}
                                </span>
                            </td>
                            <td class="px-4 py-3 max-w-xs">
                                <p class="text-xs font-mono font-medium text-zinc-200 truncate" title="{{ $record->name }}">
                                    {{ $record->name }}
                                </p>
                                @if ($record->comment)
                                    <p class="text-xs text-zinc-600 truncate">{{ $record->comment }}</p>
                                @endif
                            </td>
                            <td class="px-4 py-3 max-w-sm">
                                <span class="text-xs font-mono text-zinc-400 break-all line-clamp-2">{{ $record->content }}</span>
                            </td>
                            <td class="px-4 py-3 hidden md:table-cell">
                                @if ($record->proxiable)
                                    @if ($record->proxied)
                                        <span class="inline-flex items-center gap-1 rounded-full border border-orange-800/60 bg-orange-900/20 px-2 py-0.5 text-xs font-medium text-orange-400">
                                            <span class="block h-1.5 w-1.5 rounded-full bg-orange-500"></span>
                                            Proxied
                                        </span>
                                    @else
                                        <span class="text-xs text-zinc-600">DNS only</span>
                                    @endif
                                @else
                                    <span class="text-xs text-zinc-700">—</span>
                                @endif
                            </td>
                            <td class="px-4 py-3 hidden sm:table-cell">
                                <span class="text-xs font-mono text-zinc-500">{{ $record->ttl_label }}</span>
                            </td>
                            <td class="px-4 py-3 hidden lg:table-cell">
                                <a href="{{ route('cloudflare.zones.show', $record->zone) }}"
                                   class="text-xs text-zinc-400 hover:text-orange-400 transition-colors">
                                    {{ $record->zone->name ?? '—' }}
                                </a>
                            </td>
                            <td class="px-4 py-3 hidden xl:table-cell">
                                <span class="text-xs text-zinc-600">{{ $record->modified_on?->diffForHumans() ?? '—' }}</span>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        {{-- Pagination footer --}}
        @if ($records->hasPages())
            <div class="border-t border-zinc-800/60 px-5 py-3">
                {{ $records->links() }}
            </div>
        @endif
    </div>
@endif

</x-layouts.app>
