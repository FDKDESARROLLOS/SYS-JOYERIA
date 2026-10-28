@php
    $productStatuses = [
        'active' => 'Activo',
        'draft' => 'Borrador',
        'inactive' => 'Inactivo',
    ];
    $karatOptions = [
        10 => '10K',
        14 => '14K',
        18 => '18K',
    ];
    $metalColorOptions = ['Amarillo', 'Blanco', 'Rosa'];
    $selectedCategoryId = old('category_id', $selectedCategoryId ?? null);
    $selectedSubcategoryId = old('subcategory_id', $selectedSubcategoryId ?? null);
    $selectedCollectionId = old('collection_id', $product->collection_id);
    $selectedMaterialId = old('material_id', $product->material_id);
    $selectedBaseSizeId = old('base_size_id', $product->base_size_id);
    $codeWasGenerated = (string) old('code_generated', $codeWasGenerated ?? (filled($product->code) ? '0' : '1'));
    $categorySizes = $categorySizes ?? [];
    $productImages = $product->relationLoaded('images') ? $product->images : collect();
    $centralProductStone = $product->productStones->firstWhere('position', 'central');
    $lateralProductStone = $product->productStones->firstWhere('position', 'lateral');
    $centralStone = old('central_stone', $centralProductStone?->only([
        'stone_id',
        'shape',
        'carats',
        'origin',
        'certification',
        'color',
        'clarity',
        'cut',
    ]) ?? []);
    $sideStone = old('side_stone', $lateralProductStone?->only([
        'stone_id',
        'shape',
        'carats',
        'quantity',
        'origin',
        'certification',
        'color',
        'clarity',
        'cut',
    ]) ?? []);
    $hasSideStones = (bool) old('has_side_stones', $lateralProductStone !== null);
@endphp

