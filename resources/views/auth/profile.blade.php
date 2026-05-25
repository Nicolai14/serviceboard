<x-layouts.app title="Mein Profil">
    <div class="max-w-xl space-y-6">

        {{-- Profile Info --}}
        <div class="rounded-xl border border-zinc-800 bg-zinc-900 p-6">
            <h2 class="text-sm font-semibold text-zinc-100 mb-5">Profil-Informationen</h2>

            <form method="POST" action="{{ route('profile.update') }}" class="space-y-4">
                @csrf
                @method('PATCH')

                <div>
                    <label for="name" class="block text-sm font-medium text-zinc-400 mb-1.5">Name</label>
                    <input type="text" id="name" name="name" value="{{ old('name', $user->name) }}" required
                           class="w-full rounded-lg border px-3.5 py-2.5 text-sm bg-zinc-800 border-zinc-700 text-zinc-100
                                  focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent
                                  @error('name') border-red-500 @enderror">
                    @error('name') <p class="mt-1.5 text-xs text-red-400">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label for="email" class="block text-sm font-medium text-zinc-400 mb-1.5">E-Mail</label>
                    <input type="email" id="email" name="email" value="{{ old('email', $user->email) }}" required
                           class="w-full rounded-lg border px-3.5 py-2.5 text-sm bg-zinc-800 border-zinc-700 text-zinc-100
                                  focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent
                                  @error('email') border-red-500 @enderror">
                    @error('email') <p class="mt-1.5 text-xs text-red-400">{{ $message }}</p> @enderror
                </div>

                <button type="submit"
                        class="rounded-lg bg-blue-600 px-4 py-2.5 text-sm font-semibold text-white hover:bg-blue-500 transition-colors">
                    Speichern
                </button>
            </form>
        </div>

        {{-- Dashboard Visibility --}}
        <div class="rounded-xl border border-zinc-800 bg-zinc-900 p-6">
            <h2 class="text-sm font-semibold text-zinc-100 mb-1">Dashboard-Sichtbarkeit</h2>
            <p class="text-xs text-zinc-500 mb-5">Wenn aktiv, können andere eingeloggte User dein Dashboard read-only ansehen.</p>

            <form method="POST" action="{{ route('profile.visibility') }}">
                @csrf
                @method('PATCH')

                <label class="flex items-center justify-between gap-4 cursor-pointer">
                    <div class="min-w-0">
                        <p class="text-sm font-medium text-zinc-200">Dashboard öffentlich machen</p>
                        <p class="text-xs text-zinc-500 mt-0.5">
                            URL für andere: <code class="text-blue-400">{{ url('/u/' . $user->id) }}</code>
                        </p>
                    </div>
                    <input type="hidden" name="dashboard_public" value="0">
                    <input type="checkbox" name="dashboard_public" value="1"
                           {{ $user->dashboard_public ? 'checked' : '' }}
                           onchange="this.form.submit()"
                           class="sr-only peer">
                    <span class="relative inline-flex h-6 w-11 shrink-0 rounded-full border-2 border-transparent transition-colors duration-200
                                 {{ $user->dashboard_public ? 'bg-blue-500' : 'bg-zinc-700' }}">
                        <span class="inline-block h-5 w-5 rounded-full bg-white shadow transform transition-transform duration-200
                                     {{ $user->dashboard_public ? 'translate-x-5' : 'translate-x-0' }}"></span>
                    </span>
                </label>
            </form>
        </div>

        {{-- Change Password --}}
        <div class="rounded-xl border border-zinc-800 bg-zinc-900 p-6">
            <h2 class="text-sm font-semibold text-zinc-100 mb-5">Passwort ändern</h2>

            <form method="POST" action="{{ route('profile.password') }}" class="space-y-4">
                @csrf
                @method('PATCH')

                <div>
                    <label for="current_password" class="block text-sm font-medium text-zinc-400 mb-1.5">Aktuelles Passwort</label>
                    <input type="password" id="current_password" name="current_password" required
                           class="w-full rounded-lg border px-3.5 py-2.5 text-sm bg-zinc-800 border-zinc-700 text-zinc-100
                                  focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent
                                  @error('current_password') border-red-500 @enderror">
                    @error('current_password') <p class="mt-1.5 text-xs text-red-400">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label for="password" class="block text-sm font-medium text-zinc-400 mb-1.5">Neues Passwort</label>
                    <input type="password" id="password" name="password" required
                           class="w-full rounded-lg border px-3.5 py-2.5 text-sm bg-zinc-800 border-zinc-700 text-zinc-100
                                  focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent
                                  @error('password') border-red-500 @enderror">
                    @error('password') <p class="mt-1.5 text-xs text-red-400">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label for="password_confirmation" class="block text-sm font-medium text-zinc-400 mb-1.5">Passwort bestätigen</label>
                    <input type="password" id="password_confirmation" name="password_confirmation" required
                           class="w-full rounded-lg border px-3.5 py-2.5 text-sm bg-zinc-800 border-zinc-700 text-zinc-100
                                  focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                </div>

                <button type="submit"
                        class="rounded-lg bg-blue-600 px-4 py-2.5 text-sm font-semibold text-white hover:bg-blue-500 transition-colors">
                    Passwort ändern
                </button>
            </form>
        </div>
    </div>
</x-layouts.app>
