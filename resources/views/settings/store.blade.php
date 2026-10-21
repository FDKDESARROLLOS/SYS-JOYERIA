@extends('layouts.admin', ['title' => 'Datos de la tienda', 'sectionTitle' => 'Datos de la tienda'])

@section('content')
    @php
        $storeSettings = config('aurea-demo.store_settings', []);
        $timezones = ['America/Mexico_City'];
    @endphp

    <x-admin.page-header
        :breadcrumbs="[
            [
                'label' => 'Configuración',
                'url' => null,
            ],
            [
                'label' => 'Datos de la tienda',
                'url' => null,
            ],
        ]"
        title="Datos de la tienda"
        description="Configura la información general y comercial que identifica a {{ config('aurea-demo.store_settings.store_name', 'Tienda') }}."
        :required="true"
    >
        <x-slot:actions>
            <button
                type="button"
                class="aurea-focus inline-flex items-center justify-center rounded-lg bg-aurea-gold px-4 py-2.5 text-sm font-medium text-white shadow-sm hover:bg-[#9f793f]"
            >Guardar configuración</button>
        </x-slot:actions>
    </x-admin.page-header>

    <form class="space-y-7">
        <x-admin.form-section
            number="01"
            title="Información de la tienda"
            description="Define el nombre y los datos de contacto principales de {{ config('aurea-demo.store_settings.store_name', 'Tienda') }}."
            id="store-information-title"
        >
            <div class="grid gap-5 sm:grid-cols-2">
                <label>
                    <span class="admin-form-label admin-form-label-required">Nombre de la tienda</span>
                    <input
                        type="text"
                        name="store_name"
                        value="{{ $storeSettings['store_name'] }}"
                        placeholder="Ej. Nombre de la tienda"
                        class="admin-form-input"
                        required
                    />
                </label>
                <label>
                    <span class="admin-form-label">Razón social</span>
                    <input
                        type="text"
                        name="legal_name"
                        value="{{ $storeSettings['legal_name'] }}"
                        placeholder="Razón social de la tienda"
                        class="admin-form-input"
                    />
                </label>
                <label>
                    <span class="admin-form-label admin-form-label-required">Correo</span>
                    <input
                        type="email"
                        name="email"
                        value="{{ $storeSettings['email'] }}"
                        placeholder="hola@aureajewelry.com"
                        class="admin-form-input"
                        required
                    />
                </label>
                <label>
                    <span class="admin-form-label">Teléfono</span>
                    <input
                        type="tel"
                        name="phone"
                        value="{{ $storeSettings['phone'] }}"
                        placeholder="+52 55 0000 0000"
                        class="admin-form-input"
                    />
                </label>
                <label>
                    <span class="admin-form-label">WhatsApp</span>
                    <input
                        type="tel"
                        name="whatsapp"
                        value="{{ $storeSettings['whatsapp'] }}"
                        placeholder="+52 55 0000 0000"
                        class="admin-form-input"
                    />
                </label>
            </div>
        </x-admin.form-section>

        <x-admin.form-section
            number="02"
            title="Configuración comercial"
            description="Define los valores base que se mostrarán en las operaciones de la tienda."
            id="commercial-settings-title"
        >
            <div class="grid gap-5 sm:grid-cols-2">
                <div>
                    <span class="admin-form-label">Moneda</span>
                    <div class="flex min-h-11 items-center rounded-lg border border-[#dfe2dc] bg-[#fbfbf9] px-3 text-sm text-[#56625a]">
                        Peso mexicano (MXN)
                    </div>
                </div>
                <label>
                    <span class="admin-form-label admin-form-label-required">Zona horaria</span>
                    <select name="timezone" class="admin-form-input" required>
                        @foreach ($timezones as $timezone)
                            <option value="{{ $timezone }}" @selected($storeSettings['timezone'] === $timezone)>{{ $timezone }}</option>
                        @endforeach
                    </select>
                </label>
                <label>
                    <span class="admin-form-label">Impuesto general (%)</span>
                    <input
                        type="number"
                        name="tax_percentage"
                        value="{{ filled($storeSettings['tax_percentage']) ? number_format((float) $storeSettings['tax_percentage'], 4, '.', '') : '' }}"
                        min="0"
                        step="0.0001"
                        inputmode="decimal"
                        class="admin-form-input"
                        aria-describedby="tax-percentage-help"
                    />
                    <span id="tax-percentage-help" class="admin-form-help">Déjalo vacío si la tienda no utiliza un impuesto general.</span>
                </label>
                <label>
                    <span class="admin-form-label">Costo de envío</span>
                    <div class="admin-form-input-prefix">
                        <span>$</span>
                        <input
                            type="number"
                            name="shipping_cost"
                            value="{{ filled($storeSettings['shipping_cost']) ? number_format((float) $storeSettings['shipping_cost'], 2, '.', '') : '' }}"
                            min="0"
                            step="0.01"
                            inputmode="decimal"
                            aria-label="Costo de envío"
                            aria-describedby="shipping-cost-help"
                        />
                        <span>MXN</span>
                    </div>
                    <span id="shipping-cost-help" class="admin-form-help">Déjalo vacío si el costo de envío se definirá posteriormente o no aplica.</span>
                </label>
            </div>
        </x-admin.form-section>
    </form>
@endsection
