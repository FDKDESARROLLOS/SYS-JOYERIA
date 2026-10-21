@extends('layouts.auth', ['title' => 'Recuperar contraseña'])

@section('content')
    <div>
        <p class="text-[10px] font-semibold uppercase tracking-[.22em] text-aurea-focus">Recuperación de acceso</p>
        <h2 id="auth-title" class="mt-3 font-brand text-4xl text-[#222222]">Recuperar contraseña</h2>
        <p class="mt-3 text-sm leading-6 text-aurea-muted">Ingresa tu correo electrónico y te enviaremos las instrucciones
            para restablecer tu contraseña.</p>
    </div>

    <form class="mt-8 space-y-5" aria-label="Recuperar contraseña" method="POST" action="{{ route('password.email') }}">
        @csrf

        <label class="block">
            <span class="admin-form-label">Correo electrónico</span>

            <input type="email" name="email" value="{{ old('email') }}" required autocomplete="email"
                placeholder="nombre@tienda.com" class="admin-form-input aurea-focus" />

            @error('email')
                <p class="mt-1 text-xs text-red-600">
                    {{ $message }}
                </p>
            @enderror
        </label>

        @if (session('status'))
            <p class="rounded-lg bg-green-50 px-3 py-2 text-sm text-green-700">
                {{ session('status') }}
            </p>
        @endif

        <button type="submit"
            class="aurea-focus inline-flex w-full cursor-pointer items-center justify-center rounded-lg bg-aurea-gold px-4 py-3 text-sm font-medium text-white shadow-sm hover:bg-[#966f3f]">
            Enviar instrucciones
        </button>

        <a href="{{ route('login') }}"
            class="aurea-focus flex w-full items-center justify-center rounded-lg border border-aurea-border px-4 py-3 text-sm font-medium text-aurea-muted hover:bg-aurea-paper hover:text-aurea-focus">
            Volver a iniciar sesión
        </a>
    </form>
@endsection
