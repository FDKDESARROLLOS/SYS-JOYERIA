@extends('layouts.admin', ['title' => 'Variantes eliminadas', 'sectionTitle' => 'Variantes eliminadas'])

@section('content')
    @php
        $baseValues = [
            'material' => $productModel->material?->name ?? '—',
            'size' => $productModel->baseSize?->name ?? '—',
            'karat' => $productModel->karat ? $productModel->karat . 'K' : '—',
            'metal_color' => $productModel->metal_color ?: '—',
        ];
        $effectiveValue = fn ($variant, $key): string => $variant->{$key} === null
            ? $baseValues[$key]
            : ($key === 'karat' ? $variant->{$key} . 'K' : (string) $variant->{$key});
    @endphp

    <x-admin.page-header
        :breadcrumbs="[
            ['label' => 'Productos', 'url' => route('products.index')],
            ['label' => $product['name'], 'url' => route('products.edit', $productModel)],
            ['label' => 'Variantes eliminadas', 'url' => null],
        ]"
        title="Variantes eliminadas"
        description="Consulta y restaura variantes retiradas de este producto."
    >
        <x-slot:actions>
            <a href="{{ route('products.variants.index', $productModel) }}"
                class="aurea-focus inline-flex w-fit items-center justify-center rounded-lg border border-[#dfe2dc] px-4 py-2.5 text-sm font-medium text-[#68736c] hover:border-aurea-gold hover:text-aurea-focus">
                Volver a variantes
            </a>
        </x-slot:actions>
    </x-admin.page-header>

    <x-admin.flash-messages />

    <x-admin.product-context
        :product="$product"
        :edit-url="route('products.edit', $productModel)"
    />

    <section class="overflow-hidden rounded-2xl border border-aurea-border bg-aurea-surface shadow-[0_5px_20px_rgba(35,42,37,0.03)]"
        aria-labelledby="deleted-variants-list-title">
        <div class="border-b border-[#eef0ec] px-5 py-5 sm:px-6">
            <h3 id="deleted-variants-list-title" class="font-serif text-xl text-[#28302b]">Variantes eliminadas</h3>
            <p class="mt-1 text-xs text-[#8a938d]">{{ $productVariants->total() }} variantes retiradas de este producto.</p>
        </div>

        @if ($productVariants->isEmpty())
            <x-admin.empty-state
                title="No hay variantes eliminadas"
                description="Las variantes retiradas de este producto aparecerán aquí."
            />
        @else
            <div class="hidden overflow-x-auto sm:block">
                <table class="admin-table w-full min-w-190 text-left text-sm">
                    <caption class="sr-only">Variantes eliminadas de {{ $product['name'] }}</caption>
                    <thead class="bg-[#fbfbf9] text-[10px] uppercase tracking-wider text-[#9ca49e]">
                        <tr>
                            <th scope="col" class="px-5 py-3 font-medium sm:px-6">SKU</th>
                            <th scope="col" class="px-3 py-3 font-medium">Características</th>
                            <th scope="col" class="px-3 py-3 font-medium">Stock actual</th>
                            <th scope="col" class="px-3 py-3 font-medium">Eliminada el</th>
                            <th scope="col" class="px-5 py-3 text-right font-medium sm:px-6">Acciones</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-[#f0f1ed]">
                        @foreach ($productVariants as $variant)
                            <tr class="text-[#5b655e] hover:bg-[#fbfbf9]">
                                <td class="px-5 py-4 font-medium text-[#343d37] sm:px-6">{{ $variant->sku }}</td>
                                <td class="px-3 py-4">
                                    {{ $variant->size?->name ?? $baseValues['size'] }} ·
                                    {{ $variant->material?->name ?? $baseValues['material'] }} ·
                                    {{ $effectiveValue($variant, 'karat') }} ·
                                    {{ $effectiveValue($variant, 'metal_color') }} ·
                                    {{ $variant->stone?->name ?? '—' }}
                                </td>
                                <td class="px-3 py-4 font-medium {{ $variant->inventoryStock?->current_stock > 0 ? 'text-[#5b8668]' : 'text-[#929b94]' }}">
                                    {{ $variant->inventoryStock?->current_stock ?? 0 }}
                                    @if (($variant->inventoryStock?->current_stock ?? 0) > 0)
                                        <span class="ml-1 text-xs font-normal text-[#947142]">Con existencias</span>
                                    @endif
                                </td>
                                <td class="px-3 py-4 text-xs text-[#68736c]">{{ $variant->deleted_at?->format('d/m/Y H:i') }}</td>
                                <td class="px-5 py-4 text-right sm:px-6">
                                    <form method="POST" action="{{ route('products.variants.restore', [$productModel, $variant]) }}">
                                        @csrf
                                        @method('PATCH')
                                        <button type="submit"
                                            class="aurea-focus rounded-md px-2 py-1.5 text-xs font-medium text-aurea-gold hover:bg-aurea-paper">
                                            Restaurar
                                        </button>
                                    </form>
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
                            <div class="min-w-0">
                                <p class="text-sm font-medium text-[#343d37]">{{ $variant->sku }}</p>
                                <p class="mt-1 text-xs text-[#929b94]">
                                    {{ $variant->size?->name ?? $baseValues['size'] }} ·
                                    {{ $variant->material?->name ?? $baseValues['material'] }} ·
                                    {{ $variant->stone?->name ?? '—' }}
                                </p>
                            </div>
                            <span class="text-sm font-medium {{ $variant->inventoryStock?->current_stock > 0 ? 'text-[#5b8668]' : 'text-[#929b94]' }}">
                                {{ $variant->inventoryStock?->current_stock ?? 0 }}
                            </span>
                        </div>
                        @if (($variant->inventoryStock?->current_stock ?? 0) > 0)
                            <p class="mt-2 text-xs font-medium text-[#947142]">Con existencias físicas</p>
                        @endif
                        <p class="mt-2 text-xs text-[#68736c]">Eliminada el {{ $variant->deleted_at?->format('d/m/Y H:i') }}</p>
                        <div class="mt-3 flex justify-end">
                            <form method="POST" action="{{ route('products.variants.restore', [$productModel, $variant]) }}">
                                @csrf
                                @method('PATCH')
                                <button type="submit"
                                    class="aurea-focus rounded-md px-2 py-1.5 text-xs font-medium text-aurea-gold hover:bg-aurea-paper">
                                    Restaurar
                                </button>
                            </form>
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
