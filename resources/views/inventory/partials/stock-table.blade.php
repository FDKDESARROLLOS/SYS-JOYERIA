@props([
    'inventoryRows',
    'inventoryStocks',
    'showStatus' => true,
])

<div class="hidden overflow-x-auto sm:block">
    <table class="admin-table w-full min-w-220 text-left text-sm">
        <caption class="sr-only">Existencias actuales de productos y variantes</caption>
        <thead class="bg-[#fbfbf9] text-[10px] uppercase tracking-wider text-[#9ca49e]">
            <tr>
                <th scope="col" class="px-5 py-3 font-medium sm:px-6">Producto</th>
                <th scope="col" class="px-3 py-3 font-medium">Características</th>
                <th scope="col" class="px-3 py-3 font-medium">SKU</th>
                <th scope="col" class="px-3 py-3 font-medium">Categoría</th>
                <th scope="col" class="px-3 py-3 font-medium">Stock actual</th>
                @if ($showStatus)
                    <th scope="col" class="px-3 py-3 font-medium">Estado</th>
                @endif
                <th scope="col" class="px-5 py-3 text-right font-medium sm:px-6">Acciones</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-[#f0f1ed]">
            @foreach ($inventoryRows as $inventoryRow)
                <tr class="text-[#5b655e] hover:bg-[#fbfbf9]">
                    <td class="px-5 py-4 sm:px-6">
                        <div class="flex items-center gap-3">
                            <x-admin.product-thumbnail :image="$inventoryRow['image']" :alt="$inventoryRow['product']" size="xs" />
                            <div>
                                <p class="font-medium text-[#343d37]">{{ $inventoryRow['product'] }}</p>
                                @if ($showStatus && $inventoryRow['is_out_of_catalog'])
                                    <p class="mt-1 text-[10px] font-medium uppercase tracking-wide text-[#947142]">Fuera de catálogo</p>
                                @endif
                            </div>
                        </div>
                    </td>
                    <td class="px-3 py-4">{{ $inventoryRow['variant'] }}</td>
                    <td class="px-3 py-4 text-xs text-[#929b94]">{{ $inventoryRow['sku'] }}</td>
                    <td class="px-3 py-4">{{ $inventoryRow['category'] }}</td>
                    <td
                        class="px-3 py-4 font-medium {{ $inventoryRow['current_stock'] === 0 ? 'text-aurea-danger' : 'text-[#343d37]' }}">
                        {{ $inventoryRow['current_stock'] }}
                    </td>
                    @if ($showStatus)
                        <td class="px-3 py-4">
                            <x-admin.status-badge :label="$inventoryRow['stock_status']" :status-class="$inventoryRow['stock_status_class']" />
                        </td>
                    @endif
                    <td class="px-5 py-4 text-right sm:px-6">
                        <a href="{{ route('inventory.movements.show', $inventoryRow['inventory_stock_id']) }}"
                            class="aurea-focus rounded-md px-2 py-1.5 text-xs font-medium text-aurea-muted hover:bg-aurea-paper hover:text-aurea-focus"
                            aria-label="Ver movimientos de {{ $inventoryRow['product'] }} {{ $inventoryRow['variant'] }}">
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
                    <x-admin.product-thumbnail :image="$inventoryRow['image']" :alt="$inventoryRow['product']" size="xs" />
                    <div class="min-w-0">
                        <p class="text-sm font-medium text-[#343d37]">{{ $inventoryRow['product'] }}</p>
                        @if ($showStatus && $inventoryRow['is_out_of_catalog'])
                            <p class="mt-1 text-[10px] font-medium uppercase tracking-wide text-[#947142]">Fuera de catálogo</p>
                        @endif
                        <p class="mt-1 text-xs text-[#929b94]">{{ $inventoryRow['variant'] }}</p>
                    </div>
                </div>
                @if ($showStatus)
                    <x-admin.status-badge :label="$inventoryRow['stock_status']" :status-class="$inventoryRow['stock_status_class']" />
                @endif
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
                    <dd class="mt-0.5 font-medium text-[#343d37]">{{ $inventoryRow['current_stock'] }}</dd>
                </div>
            </dl>
            <div class="mt-3 flex justify-end">
                <a href="{{ route('inventory.movements.show', $inventoryRow['inventory_stock_id']) }}"
                    class="aurea-focus rounded-md px-2 py-1.5 text-xs font-medium text-aurea-muted hover:bg-aurea-paper hover:text-aurea-focus"
                    aria-label="Ver movimientos de {{ $inventoryRow['product'] }} {{ $inventoryRow['variant'] }}">
                    Ver movimientos
                </a>
            </div>
        </article>
    @endforeach
</div>

<div class="border-t border-[#eef0ec] px-5 py-4 sm:px-6">
    {{ $inventoryStocks->links() }}
</div>
