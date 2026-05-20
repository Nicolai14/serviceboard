<x-layouts.guest title="Passwort zurücksetzen">
    <h2 class="mb-2 text-lg font-semibold text-white">Passwort vergessen?</h2>
    <p class="mb-6 text-sm text-zinc-500">Gib deine E-Mail-Adresse ein und wir senden dir einen Reset-Link.</p>

    @if (session('status'))
        <div class="mb-4 rounded-lg border border-green-800 bg-green-900/30 px-4 py-3 text-sm text-green-400">
            {{ session('status') }}
        </div>
    @endif

    <form method="POST" action="{{ route('password.email') }}" class="space-y-4">
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

        <button type="submit"
                class="w-full rounded-lg bg-blue-600 px-4 py-2.5 text-sm font-semibold text-white
                       hover:bg-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2
                       focus:ring-offset-zinc-900 transition-colors">
            Reset-Link senden
        </button>
    </form>

    <p class="mt-6 text-center text-sm text-zinc-500">
        <a href="{{ route('login') }}" class="text-blue-400 hover:text-blue-300 transition-colors">
            ← Zurück zur Anmeldung
        </a>
    </p>
</x-layouts.guest>
