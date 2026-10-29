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
    $productStones = $product->productStones->sortBy([['sort_order', 'asc'], ['id', 'asc']])->values();
    $stoneGroups = old('stone_groups');

    if ($stoneGroups === null) {
        $stoneGroups = $productStones
            ->map(function ($stone): array {
                $stonesPerSide = $stone->stones_per_side;

                if (
                    $stonesPerSide === null &&
                    $stone->resolvedDistribution() === 'symmetric' &&
                    $stone->quantity !== null &&
                    $stone->quantity >= 2 &&
                    $stone->quantity % 2 === 0
                ) {
                    $stonesPerSide = intdiv($stone->quantity, 2);
                }

                return [
                    ...$stone->only([
                        'id',
                        'stone_id',
                        'role',
                        'distribution',
                        'shape',
                        'carats',
                        'quantity',
                        'origin',
                        'certification',
                        'color',
                        'clarity',
                        'cut',
                    ]),
                    'stones_per_side' => $stonesPerSide,
                    'role' => $stone->resolvedRole(),
                    'distribution' => $stone->resolvedDistribution(),
                ];
            })
            ->all();
    }

    $stoneGroups = is_array($stoneGroups) ? array_values($stoneGroups) : [];
    $stoneChoice = old('stone_composition_choice', $stoneGroups === [] ? 'no' : 'yes');
@endphp

