@extends('layouts.admin', ['title' => 'Registrar despacho', 'sectionTitle' => 'Registrar despacho'])

@section('content')
    @php
        $orders = config('aurea-demo.orders', []);
        $order = collect($orders)->firstWhere('order_number', request()->route('orderNumber'));
        abort_unless(is_array($order), 404);
        $fulfillment = $order['fulfillment'];
        abort_unless($fulfillment['fulfillment_status'] !== 'No aplica', 404);
        $carriers = ['Estafeta', 'DHL', 'FedEx', 'Otra'];
        $fulfillmentStatusClasses = [
            'Pendiente de preparación' => 'text-[#947142]',
            'Preparando' => 'text-[#61758b]',
            'Despachado' => 'text-[#61758b]',
            'Entregado' => 'text-[#5b8668]',
        ];
    @endphp

    <x-admin.page-header
        :breadcrumbs="[
            [
                'label' => 'Pedidos',
                'url' => route('orders.index'),
            ],
            [
                'label' => $order['order_number'],
                'url' => route('orders.show', ['orderNumber' => $order['order_number']]),
            ],
            [
                'label' => 'Registrar despacho',
                'url' => null,
            ],
        ]"
        title="Registrar despacho"
        description="Completa visualmente los datos de envío para el pedido {{ $order['order_number'] }}."
        :required="true"
    >
        <x-slot:actions>
            <a
                href="{{ route('orders.show', ['orderNumber' => $order['order_number']]) }}"
                class="aurea-focus inline-flex items-center justify-center rounded-lg border border-[#dfe2dc] px-4 py-2.5 text-sm font-medium text-[#68736c] hover:border-aurea-gold hover:text-aurea-focus"
            >Volver al pedido</a>
        </x-slot:actions>
    </x-admin.page-header>

    <div class="mb-7 flex flex-wrap items-center gap-3 border-b border-[#eef0ec] pb-5">
        <span class="text-sm text-[#68736c]">Estado actual:</span>
        <x-admin.status-badge
            :label="$fulfillment['fulfillment_status']"
            :status-class="$fulfillmentStatusClasses[$fulfillment['fulfillment_status']]"
        />
    </div>

    <form class="space-y-7">
        <x-admin.form-section
            number="01"
            title="Datos del despacho"
            description="Registra la paquetería y la información necesaria para enviar el pedido."
            id="dispatch-data-title"
        >
            <div class="grid gap-5 sm:grid-cols-2">
                <label>
                    <span class="admin-form-label admin-form-label-required">Paquetería</span>
                    <select name="carrier" class="admin-form-input" required>
                        <option value="">Seleccionar paquetería</option>
                        @foreach ($carriers as $carrier)
                            <option value="{{ $carrier }}" @selected($fulfillment['carrier'] === $carrier)>{{ $carrier }}</option>
                        @endforeach
                    </select>
                </label>
                <label>
                    <span class="admin-form-label admin-form-label-required">Número de guía</span>
                    <input
                        type="text"
                        name="tracking_number"
                        value="{{ $fulfillment['tracking_number'] }}"
                        placeholder="Ej. 1234567890"
                        class="admin-form-input"
                        required
                    />
                </label>
                <label>
                    <span class="admin-form-label admin-form-label-required">Fecha de envío</span>
                    <input
                        type="datetime-local"
                        name="shipped_at"
                        value="{{ $fulfillment['shipped_at'] }}"
                        class="admin-form-input"
                        required
                    />
                </label>
                <label>
                    <span class="admin-form-label">Entrega estimada</span>
                    <input
                        type="date"
                        name="estimated_delivery"
                        value="{{ $fulfillment['estimated_delivery'] }}"
                        class="admin-form-input"
                    />
                </label>
                <label class="sm:col-span-2">
                    <span class="admin-form-label">Notas</span>
                    <textarea
                        name="shipping_notes"
                        rows="4"
                        placeholder="Ej. Paquete entregado a la paquetería."
                        class="admin-form-input resize-y"
                    >{{ $fulfillment['shipping_notes'] }}</textarea>
                </label>
            </div>
        </x-admin.form-section>

        <div class="flex justify-end">
            <button
                type="button"
                class="aurea-focus inline-flex items-center justify-center rounded-lg bg-aurea-gold px-4 py-2.5 text-sm font-medium text-white shadow-sm hover:bg-[#9f793f]"
            >Confirmar despacho</button>
        </div>
    </form>
@endsection
