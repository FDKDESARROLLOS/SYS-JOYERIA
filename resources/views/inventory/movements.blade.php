@extends('layouts.admin', ['title' => 'Movimientos', 'sectionTitle' => 'Movimientos'])

@section('content')
    @php
        $movementTypeStyles = [
            'Entrada' => 'text-[#5b8668]',
            'Salida' => 'text-aurea-danger',
            'Ajuste' => 'text-[#947142]',
            'Devolución' => 'text-[#61758b]',
        ];
        $catalogProducts = collect(config('aurea-demo.catalog_products', []));
        $productImageForSku = fn ($sku) => $catalogProducts->first(
            fn ($product) => $sku === $product['base_sku'] || str_starts_with($sku, $product['base_sku'] . '-')
        )['image'] ?? null;
        $inventoryMovements = collect(config('aurea-demo.inventory_movements', []))->map(function ($movement) use ($movementTypeStyles, $productImageForSku) {
            $quantityClass = $movement['quantity'] >= 0 ? 'text-[#5b8668]' : 'text-aurea-danger';

            return array_merge($movement, [
                'image' => $productImageForSku($movement['sku']),
                'type_class' => $movementTypeStyles[$movement['type']] ?? 'text-[#68736c]',
                'quantity_label' => sprintf('%+d', $movement['quantity']),
                'quantity_class' => $quantityClass,
            ]);
        });
        $movementTypes = ['Entrada', 'Salida', 'Ajuste', 'Devolución'];
    @endphp

    <x-admin.page-header
        :breadcrumbs="[
            [
                'label' => 'Inventario',
                'url' => route('inventory.stock'),
            ],
            [
                'label' => 'Movimientos',
                'url' => null,
            ],
        ]"
        title="Movimientos"
        description="Consulta el historial que explica cada cambio en las existencias del catálogo."
    >
        <x-slot:actions>
            <a
                href="{{ route('inventory.movements.create') }}"
                class="aurea-focus inline-flex w-fit items-center gap-2 rounded-lg bg-aurea-gold px-4 py-2.5 text-sm font-medium text-white shadow-sm transition hover:bg-[#9f793f]"
            >
                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" aria-hidden="true">
                    <path d="M12 5v14M5 12h14" />
                </svg>
                Nuevo movimiento
            </a>
        </x-slot:actions>
    </x-admin.page-header>

    <section
        class="mb-7 rounded-2xl border border-aurea-border bg-aurea-surface p-5 shadow-[0_5px_20px_rgba(35,42,37,0.03)] sm:p-6"
        aria-labelledby="movement-filters-title"
    >
        <div class="mb-5">
            <h3 id="movement-filters-title" class="font-serif text-xl text-[#28302b]">Filtrar movimientos</h3>
            <p class="mt-1 text-xs text-[#8a938d]">Consulta una operación por producto, referencia o tipo.</p>
        </div>
        <div class="grid gap-4 sm:grid-cols-3">
            <label>
                <span class="mb-2 block text-xs font-medium text-[#68736c]">Buscar movimiento</span>
                <input
                    type="search"
                    placeholder="Ej. Anillo Aura, AU-1048-T6 o ENT-0001"
                    class="aurea-focus w-full rounded-lg border border-[#dfe2dc] bg-aurea-surface px-3 py-2.5 text-sm text-[#56625a] outline-none placeholder:text-[#adb4ae] focus:border-aurea-gold"
                />
            </label>
            <x-admin.filter-select label="Tipo de movimiento" :options="$movementTypes" />
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
        aria-labelledby="movements-list-title"
    >
        <div class="border-b border-[#eef0ec] px-5 py-5 sm:px-6">
            <h3 id="movements-list-title" class="font-serif text-xl text-[#28302b]">Historial de movimientos</h3>
            <p class="mt-1 text-xs text-[#8a938d]">Mostrando {{ $inventoryMovements->count() }} movimientos ficticios.</p>
        </div>

        @if ($inventoryMovements->isEmpty())
            <x-admin.empty-state
                title="No hay movimientos todavía"
                description="Las entradas, salidas y ajustes de inventario aparecerán aquí."
                :action-url="route('inventory.movements.create')"
                action-label="Registrar movimiento"
            />
        @else
        <div class="hidden overflow-x-auto sm:block">
            <table class="admin-table w-full min-w-300 text-left text-sm">
                <caption class="sr-only">Historial de movimientos de inventario</caption>
                <thead class="bg-[#fbfbf9] text-[10px] uppercase tracking-wider text-[#9ca49e]">
                    <tr>
                        <th scope="col" class="px-5 py-3 font-medium sm:px-6">Fecha</th>
                        <th scope="col" class="px-3 py-3 font-medium">Producto</th>
                        <th scope="col" class="px-3 py-3 font-medium">Variante</th>
                        <th scope="col" class="px-3 py-3 font-medium">SKU</th>
                        <th scope="col" class="px-3 py-3 font-medium">Tipo</th>
                        <th scope="col" class="px-3 py-3 font-medium">Cantidad</th>
                        <th scope="col" class="px-3 py-3 font-medium">Stock anterior</th>
                        <th scope="col" class="px-3 py-3 font-medium">Stock resultante</th>
                        <th scope="col" class="px-3 py-3 font-medium">Motivo / Referencia</th>
                        <th scope="col" class="px-5 py-3 font-medium sm:px-6">Usuario</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-[#f0f1ed]">
                    @foreach ($inventoryMovements as $movement)
                        <tr class="text-[#5b655e] hover:bg-[#fbfbf9]">
                            <td class="whitespace-nowrap px-5 py-4 text-xs text-[#68736c] sm:px-6">{{ $movement['date'] }}</td>
                            <td class="px-3 py-4">
                                <div class="flex items-center gap-3">
                                    <x-admin.product-thumbnail
                                        :image="$movement['image']"
                                        :alt="$movement['product']"
                                        size="xs"
                                    />
                                    <span class="font-medium text-[#343d37]">{{ $movement['product'] }}</span>
                                </div>
                            </td>
                            <td class="px-3 py-4">{{ $movement['variant'] }}</td>
                            <td class="px-3 py-4 text-xs text-[#929b94]">{{ $movement['sku'] }}</td>
                            <td class="px-3 py-4">
                                <x-admin.status-badge :label="$movement['type']" :status-class="$movement['type_class']" />
                            </td>
                            <td class="px-3 py-4 text-base font-semibold {{ $movement['quantity_class'] }}">
                                {{ $movement['quantity_label'] }}
                            </td>
                            <td class="px-3 py-4 text-[#68736c]">{{ $movement['previous_stock'] }}</td>
                            <td class="px-3 py-4 font-medium text-[#343d37]">{{ $movement['resulting_stock'] }}</td>
                            <td class="px-3 py-4">
                                <p class="text-[#5b655e]">{{ $movement['reason'] }}</p>
                                <p class="mt-1 text-xs text-[#929b94]">{{ $movement['reference'] }}</p>
                            </td>
                            <td class="px-5 py-4 text-xs text-[#68736c] sm:px-6">{{ $movement['user'] }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div class="divide-y divide-[#f0f1ed] sm:hidden">
            @foreach ($inventoryMovements as $movement)
                <article class="px-5 py-4">
                    <div class="flex items-start justify-between gap-3">
                        <div class="flex min-w-0 items-center gap-3">
                            <x-admin.product-thumbnail
                                :image="$movement['image']"
                                :alt="$movement['product']"
                                size="xs"
                            />
                            <div class="min-w-0">
                            <p class="text-xs text-[#929b94]">{{ $movement['date'] }}</p>
                            <p class="mt-1 text-sm font-medium text-[#343d37]">{{ $movement['product'] }}</p>
                            <p class="mt-1 text-xs text-[#929b94]">{{ $movement['variant'] }}</p>
                            </div>
                        </div>
                        <x-admin.status-badge :label="$movement['type']" :status-class="$movement['type_class']" />
                    </div>
                    <dl class="mt-4 grid grid-cols-2 gap-x-4 gap-y-3 text-xs">
                        <div>
                            <dt class="text-[#929b94]">SKU</dt>
                            <dd class="mt-0.5 text-[#5b655e]">{{ $movement['sku'] }}</dd>
                        </div>
                        <div>
                            <dt class="text-[#929b94]">Cantidad</dt>
                            <dd class="mt-0.5 text-base font-semibold {{ $movement['quantity_class'] }}">{{ $movement['quantity_label'] }}</dd>
                        </div>
                        <div>
                            <dt class="text-[#929b94]">Stock resultante</dt>
                            <dd class="mt-0.5 font-medium text-[#343d37]">{{ $movement['resulting_stock'] }}</dd>
                        </div>
                        <div>
                            <dt class="text-[#929b94]">Referencia</dt>
                            <dd class="mt-0.5 text-[#5b655e]">{{ $movement['reference'] }}</dd>
                        </div>
                        <div class="col-span-2">
                            <dt class="text-[#929b94]">Motivo</dt>
                            <dd class="mt-0.5 text-[#5b655e]">{{ $movement['reason'] }}</dd>
                        </div>
                        <div class="col-span-2">
                            <dt class="text-[#929b94]">Usuario</dt>
                            <dd class="mt-0.5 text-[#5b655e]">{{ $movement['user'] }}</dd>
                        </div>
                    </dl>
                </article>
            @endforeach
        </div>
        @endif
    </section>
@endsection
