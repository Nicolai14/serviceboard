<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

class ProfileController extends Controller
{
    public function show(Request $request): View
    {
        return view('auth.profile', ['user' => $request->user()]);
    }

    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name'  => ['required', 'string', 'max:100'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email,' . $request->user()->id],
        ]);

        $request->user()->update($validated);

        return back()->with('success', 'Profil wurde aktualisiert.');
    }

    public function updateVisibility(Request $request): RedirectResponse
    {
        $request->validate(['dashboard_public' => ['nullable']]);

        $request->user()->update([
            'dashboard_public' => $request->boolean('dashboard_public'),
        ]);

        return back()->with('success', $request->user()->dashboard_public
            ? 'Dein Dashboard ist jetzt öffentlich sichtbar.'
            : 'Dein Dashboard ist jetzt wieder privat.'
        );
    }

    public function updatePassword(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'current_password' => ['required', 'current_password'],
            'password'         => ['required', Password::defaults(), 'confirmed'],
        ]);

        $request->user()->update([
            'password' => Hash::make($validated['password']),
        ]);

        return back()->with('success', 'Passwort wurde geändert.');
    }
}
