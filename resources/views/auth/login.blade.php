@extends('layouts.auth', ['title' => 'Iniciar sesión'])

@section('content')
    <div>
        <p class="text-[10px] font-semibold uppercase tracking-[.22em] text-aurea-focus">Acceso administrativo</p>
        <h2 id="auth-title" class="mt-3 font-brand text-4xl text-[#222222]">Iniciar sesión</h2>
        <p class="mt-3 text-sm leading-6 text-aurea-muted">Ingresa tus datos para acceder al panel administrativo.</p>
    </div>
    <form method="POST" action="{{ route('login.store') }}" class="mt-8 space-y-5"
        aria-label="Inicio de sesión administrativo">

        @csrf

        <label class="block">
            <span class="admin-form-label">Correo electrónico</span>

            <input type="email" name="email" autocomplete="email" placeholder="nombre@tienda.com"
                value="{{ old('email') }}" class="admin-form-input aurea-focus" required />

            @error('email')
                <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
            @enderror
        </label>

        <x-auth.password-field id="login-password" name="password" label="Contraseña" autocomplete="current-password"
            placeholder="Ingresa tu contraseña" />

        <div class="flex items-center justify-between gap-4 pt-1">
            <label class="flex cursor-pointer items-center gap-2 text-xs text-aurea-muted">
                <input type="checkbox" name="remember"
                    class="h-4 w-4 rounded border-[#d9d0c5] text-aurea-gold accent-[#b08a57] focus:ring-aurea-gold-soft" />
                Recordarme
            </label>

            <a href="{{ route('password.request') }}"
                class="aurea-focus rounded text-xs font-medium text-aurea-focus hover:text-[#6f5230]">
                ¿Olvidaste tu contraseña?
            </a>
        </div>

        <button type="submit"
            class="aurea-focus inline-flex w-full cursor-pointer items-center justify-center rounded-lg bg-aurea-gold px-4 py-3 text-sm font-medium text-white shadow-sm hover:bg-[#966f3f]">
            Iniciar sesión
        </button>
    </form>
@endsection
