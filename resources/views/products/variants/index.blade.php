@extends('layouts.admin', ['title' => 'Variantes', 'sectionTitle' => 'Variantes'])

@section('content')
    @php
        $baseValues = [
            'material' => $productModel->material?->name ?? '—',
            'size' => $productModel->baseSize?->name ?? '—',
            'stone' => $productModel->productStones
                ->pluck('stone.name')
                ->filter()
                ->unique()
                ->implode(', ') ?: '—',
            'karat' => $productModel->karat ? $productModel->karat . 'K' : '—',
            'metal_color' => $productModel->metal_color ?: '—',
            'cost' => $productModel->cost !== null ? '$' . number_format((float) $productModel->cost, 2) . ' MXN' : '—',
            'price' => $productModel->price !== null ? '$' . number_format((float) $productModel->price, 2) . ' MXN' : '—',
            'sale_price' => $productModel->sale_price !== null
                ? '$' . number_format((float) $productModel->sale_price, 2) . ' MXN'
                : '—',
        ];
        $isInherited = fn ($variant, $key): bool => $variant->{$key} === null;
        $effectiveValue = fn ($variant, $key): string => $isInherited($variant, $key)
            ? $baseValues[$key]
            : ($key === 'karat' ? $variant->{$key} . 'K' : (in_array($key, ['cost', 'price', 'sale_price'], true)
                ? '$' . number_format((float) $variant->{$key}, 2) . ' MXN'
                : (string) $variant->{$key}));
    @endphp

    <x-admin.page-header
        :breadcrumbs="[
            ['label' => 'Productos', 'url' => route('products.index')],
            ['label' => $product['name'], 'url' => route('products.edit', $productModel)],
            ['label' => 'Variantes', 'url' => null],
        ]"
        title="Variantes"
        description="Administra las diferentes versiones comerciales de este producto."
    />

    <x-admin.flash-messages />

    <x-admin.product-context
        :product="$product"
        :edit-url="route('products.edit', $productModel)"
    />

    <section
        class="overflow-hidden rounded-2xl border border-aurea-border bg-aurea-surface shadow-[0_5px_20px_rgba(35,42,37,0.03)]"
        aria-labelledby="variants-list-title">
        <div
            class="flex flex-col justify-between gap-3 border-b border-[#eef0ec] px-5 py-5 sm:flex-row sm:items-center sm:px-6">
            <div>
                <h3 id="variants-list-title" class="font-serif text-xl text-[#28302b]">Variantes del producto</h3>
                <p class="mt-1 text-xs text-[#8a938d]">{{ $productVariants->total() }} versiones configuradas</p>
            </div>
            <div class="flex flex-wrap items-center gap-3">
                <p class="text-xs text-[#9ca49e]">El stock se administra desde Inventario.</p>
                <a href="{{ route('products.variants.create', $productModel) }}"
                    class="aurea-focus inline-flex w-fit items-center gap-2 rounded-lg bg-aurea-gold px-3 py-2 text-xs font-medium text-white shadow-sm hover:bg-[#9f793f]">
                    <span class="text-base leading-none">+</span>
                    Nueva variante
                </a>
            </div>
        </div>

        @if ($productVariants->isEmpty())
            <x-admin.empty-state
                title="No hay variantes todavía"
                description="Agrega una variante para definir otra versión comercial del producto."
                :action-url="route('products.variants.create', $productModel)"
                action-label="Agregar variante"
            />
        @else
            <div class="hidden overflow-x-auto sm:block">
                <table class="admin-table w-full min-w-220 text-left text-sm">
                    <caption class="sr-only">Variantes de {{ $product['name'] }}</caption>
                    <thead class="bg-[#fbfbf9] text-[10px] uppercase tracking-wider text-[#9ca49e]">
                        <tr>
                            <th scope="col" class="px-5 py-3 font-medium sm:px-6">SKU</th>
                            <th scope="col" class="px-3 py-3 font-medium">Talla</th>
                            <th scope="col" class="px-3 py-3 font-medium">Material</th>
                            <th scope="col" class="px-3 py-3 font-medium">Kilataje</th>
                            <th scope="col" class="px-3 py-3 font-medium">Color</th>
                            <th scope="col" class="px-3 py-3 font-medium">Piedra</th>
                            <th scope="col" class="px-3 py-3 font-medium">Precio</th>
                            <th scope="col" class="px-3 py-3 font-medium">Estado</th>
                            <th scope="col" class="px-5 py-3 text-right font-medium sm:px-6">Acciones</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-[#f0f1ed]">
                        @foreach ($productVariants as $variant)
                            <tr class="text-[#5b655e] hover:bg-[#fbfbf9]">
                                <td class="px-5 py-4 sm:px-6">
                                    <div class="flex items-center gap-3">
                                        <x-admin.product-thumbnail :image="$product['imagePath']" :alt="$product['name']" size="xs" />
                                        <span class="font-medium text-[#343d37]">{{ $variant->sku }}</span>
                                    </div>
                                </td>
                                <td class="px-3 py-4">{{ $variant->size?->name ?? $baseValues['size'] }}</td>
                                <td class="px-3 py-4">{{ $variant->material?->name ?? $baseValues['material'] }}</td>
                                <td class="px-3 py-4">{{ $effectiveValue($variant, 'karat') }}</td>
                                <td class="px-3 py-4">{{ $effectiveValue($variant, 'metal_color') }}</td>
                                <td class="px-3 py-4">{{ $variant->stone?->name ?? $baseValues['stone'] }}</td>
                                <td class="px-3 py-4 font-medium text-[#343d37]">{{ $effectiveValue($variant, 'price') }}</td>
                                <td class="px-3 py-4">
                                    <x-admin.status-badge
                                        :label="match ($variant->status) {
                                            'active' => 'Activo',
                                            'inactive' => 'Inactivo',
                                            default => 'Borrador',
                                        }"
                                        :status-class="match ($variant->status) {
                                            'active' => 'text-[#5b8668]',
                                            'inactive' => 'text-[#a27a3f]',
                                            default => 'text-[#806441]',
                                        }"
                                    />
                                </td>
                                <td class="px-5 py-4 text-right sm:px-6">
                                    <x-admin.row-actions
                                        :label="$variant->sku"
                                        :edit-url="route('products.variants.edit', [$productModel, $variant])"
                                        delete-label="variante"
                                        :delete-url="route('products.variants.destroy', [$productModel, $variant])"
                                    />
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="divide-y divide-[#f0f1ed] sm:hidden">
                @foreach ($productVariants as $variant)
                    <article class="px-5 py-4">
                        <div class="flex items-start justify-between gap-3">
                            <div class="flex min-w-0 items-center gap-3">
                                <x-admin.product-thumbnail :image="$product['imagePath']" :alt="$product['name']" size="xs" />
                                <div class="min-w-0">
                                    <p class="text-sm font-medium text-[#343d37]">{{ $variant->sku }}</p>
                                    <p class="mt-1 text-xs text-[#929b94]">
                                        Talla {{ $variant->size?->name ?? $baseValues['size'] }} · {{ $effectiveValue($variant, 'metal_color') }} · {{ $variant->stone?->name ?? $baseValues['stone'] }}
                                    </p>
                                </div>
                            </div>
                            <x-admin.status-badge
                                :label="$variant->status === 'active' ? 'Activo' : ($variant->status === 'inactive' ? 'Inactivo' : 'Borrador')"
                                :status-class="$variant->status === 'active' ? 'text-[#5b8668]' : ($variant->status === 'inactive' ? 'text-[#a27a3f]' : 'text-[#806441]')"
                            />
                        </div>
                        <div class="mt-3 flex items-end justify-between gap-3">
                            <div class="text-sm text-[#343d37]">
                                <span class="font-medium">{{ $effectiveValue($variant, 'price') }}</span>
                            </div>
                            <x-admin.row-actions
                                :label="$variant->sku"
                                :edit-url="route('products.variants.edit', [$productModel, $variant])"
                                delete-label="variante"
                                :delete-url="route('products.variants.destroy', [$productModel, $variant])"
                            />
                        </div>
                    </article>
                @endforeach
            </div>

            <div class="border-t border-[#eef0ec] px-5 py-4 sm:px-6">
                {{ $productVariants->links() }}
            </div>
        @endif
    </section>
@endsection
