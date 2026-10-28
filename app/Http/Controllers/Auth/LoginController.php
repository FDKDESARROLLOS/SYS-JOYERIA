<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class LoginController extends Controller
{
    public function create(): View
    {
        return view('auth.login');
    }

    public function store(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        $remember = $request->boolean('remember');

        $authenticated = Auth::attempt([
            'email' => $credentials['email'],
            'password' => $credentials['password'],
            'status' => true,
        ], $remember);

        if (! $authenticated) {
            return back()
                ->withErrors([
                    'email' => 'Las credenciales ingresadas no son válidas.',
                ])
                ->onlyInput('email');
        }

        $request->session()->regenerate();

        $request->user()->update([
            'last_login_at' => now(),
        ]);

        return match ($request->user()->role) {
            'admin' => redirect()->intended(route('dashboard')),
            'technical' => redirect()->route('settings.errors.logs'),
            default => redirect()->route('profile'),
        };
    }

    public function destroy(Request $request): RedirectResponse
    {
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }
}
