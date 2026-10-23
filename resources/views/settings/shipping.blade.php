@extends('layouts.admin', ['title' => 'Envíos', 'sectionTitle' => 'Envíos'])

@section('content')
    @php
        $shippingEnabled = filter_var(old('enabled', $shippingSetting->enabled), FILTER_VALIDATE_BOOLEAN);
        $freeShippingEnabled = filter_var(old('free_shipping_enabled', $shippingSetting->free_shipping_enabled), FILTER_VALIDATE_BOOLEAN);
        $freeShippingType = old('free_shipping_type', $shippingSetting->free_shipping_type ?? 'minimum');
        $shippingCost = number_format((float) old('shipping_cost', $shippingSetting->shipping_cost ?? 0), 2, '.', '');
        $isFreeShippingAll = $freeShippingEnabled && $freeShippingType === 'all';
        $minimumValue = old('free_shipping_minimum', $shippingSetting->free_shipping_minimum);
        $formattedMinimum = filled($minimumValue)
            ? rtrim(rtrim(number_format((float) $minimumValue, 2, '.', ','), '0'), '.')
            : null;
    @endphp

    <x-admin.page-header :breadcrumbs="[
        [
            'label' => 'Configuración',
            'url' => null,
        ],
        [
            'label' => 'Envíos',
            'url' => null,
        ],
    ]" title="Envíos"
        description="Configura el método general y las condiciones de envío de la tienda.">
        <x-slot:actions>
            <button
                type="submit"
                form="shipping-setting-form"
                class="aurea-focus inline-flex items-center justify-center rounded-lg bg-aurea-gold px-4 py-2.5 text-sm font-medium text-white shadow-sm hover:bg-[#9f793f]"
            >Guardar configuración</button>
        </x-slot:actions>
    </x-admin.page-header>

    <x-admin.flash-messages />

    <form id="shipping-setting-form" method="POST" action="{{ route('settings.shipping.update') }}" class="space-y-7">
        @csrf
        @method('PATCH')

        <x-admin.form-section number="01" title="Configuración general"
            description="Define el método de envío que estará disponible para los pedidos de la tienda."
            id="shipping-general-title">
            <div class="grid gap-5 sm:grid-cols-2">
                <div class="sm:col-span-2 border-b border-[#eef0ec] pb-5">
                    <x-admin.toggle
                        id="shipping-enabled"
                        name="enabled"
                        label="Activar envíos"
                        description="Permite ofrecer envíos como parte del proceso de compra."
                        :checked="$shippingEnabled"
                    />
                    @error('enabled')
                        <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <label>
                    <span class="admin-form-label admin-form-label-required">Nombre del método</span>
                    <input
                        type="text"
                        name="method_name"
                        value="{{ old('method_name', $shippingSetting->method_name) }}"
                        placeholder="Ej. Envío estándar"
                        class="admin-form-input"
                        required
                    />
                    @error('method_name')
                        <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                    @enderror
                </label>

                <label>
                    <span class="admin-form-label">Tiempo estimado</span>
                    <input
                        type="text"
                        name="estimated_time"
                        value="{{ old('estimated_time', $shippingSetting->estimated_time) }}"
                        placeholder="Ej. 2 a 5 días hábiles"
                        class="admin-form-input"
                    />
                    @error('estimated_time')
                        <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                    @enderror
                </label>

                <div>
                    <div id="shipping-cost-field" class="{{ $isFreeShippingAll ? 'hidden' : '' }}" @if ($isFreeShippingAll) aria-hidden="true" @endif>
                        <label>
                            <span class="admin-form-label admin-form-label-required">Costo de envío</span>
                            <div class="admin-form-input-prefix">
                                <span>$</span>
                                <input
                                    type="number"
                                    id="shipping-cost"
                                    name="shipping_cost"
                                    value="{{ $shippingCost }}"
                                    min="0"
                                    step="0.01"
                                    inputmode="decimal"
                                    aria-label="Costo de envío"
                                    aria-describedby="shipping-cost-help"
                                    @disabled($isFreeShippingAll)
                                    required
                                />
                                <span>MXN</span>
                            </div>
                            <span id="shipping-cost-help" class="admin-form-help">
                                {{ $freeShippingEnabled && $freeShippingType === 'minimum' ? 'Se cobrará este importe cuando el pedido no alcance el mínimo para envío gratis.' : 'Este será el costo de envío aplicado al pedido.' }}
                            </span>
                            @error('shipping_cost')
                                <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                            @enderror
                        </label>
                    </div>
                    <div
                        id="shipping-cost-all-message"
                        class="{{ $isFreeShippingAll ? '' : 'hidden' }} rounded-lg border border-[#e5ded2] bg-[#fbf8f3] px-3 py-3 text-sm text-[#806441] dark:border-[#5b5044] dark:bg-[#39342d] dark:text-[#e4c99c]"
                        @if (!$isFreeShippingAll) aria-hidden="true" @endif
                        role="status"
                    >
                        Todos los pedidos tendrán envío gratis.
                    </div>
                    <input
                        type="hidden"
                        id="shipping-cost-preserved"
                        name="shipping_cost"
                        value="{{ $shippingCost }}"
                        @disabled(!$isFreeShippingAll)
                    />
                </div>
            </div>
        </x-admin.form-section>

        <x-admin.form-section number="02" title="Envío gratis"
            description="Define cuándo los pedidos pueden obtener envío gratis." id="free-shipping-title">
            <div class="grid gap-5 sm:grid-cols-2">
                <div class="sm:col-span-2 border-b border-[#eef0ec] pb-5" id="free-shipping-toggle">
                    <x-admin.toggle
                        id="free-shipping-enabled"
                        name="free_shipping_enabled"
                        label="Activar envío gratis"
                        description="Permite ofrecer envío gratis para todos los pedidos o solo a partir de un monto mínimo."
                        :checked="$freeShippingEnabled"
                    />
                    @error('free_shipping_enabled')
                        <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div id="free-shipping-type-field"
                    class="{{ $freeShippingEnabled ? '' : 'hidden' }} sm:max-w-none"
                    @if (!$freeShippingEnabled) aria-hidden="true" @endif>
                    <label for="free-shipping-type">
                        <span class="admin-form-label">Modalidad de envío gratis</span>
                        <select id="free-shipping-type" name="free_shipping_type" class="admin-form-input">
                            <option value="all" @selected($freeShippingType === 'all')>Para todos los pedidos</option>
                            <option value="minimum" @selected($freeShippingType === 'minimum')>A partir de un monto mínimo</option>
                        </select>
                        @error('free_shipping_type')
                            <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                        @enderror
                    </label>
                </div>

                <div id="free-shipping-minimum-field"
                    class="{{ $freeShippingEnabled && $freeShippingType === 'minimum' ? '' : 'hidden' }} sm:max-w-none"
                    @if (!$freeShippingEnabled || $freeShippingType !== 'minimum') aria-hidden="true" @endif>
                    <label>
                        <span class="admin-form-label">Monto mínimo</span>
                        <div class="admin-form-input-prefix">
                            <span>$</span>
                            <input
                                type="number"
                                name="free_shipping_minimum"
                                value="{{ filled($minimumValue) ? number_format((float) $minimumValue, 2, '.', '') : '' }}"
                                min="0"
                                step="0.01"
                                inputmode="decimal"
                                aria-label="Monto mínimo para envío gratis"
                                aria-describedby="free-shipping-minimum-help"
                            />
                            <span>MXN</span>
                        </div>
                        <span id="free-shipping-minimum-help" class="admin-form-help">
                            {{ $formattedMinimum !== null ? "Los pedidos de \${$formattedMinimum} MXN o más tendrán envío gratis." : 'Los pedidos que alcancen este monto tendrán envío gratis.' }}
                        </span>
                        @error('free_shipping_minimum')
                            <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                        @enderror
                    </label>
                </div>
            </div>
        </x-admin.form-section>
    </form>
