<x-layouts.app title="Server hinzufügen">
    <div class="max-w-2xl">
        <div class="mb-6">
            <a href="{{ route('servers.index') }}" class="text-sm text-zinc-500 hover:text-zinc-300 transition-colors">
                ← Zurück zur Übersicht
            </a>
        </div>

        <div class="rounded-xl border border-zinc-800 bg-zinc-900 p-6">
            <h2 class="text-base font-semibold text-zinc-100 mb-6">Neuen Server hinzufügen</h2>

            <form method="POST" action="{{ route('servers.store') }}" class="space-y-6"
                  x-data="{ authMethod: '{{ old('ssh_auth_method', 'key') }}' }">
                @csrf

                {{-- Basic info --}}
                <div class="space-y-4">
                    <h3 class="text-xs font-semibold text-zinc-500 uppercase tracking-wider">Verbindung</h3>

                    <div class="grid grid-cols-2 gap-4">
                        <div class="col-span-2 sm:col-span-1">
                            <label for="name" class="block text-sm font-medium text-zinc-400 mb-1.5">Name *</label>
                            <input type="text" id="name" name="name" value="{{ old('name') }}" required autofocus
                                   class="w-full rounded-lg border px-3.5 py-2.5 text-sm bg-zinc-800 border-zinc-700 text-zinc-100
                                          placeholder-zinc-500 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent
                                          @error('name') border-red-500 @enderror"
                                   placeholder="Prod Web 01">
                            @error('name') <p class="mt-1.5 text-xs text-red-400">{{ $message }}</p> @enderror
                        </div>
                        <div class="col-span-2 sm:col-span-1">
                            <label for="hostname" class="block text-sm font-medium text-zinc-400 mb-1.5">Hostname / IP *</label>
                            <input type="text" id="hostname" name="hostname" value="{{ old('hostname') }}" required
                                   class="w-full rounded-lg border px-3.5 py-2.5 text-sm bg-zinc-800 border-zinc-700 text-zinc-100
                                          placeholder-zinc-500 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent
                                          @error('hostname') border-red-500 @enderror font-mono"
                                   placeholder="web01.example.com">
                            @error('hostname') <p class="mt-1.5 text-xs text-red-400">{{ $message }}</p> @enderror
                        </div>
                    </div>

                    <div class="grid grid-cols-3 gap-4">
                        <div>
                            <label for="ip_address" class="block text-sm font-medium text-zinc-400 mb-1.5">IP-Adresse</label>
                            <input type="text" id="ip_address" name="ip_address" value="{{ old('ip_address') }}"
                                   class="w-full rounded-lg border px-3.5 py-2.5 text-sm bg-zinc-800 border-zinc-700 text-zinc-100
                                          placeholder-zinc-500 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent font-mono"
                                   placeholder="192.168.1.10">
                        </div>
                        <div>
                            <label for="ssh_port" class="block text-sm font-medium text-zinc-400 mb-1.5">SSH-Port</label>
                            <input type="number" id="ssh_port" name="ssh_port" value="{{ old('ssh_port', 22) }}"
                                   min="1" max="65535"
                                   class="w-full rounded-lg border px-3.5 py-2.5 text-sm bg-zinc-800 border-zinc-700 text-zinc-100
                                          focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent font-mono">
                        </div>
                        <div>
                            <label for="ssh_user" class="block text-sm font-medium text-zinc-400 mb-1.5">SSH-User</label>
                            <input type="text" id="ssh_user" name="ssh_user" value="{{ old('ssh_user', 'root') }}"
                                   class="w-full rounded-lg border px-3.5 py-2.5 text-sm bg-zinc-800 border-zinc-700 text-zinc-100
                                          placeholder-zinc-500 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent font-mono"
                                   placeholder="root">
                        </div>
                    </div>
                </div>

                {{-- SSH Auth --}}
                <div class="space-y-4">
                    <h3 class="text-xs font-semibold text-zinc-500 uppercase tracking-wider">SSH Authentifizierung</h3>

                    <div>
                        <label class="block text-sm font-medium text-zinc-400 mb-2">Methode</label>
                        <div class="flex gap-3">
                            <label class="flex items-center gap-2 cursor-pointer group">
                                <input type="radio" name="ssh_auth_method" value="key"
                                       x-model="authMethod"
                                       class="h-4 w-4 border-zinc-600 bg-zinc-800 text-blue-600 focus:ring-blue-500">
                                <span class="text-sm text-zinc-300">Private Key</span>
                            </label>
                            <label class="flex items-center gap-2 cursor-pointer group">
                                <input type="radio" name="ssh_auth_method" value="password"
                                       x-model="authMethod"
                                       class="h-4 w-4 border-zinc-600 bg-zinc-800 text-blue-600 focus:ring-blue-500">
                                <span class="text-sm text-zinc-300">Passwort</span>
                            </label>
                        </div>
                    </div>

                    <div x-show="authMethod === 'key'" x-transition>
                        <label for="ssh_private_key" class="block text-sm font-medium text-zinc-400 mb-1.5">
                            Private Key (PEM)
                        </label>
                        <textarea id="ssh_private_key" name="ssh_private_key" rows="8"
                                  class="w-full rounded-lg border px-3.5 py-2.5 text-xs bg-zinc-800 border-zinc-700 text-zinc-300
                                         placeholder-zinc-600 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent
                                         font-mono resize-none @error('ssh_private_key') border-red-500 @enderror"
                                  placeholder="-----BEGIN OPENSSH PRIVATE KEY-----
