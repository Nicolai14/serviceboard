<x-layouts.app title="Service hinzufügen">
    <div class="max-w-2xl">
        <div class="mb-6">
            <a href="{{ route('servers.show', $server) }}" class="text-sm text-zinc-500 hover:text-zinc-300 transition-colors">
                ← Zurück zu {{ $server->name }}
            </a>
        </div>

        <div class="rounded-xl border border-zinc-800 bg-zinc-900 p-6">
            <h2 class="text-base font-semibold text-zinc-100 mb-6">Service hinzufügen</h2>

            <form method="POST" action="{{ route('servers.services.store', $server) }}" class="space-y-6">
                @csrf

                <div class="grid grid-cols-2 gap-4">
                    <div class="col-span-2 sm:col-span-1">
                        <label for="name" class="block text-sm font-medium text-zinc-400 mb-1.5">Name *</label>
                        <input type="text" id="name" name="name" value="{{ old('name') }}" required autofocus
                               class="w-full rounded-lg border px-3.5 py-2.5 text-sm bg-zinc-800 border-zinc-700 text-zinc-100
                                      placeholder-zinc-500 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent
                                      @error('name') border-red-500 @enderror"
                               placeholder="nginx">
                        @error('name') <p class="mt-1.5 text-xs text-red-400">{{ $message }}</p> @enderror
                    </div>
                    <div class="col-span-2 sm:col-span-1">
                        <label for="type" class="block text-sm font-medium text-zinc-400 mb-1.5">Typ *</label>
                        <select id="type" name="type" required
                                class="w-full rounded-lg border px-3.5 py-2.5 text-sm bg-zinc-800 border-zinc-700 text-zinc-100
                                       focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent
                                       @error('type') border-red-500 @enderror">
                            @foreach (['web', 'database', 'cache', 'queue', 'proxy', 'mail', 'custom'] as $type)
                                <option value="{{ $type }}" @selected(old('type', 'custom') === $type)>{{ ucfirst($type) }}</option>
                            @endforeach
                        </select>
                        @error('type') <p class="mt-1.5 text-xs text-red-400">{{ $message }}</p> @enderror
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label for="port" class="block text-sm font-medium text-zinc-400 mb-1.5">Port</label>
                        <input type="number" id="port" name="port" value="{{ old('port') }}" min="1" max="65535"
                               class="w-full rounded-lg border px-3.5 py-2.5 text-sm bg-zinc-800 border-zinc-700 text-zinc-100
                                      placeholder-zinc-500 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent font-mono
                                      @error('port') border-red-500 @enderror"
                               placeholder="80">
                        @error('port') <p class="mt-1.5 text-xs text-red-400">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label for="check_interval" class="block text-sm font-medium text-zinc-400 mb-1.5">Check-Intervall (Sek.)</label>
                        <input type="number" id="check_interval" name="check_interval" value="{{ old('check_interval', 60) }}" min="10" max="3600"
                               class="w-full rounded-lg border px-3.5 py-2.5 text-sm bg-zinc-800 border-zinc-700 text-zinc-100
                                      focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent font-mono
                                      @error('check_interval') border-red-500 @enderror">
                        @error('check_interval') <p class="mt-1.5 text-xs text-red-400">{{ $message }}</p> @enderror
                    </div>
                </div>

                <div>
                    <label for="check_url" class="block text-sm font-medium text-zinc-400 mb-1.5">Check-URL</label>
                    <input type="text" id="check_url" name="check_url" value="{{ old('check_url') }}"
                           class="w-full rounded-lg border px-3.5 py-2.5 text-sm bg-zinc-800 border-zinc-700 text-zinc-100
                                  placeholder-zinc-500 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent font-mono
                                  @error('check_url') border-red-500 @enderror"
                           placeholder="https://example.com/health">
                    @error('check_url') <p class="mt-1.5 text-xs text-red-400">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label for="notes" class="block text-sm font-medium text-zinc-400 mb-1.5">Notizen</label>
                    <textarea id="notes" name="notes" rows="3"
                              class="w-full rounded-lg border px-3.5 py-2.5 text-sm bg-zinc-800 border-zinc-700 text-zinc-100
                                     placeholder-zinc-500 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent resize-none"
                              placeholder="Optionale Notizen…">{{ old('notes') }}</textarea>
                </div>

                <label class="flex items-center gap-2.5 cursor-pointer">
                    <input type="checkbox" name="notify_on_down" value="1" @checked(old('notify_on_down'))
                           class="h-4 w-4 rounded border-zinc-600 bg-zinc-800 text-blue-600 focus:ring-blue-500">
                    <span class="text-sm text-zinc-300">Bei Ausfall benachrichtigen</span>
                    <span class="text-xs text-zinc-600">(Telegram / E-Mail)</span>
                </label>

                <div class="flex items-center gap-3 pt-2 border-t border-zinc-800">
                    <button type="submit"
                            class="rounded-lg bg-blue-600 px-5 py-2.5 text-sm font-semibold text-white hover:bg-blue-500 transition-colors">
                        Service hinzufügen
                    </button>
                    <a href="{{ route('servers.show', $server) }}"
                       class="rounded-lg border border-zinc-700 px-5 py-2.5 text-sm font-medium text-zinc-400 hover:bg-zinc-800 hover:text-zinc-200 transition-colors">
                        Abbrechen
                    </a>
                </div>
            </form>
        </div>
    </div>
</x-layouts.app>
