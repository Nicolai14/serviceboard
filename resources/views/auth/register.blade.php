<x-layouts.guest title="Registrieren">
    <h2 class="mb-6 text-lg font-semibold text-white">Konto erstellen</h2>

    <form method="POST" action="{{ route('register') }}" class="space-y-4">
        @csrf

        <div>
            <label for="name" class="block text-sm font-medium text-zinc-400 mb-1.5">Name</label>
            <input type="text" id="name" name="name" value="{{ old('name') }}" required autofocus autocomplete="name"
                   class="w-full rounded-lg border px-3.5 py-2.5 text-sm bg-zinc-800 border-zinc-700 text-zinc-100 placeholder-zinc-500
                          focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent
                          @error('name') border-red-500 @enderror"
                   placeholder="Max Mustermann">
            @error('name')
                <p class="mt-1.5 text-xs text-red-400">{{ $message }}</p>
            @enderror
        </div>

        <div>
            <label for="email" class="block text-sm font-medium text-zinc-400 mb-1.5">E-Mail</label>
            <input type="email" id="email" name="email" value="{{ old('email') }}" required autocomplete="email"
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
            <input type="password" id="password" name="password" required autocomplete="new-password"
                   class="w-full rounded-lg border px-3.5 py-2.5 text-sm bg-zinc-800 border-zinc-700 text-zinc-100 placeholder-zinc-500
                          focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent
                          @error('password') border-red-500 @enderror"
                   placeholder="Mindestens 8 Zeichen">
            @error('password')
                <p class="mt-1.5 text-xs text-red-400">{{ $message }}</p>
            @enderror
        </div>

        <div>
            <label for="password_confirmation" class="block text-sm font-medium text-zinc-400 mb-1.5">Passwort bestätigen</label>
            <input type="password" id="password_confirmation" name="password_confirmation" required autocomplete="new-password"
                   class="w-full rounded-lg border px-3.5 py-2.5 text-sm bg-zinc-800 border-zinc-700 text-zinc-100 placeholder-zinc-500
                          focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                   placeholder="••••••••">
        </div>

        <button type="submit"
                class="w-full rounded-lg bg-blue-600 px-4 py-2.5 text-sm font-semibold text-white
                       hover:bg-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2
                       focus:ring-offset-zinc-900 transition-colors">
            Konto erstellen
        </button>
    </form>

    <p class="mt-6 text-center text-sm text-zinc-500">
        Bereits registriert?
        <a href="{{ route('login') }}" class="text-blue-400 hover:text-blue-300 transition-colors font-medium">
            Anmelden
        </a>
    </p>
</x-layouts.guest>
