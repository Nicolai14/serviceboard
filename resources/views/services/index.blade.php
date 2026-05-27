<x-layouts.app title="Services — {{ $server->name }}">
    <div class="max-w-4xl">
        <div class="mb-6 flex items-center justify-between">
            <a href="{{ route('servers.show', $server) }}" class="text-sm text-zinc-500 hover:text-zinc-300 transition-colors">
                ← Zurück zu {{ $server->name }}
            </a>
            <a href="{{ route('servers.services.create', $server) }}"
               class="inline-flex items-center gap-1 rounded-lg bg-blue-600 px-3.5 py-2 text-sm font-semibold text-white hover:bg-blue-500 transition-colors">
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/>
                </svg>
                Service hinzufügen
            </a>
        </div>

        <div class="rounded-xl border border-zinc-800 bg-zinc-900">
            <div class="p-5 border-b border-zinc-800">
                <h2 class="text-sm font-semibold text-zinc-100">Services ({{ $services->total() }})</h2>
            </div>

            @if ($services->isEmpty())
                <div class="py-12 text-center">
                    <p class="text-sm text-zinc-600">Noch keine Services konfiguriert</p>
                </div>
            @else
                <ul class="divide-y divide-zinc-800">
                    @foreach ($services as $service)
                        <li class="px-5 py-3" x-data="{ editing: false }">
                            {{-- Display row --}}
                            <div class="flex items-center gap-4" x-show="!editing">
                                <div class="flex-1 min-w-0">
                                    <p class="text-sm font-medium text-zinc-100">{{ $service->name }}</p>
                                    <p class="text-xs text-zinc-500">
                                        {{ ucfirst($service->type) }}@if ($service->port) · Port {{ $service->port }}@endif
                                        @if ($service->check_url) · <span class="font-mono">{{ $service->check_url }}</span>@endif
                                    </p>
                                    @if ($service->last_checked_at)
                                        <p class="text-xs text-zinc-600 mt-0.5">
                                            Geprüft {{ $service->last_checked_at->diffForHumans() }}{{ $service->last_latency_ms !== null ? ' · ' . $service->last_latency_ms . ' ms' : '' }}
                                        </p>
                                    @endif
                                </div>
                                <span class="text-xs px-2 py-0.5 rounded-full
                                    {{ $service->status === 'running' ? 'bg-green-900/40 text-green-400' : ($service->status === 'error' ? 'bg-red-900/40 text-red-400' : 'bg-zinc-800 text-zinc-400') }}">
                                    {{ ucfirst($service->status) }}
                                </span>
                                <button type="button" @click="editing = true"
                                        class="text-xs font-medium text-zinc-400 hover:text-zinc-200 transition-colors">
                                    Bearbeiten
                                </button>
                                <form method="POST" action="{{ route('servers.services.destroy', [$server, $service]) }}"
                                      onsubmit="return confirm('Service „{{ $service->name }}“ wirklich entfernen?')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="text-xs font-medium text-red-400 hover:text-red-300 transition-colors">
                                        Löschen
                                    </button>
                                </form>
                            </div>

                            {{-- Edit form --}}
                            <form method="POST" action="{{ route('servers.services.update', [$server, $service]) }}"
                                  class="grid grid-cols-2 gap-3 sm:grid-cols-4 sm:items-end" x-show="editing" x-cloak>
                                @csrf
                                @method('PATCH')
                                <div class="col-span-2 sm:col-span-1">
                                    <label class="block text-xs font-medium text-zinc-500 mb-1">Name</label>
                                    <input type="text" name="name" value="{{ $service->name }}" required
                                           class="w-full rounded-lg border px-3 py-2 text-sm bg-zinc-800 border-zinc-700 text-zinc-100
                                                  focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                                </div>
                                <div>
                                    <label class="block text-xs font-medium text-zinc-500 mb-1">Typ</label>
                                    <select name="type" required
                                            class="w-full rounded-lg border px-3 py-2 text-sm bg-zinc-800 border-zinc-700 text-zinc-100
                                                   focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                                        @foreach (['web', 'database', 'cache', 'queue', 'proxy', 'mail', 'custom'] as $type)
                                            <option value="{{ $type }}" @selected($service->type === $type)>{{ ucfirst($type) }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div>
                                    <label class="block text-xs font-medium text-zinc-500 mb-1">Port</label>
                                    <input type="number" name="port" value="{{ $service->port }}" min="1" max="65535"
                                           class="w-full rounded-lg border px-3 py-2 text-sm bg-zinc-800 border-zinc-700 text-zinc-100 font-mono
                                                  focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                                </div>
                                <div class="col-span-2 sm:col-span-4">
                                    <label class="block text-xs font-medium text-zinc-500 mb-1">Check-URL</label>
                                    <input type="text" name="check_url" value="{{ $service->check_url }}"
                                           class="w-full rounded-lg border px-3 py-2 text-sm bg-zinc-800 border-zinc-700 text-zinc-100 font-mono
                                                  focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                                </div>
                                <input type="hidden" name="check_interval" value="{{ $service->check_interval }}">
                                <label class="col-span-2 sm:col-span-4 flex items-center gap-2.5 cursor-pointer">
                                    <input type="checkbox" name="notify_on_down" value="1" @checked($service->notify_on_down)
                                           class="h-4 w-4 rounded border-zinc-600 bg-zinc-800 text-blue-600 focus:ring-blue-500">
                                    <span class="text-sm text-zinc-300">Bei Ausfall benachrichtigen</span>
                                </label>
                                <div class="col-span-2 sm:col-span-4 flex items-center gap-2">
                                    <button type="submit"
                                            class="rounded-lg bg-blue-600 px-4 py-2 text-sm font-semibold text-white hover:bg-blue-500 transition-colors">
                                        Speichern
                                    </button>
                                    <button type="button" @click="editing = false"
                                            class="rounded-lg border border-zinc-700 px-4 py-2 text-sm font-medium text-zinc-400 hover:bg-zinc-800 hover:text-zinc-200 transition-colors">
                                        Abbrechen
                                    </button>
                                </div>
                            </form>
                        </li>
                    @endforeach
                </ul>
            @endif
        </div>

        @if ($services->hasPages())
            <div class="mt-4">
                {{ $services->links() }}
            </div>
        @endif
    </div>
</x-layouts.app>
