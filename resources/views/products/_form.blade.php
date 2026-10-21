@php
    $productCategories = collect(config('aurea-demo.categories', []));
    $mainProductCategories = $productCategories->whereNull('parent_id');
    $productSubcategories = $productCategories->whereNotNull('parent_id');
    $productCollections = collect(config('aurea-demo.collections'));
    $productMaterials = collect(config('aurea-demo.materials'))->pluck('name');
    $productStones = collect(config('aurea-demo.stones'))->pluck('name')->prepend('Sin piedra');
    $productSizes = collect(config('aurea-demo.sizes'))->pluck('name')->unique();
    $productOptions = config('aurea-demo.product_form_options', []);
    $stoneCaratOptions = $productOptions['stone_carats'] ?? [];
    $stoneShapeOptions = $productOptions['stone_shapes'] ?? [];
    $stoneColorOptions = $productOptions['stone_colors'] ?? [];
    $stoneClarityOptions = $productOptions['stone_clarities'] ?? [];
    $stoneCutOptions = $productOptions['stone_cuts'] ?? [];
    $centralStone = $product['central_stone'] ?? [
        'type' => $product['stone'] ?? 'Sin piedra',
        'shape' => '',
        'carats' => $product['stone_carats'] ?? '',
        'origin' => '',
        'certification' => '',
        'color' => '',
        'clarity' => '',
        'cut' => '',
    ];
    $sideStones = $product['side_stones'] ?? [
        'type' => '',
        'shape' => '',
        'carats' => '',
        'quantity' => '',
        'origin' => '',
        'certification' => '',
        'color' => '',
        'clarity' => '',
        'cut' => '',
    ];
    $hasSideStones = $product['has_side_stones'] ?? false;
    $productMedia = collect($product['gallery'] ?? []);

    if ($productMedia->isEmpty() && $isEditing && filled($product['image'] ?? null)) {
        $productMedia = collect([
            [
                'id' => 'current-cover',
                'path' => $product['image'],
                'alt' => 'Imagen principal de ' . $product['name'],
                'is_cover' => true,
            ],
        ]);
    }

    $coverMedia = $productMedia->firstWhere('is_cover', true) ?? $productMedia->first();

    if ($coverMedia) {
        $productMedia = collect([$coverMedia])
            ->merge($productMedia->reject(fn ($media) => ($media['id'] ?? null) === ($coverMedia['id'] ?? null)))
            ->values();
    }
@endphp

