@extends('layouts.admin', ['title' => 'Nueva variante', 'sectionTitle' => 'Nueva variante'])

@section('content')
    @php
        $productId = request()->route('id');
        $catalogProducts = config('aurea-demo.catalog_products');
        $product = collect($catalogProducts)->firstWhere('base_sku', $productId);
        abort_unless(is_array($product), 404);
        $variant = [
            'sku' => '',
            'material' => '',
            'karat' => '',
            'metal_color' => '',
            'size' => '',
            'stone' => '',
            'price' => '',
            'sale_price' => '',
            'status' => 'Activo',
        ];
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
                'label' => 'Nueva variante',
                'url' => null,
            ],
        ]"
        title="Nueva variante"
        description="Añade una versión de {{ $product['name'] }} al catálogo."
        badge="Datos de demostración"
        :required="true"
    />

    <x-admin.product-context
        :product="$product"
        :edit-url="route('products.edit', ['id' => $product['base_sku']])"
    />

    @include('products.variants._form', [
        'product' => $product,
        'variant' => $variant,
        'isEditing' => false,
    ])
@endsection
