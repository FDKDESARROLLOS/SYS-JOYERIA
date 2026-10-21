@php
    $materialStatuses = [
        1 => 'Activo',
        0 => 'Inactivo',
    ];
@endphp

<form id="material-form" method="POST"
    action="{{ $isEditing ? route('materials.update', $material) : route('materials.store') }}" class="space-y-7">
    @csrf

    @if ($isEditing)
        @method('PATCH')
    @endif

    <x-admin.form-section
        number="01"
        title="Información general"
        description="Define el nombre y el estado del material dentro del catálogo."
        id="material-general-title"
    >
        <div class="grid gap-5 sm:grid-cols-2">
            <label class="sm:col-span-2">
                <span class="admin-form-label admin-form-label-required">Nombre</span>
                <input id="material-name" type="text" name="name" value="{{ old('name', $material->name) }}"
                    placeholder="Ej. Oro" class="admin-form-input" required />
                @error('name')
                    <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                @enderror
            </label>

            <label>
                <span class="admin-form-label">Slug</span>
                <input id="material-slug-preview" type="text" value="{{ $material->slug }}"
                    class="admin-form-input bg-[#f8f9f6] text-[#929b94]" readonly aria-live="polite" />
                <span class="mt-1 block text-xs text-[#929b94]">Se genera automáticamente a partir del nombre.</span>
            </label>

            <label>
                <span class="admin-form-label admin-form-label-required">Estado</span>
                <select name="status" class="admin-form-input" required>
                    @foreach ($materialStatuses as $value => $label)
                        <option value="{{ $value }}"
                            @selected((string) old('status', $material->status ?? true) === (string) $value)>
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
</form>

@pushOnce('scripts')
    <script>
        const materialNameInput = document.getElementById('material-name');
        const materialSlugPreview = document.getElementById('material-slug-preview');

        if (materialNameInput && materialSlugPreview) {
            const slugify = (value) => value
                .normalize('NFD')
                .replace(/[\u0300-\u036f]/g, '')
                .toLowerCase()
                .replace(/[^a-z0-9]+/g, '-')
                .replace(/^-+|-+$/g, '');

            materialNameInput.addEventListener('input', () => {
                materialSlugPreview.value = slugify(materialNameInput.value);
            });

            if (!materialSlugPreview.value && materialNameInput.value) {
                materialSlugPreview.value = slugify(materialNameInput.value);
            }
        }
    </script>
@endPushOnce
