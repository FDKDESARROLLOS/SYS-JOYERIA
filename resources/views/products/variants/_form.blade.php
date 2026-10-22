@php
    $isEditing = $isEditing ?? false;
    $variantStatuses = [
        'active' => 'Activo',
        'draft' => 'Borrador',
        'inactive' => 'Inactivo',
    ];
    $selectedSizeId = old('size_id', $variant->size_id);
    $selectedMaterialId = old('material_id', $variant->material_id);
    $selectedStoneId = old('stone_id', $variant->stone_id);
    $selectedKarat = old('karat', $variant->karat);
    $selectedMetalColor = old('metal_color', $variant->metal_color);
    $selectedStatus = old('status', $variant->status ?: 'active');
    $baseMaterialName = $productModel->material?->name;
    $baseSizeName = $productModel->baseSize?->name;
    $baseStoneIds = $baseStoneIds ?? collect();

    if ((string) $selectedSizeId === (string) $productModel->base_size_id) {
        $selectedSizeId = null;
    }

    if ((string) $selectedMaterialId === (string) $productModel->material_id) {
        $selectedMaterialId = null;
    }

    if ($selectedKarat !== null && (int) $selectedKarat === (int) $productModel->karat) {
        $selectedKarat = null;
    }

    if ($selectedMetalColor !== null && $selectedMetalColor === $productModel->metal_color) {
        $selectedMetalColor = null;
    }

    if ($selectedStoneId !== null && $baseStoneIds->contains((int) $selectedStoneId)) {
        $selectedStoneId = null;
    }

    $inheritedCost = $productModel->cost !== null ? '$'.number_format((float) $productModel->cost, 2). ' MXN' : 'sin valor base';
    $inheritedPrice = $productModel->price !== null ? '$'.number_format((float) $productModel->price, 2). ' MXN' : 'sin valor base';
    $inheritedSalePrice = $productModel->sale_price !== null ? '$'.number_format((float) $productModel->sale_price, 2). ' MXN' : 'sin valor base';
    $formAction = $isEditing
        ? route('products.variants.update', [$productModel, $variant])
        : route('products.variants.store', $productModel);
@endphp

