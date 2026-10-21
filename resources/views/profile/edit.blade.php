@extends('layouts.admin', ['title' => 'Mi perfil', 'sectionTitle' => 'Mi perfil'])

@section('content')
    @php
        $initials = collect(preg_split('/\s+/', trim($user->name)))
            ->filter()
            ->take(2)
            ->map(fn($namePart) => mb_strtoupper(mb_substr($namePart, 0, 1)))
            ->implode('');

        $roleLabel = match ($user->role) {
            'admin' => 'Administrador',
            'technical' => 'Equipo técnico',
            default => ucfirst($user->role),
        };
    @endphp

    <x-admin.page-header :breadcrumbs="[
        [
            'label' => 'Mi perfil',
            'url' => null,
        ],
    ]" title="Mi perfil"
        description="Consulta y actualiza la información visible de tu cuenta administrativa." :required="true">
        <x-slot:actions>
            <button type="submit" form="profile-form"
                class="aurea-focus inline-flex items-center justify-center rounded-lg bg-aurea-gold px-4 py-2.5 text-sm font-medium text-white shadow-sm hover:bg-[#9f793f]">
                Guardar cambios
            </button>
        </x-slot:actions>
    </x-admin.page-header>

    <form id="profile-form" method="POST" action="{{ route('profile.update') }}" class="space-y-7">
        @csrf
        @method('PATCH')

        <x-admin.form-section number="01" title="Información personal"
            description="Mantén actualizados los datos con los que administras {{ config('aurea-demo.store_settings.store_name', 'Tienda') }}."
            id="profile-information-title">
            <div class="flex items-center gap-4 border-b border-[#eef0ec] pb-5">
                <div class="flex h-14 w-14 shrink-0 items-center justify-center rounded-full bg-[#eadfce] text-sm font-semibold text-[#806441]"
                    aria-label="Avatar de {{ $user->name }}">
                    {{ $initials }}
                </div>

                <div>
                    <p class="text-sm font-medium text-aurea-ink">
                        {{ $user->name }}
                    </p>

                    <p class="mt-1 text-xs text-aurea-muted">
                        {{ $roleLabel }}
                    </p>
                </div>
            </div>

            @if (session('status'))
                <div class="mt-5 rounded-lg bg-green-50 px-4 py-3 text-sm text-green-700">
                    {{ session('status') }}
                </div>
            @endif

            <div class="mt-5 grid gap-5 sm:grid-cols-2">
                <label for="profile-name">
                    <span class="admin-form-label admin-form-label-required">
                        Nombre
                    </span>

                    <input id="profile-name" type="text" name="name" value="{{ old('name', $user->name) }}"
                        class="admin-form-input aurea-focus" autocomplete="name" required />

                    @error('name')
                        <p class="mt-1 text-xs text-red-600">
                            {{ $message }}
                        </p>
                    @enderror
                </label>

                <label for="profile-email">
                    <span class="admin-form-label admin-form-label-required">
                        Correo
                    </span>

                    <input id="profile-email" type="email" name="email" value="{{ old('email', $user->email) }}"
                        class="admin-form-input aurea-focus" autocomplete="email" required />

                    @error('email')
                        <p class="mt-1 text-xs text-red-600">
                            {{ $message }}
                        </p>
                    @enderror
                </label>

                <div>
                    <span class="admin-form-label">
                        Rol
                    </span>

                    <div
                        class="flex min-h-11 items-center rounded-lg border border-[#dfe2dc] bg-[#fbfbf9] px-3 text-sm text-[#56625a]">
                        {{ $roleLabel }}
                    </div>
                </div>
            </div>
        </x-admin.form-section>
    </form>
@endsection
