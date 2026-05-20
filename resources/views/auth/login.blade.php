<x-layouts.guest title="Anmelden">
    <h2 class="mb-6 text-lg font-semibold text-white">Willkommen zurück</h2>

    <form method="POST" action="{{ route('login') }}" class="space-y-4">
        @csrf

        <div>
            <label for="email" class="block text-sm font-medium text-zinc-400 mb-1.5">E-Mail</label>
            <input type="email" id="email" name="email" value="{{ old('email') }}" required autofocus autocomplete="email"
                   class="w-full rounded-lg border px-3.5 py-2.5 text-sm bg-zinc-800 border-zinc-700 text-zinc-100 placeholder-zinc-500
                          focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent
                          @error('email') border-red-500 @enderror"
                   placeholder="name@firma.de">
            @error('email')
                <p class="mt-1.5 text-xs text-red-400">{{ $message }}</p>
            @enderror
        </div>

        <div>
            <label for="password" class="block text-sm font-medium text-zinc-400 mb-1.5">Passwort</label>
            <input type="password" id="password" name="password" required autocomplete="current-password"
                   class="w-full rounded-lg border px-3.5 py-2.5 text-sm bg-zinc-800 border-zinc-700 text-zinc-100 placeholder-zinc-500
                          focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                   placeholder="••••••••">
        </div>

        <div class="flex items-center justify-between">
            <label class="flex items-center gap-2 cursor-pointer">
                <input type="checkbox" name="remember" class="h-4 w-4 rounded border-zinc-700 bg-zinc-800 text-blue-600 focus:ring-blue-500">
                <span class="text-sm text-zinc-400">Angemeldet bleiben</span>
            </label>
            <a href="{{ route('password.request') }}" class="text-sm text-blue-400 hover:text-blue-300 transition-colors">
                Passwort vergessen?
            </a>
        </div>

        <button type="submit"
                class="w-full rounded-lg bg-blue-600 px-4 py-2.5 text-sm font-semibold text-white
                       hover:bg-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2
                       focus:ring-offset-zinc-900 transition-colors">
            Anmelden
        </button>
    </form>

    <p class="mt-6 text-center text-sm text-zinc-500">
        Noch kein Konto?
        <a href="{{ route('register') }}" class="text-blue-400 hover:text-blue-300 transition-colors font-medium">
            Registrieren
        </a>
    </p>
</x-layouts.guest>