<form id="product-form" method="POST"
    action="{{ $isEditing ? route('products.update', $product) : route('products.store') }}"
    enctype="multipart/form-data"
    class="product-form grid items-start gap-7 xl:grid-cols-[minmax(0,1fr)_19rem]">
    @csrf

    <input id="product-code-generated" type="hidden" name="code_generated"
        value="{{ $codeWasGenerated }}" />

    @if ($isEditing)
        @method('PATCH')
    @endif

    <div class="space-y-7">
        @if ($errors->any())
            <div class="rounded-xl border border-[#ecd0cb] bg-[#fff8f6] px-4 py-3 text-sm text-aurea-danger" role="alert">
                Revisa los campos marcados para continuar.
            </div>
        @endif

        @if ($categoryRelationInactive ?? false)
            <div class="rounded-xl border border-[#eadfcf] bg-[#fffaf2] px-4 py-3 text-sm text-[#80602f] dark:border-[#66543c] dark:bg-[#3b352c] dark:text-[#ead7b7]" role="status">
                La categoría histórica de este producto ya no está activa. Puedes conservar la relación actual o seleccionar una categoría vigente antes de guardar.
            </div>
        @endif

        <x-admin.form-section
            number="01"
            title="Información general"
            description="Define la identidad comercial y la clasificación de la pieza."
            id="general-title"
        >
            <div class="grid gap-5 sm:grid-cols-2">
                <label>
                    <span class="admin-form-label admin-form-label-required">Nombre</span>
                    <input id="product-name" type="text" name="name" value="{{ old('name', $product->name) }}"
                        placeholder="Ej. Anillo Aura" class="admin-form-input" required />
                    @error('name') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </label>

                <label>
                    <span class="admin-form-label">Modelo</span>
                    <input type="text" name="model" value="{{ old('model', $product->model) }}"
                        placeholder="Ej. Solitario Aura" class="admin-form-input" />
                    @error('model') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </label>

                <label>
                    <span class="admin-form-label">Subtítulo</span>
                    <input type="text" name="subtitle" value="{{ old('subtitle', $product->subtitle) }}"
                        placeholder="Ej. Elegancia clásica con brillo contemporáneo" class="admin-form-input" />
                    @error('subtitle') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </label>

                <label class="sm:col-span-2">
                    <span class="admin-form-label">Descripción corta</span>
                    <textarea id="product-short-description" name="short_description" rows="3"
                        placeholder="Describe brevemente la pieza y su carácter."
                        class="admin-form-input aurea-focus">{{ old('short_description', $product->short_description) }}</textarea>
                    @error('short_description') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </label>

                <label>
                    <span class="admin-form-label admin-form-label-required">Categoría</span>
                    <select id="product-category" name="category_id" class="admin-form-input" required>
                        <option value="">Selecciona una categoría</option>
                        @foreach ($categories as $category)
                            <option value="{{ $category->id }}" @selected((string) $selectedCategoryId === (string) $category->id)>
                                {{ $category->name }}
                            </option>
                        @endforeach
                    </select>
                    @error('category_id') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </label>

                <div id="product-subcategory-field" class="{{ $selectedCategoryId && $categories->firstWhere('id', (int) $selectedCategoryId)?->children->isNotEmpty() ? '' : 'hidden' }}">
                    <label>
                        <span class="admin-form-label">Subcategoría</span>
                        <select id="product-subcategory" name="subcategory_id" class="admin-form-input">
                            <option value="">Sin subcategoría</option>
                            @foreach ($categories as $category)
                                @foreach ($category->children as $subcategory)
                                    <option value="{{ $subcategory->id }}" data-parent-id="{{ $category->id }}"
                                        @selected((string) $selectedSubcategoryId === (string) $subcategory->id)>
                                        {{ $subcategory->name }}
                                    </option>
                                @endforeach
                            @endforeach
                        </select>
                        @error('subcategory_id') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                    </label>
                </div>

                <label>
                    <span class="admin-form-label">Colección</span>
                    <select name="collection_id" class="admin-form-input">
                        <option value="">Selecciona una colección</option>
                        @foreach ($collections as $collection)
                            <option value="{{ $collection->id }}" @selected((string) $selectedCollectionId === (string) $collection->id)>
                                {{ $collection->name }}
                            </option>
                        @endforeach
                    </select>
                    @error('collection_id') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </label>

                <label>
                    <span class="admin-form-label admin-form-label-required">Código</span>
                    <input id="product-code" type="text" name="code" value="{{ old('code', $product->code) }}"
                        data-code-generated="{{ $codeWasGenerated === '1' ? 'true' : 'false' }}"
                        placeholder="Ej. ANA-001" class="admin-form-input" required />
                    <span class="admin-form-help">Sugerencia automática editable. Referencia interna del producto.</span>
                    @error('code') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </label>

                <label>
                    <span class="admin-form-label">Slug</span>
                    <input id="product-slug-preview" type="text"
                        value="{{ old('name', $product->name) ? \Illuminate\Support\Str::slug(old('name', $product->name)) : $product->slug }}"
                        placeholder="anillo-aura" class="admin-form-input bg-[#f8f9f6] text-[#929b94]" readonly
                        aria-live="polite" />
                    <span class="admin-form-help">Se genera automáticamente a partir del nombre.</span>
                </label>
            </div>
        </x-admin.form-section>

        <x-admin.form-section
            number="02"
            title="Precio"
            description="Configura el valor de venta y el costo interno de la pieza."
            id="price-title"
        >
            <div class="grid gap-5 sm:grid-cols-3">
                <label>
                    <span class="admin-form-label admin-form-label-required">Precio base</span>
                    <div class="admin-form-input-prefix">
                        <span>$</span>
                        <input type="number" name="price" value="{{ old('price', $product->price) }}" min="0"
                            step="0.01" inputmode="decimal" aria-label="Precio base" required />
                    </div>
                    @error('price') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </label>
                <label>
                    <span class="admin-form-label">Precio de oferta</span>
                    <div class="admin-form-input-prefix">
                        <span>$</span>
                        <input type="number" name="sale_price" value="{{ old('sale_price', $product->sale_price) }}"
                            min="0" step="0.01" inputmode="decimal" placeholder="Opcional" aria-label="Precio de oferta" />
                    </div>
                    @error('sale_price') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </label>
                <label>
                    <span class="admin-form-label">Costo</span>
                    <div class="admin-form-input-prefix">
                        <span>$</span>
                        <input type="number" name="cost" value="{{ old('cost', $product->cost) }}" min="0"
                            step="0.01" inputmode="decimal" aria-label="Costo" />
                    </div>
                    @error('cost') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </label>
            </div>
        </x-admin.form-section>

        <x-admin.form-section
            number="03"
            title="Especificaciones de joyería"
            description="Completa los detalles técnicos y comerciales que describen la pieza."
            id="jewelry-title"
        >
            <div class="grid gap-5 sm:grid-cols-2 lg:grid-cols-3">
                <label>
                    <span class="admin-form-label">Material</span>
                    <select name="material_id" class="admin-form-input">
                        <option value="">Sin material</option>
                        @foreach ($materials as $material)
                            <option value="{{ $material->id }}" @selected((string) $selectedMaterialId === (string) $material->id)>
                                {{ $material->name }}
                            </option>
                        @endforeach
                    </select>
                    @error('material_id') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </label>

                <label>
                    <span class="admin-form-label">Kilataje del material</span>
                    <select name="karat" class="admin-form-input">
                        <option value="">Sin especificar</option>
                        @foreach ($karatOptions as $value => $label)
                            <option value="{{ $value }}" @selected((string) old('karat', $product->karat) === (string) $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                    @error('karat') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </label>

                <label>
                    <span class="admin-form-label">Color del oro</span>
                    <select name="metal_color" class="admin-form-input">
                        <option value="">Sin especificar</option>
                        @foreach ($metalColorOptions as $metalColor)
                            <option value="{{ $metalColor }}" @selected(old('metal_color', $product->metal_color) === $metalColor)>{{ $metalColor }}</option>
                        @endforeach
                    </select>
                    @error('metal_color') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </label>

                <label>
                    <span class="admin-form-label">Talla base o referencia</span>
                    <select name="base_size_id" class="admin-form-input">
                        <option value="">Sin talla base</option>
                        @foreach ($applicableSizes as $size)
                            <option value="{{ $size['id'] }}" @selected((string) $selectedBaseSizeId === (string) $size['id'])>
                                {{ $size['name'] }}
                            </option>
                        @endforeach
                    </select>
                    <span class="admin-form-help">Las tallas corresponden a la categoría seleccionada.</span>
                    @error('base_size_id') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </label>
            </div>
        </x-admin.form-section>

        <x-admin.form-section
            number="04"
            title="Información de piedras"
            description="Describe la piedra central y las piedras laterales que forman parte de la pieza."
            id="stones-title"
        >
            <div class="space-y-7">
                <div>
                    <h4 class="text-sm font-medium text-[#3d4640] dark:text-[#eeeae2]">Piedra central</h4>
                    <p class="mt-1 text-xs text-[#8a938d]">Información principal de la piedra que acompaña al producto.</p>
                    <div class="mt-5 grid gap-5 sm:grid-cols-2 lg:grid-cols-3">
                        <label>
                            <span class="admin-form-label">Tipo de piedra</span>
                            <select name="central_stone[stone_id]" class="admin-form-input">
                                <option value="">Sin piedra</option>
                                @foreach ($stones as $stone)
                                    <option value="{{ $stone->id }}" @selected((string) ($centralStone['stone_id'] ?? '') === (string) $stone->id)>
                                        {{ $stone->name }}
                                    </option>
                                @endforeach
                            </select>
                            @error('central_stone.stone_id') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                        </label>
                        <label>
                            <span class="admin-form-label">Forma de la piedra</span>
                            <input type="text" name="central_stone[shape]" value="{{ $centralStone['shape'] ?? '' }}"
                                placeholder="Ej. Redondo" class="admin-form-input" />
                            @error('central_stone.shape') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                        </label>
                        <label>
                            <span class="admin-form-label">Quilates</span>
                            <input type="number" name="central_stone[carats]" value="{{ $centralStone['carats'] ?? '' }}"
                                min="0" step="0.01" inputmode="decimal" placeholder="Ej. 0.50" class="admin-form-input" />
                            @error('central_stone.carats') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                        </label>
                        <label>
                            <span class="admin-form-label">Origen</span>
                            <input type="text" name="central_stone[origin]" value="{{ $centralStone['origin'] ?? '' }}"
                                placeholder="Ej. Laboratorio" class="admin-form-input" />
                            @error('central_stone.origin') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                        </label>
                        <label>
                            <span class="admin-form-label">Certificación</span>
                            <input type="text" name="central_stone[certification]" value="{{ $centralStone['certification'] ?? '' }}"
                                placeholder="Ej. IGI" class="admin-form-input" />
                            @error('central_stone.certification') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                        </label>
                        <label>
                            <span class="admin-form-label">Color</span>
                            <input type="text" name="central_stone[color]" value="{{ $centralStone['color'] ?? '' }}"
                                placeholder="Ej. D" class="admin-form-input" />
                            @error('central_stone.color') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                        </label>
                        <label>
                            <span class="admin-form-label">Claridad</span>
                            <input type="text" name="central_stone[clarity]" value="{{ $centralStone['clarity'] ?? '' }}"
                                placeholder="Ej. VS1" class="admin-form-input" />
                            @error('central_stone.clarity') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                        </label>
                        <label>
                            <span class="admin-form-label">Corte</span>
                            <input type="text" name="central_stone[cut]" value="{{ $centralStone['cut'] ?? '' }}"
                                placeholder="Ej. Excellent" class="admin-form-input" />
                            @error('central_stone.cut') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                        </label>
                    </div>
                </div>
                <div class="border-t border-[#eef0ec] pt-6">
                    <x-admin.toggle id="product-has-side-stones" name="has_side_stones"
                        label="Este producto tiene piedras laterales"
                        description="Activa esta opción para registrar las piedras que acompañan a la piedra central."
                        :checked="$hasSideStones" />
                    <div id="side-stones-fields" class="{{ $hasSideStones ? '' : 'hidden' }} mt-5"
                        @if (! $hasSideStones) aria-hidden="true" @endif>
                        <div class="grid gap-5 sm:grid-cols-2 lg:grid-cols-3">
                            <label>
                                <span class="admin-form-label">Tipo de piedra</span>
                                <select name="side_stone[stone_id]" class="admin-form-input">
                                    <option value="">Selecciona una piedra</option>
                                    @foreach ($stones as $stone)
                                        <option value="{{ $stone->id }}" @selected((string) ($sideStone['stone_id'] ?? '') === (string) $stone->id)>
                                            {{ $stone->name }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('side_stone.stone_id') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                            </label>
                            <label>
                                <span class="admin-form-label">Forma de la piedra</span>
                                <input type="text" name="side_stone[shape]" value="{{ $sideStone['shape'] ?? '' }}"
                                    placeholder="Ej. Redondo" class="admin-form-input" />
                                @error('side_stone.shape') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                            </label>
                            <label>
                                <span class="admin-form-label">Quilates por piedra</span>
                                <input type="number" name="side_stone[carats]" value="{{ $sideStone['carats'] ?? '' }}"
                                    min="0" step="0.01" inputmode="decimal" placeholder="Ej. 0.10" class="admin-form-input" />
                                @error('side_stone.carats') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                            </label>
                            <label>
                                <span class="admin-form-label">Cantidad</span>
                                <input type="number" name="side_stone[quantity]" value="{{ $sideStone['quantity'] ?? '' }}"
                                    min="1" step="1" placeholder="Ej. 2" class="admin-form-input" />
                                @error('side_stone.quantity') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                            </label>
                            <label>
                                <span class="admin-form-label">Origen</span>
                                <input type="text" name="side_stone[origin]" value="{{ $sideStone['origin'] ?? '' }}"
                                    placeholder="Ej. Laboratorio" class="admin-form-input" />
                                @error('side_stone.origin') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                            </label>
                            <label>
                                <span class="admin-form-label">Certificación</span>
                                <input type="text" name="side_stone[certification]" value="{{ $sideStone['certification'] ?? '' }}"
                                    placeholder="Ej. IGI" class="admin-form-input" />
                                @error('side_stone.certification') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                            </label>
                            <label>
                                <span class="admin-form-label">Color</span>
                                <input type="text" name="side_stone[color]" value="{{ $sideStone['color'] ?? '' }}"
                                    placeholder="Ej. G" class="admin-form-input" />
                                @error('side_stone.color') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                            </label>
                            <label>
                                <span class="admin-form-label">Claridad</span>
                                <input type="text" name="side_stone[clarity]" value="{{ $sideStone['clarity'] ?? '' }}"
                                    placeholder="Ej. VS1" class="admin-form-input" />
                                @error('side_stone.clarity') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                            </label>
                            <label>
                                <span class="admin-form-label">Corte</span>
                                <input type="text" name="side_stone[cut]" value="{{ $sideStone['cut'] ?? '' }}"
                                    placeholder="Ej. Excellent" class="admin-form-input" />
                                @error('side_stone.cut') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                            </label>
                        </div>
                    </div>
                </div>
            </div>
        </x-admin.form-section>

        <x-admin.form-section
            number="05"
            title="Multimedia"
            description="Añade imágenes y define la portada de la pieza."
            id="media-title"
        >
            <div id="product-media-manager" class="space-y-6">
                <label id="product-image-dropzone" for="product-image-upload"
                    class="flex min-h-52 cursor-pointer flex-col items-center justify-center rounded-xl border border-dashed border-[#d8d9d2] bg-[#fbfbf9] px-5 text-center transition hover:border-aurea-gold hover:bg-[#fbf8f3] focus-within:border-aurea-gold focus-within:ring-2 focus-within:ring-aurea-gold-soft/20 dark:bg-[#292b29]">
                    <svg class="h-8 w-8 text-[#b58d55]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.4" aria-hidden="true">
                        <path d="M4 5h16v14H4zM7 15l3-3 2 2 2-2 3 3M8 9h.01M12 4v7m-3-3 3-3 3 3" />
                    </svg>
                    <span id="product-image-dropzone-title" class="mt-3 text-sm font-medium text-[#59635c] dark:text-[#d0d4cf]">Arrastra tus imágenes aquí</span>
                    <span class="mt-1 text-xs text-[#8a938d]">o haz clic para seleccionarlas</span>
                    <span class="mt-3 text-[11px] text-[#9da59f]">PNG, JPG o WEBP · Máximo 2 MB por imagen</span>
                    <input id="product-image-upload" name="images[]" type="file"
                        accept="image/png,image/jpeg,image/webp" multiple class="sr-only" />
                </label>

                <div id="product-gallery-wrapper" class="{{ $productImages->isEmpty() ? 'hidden' : '' }} space-y-3">
                    <div class="flex flex-wrap items-end justify-between gap-2">
                        <div>
                            <p class="admin-form-label">Imágenes del producto</p>
                            <p class="mt-1 text-xs text-[#8a938d]">Define el texto alternativo y la imagen de portada.</p>
                        </div>
                        <span id="product-gallery-count" class="text-xs text-[#929b94]">{{ $productImages->count() }} imágenes</span>
                    </div>
                    <div id="product-gallery-list" class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3" aria-live="polite">
                        @foreach ($productImages as $image)
                            <article data-existing-image data-media-token="{{ $image->id }}" data-image-id="{{ $image->id }}" draggable="true" tabindex="0"
                                class="product-media-card group overflow-hidden rounded-xl border border-[#e1e3dd] bg-white shadow-[0_4px_14px_rgba(35,42,37,0.04)] dark:border-[#4b4a45] dark:bg-[#292b29]">
                                <div class="relative cursor-grab active:cursor-grabbing">
                                    @if ($image->hasPublicFile())
                                        <img src="{{ $image->publicUrl() }}"
                                            alt="{{ $image->alt ?: $product->name }}" class="block h-36 w-full object-cover" />
                                    @else
                                        <div class="flex h-36 w-full items-center justify-center bg-[#f7f3ec] text-xs text-[#929b94] dark:bg-[#292b29] dark:text-[#777d77]">
                                            Sin imagen
                                        </div>
                                    @endif
                                    <span data-media-position class="absolute left-2 top-2 rounded-md bg-white/90 px-2 py-1 text-[10px] font-semibold text-[#68736c] shadow-sm">Posición {{ $loop->iteration }}</span>
                                    <span class="absolute left-2 top-9 rounded-md bg-white/90 px-2 py-1 text-[10px] font-semibold text-[#68736c] shadow-sm">Imagen guardada</span>
                                    <span data-cover-badge class="absolute right-2 top-9 rounded-md bg-[#eadfce] px-2 py-1 text-[10px] font-semibold uppercase tracking-[.1em] text-[#806441] {{ $image->is_cover ? '' : 'hidden' }}">Portada</span>
                                    <span class="absolute right-2 top-2 rounded-md bg-[#28302b]/75 px-2 py-1 text-[10px] font-medium text-white opacity-0 transition group-hover:opacity-100 group-focus-within:opacity-100">Arrastrar</span>
                                </div>
                                <div class="space-y-3 p-3">
                                    <label>
                                        <span class="admin-form-label">Texto alternativo</span>
                                        <input type="text" name="image_alt[{{ $image->id }}]" value="{{ old('image_alt.'.$image->id, $image->alt) }}"
                                            placeholder="{{ $product->name }}" class="admin-form-input" />
                                    </label>
                                    <div class="flex items-center justify-between gap-2">
                                        <label class="flex items-center gap-2 text-xs text-[#68736c] dark:text-[#d0d4cf]">
                                            <input type="radio" name="cover_image_id" value="{{ $image->id }}"
                                                @checked($image->is_cover) class="text-aurea-gold focus:ring-aurea-gold" />
                                            Portada
                                        </label>
                                        <div class="ml-auto flex items-center gap-1">
                                            <button type="button" data-move-image="up" title="Mover imagen hacia arriba"
                                                class="aurea-focus rounded-md p-2 text-[#8a938d] hover:bg-[#f1f2ee] hover:text-aurea-focus" aria-label="Mover imagen hacia arriba">↑</button>
                                            <button type="button" data-move-image="down" title="Mover imagen hacia abajo"
                                                class="aurea-focus rounded-md p-2 text-[#8a938d] hover:bg-[#f1f2ee] hover:text-aurea-focus" aria-label="Mover imagen hacia abajo">↓</button>
                                            <button type="button" data-remove-existing-image
                                                class="aurea-focus rounded-md px-2.5 py-2 text-[11px] font-medium text-[#6f675d] hover:bg-[#f9e9e6] hover:text-aurea-danger">Eliminar</button>
                                        </div>
                                    </div>
                                </div>
                            </article>
                        @endforeach
                    </div>
                </div>
                <div id="removed-images-container"></div>
            </div>
        </x-admin.form-section>
    </div>

    <aside class="space-y-7 lg:sticky lg:top-7">
        <x-admin.form-section
            number="06"
            title="Visibilidad"
            description="Define cómo se mostrará el producto en la tienda."
            id="visibility-title"
        >
            <div class="space-y-4">
                <label>
                    <span class="admin-form-label admin-form-label-required">Estado</span>
                    <select name="status" class="admin-form-input" required>
                        @foreach ($productStatuses as $value => $label)
                            <option value="{{ $value }}" @selected(old('status', $product->status ?? 'draft') === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                    @error('status') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </label>
            </div>
        </x-admin.form-section>

        <section class="rounded-2xl border border-aurea-border bg-aurea-surface p-5 shadow-[0_5px_20px_rgba(35,42,37,0.03)] dark:bg-[#2d302e]" aria-labelledby="preview-title">
            <div class="flex items-center justify-between gap-3">
                <div>
                    <h3 id="preview-title" class="font-serif text-xl text-[#28302b] dark:text-[#eeeae2]">Resumen</h3>
                    <p class="mt-1 text-xs text-[#8a938d]">{{ $isEditing ? 'Producto existente' : 'Antes de publicar' }}</p>
                </div>
            </div>
            <ul class="mt-5 space-y-3 text-xs text-[#68736c] dark:text-[#c4cbc5]">
                <li data-summary-item="general" class="flex items-center gap-2">
                    <span data-summary-icon class="flex h-4 w-4 items-center justify-center rounded-full bg-[#edf5ee] text-[#5b8668]">✓</span>
                    <span data-summary-label>Información general</span>
                </li>
                <li data-summary-item="details" class="flex items-center gap-2">
                    <span data-summary-icon class="flex h-4 w-4 items-center justify-center rounded-full bg-[#edf5ee] text-[#5b8668]">✓</span>
                    <span data-summary-label>Precio y detalles</span>
                </li>
                <li data-summary-item="stones" class="flex items-center gap-2 text-[#a0a7a2]">
                    <span data-summary-icon class="h-4 w-4 rounded-full border border-[#dfe2dc]"></span>
                    <span data-summary-label>Piedras opcionales</span>
                </li>
                <li data-summary-item="images" class="flex items-center gap-2 text-[#a0a7a2]">
                    <span data-summary-icon class="h-4 w-4 rounded-full border border-[#dfe2dc]"></span>
                    <span data-summary-label>Imágenes pendientes</span>
                </li>
                <li data-summary-item="visibility" class="flex items-center gap-2">
                    <span data-summary-icon class="flex h-4 w-4 items-center justify-center rounded-full bg-[#edf5ee] text-[#5b8668]">✓</span>
                    <span data-summary-label>Visibilidad</span>
                </li>
            </ul>
        </section>

        <button type="submit" form="product-form"
            class="aurea-focus inline-flex w-full items-center justify-center rounded-lg bg-aurea-gold px-4 py-2.5 text-sm font-medium text-white shadow-sm hover:bg-[#9f793f]">
            {{ $isEditing ? 'Guardar cambios' : 'Guardar producto' }}
        </button>
    </aside>
</form>

@pushOnce('scripts')
    <script>
        const productNameInput = document.getElementById('product-name');
        const productSlugPreview = document.getElementById('product-slug-preview');
        const productCodeInput = document.getElementById('product-code');
        const productCodeGeneratedInput = document.getElementById('product-code-generated');
        const productCodeSuggestionUrl = @json(route('products.code-suggestion'));
        const productIsEditing = @json($isEditing);
        const productCategorySelect = document.getElementById('product-category');
        const productSubcategoryField = document.getElementById('product-subcategory-field');
        const productSubcategorySelect = document.getElementById('product-subcategory');
        const productBaseSizeSelect = document.querySelector('[name="base_size_id"]');
        const categorySizes = @json($categorySizes);
        const sideStonesToggle = document.getElementById('product-has-side-stones');
        const sideStonesFields = document.getElementById('side-stones-fields');
        const productImageUpload = document.getElementById('product-image-upload');
        const productImageDropzone = document.getElementById('product-image-dropzone');
        const productImageDropzoneTitle = document.getElementById('product-image-dropzone-title');
        const productGalleryWrapper = document.getElementById('product-gallery-wrapper');
        const productGalleryList = document.getElementById('product-gallery-list');
        const productGalleryCount = document.getElementById('product-gallery-count');
        const removedImagesContainer = document.getElementById('removed-images-container');
        let newImages = [];

        const updateApplicableSizes = () => {
            if (!productBaseSizeSelect) {
                return;
            }

            const effectiveCategoryId = productSubcategorySelect?.value || productCategorySelect?.value;
            const sizes = categorySizes[effectiveCategoryId] ?? [];
            const currentSizeId = productBaseSizeSelect.value;

            productBaseSizeSelect.replaceChildren(new Option('Sin talla base', ''));
            sizes.forEach((size) => {
                const option = new Option(size.name, size.id);
                productBaseSizeSelect.add(option);
            });

            productBaseSizeSelect.value = sizes.some((size) => String(size.id) === currentSizeId)
                ? currentSizeId
                : '';
        };

        const updateSubcategories = (clearSelection = false) => {
            if (!productCategorySelect || !productSubcategorySelect || !productSubcategoryField) {
                return;
            }

            const selectedCategoryId = productCategorySelect.value;
            const options = Array.from(productSubcategorySelect.options).slice(1);
            const matchingOptions = options.filter((option) => option.dataset.parentId === selectedCategoryId);

            options.forEach((option) => {
                const matches = option.dataset.parentId === selectedCategoryId;
                option.hidden = ! matches;
                option.disabled = ! matches;
            });

            if (clearSelection || ! matchingOptions.some((option) => option.value === productSubcategorySelect.value)) {
                productSubcategorySelect.value = '';
            }

            const hasSubcategories = matchingOptions.length > 0;
            productSubcategoryField.classList.toggle('hidden', ! hasSubcategories);
            productSubcategoryField.toggleAttribute('aria-hidden', ! hasSubcategories);
            productSubcategorySelect.disabled = ! hasSubcategories;
            updateApplicableSizes();
        };

        const updateSideStonesFields = () => {
            if (!sideStonesToggle || !sideStonesFields) {
                return;
            }

            const isEnabled = sideStonesToggle.checked;

            sideStonesFields.classList.toggle('hidden', !isEnabled);
            sideStonesFields.toggleAttribute('aria-hidden', !isEnabled);
            sideStonesFields.querySelectorAll('input, select').forEach((field) => {
                field.disabled = !isEnabled;
            });
        };

        const slugify = (value) => value
            .normalize('NFD')
            .replace(/[\u0300-\u036f]/g, '')
            .toLowerCase()
            .replace(/[^a-z0-9]+/g, '-')
            .replace(/^-+|-+$/g, '');
        let codeManuallyEdited = productCodeInput?.dataset.codeGenerated === 'false';

        const compactCodePart = (value) => value
            .normalize('NFD')
            .replace(/[\u0300-\u036f]/g, '')
            .toUpperCase()
            .replace(/[^A-Z0-9]/g, '')
            .slice(0, 3);

        const suggestProductCode = () => {
            const categoryOption = productCategorySelect?.value
                ? productCategorySelect.selectedOptions[0]
                : null;
            const categoryPart = compactCodePart(categoryOption?.textContent ?? '') || 'PRO';
            const nameWords = productNameInput?.value.trim().split(/\s+/) ?? [];

            if (nameWords[0] && compactCodePart(nameWords[0]) === categoryPart) {
                nameWords.shift();
            }

            const namePart = compactCodePart(nameWords[0] ?? 'PRO') || 'PRO';

            return `${categoryPart}-${namePart}-001`;
        };

        let codeSuggestionRequestId = 0;

        const updateProductCodeSuggestion = async () => {
            if (codeManuallyEdited || ! productCodeInput) {
                return;
            }

            const name = productNameInput?.value.trim() ?? '';
            const categoryId = productCategorySelect?.value ?? '';

            if (name === '' || categoryId === '') {
                productCodeInput.value = '';
                productCodeGeneratedInput.value = '1';
                return;
            }

            const requestId = ++codeSuggestionRequestId;
            const url = new URL(productCodeSuggestionUrl, window.location.origin);
            url.searchParams.set('name', name);
            url.searchParams.set('category_id', categoryId);

            try {
                const response = await fetch(url, {
                    headers: { Accept: 'application/json' },
                });
                const data = await response.json();

                if (requestId === codeSuggestionRequestId && ! codeManuallyEdited) {
                    productCodeInput.value = data.code ?? suggestProductCode();
                    productCodeGeneratedInput.value = '1';
                }
            } catch (error) {
                if (requestId === codeSuggestionRequestId && ! codeManuallyEdited) {
                    productCodeInput.value = suggestProductCode();
                    productCodeGeneratedInput.value = '1';
                }
            }
        };

        productCodeInput?.addEventListener('input', () => {
            codeManuallyEdited = true;
            productCodeInput.dataset.codeGenerated = 'false';
            productCodeGeneratedInput.value = '0';
        });

        productNameInput?.addEventListener('input', () => {
            if (productSlugPreview) {
                productSlugPreview.value = slugify(productNameInput.value);
            }

            updateAutomaticImageAlts();
            updateProductCodeSuggestion();
        });

        sideStonesToggle?.addEventListener('change', updateSideStonesFields);
        updateSideStonesFields();

        productCategorySelect?.addEventListener('change', () => {
            updateSubcategories(true);
            updateProductCodeSuggestion();
        });
        productSubcategorySelect?.addEventListener('change', () => {
            updateApplicableSizes();
        });
        updateSubcategories();
        if (! productIsEditing) {
            updateProductCodeSuggestion();
        }

        const imageReorderUrl = @json($isEditing ? route('products.images.reorder', $product) : null);
        const updateGalleryState = () => {
            if (!productGalleryList || !productGalleryWrapper) {
                return;
            }

            const savedImages = productGalleryList.querySelectorAll('[data-existing-image]:not(.hidden)').length;
            const imageCount = savedImages + newImages.length;

            productGalleryWrapper.classList.toggle('hidden', imageCount === 0);
            if (productGalleryCount) {
                productGalleryCount.textContent = `${imageCount} ${imageCount === 1 ? 'imagen' : 'imágenes'}`;
            }
        };

        const mediaCards = () => Array.from(productGalleryList?.children ?? [])
            .filter((card) => ! card.classList.contains('hidden'));

        const updateMediaOrderField = () => {
            const order = mediaCards().map((card) => card.dataset.mediaToken);
            let field = document.getElementById('product-media-order');

            if (! field) {
                field = document.createElement('input');
                field.type = 'hidden';
                field.id = 'product-media-order';
                field.name = 'image_order';
                document.getElementById('product-form')?.append(field);
            }

            field.value = order.join(',');
        };

        const persistExistingOrder = async () => {
            if (! imageReorderUrl) {
                return;
            }

            const imageIds = mediaCards()
                .filter((card) => card.hasAttribute('data-existing-image'))
                .map((card) => Number(card.dataset.imageId));

            if (imageIds.length === 0) {
                return;
            }

            try {
                const response = await fetch(imageReorderUrl, {
                    method: 'PATCH',
                    headers: {
                        'Accept': 'application/json',
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content ?? document.querySelector('[name="_token"]')?.value,
                    },
                    body: JSON.stringify({ images: imageIds }),
                });

                if (! response.ok) {
                    throw new Error('No se pudo guardar el orden de las imágenes.');
                }
            } catch (error) {
                window.alert(error.message);
                window.location.reload();
            }
        };

        const updateMediaPositions = (save = false) => {
            const cards = mediaCards();

            cards.forEach((card, index) => {
                const position = card.querySelector('[data-media-position]');
                const moveUpButton = card.querySelector('[data-move-image="up"]');
                const moveDownButton = card.querySelector('[data-move-image="down"]');

                if (position) {
                    position.textContent = `Posición ${index + 1}`;
                }

                moveUpButton?.toggleAttribute('disabled', index === 0);
                moveDownButton?.toggleAttribute('disabled', index === cards.length - 1);
                moveUpButton?.classList.toggle('opacity-40', index === 0);
                moveDownButton?.classList.toggle('opacity-40', index === cards.length - 1);
            });

            updateMediaOrderField();
            updateGalleryState();

            if (save) {
                persistExistingOrder();
            }
        };

        const moveMediaCard = (card, direction) => {
            const cards = mediaCards();
            const currentIndex = cards.indexOf(card);
            const nextIndex = currentIndex + direction;

            if (currentIndex < 0 || nextIndex < 0 || nextIndex >= cards.length) {
                return;
            }

            if (direction < 0) {
                productGalleryList.insertBefore(card, cards[nextIndex]);
            } else {
                productGalleryList.insertBefore(card, cards[nextIndex].nextSibling);
            }

            updateMediaPositions(true);
            card.focus();
        };

        let draggedMediaCard = null;
        const bindMediaCard = (card) => {
            card.addEventListener('dragstart', () => {
                draggedMediaCard = card;
                card.classList.add('opacity-50');
            });
            card.addEventListener('dragend', () => {
                draggedMediaCard = null;
                card.classList.remove('opacity-50');
                updateMediaPositions(true);
            });
            card.addEventListener('dragover', (event) => event.preventDefault());
            card.addEventListener('drop', (event) => {
                event.preventDefault();

                if (! draggedMediaCard || draggedMediaCard === card) {
                    return;
                }

                const cards = mediaCards();
                const draggedIndex = cards.indexOf(draggedMediaCard);
                const targetIndex = cards.indexOf(card);

                productGalleryList.insertBefore(
                    draggedMediaCard,
                    draggedIndex < targetIndex ? card.nextSibling : card,
                );
                updateMediaPositions(true);
            });
            card.querySelector('[data-move-image="up"]')?.addEventListener('click', () => moveMediaCard(card, -1));
            card.querySelector('[data-move-image="down"]')?.addEventListener('click', () => moveMediaCard(card, 1));
        };

        const updateCoverIndicators = () => {
            mediaCards().forEach((card) => {
                const isCover = Boolean(card.querySelector('input[name="cover_image_id"]:checked, input[name="cover_new_index"]:checked'));
                card.querySelector('[data-cover-badge]')?.classList.toggle('hidden', ! isCover);
            });
        };

        const ensureCoverSelection = () => {
            const cards = mediaCards();
            const hasCover = cards.some((card) => card.querySelector('input[name="cover_image_id"]:checked, input[name="cover_new_index"]:checked'));

            if (! hasCover && cards[0]) {
                cards[0].querySelector('input[name="cover_image_id"], input[name="cover_new_index"]')?.click();
            }

            updateCoverIndicators();
        };

        const updateAutomaticImageAlts = () => {
            const productName = productNameInput?.value.trim() ?? '';

            newImages.forEach((image, index) => {
                if (image.altCustomized) {
                    return;
                }

                image.alt = productName;
                const altInput = productGalleryList?.querySelector(`[data-new-image-index="${index}"] input[name="new_image_alts[${index}]"]`);

                if (altInput) {
                    altInput.value = productName;
                }
            });
        };

        const syncUploadedFiles = () => {
            if (!productImageUpload || typeof DataTransfer === 'undefined') {
                return;
            }

            const transfer = new DataTransfer();
            newImages.forEach((image) => transfer.items.add(image.file));
            productImageUpload.files = transfer.files;
        };

        const renderNewImages = () => {
            productGalleryList?.querySelectorAll('[data-new-image]').forEach((card) => card.remove());

            newImages.forEach((image, index) => {
                const card = document.createElement('article');
                card.dataset.newImage = 'true';
                card.dataset.newImageIndex = index;
                card.dataset.mediaToken = `new-${index}`;
                card.draggable = true;
                card.tabIndex = 0;
                card.className = 'group overflow-hidden rounded-xl border border-[#e1e3dd] bg-white shadow-[0_4px_14px_rgba(35,42,37,0.04)] dark:border-[#4b4a45] dark:bg-[#292b29]';
                const imageFrame = document.createElement('div');
                imageFrame.className = 'relative cursor-grab active:cursor-grabbing';

                const preview = document.createElement('img');
                preview.src = image.preview;
                preview.alt = image.file.name;
                preview.className = 'h-36 w-full object-cover';
                imageFrame.append(preview);

                const newBadge = document.createElement('span');
                newBadge.className = 'absolute left-2 top-2 rounded-md bg-white/90 px-2 py-1 text-[10px] font-semibold text-[#68736c] shadow-sm';
                newBadge.textContent = 'Nueva';
                imageFrame.append(newBadge);

                const positionBadge = document.createElement('span');
                positionBadge.dataset.mediaPosition = '';
                positionBadge.className = 'absolute left-2 top-9 rounded-md bg-white/90 px-2 py-1 text-[10px] font-semibold text-[#68736c] shadow-sm';
                imageFrame.append(positionBadge);

                const coverBadge = document.createElement('span');
                coverBadge.dataset.coverBadge = '';
                coverBadge.className = 'absolute right-2 top-9 hidden rounded-md bg-[#eadfce] px-2 py-1 text-[10px] font-semibold uppercase tracking-[.1em] text-[#806441]';
                coverBadge.textContent = 'Portada';
                imageFrame.append(coverBadge);

                const dragBadge = document.createElement('span');
                dragBadge.className = 'absolute right-2 top-2 rounded-md bg-[#28302b]/75 px-2 py-1 text-[10px] font-medium text-white';
                dragBadge.textContent = 'Arrastrar';
                imageFrame.append(dragBadge);
                card.append(imageFrame);

                const details = document.createElement('div');
                details.className = 'space-y-3 p-3';

                const altLabel = document.createElement('label');
                const altLabelText = document.createElement('span');
                altLabelText.className = 'admin-form-label';
                altLabelText.textContent = 'Texto alternativo';
                altLabel.append(altLabelText);

                const altInput = document.createElement('input');
                altInput.type = 'text';
                altInput.name = `new_image_alts[${index}]`;
                altInput.value = image.alt;
                altInput.placeholder = 'Describe la imagen';
                altInput.className = 'admin-form-input';
                altLabel.append(altInput);
                details.append(altLabel);

                const controls = document.createElement('div');
                controls.className = 'flex items-center justify-between gap-2';
                const coverLabel = document.createElement('label');
                coverLabel.className = 'flex items-center gap-2 text-xs text-[#68736c] dark:text-[#d0d4cf]';

                const coverInput = document.createElement('input');
                coverInput.type = 'radio';
                coverInput.name = 'cover_new_index';
                coverInput.value = index;
                coverInput.className = 'text-aurea-gold focus:ring-aurea-gold';
                coverLabel.append(coverInput);
                const coverLabelText = document.createTextNode('Portada');
                coverLabel.append(coverLabelText);
                controls.append(coverLabel);

                const actionButtons = document.createElement('div');
                actionButtons.className = 'ml-auto flex items-center gap-1';
                const createActionButton = (text, attributes, className) => {
                    const button = document.createElement('button');
                    button.type = 'button';
                    button.className = className;
                    button.textContent = text;
                    Object.entries(attributes).forEach(([attribute, value]) => button.setAttribute(attribute, value));
                    return button;
                };
                actionButtons.append(createActionButton('↑', {
                    'data-move-image': 'up',
                    title: 'Mover imagen hacia arriba',
                    'aria-label': 'Mover imagen hacia arriba',
                }, 'aurea-focus rounded-md p-2 text-[#8a938d] hover:bg-[#f1f2ee] hover:text-aurea-focus'));
                actionButtons.append(createActionButton('↓', {
                    'data-move-image': 'down',
                    title: 'Mover imagen hacia abajo',
                    'aria-label': 'Mover imagen hacia abajo',
                }, 'aurea-focus rounded-md p-2 text-[#8a938d] hover:bg-[#f1f2ee] hover:text-aurea-focus'));
                actionButtons.append(createActionButton('Eliminar', {
                    'data-remove-new-image': index,
                }, 'aurea-focus rounded-md px-2.5 py-2 text-[11px] font-medium text-[#6f675d] hover:bg-[#f9e9e6] hover:text-aurea-danger'));
                controls.append(actionButtons);
                details.append(controls);
                card.append(details);
                productGalleryList?.append(card);
                bindMediaCard(card);
                altInput.addEventListener('input', (event) => {
                    image.alt = event.target.value;
                    image.altCustomized = true;
                });
            });

            productGalleryList?.querySelectorAll('[data-remove-new-image]').forEach((button) => {
                button.addEventListener('click', () => {
                    newImages.splice(Number(button.dataset.removeNewImage), 1);
                    syncUploadedFiles();
                    renderNewImages();
                    updateMediaPositions();
                    updateSummary();
                });
            });
        };

        const addMediaFiles = (files) => {
            const validMimeTypes = ['image/jpeg', 'image/png', 'image/webp'];
            const validExtensions = /\.(jpe?g|png|webp)$/i;
            const maxFileSize = 2 * 1024 * 1024;

            Array.from(files)
                .filter((file) => {
                    const hasValidType = validMimeTypes.includes(file.type) || validExtensions.test(file.name);

                    return hasValidType && file.size <= maxFileSize;
                })
                .filter((file) => {
                    const fileKey = `${file.name}-${file.size}-${file.lastModified}`;

                    return ! newImages.some((image) => image.key === fileKey);
                })
                .forEach((file) => {
                newImages.push({
                    file,
                    preview: URL.createObjectURL(file),
                    alt: productNameInput?.value.trim() ?? '',
                    key: `${file.name}-${file.size}-${file.lastModified}`,
                });
                });

            syncUploadedFiles();
            renderNewImages();
            ensureCoverSelection();
            updateMediaPositions();
            updateSummary();
        };

        productImageUpload?.addEventListener('change', (event) => addMediaFiles(event.target.files));

        let fileDragDepth = 0;
        const setFileDropzoneState = (isActive) => {
            productImageDropzone?.classList.toggle('border-aurea-gold', isActive);
            productImageDropzone?.classList.toggle('bg-[#fbf8f3]', isActive);
            productImageDropzone?.classList.toggle('dark:bg-[#343632]', isActive);

            if (productImageDropzoneTitle) {
                productImageDropzoneTitle.textContent = isActive
                    ? 'Suelta las imágenes aquí'
                    : 'Arrastra tus imágenes aquí';
            }
        };

        productImageDropzone?.addEventListener('dragenter', (event) => {
            event.preventDefault();
            event.stopPropagation();
            fileDragDepth += 1;
            setFileDropzoneState(true);
        });
        productImageDropzone?.addEventListener('dragover', (event) => {
            event.preventDefault();
            event.stopPropagation();
            event.dataTransfer.dropEffect = 'copy';
            setFileDropzoneState(true);
        });
        productImageDropzone?.addEventListener('dragleave', (event) => {
            event.preventDefault();
            event.stopPropagation();
            fileDragDepth = Math.max(0, fileDragDepth - 1);

            if (fileDragDepth === 0) {
                setFileDropzoneState(false);
            }
        });
        productImageDropzone?.addEventListener('drop', (event) => {
            event.preventDefault();
            event.stopPropagation();
            fileDragDepth = 0;
            setFileDropzoneState(false);
            addMediaFiles(event.dataTransfer.files);
        });

        productGalleryList?.addEventListener('change', (event) => {
            if (! event.target.matches('input[name="cover_image_id"], input[name="cover_new_index"]')) {
                return;
            }

            if (event.target.name === 'cover_image_id') {
                productGalleryList.querySelectorAll('input[name="cover_new_index"]').forEach((input) => {
                    input.checked = false;
                });
            } else {
                productGalleryList.querySelectorAll('input[name="cover_image_id"]').forEach((input) => {
                    input.checked = false;
                });
            }

            updateCoverIndicators();
        });

        productGalleryList?.querySelectorAll('[data-remove-existing-image]').forEach((button) => {
            button.addEventListener('click', () => {
                const card = button.closest('[data-existing-image]');
                const imageId = card?.dataset.imageId;

                if (!card || ! imageId) {
                    return;
                }

                const input = document.createElement('input');
                input.type = 'hidden';
                input.name = 'remove_image_ids[]';
                input.value = imageId;
                removedImagesContainer?.append(input);
                card.classList.add('hidden');
                ensureCoverSelection();
                updateMediaPositions();
                updateSummary();
            });
        });

        productGalleryList?.querySelectorAll('.product-media-card').forEach(bindMediaCard);
        ensureCoverSelection();
        updateMediaPositions();

        const updateSummaryItem = (key, complete, label, optional = false) => {
            const item = document.querySelector(`[data-summary-item="${key}"]`);
            const icon = item?.querySelector('[data-summary-icon]');
            const labelElement = item?.querySelector('[data-summary-label]');

            if (! item || ! icon || ! labelElement) {
                return;
            }

            item.classList.toggle('text-[#a0a7a2]', ! complete && ! optional);
            icon.className = complete
                ? 'flex h-4 w-4 items-center justify-center rounded-full bg-[#edf5ee] text-[#5b8668]'
                : 'h-4 w-4 rounded-full border border-[#dfe2dc]';
            icon.textContent = complete ? '✓' : '';
            labelElement.textContent = label;
        };

        const updateSummary = () => {
            const value = (selector) => document.querySelector(selector)?.value.trim() ?? '';
            const hasGeneral = Boolean(value('[name="name"]') && value('[name="code"]') && value('[name="category_id"]'));
            const price = Number(value('[name="price"]'));
            const hasDetails = value('[name="price"]') !== '' && Number.isFinite(price) && price >= 0;
            const hasCentralStone = Boolean(value('[name="central_stone[stone_id]"]'));
            const hasLateralStone = Boolean(sideStonesToggle?.checked && value('[name="side_stone[stone_id]"]') && value('[name="side_stone[quantity]"]'));
            const imageCount = productGalleryList?.querySelectorAll('[data-existing-image]:not(.hidden)').length + newImages.length;

            updateSummaryItem('general', hasGeneral, 'Información general');
            updateSummaryItem('details', hasDetails, 'Precio y detalles');
            updateSummaryItem('stones', hasCentralStone || hasLateralStone, hasCentralStone || hasLateralStone ? 'Piedras configuradas' : 'Piedras opcionales', ! (hasCentralStone || hasLateralStone));
            updateSummaryItem('images', imageCount > 0, imageCount > 0 ? 'Imágenes configuradas' : 'Imágenes pendientes');
            updateSummaryItem('visibility', Boolean(value('[name="status"]')), 'Visibilidad');
        };

        document.querySelectorAll('#product-form input, #product-form select, #product-form textarea').forEach((field) => {
            field.addEventListener('input', updateSummary);
            field.addEventListener('change', updateSummary);
        });
        updateGalleryState();
        updateSummary();
    </script>
@endPushOnce
