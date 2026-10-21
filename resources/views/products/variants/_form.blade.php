@php
    $variantMaterials = collect(config('aurea-demo.materials', []))->pluck('name');
    $variantKarats = config('aurea-demo.product_form_options.karats', []);
    $variantColors = config('aurea-demo.product_form_options.metal_colors', []);
    $variantStones = collect(config('aurea-demo.stones', []))->pluck('name');
    $variantSizes = collect(config('aurea-demo.sizes', []))
        ->filter(function ($sizeOption) use ($product) {
            return in_array($product['category_id'] ?? null, $sizeOption['category_ids'] ?? [], true);
        })
        ->sortBy('order')
        ->values();
    $variantStatuses = ['Activo', 'Borrador', 'Agotado'];
    $baseProduct = config('aurea-demo.product_form.products.' . $product['base_sku'], []);
    $basePrice = '$' . number_format((float) ($baseProduct['price'] ?? 0), 2) . ' MXN';
    $baseSalePrice = filled($baseProduct['sale_price'] ?? null)
        ? '$' . number_format((float) $baseProduct['sale_price'], 2) . ' MXN'
        : 'sin precio de oferta';
@endphp

<form>
    <x-admin.form-section
        number="01"
        title="Datos de la variante"
        description="Define la versión comercial y los atributos que pueden cambiar respecto al producto."
    >
        <div class="grid gap-5 sm:grid-cols-2">
            <label>
                <span class="admin-form-label admin-form-label-required">SKU</span>
                <input
                    type="text"
                    name="sku"
                    value="{{ $variant['sku'] }}"
                    placeholder="Ej. AU-1048-T7"
                    class="admin-form-input"
                    required
                />
            </label>
            <label>
                <span class="admin-form-label">Talla</span>
                <select name="size" class="admin-form-input">
                    <option value="">Seleccionar talla</option>
                    @foreach ($variantSizes as $variantSize)
                        <option value="{{ $variantSize['name'] }}" @selected($variant['size'] === $variantSize['name'])>{{ $variantSize['name'] }}</option>
                    @endforeach
                </select>
                <span class="admin-form-help">Solo se muestran tallas aplicables a la categoría del producto.</span>
            </label>
            <label>
                <span class="admin-form-label">Material</span>
                <select name="material" class="admin-form-input">
                    <option value="">Usar material del producto</option>
                    @foreach ($variantMaterials as $variantMaterial)
                        <option @selected($variant['material'] === $variantMaterial)>{{ $variantMaterial }}</option>
                    @endforeach
                </select>
                <span class="admin-form-help">Completa este campo solo si cambia respecto al producto.</span>
            </label>
            <label>
                <span class="admin-form-label">Kilataje del material</span>
                <select name="karat" class="admin-form-input">
                    <option value="">Usar kilataje del producto</option>
                    @foreach ($variantKarats as $variantKarat)
                        <option @selected($variant['karat'] === $variantKarat)>{{ $variantKarat }}</option>
                    @endforeach
                </select>
                <span class="admin-form-help">Completa este campo solo si cambia respecto al producto.</span>
            </label>
            <label>
                <span class="admin-form-label">Color del oro</span>
                <select name="metal_color" class="admin-form-input">
                    <option value="">Usar color del producto</option>
                    @foreach ($variantColors as $variantColor)
                        <option @selected(($variant['metal_color'] ?? '') === $variantColor)>{{ $variantColor }}</option>
                    @endforeach
                </select>
                <span class="admin-form-help">Completa este campo solo si cambia respecto al producto.</span>
            </label>
            <label>
                <span class="admin-form-label admin-form-label-required">Tipo de piedra</span>
                <select name="stone" class="admin-form-input" required>
                    <option value="">Seleccionar piedra</option>
                    @foreach ($variantStones as $variantStone)
                        <option @selected(($variant['stone'] ?? '') === $variantStone)>{{ $variantStone }}</option>
                    @endforeach
                </select>
                <span class="admin-form-help">La piedra puede cambiar el precio de esta variante.</span>
            </label>
        </div>
    </x-admin.form-section>

    <div class="mt-7">
        <x-admin.form-section
            number="02"
            title="Precio y estado"
            description="Configura el valor comercial y la disponibilidad de esta versión."
        >
            <div class="grid gap-5 sm:grid-cols-3">
                <label>
                    <span class="admin-form-label">Precio</span>
                    <div class="admin-form-input-prefix">
                        <span>$</span>
                        <input type="number" name="price" value="{{ in_array($variant['price'], ['', '—'], true) ? '' : str_replace(['$', ','], '', $variant['price']) }}" min="0" step="0.01" inputmode="decimal" placeholder="Usar precio base" aria-label="Precio de variante" />
                    </div>
                    <span class="admin-form-help">Si lo dejas vacío, se usará el precio base de {{ $basePrice }}.</span>
                </label>
                <label>
                    <span class="admin-form-label">Precio de oferta</span>
                    <div class="admin-form-input-prefix">
                        <span>$</span>
                        <input type="number" name="sale_price" value="{{ in_array($variant['sale_price'], ['', '—'], true) ? '' : str_replace(['$', ','], '', $variant['sale_price']) }}" min="0" step="0.01" inputmode="decimal" placeholder="Usar oferta del producto" aria-label="Precio de oferta de variante" />
                    </div>
                    <span class="admin-form-help">Si lo dejas vacío, se usará la oferta del producto de {{ $baseSalePrice }}.</span>
                </label>
                <label>
                    <span class="admin-form-label admin-form-label-required">Estado</span>
                    <select name="status" class="admin-form-input" required>
                        @foreach ($variantStatuses as $variantStatus)
                            <option @selected($variant['status'] === $variantStatus)>{{ $variantStatus }}</option>
                        @endforeach
                    </select>
                </label>
            </div>

            <div class="mt-6 flex flex-wrap justify-end gap-2 border-t border-[#eef0ec] pt-5">
                <a
                    href="{{ route('products.variants.index', ['id' => $product['base_sku']]) }}"
                    class="aurea-focus inline-flex items-center justify-center rounded-lg border border-[#dfe2dc] px-4 py-2.5 text-sm font-medium text-[#68736c] hover:border-aurea-gold hover:text-aurea-focus"
                >Cancelar</a>
                <button
                    type="button"
                    class="aurea-focus inline-flex items-center justify-center rounded-lg bg-aurea-gold px-4 py-2.5 text-sm font-medium text-white shadow-sm hover:bg-[#9f793f]"
                >{{ $isEditing ? 'Guardar cambios' : 'Guardar variante' }}</button>
            </div>
        </x-admin.form-section>
    </div>
</form>
