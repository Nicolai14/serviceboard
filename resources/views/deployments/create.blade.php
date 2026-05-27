<x-layouts.app title="Neues Deployment">
    <div class="max-w-2xl">
        <div class="mb-6">
            <a href="{{ route('servers.deployments.index', $server) }}" class="text-sm text-zinc-500 hover:text-zinc-300 transition-colors">
                ← Zurück zu den Deployments
            </a>
        </div>

        <div class="rounded-xl border border-zinc-800 bg-zinc-900 p-6">
            <h2 class="text-base font-semibold text-zinc-100 mb-1">Neues Deployment</h2>
            <p class="text-xs text-zinc-500 mb-6">Wird per SSH auf <span class="font-mono">{{ $server->name }}</span> ausgeführt.</p>

            <form method="POST" action="{{ route('servers.deployments.store', $server) }}" class="space-y-6"
                  x-data="{ type: '{{ old('type', 'script') }}' }">
                @csrf

                <div class="grid grid-cols-2 gap-4">
                    <div class="col-span-2 sm:col-span-1">
                        <label for="name" class="block text-sm font-medium text-zinc-400 mb-1.5">Name *</label>
                        <input type="text" id="name" name="name" value="{{ old('name') }}" required autofocus
                               class="w-full rounded-lg border px-3.5 py-2.5 text-sm bg-zinc-800 border-zinc-700 text-zinc-100
                                      placeholder-zinc-500 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent
                                      @error('name') border-red-500 @enderror"
                               placeholder="Release v1.2.0">
                        @error('name') <p class="mt-1.5 text-xs text-red-400">{{ $message }}</p> @enderror
                    </div>
                    <div class="col-span-2 sm:col-span-1">
                        <label for="type" class="block text-sm font-medium text-zinc-400 mb-1.5">Typ *</label>
                        <select id="type" name="type" x-model="type" required
                                class="w-full rounded-lg border px-3.5 py-2.5 text-sm bg-zinc-800 border-zinc-700 text-zinc-100
                                       focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                            @foreach ($types as $t)
                                <option value="{{ $t->value }}">{{ $t->label() }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div>
                    <label for="directory" class="block text-sm font-medium text-zinc-400 mb-1.5">Arbeitsverzeichnis</label>
                    <input type="text" id="directory" name="directory" value="{{ old('directory') }}"
                           class="w-full rounded-lg border px-3.5 py-2.5 text-sm bg-zinc-800 border-zinc-700 text-zinc-100
                                  placeholder-zinc-500 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent font-mono"
                           placeholder="/root/app">
                </div>

                {{-- Git --}}
                <div x-show="type === 'git'" x-transition class="space-y-4">
                    <div>
                        <label for="repository" class="block text-sm font-medium text-zinc-400 mb-1.5">Repository</label>
                        <input type="text" id="repository" name="repository" value="{{ old('repository') }}"
                               class="w-full rounded-lg border px-3.5 py-2.5 text-sm bg-zinc-800 border-zinc-700 text-zinc-100
                                      placeholder-zinc-500 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent font-mono"
                               placeholder="git@github.com:org/repo.git">
                    </div>
                    <div>
                        <label for="branch" class="block text-sm font-medium text-zinc-400 mb-1.5">Branch</label>
                        <input type="text" id="branch" name="branch" value="{{ old('branch', 'main') }}"
                               class="w-full rounded-lg border px-3.5 py-2.5 text-sm bg-zinc-800 border-zinc-700 text-zinc-100
                                      focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent font-mono">
                    </div>
                </div>

                {{-- Script --}}
                <div x-show="type === 'script'" x-transition>
                    <label for="script" class="block text-sm font-medium text-zinc-400 mb-1.5">Shell-Script</label>
                    <textarea id="script" name="script" rows="8"
                              class="w-full rounded-lg border px-3.5 py-2.5 text-xs bg-zinc-800 border-zinc-700 text-zinc-300
                                     placeholder-zinc-600 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent
                                     font-mono resize-none">{{ old('script') }}</textarea>
                    <p class="mt-1.5 text-xs text-zinc-600">Wird im Arbeitsverzeichnis als <span class="font-mono">{{ $server->ssh_user }}</span> ausgeführt.</p>
                </div>

                {{-- Docker Compose --}}
                <div x-show="type === 'docker_compose'" x-transition class="space-y-4">
                    <div>
                        <label for="compose_file" class="block text-sm font-medium text-zinc-400 mb-1.5">Compose-Datei</label>
                        <input type="text" id="compose_file" name="compose_file" value="{{ old('compose_file', 'docker-compose.yml') }}"
                               class="w-full rounded-lg border px-3.5 py-2.5 text-sm bg-zinc-800 border-zinc-700 text-zinc-100
                                      focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent font-mono">
                    </div>
                    <label class="flex items-center gap-2.5 cursor-pointer">
                        <input type="checkbox" name="pull_images" value="1" @checked(old('pull_images'))
                               class="h-4 w-4 rounded border-zinc-600 bg-zinc-800 text-blue-600 focus:ring-blue-500">
                        <span class="text-sm text-zinc-300">Images vorher pullen</span>
                    </label>
                </div>

                <div class="flex items-center gap-3 pt-2 border-t border-zinc-800">
                    <button type="submit"
                            class="rounded-lg bg-blue-600 px-5 py-2.5 text-sm font-semibold text-white hover:bg-blue-500 transition-colors">
                        Deployment starten
                    </button>
                    <a href="{{ route('servers.deployments.index', $server) }}"
                       class="rounded-lg border border-zinc-700 px-5 py-2.5 text-sm font-medium text-zinc-400 hover:bg-zinc-800 hover:text-zinc-200 transition-colors">
                        Abbrechen
                    </a>
                </div>
            </form>
        </div>
    </div>
</x-layouts.app>
