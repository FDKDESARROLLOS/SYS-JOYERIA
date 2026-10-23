@php
    $severities = [
        'error' => 'Error',
        'warning' => 'Advertencia',
        'info' => 'Informativo',
    ];
@endphp

<form method="POST" action="{{ $isEditing ? route('settings.errors.update', $errorCode) : route('settings.errors.store') }}" class="space-y-7">
    @csrf

    @if ($isEditing)
        @method('PATCH')
    @endif

    <x-admin.form-section
        number="01"
        title="Información del código"
        description="Usa una identificación estable y una descripción clara para facilitar el diagnóstico."
        id="error-code-general-title"
    >
        <div class="grid gap-5 sm:grid-cols-2">
            <label>
                <span class="admin-form-label admin-form-label-required">Código</span>
                <input id="error-code-code" type="text" name="code" value="{{ old('code', $errorCode->code) }}" placeholder="Ej. INV-001" class="admin-form-input" required />
                @error('code')<p class="mt-1 text-xs text-red-600 dark:text-red-400">{{ $message }}</p>@enderror
            </label>

            <label>
                <span class="admin-form-label admin-form-label-required">Módulo</span>
                <input id="error-code-module" type="text" name="module" value="{{ old('module', $errorCode->module) }}" placeholder="Ej. Inventario" class="admin-form-input" required />
                @error('module')<p class="mt-1 text-xs text-red-600 dark:text-red-400">{{ $message }}</p>@enderror
            </label>

            <label class="sm:col-span-2">
                <span class="admin-form-label admin-form-label-required">Título</span>
                <input id="error-code-title" type="text" name="title" value="{{ old('title', $errorCode->title) }}" placeholder="Ej. Stock insuficiente" class="admin-form-input" required />
                @error('title')<p class="mt-1 text-xs text-red-600 dark:text-red-400">{{ $message }}</p>@enderror
            </label>

            <label class="sm:col-span-2">
                <span class="admin-form-label">Descripción</span>
                <textarea id="error-code-description" name="description" rows="4" placeholder="Describe cuándo se utiliza este código." class="admin-form-input">{{ old('description', $errorCode->description) }}</textarea>
                @error('description')<p class="mt-1 text-xs text-red-600 dark:text-red-400">{{ $message }}</p>@enderror
            </label>

            <label>
                <span class="admin-form-label admin-form-label-required">Severidad</span>
                <select name="severity" class="admin-form-input" required>
                    @foreach ($severities as $value => $label)
                        <option value="{{ $value }}" @selected(old('severity', $errorCode->severity) === $value)>{{ $label }}</option>
                    @endforeach
                </select>
                @error('severity')<p class="mt-1 text-xs text-red-600 dark:text-red-400">{{ $message }}</p>@enderror
            </label>

            <label>
                <span class="admin-form-label admin-form-label-required">Estado</span>
                <select name="status" class="admin-form-input" required>
                    <option value="1" @selected((string) old('status', $errorCode->status ?? true) === '1')>Activo</option>
                    <option value="0" @selected((string) old('status', $errorCode->status ?? true) === '0')>Inactivo</option>
                </select>
                @error('status')<p class="mt-1 text-xs text-red-600 dark:text-red-400">{{ $message }}</p>@enderror
            </label>
        </div>
    </x-admin.form-section>

    <div class="flex flex-col-reverse justify-end gap-3 sm:flex-row">
        <a href="{{ route('settings.errors') }}" class="aurea-focus inline-flex items-center justify-center rounded-lg border border-aurea-border px-4 py-2.5 text-sm font-medium text-[#68736c] dark:text-[#d0d4cf]">Cancelar</a>
        <button type="submit" class="aurea-focus inline-flex items-center justify-center rounded-lg bg-aurea-gold px-4 py-2.5 text-sm font-medium text-white shadow-sm transition hover:bg-[#9f793f]">{{ $isEditing ? 'Guardar cambios' : 'Crear código' }}</button>
    </div>
</form>