<form id="product-form" method="POST"
    action="{{ $isEditing ? route('products.update', $product) : route('products.store') }}" enctype="multipart/form-data"
    class="product-form grid items-start gap-7 xl:grid-cols-[minmax(0,1fr)_19rem]">
    @csrf

    <input id="product-code-generated" type="hidden" name="code_generated" value="{{ $codeWasGenerated }}" />

    <input type="hidden" name="has_side_stones" value="0" />

    @if ($isEditing)
        @method('PATCH')
    @endif

    <div class="space-y-7">
        <nav class="rounded-2xl border border-aurea-border bg-aurea-surface p-4 shadow-[0_5px_20px_rgba(35,42,37,0.03)] dark:bg-[#2d302e] sm:p-5"
            aria-label="Progreso del formulario">
            <div class="flex items-center justify-between gap-3">
                <div>
                    <p class="text-[10px] font-semibold uppercase tracking-[.18em] text-[#aa8046]">Configuración del
                        producto</p>
                    <p class="mt-1 text-sm font-medium text-[#3d4640] dark:text-[#eeeae2]" data-product-mobile-step>
                        Producto</p>
                </div>
                <span class="text-xs text-[#8a938d]" data-product-progress-label>Paso 1 de 5</span>
            </div>
            <div class="mt-4 hidden gap-1 sm:grid sm:grid-cols-5" data-product-stepper>
                @foreach (['Producto', 'Precio', 'Piedras', 'Imágenes', 'Revisión'] as $stepLabel)
                    <button type="button" data-product-step-target="{{ $loop->iteration }}"
                        class="aurea-focus min-w-0 rounded-lg px-2 py-2 text-[11px] text-[#8a938d] transition hover:bg-[#fbf8f3] dark:hover:bg-[#35312a]">
                        <span
                            class="mx-auto flex h-5 w-5 items-center justify-center rounded-full border border-[#dfe2dc] text-[10px] font-semibold"
                            data-product-step-indicator>{{ $loop->iteration }}</span>
                        <span class="mt-1 block truncate" data-product-step-label>{{ $stepLabel }}</span>
                    </button>
                @endforeach
            </div>
            <div class="mt-4 h-1 overflow-hidden rounded-full bg-[#eef0ec] dark:bg-[#4b4a45]"><span
                    data-product-progress-bar
                    class="block h-full w-[20%] rounded-full bg-aurea-gold transition-all"></span></div>
        </nav>

        @if ($errors->any())
            <div class="rounded-xl border border-[#ecd0cb] bg-[#fff8f6] px-4 py-3 text-sm text-aurea-danger"
                role="alert">
                Revisa los campos marcados para continuar.
            </div>
        @endif

        @if ($categoryRelationInactive ?? false)
            <div class="rounded-xl border border-[#eadfcf] bg-[#fffaf2] px-4 py-3 text-sm text-[#80602f] dark:border-[#66543c] dark:bg-[#3b352c] dark:text-[#ead7b7]"
                role="status">
                La categoría histórica de este producto ya no está activa. Puedes conservar la relación actual o
                seleccionar una categoría vigente antes de guardar.
            </div>
        @endif

        <x-admin.form-section step="1"
            title="{{ $isEditing ? '¿Qué información define este producto?' : '¿Qué producto estás creando?' }}"
            description="Cuéntanos lo esencial de la pieza." id="general-title">
            <div class="grid gap-5 sm:grid-cols-2">
                <label>
                    <span class="admin-form-label admin-form-label-required">¿Cómo se llama el producto?</span>
                    <input id="product-name" type="text" name="name" value="{{ old('name', $product->name) }}"
                        placeholder="Ej. Anillo Aura" class="admin-form-input" required />
                    @error('name')
                        <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                    @enderror
                </label>

                <label>
                    <span class="admin-form-label">Modelo o referencia</span>
                    <input type="text" name="model" value="{{ old('model', $product->model) }}"
                        placeholder="Ej. Solitario Aura" class="admin-form-input" />
                    @error('model')
                        <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                    @enderror
                </label>

                <label>
                    <span class="admin-form-label">Subtítulo comercial</span>
                    <input type="text" name="subtitle" value="{{ old('subtitle', $product->subtitle) }}"
                        placeholder="Ej. Elegancia clásica con brillo contemporáneo" class="admin-form-input" />
                    @error('subtitle')
                        <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                    @enderror
                </label>

                <label class="sm:col-span-2">
                    <span class="admin-form-label">¿Cómo describirías esta pieza?</span>
                    <textarea id="product-short-description" name="short_description" rows="3"
                        placeholder="Describe brevemente la pieza y su carácter." class="admin-form-input aurea-focus">{{ old('short_description', $product->short_description) }}</textarea>
                    @error('short_description')
                        <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                    @enderror
                </label>

                <label>
                    <span class="admin-form-label admin-form-label-required">¿A qué categoría pertenece?</span>
                    <select id="product-category" name="category_id" class="admin-form-input" required>
                        <option value="">Selecciona una categoría</option>
                        @foreach ($categories as $category)
                            <option value="{{ $category->id }}" @selected((string) $selectedCategoryId === (string) $category->id)>
                                {{ $category->name }}
                            </option>
                        @endforeach
                    </select>
                    @error('category_id')
                        <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                    @enderror
                </label>

                <div id="product-subcategory-field"
                    class="{{ $selectedCategoryId && $categories->firstWhere('id', (int) $selectedCategoryId)?->children->isNotEmpty() ? '' : 'hidden' }}">
                    <label>
                        <span class="admin-form-label">¿Dentro de qué tipo de producto se encuentra?</span>
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
                        @error('subcategory_id')
                            <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                        @enderror
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
                    @error('collection_id')
                        <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                    @enderror
                </label>

                <label>
                    <span class="admin-form-label admin-form-label-required">Código</span>
                    <input id="product-code" type="text" name="code" value="{{ old('code', $product->code) }}"
                        data-code-generated="{{ $codeWasGenerated === '1' ? 'true' : 'false' }}"
                        placeholder="Ej. ANA-001" class="admin-form-input" required />
                    <span class="admin-form-help">Sugerencia automática editable. Referencia interna del
                        producto.</span>
                    @error('code')
                        <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                    @enderror
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

        <x-admin.form-section step="2" title="Precio y detalles" description="¿Cómo se venderá este producto?"
            id="price-title">
            <div class="grid gap-5 sm:grid-cols-3">
                <label>
                    <span class="admin-form-label admin-form-label-required">¿Cuál es su precio?</span>
                    <div class="admin-form-input-prefix">
                        <span>$</span>
                        <input type="number" name="price" value="{{ old('price', $product->price) }}"
                            min="0" step="0.01" inputmode="decimal" aria-label="Precio base" required />
                    </div>
                    @error('price')
                        <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                    @enderror
                </label>
                <label>
                    <span class="admin-form-label">Precio de oferta</span>
                    <div class="admin-form-input-prefix">
                        <span>$</span>
                        <input type="number" name="sale_price"
                            value="{{ old('sale_price', $product->sale_price) }}" min="0" step="0.01"
                            inputmode="decimal" placeholder="Opcional" aria-label="Precio de oferta" />
                    </div>
                    @error('sale_price')
                        <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                    @enderror
                </label>
                <label>
                    <span class="admin-form-label">Costo</span>
                    <div class="admin-form-input-prefix">
                        <span>$</span>
                        <input type="number" name="cost" value="{{ old('cost', $product->cost) }}"
                            min="0" step="0.01" inputmode="decimal" aria-label="Costo" />
                    </div>
                    @error('cost')
                        <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                    @enderror
                </label>
            </div>
        </x-admin.form-section>

        <x-admin.form-section step="2" title="Detalles de joyería"
            description="¿Qué características tiene la pieza?" id="jewelry-title">
            <div class="grid gap-5 sm:grid-cols-2 lg:grid-cols-3">
                <label>
                    <span class="admin-form-label">¿De qué material está hecha?</span>
                    <select name="material_id" class="admin-form-input">
                        <option value="">Sin material</option>
                        @foreach ($materials as $material)
                            <option value="{{ $material->id }}" @selected((string) $selectedMaterialId === (string) $material->id)>
                                {{ $material->name }}
                            </option>
                        @endforeach
                    </select>
                    @error('material_id')
                        <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                    @enderror
                </label>

                <label>
                    <span class="admin-form-label">¿Qué quilataje tiene el metal?</span>
                    <select name="karat" class="admin-form-input">
                        <option value="">Sin especificar</option>
                        @foreach ($karatOptions as $value => $label)
                            <option value="{{ $value }}" @selected((string) old('karat', $product->karat) === (string) $value)>{{ $label }}
                            </option>
                        @endforeach
                    </select>
                    @error('karat')
                        <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                    @enderror
                </label>

                <label>
                    <span class="admin-form-label">¿Cuál es el color del metal?</span>
                    <select name="metal_color" class="admin-form-input">
                        <option value="">Sin especificar</option>
                        @foreach ($metalColorOptions as $metalColor)
                            <option value="{{ $metalColor }}" @selected(old('metal_color', $product->metal_color) === $metalColor)>{{ $metalColor }}
                            </option>
                        @endforeach
                    </select>
                    @error('metal_color')
                        <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                    @enderror
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
                    @error('base_size_id')
                        <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                    @enderror
                </label>
            </div>
        </x-admin.form-section>

        <x-admin.form-section step="3" title="¿Esta pieza tiene piedras?"
            description="Agrega cada grupo físico que forma parte de la pieza." id="stones-title">
            <div>
                <fieldset class="mb-5 rounded-xl border border-[#eef0ec] p-4 dark:border-[#4b4a45]">
                    <legend class="admin-form-label">¿El producto tiene piedras físicas?</legend>
                    <div class="mt-3 flex flex-wrap gap-3">
                        @foreach (['yes' => 'Sí', 'no' => 'No'] as $value => $label)
                            <label
                                class="flex cursor-pointer items-center gap-2 rounded-lg border border-[#e5e6e0] px-4 py-2 text-sm text-[#59635c] has-[:checked]:border-aurea-gold has-[:checked]:bg-[#fbf8f3] dark:border-[#4b4a45] dark:text-[#d0d4cf]">
                                <input type="radio" name="stone_composition_choice" value="{{ $value }}"
                                    data-stones-choice class="text-aurea-gold focus:ring-aurea-gold"
                                    @checked($value === $stoneChoice) />
                                {{ $label }}
                            </label>
                        @endforeach
                    </div>
                </fieldset>
                <p data-stones-no-message
                    class="hidden mb-5 rounded-lg bg-[#fbfbf9] px-4 py-3 text-sm text-[#68736c] dark:bg-[#292b29] dark:text-[#c4cbc5]">
                    Este producto se guardará sin composición de piedras.</p>
                <div data-stones-groups-ui>
                    <div class="flex items-start justify-between gap-4">
                        <div>
                            <h4 class="text-sm font-medium text-[#3d4640] dark:text-[#eeeae2]">Grupos físicos de
                                piedras</h4>
                            <p class="mt-1 text-xs text-[#8a938d]">Cada grupo puede representar una función y
                                distribución distinta.</p>
                        </div>
                        <button type="button" id="add-side-stone" data-add-stone-group
                            class="aurea-focus inline-flex shrink-0 items-center rounded-lg border border-[#d8d9d2] px-3 py-2 text-xs font-medium text-[#59635c] hover:border-aurea-gold hover:text-[#806441]">
                            Agregar grupo de piedras
                        </button>
                    </div>
                    <div id="stone-groups-list" class="mt-5 space-y-5">
                        @foreach ($stoneGroups as $index => $stoneGroup)
                            @include('products._stone-group', [
                                'stoneGroup' => $stoneGroup,
                                'index' => $index,
                            ])
                        @endforeach
                    </div>
                    <p id="stone-groups-empty"
                        class="{{ $stoneGroups === [] ? '' : 'hidden' }} mt-5 rounded-lg bg-[#fbfbf9] px-4 py-3 text-xs text-[#8a938d] dark:bg-[#292b29]">
                        No hay grupos de piedras configurados.</p>
                    <template id="stone-group-template">
                        @include('products._stone-group', [
                            'stoneGroup' => [],
                            'index' => '__INDEX__',
                            'isTemplate' => true,
                        ])
                    </template>
                </div>
            </div>
        </x-admin.form-section>

        <x-admin.form-section step="4" title="¿Qué imágenes quieres mostrar del producto?"
            description="Agrega las fotografías que se mostrarán en la tienda." id="media-title">
            <div id="product-media-manager" class="space-y-6">
                <label id="product-image-dropzone" for="product-image-upload"
                    class="flex min-h-52 cursor-pointer flex-col items-center justify-center rounded-xl border border-dashed border-[#d8d9d2] bg-[#fbfbf9] px-5 text-center transition hover:border-aurea-gold hover:bg-[#fbf8f3] focus-within:border-aurea-gold focus-within:ring-2 focus-within:ring-aurea-gold-soft/20 dark:bg-[#292b29]">
                    <svg class="h-8 w-8 text-[#b58d55]" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                        stroke-width="1.4" aria-hidden="true">
                        <path d="M4 5h16v14H4zM7 15l3-3 2 2 2-2 3 3M8 9h.01M12 4v7m-3-3 3-3 3 3" />
                    </svg>
                    <span id="product-image-dropzone-title"
                        class="mt-3 text-sm font-medium text-[#59635c] dark:text-[#d0d4cf]">Arrastra tus imágenes
                        aquí</span>
                    <span class="mt-1 text-xs text-[#8a938d]">o haz clic para seleccionarlas</span>
                    <span class="mt-3 text-[11px] text-[#9da59f]">PNG, JPG o WEBP · Máximo 2 MB por imagen</span>
                    <input id="product-image-upload" name="images[]" type="file"
                        accept="image/png,image/jpeg,image/webp" multiple class="sr-only" />
                </label>

                <div id="product-gallery-wrapper" class="{{ $productImages->isEmpty() ? 'hidden' : '' }} space-y-3">
                    <div class="flex flex-wrap items-end justify-between gap-2">
                        <div>
                            <p class="admin-form-label">Imágenes del producto</p>
                            <p class="mt-1 text-xs text-[#8a938d]">Define el texto alternativo y la imagen de portada.
                            </p>
                        </div>
                        <span id="product-gallery-count" class="text-xs text-[#929b94]">{{ $productImages->count() }}
                            imágenes</span>
                    </div>
                    <div id="product-gallery-list" class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3"
                        aria-live="polite">
                        @foreach ($productImages as $image)
                            <article data-existing-image data-media-token="{{ $image->id }}"
                                data-image-id="{{ $image->id }}" draggable="true" tabindex="0"
                                class="product-media-card group overflow-hidden rounded-xl border border-[#e1e3dd] bg-white shadow-[0_4px_14px_rgba(35,42,37,0.04)] dark:border-[#4b4a45] dark:bg-[#292b29]">
                                <div class="relative cursor-grab active:cursor-grabbing">
                                    @if ($image->hasPublicFile())
                                        <img src="{{ $image->publicUrl() }}"
                                            alt="{{ $image->alt ?: $product->name }}"
                                            class="block h-36 w-full object-cover" />
                                    @else
                                        <div
                                            class="flex h-36 w-full items-center justify-center bg-[#f7f3ec] text-xs text-[#929b94] dark:bg-[#292b29] dark:text-[#777d77]">
                                            Sin imagen
                                        </div>
                                    @endif
                                    <span data-media-position
                                        class="absolute left-2 top-2 rounded-md bg-white/90 px-2 py-1 text-[10px] font-semibold text-[#68736c] shadow-sm">Posición
                                        {{ $loop->iteration }}</span>
                                    <span
                                        class="absolute left-2 top-9 rounded-md bg-white/90 px-2 py-1 text-[10px] font-semibold text-[#68736c] shadow-sm">Imagen
                                        guardada</span>
                                    <span data-cover-badge
                                        class="absolute right-2 top-9 rounded-md bg-[#eadfce] px-2 py-1 text-[10px] font-semibold uppercase tracking-[.1em] text-[#806441] {{ $image->is_cover ? '' : 'hidden' }}">Portada</span>
                                    <span
                                        class="absolute right-2 top-2 rounded-md bg-[#28302b]/75 px-2 py-1 text-[10px] font-medium text-white opacity-0 transition group-hover:opacity-100 group-focus-within:opacity-100">Arrastrar</span>
                                </div>
                                <div class="space-y-3 p-3">
                                    <label>
                                        <span class="admin-form-label">Texto alternativo</span>
                                        <input type="text" name="image_alt[{{ $image->id }}]"
                                            value="{{ old('image_alt.' . $image->id, $image->alt) }}"
                                            placeholder="{{ $product->name }}" class="admin-form-input" />
                                    </label>
                                    <div class="flex items-center justify-between gap-2">
                                        <label
                                            class="flex items-center gap-2 text-xs text-[#68736c] dark:text-[#d0d4cf]">
                                            <input type="radio" name="cover_image_id" value="{{ $image->id }}"
                                                @checked($image->is_cover)
                                                class="text-aurea-gold focus:ring-aurea-gold" />
                                            Portada
                                        </label>
                                        <div class="ml-auto flex items-center gap-1">
                                            <button type="button" data-move-image="up"
                                                title="Mover imagen hacia arriba"
                                                class="aurea-focus rounded-md p-2 text-[#8a938d] hover:bg-[#f1f2ee] hover:text-aurea-focus"
                                                aria-label="Mover imagen hacia arriba">↑</button>
                                            <button type="button" data-move-image="down"
                                                title="Mover imagen hacia abajo"
                                                class="aurea-focus rounded-md p-2 text-[#8a938d] hover:bg-[#f1f2ee] hover:text-aurea-focus"
                                                aria-label="Mover imagen hacia abajo">↓</button>
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

        <section data-product-step="5"
            class="rounded-2xl border border-aurea-border bg-aurea-surface p-5 shadow-[0_5px_20px_rgba(35,42,37,0.03)] dark:bg-[#2d302e] sm:p-6">
            <div class="border-b border-[#eef0ec] pb-5 dark:border-[#4b4a45]">
                <h3 class="font-serif text-xl text-[#28302b] dark:text-[#eeeae2]">Así se verá tu producto</h3>
                <p class="mt-1 text-xs text-[#8a938d]">Revisa la información antes de guardar.</p>
            </div>
            <div class="mt-5 overflow-hidden rounded-2xl border border-[#e5e6e0] bg-white shadow-[0_10px_30px_rgba(35,42,37,0.06)] dark:border-[#4b4a45] dark:bg-[#292b29]"
                data-product-review>
                <div class="grid gap-6 p-4 sm:p-6 lg:grid-cols-[minmax(0,.9fr)_minmax(0,1.1fr)]">
                    <div class="overflow-hidden rounded-xl bg-[#f7f3ec] dark:bg-[#343632]">
                        <div data-review-image-placeholder
                            class="flex aspect-square items-center justify-center text-xs text-[#929b94]">Sin imagen
                            principal</div><img data-review-main-image class="hidden aspect-square w-full object-cover"
                            alt="" />
                    </div>
                    <div class="flex flex-col justify-center">
                        <button type="button" data-review-edit="1"
                            class="aurea-focus self-start text-xs font-medium text-[#806441]">Editar producto</button>
                        <h1 data-review-product-name
                            class="mt-3 font-serif text-3xl leading-tight text-[#28302b] dark:text-[#eeeae2]">Nombre
                            del producto</h1>
                        <p data-review-product-description
                            class="mt-3 text-sm leading-6 text-[#68736c] dark:text-[#c4cbc5]">Agrega una descripción
                            breve para verla aquí.</p>
                        <div class="mt-5 flex flex-wrap items-baseline gap-x-3 gap-y-1"><span data-review-product-price
                                class="text-xl font-medium text-[#28302b] dark:text-[#eeeae2]">$0.00</span><span
                                data-review-product-sale class="hidden text-sm text-[#a56a5c]"></span></div>
                        <dl data-review-product-attributes class="mt-5 grid gap-x-4 gap-y-2 text-xs sm:grid-cols-2">
                        </dl>
                    </div>
                </div>
                <div class="border-t border-[#eef0ec] p-4 sm:p-6 dark:border-[#4b4a45]">
                    <div class="flex items-center justify-between gap-3">
                        <div>
                            <h2 class="font-serif text-xl text-[#28302b] dark:text-[#eeeae2]">Información de las
                                piedras</h2>
                            <p class="mt-1 text-xs text-[#8a938d]">Así se mostrará la composición en la tienda.</p>
                        </div><button type="button" data-review-edit="3"
                            class="aurea-focus text-xs font-medium text-[#806441]">Editar piedras</button>
                    </div>
                    <div data-review-stone-summary class="mt-4 hidden grid gap-3 sm:grid-cols-3"></div>
                    <div data-review-stones class="mt-4 grid gap-3 sm:grid-cols-2"></div>
                </div>
                <div class="border-t border-[#eef0ec] px-4 py-4 dark:border-[#4b4a45] sm:px-6">
                    <div class="flex flex-wrap items-center justify-between gap-3">
                        <p data-review-variants class="text-xs text-[#8a938d]"></p>
                        <div class="flex items-center gap-3"><button type="button" data-review-edit="2"
                                class="aurea-focus text-xs font-medium text-[#806441]">Editar precio</button><button
                                type="button" data-review-edit="4"
                                class="aurea-focus text-xs font-medium text-[#806441]">Editar imágenes</button></div>
                    </div>
                    <p data-review-images class="mt-2 text-xs text-[#8a938d]"></p>
                </div>
            </div>
        </section>

        <div class="flex items-center justify-between gap-3" data-product-navigation>
            <button type="button" data-product-back
                class="aurea-focus rounded-lg border border-[#d8d9d2] px-4 py-2.5 text-sm font-medium text-[#59635c] hover:border-aurea-gold hover:text-[#806441]">Atrás</button>
            <button type="button" data-product-next
                class="aurea-focus rounded-lg bg-aurea-gold px-4 py-2.5 text-sm font-medium text-white hover:bg-[#9f793f]">Continuar</button>
            <button type="submit" form="product-form" data-product-final-submit
                class="hidden aurea-focus rounded-lg bg-aurea-gold px-5 py-2.5 text-sm font-medium text-white shadow-sm hover:bg-[#9f793f]">{{ $isEditing ? 'Guardar cambios' : 'Crear producto' }}</button>
        </div>
    </div>

    <aside class="space-y-7 lg:sticky lg:top-7">
        <div>
            <x-admin.form-section title="Visibilidad" description="Define cómo se mostrará el producto en la tienda."
                id="visibility-title">
                <div class="space-y-4">
                    <label>
                        <span class="admin-form-label admin-form-label-required">Estado</span>
                        <select name="status" class="admin-form-input" required>
                            @foreach ($productStatuses as $value => $label)
                                <option value="{{ $value }}" @selected(old('status', $product->status ?? 'draft') === $value)>{{ $label }}
                                </option>
                            @endforeach
                        </select>
                        @error('status')
                            <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                        @enderror
                    </label>
                </div>
            </x-admin.form-section>
        </div>

        <section
            class="rounded-2xl border border-aurea-border bg-aurea-surface p-5 shadow-[0_5px_20px_rgba(35,42,37,0.03)] dark:bg-[#2d302e]"
            aria-labelledby="preview-title">
            <div class="flex items-center justify-between gap-3">
                <div>
                    <h3 id="preview-title" class="font-serif text-xl text-[#28302b] dark:text-[#eeeae2]">Resumen</h3>
                    <p class="mt-1 text-xs text-[#8a938d]">
                        {{ $isEditing ? 'Producto existente' : 'Antes de publicar' }}</p>
                </div>
            </div>
            <ul class="mt-5 space-y-3 text-xs text-[#68736c] dark:text-[#c4cbc5]">
                <li data-summary-item="general" class="flex items-center gap-2">
                    <span data-summary-icon
                        class="flex h-4 w-4 items-center justify-center rounded-full bg-[#edf5ee] text-[#5b8668]">✓</span>
                    <span data-summary-label>Información general</span>
                </li>
                <li data-summary-item="details" class="flex items-center gap-2">
                    <span data-summary-icon
                        class="flex h-4 w-4 items-center justify-center rounded-full bg-[#edf5ee] text-[#5b8668]">✓</span>
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
            </ul>
        </section>

    </aside>