<form class="grid items-start gap-7 xl:grid-cols-[minmax(0,1fr)_19rem]">
    <div class="space-y-7">
        <x-admin.form-section
            number="01"
            title="Información general"
            description="Define la identidad comercial y la clasificación de la pieza."
            id="general-title"
        >
            <div class="grid gap-5 sm:grid-cols-2">
                <label>
                    <span class="admin-form-label admin-form-label-required">Nombre</span>
                    <input
                        type="text"
                        name="name"
                        value="{{ $product['name'] }}"
                        placeholder="Ej. Anillo Aura"
                        class="admin-form-input"
                        required
                    />
                </label>
                <label>
                    <span class="admin-form-label admin-form-label-required">Modelo</span>
                    <input
                        type="text"
                        name="model"
                        value="{{ $product['model'] }}"
                        placeholder="Ej. Solitario Aura"
                        class="admin-form-input"
                        required
                    />
                </label>
                <label>
                    <span class="admin-form-label">Subtítulo</span>
                    <input
                        type="text"
                        name="subtitle"
                        value="{{ $product['subtitle'] ?? '' }}"
                        placeholder="Ej. Elegancia clásica con brillo contemporáneo"
                        class="admin-form-input"
                    />
                </label>
                <label class="sm:col-span-2">
                    <span class="admin-form-label">Descripción corta</span>
                    <textarea
                        id="product-short-description"
                        name="short_description"
                        rows="3"
                        placeholder="Describe brevemente la pieza y su carácter."
                        class="admin-form-input aurea-focus"
                    >{{ $product['short_description'] ?? '' }}</textarea>
                </label>
                <label>
                    <span class="admin-form-label admin-form-label-required">Código</span>
                    <input
                        type="text"
                        name="code"
                        value="{{ $product['code'] }}"
                        placeholder="Ej. ANA-001"
                        class="admin-form-input"
                        required
                    />
                    <span class="admin-form-help">Referencia interna del producto.</span>
                </label>
                <label>
                    <span class="admin-form-label">SKU base</span>
                    <input
                        type="text"
                        name="base_sku"
                        value="{{ $product['base_sku'] }}"
                        placeholder="Ej. AU-1048"
                        class="admin-form-input"
                    />
                    <span class="admin-form-help">Identificador comercial del producto. Las variantes pueden tener su propio SKU.</span>
                </label>
                <label>
                    <span class="admin-form-label">Slug</span>
                    <input
                        type="text"
                        name="slug"
                        value="{{ $product['slug'] }}"
                        placeholder="anillo-aura"
                        class="admin-form-input"
                    />
                </label>
                <label>
                    <span class="admin-form-label admin-form-label-required">Categoría</span>
                    <select id="product-category" name="category_id" class="admin-form-input" required>
                        <option value="">Selecciona una categoría</option>
                        @foreach ($mainProductCategories as $productCategory)
                            <option value="{{ $productCategory['id'] }}" @selected($product['category_id'] === $productCategory['id'])>{{ $productCategory['name'] }}</option>
                        @endforeach
                    </select>
                </label>
                <div id="product-subcategory-field" class="hidden">
                    <label>
                        <span class="admin-form-label admin-form-label-required">Subcategoría</span>
                        <select id="product-subcategory" name="subcategory_id" class="admin-form-input">
                            <option value="">Selecciona una subcategoría</option>
                            @foreach ($productSubcategories as $productSubcategory)
                                <option
                                    value="{{ $productSubcategory['id'] }}"
                                    data-parent-id="{{ $productSubcategory['parent_id'] }}"
                                    @selected(($product['subcategory_id'] ?? '') === $productSubcategory['id'])
                                >{{ $productSubcategory['name'] }}</option>
                            @endforeach
                        </select>
                    </label>
                </div>
                <label>
                    <span class="admin-form-label">Colección</span>
                    <select name="collection" class="admin-form-input">
                        <option value="">Selecciona una colección</option>
                        @foreach ($productCollections as $productCollection)
                            <option @selected($product['collection'] === $productCollection['name'])>{{ $productCollection['name'] }}</option>
                        @endforeach
                    </select>
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
                        <input type="number" name="price" value="{{ $product['price'] }}" min="0" step="0.01" inputmode="decimal" aria-label="Precio base" required />
                    </div>
                </label>
                <label>
                    <span class="admin-form-label">Precio de oferta</span>
                    <div class="admin-form-input-prefix">
                        <span>$</span>
                        <input type="number" name="sale_price" value="{{ $product['sale_price'] }}" min="0" step="0.01" inputmode="decimal" placeholder="Opcional" aria-label="Precio de oferta" />
                    </div>
                </label>
                <label>
                    <span class="admin-form-label">Costo</span>
                    <div class="admin-form-input-prefix">
                        <span>$</span>
                        <input type="number" name="cost" value="{{ $product['cost'] }}" min="0" step="0.01" inputmode="decimal" aria-label="Costo" />
                    </div>
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
                    <span class="admin-form-label admin-form-label-required">Material</span>
                    <select name="material" class="admin-form-input" required>
                        @foreach ($productMaterials as $productMaterial)
                            <option @selected($product['material'] === $productMaterial)>{{ $productMaterial }}</option>
                        @endforeach
                    </select>
                </label>
                <label>
                    <span class="admin-form-label">Kilataje del material</span>
                        <select name="karat" class="admin-form-input">
                        @foreach ($productOptions['karats'] as $productKarat)
                            <option @selected($product['karat'] === $productKarat)>{{ $productKarat }}</option>
                        @endforeach
                        </select>
                </label>
                <label>
                    <span class="admin-form-label">Color del oro</span>
                    <select name="metal_color" class="admin-form-input">
                        @foreach ($productOptions['metal_colors'] as $productMetalColor)
                            <option @selected(($product['metal_color'] ?? 'Amarillo') === $productMetalColor)>{{ $productMetalColor }}</option>
                        @endforeach
                    </select>
                </label>
                <label>
                    <span class="admin-form-label">Talla base o referencia</span>
                    <select name="size_reference" class="admin-form-input">
                        @foreach ($productSizes as $productSize)
                            <option @selected($product['size_reference'] === $productSize)>{{ $productSize }}</option>
                        @endforeach
                    </select>
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
                    <h4 class="text-sm font-medium text-[#3d4640]">Piedra central</h4>
                    <p class="mt-1 text-xs text-[#8a938d]">Información principal de la piedra que acompaña al producto.</p>
                    <div class="mt-5 grid gap-5 sm:grid-cols-2 lg:grid-cols-3">
                        <label>
                            <span class="admin-form-label">Tipo de piedra</span>
                            <select name="central_stone[type]" class="admin-form-input">
                                @foreach ($productStones as $productStone)
                                    <option @selected(($centralStone['type'] ?? '') === $productStone)>{{ $productStone }}</option>
                                @endforeach
                            </select>
                        </label>
                        <label>
                            <span class="admin-form-label">Forma de la piedra</span>
                            <select name="central_stone[shape]" class="admin-form-input">
                                <option value="">Selecciona la forma</option>
                                @foreach ($stoneShapeOptions as $stoneShape)
                                    <option @selected(($centralStone['shape'] ?? '') === $stoneShape)>{{ $stoneShape }}</option>
                                @endforeach
                            </select>
                        </label>
                        <label>
                            <span class="admin-form-label">Quilates</span>
                            <select name="central_stone[carats]" class="admin-form-input">
                                <option value="">Selecciona los quilates</option>
                                @foreach ($stoneCaratOptions as $stoneCarat)
                                    <option @selected(($centralStone['carats'] ?? '') === $stoneCarat)>{{ $stoneCarat }}</option>
                                @endforeach
                            </select>
                        </label>
                        <label>
                            <span class="admin-form-label">Origen</span>
                            <input type="text" name="central_stone[origin]" value="{{ $centralStone['origin'] ?? '' }}" placeholder="Ej. Laboratorio" class="admin-form-input" />
                        </label>
                        <label>
                            <span class="admin-form-label">Certificación</span>
                            <input type="text" name="central_stone[certification]" value="{{ $centralStone['certification'] ?? '' }}" placeholder="Ej. IGI" class="admin-form-input" />
                        </label>
                        <label>
                            <span class="admin-form-label">Color</span>
                            <select name="central_stone[color]" class="admin-form-input">
                                <option value="">Selecciona el color</option>
                                @foreach ($stoneColorOptions as $stoneColor)
                                    <option @selected(($centralStone['color'] ?? '') === $stoneColor)>{{ $stoneColor }}</option>
                                @endforeach
                            </select>
                        </label>
                        <label>
                            <span class="admin-form-label">Claridad</span>
                            <select name="central_stone[clarity]" class="admin-form-input">
                                <option value="">Selecciona la claridad</option>
                                @foreach ($stoneClarityOptions as $stoneClarity)
                                    <option @selected(($centralStone['clarity'] ?? '') === $stoneClarity)>{{ $stoneClarity }}</option>
                                @endforeach
                            </select>
                        </label>
                        <label>
                            <span class="admin-form-label">Corte</span>
                            <select name="central_stone[cut]" class="admin-form-input">
                                <option value="">Selecciona el corte</option>
                                @foreach ($stoneCutOptions as $stoneCut)
                                    <option @selected(($centralStone['cut'] ?? '') === $stoneCut)>{{ $stoneCut }}</option>
                                @endforeach
                            </select>
                        </label>
                    </div>
                </div>

                <div class="border-t border-[#eef0ec] pt-6">
                    <x-admin.toggle
                        id="product-has-side-stones"
                        name="has_side_stones"
                        label="Este producto tiene piedras laterales"
                        description="Activa esta opción para registrar las piedras que acompañan a la piedra central."
                        :checked="$hasSideStones"
                    />
                    <div id="side-stones-fields" class="{{ $hasSideStones ? '' : 'hidden' }} mt-5" @if (! $hasSideStones) aria-hidden="true" @endif>
                        <div class="grid gap-5 sm:grid-cols-2 lg:grid-cols-3">
                            <label>
                                <span class="admin-form-label">Tipo de piedra</span>
                                <select name="side_stones[type]" class="admin-form-input">
                                    <option value="">Selecciona una piedra</option>
                                        @foreach ($productStones as $productStone)
                                            <option @selected(($sideStones['type'] ?? '') === $productStone)>{{ $productStone }}</option>
                                        @endforeach
                                    </select>
                                </label>
                            <label>
                                <span class="admin-form-label">Forma de la piedra</span>
                                <select name="side_stones[shape]" class="admin-form-input">
                                    <option value="">Selecciona la forma</option>
                                    @foreach ($stoneShapeOptions as $stoneShape)
                                        <option @selected(($sideStones['shape'] ?? '') === $stoneShape)>{{ $stoneShape }}</option>
                                    @endforeach
                                </select>
                            </label>
                            <label>
                                <span class="admin-form-label">Quilates por piedra</span>
                                <select name="side_stones[carats]" class="admin-form-input">
                                    <option value="">Selecciona los quilates</option>
                                    @foreach ($stoneCaratOptions as $stoneCarat)
                                        <option @selected(($sideStones['carats'] ?? '') === $stoneCarat)>{{ $stoneCarat }}</option>
                                    @endforeach
                                </select>
                            </label>
                            <label>
                                <span class="admin-form-label">Cantidad</span>
                                <input type="number" name="side_stones[quantity]" value="{{ $sideStones['quantity'] ?? '' }}" min="1" step="1" placeholder="Ej. 2" class="admin-form-input" />
                            </label>
                            <label>
                                <span class="admin-form-label">Origen</span>
                                <input type="text" name="side_stones[origin]" value="{{ $sideStones['origin'] ?? '' }}" placeholder="Ej. Laboratorio" class="admin-form-input" />
                            </label>
                            <label>
                                <span class="admin-form-label">Certificación</span>
                                <input type="text" name="side_stones[certification]" value="{{ $sideStones['certification'] ?? '' }}" placeholder="Ej. NA por tamaño" class="admin-form-input" />
                            </label>
                            <label>
                                <span class="admin-form-label">Color</span>
                                <select name="side_stones[color]" class="admin-form-input">
                                    <option value="">Selecciona el color</option>
                                    @foreach ($stoneColorOptions as $stoneColor)
                                        <option @selected(($sideStones['color'] ?? '') === $stoneColor)>{{ $stoneColor }}</option>
                                    @endforeach
                                </select>
                            </label>
                            <label>
                                <span class="admin-form-label">Claridad</span>
                                <select name="side_stones[clarity]" class="admin-form-input">
                                    <option value="">Selecciona la claridad</option>
                                    @foreach ($stoneClarityOptions as $stoneClarity)
                                        <option @selected(($sideStones['clarity'] ?? '') === $stoneClarity)>{{ $stoneClarity }}</option>
                                    @endforeach
                                </select>
                            </label>
                            <label>
                                <span class="admin-form-label">Corte</span>
                                <select name="side_stones[cut]" class="admin-form-input">
                                    <option value="">Selecciona el corte</option>
                                    @foreach ($stoneCutOptions as $stoneCut)
                                        <option @selected(($sideStones['cut'] ?? '') === $stoneCut)>{{ $stoneCut }}</option>
                                    @endforeach
                                </select>
                            </label>
                        </div>
                    </div>
                </div>
            </div>
        </x-admin.form-section>

        <x-admin.form-section
            number="05"
            title="Multimedia"
            description="Añade los recursos visuales del producto."
            id="media-title"
        >
            <div id="product-media-manager" class="space-y-6">
                <input id="product-media-order" type="hidden" name="media_order" value="" />

                <label
                    id="product-image-dropzone"
                    for="product-image-upload"
                    class="flex min-h-52 cursor-pointer flex-col items-center justify-center rounded-xl border border-dashed border-[#d8d9d2] bg-[#fbfbf9] px-5 text-center transition hover:border-aurea-gold hover:bg-[#fbf8f3] focus-within:border-aurea-gold focus-within:ring-2 focus-within:ring-aurea-gold-soft/20"
                >
                    <svg class="h-8 w-8 text-[#b58d55]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.4" aria-hidden="true">
                        <path d="M4 5h16v14H4zM7 15l3-3 2 2 2-2 3 3M8 9h.01M12 4v7m-3-3 3-3 3 3" />
                    </svg>
                    <span class="mt-3 text-sm font-medium text-[#59635c]">Arrastra tus imágenes aquí</span>
                    <span class="mt-1 text-xs text-[#8a938d]">o haz clic para seleccionarlas</span>
                    <span class="mt-3 text-[11px] text-[#9da59f]">PNG, JPG o WEBP · Máximo 5 MB por imagen</span>
                    <input id="product-image-upload" name="images[]" type="file" accept="image/png,image/jpeg,image/webp" multiple class="sr-only" aria-describedby="product-image-upload-help" />
                    <span id="product-image-upload-help" class="sr-only">Selecciona una o varias imágenes del producto.</span>
                </label>

                <div id="product-gallery-wrapper" class="{{ $productMedia->isEmpty() ? 'hidden' : '' }} space-y-3">
                    <div class="flex flex-wrap items-end justify-between gap-2">
                        <div>
                            <p class="admin-form-label">Imágenes del producto</p>
                            <p class="mt-1 text-xs text-[#8a938d]">Arrastra las miniaturas para definir el orden de la ficha.</p>
                        </div>
                        <span id="product-gallery-count" class="text-xs text-[#929b94]">{{ count($productMedia) }} imágenes</span>
                    </div>
                    <div id="product-gallery-list" class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3" aria-live="polite">
                        @foreach ($productMedia as $index => $media)
                            <article
                                class="product-media-card group overflow-hidden rounded-xl border border-[#e1e3dd] bg-white shadow-[0_4px_14px_rgba(35,42,37,0.04)]"
                                draggable="true"
                                tabindex="0"
                                data-media-id="{{ $media['id'] }}"
                                data-media-path="{{ $media['path'] }}"
                                data-is-cover="{{ $index === 0 ? 'true' : 'false' }}"
                            >
                                <div class="relative cursor-grab active:cursor-grabbing">
                                    <img src="{{ asset($media['path']) }}" alt="{{ $media['alt'] ?? 'Imagen del producto' }}" class="h-36 w-full object-cover" />
                                    <span class="product-media-position absolute left-2 top-2 rounded-md bg-white/90 px-2 py-1 text-[10px] font-semibold text-[#68736c] shadow-sm">Posición {{ $index + 1 }}</span>
                                    <span class="media-cover-badge absolute right-2 top-2 rounded-md bg-[#eadfce] px-2 py-1 text-[10px] font-semibold uppercase tracking-[.1em] text-[#806441] {{ $index === 0 ? '' : 'hidden' }}">Portada</span>
                                    <span class="absolute right-2 top-9 rounded-md bg-[#28302b]/75 px-2 py-1 text-[10px] font-medium text-white opacity-0 transition group-hover:opacity-100 group-focus-within:opacity-100">Arrastrar</span>
                                </div>
                                <div class="space-y-3 p-3">
                                    <p class="truncate text-xs font-medium text-[#59635c]">{{ $media['alt'] ?? 'Imagen del producto' }}</p>
                                    <div class="flex items-center justify-between gap-2">
                                        <button
                                            type="button"
                                            class="use-as-cover aurea-focus rounded-md border border-[#dfe2dc] px-2.5 py-2 text-[11px] font-medium text-[#68736c] hover:border-aurea-gold hover:text-aurea-focus {{ $index === 0 ? 'hidden' : '' }}"
                                            aria-label="Usar {{ $media['alt'] ?? 'esta imagen' }} como portada"
                                        >Usar como portada</button>
                                        <span class="current-cover-label text-[11px] font-medium text-[#806441] {{ $index === 0 ? '' : 'hidden' }}">Portada actual</span>
                                        <div class="ml-auto flex items-center gap-1">
                                            <button type="button" title="Mover imagen hacia arriba" class="move-media-up aurea-focus rounded-md p-2 text-[#8a938d] hover:bg-[#f1f2ee] hover:text-aurea-focus" aria-label="Mover imagen hacia arriba">
                                                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" aria-hidden="true"><path d="m6 14 6-6 6 6" /></svg>
                                            </button>
                                            <button type="button" title="Mover imagen hacia abajo" class="move-media-down aurea-focus rounded-md p-2 text-[#8a938d] hover:bg-[#f1f2ee] hover:text-aurea-focus" aria-label="Mover imagen hacia abajo">
                                                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" aria-hidden="true"><path d="m6 10 6 6 6-6" /></svg>
                                            </button>
                                            <button type="button" class="remove-media aurea-focus rounded-md p-2 text-[#6f675d] hover:bg-[#f9e9e6] hover:text-aurea-danger" aria-label="Eliminar {{ $media['alt'] ?? 'esta imagen' }}" title="Eliminar imagen">
                                                <x-admin.icon name="trash" class="h-[18px] w-[18px]" />
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </article>
                        @endforeach
                    </div>
                </div>
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
                        @foreach (['Activo', 'Borrador', 'Agotado'] as $productStatus)
                            <option @selected($product['status'] === $productStatus)>{{ $productStatus }}</option>
                        @endforeach
                    </select>
                </label>
                <div class="space-y-3">
                    <x-admin.toggle id="product-featured" name="featured" label="Producto destacado" description="Mostrar en espacios destacados." :checked="$product['featured']" />
                    <x-admin.toggle id="product-new" name="new" label="Producto nuevo" description="Marcar como novedad." :checked="$product['new']" />
                    <x-admin.toggle id="product-bestseller" name="bestseller" label="Más vendido" description="Incluir en la selección de favoritos." :checked="$product['bestseller']" />
                </div>
            </div>
        </x-admin.form-section>

        <section class="rounded-2xl border border-aurea-border bg-aurea-surface p-5 shadow-[0_5px_20px_rgba(35,42,37,0.03)]" aria-labelledby="preview-title">
            <div class="flex items-center justify-between gap-3">
                <div>
                    <h3 id="preview-title" class="font-serif text-xl text-[#28302b]">Resumen</h3>
                    <p class="mt-1 text-xs text-[#8a938d]">{{ $isEditing ? 'Producto existente' : 'Antes de publicar' }}</p>
                </div>
                <span class="text-sm font-semibold text-[#a67b3e]">65%</span>
            </div>
            <div class="mt-4 h-1.5 overflow-hidden rounded-full bg-[#edf0eb]"><div class="h-full w-[65%] rounded-full bg-aurea-gold"></div></div>
            <ul class="mt-5 space-y-3 text-xs text-[#68736c]">
                <li class="flex items-center gap-2"><span class="flex h-4 w-4 items-center justify-center rounded-full bg-[#edf5ee] text-[#5b8668]">✓</span>Información general</li>
                <li class="flex items-center gap-2"><span class="flex h-4 w-4 items-center justify-center rounded-full bg-[#edf5ee] text-[#5b8668]">✓</span>Precio y detalles</li>
                @if ($coverMedia)
                    <li class="flex items-center gap-2"><span class="flex h-4 w-4 items-center justify-center rounded-full bg-[#edf5ee] text-[#5b8668]">✓</span>Portada configurada</li>
                @else
                    <li class="flex items-center gap-2 text-[#a0a7a2]"><span class="h-4 w-4 rounded-full border border-[#dfe2dc]"></span>Portada pendiente</li>
                @endif
            </ul>
        </section>
    </aside>