<form id="variant-form" method="POST" action="{{ $formAction }}">
    @csrf
    @if ($isEditing)
        @method('PATCH')
    @endif

    @if ($errors->any())
        <div class="mb-6 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700" role="alert">
            <p class="font-medium">Revisa los datos de la variante.</p>
            <ul class="mt-1 list-disc pl-5">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <x-admin.form-section
        number="01"
        title="Datos de la variante"
        description="Define la versión comercial y los atributos que pueden cambiar respecto al producto."
    >
        <div class="mb-5 rounded-lg border border-[#ece8dd] bg-[#fcfaf5] px-4 py-3 text-sm text-[#68736c]">
            <strong class="font-medium text-[#343d37]">Herencia:</strong>
            un campo vacío hereda el valor del producto; un valor seleccionado lo sobrescribe.
        </div>
        <div class="grid gap-5 sm:grid-cols-2">
            <div>
                <span class="admin-form-label">SKU de inventario</span>
                <div class="admin-form-input flex items-center bg-[#fbfbf9] text-[#68736c]" aria-live="polite">
                    {{ $isEditing ? $variant->sku : 'Se generará automáticamente al guardar' }}
                </div>
                <p class="admin-form-help">Se genera en backend a partir del SKU base y los atributos de la variante.</p>
            </div>

            <label>
                <span class="admin-form-label">Talla</span>
                <select name="size_id" class="admin-form-input">
                    <option value="">
                        {{ $baseSizeName ? 'Usar talla de referencia ('.$baseSizeName.')' : 'Sin talla específica / Usar talla de referencia' }}
                    </option>
                    @foreach ($applicableSizes as $size)
                        <option value="{{ $size->id }}" @selected((string) $selectedSizeId === (string) $size->id)>
                            {{ $size->name }}
                        </option>
                    @endforeach
                </select>
                <span class="admin-form-help">Solo se muestran tallas aplicables a la categoría del producto.</span>
                @error('size_id') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
            </label>

            <label>
                <span class="admin-form-label">Material</span>
                <select name="material_id" class="admin-form-input">
                    <option value="">Usar material del producto{{ $baseMaterialName ? ' ('.$baseMaterialName.')' : '' }}</option>
                    @foreach ($materials as $material)
                        <option value="{{ $material->id }}" @selected((string) $selectedMaterialId === (string) $material->id)>
                            {{ $material->name }}
                        </option>
                    @endforeach
                </select>
                <span class="admin-form-help">Déjalo vacío para usar el material del producto.</span>
                @error('material_id') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
            </label>

            <label>
                <span class="admin-form-label">Kilataje del material</span>
                <select name="karat" class="admin-form-input">
                    <option value="">Usar kilataje del producto{{ $productModel->karat !== null ? ' ('.$productModel->karat.'K)' : '' }}</option>
                    @foreach ($karatOptions as $value => $label)
                        <option value="{{ $value }}" @selected((string) $selectedKarat === (string) $value)>{{ $label }}</option>
                    @endforeach
                </select>
                <span class="admin-form-help">Déjalo vacío para usar el kilataje del producto.</span>
                @error('karat') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
            </label>

            <label>
                <span class="admin-form-label">Color del metal</span>
                <select name="metal_color" class="admin-form-input">
                    <option value="">Usar color del producto{{ filled($productModel->metal_color) ? ' ('.$productModel->metal_color.')' : '' }}</option>
                    @foreach ($metalColorOptions as $metalColor)
                        <option value="{{ $metalColor }}" @selected($selectedMetalColor === $metalColor)>{{ $metalColor }}</option>
                    @endforeach
                </select>
                <span class="admin-form-help">Déjalo vacío para usar el color del producto.</span>
                @error('metal_color') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
            </label>

            <label>
                <span class="admin-form-label">Piedra</span>
                <select name="stone_id" class="admin-form-input">
                    <option value="">Usar configuración de piedra del producto</option>
                    @foreach ($stones as $stone)
                        <option value="{{ $stone->id }}" @selected((string) $selectedStoneId === (string) $stone->id)>
                            {{ $stone->name }}
                        </option>
                    @endforeach
                </select>
                <span class="admin-form-help">La piedra puede cambiar el precio de esta variante.</span>
                @error('stone_id') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
            </label>
        </div>
    </x-admin.form-section>

    <div class="mt-7">
        <x-admin.form-section
            number="02"
            title="Precio y estado"
            description="Configura los valores comerciales y la disponibilidad de esta versión."
        >
            <div class="grid gap-5 sm:grid-cols-2 lg:grid-cols-4">
                <label>
                    <span class="admin-form-label">Costo</span>
                    <div class="admin-form-input-prefix">
                        <span>$</span>
                        <input type="number" name="cost" value="{{ old('cost', $variant->cost) }}" min="0" step="0.01" inputmode="decimal" placeholder="Usar costo base ({{ $inheritedCost }})" aria-label="Costo de variante" />
                    </div>
                    <span class="admin-form-help">Vacío para usar el costo base.</span>
                    @error('cost') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </label>

                <label>
                    <span class="admin-form-label">Precio</span>
                    <div class="admin-form-input-prefix">
                        <span>$</span>
                        <input type="number" name="price" value="{{ old('price', $variant->price) }}" min="0" step="0.01" inputmode="decimal" placeholder="Usar precio base ({{ $inheritedPrice }})" aria-label="Precio de variante" />
                    </div>
                    <span class="admin-form-help">Vacío para usar el precio base.</span>
                    @error('price') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </label>

                <label>
                    <span class="admin-form-label">Precio de oferta</span>
                    <div class="admin-form-input-prefix">
                        <span>$</span>
                        <input type="number" name="sale_price" value="{{ old('sale_price', $variant->sale_price) }}" min="0" step="0.01" inputmode="decimal" placeholder="Usar oferta del producto ({{ $inheritedSalePrice }})" aria-label="Precio de oferta de variante" />
                    </div>
                    <span class="admin-form-help">Vacío para usar la oferta del producto.</span>
                    @error('sale_price') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </label>

                <label>
                    <span class="admin-form-label admin-form-label-required">Estado</span>
                    <select name="status" class="admin-form-input" required>
                        @foreach ($variantStatuses as $value => $label)
                            <option value="{{ $value }}" @selected($selectedStatus === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                    @error('status') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </label>
            </div>

            <div class="mt-6 flex flex-wrap justify-end gap-2 border-t border-[#eef0ec] pt-5">
                <a
                    href="{{ route('products.variants.index', $productModel) }}"
                    class="aurea-focus inline-flex items-center justify-center rounded-lg border border-[#dfe2dc] px-4 py-2.5 text-sm font-medium text-[#68736c] hover:border-aurea-gold hover:text-aurea-focus"
                >Cancelar</a>
                <button
                    type="submit"
                    class="aurea-focus inline-flex items-center justify-center rounded-lg bg-aurea-gold px-4 py-2.5 text-sm font-medium text-white shadow-sm hover:bg-[#9f793f]"
                >{{ $isEditing ? 'Guardar cambios' : 'Guardar variante' }}</button>
            </div>
        </x-admin.form-section>
    </div>
</form>
