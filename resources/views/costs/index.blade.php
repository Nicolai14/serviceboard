<x-layouts.app title="Kosten">
    @php
        $fmt = fn ($v) => number_format((float) $v, 2, ',', '.') . ' €';
        $groupMeta = [
            'server' => ['label' => 'Server', 'empty' => 'Keine Server in diesem Workspace.'],
            'domain' => ['label' => 'Domains', 'empty' => 'Keine Domains vorhanden.'],
            'manual' => ['label' => 'Sonstige Posten', 'empty' => 'Noch keine eigenen Posten.'],
        ];
    @endphp

    <div class="max-w-5xl">

        {{-- Header --}}
        <div class="mb-6">
            <h2 class="text-lg font-semibold text-zinc-100">Kostenübersicht</h2>
            <p class="text-sm text-zinc-500 mt-1">
                Alle Server und Domains werden automatisch gelistet — trag die monatlichen Preise ein.
                <span class="text-zinc-600">({{ $workspace->type->icon() }} {{ $workspace->name }})</span>
            </p>
        </div>

        {{-- Summary cards --}}
        <div class="grid grid-cols-2 gap-3 sm:grid-cols-4 mb-6">
            <div class="rounded-xl border border-blue-600/20 bg-blue-600/10 p-4">
                <p class="text-xs font-medium uppercase tracking-wide text-blue-400/80">Monatlich gesamt</p>
                <p class="mt-1.5 text-2xl font-bold text-blue-300">{{ $fmt($grand_total) }}</p>
            </div>
            <div class="rounded-xl border border-zinc-800 bg-zinc-900 p-4">
                <p class="text-xs font-medium uppercase tracking-wide text-zinc-500">Jährlich gesamt</p>
                <p class="mt-1.5 text-2xl font-bold text-zinc-100">{{ $fmt($grand_total * 12) }}</p>
            </div>
            <div class="rounded-xl border border-zinc-800 bg-zinc-900 p-4">
                <p class="text-xs font-medium uppercase tracking-wide text-zinc-500">Server / Domains</p>
                <p class="mt-1.5 text-sm font-semibold text-zinc-300">
                    {{ $fmt($totals['server']) }} <span class="text-zinc-600">·</span> {{ $fmt($totals['domain']) }}
                </p>
            </div>
            <div class="rounded-xl border border-zinc-800 bg-zinc-900 p-4">
                <p class="text-xs font-medium uppercase tracking-wide text-zinc-500">Erfasst</p>
                <p class="mt-1.5 text-sm font-semibold text-zinc-300">
                    {{ $priced_count }} <span class="text-zinc-600">/ {{ $priced_count + $unpriced_count }} Posten</span>
                </p>
            </div>
        </div>

        @if (session('errors') ?? false)
            {{-- handled globally --}}
        @endif

        {{-- ---------------------------------------------------------------- --}}
        {{-- Bulk price form                                                   --}}
        {{-- ---------------------------------------------------------------- --}}
        <form method="POST" action="{{ route('costs.update') }}">
            @csrf
            @method('PATCH')

            @foreach (['server', 'domain', 'manual'] as $key)
                @php $items = $groups[$key]; @endphp
                <div class="rounded-xl border border-zinc-800 bg-zinc-900 mb-5 overflow-hidden">
                    <div class="flex items-center justify-between px-5 py-3 border-b border-zinc-800">
                        <h3 class="text-sm font-semibold text-zinc-100">
                            {{ $groupMeta[$key]['label'] }}
                            <span class="text-zinc-600 font-normal">({{ $items->count() }})</span>
                        </h3>
                        <span class="text-sm font-semibold text-zinc-300">{{ $fmt($totals[$key]) }}</span>
                    </div>

                    @if ($items->isEmpty())
                        <p class="px-5 py-6 text-center text-sm text-zinc-600">{{ $groupMeta[$key]['empty'] }}</p>
                    @else
                        <ul class="divide-y divide-zinc-800">
                            @foreach ($items as $item)
                                <li class="grid grid-cols-1 gap-3 px-5 py-3 sm:grid-cols-12 sm:items-center">
                                    {{-- Name --}}
                                    <div class="sm:col-span-4 min-w-0">
                                        <p class="text-sm font-medium text-zinc-100 truncate">{{ $item->displayName() }}</p>
                                        <p class="text-xs text-zinc-500">
                                            @if ($item->category() === 'server')
                                                Server{!! $item->costable?->ip_address ? ' · <span class="font-mono">' . e($item->costable->ip_address) . '</span>' : '' !!}
                                            @elseif ($item->category() === 'domain')
                                                Domain
                                            @else
                                                Eigener Posten
                                            @endif
                                        </p>
                                    </div>

                                    {{-- Notes --}}
                                    <div class="sm:col-span-5">
                                        <input type="text" name="items[{{ $item->id }}][notes]"
                                               value="{{ $item->notes }}" placeholder="Notiz (optional)" maxlength="500"
                                               class="w-full rounded-lg border px-3 py-2 text-sm bg-zinc-800 border-zinc-700 text-zinc-300 placeholder-zinc-600
                                                      focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                                    </div>

                                    {{-- Price --}}
                                    <div class="sm:col-span-3 flex items-center gap-2">
                                        <div class="relative flex-1">
                                            <input type="number" step="0.01" min="0" max="99999999"
                                                   name="items[{{ $item->id }}][monthly_price]"
                                                   value="{{ $item->monthly_price }}" placeholder="0,00"
                                                   class="w-full rounded-lg border pl-3 pr-8 py-2 text-sm text-right bg-zinc-800 border-zinc-700 text-zinc-100 font-mono
                                                          focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                                            <span class="pointer-events-none absolute inset-y-0 right-3 flex items-center text-sm text-zinc-500">€</span>
                                        </div>
                                        @if ($item->isManual())
                                            <button type="submit" form="del-{{ $item->id }}"
                                                    onclick="return confirm('Posten „{{ $item->displayName() }}“ wirklich entfernen?')"
                                                    class="shrink-0 text-zinc-600 hover:text-red-400 transition-colors" title="Entfernen">
                                                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 00-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 00-7.5 0"/>
                                                </svg>
                                            </button>
                                        @endif
                                    </div>
                                </li>
                            @endforeach
                        </ul>
                    @endif
                </div>
            @endforeach

            {{-- Grand total + save --}}
            <div class="sticky bottom-0 flex items-center justify-between rounded-xl border border-zinc-800 bg-zinc-900/95 backdrop-blur px-5 py-4 mb-6">
                <div>
                    <p class="text-xs uppercase tracking-wide text-zinc-500">Endsumme monatlich</p>
                    <p class="text-xl font-bold text-blue-300">{{ $fmt($grand_total) }}
                        <span class="text-sm font-normal text-zinc-500">· {{ $fmt($grand_total * 12) }} / Jahr</span>
                    </p>
                </div>
                <button type="submit"
                        class="rounded-lg bg-blue-600 px-5 py-2.5 text-sm font-semibold text-white hover:bg-blue-500 transition-colors">
                    Kosten speichern
                </button>
            </div>
        </form>

        {{-- ---------------------------------------------------------------- --}}
        {{-- Delete forms for manual items (kept outside the bulk form)        --}}
        {{-- ---------------------------------------------------------------- --}}
        @foreach ($groups['manual'] as $item)
            <form id="del-{{ $item->id }}" method="POST" action="{{ route('costs.destroy', $item) }}" class="hidden">
                @csrf
                @method('DELETE')
            </form>
        @endforeach

        {{-- ---------------------------------------------------------------- --}}
        {{-- Add manual item                                                   --}}
        {{-- ---------------------------------------------------------------- --}}
        <div class="rounded-xl border border-zinc-800 bg-zinc-900 p-5" x-data="{ open: false }">
            <button type="button" @click="open = !open"
                    class="flex items-center gap-2 text-sm font-medium text-zinc-300 hover:text-zinc-100 transition-colors">
                <svg class="h-4 w-4 transition-transform" :class="open ? 'rotate-45' : ''"
                     fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/>
                </svg>
                Eigenen Posten hinzufügen
            </button>

            <form method="POST" action="{{ route('costs.store') }}"
                  class="mt-4 grid grid-cols-1 gap-3 sm:grid-cols-12 sm:items-end" x-show="open" x-cloak>
                @csrf
                <div class="sm:col-span-5">
                    <label class="block text-xs font-medium text-zinc-500 mb-1">Bezeichnung</label>
                    <input type="text" name="label" required maxlength="120" placeholder="z. B. Software-Lizenz"
                           class="w-full rounded-lg border px-3 py-2 text-sm bg-zinc-800 border-zinc-700 text-zinc-100 placeholder-zinc-600
                                  focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                </div>
                <div class="sm:col-span-4">
                    <label class="block text-xs font-medium text-zinc-500 mb-1">Notiz (optional)</label>
                    <input type="text" name="notes" maxlength="500"
                           class="w-full rounded-lg border px-3 py-2 text-sm bg-zinc-800 border-zinc-700 text-zinc-300 placeholder-zinc-600
                                  focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                </div>
                <div class="sm:col-span-2">
                    <label class="block text-xs font-medium text-zinc-500 mb-1">Preis / Monat</label>
                    <div class="relative">
                        <input type="number" step="0.01" min="0" max="99999999" name="monthly_price" placeholder="0,00"
                               class="w-full rounded-lg border pl-3 pr-8 py-2 text-sm text-right bg-zinc-800 border-zinc-700 text-zinc-100 font-mono
                                      focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                        <span class="pointer-events-none absolute inset-y-0 right-3 flex items-center text-sm text-zinc-500">€</span>
                    </div>
                </div>
                <div class="sm:col-span-1">
                    <button type="submit"
                            class="w-full rounded-lg bg-blue-600 px-3 py-2 text-sm font-semibold text-white hover:bg-blue-500 transition-colors">
                        +
                    </button>
                </div>
            </form>
        </div>
    </div>
</x-layouts.app>