</form>

@pushOnce('scripts')
    <script>
        const productNameInput = document.getElementById('product-name');
        const productForm = document.getElementById('product-form');
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
        const addStoneGroupButton = document.querySelector('[data-add-stone-group]');
        const stoneGroupsList = document.getElementById('stone-groups-list');
        const stoneGroupsEmpty = document.getElementById('stone-groups-empty');
        const stoneGroupsUi = document.querySelector('[data-stones-groups-ui]');
        const stoneNoMessage = document.querySelector('[data-stones-no-message]');
        const stoneGroupTemplate = document.getElementById('stone-group-template');
        const productImageUpload = document.getElementById('product-image-upload');
        const productImageDropzone = document.getElementById('product-image-dropzone');
        const productImageDropzoneTitle = document.getElementById('product-image-dropzone-title');
        const productGalleryWrapper = document.getElementById('product-gallery-wrapper');
        const productGalleryList = document.getElementById('product-gallery-list');
        const productGalleryCount = document.getElementById('product-gallery-count');
        const removedImagesContainer = document.getElementById('removed-images-container');
        const productStepNames = ['Producto', 'Precio y detalles', 'Piedras', 'Imágenes', 'Revisión'];
        const productStepSections = Array.from(document.querySelectorAll('[data-product-step]'));
        const productStepper = document.querySelector('[data-product-stepper]');
        const productProgressBar = document.querySelector('[data-product-progress-bar]');
        const productProgressLabel = document.querySelector('[data-product-progress-label]');
        const productMobileStep = document.querySelector('[data-product-mobile-step]');
        const productNextButton = document.querySelector('[data-product-next]');
        const productBackButton = document.querySelector('[data-product-back]');
        const productFinalSubmit = document.querySelector('[data-product-final-submit]');
        const productStoneChoiceInputs = document.querySelectorAll('[data-stones-choice]');
        let currentProductStep = 1;
        let newImages = [];

        const setProductStoneChoice = (choice) => {
            const hasStones = choice === 'yes';

            stoneGroupsList?.classList.toggle('hidden', !hasStones);
            stoneGroupsUi?.classList.toggle('hidden', !hasStones);
            stoneGroupsUi?.toggleAttribute('aria-hidden', !hasStones);
            stoneNoMessage?.classList.toggle('hidden', hasStones);
            stoneGroupsEmpty?.classList.toggle('hidden', !hasStones || Boolean(stoneGroupsList?.children.length));
            stoneGroupsList?.querySelectorAll('input, select, textarea').forEach((field) => {
                field.disabled = !hasStones;
            });

            if (hasStones && stoneGroupsList && stoneGroupsList.children.length === 0) {
                addStoneGroup();
            }
        };

        const productStepFields = (step) => productStepSections
            .filter((section) => Number(section.dataset.productStep) === step)
            .flatMap((section) => Array.from(section.querySelectorAll('input, select, textarea')))
            .filter((field) => !field.disabled && !field.readOnly && field.type !== 'hidden');

        const setProductStep = (step, validateCurrent = false) => {
            if (validateCurrent && step > currentProductStep) {
                const invalidField = productStepFields(currentProductStep).find((field) => !field.checkValidity());

                if (invalidField) {
                    invalidField.reportValidity();
                    return;
                }
            }

            currentProductStep = Math.min(5, Math.max(1, step));
            productStepSections.forEach((section) => {
                const isCurrent = Number(section.dataset.productStep) === currentProductStep;
                section.classList.toggle('hidden', !isCurrent);
                section.toggleAttribute('aria-hidden', !isCurrent);
            });
            document.querySelectorAll('[data-product-step-target]').forEach((button) => {
                const isCurrent = Number(button.dataset.productStepTarget) === currentProductStep;
                const indicator = button.querySelector('[data-product-step-indicator]');
                button.classList.toggle('bg-[#fbf8f3]', isCurrent);
                button.classList.toggle('text-[#806441]', isCurrent);
                button.setAttribute('aria-current', isCurrent ? 'step' : 'false');
                indicator?.classList.toggle('border-aurea-gold', isCurrent);
                indicator?.classList.toggle('bg-aurea-gold', isCurrent);
                indicator?.classList.toggle('text-white', isCurrent);
            });
            if (productProgressBar) {
                productProgressBar.style.width = `${(currentProductStep / 5) * 100}%`;
            }
            if (productProgressLabel) {
                productProgressLabel.textContent = `Paso ${currentProductStep} de 5`;
            }
            if (productMobileStep) {
                productMobileStep.textContent = productStepNames[currentProductStep - 1];
            }
            productBackButton?.classList.toggle('invisible', currentProductStep === 1);
            productNextButton?.classList.toggle('hidden', currentProductStep === 5);
            productFinalSubmit?.classList.toggle('hidden', currentProductStep !== 5);
            productStepper?.scrollIntoView({
                behavior: 'smooth',
                block: 'nearest'
            });
        };

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

            productBaseSizeSelect.value = sizes.some((size) => String(size.id) === currentSizeId) ?
                currentSizeId :
                '';
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
                option.hidden = !matches;
                option.disabled = !matches;
            });

            if (clearSelection || !matchingOptions.some((option) => option.value === productSubcategorySelect.value)) {
                productSubcategorySelect.value = '';
            }

            const hasSubcategories = matchingOptions.length > 0;
            productSubcategoryField.classList.toggle('hidden', !hasSubcategories);
            productSubcategoryField.toggleAttribute('aria-hidden', !hasSubcategories);
            productSubcategorySelect.disabled = !hasSubcategories;
            updateApplicableSizes();
        };

        const updateStoneGroupLabels = () => {
            stoneGroupsList?.querySelectorAll('[data-stone-group]').forEach((group, index) => {
                const label = group.querySelector('[data-stone-group-label]');

                if (label) {
                    label.textContent = `Grupo de piedras ${index + 1}`;
                }

                updateStoneGroupFields(group);
                updateStoneGroupSummary(group);
            });

            stoneGroupsEmpty?.classList.toggle('hidden', Boolean(stoneGroupsList?.children.length));
        };

        const updateStoneGroupFields = (group) => {
            const placement = group.querySelector('[data-stone-placement]:checked')?.value;
            const role = group.querySelector('[data-stone-role]');
            const distributionInput = group.querySelector('[data-stone-distribution]');
            const placementFieldset = group.querySelector('[data-stone-placement-fieldset]');
            const placementOptions = group.querySelector('[data-stone-placement-options]');
            const placementSummary = group.querySelector('[data-stone-placement-summary]');
            const placementSummaryTitle = group.querySelector('[data-stone-placement-summary-title]');
            const placementSummaryDescription = group.querySelector('[data-stone-placement-summary-description]');
            const placementSummaryDiagram = group.querySelector('[data-stone-placement-summary-diagram]');
            const stoneSection = group.querySelector('[data-stone-stone-section]');
            const quantitySection = group.querySelector('[data-stone-quantity-section]');
            const technicalSection = group.querySelector('[data-stone-technical-section]');
            const stoneId = group.querySelector('select[name$="[stone_id]"]')?.value;
            const quantity = group.querySelector('[data-stone-quantity]');
            const quantityHelp = group.querySelector('[data-stone-quantity-help]');
            const quantityField = group.querySelector('[data-stone-quantity-field]');
            const symmetricField = group.querySelector('[data-stone-symmetric-field]');
            const mainQuantity = group.querySelector('[data-stone-main-quantity]');
            const stonesPerSide = group.querySelector('[data-stones-per-side]');
            const total = group.querySelector('[data-stone-total]');
            const perSide = Number(stonesPerSide?.value);
            const placementMap = {
                main: ['main', 'single'],
                symmetric: ['accent', 'symmetric'],
                pave: ['pave', 'distributed'],
                halo: ['halo', 'distributed'],
            };
            const mappedValues = placementMap[placement];
            const legacyOther = placement === 'legacy' || (!placement && role?.value === 'other');
            const distribution = mappedValues?.[1] ?? distributionInput?.value;
            const placementDetails = {
                main: ['Piedra principal', 'Una piedra protagonista de la pieza.'],
                symmetric: ['Laterales simétricas', 'Las mismas piedras se repiten en ambos lados.'],
                pave: ['Pavé', 'Varias piedras distribuidas sobre la superficie.'],
                halo: ['Halo', 'Piedras colocadas alrededor de una piedra principal.'],
                legacy: ['Acentos (compatibilidad)', 'Configuración anterior conservada sin cambios.'],
            } [placement];
            const placementDiagrams = {
                main: '●',
                symmetric: '• • ● • •',
                pave: '• • • • • • •',
                halo: '• • •\n• ● •\n• • •',
                legacy: '•  ●  •',
            };

            if (mappedValues && role) {
                role.value = mappedValues[0];
            }
            if (mappedValues && distributionInput) {
                distributionInput.value = distribution;
            }

            placementFieldset?.classList.toggle('hidden', Boolean(placement));
            placementOptions?.classList.toggle('hidden', Boolean(placement));
            placementSummary?.classList.toggle('hidden', !placement);
            placementSummary?.classList.toggle('flex', Boolean(placement));
            if (placementDetails) {
                if (placementSummaryTitle) {
                    placementSummaryTitle.textContent = placementDetails[0];
                }
                if (placementSummaryDescription) {
                    placementSummaryDescription.textContent = placementDetails[1];
                }
                if (placementSummaryDiagram) {
                    placementSummaryDiagram.textContent = placementDiagrams[placement] ?? '';
                }
            }

            stoneSection?.classList.toggle('hidden', !placement && !legacyOther);
            quantitySection?.classList.toggle('hidden', !stoneId);
            technicalSection?.classList.toggle('hidden', !stoneId);
            quantityField?.classList.toggle('hidden', placement === 'main' || placement === 'symmetric');
            symmetricField?.classList.toggle('hidden', placement !== 'symmetric');
            mainQuantity?.classList.toggle('hidden', placement !== 'main');
            if (stonesPerSide) {
                stonesPerSide.required = distribution === 'symmetric';
            }

            if (quantity) {
                quantity.readOnly = distribution !== 'distributed';
                quantity.required = distribution === 'distributed';

                if (distribution === 'single') {
                    quantity.value = '1';
                } else if (distribution === 'symmetric' && Number.isInteger(perSide) && perSide > 0) {
                    quantity.value = String(perSide * 2);
                }
            }

            if (quantityHelp) {
                quantityHelp.textContent = placement === 'pave' ?
                    'Número total de piedras distribuidas en la pieza.' :
                    placement === 'halo' ?
                    'Número total de piedras que forman el halo.' :
                    'Número total de piedras decorativas de este grupo.';
            }

            if (!total) {
                return;
            }

            total.textContent = distribution === 'symmetric' && Number.isInteger(perSide) && perSide > 0 ?
                `Total en ambos lados: ${perSide * 2} piedras` :
                'Total en ambos lados: — piedras';

        };

        const updateStoneGroupSummary = (group) => {
            const placement = group.querySelector('[data-stone-placement]:checked')?.value;
            const label = {
                main: 'Piedra principal',
                symmetric: 'Laterales simétricas',
                pave: 'Pavé',
                halo: 'Halo',
                accents: 'Acentos',
            } [placement];
            const isLegacyAccents = group.querySelector('[data-stone-role]')?.value === 'accent'
                && group.querySelector('[data-stone-distribution]')?.value === 'distributed';
            const resolvedLabel = label ?? (isLegacyAccents ? 'Acentos (compatibilidad)' : null);
            const stone = group.querySelector('select[name$="[stone_id]"]')?.selectedOptions[0]?.textContent?.trim();
            const stonesPerSide = group.querySelector('[data-stones-per-side]')?.value;
            const quantity = group.querySelector('[data-stone-quantity]')?.value;
            const carats = group.querySelector('input[name$="[carats]"]')?.value.trim();
            const technical = [
                group.querySelector('input[name$="[origin]"]')?.value.trim(),
                group.querySelector('input[name$="[certification]"]')?.value.trim(),
                group.querySelector('input[name$="[color]"]')?.value.trim(),
                group.querySelector('input[name$="[clarity]"]')?.value.trim(),
                group.querySelector('input[name$="[cut]"]')?.value.trim(),
            ].filter(Boolean).join(' · ');
            const summary = group.querySelector('[data-stone-group-summary]');

            if (!summary) {
                return;
            }

            if (!resolvedLabel && group.querySelector('[data-stone-role]')?.value === 'other') {
                summary.textContent = `Configuración personalizada antigua${stone ? ` · ${stone}` : ''}`;
                return;
            }

            if (!resolvedLabel) {
                summary.textContent = 'Elige cómo están colocadas estas piedras.';
                return;
            }

            const details = [resolvedLabel, stone || null];
            if (placement === 'symmetric' && stonesPerSide) {
                details.push(`${stonesPerSide} por lado`, `${Number(stonesPerSide) * 2} total`);
            } else if (['pave', 'halo'].includes(placement) && quantity) {
                details.push(`${quantity} piedras`);
            } else if (placement === 'main') {
                details.push('1 piedra');
            }
            if (carats) {
                details.push(`${carats} ct${placement === 'main' ? '' : ' c/u'}`);
            }
            if (technical) {
                details.push(technical);
            }

            summary.textContent = details.filter(Boolean).join(' · ');
        };

        const setReviewRows = (container, rows) => {
            if (!container) {
                return;
            }

            container.replaceChildren();
            rows.filter(([, value]) => value !== '').forEach(([label, value]) => {
                const term = document.createElement('dt');
                const description = document.createElement('dd');
                term.className = 'text-xs text-[#8a938d]';
                description.className = 'text-sm font-medium text-[#59635c] dark:text-[#d0d4cf]';
                term.textContent = label;
                description.textContent = value;
                container.append(term, description);
            });
        };

        const updateProductReview = () => {
            const value = (selector) => document.querySelector(selector)?.value.trim() ?? '';
            const selectedText = (selector) => {
                const option = document.querySelector(selector)?.selectedOptions[0];

                return option?.value ? option.textContent.trim() : '';
            };
            const productName = document.querySelector('[data-review-product-name]');
            const productDescription = document.querySelector('[data-review-product-description]');
            const productPrice = document.querySelector('[data-review-product-price]');
            const productSale = document.querySelector('[data-review-product-sale]');
            const productAttributes = document.querySelector('[data-review-product-attributes]');
            const formatPrice = (rawValue) => {
                const amount = Number(rawValue);

                return rawValue !== '' && Number.isFinite(amount) ?
                    `$${amount.toLocaleString('es-MX', { minimumFractionDigits: 2, maximumFractionDigits: 2 })} MXN` :
                    'Precio pendiente';
            };

            if (productName) {
                productName.textContent = value('[name="name"]') || 'Nombre del producto';
            }
            if (productDescription) {
                productDescription.textContent = value('[name="short_description"]') ||
                    'Agrega una descripción breve para verla aquí.';
            }
            if (productPrice) {
                productPrice.textContent = formatPrice(value('[name="price"]'));
            }
            if (productSale) {
                const salePrice = value('[name="sale_price"]');
                productSale.textContent = salePrice ? `Oferta: ${formatPrice(salePrice)}` : '';
                productSale.classList.toggle('hidden', salePrice === '');
            }
            setReviewRows(productAttributes, [
                ['Material', selectedText('[name="material_id"]')],
                ['Kilataje', selectedText('[name="karat"]')],
                ['Color del oro', selectedText('[name="metal_color"]')],
                ['Talla base', selectedText('[name="base_size_id"]')],
            ]);

            const reviewImage = document.querySelector('[data-review-main-image]');
            const reviewImagePlaceholder = document.querySelector('[data-review-image-placeholder]');
            const selectedCover = productGalleryList?.querySelector(
                'input[name="cover_image_id"]:checked, input[name="cover_new_index"]:checked');
            const coverCard = selectedCover?.closest('[data-existing-image], [data-new-image]') ??
                productGalleryList?.querySelector('[data-existing-image]:not(.hidden), [data-new-image]');
            const coverImage = coverCard?.querySelector('img');

            if (reviewImage && coverImage) {
                reviewImage.src = coverImage.src;
                reviewImage.alt = productName?.textContent || '';
                reviewImage.classList.remove('hidden');
                reviewImagePlaceholder?.classList.add('hidden');
            } else {
                reviewImage?.classList.add('hidden');
                reviewImagePlaceholder?.classList.remove('hidden');
            }

            const variantsReview = document.querySelector('[data-review-variants]');
            if (variantsReview) {
                variantsReview.textContent = productIsEditing ?
                    'Las variantes existentes se conservan y se gestionan desde su sección independiente.' :
                    'Se creará la variante base al guardar. Después podrás agregar otras versiones.';
            }

            const stonesReview = document.querySelector('[data-review-stones]');
            const stoneSummary = document.querySelector('[data-review-stone-summary]');
            stonesReview?.replaceChildren();
            stoneSummary?.replaceChildren();
            const labels = {
                main: 'Piedra principal',
                symmetric: 'Laterales simétricas',
                pave: 'Pavé',
                halo: 'Halo',
            };
            const hasStones = document.querySelector('[data-stones-choice]:checked')?.value === 'yes';
            if (hasStones) {
                stoneGroupsList?.querySelectorAll('[data-stone-group]').forEach((group) => {
                    const placement = group.querySelector('[data-stone-placement]:checked')?.value;
                    const stone = group.querySelector('select[name$="[stone_id]"]')?.selectedOptions[0]
                        ?.textContent?.trim() || '';
                    const distribution = group.querySelector('[data-stone-distribution]')?.value;
                    const quantity = Number(group.querySelector('[data-stone-quantity]')?.value);
                    const stonesPerSide = Number(group.querySelector('[data-stones-per-side]')?.value);
                    const carats = group.querySelector('input[name$="[carats]"]')?.value.trim() || '';
                    const title = labels[placement] ?? (group.querySelector('[data-stone-role]')?.value ===
                        'other' ? 'Configuración personalizada antigua' : 'Grupo pendiente');
                    const quantityText = distribution === 'single' ?
                        '1 piedra' :
                        distribution === 'symmetric' && Number.isInteger(stonesPerSide) && stonesPerSide > 0 ?
                        `${stonesPerSide} por lado · ${stonesPerSide * 2} total` :
                        Number.isInteger(quantity) && quantity > 0 ?
                        `${quantity} ${quantity === 1 ? 'piedra' : 'piedras'}` :
                        'Cantidad pendiente';
                    const technical = [
                        group.querySelector('input[name$="[origin]"]')?.value.trim(),
                        group.querySelector('input[name$="[certification]"]')?.value.trim(),
                        group.querySelector('input[name$="[color]"]')?.value.trim(),
                        group.querySelector('input[name$="[clarity]"]')?.value.trim(),
                        group.querySelector('input[name$="[cut]"]')?.value.trim(),
                    ].filter(Boolean).join(' · ');
                    const item = document.createElement('article');
                    const itemTitle = document.createElement('p');
                    const itemDetails = document.createElement('p');
                    const itemTechnical = document.createElement('p');
                    item.className =
                        `rounded-xl border p-4 ${placement === 'main' ? 'border-[#d9c39e] bg-[#fbf8f3]' : 'border-[#e5e6e0] bg-[#fbfbf9]'} dark:border-[#4b4a45] dark:bg-[#292b29]`;
                    itemTitle.className = 'text-[10px] font-semibold uppercase tracking-[.12em] text-[#806441]';
                    itemDetails.className = 'mt-1 text-sm font-medium text-[#3d4640] dark:text-[#eeeae2]';
                    itemTechnical.className = 'mt-2 text-xs leading-5 text-[#68736c] dark:text-[#c4cbc5]';
                    itemTitle.textContent = placement === 'main' ? 'Piedra central' : title;
                    itemDetails.textContent =
                        `${stone || 'Piedra sin seleccionar'} · ${quantityText}${carats ? ` · ${carats} ct${distribution === 'single' ? '' : ' c/u'}` : ''}`;
                    itemTechnical.textContent = technical;
                    item.append(itemTitle, itemDetails);
                    if (technical) {
                        item.append(itemTechnical);
                    }
                    stonesReview?.append(item);
                });
            }
            if (stonesReview && !stonesReview.children.length) {
                stonesReview.textContent = 'No se configuraron piedras físicas.';
            }

            const imageCount = productGalleryList?.querySelectorAll('[data-existing-image]:not(.hidden)').length +
                newImages.length;
            const reviewImages = document.querySelector('[data-review-images]');
            if (reviewImages) {
                reviewImages.textContent =
                    `${imageCount} ${imageCount === 1 ? 'imagen configurada' : 'imágenes configuradas'}`;
            }

            const stoneGroups = Array.from(stoneGroupsList?.querySelectorAll('[data-stone-group]') ?? []);
            const mainGroup = stoneGroups.find((group) => group.querySelector('[data-stone-placement]:checked')
                    ?.value === 'main') ??
                stoneGroups.find((group) => ['color', 'clarity', 'cut'].some((field) => group.querySelector(
                    `input[name$="[${field}]"]`)?.value.trim()));
            const summaryFields = [
                ['Color', mainGroup?.querySelector('input[name$="[color]"]')?.value.trim() || ''],
                ['Claridad', mainGroup?.querySelector('input[name$="[clarity]"]')?.value.trim() || ''],
                ['Corte', mainGroup?.querySelector('input[name$="[cut]"]')?.value.trim() || ''],
            ].filter(([, value]) => value !== '');
            if (stoneSummary && summaryFields.length > 0) {
                stoneSummary.classList.remove('hidden');
                summaryFields.forEach(([label, fieldValue]) => {
                    const item = document.createElement('div');
                    const caption = document.createElement('span');
                    const value = document.createElement('strong');
                    item.className = 'rounded-lg bg-[#fbfbf9] px-3 py-2 dark:bg-[#292b29]';
                    caption.className = 'block text-[10px] uppercase tracking-[.12em] text-[#8a938d]';
                    value.className = 'mt-1 block text-sm font-medium text-[#59635c] dark:text-[#d0d4cf]';
                    caption.textContent = label;
                    value.textContent = fieldValue;
                    item.append(caption, value);
                    stoneSummary.append(item);
                });
            }
        };

        const bindStoneGroup = (group) => {
            const setExpanded = (expanded) => {
                const toggle = group.querySelector('[data-stone-group-toggle]');
                const content = group.querySelector('[data-stone-group-content]');
                const label = toggle?.querySelector('[data-stone-group-toggle-label]');

                toggle?.setAttribute('aria-expanded', String(expanded));
                content?.classList.toggle('hidden', !expanded);
                if (label) {
                    label.textContent = expanded ? 'Ocultar' : 'Editar';
                }
            };

            const collapseOtherGroups = () => {
                stoneGroupsList?.querySelectorAll('[data-stone-group]').forEach((otherGroup) => {
                    if (otherGroup === group) {
                        return;
                    }

                    const toggle = otherGroup.querySelector('[data-stone-group-toggle]');
                    const content = otherGroup.querySelector('[data-stone-group-content]');
                    const label = toggle?.querySelector('[data-stone-group-toggle-label]');

                    toggle?.setAttribute('aria-expanded', 'false');
                    content?.classList.add('hidden');
                    if (label) {
                        label.textContent = 'Editar';
                    }
                });
            };

            group.querySelector('[data-remove-stone-group]')?.addEventListener('click', () => {
                group.remove();
                updateStoneGroupLabels();
                updateSummary();
            });
            group.querySelector('[data-stones-per-side]')?.addEventListener('input', () => updateStoneGroupFields(
                group));
            group.querySelectorAll('[data-stone-placement]')
                .forEach((field) => field.addEventListener('change', () => {
                    updateStoneGroupFields(group);
                    updateStoneGroupSummary(group);
                    updateSummary();
                }));
            group.querySelector('[data-stone-quantity]')?.addEventListener('input', () => updateStoneGroupSummary(
                group));
            group.querySelectorAll('[data-stone-technical-choice]').forEach((field) => {
                field.addEventListener('change', () => {
                    const panel = group.querySelector('[data-stone-technical-panel]');
                    panel?.classList.toggle('hidden', field.value !== 'yes');
                    updateStoneGroupSummary(group);
                    updateSummary();
                });
            });
            group.querySelector('[data-stone-change-placement]')?.addEventListener('click', () => {
                group.querySelector('[data-stone-placement-fieldset]')?.classList.remove('hidden');
                group.querySelector('[data-stone-placement-options]')?.classList.remove('hidden');
                group.querySelector('[data-stone-placement-summary]')?.classList.add('hidden');
                group.querySelector('[data-stone-placement-summary]')?.classList.remove('flex');
            });
            group.querySelector('[data-stone-group-toggle]')?.addEventListener('click', (event) => {
                const expanded = event.currentTarget.getAttribute('aria-expanded') === 'true';

                if (!expanded) {
                    collapseOtherGroups();
                }
                setExpanded(!expanded);
            });
            group.querySelectorAll('input, select').forEach((field) => field.addEventListener('input', () =>
                updateStoneGroupSummary(group)));
            group.querySelector('select[name$="[stone_id]"]')?.addEventListener('change', () => {
                updateStoneGroupFields(group);
                updateStoneGroupSummary(group);
                updateSummary();
            });
            updateStoneGroupFields(group);
            updateStoneGroupSummary(group);

            if (group.hasAttribute('data-stone-configured')) {
                setExpanded(false);
            }
        };

        const addStoneGroup = () => {
            if (!stoneGroupTemplate || !stoneGroupsList) {
                return;
            }

            const index = stoneGroupsList.children.length;
            const group = stoneGroupTemplate.content.cloneNode(true);
            const element = group.querySelector('[data-stone-group]');

            stoneGroupsList.querySelectorAll('[data-stone-group-content]').forEach((content) => content.classList.add(
                'hidden'));
            stoneGroupsList.querySelectorAll('[data-stone-group-toggle]').forEach((toggle) => {
                toggle.setAttribute('aria-expanded', 'false');
                toggle.querySelector('[data-stone-group-toggle-label]')?.replaceChildren(document
                    .createTextNode('Editar'));
            });

            group.querySelectorAll('[name]').forEach((field) => {
                field.name = field.name.replaceAll('__INDEX__', String(index));
            });
            group.querySelectorAll('[id], [aria-controls]').forEach((field) => {
                field.id = field.id?.replaceAll('__INDEX__', String(index));
                field.setAttribute('aria-controls', field.getAttribute('aria-controls')?.replaceAll('__INDEX__',
                    String(index)) ?? '');
            });
            stoneGroupsList.append(group);
            const stonesYes = document.querySelector('[data-stones-choice][value="yes"]');
            if (stonesYes) {
                stonesYes.checked = true;
            }
            setProductStoneChoice('yes');
            bindStoneGroup(element);
            updateStoneGroupLabels();
            updateSummary();
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
            const categoryOption = productCategorySelect?.value ?
                productCategorySelect.selectedOptions[0] :
                null;
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
            if (codeManuallyEdited || !productCodeInput) {
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
                    headers: {
                        Accept: 'application/json'
                    },
                });
                const data = await response.json();

                if (requestId === codeSuggestionRequestId && !codeManuallyEdited) {
                    productCodeInput.value = data.code ?? suggestProductCode();
                    productCodeGeneratedInput.value = '1';
                }
            } catch (error) {
                if (requestId === codeSuggestionRequestId && !codeManuallyEdited) {
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

        addStoneGroupButton?.addEventListener('click', addStoneGroup);
        stoneGroupsList?.querySelectorAll('[data-stone-group]').forEach(bindStoneGroup);
        updateStoneGroupLabels();
        productStoneChoiceInputs.forEach((input) => {
            input.addEventListener('change', () => setProductStoneChoice(input.value));
        });
        setProductStoneChoice(document.querySelector('[data-stones-choice]:checked')?.value ?? 'no');

        document.querySelectorAll('[data-product-step-target]').forEach((button) => {
            button.addEventListener('click', () => setProductStep(Number(button.dataset.productStepTarget)));
        });
        productBackButton?.addEventListener('click', () => setProductStep(currentProductStep - 1));
        productNextButton?.addEventListener('click', () => setProductStep(currentProductStep + 1, true));
        document.querySelectorAll('[data-review-edit]').forEach((button) => {
            button.addEventListener('click', () => setProductStep(Number(button.dataset.reviewEdit)));
        });
        productForm?.addEventListener('submit', (event) => {
            if (currentProductStep !== 5) {
                event.preventDefault();
                setProductStep(currentProductStep + 1, true);

                return;
            }

            if (!productForm.checkValidity()) {
                event.preventDefault();
                const invalidField = productStepSections
                    .flatMap((section) => Array.from(section.querySelectorAll('input, select, textarea')))
                    .find((field) => !field.disabled && !field.checkValidity());

                if (invalidField) {
                    const invalidStep = invalidField.closest('[data-product-step]')?.dataset.productStep;
                    setProductStep(Number(invalidStep) || 1);
                    invalidField.reportValidity();
                }
            }
        });

        productCategorySelect?.addEventListener('change', () => {
            updateSubcategories(true);
            updateProductCodeSuggestion();
        });
        productSubcategorySelect?.addEventListener('change', () => {
            updateApplicableSizes();
        });
        updateSubcategories();
        if (!productIsEditing) {
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
            .filter((card) => !card.classList.contains('hidden'));

        const updateMediaOrderField = () => {
            const order = mediaCards().map((card) => card.dataset.mediaToken);
            let field = document.getElementById('product-media-order');

            if (!field) {
                field = document.createElement('input');
                field.type = 'hidden';
                field.id = 'product-media-order';
                field.name = 'image_order';
                document.getElementById('product-form')?.append(field);
            }

            field.value = order.join(',');
        };

        const persistExistingOrder = async () => {
            if (!imageReorderUrl) {
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
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content ??
                            document.querySelector('[name="_token"]')?.value,
                    },
                    body: JSON.stringify({
                        images: imageIds
                    }),
                });

                if (!response.ok) {
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

                if (!draggedMediaCard || draggedMediaCard === card) {
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
                const isCover = Boolean(card.querySelector(
                    'input[name="cover_image_id"]:checked, input[name="cover_new_index"]:checked'));
                card.querySelector('[data-cover-badge]')?.classList.toggle('hidden', !isCover);
            });
        };

        const ensureCoverSelection = () => {
            const cards = mediaCards();
            const hasCover = cards.some((card) => card.querySelector(
                'input[name="cover_image_id"]:checked, input[name="cover_new_index"]:checked'));

            if (!hasCover && cards[0]) {
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
                const altInput = productGalleryList?.querySelector(
                    `[data-new-image-index="${index}"] input[name="new_image_alts[${index}]"]`);

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
                card.className =
                    'group overflow-hidden rounded-xl border border-[#e1e3dd] bg-white shadow-[0_4px_14px_rgba(35,42,37,0.04)] dark:border-[#4b4a45] dark:bg-[#292b29]';
                const imageFrame = document.createElement('div');
                imageFrame.className = 'relative cursor-grab active:cursor-grabbing';

                const preview = document.createElement('img');
                preview.src = image.preview;
                preview.alt = image.file.name;
                preview.className = 'h-36 w-full object-cover';
                imageFrame.append(preview);

                const newBadge = document.createElement('span');
                newBadge.className =
                    'absolute left-2 top-2 rounded-md bg-white/90 px-2 py-1 text-[10px] font-semibold text-[#68736c] shadow-sm';
                newBadge.textContent = 'Nueva';
                imageFrame.append(newBadge);

                const positionBadge = document.createElement('span');
                positionBadge.dataset.mediaPosition = '';
                positionBadge.className =
                    'absolute left-2 top-9 rounded-md bg-white/90 px-2 py-1 text-[10px] font-semibold text-[#68736c] shadow-sm';
                imageFrame.append(positionBadge);

                const coverBadge = document.createElement('span');
                coverBadge.dataset.coverBadge = '';
                coverBadge.className =
                    'absolute right-2 top-9 hidden rounded-md bg-[#eadfce] px-2 py-1 text-[10px] font-semibold uppercase tracking-[.1em] text-[#806441]';
                coverBadge.textContent = 'Portada';
                imageFrame.append(coverBadge);

                const dragBadge = document.createElement('span');
                dragBadge.className =
                    'absolute right-2 top-2 rounded-md bg-[#28302b]/75 px-2 py-1 text-[10px] font-medium text-white';
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
                    Object.entries(attributes).forEach(([attribute, value]) => button.setAttribute(
                        attribute, value));
                    return button;
                };
                actionButtons.append(createActionButton('↑', {
                        'data-move-image': 'up',
                        title: 'Mover imagen hacia arriba',
                        'aria-label': 'Mover imagen hacia arriba',
                    },
                    'aurea-focus rounded-md p-2 text-[#8a938d] hover:bg-[#f1f2ee] hover:text-aurea-focus'
                ));
                actionButtons.append(createActionButton('↓', {
                        'data-move-image': 'down',
                        title: 'Mover imagen hacia abajo',
                        'aria-label': 'Mover imagen hacia abajo',
                    },
                    'aurea-focus rounded-md p-2 text-[#8a938d] hover:bg-[#f1f2ee] hover:text-aurea-focus'
                ));
                actionButtons.append(createActionButton('Eliminar', {
                        'data-remove-new-image': index,
                    },
                    'aurea-focus rounded-md px-2.5 py-2 text-[11px] font-medium text-[#6f675d] hover:bg-[#f9e9e6] hover:text-aurea-danger'
                ));
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

                    return !newImages.some((image) => image.key === fileKey);
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
                productImageDropzoneTitle.textContent = isActive ?
                    'Suelta las imágenes aquí' :
                    'Arrastra tus imágenes aquí';
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
            if (!event.target.matches('input[name="cover_image_id"], input[name="cover_new_index"]')) {
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

                if (!card || !imageId) {
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

            if (!item || !icon || !labelElement) {
                return;
            }

            item.classList.toggle('text-[#a0a7a2]', !complete && !optional);
            icon.className = complete ?
                'flex h-4 w-4 items-center justify-center rounded-full bg-[#edf5ee] text-[#5b8668]' :
                'h-4 w-4 rounded-full border border-[#dfe2dc]';
            icon.textContent = complete ? '✓' : '';
            labelElement.textContent = label;
        };

        const updateSummary = () => {
            const value = (selector) => document.querySelector(selector)?.value.trim() ?? '';
            const hasGeneral = Boolean(value('[name="name"]') && value('[name="code"]') && value(
                '[name="category_id"]'));
            const price = Number(value('[name="price"]'));
            const hasDetails = value('[name="price"]') !== '' && Number.isFinite(price) && price >= 0;
            const hasStoneGroup = document.querySelector('[data-stones-choice]:checked')?.value === 'yes' &&
                Boolean(stoneGroupsList?.querySelector('[name$="[stone_id]"]')?.value);
            const imageCount = productGalleryList?.querySelectorAll('[data-existing-image]:not(.hidden)').length +
                newImages.length;

            updateSummaryItem('general', hasGeneral, 'Información general');
            updateSummaryItem('details', hasDetails, 'Precio y detalles');
            updateSummaryItem('stones', hasStoneGroup, hasStoneGroup ? 'Piedras configuradas' : 'Piedras opcionales', !
                hasStoneGroup);
            updateSummaryItem('images', imageCount > 0, imageCount > 0 ? 'Imágenes configuradas' :
                'Imágenes pendientes');
            updateProductReview();
        };

        document.querySelectorAll('#product-form input, #product-form select, #product-form textarea').forEach((field) => {
            field.addEventListener('input', updateSummary);
            field.addEventListener('change', updateSummary);
        });
        const firstErrorStep = Array.from(document.querySelectorAll('.text-red-600'))
            .map((error) => error.closest('[data-product-step]')?.dataset.productStep)
            .find(Boolean);
        updateGalleryState();
        updateSummary();
        setProductStep(Number(firstErrorStep) || 1);
    </script>
@endPushOnce
