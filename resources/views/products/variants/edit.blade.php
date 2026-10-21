@extends('layouts.admin', ['title' => 'Editar variante', 'sectionTitle' => 'Editar variante'])

@section('content')
    @php
        $productId = request()->route('id');
        $variantId = request()->route('variant');
        $catalogProducts = config('aurea-demo.catalog_products');
        $product = collect($catalogProducts)->firstWhere('base_sku', $productId);
        abort_unless(is_array($product), 404);
        $productVariants = config('aurea-demo.product_variants.' . $product['base_sku'], []);
        $variant = collect($productVariants)->firstWhere('sku', $variantId);
        abort_unless(is_array($variant), 404);
    @endphp

    <x-admin.page-header
        :breadcrumbs="[
            [
                'label' => 'Productos',
                'url' => route('products.index'),
            ],
            [
                'label' => $product['name'],
                'url' => route('products.edit', ['id' => $product['base_sku']]),
            ],
            [
                'label' => 'Variantes',
                'url' => route('products.variants.index', ['id' => $product['base_sku']]),
            ],
            [
                'label' => 'Editar variante',
                'url' => null,
            ],
        ]"
        title="Editar variante"
        description="Actualiza una versión de {{ $product['name'] }} sin modificar existencias."
        badge="Variante existente"
        badge-class="bg-[#edf5ee] text-[#5b8668]"
        :required="true"
    />

    <x-admin.product-context
        :product="$product"
        :edit-url="route('products.edit', ['id' => $product['base_sku']])"
        :meta="'Variante: ' . $variant['sku']"
    />

    @include('products.variants._form', [
        'product' => $product,
        'variant' => $variant,
        'isEditing' => true,
    ])
@endsection
