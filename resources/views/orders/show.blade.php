@extends('layouts.admin', ['title' => 'Pedido', 'sectionTitle' => 'Pedido'])

@section('content')
    @php
        $orders = config('aurea-demo.orders', []);
        $order = collect($orders)->firstWhere('order_number', request()->route('orderNumber'));
        abort_unless(is_array($order), 404);
        $catalogProducts = collect(config('aurea-demo.catalog_products', []));
        $productImageForSku = fn ($sku) => $catalogProducts->first(
            fn ($product) => $sku === $product['base_sku'] || str_starts_with($sku, $product['base_sku'] . '-')
        )['image'] ?? null;
        $orderStatusClasses = [
            'Pendiente' => 'text-[#947142]',
            'Procesando' => 'text-[#61758b]',
            'Enviado' => 'text-[#61758b]',
            'Entregado' => 'text-[#5b8668]',
            'Cancelado' => 'text-aurea-danger',
        ];
        $paymentStatusClasses = [
            'Pendiente' => 'text-[#947142]',
            'Pagado' => 'text-[#5b8668]',
            'Reembolsado' => 'text-[#61758b]',
            'Fallido' => 'text-aurea-danger',
        ];
        $fulfillmentStatusClasses = [
            'Pendiente de preparación' => 'text-[#947142]',
            'Preparando' => 'text-[#61758b]',
            'Despachado' => 'text-[#61758b]',
            'Entregado' => 'text-[#5b8668]',
            'No aplica' => 'text-[#929b94]',
        ];
        $fulfillment = $order['fulfillment'];
        $canRegisterDispatch = in_array($fulfillment['fulfillment_status'], ['Pendiente de preparación', 'Preparando'], true);
    @endphp

    <x-admin.page-header
        :breadcrumbs="[
            [
                'label' => 'Pedidos',
                'url' => route('orders.index'),
            ],
            [
                'label' => $order['order_number'],
                'url' => null,
            ],
        ]"
        title="Pedido {{ $order['order_number'] }}"
        description="Consulta la información del pedido y su resumen comercial."
    >
        <x-slot:actions>
            <a
                href="{{ route('orders.index') }}"
                class="aurea-focus inline-flex items-center justify-center rounded-lg border border-[#dfe2dc] px-4 py-2.5 text-sm font-medium text-[#68736c] hover:border-aurea-gold hover:text-aurea-focus"
            >Volver a pedidos</a>
        </x-slot:actions>
    </x-admin.page-header>

    <div class="mb-7 flex flex-wrap items-center gap-4 border-b border-[#eef0ec] pb-5">
        <p class="text-sm text-[#68736c]">{{ $order['date'] }}</p>
        <x-admin.status-badge :label="$order['order_status']" :status-class="$orderStatusClasses[$order['order_status']]" />
        <x-admin.status-badge :label="$order['payment_status']" :status-class="$paymentStatusClasses[$order['payment_status']]" />
    </div>

    <div class="grid items-start gap-7 xl:grid-cols-[minmax(0,1fr)_19rem]">
        <div class="space-y-7">
            <section class="rounded-2xl border border-aurea-border bg-aurea-surface p-5 shadow-[0_5px_20px_rgba(35,42,37,0.03)] sm:p-6" aria-labelledby="customer-title">
                <div class="mb-5 border-b border-[#eef0ec] pb-5">
                    <h2 id="customer-title" class="font-serif text-xl text-[#28302b]">Información del cliente</h2>
                </div>
                <dl class="grid gap-5 sm:grid-cols-3">
                    <div>
                        <dt class="text-xs text-[#929b94]">Nombre</dt>
                        <dd class="mt-1 text-sm text-[#343d37]">{{ $order['customer_name'] }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs text-[#929b94]">Correo</dt>
                        <dd class="mt-1 break-words text-sm text-[#343d37]">{{ $order['customer_email'] }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs text-[#929b94]">Teléfono</dt>
                        <dd class="mt-1 text-sm text-[#343d37]">{{ $order['customer_phone'] }}</dd>
                    </div>
                </dl>
            </section>

            <section class="rounded-2xl border border-aurea-border bg-aurea-surface p-5 shadow-[0_5px_20px_rgba(35,42,37,0.03)] sm:p-6" aria-labelledby="shipping-title">
                <div class="mb-5 border-b border-[#eef0ec] pb-5">
                    <h2 id="shipping-title" class="font-serif text-xl text-[#28302b]">Dirección de envío</h2>
                </div>
                <address class="not-italic text-sm leading-6 text-[#5b655e]">
                    <strong class="font-medium text-[#343d37]">{{ $order['shipping_address']['recipient'] }}</strong><br>
                    {{ $order['shipping_address']['street'] }} {{ $order['shipping_address']['exterior_number'] }}{{ $order['shipping_address']['interior_number'] ? ', Int. ' . $order['shipping_address']['interior_number'] : '' }}<br>
                    {{ $order['shipping_address']['neighborhood'] }}<br>
                    {{ $order['shipping_address']['city'] }}, {{ $order['shipping_address']['state'] }}<br>
                    C.P. {{ $order['shipping_address']['postal_code'] }}, {{ $order['shipping_address']['country'] }}
                </address>
            </section>

            <section class="overflow-hidden rounded-2xl border border-aurea-border bg-aurea-surface shadow-[0_5px_20px_rgba(35,42,37,0.03)]" aria-labelledby="items-title">
                <div class="border-b border-[#eef0ec] px-5 py-5 sm:px-6">
                    <h2 id="items-title" class="font-serif text-xl text-[#28302b]">Productos</h2>
                </div>
                <div class="hidden overflow-x-auto sm:block">
                    <table class="admin-table w-full min-w-190 text-left text-sm">
                        <caption class="sr-only">Productos del pedido {{ $order['order_number'] }}</caption>
                        <thead class="bg-[#fbfbf9] text-[10px] uppercase tracking-wider text-[#9ca49e]">
                            <tr>
                                <th scope="col" class="px-5 py-3 font-medium sm:px-6">Producto</th>
                                <th scope="col" class="px-3 py-3 font-medium">Variante</th>
                                <th scope="col" class="px-3 py-3 font-medium">SKU</th>
                                <th scope="col" class="px-3 py-3 font-medium">Precio</th>
                                <th scope="col" class="px-3 py-3 font-medium">Cantidad</th>
                                <th scope="col" class="px-5 py-3 text-right font-medium sm:px-6">Subtotal</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-[#f0f1ed]">
                            @foreach ($order['items'] as $item)
                                <tr class="text-[#5b655e]">
                                    <td class="px-5 py-4 sm:px-6">
                                        <div class="flex items-center gap-3">
                                            <x-admin.product-thumbnail
                                                :image="$productImageForSku($item['sku'])"
                                                :alt="$item['product']"
                                                size="xs"
                                            />
                                            <span class="font-medium text-[#343d37]">{{ $item['product'] }}</span>
                                        </div>
                                    </td>
                                    <td class="px-3 py-4">{{ $item['variant'] }}</td>
                                    <td class="px-3 py-4 text-xs text-[#929b94]">{{ $item['sku'] }}</td>
                                    <td class="whitespace-nowrap px-3 py-4">${{ number_format($item['unit_price'], 2) }} MXN</td>
                                    <td class="px-3 py-4">{{ $item['quantity'] }}</td>
                                    <td class="whitespace-nowrap px-5 py-4 text-right font-medium text-[#343d37] sm:px-6">${{ number_format($item['subtotal'], 2) }} MXN</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                <div class="divide-y divide-[#f0f1ed] sm:hidden">
                    @foreach ($order['items'] as $item)
                        <article class="px-5 py-4">
                            <div class="flex items-start justify-between gap-3">
                                <div class="flex min-w-0 items-center gap-3">
                                    <x-admin.product-thumbnail
                                        :image="$productImageForSku($item['sku'])"
                                        :alt="$item['product']"
                                        size="xs"
                                    />
                                    <div>
                                        <p class="text-sm font-medium text-[#343d37]">{{ $item['product'] }}</p>
                                    <p class="mt-1 text-xs text-[#929b94]">{{ $item['variant'] }} · {{ $item['sku'] }}</p>
                                    </div>
                                </div>
                                <p class="whitespace-nowrap text-sm font-medium text-[#343d37]">${{ number_format($item['subtotal'], 2) }} MXN</p>
                            </div>
                            <p class="mt-3 text-xs text-[#68736c]">${{ number_format($item['unit_price'], 2) }} MXN · Cantidad {{ $item['quantity'] }}</p>
                        </article>
                    @endforeach
                </div>
            </section>

            <section class="rounded-2xl border border-aurea-border bg-aurea-surface p-5 shadow-[0_5px_20px_rgba(35,42,37,0.03)] sm:p-6" aria-labelledby="fulfillment-title">
                <div class="mb-5 flex flex-col justify-between gap-4 border-b border-[#eef0ec] pb-5 sm:flex-row sm:items-start">
                    <div>
                        <h2 id="fulfillment-title" class="font-serif text-xl text-[#28302b]">Despacho</h2>
                        <p class="mt-1 text-xs text-[#8a938d]">Consulta la preparación y el envío de este pedido.</p>
                    </div>
                    <x-admin.status-badge
                        :label="$fulfillment['fulfillment_status']"
                        :status-class="$fulfillmentStatusClasses[$fulfillment['fulfillment_status']]"
                    />
                </div>
                @if ($fulfillment['fulfillment_status'] === 'No aplica')
                    <p class="text-sm leading-6 text-[#68736c]">
                        Este pedido fue cancelado antes del despacho y no requiere envío.
                    </p>
                @elseif ($canRegisterDispatch)
                    <div class="flex flex-col justify-between gap-4 sm:flex-row sm:items-center">
                        <p class="max-w-xl text-sm leading-6 text-[#68736c]">
                            {{ $fulfillment['fulfillment_status'] === 'Preparando' ? 'El pedido está en preparación y listo para completar sus datos de despacho.' : 'Este pedido todavía no cuenta con un registro de despacho.' }}
                        </p>
                        <a
                            href="{{ route('orders.dispatch', ['orderNumber' => $order['order_number']]) }}"
                            class="aurea-focus inline-flex w-fit items-center justify-center rounded-lg bg-aurea-gold px-4 py-2.5 text-sm font-medium text-white shadow-sm hover:bg-[#9f793f]"
                        >Registrar despacho</a>
                    </div>
                @else
                    <dl class="grid gap-x-5 gap-y-5 sm:grid-cols-2">
                        <div>
                            <dt class="text-xs text-[#929b94]">Paquetería</dt>
                            <dd class="mt-1 text-sm text-[#343d37]">{{ $fulfillment['carrier'] }}</dd>
                        </div>
                        <div>
                            <dt class="text-xs text-[#929b94]">Número de guía</dt>
                            <dd class="mt-1 text-sm text-[#343d37]">{{ $fulfillment['tracking_number'] }}</dd>
                        </div>
                        <div>
                            <dt class="text-xs text-[#929b94]">Fecha de envío</dt>
                            <dd class="mt-1 text-sm text-[#343d37]">{{ \Illuminate\Support\Carbon::parse($fulfillment['shipped_at'])->locale('es')->translatedFormat('d M Y') }}</dd>
                        </div>
                        <div>
                            <dt class="text-xs text-[#929b94]">Entrega estimada</dt>
                            <dd class="mt-1 text-sm text-[#343d37]">{{ \Illuminate\Support\Carbon::parse($fulfillment['estimated_delivery'])->locale('es')->translatedFormat('d M Y') }}</dd>
                        </div>
                        @if ($fulfillment['shipping_notes'])
                            <div class="sm:col-span-2">
                                <dt class="text-xs text-[#929b94]">Notas</dt>
                                <dd class="mt-1 text-sm leading-6 text-[#5b655e]">{{ $fulfillment['shipping_notes'] }}</dd>
                            </div>
                        @endif
                    </dl>
                @endif
            </section>
        </div>

        <aside class="space-y-7 lg:sticky lg:top-7">
            <section class="rounded-2xl border border-aurea-border bg-aurea-surface p-5 shadow-[0_5px_20px_rgba(35,42,37,0.03)]" aria-labelledby="summary-title">
                <h2 id="summary-title" class="font-serif text-xl text-[#28302b]">Resumen del pedido</h2>
                <dl class="mt-5 space-y-3 text-sm">
                    <div class="flex justify-between gap-4 text-[#68736c]"><dt>Subtotal</dt><dd>${{ number_format($order['subtotal'], 2) }} MXN</dd></div>
                    <div class="flex justify-between gap-4 text-[#68736c]"><dt>Envío</dt><dd>${{ number_format($order['shipping_cost'], 2) }} MXN</dd></div>
                    <div class="flex justify-between gap-4 text-[#68736c]"><dt>Impuestos</dt><dd>${{ number_format($order['tax'], 2) }} MXN</dd></div>
                    <div class="flex justify-between gap-4 border-t border-[#eef0ec] pt-4 text-base font-semibold text-[#343d37]"><dt>Total</dt><dd>${{ number_format($order['total'], 2) }} MXN</dd></div>
                </dl>
            </section>

            <section class="rounded-2xl border border-aurea-border bg-aurea-surface p-5 shadow-[0_5px_20px_rgba(35,42,37,0.03)]" aria-labelledby="payment-title">
                <h2 id="payment-title" class="font-serif text-xl text-[#28302b]">Pago</h2>
                <dl class="mt-5 space-y-4 text-sm">
                    <div><dt class="text-xs text-[#929b94]">Método</dt><dd class="mt-1 text-[#343d37]">{{ $order['payment_method'] }}</dd></div>
                    <div><dt class="text-xs text-[#929b94]">Estado</dt><dd class="mt-1"><x-admin.status-badge :label="$order['payment_status']" :status-class="$paymentStatusClasses[$order['payment_status']]" /></dd></div>
                    @if ($order['payment_reference'])
                        <div><dt class="text-xs text-[#929b94]">Referencia</dt><dd class="mt-1 text-xs text-[#5b655e]">{{ $order['payment_reference'] }}</dd></div>
                    @endif
                </dl>
            </section>
        </aside>
    </div>
@endsection