</form>

@push('scripts')
    <script>
        const productCategorySelect = document.getElementById('product-category');
        const productSubcategoryField = document.getElementById('product-subcategory-field');
        const productSubcategorySelect = document.getElementById('product-subcategory');

        const updateProductSubcategories = () => {
            const selectedCategoryId = productCategorySelect.value;
            const subcategoryOptions = Array.from(productSubcategorySelect.options).slice(1);
            const matchingOptions = subcategoryOptions.filter((option) => option.dataset.parentId === selectedCategoryId);

            subcategoryOptions.forEach((option) => {
                const matchesCategory = option.dataset.parentId === selectedCategoryId;

                option.hidden = !matchesCategory;
                option.disabled = !matchesCategory;
            });

            const hasSubcategories = matchingOptions.length > 0;
            const selectedOption = subcategoryOptions.find((option) => option.value === productSubcategorySelect.value);
            const selectedOptionMatches = selectedOption?.dataset.parentId === selectedCategoryId;

            productSubcategoryField.classList.toggle('hidden', !hasSubcategories);
            productSubcategoryField.toggleAttribute('aria-hidden', !hasSubcategories);
            productSubcategorySelect.required = hasSubcategories;

            if (!selectedOptionMatches) {
                productSubcategorySelect.value = '';
            }
        };

        productCategorySelect.addEventListener('change', () => {
            productSubcategorySelect.value = '';
            updateProductSubcategories();
        });
        updateProductSubcategories();

        const sideStonesToggle = document.getElementById('product-has-side-stones');
        const sideStonesFields = document.getElementById('side-stones-fields');

        sideStonesToggle?.addEventListener('change', () => {
            const hasSideStones = sideStonesToggle.checked;

            sideStonesFields?.classList.toggle('hidden', !hasSideStones);
            sideStonesFields?.toggleAttribute('aria-hidden', !hasSideStones);
        });

        const imageDropzone = document.getElementById('product-image-dropzone');
        const imageUpload = document.getElementById('product-image-upload');
        const galleryWrapper = document.getElementById('product-gallery-wrapper');
        const galleryList = document.getElementById('product-gallery-list');
        const galleryCount = document.getElementById('product-gallery-count');
        const mediaOrder = document.getElementById('product-media-order');
        let draggedMediaCard = null;

        const mediaCards = () => Array.from(galleryList?.children ?? []);

        const ensureCoverFirst = () => {
            const cards = mediaCards();
            const cover = cards.find((card) => card.dataset.isCover === 'true');

            if (cover && cards[0] !== cover) {
                galleryList.prepend(cover);
            }
        };

        const updateMediaPositions = () => {
            ensureCoverFirst();
            const cards = mediaCards();

            cards.forEach((card, index) => {
                const position = card.querySelector('.product-media-position');
                const moveUpButton = card.querySelector('.move-media-up');
                const moveDownButton = card.querySelector('.move-media-down');
                const coverBadge = card.querySelector('.media-cover-badge');
                const useAsCoverButton = card.querySelector('.use-as-cover');
                const currentCoverLabel = card.querySelector('.current-cover-label');
                const isCover = card.dataset.isCover === 'true';

                if (position) {
                    position.textContent = `Posición ${index + 1}`;
                }

                moveUpButton?.toggleAttribute('disabled', index === 0);
                moveDownButton?.toggleAttribute('disabled', index === cards.length - 1);
                moveUpButton?.classList.toggle('opacity-40', index === 0);
                moveDownButton?.classList.toggle('opacity-40', index === cards.length - 1);
                coverBadge?.classList.toggle('hidden', !isCover);
                useAsCoverButton?.classList.toggle('hidden', isCover);
                currentCoverLabel?.classList.toggle('hidden', !isCover);
            });

            if (galleryCount) {
                galleryCount.textContent = `${cards.length} ${cards.length === 1 ? 'imagen' : 'imágenes'}`;
            }

            if (galleryWrapper) {
                galleryWrapper.classList.toggle('hidden', cards.length === 0);
            }

            if (mediaOrder) {
                mediaOrder.value = cards.map((card) => card.dataset.mediaId).join(',');
            }
        };

        const makeCover = (card) => {
            if (!card) {
                return;
            }

            mediaCards().forEach((galleryCard) => {
                galleryCard.dataset.isCover = galleryCard === card ? 'true' : 'false';
            });
            galleryList.prepend(card);
            updateMediaPositions();
        };

        const moveMediaCard = (card, direction) => {
            const cards = mediaCards();
            const currentIndex = cards.indexOf(card);

            if (currentIndex < 0) {
                return;
            }

            const nextIndex = currentIndex + direction;

            if (nextIndex < 0 || nextIndex >= cards.length) {
                return;
            }

            if (direction < 0) {
                galleryList.insertBefore(card, cards[nextIndex]);
            } else {
                galleryList.insertBefore(card, cards[nextIndex].nextSibling);
            }

            updateMediaPositions();
            card.focus();
        };

        const removeMediaCard = (card) => {
            const wasCover = card.dataset.isCover === 'true';

            card.remove();

            if (wasCover && galleryList.children.length > 0) {
                galleryList.firstElementChild.dataset.isCover = 'true';
            }

            updateMediaPositions();
        };

        const bindMediaCard = (card) => {
            card.addEventListener('dragstart', () => {
                draggedMediaCard = card;
                card.classList.add('opacity-50');
            });
            card.addEventListener('dragend', () => {
                draggedMediaCard = null;
                card.classList.remove('opacity-50');
                updateMediaPositions();
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

                if (draggedIndex < targetIndex) {
                    galleryList.insertBefore(draggedMediaCard, card.nextSibling);
                } else {
                    galleryList.insertBefore(draggedMediaCard, card);
                }

                updateMediaPositions();
            });
            card.querySelector('.use-as-cover')?.addEventListener('click', () => makeCover(card));
            card.querySelector('.move-media-up')?.addEventListener('click', () => moveMediaCard(card, -1));
            card.querySelector('.move-media-down')?.addEventListener('click', () => moveMediaCard(card, 1));
            card.querySelector('.remove-media')?.addEventListener('click', () => removeMediaCard(card));
        };

        const createMediaCard = (file) => {
            const card = document.createElement('article');
            const imageUrl = URL.createObjectURL(file);

            card.className = 'product-media-card group overflow-hidden rounded-xl border border-[#e1e3dd] bg-white shadow-[0_4px_14px_rgba(35,42,37,0.04)]';
            card.draggable = true;
            card.tabIndex = 0;
            card.dataset.mediaId = `upload-${Date.now()}-${Math.random().toString(36).slice(2)}`;
            card.dataset.mediaPath = imageUrl;
            card.dataset.isCover = 'false';
            card.innerHTML = `
                <div class="relative cursor-grab active:cursor-grabbing">
                    <img class="h-36 w-full object-cover" alt="" />
                    <span class="product-media-position absolute left-2 top-2 rounded-md bg-white/90 px-2 py-1 text-[10px] font-semibold text-[#68736c] shadow-sm"></span>
                    <span class="media-cover-badge absolute right-2 top-2 hidden rounded-md bg-[#eadfce] px-2 py-1 text-[10px] font-semibold uppercase tracking-[.1em] text-[#806441]">Portada</span>
                    <span class="absolute right-2 top-9 rounded-md bg-[#28302b]/75 px-2 py-1 text-[10px] font-medium text-white opacity-0 transition group-hover:opacity-100 group-focus-within:opacity-100">Arrastrar</span>
                </div>
                <div class="space-y-3 p-3">
                    <p class="media-name truncate text-xs font-medium text-[#59635c]"></p>
                    <div class="flex items-center justify-between gap-2">
                        <button type="button" class="use-as-cover aurea-focus rounded-md border border-[#dfe2dc] px-2.5 py-2 text-[11px] font-medium text-[#68736c] hover:border-aurea-gold hover:text-aurea-focus">Usar como portada</button>
                        <span class="current-cover-label hidden text-[11px] font-medium text-[#806441]">Portada actual</span>
                        <div class="ml-auto flex items-center gap-1">
                            <button type="button" title="Mover imagen hacia arriba" class="move-media-up aurea-focus rounded-md p-2 text-[#8a938d] hover:bg-[#f1f2ee] hover:text-aurea-focus" aria-label="Mover imagen hacia arriba"><svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" aria-hidden="true"><path d="m6 14 6-6 6 6" /></svg></button>
                            <button type="button" title="Mover imagen hacia abajo" class="move-media-down aurea-focus rounded-md p-2 text-[#8a938d] hover:bg-[#f1f2ee] hover:text-aurea-focus" aria-label="Mover imagen hacia abajo"><svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" aria-hidden="true"><path d="m6 10 6 6 6-6" /></svg></button>
                            <button type="button" class="remove-media aurea-focus rounded-md p-2 text-[#6f675d] hover:bg-[#f9e9e6] hover:text-aurea-danger" aria-label="Eliminar imagen" title="Eliminar imagen"><svg class="h-[18px] w-[18px]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M4 7h16" /><path d="M9 7V4h6v3" /><path d="M6 7l1 13h10l1-13" /><path d="M10 11v5M14 11v5" /></svg></button>
                        </div>
                    </div>
                </div>
            `;
            card.querySelector('img').src = imageUrl;
            card.querySelector('img').alt = file.name;
            card.querySelector('.media-name').textContent = file.name;
            bindMediaCard(card);

            return card;
        };

        const addMediaFiles = (files) => {
            const validFiles = Array.from(files).filter((file) => file.type.startsWith('image/') && file.size <= 5 * 1024 * 1024);

            validFiles.forEach((file) => galleryList.append(createMediaCard(file)));
            updateMediaPositions();

            if (mediaCards().length === validFiles.length && validFiles.length > 0) {
                makeCover(galleryList.firstElementChild);
            }
        };

        imageUpload?.addEventListener('change', (event) => addMediaFiles(event.target.files));
        imageDropzone?.addEventListener('dragover', (event) => {
            event.preventDefault();
            imageDropzone.classList.add('border-aurea-gold', 'bg-[#fbf8f3]');
        });
        imageDropzone?.addEventListener('dragleave', () => imageDropzone.classList.remove('border-aurea-gold', 'bg-[#fbf8f3]'));
        imageDropzone?.addEventListener('drop', (event) => {
            event.preventDefault();
            imageDropzone.classList.remove('border-aurea-gold', 'bg-[#fbf8f3]');
            addMediaFiles(event.dataTransfer.files);
        });

        galleryList?.querySelectorAll('.product-media-card').forEach(bindMediaCard);

        updateMediaPositions();
    </script>
@endpush
