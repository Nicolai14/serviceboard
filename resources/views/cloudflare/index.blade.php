<x-layouts.app title="Domains">

{{-- Header --}}
<div class="flex items-start justify-between gap-4 mb-6">
    <div>
        <h1 class="text-xl font-bold text-white">Domains</h1>
        <p class="text-sm text-zinc-500 mt-0.5">Cloudflare Zones & API Tokens</p>
    </div>
    <button x-data x-on:click="$dispatch('open-add-token')"
            class="inline-flex items-center gap-2 rounded-xl bg-orange-600 px-4 py-2.5 text-sm font-semibold text-white hover:bg-orange-500 transition-colors shadow-[0_0_16px_theme(colors.orange.600/25%)]">
        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
            <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/>
        </svg>
        API Token hinzufügen
    </button>
</div>

{{-- Stats strip --}}
<div class="grid grid-cols-2 sm:grid-cols-4 gap-4 mb-8">
    <div class="rounded-2xl border border-zinc-800 bg-zinc-900 p-5">
        <p class="text-xs font-semibold uppercase tracking-wider text-zinc-500 mb-3">API Tokens</p>
        <p class="text-3xl font-bold text-white">{{ $stats['tokens'] }}</p>
    </div>
    <div class="rounded-2xl border border-zinc-800 bg-zinc-900 p-5">
        <p class="text-xs font-semibold uppercase tracking-wider text-zinc-500 mb-3">Zonen gesamt</p>
        <p class="text-3xl font-bold text-zinc-200">{{ $stats['zones'] }}</p>
    </div>
    <div class="relative rounded-2xl border border-zinc-800 bg-zinc-900 p-5 overflow-hidden">
        <div class="absolute inset-0 bg-gradient-to-br from-green-500/5 to-transparent pointer-events-none"></div>
        <div class="flex items-center justify-between mb-3">
            <p class="text-xs font-semibold uppercase tracking-wider text-zinc-500">Aktiv</p>
            <span class="flex h-2 w-2 rounded-full bg-green-500 shadow-[0_0_6px_theme(colors.green.500)]"></span>
        </div>
        <p class="text-3xl font-bold text-green-400">{{ $stats['active'] }}</p>
    </div>
    <div class="relative rounded-2xl border border-zinc-800 bg-zinc-900 p-5 overflow-hidden">
        <div class="absolute inset-0 bg-gradient-to-br from-orange-500/5 to-transparent pointer-events-none"></div>
        <p class="text-xs font-semibold uppercase tracking-wider text-zinc-500 mb-3">DNS Records</p>
        <p class="text-3xl font-bold text-orange-400">{{ number_format($stats['dns_total']) }}</p>
    </div>
</div>

