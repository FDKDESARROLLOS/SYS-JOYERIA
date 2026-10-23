@extends('layouts.admin', ['title' => 'Productos', 'sectionTitle' => 'Productos'])

@section('content')
    <x-admin.page-header
        :breadcrumbs="[
            ['label' => 'Catálogo', 'url' => route('products.index')],
            ['label' => 'Productos', 'url' => null],
        ]"
        title="Productos"
        description="Gestiona las piezas que forman parte del catálogo de {{ config('aurea-demo.store_settings.store_name', 'Tienda') }}."
    >
        <x-slot:actions>
            @if (auth()->user()?->role === 'admin')
                <a href="{{ route('products.deleted') }}"
                    class="aurea-focus inline-flex w-fit items-center justify-center rounded-lg border border-[#dfe2dc] px-4 py-2.5 text-sm font-medium text-[#68736c] transition hover:border-aurea-gold hover:text-aurea-focus">
                    Productos eliminados
                </a>
            @endif
            <a href="{{ route('products.create') }}"
                class="aurea-focus inline-flex w-fit items-center gap-2 rounded-lg bg-aurea-gold px-4 py-2.5 text-sm font-medium text-white shadow-sm transition hover:bg-[#9f793f]">
                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" aria-hidden="true">
                    <path d="M12 5v14M5 12h14" />
                </svg>
                Nuevo producto
            </a>
        </x-slot:actions>
    </x-admin.page-header>

    <x-admin.flash-messages />

    <section class="mb-7 rounded-2xl border border-aurea-border bg-aurea-surface p-5 shadow-[0_5px_20px_rgba(35,42,37,0.03)] sm:p-6"
        aria-labelledby="product-filters-title">
        <div class="mb-5">
            <h3 id="product-filters-title" class="font-serif text-xl text-[#28302b]">Filtrar productos</h3>
            <p class="mt-1 text-xs text-[#8a938d]">Busca por datos comerciales o filtra por clasificación y estado.</p>
        </div>
        <form method="GET" action="{{ route('products.index') }}" class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
            <label class="sm:col-span-2 lg:col-span-4">
                <span class="mb-2 block text-xs font-medium text-[#68736c]">Buscar producto</span>
                <input type="search" name="search" value="{{ request('search') }}"
                    placeholder="Nombre, modelo, código, SKU o slug"
                    class="aurea-focus w-full rounded-lg border border-[#dfe2dc] bg-aurea-surface px-3 py-2.5 text-sm text-[#56625a] outline-none placeholder:text-[#adb4ae] focus:border-aurea-gold" />
            </label>

            <label>
                <span class="mb-2 block text-xs font-medium text-[#68736c]">Categoría</span>
                <select name="category_id" class="admin-form-input">
                    <option value="">Todas</option>
                    @foreach ($categories as $category)
                        <option value="{{ $category->id }}" @selected((string) request('category_id') === (string) $category->id)>
                            {{ $category->name }}
                        </option>
                    @endforeach
                </select>
            </label>

            <label>
                <span class="mb-2 block text-xs font-medium text-[#68736c]">Colección</span>
                <select name="collection_id" class="admin-form-input">
                    <option value="">Todas</option>
                    @foreach ($collections as $collection)
                        <option value="{{ $collection->id }}" @selected((string) request('collection_id') === (string) $collection->id)>
                            {{ $collection->name }}
                        </option>
                    @endforeach
                </select>
            </label>

            <label>
                <span class="mb-2 block text-xs font-medium text-[#68736c]">Estado</span>
                <select name="status" class="admin-form-input">
                    <option value="">Todos</option>
                    <option value="active" @selected(request('status') === 'active')>Activo</option>
                    <option value="draft" @selected(request('status') === 'draft')>Borrador</option>
                    <option value="inactive" @selected(request('status') === 'inactive')>Inactivo</option>
                </select>
            </label>

            <div class="flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-end">
                <button type="submit"
                    class="aurea-focus inline-flex w-full items-center justify-center rounded-lg bg-aurea-gold px-4 py-2.5 text-sm font-medium text-white shadow-sm transition hover:bg-[#9f793f] sm:w-auto">Aplicar filtros</button>
                <a href="{{ route('products.index') }}"
                    class="aurea-focus inline-flex w-full items-center justify-center rounded-lg border border-[#dfe2dc] px-4 py-2.5 text-sm font-medium text-[#68736c] transition hover:border-aurea-gold hover:text-aurea-focus sm:w-auto">Limpiar</a>
            </div>
        </form>
    </section>

    <section class="overflow-hidden rounded-2xl border border-aurea-border bg-aurea-surface shadow-[0_5px_20px_rgba(35,42,37,0.03)]"
        aria-labelledby="products-list-title">
        <div class="border-b border-[#eef0ec] px-5 py-5 sm:px-6">
            <h3 id="products-list-title" class="font-serif text-xl text-[#28302b]">Listado de productos</h3>
            <p class="mt-1 text-xs text-[#8a938d]">Mostrando {{ $products->total() }} productos.</p>
        </div>

        @if ($products->isEmpty())
            <x-admin.empty-state
                title="No hay productos todavía"
                description="Agrega un producto para comenzar a construir tu catálogo."
                :action-url="route('products.create')"
                action-label="Agregar producto"
            />
        @else
            <div class="hidden overflow-x-auto sm:block">
                <table class="admin-table w-full min-w-190 text-left text-sm">
                    <caption class="sr-only">Listado de productos</caption>
                    <thead class="bg-[#fbfbf9] text-[10px] uppercase tracking-wider text-[#9ca49e]">
                        <tr>
                            <th scope="col" class="px-5 py-3 font-medium sm:px-6">Producto</th>
                            <th scope="col" class="px-3 py-3 font-medium">Código</th>
                            <th scope="col" class="px-3 py-3 font-medium">Categoría</th>
                            <th scope="col" class="px-3 py-3 font-medium">Colección</th>
                            <th scope="col" class="px-3 py-3 font-medium">Precio</th>
                            <th scope="col" class="px-3 py-3 font-medium">Estado</th>
                            <th scope="col" class="px-5 py-3 text-right font-medium sm:px-6">Acciones</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-[#f0f1ed]">
                        @foreach ($products as $product)
                            @php
                                $productImage = $product->images->first();
                            @endphp
                            <tr class="text-[#5b655e] hover:bg-[#fbfbf9]">
                                <td class="px-5 py-4 sm:px-6">
                                    <div class="flex items-center gap-3">
                                        <div class="flex h-11 w-11 shrink-0 items-center justify-center overflow-hidden rounded-lg border border-[#e7e2da] bg-[#f7f3ec] dark:border-[#4b4a45] dark:bg-[#292b29]">
                                            @if ($productImage?->hasPublicFile())
                                                <img src="{{ $productImage->publicUrl() }}" alt="{{ $productImage->alt ?: $product->name }}"
                                                    class="block h-full w-full object-cover" />
                                            @else
                                                <span class="text-xs text-[#a6ada7] dark:text-[#777d77]" aria-hidden="true">—</span>
                                                <span class="sr-only">Sin imagen</span>
                                            @endif
                                        </div>
                                        <div class="min-w-0">
                                            <p class="font-medium text-[#343d37]">{{ $product->name }}</p>
                                            <p class="mt-1 text-[11px] text-[#929b94]">{{ $product->model ?: 'Sin modelo' }}</p>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-3 py-4 text-xs text-[#929b94]">{{ $product->code }}</td>
                                <td class="px-3 py-4">{{ $product->category?->name ?? 'Sin categoría' }}</td>
                                <td class="px-3 py-4">{{ $product->collection?->name ?? 'Sin colección' }}</td>
                                <td class="px-3 py-4 font-medium text-[#343d37]">${{ number_format((float) $product->price, 2) }}</td>
                                <td class="px-3 py-4">
                                    <x-admin.status-badge
                                        :label="match ($product->status) {
                                            'active' => 'Activo',
                                            'inactive' => 'Inactivo',
                                            default => 'Borrador',
                                        }"
                                        :status-class="match ($product->status) {
                                            'active' => 'text-[#5b8668]',
                                            'inactive' => 'text-[#a27a3f]',
                                            default => 'text-[#806441]',
                                        }"
                                    />
                                </td>
                                <td class="px-5 py-4 text-right sm:px-6">
                                    <x-admin.row-actions
                                        :label="$product->name"
                                        :edit-url="route('products.edit', $product)"
                                        :secondary-url="route('products.variants.index', $product)"
                                         secondary-label="Variantes"
                                         delete-label="producto"
                                         :delete-stock="$product->physical_stock"
                                         :delete-url="route('products.destroy', $product)"
                                    />
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="divide-y divide-[#f0f1ed] sm:hidden">
                        @foreach ($products as $product)
                            @php
                                $productImage = $product->images->first();
                            @endphp
                            <article class="px-5 py-4">
                                <div class="flex items-start justify-between gap-3">
                                    <div class="flex min-w-0 items-center gap-3">
                                        <div class="flex h-10 w-10 shrink-0 items-center justify-center overflow-hidden rounded-lg border border-[#e7e2da] bg-[#f7f3ec] dark:border-[#4b4a45] dark:bg-[#292b29]">
                                            @if ($productImage?->hasPublicFile())
                                                <img src="{{ $productImage->publicUrl() }}" alt="{{ $productImage->alt ?: $product->name }}"
                                                    class="block h-full w-full object-cover" />
                                            @else
                                                <span class="text-xs text-[#a6ada7] dark:text-[#777d77]" aria-hidden="true">—</span>
                                                <span class="sr-only">Sin imagen</span>
                                            @endif
                                        </div>
                                        <div class="min-w-0">
                                            <p class="text-sm font-medium text-[#343d37]">{{ $product->name }}</p>
                                            <p class="mt-1 text-xs text-[#929b94]">{{ $product->code }} · {{ $product->category?->name ?? 'Sin categoría' }}</p>
                                        </div>
                                    </div>
                                    <x-admin.status-badge
                                :label="$product->status === 'active' ? 'Activo' : ($product->status === 'inactive' ? 'Inactivo' : 'Borrador')"
                                :status-class="$product->status === 'active' ? 'text-[#5b8668]' : ($product->status === 'inactive' ? 'text-[#a27a3f]' : 'text-[#806441]' )"
                            />
                        </div>
                        <div class="mt-3 flex items-end justify-between gap-3">
                            <div>
                                <p class="text-sm font-medium text-[#343d37]">${{ number_format((float) $product->price, 2) }}</p>
                                <p class="mt-1 text-[11px] text-[#929b94]">{{ $product->collection?->name ?? 'Sin colección' }}</p>
                            </div>
                            <x-admin.row-actions
                                :label="$product->name"
                                :edit-url="route('products.edit', $product)"
                                 :secondary-url="route('products.variants.index', $product)"
                                 secondary-label="Variantes"
                                 delete-label="producto"
                                 :delete-stock="$product->physical_stock"
                                 :delete-url="route('products.destroy', $product)"
                            />
                        </div>
                    </article>
                @endforeach
            </div>

            <div class="border-t border-[#eef0ec] px-5 py-4 sm:px-6">
                {{ $products->links() }}
            </div>
        @endif
    </section>
@endsection
