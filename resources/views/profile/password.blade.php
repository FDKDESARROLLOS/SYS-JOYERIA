@extends('layouts.admin', ['title' => 'Cambiar contraseña', 'sectionTitle' => 'Cambiar contraseña'])

@section('content')
    <x-admin.page-header :breadcrumbs="[
        [
            'label' => 'Mi perfil',
            'url' => route('profile'),
        ],
        [
            'label' => 'Cambiar contraseña',
            'url' => null,
        ],
    ]" title="Cambiar contraseña"
        description="Actualiza la contraseña de acceso a la administración de {{ config('aurea-demo.store_settings.store_name', 'Tienda') }}."
        :required="true">
        <x-slot:actions>
            <button type="submit" form="password-form"
                class="aurea-focus inline-flex items-center justify-center rounded-lg bg-aurea-gold px-4 py-2.5 text-sm font-medium text-white shadow-sm hover:bg-[#9f793f]">
                Actualizar contraseña
            </button>
        </x-slot:actions>
    </x-admin.page-header>

    <form id="password-form" method="POST" action="{{ route('profile.password.update') }}" class="space-y-7">
        @csrf

        <x-admin.form-section number="01" title="Seguridad de la cuenta"
            description="Usa una contraseña segura para proteger el acceso administrativo." id="password-information-title">
            <div class="max-w-2xl space-y-5">

                @if (session('status'))
                    <div class="rounded-lg bg-green-50 px-4 py-3 text-sm text-green-700">
                        {{ session('status') }}
                    </div>
                @endif

                <label for="current-password">
                    <span class="admin-form-label admin-form-label-required">
                        Contraseña actual
                    </span>

                    <input id="current-password" type="password" name="current_password"
                        class="admin-form-input aurea-focus" autocomplete="current-password" required />

                    @error('current_password')
                        <p class="mt-1 text-xs text-red-600">
                            {{ $message }}
                        </p>
                    @enderror
                </label>

                <label for="new-password">
                    <span class="admin-form-label admin-form-label-required">
                        Nueva contraseña
                    </span>

                    <input id="new-password" type="password" name="password" class="admin-form-input aurea-focus"
                        autocomplete="new-password" required />

                    @error('password')
                        <p class="mt-1 text-xs text-red-600">
                            {{ $message }}
                        </p>
                    @enderror
                </label>

                <label for="password-confirmation">
                    <span class="admin-form-label admin-form-label-required">
                        Confirmar nueva contraseña
                    </span>

                    <input id="password-confirmation" type="password" name="password_confirmation"
                        class="admin-form-input aurea-focus" autocomplete="new-password" required />

                    @error('password_confirmation')
                        <p class="mt-1 text-xs text-red-600">
                            {{ $message }}
                        </p>
                    @enderror
                </label>

            </div>
        </x-admin.form-section>
    </form>
@endsection
