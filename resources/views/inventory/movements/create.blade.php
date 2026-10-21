@extends('layouts.admin', ['title' => 'Nuevo movimiento', 'sectionTitle' => 'Nuevo movimiento'])

@section('content')
    @php
        $inventoryItems = collect(config('aurea-demo.inventory_stock', []));
        $movementTypes = ['Entrada', 'Salida', 'Ajuste', 'Devolución'];
        $demoDate = '2026-10-02T10:30';
    @endphp

    <x-admin.page-header :breadcrumbs="[
        [
            'label' => 'Inventario',
            'url' => route('inventory.stock'),
        ],
        [
            'label' => 'Movimientos',
            'url' => route('inventory.movements'),
        ],
        [
            'label' => 'Nuevo movimiento',
            'url' => null,
        ],
    ]" title="Nuevo movimiento"
        description="Registra visualmente una entrada, salida, devolución o ajuste de inventario." :required="true">
        <x-slot:actions>
            <a href="{{ route('inventory.movements') }}"
                class="aurea-focus inline-flex items-center justify-center rounded-lg border border-[#dfe2dc] px-4 py-2.5 text-sm font-medium text-[#68736c] hover:border-aurea-gold hover:text-aurea-focus">Cancelar</a>
        </x-slot:actions>
    </x-admin.page-header>

    <form id="inventory-movement-form" class="grid items-start gap-7 xl:grid-cols-[minmax(0,1fr)_19rem]"
        onsubmit="return false">
        <div class="space-y-7">
            <x-admin.form-section number="01" title="Operación"
                description="Selecciona la unidad inventariable y define cómo cambiará su stock."
                id="movement-operation-title">
                <div class="grid gap-5 sm:grid-cols-2">
                    <label>
                        <span class="admin-form-label admin-form-label-required">Tipo de movimiento</span>
                        <select id="movement-type" name="type" class="admin-form-input" required>
                            <option value="">Seleccionar tipo</option>
                            @foreach ($movementTypes as $movementType)
                                <option>{{ $movementType }}</option>
                            @endforeach
                        </select>
                    </label>
                    <label>
                        <span class="admin-form-label admin-form-label-required">Producto / variante</span>
                        <select id="inventory-item" name="sku" class="admin-form-input" required>
                            <option value="">Seleccionar producto o variante</option>
                            @foreach ($inventoryItems as $inventoryItem)
                                <option value="{{ $inventoryItem['sku'] }}"
                                    data-stock="{{ $inventoryItem['current_stock'] }}">
                                    {{ $inventoryItem['product'] }} — {{ $inventoryItem['variant'] }} —
                                    {{ $inventoryItem['sku'] }}
                                </option>
                            @endforeach
                        </select>
                    </label>
                    <div class="sm:col-span-2 rounded-xl border border-[#eef0ec] bg-[#fbfbf9] px-4 py-3">
                        <p class="text-xs text-[#929b94]">Stock actual</p>
                        <p id="current-stock" class="mt-1 text-sm font-medium text-[#343d37]">Selecciona un producto o
                            variante</p>
                    </div>
                    <label>
                        <span class="admin-form-label admin-form-label-required">Cantidad</span>
                        <input id="movement-quantity" name="quantity" type="number" min="1" step="1"
                            placeholder="Ej. 5" class="admin-form-input" required
                            aria-describedby="movement-quantity-help" />
                        <span id="movement-quantity-help" class="admin-form-help">Escribe siempre una cantidad
                            positiva.</span>
                    </label>
                    <div id="adjustment-type-field" class="hidden">
                        <label>
                            <span class="admin-form-label admin-form-label-required">Tipo de ajuste</span>
                            <select id="adjustment-type" name="adjustment_type" class="admin-form-input">
                                <option value="">Seleccionar ajuste</option>
                                <option value="increase">Aumentar stock</option>
                                <option value="decrease">Disminuir stock</option>
                            </select>
                        </label>
                    </div>
                    <p id="stock-validation" class="hidden sm:col-span-2 text-sm text-aurea-danger" role="alert"></p>
                </div>
            </x-admin.form-section>

            <x-admin.form-section number="02" title="Detalle del movimiento"
                description="Documenta el motivo y el contexto de esta operación." id="movement-detail-title">
                <div class="grid gap-5 sm:grid-cols-2">
                    <label class="sm:col-span-2">
                        <span class="admin-form-label admin-form-label-required">Motivo</span>
                        <textarea id="movement-reason" name="reason" rows="3" placeholder="Ej. Reposición de inventario"
                            class="admin-form-input resize-y" required></textarea>
                    </label>
                    <label>
                        <span class="admin-form-label">Referencia</span>
                        <input id="movement-reference" name="reference" type="text" placeholder="Ej. ENT-0002"
                            class="admin-form-input" />
                    </label>
                    <label>
                        <span class="admin-form-label admin-form-label-required">Fecha</span>
                        <input id="movement-date" name="date" type="datetime-local" value="{{ $demoDate }}"
                            class="admin-form-input" required />
                    </label>
                    <div class="sm:col-span-2 rounded-xl border border-[#eef0ec] bg-[#fbfbf9] px-4 py-3">
                        <p class="text-xs text-[#929b94]">Usuario</p>
                        <p class="mt-1 text-sm font-medium text-[#343d37]">Admin Prueba <span
                                class="font-normal text-[#929b94]">· Administradora</span></p>
                    </div>
                </div>
            </x-admin.form-section>
        </div>

        <aside class="space-y-7 lg:sticky lg:top-7">
            <x-admin.form-section number="03" title="Resultado estimado"
                description="Vista previa del cambio, sin guardar información." id="movement-preview-title">
                <dl class="space-y-4 text-sm">
                    <div class="flex items-center justify-between gap-4">
                        <dt class="text-[#929b94]">Stock actual</dt>
                        <dd id="preview-current-stock" class="font-medium text-[#343d37]">—</dd>
                    </div>
                    <div class="flex items-center justify-between gap-4">
                        <dt class="text-[#929b94]">Movimiento</dt>
                        <dd id="preview-movement" class="font-semibold text-[#68736c]">—</dd>
                    </div>
                    <div class="flex items-center justify-between gap-4 border-t border-[#eef0ec] pt-4">
                        <dt class="font-medium text-[#5b655e]">Stock resultante</dt>
                        <dd id="preview-resulting-stock" class="text-lg font-semibold text-[#343d37]">—</dd>
                    </div>
                </dl>
                <button id="register-movement" type="button"
                    class="aurea-focus mt-6 inline-flex w-full items-center justify-center rounded-lg bg-aurea-gold px-4 py-2.5 text-sm font-medium text-white shadow-sm transition hover:bg-[#9f793f] disabled:cursor-not-allowed disabled:opacity-50">Registrar
                    movimiento</button>
                <p class="mt-3 text-center text-[11px] text-[#929b94]">Esta acción es solo visual por ahora.</p>
            </x-admin.form-section>
        </aside>
    </form>
