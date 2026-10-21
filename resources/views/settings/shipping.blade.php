@extends('layouts.admin', ['title' => 'Envíos', 'sectionTitle' => 'Envíos'])

@section('content')
    @php
        $shippingSettings = config('aurea-demo.shipping_settings', []);
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
        description="Configura el método general y las condiciones de envío de {{ config('aurea-demo.store_settings.store_name', 'Tienda') }}.">
        <x-slot:actions>
            <button type="button"
                class="aurea-focus inline-flex items-center justify-center rounded-lg bg-aurea-gold px-4 py-2.5 text-sm font-medium text-white shadow-sm hover:bg-[#9f793f]">Guardar
                configuración</button>
        </x-slot:actions>
    </x-admin.page-header>

    <form class="space-y-7">
        <x-admin.form-section number="01" title="Configuración general"
            description="Define el método de envío que estará disponible para los pedidos de {{ config('aurea-demo.store_settings.store_name', 'Tienda') }}."
            id="shipping-general-title">
            <div class="grid gap-5 sm:grid-cols-2">
                <div class="sm:col-span-2 border-b border-[#eef0ec] pb-5">
                    <x-admin.toggle id="shipping-enabled" name="shipping_enabled" label="Activar envíos"
                        description="Permite ofrecer envíos como parte del proceso de compra." :checked="$shippingSettings['enabled']" />
                </div>
                <label>
                    <span class="admin-form-label admin-form-label-required">Nombre del método</span>
                    <input type="text" name="method_name" value="{{ $shippingSettings['method_name'] }}"
                        placeholder="Ej. Envío estándar" class="admin-form-input" required />
                </label>
                <label>
                    <span class="admin-form-label admin-form-label-required">Tiempo estimado</span>
                    <input type="text" name="estimated_time" value="{{ $shippingSettings['estimated_time'] }}"
                        placeholder="Ej. 2 a 5 días hábiles" class="admin-form-input" required />
                </label>
                <label>
                    <span class="admin-form-label admin-form-label-required">Costo de envío</span>
                    <div class="admin-form-input-prefix">
                        <span>$</span>
                        <input type="number" name="shipping_cost"
                            value="{{ number_format((float) ($shippingSettings['shipping_cost'] ?? 0), 2, '.', '') }}"
                            min="0" step="0.01" inputmode="decimal" aria-label="Costo de envío"
                            aria-describedby="shipping-cost-help" required />
                        <span>MXN</span>
                    </div>
                    <span id="shipping-cost-help" class="admin-form-help">Este costo se aplicará cuando el pedido no tenga
                        envío gratis.</span>
                </label>
            </div>
        </x-admin.form-section>

        <x-admin.form-section number="02" title="Envío gratis"
            description="Define cuándo los pedidos pueden obtener envío gratis." id="free-shipping-title">
            <div class="grid gap-5 sm:grid-cols-2">
                <div class="sm:col-span-2 border-b border-[#eef0ec] pb-5" id="free-shipping-toggle">
                    <x-admin.toggle id="free-shipping-enabled" name="free_shipping_enabled" label="Activar envío gratis"
                        description="Permite ofrecer envío gratis para todos los pedidos o solo a partir de un monto mínimo."
                        :checked="$shippingSettings['free_shipping_enabled']" />
                </div>
                @php
                    $freeShippingType = $shippingSettings['free_shipping_type'] ?? 'minimum';
                @endphp
                <div id="free-shipping-type-field"
                    class="{{ $shippingSettings['free_shipping_enabled'] ? '' : 'hidden' }} sm:col-span-2 sm:max-w-md"
                    @if (!$shippingSettings['free_shipping_enabled']) aria-hidden="true" @endif>
                    <label for="free-shipping-type">
                        <span class="admin-form-label">Modalidad de envío gratis</span>
                        <select id="free-shipping-type" name="free_shipping_type" class="admin-form-input">
                            <option value="all" @selected($freeShippingType === 'all')>Para todos los pedidos</option>
                            <option value="minimum" @selected($freeShippingType === 'minimum')>A partir de un monto mínimo</option>
                        </select>
                        <span id="free-shipping-all-help"
                            class="admin-form-help {{ $shippingSettings['free_shipping_enabled'] && $freeShippingType === 'all' ? '' : 'hidden' }}"
                            @if (!$shippingSettings['free_shipping_enabled'] || $freeShippingType !== 'all') aria-hidden="true" @endif>Todos los pedidos tendrán envío
                            gratis.</span>
                    </label>
                </div>
                <div id="free-shipping-minimum-field"
                    class="{{ $shippingSettings['free_shipping_enabled'] && $freeShippingType === 'minimum' ? '' : 'hidden' }} sm:max-w-md"
                    @if (!$shippingSettings['free_shipping_enabled'] || $freeShippingType !== 'minimum') aria-hidden="true" @endif>
                    <label>
                        <span class="admin-form-label">Monto mínimo</span>
                        <div class="admin-form-input-prefix">
                            <span>$</span>
                            <input type="number" name="free_shipping_minimum"
                                value="{{ filled($shippingSettings['free_shipping_minimum']) ? number_format((float) $shippingSettings['free_shipping_minimum'], 2, '.', '') : '' }}"
                                min="0" step="0.01" inputmode="decimal"
                                aria-label="Monto mínimo para envío gratis" aria-describedby="free-shipping-minimum-help" />
                            <span>MXN</span>
                        </div>
                        <span id="free-shipping-minimum-help" class="admin-form-help">Los pedidos iguales o superiores a
                            este monto tendrán envío gratis.</span>
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
        const freeShippingAllHelp = document.getElementById('free-shipping-all-help');
        const freeShippingMinimumField = document.getElementById('free-shipping-minimum-field');
        const freeShippingMinimumInput = document.querySelector('input[name="free_shipping_minimum"]');

        const updateFreeShippingField = () => {
            const isEnabled = freeShippingToggle.checked;
            const usesMinimum = freeShippingTypeInput.value === 'minimum';
            const appliesToAll = freeShippingTypeInput.value === 'all';

            freeShippingTypeField.classList.toggle('hidden', !isEnabled);
            freeShippingTypeField.toggleAttribute('aria-hidden', !isEnabled);
            freeShippingAllHelp.classList.toggle('hidden', !isEnabled || !appliesToAll);
            freeShippingAllHelp.toggleAttribute('aria-hidden', !isEnabled || !appliesToAll);
            freeShippingMinimumField.classList.toggle('hidden', !isEnabled || !usesMinimum);
            freeShippingMinimumField.toggleAttribute('aria-hidden', !isEnabled || !usesMinimum);
            freeShippingMinimumInput.required = isEnabled && usesMinimum;

            if (!isEnabled) {
                freeShippingMinimumInput.value = '';
            }
        };

        updateFreeShippingField();
        freeShippingToggle.addEventListener('change', updateFreeShippingField);
        freeShippingTypeInput.addEventListener('change', updateFreeShippingField);
    </script>
@endpush
