@props([
    'id',
    'name',
    'label',
    'autocomplete',
    'placeholder',
])

<label class="block">
    <span class="admin-form-label">{{ $label }}</span>
    <span class="admin-form-input-prefix aurea-focus">
        <input
            id="{{ $id }}"
            type="password"
            name="{{ $name }}"
            autocomplete="{{ $autocomplete }}"
            placeholder="{{ $placeholder }}"
            aria-describedby="{{ $id }}-help"
        />
        <button
            type="button"
            class="aurea-focus shrink-0 rounded-md p-1 text-aurea-muted hover:bg-aurea-paper hover:text-aurea-focus"
            data-password-toggle
            data-target="{{ $id }}"
            aria-label="Mostrar contraseña"
            title="Mostrar contraseña"
            aria-pressed="false"
        >
            <span data-password-show><x-admin.icon name="eye" class="h-[18px] w-[18px]" /></span>
            <span data-password-hide class="hidden"><x-admin.icon name="eye-off" class="h-[18px] w-[18px]" /></span>
        </button>
    </span>
    <span id="{{ $id }}-help" class="sr-only">Puedes mostrar u ocultar la contraseña.</span>
</label>
