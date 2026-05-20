<x-layouts.app title="Alerts">
    <div class="mb-6 flex items-center justify-between">
        <p class="text-sm text-zinc-500">{{ $alerts->total() }} Alerts insgesamt</p>

        @if ($alerts->total() > 0)
            <form method="POST" action="{{ route('alerts.read-all') }}">
                @csrf
                <button type="submit"
                        class="text-sm text-blue-400 hover:text-blue-300 transition-colors">
                    Alle als gelesen markieren
                </button>
            </form>
        @endif
    </div>

    {{-- Filter --}}
    <form method="GET" class="mb-6 flex gap-3">
        <select name="severity"
                class="rounded-lg border border-zinc-700 bg-zinc-900 px-3 py-2 text-sm text-zinc-300 focus:outline-none focus:ring-2 focus:ring-blue-500">
            <option value="">Alle Schweregrade</option>
            <option value="critical" @selected(request('severity') === 'critical')>Kritisch</option>
            <option value="warning" @selected(request('severity') === 'warning')>Warnung</option>
            <option value="info" @selected(request('severity') === 'info')>Info</option>
        </select>
        <label class="flex items-center gap-2 cursor-pointer">
            <input type="checkbox" name="unread" value="1" @checked(request('unread'))
                   class="h-4 w-4 rounded border-zinc-700 bg-zinc-800 text-blue-600 focus:ring-blue-500">
            <span class="text-sm text-zinc-400">Nur ungelesen</span>
        </label>
        <button type="submit"
                class="rounded-lg border border-zinc-700 bg-zinc-800 px-4 py-2 text-sm text-zinc-300 hover:bg-zinc-700 transition-colors">
            Filtern
        </button>
    </form>

    @if ($alerts->isEmpty())
        <div class="flex flex-col items-center justify-center rounded-xl border border-dashed border-zinc-800 py-20 text-center">
            <svg class="h-14 w-14 text-zinc-700 mb-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1">
                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
            <p class="text-base font-medium text-zinc-400 mb-1">Keine Alerts</p>
            <p class="text-sm text-zinc-600">Alle Systeme laufen problemlos.</p>
        </div>
    @else
        <div class="space-y-2">
            @foreach ($alerts as $alert)
                <div class="rounded-xl border bg-zinc-900 p-4
                    {{ !$alert->is_read ? 'border-zinc-700' : 'border-zinc-800 opacity-60' }}">
                    <div class="flex items-start gap-4">
                        {{-- Severity badge --}}
                        <div class="shrink-0 mt-0.5">
                            @if ($alert->severity === 'critical')
                                <span class="inline-flex items-center rounded-full bg-red-900/50 px-2 py-0.5 text-xs font-medium text-red-400 border border-red-800">Kritisch</span>
                            @elseif ($alert->severity === 'warning')
                                <span class="inline-flex items-center rounded-full bg-yellow-900/50 px-2 py-0.5 text-xs font-medium text-yellow-400 border border-yellow-800">Warnung</span>
                            @else
                                <span class="inline-flex items-center rounded-full bg-blue-900/50 px-2 py-0.5 text-xs font-medium text-blue-400 border border-blue-800">Info</span>
                            @endif
                        </div>

                        <div class="flex-1 min-w-0">
                            <p class="text-sm text-zinc-200">{{ $alert->message }}</p>
                            <div class="mt-1 flex items-center gap-3 text-xs text-zinc-500">
                                @if ($alert->server)
                                    <a href="{{ route('servers.show', $alert->server) }}" class="hover:text-zinc-300 transition-colors">
                                        {{ $alert->server->name }}
                                    </a>
                                    <span>·</span>
                                @endif
                                <span>{{ $alert->type }}</span>
                                <span>·</span>
                                <span>{{ $alert->created_at->diffForHumans() }}</span>
                                @if ($alert->isResolved())
                                    <span>·</span>
                                    <span class="text-green-600">Behoben</span>
                                @endif
                            </div>
                        </div>

                        <div class="flex items-center gap-2 shrink-0">
                            @if (!$alert->is_read)
                                <form method="POST" action="{{ route('alerts.read', $alert) }}">
                                    @csrf
                                    <button type="submit" class="text-xs text-zinc-500 hover:text-zinc-300 transition-colors">
                                        Gelesen
                                    </button>
                                </form>
                            @endif
                            @if (!$alert->isResolved())
                                <form method="POST" action="{{ route('alerts.resolve', $alert) }}">
                                    @csrf
                                    <button type="submit" class="text-xs text-zinc-500 hover:text-green-400 transition-colors">
                                        Beheben
                                    </button>
                                </form>
                            @endif
                        </div>
                    </div>
                </div>
            @endforeach
        </div>

        <div class="mt-6">
            {{ $alerts->withQueryString()->links() }}
        </div>
    @endif
</x-layouts.app>
