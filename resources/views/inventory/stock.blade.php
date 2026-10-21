@extends('layouts.admin', ['title' => 'Existencias', 'sectionTitle' => 'Existencias'])

@section('content')
    @php
        $catalogProducts = collect(config('aurea-demo.catalog_products', []));
        $productImageForSku = fn ($sku) => $catalogProducts->first(
            fn ($product) => $sku === $product['base_sku'] || str_starts_with($sku, $product['base_sku'] . '-')
        )['image'] ?? null;
        $inventoryRows = collect(config('aurea-demo.inventory_stock'))->map(function ($inventoryItem) use ($productImageForSku) {
            if ($inventoryItem['current_stock'] === 0) {
                $stockStatus = 'Sin stock';
                $stockStatusClass = 'text-aurea-danger';
            } elseif ($inventoryItem['current_stock'] <= $inventoryItem['minimum_stock']) {
                $stockStatus = 'Stock bajo';
                $stockStatusClass = 'text-[#947142]';
            } else {
                $stockStatus = 'Disponible';
                $stockStatusClass = 'text-[#5b8668]';
            }

            return array_merge($inventoryItem, [
                'image' => $productImageForSku($inventoryItem['sku']),
                'stock_status' => $stockStatus,
                'stock_status_class' => $stockStatusClass,
            ]);
        });
        $inventoryCategories = $inventoryRows->pluck('category')->unique()->values()->all();
        $inventoryStatuses = ['Disponible', 'Stock bajo', 'Sin stock'];
    @endphp

    <x-admin.page-header
        :breadcrumbs="[
            [
                'label' => 'Inventario',
                'url' => null,
            ],
            [
                'label' => 'Existencias',
                'url' => null,
            ],
        ]"
        title="Existencias"
        description="Consulta el stock actual de tus productos y variantes sin modificar sus cantidades."
    />

    <section
        class="mb-7 rounded-2xl border border-aurea-border bg-aurea-surface p-5 shadow-[0_5px_20px_rgba(35,42,37,0.03)] sm:p-6"
        aria-labelledby="stock-filters-title"
    >
        <div class="mb-5">
            <h3 id="stock-filters-title" class="font-serif text-xl text-[#28302b]">Filtrar existencias</h3>
            <p class="mt-1 text-xs text-[#8a938d]">Encuentra rápidamente un producto o revisa los niveles de stock.</p>
        </div>
        <div class="grid gap-4 sm:grid-cols-3">
            <label>
                <span class="mb-2 block text-xs font-medium text-[#68736c]">Buscar por nombre o SKU</span>
                <input
                    type="search"
                    placeholder="Ej. Anillo Aura o AU-1048-T7"
                    class="aurea-focus w-full rounded-lg border border-[#dfe2dc] bg-aurea-surface px-3 py-2.5 text-sm text-[#56625a] outline-none placeholder:text-[#adb4ae] focus:border-aurea-gold"
                />
            </label>
            <x-admin.filter-select label="Categoría" :options="$inventoryCategories" />
            <x-admin.filter-select label="Estado de stock" :options="$inventoryStatuses" />
        </div>
    </section>

    <section
        class="overflow-hidden rounded-2xl border border-aurea-border bg-aurea-surface shadow-[0_5px_20px_rgba(35,42,37,0.03)]"
        aria-labelledby="stock-list-title"
    >
        <div class="border-b border-[#eef0ec] px-5 py-5 sm:px-6">
            <h3 id="stock-list-title" class="font-serif text-xl text-[#28302b]">Stock actual</h3>
            <p class="mt-1 text-xs text-[#8a938d]">Mostrando {{ $inventoryRows->count() }} productos y variantes ficticios.</p>
        </div>

        @if ($inventoryRows->isEmpty())
            <x-admin.empty-state
                title="No hay existencias todavía"
                description="El stock actual de productos y variantes aparecerá aquí."
            />
        @else
        <div class="hidden overflow-x-auto sm:block">
            <table class="admin-table w-full min-w-220 text-left text-sm">
                <caption class="sr-only">Existencias actuales de productos y variantes</caption>
                <thead class="bg-[#fbfbf9] text-[10px] uppercase tracking-wider text-[#9ca49e]">
                    <tr>
                        <th scope="col" class="px-5 py-3 font-medium sm:px-6">Producto</th>
                        <th scope="col" class="px-3 py-3 font-medium">Variante</th>
                        <th scope="col" class="px-3 py-3 font-medium">SKU</th>
                        <th scope="col" class="px-3 py-3 font-medium">Categoría</th>
                        <th scope="col" class="px-3 py-3 font-medium">Stock actual</th>
                        <th scope="col" class="px-3 py-3 font-medium">Stock mínimo</th>
                        <th scope="col" class="px-3 py-3 font-medium">Estado</th>
                        <th scope="col" class="px-5 py-3 text-right font-medium sm:px-6">Acciones</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-[#f0f1ed]">
                    @foreach ($inventoryRows as $inventoryRow)
                        <tr class="text-[#5b655e] hover:bg-[#fbfbf9]">
                            <td class="px-5 py-4 sm:px-6">
                                <div class="flex items-center gap-3">
                                    <x-admin.product-thumbnail
                                        :image="$inventoryRow['image']"
                                        :alt="$inventoryRow['product']"
                                        size="xs"
                                    />
                                    <p class="font-medium text-[#343d37]">{{ $inventoryRow['product'] }}</p>
                                </div>
                            </td>
                            <td class="px-3 py-4">{{ $inventoryRow['variant'] }}</td>
                            <td class="px-3 py-4 text-xs text-[#929b94]">{{ $inventoryRow['sku'] }}</td>
                            <td class="px-3 py-4">{{ $inventoryRow['category'] }}</td>
                            <td class="px-3 py-4 font-medium {{ $inventoryRow['current_stock'] === 0 ? 'text-aurea-danger' : 'text-[#343d37]' }}">
                                {{ $inventoryRow['current_stock'] }}
                            </td>
                            <td class="px-3 py-4 text-[#929b94]">{{ $inventoryRow['minimum_stock'] }}</td>
                            <td class="px-3 py-4">
                                <x-admin.status-badge
                                    :label="$inventoryRow['stock_status']"
                                    :status-class="$inventoryRow['stock_status_class']"
                                />
                            </td>
                            <td class="px-5 py-4 text-right sm:px-6">
                                <a
                                    href="{{ route('inventory.movements', ['sku' => $inventoryRow['sku']]) }}"
                                    class="aurea-focus rounded-md px-2 py-1.5 text-xs font-medium text-aurea-muted hover:bg-aurea-paper hover:text-aurea-focus"
                                    aria-label="Ver movimientos de {{ $inventoryRow['product'] }} {{ $inventoryRow['variant'] }}"
                                >
                                    Ver movimientos
                                </a>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div class="divide-y divide-[#f0f1ed] sm:hidden">
            @foreach ($inventoryRows as $inventoryRow)
                <article class="px-5 py-4">
                    <div class="flex items-start justify-between gap-3">
                        <div class="flex min-w-0 items-center gap-3">
                            <x-admin.product-thumbnail
                                :image="$inventoryRow['image']"
                                :alt="$inventoryRow['product']"
                                size="xs"
                            />
                            <div class="min-w-0">
                                <p class="text-sm font-medium text-[#343d37]">{{ $inventoryRow['product'] }}</p>
                            <p class="mt-1 text-xs text-[#929b94]">{{ $inventoryRow['variant'] }}</p>
                            </div>
                        </div>
                        <x-admin.status-badge
                            :label="$inventoryRow['stock_status']"
                            :status-class="$inventoryRow['stock_status_class']"
                        />
                    </div>
                    <dl class="mt-3 grid grid-cols-2 gap-x-4 gap-y-2 text-xs">
                        <div>
                            <dt class="text-[#929b94]">SKU</dt>
                            <dd class="mt-0.5 text-[#5b655e]">{{ $inventoryRow['sku'] }}</dd>
                        </div>
                        <div>
                            <dt class="text-[#929b94]">Categoría</dt>
                            <dd class="mt-0.5 text-[#5b655e]">{{ $inventoryRow['category'] }}</dd>
                        </div>
                        <div>
                            <dt class="text-[#929b94]">Stock</dt>
                            <dd class="mt-0.5 font-medium text-[#343d37]">
                                {{ $inventoryRow['current_stock'] }} / {{ $inventoryRow['minimum_stock'] }} mínimo
                            </dd>
                        </div>
                    </dl>
                    <div class="mt-3 flex justify-end">
                        <a
                            href="{{ route('inventory.movements', ['sku' => $inventoryRow['sku']]) }}"
                            class="aurea-focus rounded-md px-2 py-1.5 text-xs font-medium text-aurea-muted hover:bg-aurea-paper hover:text-aurea-focus"
                            aria-label="Ver movimientos de {{ $inventoryRow['product'] }} {{ $inventoryRow['variant'] }}"
                        >
                            Ver movimientos
                        </a>
                    </div>
                </article>
            @endforeach
        </div>
        @endif
    </section>
@endsection