{{-- Add token modal --}}
<div x-data="{ open: false, loading: false }"
     x-on:open-add-token.window="open = true"
     x-show="open"
     x-transition.opacity
     class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/70 backdrop-blur-sm"
     style="display:none">
    <div @click.outside="open = false"
         class="w-full max-w-md rounded-2xl border border-zinc-800 bg-zinc-900 shadow-2xl">
        <div class="flex items-center justify-between px-6 py-4 border-b border-zinc-800">
            <h2 class="text-sm font-semibold text-zinc-100">API Token hinzufügen</h2>
            <button @click="open = false" class="rounded-lg p-1 text-zinc-500 hover:text-zinc-300 hover:bg-zinc-800 transition-colors">
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>
        <form method="POST" action="{{ route('cloudflare.tokens.store') }}" class="px-6 py-5 space-y-4" @submit="loading = true">
            @csrf
            <div>
                <label class="block text-xs font-medium text-zinc-400 mb-1.5">Bezeichnung</label>
                <input type="text" name="name" required placeholder="z.B. Hauptaccount"
                       class="w-full rounded-xl border border-zinc-700 bg-zinc-800 px-3 py-2.5 text-sm text-zinc-100 placeholder-zinc-600 focus:border-orange-500 focus:outline-none focus:ring-1 focus:ring-orange-500">
            </div>
            <div>
                <label class="block text-xs font-medium text-zinc-400 mb-1.5">
                    Cloudflare API Token
                    <span class="text-zinc-600 font-normal ml-1">Zone:Read, DNS:Read</span>
                </label>
                <input type="password" name="api_token" required autocomplete="off"
                       placeholder="Token einfügen…"
                       class="w-full rounded-xl border border-zinc-700 bg-zinc-800 px-3 py-2.5 text-sm text-zinc-100 font-mono placeholder-zinc-600 focus:border-orange-500 focus:outline-none focus:ring-1 focus:ring-orange-500">
                <p class="mt-1.5 text-xs text-zinc-600">Wird verschlüsselt gespeichert und nie im Klartext angezeigt.</p>
            </div>
            <div class="flex justify-end gap-3 pt-1">
                <button type="button" @click="open = false"
                        class="rounded-xl border border-zinc-700 bg-zinc-800 px-4 py-2 text-sm font-medium text-zinc-300 hover:bg-zinc-700 transition-colors">
                    Abbrechen
                </button>
                <button type="submit" :disabled="loading"
                        class="inline-flex items-center gap-2 rounded-xl bg-orange-600 px-4 py-2 text-sm font-medium text-white hover:bg-orange-500 disabled:opacity-60 disabled:cursor-not-allowed transition-colors">
                    <svg x-show="loading" class="h-4 w-4 animate-spin" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0l3.181 3.183a8.25 8.25 0 0013.803-3.7M4.031 9.865a8.25 8.25 0 0113.803-3.7l3.181 3.182m0-4.991v4.99"/>
                    </svg>
                    <span x-text="loading ? 'Verifiziere…' : 'Token speichern'">Token speichern</span>
                </button>
            </div>
        </form>
    </div>
</div>

{{-- Tokens panel --}}
@if ($tokens->isNotEmpty())
    <div class="mb-6 rounded-2xl border border-zinc-800 bg-zinc-900 overflow-hidden">
        <div class="px-5 py-4 border-b border-zinc-800/60 bg-zinc-950/30">
            <h2 class="text-sm font-semibold text-zinc-100">API Tokens</h2>
        </div>
        <div class="divide-y divide-zinc-800/60">
            @foreach ($tokens as $token)
                <div x-data="{
                         syncing: false, msg: null,
                         csrfToken: document.querySelector('meta[name=csrf-token]').content,
                         async syncNow() {
                             this.syncing = true; this.msg = null;
                             const r = await fetch('{{ route('cloudflare.tokens.sync', $token) }}', { method:'POST', headers:{'X-CSRF-TOKEN':this.csrfToken,'Accept':'application/json'} });
                             const d = await r.json(); this.msg = d.message; this.syncing = false;
                         }
                     }"
                     class="flex items-center gap-4 px-5 py-3.5">
                    <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl border
                                {{ $token->status === 'active' ? 'bg-green-500/10 border-green-500/20' : 'bg-red-500/10 border-red-500/20' }}">
                        <svg class="h-4 w-4 {{ $token->status === 'active' ? 'text-green-400' : 'text-red-400' }}" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 5.25a3 3 0 013 3m3 0a6 6 0 01-7.029 5.912c-.563-.097-1.159.026-1.563.43L10.5 17.25H8.25v2.25H6v2.25H2.25v-2.818c0-.597.237-1.17.659-1.591l6.499-6.499c.404-.404.527-1 .43-1.563A6 6 0 1121.75 8.25z"/>
                        </svg>
                    </div>
                    <div class="flex-1 min-w-0">
                        <p class="text-sm font-medium text-zinc-100">{{ $token->name }}</p>
                        <p class="text-xs text-zinc-500">
                            @if ($token->account_name) {{ $token->account_name }} · @endif
                            {{ $token->zones_count }} Zone(n)
                            @if ($token->last_verified_at) · {{ $token->last_verified_at->diffForHumans() }} @endif
                        </p>
                        @if ($token->status === 'error' && $token->error_message)
                            <p class="text-xs text-red-400 mt-0.5">{{ $token->error_message }}</p>
                        @endif
                    </div>
                    <span class="rounded-full border px-2.5 py-0.5 text-xs font-medium capitalize shrink-0
                        {{ $token->status === 'active' ? 'border-green-800/60 bg-green-900/30 text-green-400' : 'border-red-800/60 bg-red-900/30 text-red-400' }}">
                        {{ $token->status }}
                    </span>
                    <template x-if="msg"><span class="text-xs text-blue-400 shrink-0" x-text="msg"></span></template>
                    <button @click="syncNow()" :disabled="syncing"
                            class="inline-flex items-center gap-1.5 rounded-lg border border-zinc-700 bg-zinc-800 px-3 py-1.5 text-xs font-medium text-zinc-300 hover:bg-zinc-700 disabled:opacity-50 disabled:cursor-not-allowed transition-colors shrink-0">
                        <svg class="h-3.5 w-3.5" :class="{'animate-spin':syncing}" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0l3.181 3.183a8.25 8.25 0 0013.803-3.7M4.031 9.865a8.25 8.25 0 0113.803-3.7l3.181 3.182m0-4.991v4.99"/>
                        </svg>
                        <span x-text="syncing ? 'Sync…' : 'Sync'">Sync</span>
                    </button>
                    <form method="POST" action="{{ route('cloudflare.tokens.destroy', $token) }}"
                          onsubmit="return confirm('Token und alle zugehoerigen Daten loeschen?')">
                        @csrf @method('DELETE')
                        <button type="submit" class="rounded-lg border border-zinc-800 bg-zinc-900 p-1.5 text-zinc-600 hover:text-red-400 hover:border-red-800 transition-colors">
                            <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                            </svg>
                        </button>
                    </form>
                </div>
            @endforeach
        </div>
    </div>
