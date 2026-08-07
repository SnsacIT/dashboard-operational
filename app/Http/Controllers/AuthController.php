<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;

class AuthController extends Controller
{
    public function showLogin(): View
    {
        return view('auth.login');
    }

    public function login(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'nip' => ['required', 'string'],
            'password' => ['required', 'string'],
        ]);

        $user = \App\Models\User::query()
            ->where('nip', $credentials['nip'])
            ->first();

        $passwordIsValid = $user && $this->passwordMatches($credentials['password'], (string) $user->password);

        if (! $passwordIsValid) {
            return back()
                ->withErrors(['nip' => 'NIP atau password tidak sesuai.'])
                ->onlyInput('nip');
        }

        Auth::login($user, $request->boolean('remember'));

        $user->forceFill([
            'last_login_at' => now('Asia/Jakarta'),
        ])->save();

        $request->session()->regenerate();

        return redirect()->route('dashboard');
    }

    public function logout(Request $request): RedirectResponse
    {
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }

    private function passwordMatches(string $plainPassword, string $hashedPassword): bool
    {
        if ($hashedPassword === '') {
            return false;
        }

        if (str_starts_with($hashedPassword, '$2y$')) {
            return Hash::check($plainPassword, $hashedPassword);
        }

        return password_verify($plainPassword, $hashedPassword);
    }
}
