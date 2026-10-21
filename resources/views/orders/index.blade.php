@extends('layouts.admin', ['title' => 'Pedidos', 'sectionTitle' => 'Pedidos'])

@section('content')
    @php
        $orders = config('aurea-demo.orders', []);
        $orderStatuses = ['Pendiente', 'Procesando', 'Enviado', 'Entregado', 'Cancelado'];
        $paymentStatuses = ['Pendiente', 'Pagado', 'Reembolsado', 'Fallido'];
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
    @endphp

    <x-admin.page-header
        :breadcrumbs="[
            [
                'label' => 'Ventas',
                'url' => null,
            ],
            [
                'label' => 'Pedidos',
                'url' => null,
            ],
        ]"
        title="Pedidos"
        description="Consulta y organiza los pedidos recibidos en la tienda {{ config('aurea-demo.store_settings.store_name', 'Tienda') }}."
    />

    <section
        class="mb-7 rounded-2xl border border-aurea-border bg-aurea-surface p-5 shadow-[0_5px_20px_rgba(35,42,37,0.03)] sm:p-6"
        aria-labelledby="order-filters-title"
    >
        <div class="mb-5">
            <h3 id="order-filters-title" class="font-serif text-xl text-[#28302b]">Filtrar pedidos</h3>
            <p class="mt-1 text-xs text-[#8a938d]">Encuentra un pedido por número, cliente o correo electrónico.</p>
        </div>
        <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
            <label class="sm:col-span-2 xl:col-span-1">
                <span class="mb-2 block text-xs font-medium text-[#68736c]">Buscar pedido</span>
                <input
                    type="search"
                    placeholder="Ej. ORD-1001 o Mariana López"
                    class="aurea-focus w-full rounded-lg border border-[#dfe2dc] bg-aurea-surface px-3 py-2.5 text-sm text-[#56625a] outline-none placeholder:text-[#adb4ae] focus:border-aurea-gold"
                />
            </label>
            <x-admin.filter-select label="Estado del pedido" :options="$orderStatuses" />
            <x-admin.filter-select label="Estado de pago" :options="$paymentStatuses" />
            <label>
                <span class="mb-2 block text-xs font-medium text-[#68736c]">Fecha</span>
                <input
                    type="date"
                    class="aurea-focus w-full rounded-lg border border-[#dfe2dc] bg-aurea-surface px-3 py-2.5 text-sm text-[#56625a] outline-none focus:border-aurea-gold"
                />
            </label>
        </div>
    </section>

    <section
        class="overflow-hidden rounded-2xl border border-aurea-border bg-aurea-surface shadow-[0_5px_20px_rgba(35,42,37,0.03)]"
        aria-labelledby="orders-list-title"
    >
        <div class="border-b border-[#eef0ec] px-5 py-5 sm:px-6">
            <h3 id="orders-list-title" class="font-serif text-xl text-[#28302b]">Listado de pedidos</h3>
            <p class="mt-1 text-xs text-[#8a938d]">Mostrando {{ count($orders) }} pedidos ficticios.</p>
        </div>

        @if (empty($orders))
            <x-admin.empty-state
                title="No hay pedidos todavía"
                description="Los pedidos recibidos aparecerán aquí para que puedas darles seguimiento."
            />
        @else
        <div class="hidden overflow-x-auto sm:block">
            <table class="admin-table w-full min-w-300 text-left text-sm">
                <caption class="sr-only">Listado de pedidos de la tienda</caption>
                <thead class="bg-[#fbfbf9] text-[10px] uppercase tracking-wider text-[#9ca49e]">
                    <tr>
                        <th scope="col" class="px-5 py-3 font-medium sm:px-6">Pedido</th>
                        <th scope="col" class="px-3 py-3 font-medium">Cliente</th>
                        <th scope="col" class="px-3 py-3 font-medium">Fecha</th>
                        <th scope="col" class="px-3 py-3 font-medium">Productos</th>
                        <th scope="col" class="px-3 py-3 font-medium">Total</th>
                        <th scope="col" class="px-3 py-3 font-medium">Método de pago</th>
                        <th scope="col" class="px-3 py-3 font-medium">Pago</th>
                        <th scope="col" class="px-3 py-3 font-medium">Estado</th>
                        <th scope="col" class="px-5 py-3 text-right font-medium sm:px-6">Acciones</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-[#f0f1ed]">
                    @foreach ($orders as $order)
                        <tr class="text-[#5b655e] hover:bg-[#fbfbf9]">
                            <td class="px-5 py-4 sm:px-6">
                                <p class="font-medium text-[#343d37]">{{ $order['order_number'] }}</p>
                                <p class="mt-1 text-xs text-[#929b94]">{{ $order['customer_email'] }}</p>
                            </td>
                            <td class="px-3 py-4">{{ $order['customer_name'] }}</td>
                            <td class="whitespace-nowrap px-3 py-4 text-xs text-[#68736c]">{{ $order['date'] }}</td>
                            <td class="px-3 py-4">{{ $order['items_count'] }}</td>
                            <td class="whitespace-nowrap px-3 py-4 font-medium text-[#343d37]">
                                ${{ number_format($order['total'], 2) }} MXN
                            </td>
                            <td class="px-3 py-4">{{ $order['payment_method'] }}</td>
                            <td class="px-3 py-4">
                                <x-admin.status-badge
                                    :label="$order['payment_status']"
                                    :status-class="$paymentStatusClasses[$order['payment_status']]"
                                />
                            </td>
                            <td class="px-3 py-4">
                                <x-admin.status-badge
                                    :label="$order['order_status']"
                                    :status-class="$orderStatusClasses[$order['order_status']]"
                                />
                            </td>
                            <td class="px-5 py-4 text-right sm:px-6">
                                <a
                                    href="{{ route('orders.show', ['orderNumber' => $order['order_number']]) }}"
                                    class="aurea-focus rounded-md px-2 py-1.5 text-xs font-medium text-aurea-muted hover:bg-aurea-paper hover:text-aurea-focus"
                                >Ver pedido</a>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div class="divide-y divide-[#f0f1ed] sm:hidden">
            @foreach ($orders as $order)
                <article class="px-5 py-4">
                    <div class="flex items-start justify-between gap-3">
                        <div class="min-w-0">
                            <p class="text-sm font-medium text-[#343d37]">{{ $order['order_number'] }}</p>
                            <p class="mt-1 text-xs text-[#929b94]">{{ $order['customer_name'] }}</p>
                        </div>
                        <x-admin.status-badge
                            :label="$order['order_status']"
                            :status-class="$orderStatusClasses[$order['order_status']]"
                        />
                    </div>
                    <dl class="mt-4 grid grid-cols-2 gap-x-4 gap-y-3 text-xs">
                        <div>
                            <dt class="text-[#929b94]">Fecha</dt>
                            <dd class="mt-0.5 text-[#5b655e]">{{ $order['date'] }}</dd>
                        </div>
                        <div>
                            <dt class="text-[#929b94]">Total</dt>
                            <dd class="mt-0.5 font-medium text-[#343d37]">${{ number_format($order['total'], 2) }} MXN</dd>
                        </div>
                        <div>
                            <dt class="text-[#929b94]">Pago</dt>
                            <dd class="mt-0.5"><x-admin.status-badge :label="$order['payment_status']" :status-class="$paymentStatusClasses[$order['payment_status']]" /></dd>
                        </div>
                        <div>
                            <dt class="text-[#929b94]">Productos</dt>
                            <dd class="mt-0.5 text-[#5b655e]">{{ $order['items_count'] }}</dd>
                        </div>
                    </dl>
                    <p class="mt-3 text-xs text-[#929b94]">{{ $order['customer_email'] }}</p>
                    <div class="mt-3 flex justify-end">
                        <a
                            href="{{ route('orders.show', ['orderNumber' => $order['order_number']]) }}"
                            class="aurea-focus rounded-md px-2 py-1.5 text-xs font-medium text-aurea-muted hover:bg-aurea-paper hover:text-aurea-focus"
                        >Ver pedido</a>
                    </div>
                </article>
            @endforeach
        </div>
        @endif
    </section>
@endsection