@endif

{{-- Zones table --}}
@if ($zones->isEmpty())
    <div class="flex flex-col items-center justify-center rounded-2xl border border-dashed border-zinc-800 py-20 text-center">
        <div class="flex h-14 w-14 items-center justify-center rounded-full bg-orange-500/10 border border-orange-500/20 mb-4">
            <svg class="h-7 w-7 text-orange-500/60" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 21a9.004 9.004 0 008.716-6.747M12 21a9.004 9.004 0 01-8.716-6.747M12 21c2.485 0 4.5-4.03 4.5-9S14.485 3 12 3m0 18c-2.485 0-4.5-4.03-4.5-9S9.515 3 12 3m0 0a8.997 8.997 0 017.843 4.582M12 3a8.997 8.997 0 00-7.843 4.582m15.686 0A11.953 11.953 0 0112 10.5c-2.998 0-5.74-1.1-7.843-2.918m15.686 0A8.959 8.959 0 0121 12c0 .778-.099 1.533-.284 2.253"/>
            </svg>
        </div>
        <h3 class="text-base font-medium text-zinc-400 mb-1">Keine Zonen gefunden</h3>
        <p class="text-sm text-zinc-600 mb-4">API Token hinzufügen und Zonen synchronisieren.</p>
        <button x-data x-on:click="$dispatch('open-add-token')"
                class="text-sm text-orange-400 hover:text-orange-300 transition-colors">
            API Token hinzufügen →
        </button>
    </div>
