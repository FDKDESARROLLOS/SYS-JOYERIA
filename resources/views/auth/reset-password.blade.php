@extends('layouts.auth', ['title' => 'Crear nueva contraseña'])

@section('content')
    <div>
        <p class="text-[10px] font-semibold uppercase tracking-[.22em] text-aurea-focus">
            Nueva contraseña
        </p>

        <h2 id="auth-title" class="mt-3 font-brand text-4xl text-[#222222]">
            Crear nueva contraseña
        </h2>

        <p class="mt-3 text-sm leading-6 text-aurea-muted">
            Define una nueva contraseña para volver a acceder al panel administrativo.
        </p>
    </div>

    <form method="POST" action="{{ route('password.update') }}" class="mt-8 space-y-5" aria-label="Crear nueva contraseña">
        @csrf

        <input type="hidden" name="token" value="{{ $token }}">
        <input type="hidden" name="email" value="{{ old('email', $email) }}">

        <x-auth.password-field id="new-password" name="password" label="Nueva contraseña" autocomplete="new-password"
            placeholder="Ingresa una nueva contraseña" />

        <x-auth.password-field id="confirm-password" name="password_confirmation" label="Confirmar contraseña"
            autocomplete="new-password" placeholder="Repite la nueva contraseña" />

        @if ($errors->any())
            <div class="rounded-lg bg-red-50 px-3 py-2 text-sm text-red-600">
                @foreach ($errors->all() as $error)
                    <p>{{ $error }}</p>
                @endforeach
            </div>
        @endif

        <button type="submit"
            class="aurea-focus inline-flex w-full cursor-pointer items-center justify-center rounded-lg bg-aurea-gold px-4 py-3 text-sm font-medium text-white shadow-sm hover:bg-[#966f3f]">
            Actualizar contraseña
        </button>

        <a href="{{ route('login') }}"
            class="aurea-focus flex w-full items-center justify-center rounded-lg border border-aurea-border px-4 py-3 text-sm font-medium text-aurea-muted hover:bg-aurea-paper hover:text-aurea-focus">
            Volver a iniciar sesión
        </a>
    </form>
@endsection
