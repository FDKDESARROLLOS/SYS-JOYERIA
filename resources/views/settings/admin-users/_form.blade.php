@php
    $adminUserRoles = [
        'admin' => 'Administrador',
        'technical' => 'Equipo técnico',
    ];

    $adminUserStatuses = [
        1 => 'Activo',
        0 => 'Inactivo',
    ];

    $formUser = $user ?? null;
@endphp

<form id="admin-user-form" method="POST"
    action="{{ $isEditing ? route('settings.admin-users.update', $formUser) : route('settings.admin-users.store') }}"
    class="space-y-7">
    @csrf

    @if ($isEditing)
        @method('PATCH')
    @endif

    <x-admin.form-section number="01" title="Información del usuario"
        description="Define los datos básicos y el nivel de acceso del usuario administrativo."
        id="admin-user-information-title">
        <div class="grid gap-5 sm:grid-cols-2">

            <label>
                <span class="admin-form-label admin-form-label-required">
                    Nombre
                </span>

                <input type="text" name="name" value="{{ old('name', $formUser?->name) }}"
                    placeholder="Ej. Administrador" class="admin-form-input" required />

                @error('name')
                    <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                @enderror
            </label>

            <label>
                <span class="admin-form-label admin-form-label-required">
                    Correo
                </span>

                <input type="email" name="email" value="{{ old('email', $formUser?->email) }}"
                    placeholder="nombre@tienda.com" class="admin-form-input" required />

                @error('email')
                    <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                @enderror
            </label>

            <label>
                <span class="admin-form-label admin-form-label-required">
                    Rol
                </span>

                <select name="role" class="admin-form-input" required>
                    @foreach ($adminUserRoles as $value => $label)
                        <option value="{{ $value }}" @selected(old('role', $formUser?->role) === $value)>
                            {{ $label }}
                        </option>
                    @endforeach
                </select>

                @error('role')
                    <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                @enderror
            </label>

            <label>
                <span class="admin-form-label admin-form-label-required">
                    Estado
                </span>

                <select name="status" class="admin-form-input" required>
                    @foreach ($adminUserStatuses as $value => $label)
                        <option value="{{ $value }}" @selected((string) old('status', $formUser?->status ?? 1) === (string) $value)>
                            {{ $label }}
                        </option>
                    @endforeach
                </select>

                @error('status')
                    <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                @enderror
            </label>

        </div>
    </x-admin.form-section>

    <x-admin.form-section number="02" title="Contraseña"
        description="{{ $isEditing
            ? 'Deja estos campos vacíos si no deseas cambiar la contraseña.'
            : 'Define la contraseña inicial del usuario administrativo.' }}"
        id="admin-user-password-title">
        <div class="grid gap-5 sm:grid-cols-2">

            <label>
                <span class="admin-form-label {{ $isEditing ? '' : 'admin-form-label-required' }}">
                    {{ $isEditing ? 'Nueva contraseña' : 'Contraseña' }}
                </span>

                <input type="password" name="password" autocomplete="new-password" class="admin-form-input"
                    @required(!$isEditing) />

                @error('password')
                    <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                @enderror
            </label>

            <label>
                <span class="admin-form-label {{ $isEditing ? '' : 'admin-form-label-required' }}">
                    {{ $isEditing ? 'Confirmar nueva contraseña' : 'Confirmar contraseña' }}
                </span>

                <input type="password" name="password_confirmation" autocomplete="new-password" class="admin-form-input"
                    @required(!$isEditing) />
            </label>

        </div>
    </x-admin.form-section>
</form>