...
-----END OPENSSH PRIVATE KEY-----">{{ old('ssh_private_key') }}</textarea>
                        <p class="mt-1.5 text-xs text-zinc-600">Der Key wird verschlüsselt gespeichert.</p>
                        @error('ssh_private_key') <p class="mt-1.5 text-xs text-red-400">{{ $message }}</p> @enderror
                    </div>

                    <div x-show="authMethod === 'password'" x-transition>
                        <label for="ssh_password" class="block text-sm font-medium text-zinc-400 mb-1.5">SSH-Passwort</label>
                        <input type="password" id="ssh_password" name="ssh_password"
                               class="w-full rounded-lg border px-3.5 py-2.5 text-sm bg-zinc-800 border-zinc-700 text-zinc-100
                                      focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                               placeholder="••••••••">
                        <p class="mt-1.5 text-xs text-zinc-600">Das Passwort wird verschlüsselt gespeichert.</p>
                    </div>
                </div>

                {{-- Meta --}}
                <div class="space-y-4">
                    <h3 class="text-xs font-semibold text-zinc-500 uppercase tracking-wider">Weitere Angaben</h3>
                    <div>
                        <label for="os" class="block text-sm font-medium text-zinc-400 mb-1.5">Betriebssystem</label>
                        <input type="text" id="os" name="os" value="{{ old('os') }}"
                               class="w-full rounded-lg border px-3.5 py-2.5 text-sm bg-zinc-800 border-zinc-700 text-zinc-100
                                      placeholder-zinc-500 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                               placeholder="Ubuntu 24.04 LTS">
                    </div>
                    <div>
                        <label for="notes" class="block text-sm font-medium text-zinc-400 mb-1.5">Notizen</label>
                        <textarea id="notes" name="notes" rows="3"
                                  class="w-full rounded-lg border px-3.5 py-2.5 text-sm bg-zinc-800 border-zinc-700 text-zinc-100
                                         placeholder-zinc-500 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent resize-none"
                                  placeholder="Optionale Notizen…">{{ old('notes') }}</textarea>
                    </div>
                </div>

                <div class="flex items-center gap-3 pt-2 border-t border-zinc-800">
                    <button type="submit"
                            class="rounded-lg bg-blue-600 px-5 py-2.5 text-sm font-semibold text-white hover:bg-blue-500 transition-colors">
                        Server hinzufügen
                    </button>
                    <a href="{{ route('servers.index') }}"
                       class="rounded-lg border border-zinc-700 px-5 py-2.5 text-sm font-medium text-zinc-400 hover:bg-zinc-800 hover:text-zinc-200 transition-colors">
                        Abbrechen
                    </a>
                </div>
            </form>
        </div>
    </div>
</x-layouts.app>
