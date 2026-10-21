<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

class AdminUserController extends Controller
{
    public function index(): View
    {
        $users = User::query()
            ->orderBy('name')
            ->paginate(20);

        return view('settings.admin-users.index', [
            'users' => $users,
        ]);
    }

    public function create(): View
    {
        return view('settings.admin-users.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'role' => ['required', Rule::in(['admin', 'technical'])],
            'status' => ['required', 'boolean'],
            'password' => [
                'required',
                'confirmed',
                Password::min(8),
            ],
        ]);

        User::create($validated);

        return redirect()
            ->route('settings.admin-users.index')
            ->with('status', 'Usuario creado correctamente.');
    }

    public function edit(User $user): View
    {
        return view('settings.admin-users.edit', [
            'user' => $user,
        ]);
    }

    public function update(Request $request, User $user): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => [
                'required',
                'email',
                'max:255',
                Rule::unique('users', 'email')->ignore($user->id),
            ],
            'role' => ['required', Rule::in(['admin', 'technical'])],
            'status' => ['required', 'boolean'],
        ]);

        $user->update($validated);

        return back()->with('status', 'Usuario actualizado correctamente.');
    }

    public function destroy(Request $request, User $user): RedirectResponse
    {
        if ($request->user()->is($user)) {
            return back()->withErrors([
                'user' => 'No puedes eliminar tu propio usuario.',
            ]);
        }

        $user->delete();

        return redirect()
            ->route('settings.admin-users.index')
            ->with('status', 'Usuario eliminado correctamente.');
    }
}