@endsection

@push('scripts')
    <script>
        const movementType = document.getElementById('movement-type');
        const inventoryItem = document.getElementById('inventory-item');
        const movementQuantity = document.getElementById('movement-quantity');
        const movementReason = document.getElementById('movement-reason');
        const movementDate = document.getElementById('movement-date');
        const adjustmentTypeField = document.getElementById('adjustment-type-field');
        const adjustmentType = document.getElementById('adjustment-type');
        const currentStock = document.getElementById('current-stock');
        const stockValidation = document.getElementById('stock-validation');
        const previewCurrentStock = document.getElementById('preview-current-stock');
        const previewMovement = document.getElementById('preview-movement');
        const previewResultingStock = document.getElementById('preview-resulting-stock');
        const registerMovement = document.getElementById('register-movement');

        const getSelectedStock = () => {
            const selectedOption = inventoryItem.options[inventoryItem.selectedIndex];

            return selectedOption?.dataset.stock === undefined ? null : Number(selectedOption.dataset.stock);
        };

        const updateMovementPreview = () => {
            const stock = getSelectedStock();
            const quantity = Number(movementQuantity.value);
            const selectedType = movementType.value;
            const isAdjustment = selectedType === 'Ajuste';
            let direction = 0;

            adjustmentTypeField.classList.toggle('hidden', !isAdjustment);
            adjustmentType.required = isAdjustment;

            if (!isAdjustment) {
                adjustmentType.value = '';
            }

            if (selectedType === 'Entrada' || selectedType === 'Devolución') {
                direction = 1;
            } else if (selectedType === 'Salida' || (isAdjustment && adjustmentType.value === 'decrease')) {
                direction = -1;
            }

            currentStock.textContent = stock === null ? 'Selecciona un producto o variante' : `${stock} unidades`;
            previewCurrentStock.textContent = stock === null ? '—' : stock;

            const missingRequiredField = stock === null ||
                !selectedType ||
                !quantity ||
                direction === 0 ||
                !movementReason.value.trim() ||
                !movementDate.value ||
                (isAdjustment && !adjustmentType.value);

            if (missingRequiredField) {
                previewMovement.textContent = '—';
                previewResultingStock.textContent = '—';
                stockValidation.classList.add('hidden');
                registerMovement.disabled = true;

                return;
            }

            const signedQuantity = quantity * direction;
            const resultingStock = stock + signedQuantity;
            const exceedsAvailableStock = resultingStock < 0;

            previewMovement.textContent = `${signedQuantity > 0 ? '+' : ''}${signedQuantity}`;
            previewResultingStock.textContent = exceedsAvailableStock ? '—' : resultingStock;
            stockValidation.textContent = exceedsAvailableStock ?
                'La cantidad no puede superar el stock disponible.' :
                '';
            stockValidation.classList.toggle('hidden', !exceedsAvailableStock);
            registerMovement.disabled = exceedsAvailableStock;
        };

        [movementType, inventoryItem, movementQuantity, adjustmentType].forEach((field) => {
            field.addEventListener('input', updateMovementPreview);
            field.addEventListener('change', updateMovementPreview);
        });

        updateMovementPreview();
    </script>
@endpush
