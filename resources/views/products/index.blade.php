@extends('layouts.admin', ['title' => 'Productos', 'sectionTitle' => 'Productos'])

@section('content')
    @php
        $productFilters = config('aurea-demo.product_filters');
        $catalogProducts = config('aurea-demo.catalog_products');
        $catalogSummary = config('aurea-demo.catalog_summary');
    @endphp

    <div class="mb-8 flex flex-col justify-between gap-4 sm:flex-row sm:items-end">
        <div>
            <div class="mb-2 flex flex-wrap items-center gap-2">
                <p class="text-sm text-aurea-muted">Catálogo / Productos</p>

            </div>
            <h2 class="font-serif text-3xl text-aurea-ink">Productos</h2>
            <p class="mt-2 max-w-xl text-sm text-aurea-muted">
                Gestiona las piezas que forman parte de la colección de {{ config('aurea-demo.store_settings.store_name', 'Tienda') }}, desde su publicación hasta su disponibilidad.
            </p>
        </div>
        <a
            href="{{ route('products.create') }}"
            class="aurea-focus inline-flex w-fit items-center gap-2 rounded-lg bg-aurea-gold px-4 py-2.5 text-sm font-medium text-white shadow-sm transition hover:bg-[#9f793f]"
        >
            <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7"
                aria-hidden="true">
                <path d="M12 5v14M5 12h14" />
            </svg>
            Añadir producto
        </a>
    </div>

    <section class="mb-7 grid gap-4 sm:grid-cols-2 xl:grid-cols-4" aria-label="Resumen del catálogo">
        @foreach ($catalogSummary as $summary)
            <article
                class="rounded-2xl border border-aurea-border bg-aurea-surface p-5 shadow-[0_5px_20px_rgba(35,42,37,0.03)]">
                <div class="flex items-start justify-between gap-3">
                    <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-[#f2eadc] text-[#a67b3e]">
                        <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"
                            aria-hidden="true">
                            <path d="{{ $summary['icon'] }}" />
                        </svg>
                    </div>
                    @if ($loop->last)
                        <span
                            class="rounded-full bg-[#f9e9e6] px-2 py-1 text-[10px] font-semibold text-aurea-danger">Atención</span>
                    @endif
                </div>
                <p class="mt-5 text-sm text-[#7b847e]">{{ $summary['label'] }}</p>
                <p class="mt-1 text-2xl font-semibold tracking-tight text-[#252a27]">{{ $summary['value'] }}</p>
                <p class="mt-1 text-[11px] text-[#a0a7a2]">{{ $summary['detail'] }}</p>
            </article>
        @endforeach
    </section>

    <section data-filter-section
        class="mb-7 rounded-2xl border border-aurea-border bg-aurea-surface p-5 shadow-[0_5px_20px_rgba(35,42,37,0.03)] sm:p-6"
        aria-labelledby="filters-title">
        <div class="mb-5 flex flex-col justify-between gap-2 sm:flex-row sm:items-center">
            <div>
                <h3 id="filters-title" class="font-serif text-xl text-[#28302b]">Filtrar catálogo</h3>
                <p class="mt-1 text-xs text-[#8a938d]">Encuentra rápidamente una pieza por sus características.</p>
            </div>
            <button type="button" data-clear-filters class="aurea-focus w-fit cursor-pointer text-xs font-medium text-[#aa8046] hover:text-[#80602f]">Limpiar
                filtros</button>
        </div>
        <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
            <label class="sm:col-span-2 xl:col-span-2">
                <span class="mb-2 block text-xs font-medium text-[#68736c]">
                    Buscar por nombre o SKU
                </span>
                <div
                    class="flex items-center gap-2 rounded-lg border border-[#dfe2dc] px-3 py-2.5 focus-within:border-aurea-gold focus-within:ring-2 focus-within:ring-aurea-gold-soft/20">
                    <svg class="h-4 w-4 shrink-0 text-[#8c958f]" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                        stroke-width="1.7" aria-hidden="true">
                        <circle cx="11" cy="11" r="6.5" />
                        <path d="m16 16 4 4" />
                    </svg>
                    <input type="search" placeholder="Ej. Anillo Aura o AU-1048"
                        class="min-w-0 flex-1 border-0 bg-transparent p-0 text-sm text-[#343d37] outline-none placeholder:text-[#adb4ae]" />
                </div>
            </label>
            @foreach ($productFilters as $productFilter)
                <x-admin.filter-select :label="$productFilter['label']" :options="$productFilter['options']" />
            @endforeach
            <label>
                <span class="mb-2 block text-xs font-medium text-[#68736c]">Precio desde</span>
                <input type="text" placeholder="$ 0"
                    class="aurea-focus w-full rounded-lg border border-[#dfe2dc] bg-white px-3 py-2.5 text-sm text-[#56625a] outline-none placeholder:text-[#adb4ae] focus:border-aurea-gold" />
            </label>
            <label>
                <span class="mb-2 block text-xs font-medium text-[#68736c]">Precio hasta</span>
                <input type="text" placeholder="$ 5,000"
                    class="aurea-focus w-full rounded-lg border border-[#dfe2dc] bg-white px-3 py-2.5 text-sm text-[#56625a] outline-none placeholder:text-[#adb4ae] focus:border-aurea-gold" />
            </label>
        </div>
    </section>

    <section
        class="overflow-hidden rounded-2xl border border-aurea-border bg-aurea-surface shadow-[0_5px_20px_rgba(35,42,37,0.03)]"
        aria-labelledby="products-list-title">
        <div
            class="flex flex-col justify-between gap-3 border-b border-[#eef0ec] px-5 py-5 sm:flex-row sm:items-center sm:px-6">
            <div>
                <h3 id="products-list-title" class="font-serif text-xl text-[#28302b]">Catálogo</h3>
                <p class="mt-1 text-xs text-[#8a938d]">Mostrando 4 de 324 productos</p>
            </div>
            <button type="button"
                class="aurea-focus w-fit rounded-md border border-[#dfe2dc] px-3 py-2 text-xs text-[#68736c] hover:border-aurea-gold hover:text-aurea-gold">
                Ordenar: Más recientes
            </button>
        </div>
        @if (empty($catalogProducts))
            <x-admin.empty-state
                title="No hay productos todavía"
                description="Agrega tu primer producto para comenzar."
                :action-url="route('products.create')"
                action-label="Agregar producto"
            />
        @else
        <div class="hidden overflow-x-auto sm:block">
            <table class="admin-table w-full min-w-190 text-left text-sm">
                <caption class="sr-only">Listado de productos del catálogo de {{ config('aurea-demo.store_settings.store_name', 'Tienda') }}</caption>
                <thead class="bg-[#fbfbf9] text-[10px] uppercase tracking-wider text-[#9ca49e]">
                    <tr>
                        <th scope="col" class="px-5 py-3 font-medium sm:px-6">Producto</th>
                        <th scope="col" class="px-3 py-3 font-medium">Código</th>
                        <th scope="col" class="px-3 py-3 font-medium">SKU base</th>
                        <th scope="col" class="px-3 py-3 font-medium">Categoría</th>
                        <th scope="col" class="px-3 py-3 font-medium">Inventario</th>
                        <th scope="col" class="px-3 py-3 font-medium">Precio</th>
                        <th scope="col" class="px-3 py-3 font-medium">Estado</th>
                        <th scope="col" class="px-5 py-3 text-right font-medium sm:px-6">Acciones</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-[#f0f1ed]">
                    @foreach ($catalogProducts as $product)
                        <tr class="text-[#5b655e] hover:bg-[#fbfbf9]">
                            <td class="px-5 py-4 sm:px-6">
                                <div class="flex items-center gap-3">
                                     <x-admin.product-thumbnail
                                         :image="$product['image'] ?? null"
                                         :alt="$product['name']"
                                         size="xs"
                                     />
                                    <div class="min-w-0">
                                        <p class="font-medium text-[#343d37]">{{ $product['name'] }}</p>
                                        <p class="mt-1 text-[11px] text-[#929b94]">
                                            {{ $product['material'] }}
                                            @if ($product['featured'])
                                                · Destacado
                                            @endif
                                        </p>
                                    </div>
                                </div>
                            </td>
                            <td class="px-3 py-4 text-xs text-[#929b94]">{{ $product['code'] }}</td>
                            <td class="px-3 py-4 text-xs text-[#929b94]">{{ $product['base_sku'] }}</td>
                            <td class="px-3 py-4">{{ $product['category'] }}</td>
                            <td class="px-3 py-4 text-xs font-medium {{ $product['stockClass'] }}">{{ $product['stock'] }}
                            </td>
                            <td class="px-3 py-4 font-medium text-[#343d37]">{{ $product['price'] }}</td>
                            <td class="px-3 py-4">
                                <x-admin.status-badge :label="$product['status']" :status-class="$product['statusClass']" />
                            </td>
                            <td class="px-5 py-4 text-right sm:px-6">
                                <x-admin.row-actions
                                    :label="$product['name']"
                                    :edit-url="route('products.edit', ['id' => $product['base_sku']])"
                                     :secondary-url="route('products.variants.index', ['id' => $product['base_sku']])"
                                     secondary-label="Variantes"
                                     delete-label="producto"
                                />
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <div class="divide-y divide-[#f0f1ed] sm:hidden">
            @foreach ($catalogProducts as $product)
                <article class="px-5 py-4">
                    <div class="flex items-start gap-3">
                         <x-admin.product-thumbnail
                             :image="$product['image'] ?? null"
                             :alt="$product['name']"
                             size="sm"
                         />
                        <div class="min-w-0 flex-1">
                            <div class="flex items-start justify-between gap-3">
                                <div>
                                    <p class="text-sm font-medium text-[#343d37]">{{ $product['name'] }}</p>
                                    <p class="mt-1 text-xs text-[#929b94]">
                                     Código {{ $product['code'] }} · SKU {{ $product['base_sku'] }} · {{ $product['material'] }}
                                    </p>
                                </div>
                                <x-admin.status-badge :label="$product['status']" :status-class="$product['statusClass']" />
                            </div>
                        </div>
                    </div>
                    <div class="mt-3 flex items-end justify-between gap-3">
                        <div>
                            <p class="text-sm font-medium text-[#343d37]">{{ $product['price'] }}</p>
                            <p class="mt-1 text-[11px] {{ $product['stockClass'] }}">{{ $product['stock'] }}</p>
                        </div>
                        <x-admin.row-actions
                            :label="$product['name']"
                            :edit-url="route('products.edit', ['id' => $product['base_sku']])"
                            :secondary-url="route('products.variants.index', ['id' => $product['base_sku']])"
                            secondary-label="Variantes"
                            delete-label="producto"
                        />
                    </div>
                </article>
            @endforeach
        </div>
        <div
            class="flex flex-col gap-3 border-t border-[#eef0ec] px-5 py-4 text-xs text-[#929b94] sm:flex-row sm:items-center sm:justify-between sm:px-6">
            <span>Página 1 de 81</span>
            <div class="flex items-center gap-1">
                <button type="button" disabled
                    class="aurea-focus cursor-not-allowed rounded-md border border-[#eef0ec] px-3 py-2 text-[#c1c7c2]">Anterior</button>
                <button type="button"
                    class="aurea-focus cursor-pointer rounded-md border border-aurea-gold bg-[#f8f1e5] px-3 py-2 font-medium text-[#947142]"
                    aria-current="page">1</button>
                <button type="button"
                    class="aurea-focus cursor-pointer rounded-md border border-[#eef0ec] px-3 py-2 hover:border-aurea-gold hover:text-aurea-focus">2</button>
                <button type="button"
                    class="aurea-focus cursor-pointer rounded-md border border-[#eef0ec] px-3 py-2 hover:border-aurea-gold hover:text-aurea-focus">Siguiente</button>
            </div>
        </div>
        @endif
    </section>
@endsection
