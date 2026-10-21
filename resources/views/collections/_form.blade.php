@php
    $collectionStatuses = [
        1 => 'Activa',
        0 => 'Inactiva',
    ];
@endphp

<form id="collection-form" method="POST"
    action="{{ $isEditing ? route('collections.update', $collection) : route('collections.store') }}" class="space-y-7">
    @csrf

    @if ($isEditing)
        @method('PATCH')
    @endif

    @if ($errors->any())
        <div class="rounded-xl border border-[#ecd0cb] bg-[#fff8f6] px-4 py-3 text-sm text-aurea-danger" role="alert">
            Revisa los campos marcados para continuar.
        </div>
    @endif

    <x-admin.form-section number="01" title="Información general"
        description="Define el nombre y la presencia de la colección dentro del catálogo." id="collection-general-title">
        <div class="grid gap-5 sm:grid-cols-2">
            <label class="sm:col-span-2">
                <span class="admin-form-label admin-form-label-required">Nombre</span>
                <input id="collection-name" type="text" name="name" value="{{ old('name', $collection->name) }}"
                    placeholder="Ej. Eternal"
                    class="admin-form-input" required />
                @error('name')
                    <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                @enderror
            </label>

            <label>
                <span class="admin-form-label">Slug</span>
                <input id="collection-slug-preview" type="text"
                    value="{{ $collection->slug }}" class="admin-form-input bg-[#f8f9f6] text-[#929b94]"
                    readonly aria-live="polite" />
                <span class="mt-1 block text-xs text-[#929b94]">Se genera automáticamente a partir del nombre.</span>
            </label>

            <label>
                <span class="admin-form-label admin-form-label-required">Estado</span>
                <select name="status" class="admin-form-input" required>
                    @foreach ($collectionStatuses as $value => $label)
                        <option value="{{ $value }}"
                            @selected((string) old('status', $collection->status ?? true) === (string) $value)>
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
        const collectionNameInput = document.getElementById('collection-name');
        const collectionSlugPreview = document.getElementById('collection-slug-preview');

        if (collectionNameInput && collectionSlugPreview) {
            const slugify = (value) => value
                .normalize('NFD')
                .replace(/[\u0300-\u036f]/g, '')
                .toLowerCase()
                .replace(/[^a-z0-9]+/g, '-')
                .replace(/^-+|-+$/g, '');

            collectionNameInput.addEventListener('input', () => {
                collectionSlugPreview.value = slugify(collectionNameInput.value);
            });

            if (!collectionSlugPreview.value && collectionNameInput.value) {
                collectionSlugPreview.value = slugify(collectionNameInput.value);
            }
        }
    </script>
@endPushOnce