@else
    <div class="rounded-2xl border border-zinc-800 bg-zinc-900 overflow-hidden">
        <div class="flex items-center justify-between px-5 py-4 border-b border-zinc-800/60 bg-zinc-950/30">
            <h2 class="text-sm font-semibold text-zinc-100">Zonen ({{ $stats['zones'] }})</h2>
            <div class="flex items-center gap-3">
                <span class="text-xs text-zinc-600">Auto-Sync alle 15 Min</span>
                <a href="{{ route('cloudflare.dns') }}"
                   class="inline-flex items-center gap-1.5 text-xs text-orange-400 hover:text-orange-300 transition-colors">
                    <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M8.288 15.038a5.25 5.25 0 017.424 0M5.106 11.856c3.807-3.808 9.98-3.808 13.788 0M1.924 8.674c5.565-5.565 14.587-5.565 20.152 0M12.53 18.22l-.53.53-.53-.53a.75.75 0 011.06 0z"/>
                    </svg>
                    Alle DNS Records →
                </a>
            </div>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="border-b border-zinc-800/40">
                        <th class="px-5 py-2.5 text-left text-xs font-semibold uppercase tracking-wider text-zinc-500">Domain</th>
                        <th class="px-4 py-2.5 text-left text-xs font-semibold uppercase tracking-wider text-zinc-500">Status</th>
                        <th class="px-4 py-2.5 text-left text-xs font-semibold uppercase tracking-wider text-zinc-500 hidden md:table-cell">Plan</th>
                        <th class="px-4 py-2.5 text-left text-xs font-semibold uppercase tracking-wider text-zinc-500 hidden lg:table-cell">Typ</th>
                        <th class="px-4 py-2.5 text-left text-xs font-semibold uppercase tracking-wider text-zinc-500">DNS</th>
                        <th class="px-4 py-2.5 text-left text-xs font-semibold uppercase tracking-wider text-zinc-500 hidden xl:table-cell">Sync</th>
                        <th class="px-4 py-2.5"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-800/40">
                    @foreach ($zones as $zone)
                        @php
                            $statusColor = match($zone->status) {
                                'active'       => 'border-green-800/60 bg-green-900/30 text-green-400',
                                'pending'      => 'border-yellow-800/60 bg-yellow-900/30 text-yellow-400',
                                'initializing' => 'border-blue-800/60 bg-blue-900/30 text-blue-400',
                                'moved'        => 'border-orange-800/60 bg-orange-900/30 text-orange-400',
                                default        => 'border-zinc-700 bg-zinc-800 text-zinc-400',
                            };
                        @endphp
                        <tr class="hover:bg-zinc-800/30 transition-colors">
                            <td class="px-5 py-3.5">
                                <div class="flex items-center gap-2.5">
                                    <span class="h-2 w-2 rounded-full shrink-0 {{ $zone->paused ? 'bg-yellow-500' : ($zone->status === 'active' ? 'bg-green-500 shadow-[0_0_4px_theme(colors.green.500)]' : 'bg-zinc-600') }}"></span>
                                    <span class="font-medium text-zinc-100">{{ $zone->name }}</span>
                                </div>
                            </td>
                            <td class="px-4 py-3.5">
                                <span class="inline-flex items-center rounded-full border px-2.5 py-0.5 text-xs font-medium {{ $statusColor }}">
                                    {{ $zone->status }}{{ $zone->paused ? ' · paused' : '' }}
                                </span>
                            </td>
                            <td class="px-4 py-3.5 hidden md:table-cell">
                                <span class="text-xs text-zinc-400">{{ $zone->plan_name ?? '—' }}</span>
                            </td>
                            <td class="px-4 py-3.5 hidden lg:table-cell">
                                <span class="text-xs text-zinc-500 capitalize">{{ $zone->type }}</span>
                            </td>
                            <td class="px-4 py-3.5">
                                <span class="text-sm font-semibold text-orange-400">{{ number_format($zone->dns_records_count) }}</span>
                            </td>
                            <td class="px-4 py-3.5 hidden xl:table-cell">
                                <span class="text-xs text-zinc-600">{{ $zone->synced_at?->diffForHumans() ?? '—' }}</span>
                            </td>
                            <td class="px-4 py-3.5 text-right">
                                <a href="{{ route('cloudflare.zones.show', $zone) }}"
                                   class="inline-flex items-center gap-1 text-xs text-orange-400 hover:text-orange-300 transition-colors whitespace-nowrap">
                                    DNS
                                    <svg class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 4.5l7.5 7.5-7.5 7.5"/>
                                    </svg>
                                </a>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
@endif

</x-layouts.app>
