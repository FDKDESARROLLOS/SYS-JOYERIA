@php
    $sizeStatuses = [
        1 => 'Activo',
        0 => 'Inactivo',
    ];
    $isEditing = isset($size->exists) && $size->exists;
    $selectedCategoryIds = collect(old('category_ids', $selectedCategoryIds ?? []))
        ->map(fn ($categoryId): int => (int) $categoryId)
        ->all();
@endphp

<form id="size-form" method="POST"
    action="{{ $isEditing ? route('sizes.update', $size) : route('sizes.store') }}" class="space-y-7">
    @csrf

    @if ($isEditing)
        @method('PATCH')
    @endif

    <x-admin.form-section
        number="01"
        title="Información general"
        description="Define el nombre, las categorías aplicables y el estado de la talla dentro del catálogo."
        id="size-general-title"
    >
        <div class="grid gap-5 sm:grid-cols-2">
            <label>
                <span class="admin-form-label admin-form-label-required">Nombre</span>
                <input type="text" name="name" value="{{ old('name', $size->name) }}"
                    placeholder="Ej. 7 o 18 cm" class="admin-form-input" required />
                @error('name')
                    <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                @enderror
            </label>

            <label>
                <span class="admin-form-label admin-form-label-required">Estado</span>
                <select name="status" class="admin-form-input" required>
                    @foreach ($sizeStatuses as $value => $label)
                        <option value="{{ $value }}"
                            @selected((string) old('status', $size->status ?? true) === (string) $value)>
                            {{ $label }}
                        </option>
                    @endforeach
                </select>
                @error('status')
                    <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                @enderror
            </label>

            <fieldset class="sm:col-span-2">
                <legend class="admin-form-label">Categorías aplicables</legend>
                <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
                    @forelse ($categories as $category)
                        <label class="group relative">
                            <input
                                type="checkbox"
                                name="category_ids[]"
                                value="{{ $category->id }}"
                                class="peer sr-only"
                                @checked(in_array($category->id, $selectedCategoryIds, true))
                            />
                            <span class="aurea-focus flex cursor-pointer items-center gap-3 rounded-xl border border-[#dfe2dc] bg-white px-3.5 py-3 text-sm text-[#59635c] transition hover:border-aurea-gold group-has-[:checked]:border-aurea-gold group-has-[:checked]:bg-[#fbf8f3] group-has-[:focus-visible]:outline group-has-[:focus-visible]:outline-2 group-has-[:focus-visible]:outline-aurea-focus group-has-[:focus-visible]:outline-offset-2">
                                <span class="flex h-5 w-5 shrink-0 items-center justify-center rounded-md border border-[#cfd4ce] bg-white text-white transition group-has-[:checked]:border-aurea-gold group-has-[:checked]:bg-aurea-gold">
                                    <svg class="h-3.5 w-3.5 opacity-0 transition group-has-[:checked]:opacity-100" viewBox="0 0 16 16"
                                        fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                                        <path d="m3 8 3 3 7-7" />
                                    </svg>
                                </span>
                                <span class="font-medium">{{ $category->name }}</span>
                            </span>
                        </label>
                    @empty
                        <p class="text-sm text-[#929b94]">No hay categorías activas disponibles.</p>
                    @endforelse
                </div>
                <span class="admin-form-help">Puedes seleccionar una o varias categorías principales.</span>
                @error('category_ids')
                    <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                @enderror
                @error('category_ids.*')
                    <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                @enderror
            </fieldset>
        </div>
    </x-admin.form-section>
</form>
