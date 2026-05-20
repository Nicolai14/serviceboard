<x-layouts.app :title="'Bearbeiten: ' . $server->name">
    <div class="max-w-2xl">
        <div class="mb-6">
            <a href="{{ route('servers.show', $server) }}" class="text-sm text-zinc-500 hover:text-zinc-300 transition-colors">
                ← Zurück zu {{ $server->name }}
            </a>
        </div>

        <div class="rounded-xl border border-zinc-800 bg-zinc-900 p-6">
            <h2 class="text-base font-semibold text-zinc-100 mb-6">Server bearbeiten</h2>

            <form method="POST" action="{{ route('servers.update', $server) }}" class="space-y-6"
                  x-data="{ authMethod: '{{ old('ssh_auth_method', $server->ssh_auth_method ?? 'key') }}' }">
                @csrf
                @method('PATCH')

                {{-- Basic info --}}
                <div class="space-y-4">
                    <h3 class="text-xs font-semibold text-zinc-500 uppercase tracking-wider">Verbindung</h3>

                    <div class="grid grid-cols-2 gap-4">
                        <div class="col-span-2 sm:col-span-1">
                            <label for="name" class="block text-sm font-medium text-zinc-400 mb-1.5">Name *</label>
                            <input type="text" id="name" name="name" value="{{ old('name', $server->name) }}" required
                                   class="w-full rounded-lg border px-3.5 py-2.5 text-sm bg-zinc-800 border-zinc-700 text-zinc-100
                                          focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent
                                          @error('name') border-red-500 @enderror">
                        </div>
                        <div class="col-span-2 sm:col-span-1">
                            <label for="status" class="block text-sm font-medium text-zinc-400 mb-1.5">Status</label>
                            <select id="status" name="status"
                                    class="w-full rounded-lg border px-3.5 py-2.5 text-sm bg-zinc-800 border-zinc-700 text-zinc-100
                                           focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                                @foreach (['online', 'offline', 'maintenance', 'unknown'] as $s)
                                    <option value="{{ $s }}" @selected(old('status', $server->status) === $s)>{{ ucfirst($s) }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <div class="grid grid-cols-3 gap-4">
                        <div>
                            <label for="hostname" class="block text-sm font-medium text-zinc-400 mb-1.5">Hostname *</label>
                            <input type="text" id="hostname" name="hostname" value="{{ old('hostname', $server->hostname) }}" required
                                   class="w-full rounded-lg border px-3.5 py-2.5 text-sm bg-zinc-800 border-zinc-700 text-zinc-100
                                          focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent font-mono">
                        </div>
                        <div>
                            <label for="ssh_port" class="block text-sm font-medium text-zinc-400 mb-1.5">SSH-Port</label>
                            <input type="number" id="ssh_port" name="ssh_port" value="{{ old('ssh_port', $server->ssh_port) }}"
                                   min="1" max="65535"
                                   class="w-full rounded-lg border px-3.5 py-2.5 text-sm bg-zinc-800 border-zinc-700 text-zinc-100
                                          focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent font-mono">
                        </div>
                        <div>
                            <label for="ssh_user" class="block text-sm font-medium text-zinc-400 mb-1.5">SSH-User</label>
                            <input type="text" id="ssh_user" name="ssh_user" value="{{ old('ssh_user', $server->ssh_user) }}"
                                   class="w-full rounded-lg border px-3.5 py-2.5 text-sm bg-zinc-800 border-zinc-700 text-zinc-100
                                          focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent font-mono">
                        </div>
                    </div>
                </div>

                {{-- SSH Auth --}}
                <div class="space-y-4">
                    <h3 class="text-xs font-semibold text-zinc-500 uppercase tracking-wider">SSH Authentifizierung</h3>

                    <div class="flex gap-3">
                        <label class="flex items-center gap-2 cursor-pointer">
                            <input type="radio" name="ssh_auth_method" value="key"
                                   x-model="authMethod"
                                   class="h-4 w-4 border-zinc-600 bg-zinc-800 text-blue-600 focus:ring-blue-500">
                            <span class="text-sm text-zinc-300">Private Key</span>
                        </label>
                        <label class="flex items-center gap-2 cursor-pointer">
                            <input type="radio" name="ssh_auth_method" value="password"
                                   x-model="authMethod"
                                   class="h-4 w-4 border-zinc-600 bg-zinc-800 text-blue-600 focus:ring-blue-500">
                            <span class="text-sm text-zinc-300">Passwort</span>
                        </label>
                    </div>

                    <div x-show="authMethod === 'key'" x-transition>
                        <label for="ssh_private_key" class="block text-sm font-medium text-zinc-400 mb-1.5">
                            Private Key (PEM)
                            @if ($server->ssh_private_key)
                                <span class="ml-2 text-xs text-green-600 font-normal">✓ gespeichert</span>
                            @endif
                        </label>
                        <textarea id="ssh_private_key" name="ssh_private_key" rows="6"
                                  class="w-full rounded-lg border px-3.5 py-2.5 text-xs bg-zinc-800 border-zinc-700 text-zinc-300
                                         placeholder-zinc-600 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent
                                         font-mono resize-none"
                                  placeholder="{{ $server->ssh_private_key ? '(leer lassen um bestehenden Key beizubehalten)' : '-----BEGIN OPENSSH PRIVATE KEY-----' }}"></textarea>
                        <p class="mt-1.5 text-xs text-zinc-600">Leer lassen = bestehender Key bleibt erhalten.</p>
                    </div>

                    <div x-show="authMethod === 'password'" x-transition>
                        <label for="ssh_password" class="block text-sm font-medium text-zinc-400 mb-1.5">
                            SSH-Passwort
                            @if ($server->ssh_password)
                                <span class="ml-2 text-xs text-green-600 font-normal">✓ gespeichert</span>
                            @endif
                        </label>
                        <input type="password" id="ssh_password" name="ssh_password"
                               class="w-full rounded-lg border px-3.5 py-2.5 text-sm bg-zinc-800 border-zinc-700 text-zinc-100
                                      focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                               placeholder="{{ $server->ssh_password ? '(leer lassen um Passwort beizubehalten)' : '••••••••' }}">
                        <p class="mt-1.5 text-xs text-zinc-600">Leer lassen = bestehendes Passwort bleibt erhalten.</p>
                    </div>
                </div>

                {{-- Meta --}}
                <div class="space-y-4">
                    <h3 class="text-xs font-semibold text-zinc-500 uppercase tracking-wider">Weitere Angaben</h3>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label for="os" class="block text-sm font-medium text-zinc-400 mb-1.5">Betriebssystem</label>
                            <input type="text" id="os" name="os" value="{{ old('os', $server->os) }}"
                                   class="w-full rounded-lg border px-3.5 py-2.5 text-sm bg-zinc-800 border-zinc-700 text-zinc-100
                                          placeholder-zinc-500 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                        </div>
                        <div>
                            <label for="ip_address" class="block text-sm font-medium text-zinc-400 mb-1.5">IP-Adresse</label>
                            <input type="text" id="ip_address" name="ip_address" value="{{ old('ip_address', $server->ip_address) }}"
                                   class="w-full rounded-lg border px-3.5 py-2.5 text-sm bg-zinc-800 border-zinc-700 text-zinc-100
                                          placeholder-zinc-500 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent font-mono">
                        </div>
                    </div>
                    <div>
                        <label for="notes" class="block text-sm font-medium text-zinc-400 mb-1.5">Notizen</label>
                        <textarea id="notes" name="notes" rows="3"
                                  class="w-full rounded-lg border px-3.5 py-2.5 text-sm bg-zinc-800 border-zinc-700 text-zinc-100
                                         placeholder-zinc-500 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent resize-none">{{ old('notes', $server->notes) }}</textarea>
                    </div>
                </div>

                <div class="flex items-center gap-3 pt-2 border-t border-zinc-800">
                    <button type="submit"
                            class="rounded-lg bg-blue-600 px-5 py-2.5 text-sm font-semibold text-white hover:bg-blue-500 transition-colors">
                        Änderungen speichern
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