@endsection

@push('scripts')
    <script>
        const freeShippingToggle = document.querySelector('#free-shipping-toggle input[type="checkbox"]');
        const freeShippingTypeField = document.getElementById('free-shipping-type-field');
        const freeShippingTypeInput = document.getElementById('free-shipping-type');
        const freeShippingMinimumField = document.getElementById('free-shipping-minimum-field');
        const freeShippingMinimumInput = document.querySelector('input[name="free_shipping_minimum"]');
        const freeShippingMinimumHelp = document.getElementById('free-shipping-minimum-help');
        const shippingCostField = document.getElementById('shipping-cost-field');
        const shippingCostInput = document.getElementById('shipping-cost');
        const shippingCostPreserved = document.getElementById('shipping-cost-preserved');
        const shippingCostHelp = document.getElementById('shipping-cost-help');
        const shippingCostAllMessage = document.getElementById('shipping-cost-all-message');

        const updateFreeShippingField = () => {
            const isEnabled = freeShippingToggle.checked;
            const usesMinimum = freeShippingTypeInput.value === 'minimum';
            const appliesToAll = freeShippingTypeInput.value === 'all';
            const hidesShippingCost = isEnabled && appliesToAll;

            freeShippingTypeField.classList.toggle('hidden', !isEnabled);
            freeShippingTypeField.toggleAttribute('aria-hidden', !isEnabled);
            freeShippingMinimumField.classList.toggle('hidden', !isEnabled || !usesMinimum);
            freeShippingMinimumField.toggleAttribute('aria-hidden', !isEnabled || !usesMinimum);
            freeShippingMinimumInput.required = isEnabled && usesMinimum;

            if (freeShippingMinimumInput.value.trim() === '') {
                freeShippingMinimumHelp.textContent = 'Los pedidos que alcancen este monto tendrán envío gratis.';
            } else {
                const minimum = Number(freeShippingMinimumInput.value);
                const formattedMinimum = Number.isFinite(minimum)
                    ? new Intl.NumberFormat('es-MX', {
                        minimumFractionDigits: 0,
                        maximumFractionDigits: 2,
                    }).format(minimum)
                    : null;

                freeShippingMinimumHelp.textContent = formattedMinimum === null
                    ? 'Los pedidos que alcancen este monto tendrán envío gratis.'
                    : `Los pedidos de $${formattedMinimum} MXN o más tendrán envío gratis.`;
            }

            if (hidesShippingCost) {
                shippingCostPreserved.value = shippingCostInput.value;
            } else if (shippingCostPreserved.value !== '') {
                shippingCostInput.value = shippingCostPreserved.value;
            }

            shippingCostField.classList.toggle('hidden', hidesShippingCost);
            shippingCostField.toggleAttribute('aria-hidden', hidesShippingCost);
            shippingCostInput.disabled = hidesShippingCost;
            shippingCostPreserved.disabled = !hidesShippingCost;
            shippingCostAllMessage.classList.toggle('hidden', !hidesShippingCost);
            shippingCostAllMessage.toggleAttribute('aria-hidden', !hidesShippingCost);
            shippingCostHelp.textContent = usesMinimum
                ? 'Este costo se aplicará hasta alcanzar el monto mínimo.'
                : 'Este costo se aplicará a los pedidos sin envío gratis.';

            if (!isEnabled) {
                freeShippingMinimumInput.value = '';
            }
        };

        updateFreeShippingField();
        freeShippingToggle.addEventListener('change', updateFreeShippingField);
        freeShippingTypeInput.addEventListener('change', updateFreeShippingField);
    </script>
@endpush
