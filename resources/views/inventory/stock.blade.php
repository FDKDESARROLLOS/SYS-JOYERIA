@extends('layouts.admin', ['title' => 'Existencias', 'sectionTitle' => 'Existencias'])

@section('content')
    <x-admin.page-header :breadcrumbs="[
        [
            'label' => 'Inventario',
            'url' => null,
        ],
        [
            'label' => 'Existencias',
            'url' => null,
        ],
    ]" title="Existencias"
        description="Consulta el stock actual de tus productos y variantes sin modificar sus cantidades." />

    <section
        class="mb-7 rounded-2xl border border-aurea-border bg-aurea-surface p-5 shadow-[0_5px_20px_rgba(35,42,37,0.03)] sm:p-6"
        aria-labelledby="stock-filters-title">
        <div class="mb-5">
            <h3 id="stock-filters-title" class="font-serif text-xl text-[#28302b]">Filtrar existencias</h3>
            <p class="mt-1 text-xs text-[#8a938d]">Encuentra rápidamente un producto o revisa los niveles de stock.</p>
        </div>
        <form method="GET" action="{{ route('inventory.stock') }}" class="grid gap-4 sm:grid-cols-3">
            <label>
                <span class="mb-2 block text-xs font-medium text-[#68736c]">Buscar por nombre o SKU</span>
                <input type="search" name="search" value="{{ request('search') }}"
                    placeholder="Ej. Anillo Aura o AU-1048-T7"
                    class="aurea-focus w-full rounded-lg border border-[#dfe2dc] bg-aurea-surface px-3 py-2.5 text-sm text-[#56625a] outline-none placeholder:text-[#adb4ae] focus:border-aurea-gold" />
            </label>
            <x-admin.filter-select label="Categoría" name="category_id" :options="$categories->pluck('name', 'id')->all()" :auto-submit="true" />
            <x-admin.filter-select label="Estado de stock" name="stock_status" :options="[
                'available' => 'Disponible',
                'out' => 'Sin stock',
            ]" :auto-submit="true" />
        </form>
    </section>

    <section
        class="overflow-hidden rounded-2xl border border-aurea-border bg-aurea-surface shadow-[0_5px_20px_rgba(35,42,37,0.03)]"
        aria-labelledby="active-stock-list-title">
        <div class="border-b border-[#eef0ec] px-5 py-5 sm:px-6">
            <h3 id="active-stock-list-title" class="font-serif text-xl text-[#28302b]">Existencias activas</h3>
            <p class="mt-1 text-xs text-[#8a938d]">Mostrando {{ $inventoryStocks->total() }} productos y variantes.
            </p>
        </div>

        @if ($inventoryRows->isEmpty())
            <x-admin.empty-state title="No hay existencias todavía"
                description="El stock actual de productos y variantes aparecerá aquí." />
        @else
            @include('inventory.partials.stock-table', [
                'inventoryRows' => $inventoryRows,
                'inventoryStocks' => $inventoryStocks,
            ])
        @endif
    </section>

    @if ($archivedInventoryRows->isNotEmpty())
        <section
            class="mt-7 overflow-hidden rounded-2xl border border-aurea-border bg-aurea-surface shadow-[0_5px_20px_rgba(35,42,37,0.03)]"
            aria-labelledby="archived-stock-list-title">
            <div class="border-b border-[#eef0ec] px-5 py-5 sm:px-6">
                <h3 id="archived-stock-list-title" class="font-serif text-xl text-[#28302b]">Existencias fuera de catálogo</h3>
                <p class="mt-1 text-xs text-[#8a938d]">Estos artículos ya no están disponibles en el catálogo, pero todavía tienen existencias físicas.</p>
            </div>

            @include('inventory.partials.stock-table', [
                'inventoryRows' => $archivedInventoryRows,
                'inventoryStocks' => $archivedInventoryStocks,
                'showStatus' => false,
            ])
        </section>
    @endif
@endsection
