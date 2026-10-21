@extends('layouts.admin', ['title' => 'Pagos', 'sectionTitle' => 'Pagos'])

@section('content')
    @php
        $paymentSettings = config('aurea-demo.payment_settings', []);
        $stripeModes = ['Pruebas', 'Producción'];
        $paypalModes = ['Sandbox', 'Producción'];
    @endphp

    <x-admin.page-header
        :breadcrumbs="[
            [
                'label' => 'Configuración',
                'url' => null,
            ],
            [
                'label' => 'Pagos',
                'url' => null,
            ],
        ]"
        title="Pagos"
        description="Configura visualmente los proveedores de pago disponibles para {{ config('aurea-demo.store_settings.store_name', 'Tienda') }}."
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
            title="Stripe"
            description="Configura las credenciales para procesar pagos con tarjeta."
            id="stripe-settings-title"
        >
            <div class="grid gap-5 sm:grid-cols-2">
                <div class="sm:col-span-2 border-b border-[#eef0ec] pb-5">
                    <x-admin.toggle
                        id="stripe-enabled"
                        name="stripe_enabled"
                        label="Activar Stripe"
                        description="Permite mostrar Stripe como método de pago disponible."
                        :checked="$paymentSettings['stripe']['enabled']"
                    />
                </div>
                <label>
                    <span class="admin-form-label">Modo</span>
                    <select name="stripe_mode" class="admin-form-input">
                        @foreach ($stripeModes as $stripeMode)
                            <option value="{{ $stripeMode }}" @selected($paymentSettings['stripe']['mode'] === $stripeMode)>{{ $stripeMode }}</option>
                        @endforeach
                    </select>
                </label>
                <label>
                    <span class="admin-form-label">Clave pública</span>
                    <input
                        type="text"
                        name="stripe_public_key"
                        value="{{ $paymentSettings['stripe']['public_key'] }}"
                        class="admin-form-input"
                    />
                </label>
                <label>
                    <span class="admin-form-label">Clave secreta</span>
                        <input
                            type="password"
                            name="stripe_secret_key"
                            placeholder="••••••••••••"
                            class="admin-form-input"
                    />
                </label>
                <label>
                    <span class="admin-form-label">Webhook secret</span>
                        <input
                            type="password"
                            name="stripe_webhook_secret"
                            placeholder="••••••••••••"
                            class="admin-form-input"
                    />
                </label>
            </div>
        </x-admin.form-section>

        <x-admin.form-section
            number="02"
            title="PayPal"
            description="Configura las credenciales de tu cuenta de PayPal."
            id="paypal-settings-title"
        >
            <div class="grid gap-5 sm:grid-cols-2">
                <div class="sm:col-span-2 border-b border-[#eef0ec] pb-5">
                    <x-admin.toggle
                        id="paypal-enabled"
                        name="paypal_enabled"
                        label="Activar PayPal"
                        description="Permite mostrar PayPal como método de pago disponible."
                        :checked="$paymentSettings['paypal']['enabled']"
                    />
                </div>
                <label>
                    <span class="admin-form-label">Modo</span>
                    <select name="paypal_mode" class="admin-form-input">
                        @foreach ($paypalModes as $paypalMode)
                            <option value="{{ $paypalMode }}" @selected($paymentSettings['paypal']['mode'] === $paypalMode)>{{ $paypalMode }}</option>
                        @endforeach
                    </select>
                </label>
                <label>
                    <span class="admin-form-label">Client ID</span>
                    <input
                        type="text"
                        name="paypal_client_id"
                        value="{{ $paymentSettings['paypal']['client_id'] }}"
                        class="admin-form-input"
                    />
                </label>
                <label>
                    <span class="admin-form-label">Client Secret</span>
                        <input
                            type="password"
                            name="paypal_client_secret"
                            placeholder="••••••••••••"
                            class="admin-form-input"
                    />
                </label>
            </div>
        </x-admin.form-section>
    </form>
@endsection
