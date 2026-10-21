@php
    $categoryStatuses = [
        1 => 'Activo',
        0 => 'Inactivo',
    ];
@endphp

<form id="category-form" method="POST"
    action="{{ $isEditing ? route('categories.update', $category) : route('categories.store') }}" class="space-y-7">
    @csrf

    @if ($isEditing)
        @method('PATCH')
    @endif

    <x-admin.form-section number="01" title="Información general"
        description="Define la identidad y la organización principal de la categoría." id="general-title">
        <div class="grid gap-5 sm:grid-cols-2">
            <label class="sm:col-span-2">
                <span class="admin-form-label admin-form-label-required">Nombre</span>
                <input id="category-name" type="text" name="name" value="{{ old('name', $category->name) }}"
                    placeholder="Ej. Anillos"
                    class="admin-form-input" required />
                @error('name')
                    <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                @enderror
            </label>

            <label>
                <span class="admin-form-label">Slug</span>
                <input id="category-slug-preview" type="text" value="{{ $category->slug }}"
                    class="admin-form-input bg-[#f8f9f6] text-[#929b94]" readonly aria-live="polite" />
                <span class="mt-1 block text-xs text-[#929b94]">Se genera automáticamente a partir del nombre.</span>
            </label>

            <label>
                <span class="admin-form-label">Categoría padre</span>
                <select name="parent_id" class="admin-form-input">
                    <option value="">Categoría principal</option>
                    @foreach ($parentCategories as $parentCategory)
                        <option value="{{ $parentCategory->id }}"
                            @selected((string) old('parent_id', $category->parent_id) === (string) $parentCategory->id)>
                            {{ $parentCategory->name }}
                        </option>
                    @endforeach
                </select>
                @error('parent_id')
                    <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                @enderror
            </label>
        </div>
    </x-admin.form-section>

    <x-admin.form-section number="02" title="Configuración"
        description="Define el orden y la visibilidad de la categoría." id="settings-title">
        <div class="grid gap-5 sm:grid-cols-2">
            <label>
                <span class="admin-form-label admin-form-label-required">Estado</span>
                <select name="status" class="admin-form-input" required>
                    @foreach ($categoryStatuses as $value => $label)
                        <option value="{{ $value }}"
                            @selected((string) old('status', $category->status ?? true) === (string) $value)>
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
        const categoryNameInput = document.getElementById('category-name');
        const categorySlugPreview = document.getElementById('category-slug-preview');

        if (categoryNameInput && categorySlugPreview) {
            const slugify = (value) => value
                .normalize('NFD')
                .replace(/[\u0300-\u036f]/g, '')
                .toLowerCase()
                .replace(/[^a-z0-9]+/g, '-')
                .replace(/^-+|-+$/g, '');

            categoryNameInput.addEventListener('input', () => {
                categorySlugPreview.value = slugify(categoryNameInput.value);
            });

            if (!categorySlugPreview.value && categoryNameInput.value) {
                categorySlugPreview.value = slugify(categoryNameInput.value);
            }
        }
    </script>
@endPushOnce
