@extends('layouts.admin', ['title' => 'Nueva variante', 'sectionTitle' => 'Nueva variante'])

@section('content')
    <x-admin.page-header
        :breadcrumbs="[
            ['label' => 'Productos', 'url' => route('products.index')],
            ['label' => $product['name'], 'url' => route('products.edit', $productModel)],
            ['label' => 'Variantes', 'url' => route('products.variants.index', $productModel)],
            ['label' => 'Nueva variante', 'url' => null],
        ]"
        title="Nueva variante"
        description="Añade una versión de {{ $product['name'] }} al catálogo."
        :required="true"
    >
        <x-slot:actions>
            <a href="{{ route('products.variants.index', $productModel) }}"
                class="aurea-focus inline-flex items-center justify-center rounded-lg border border-[#dfe2dc] px-4 py-2.5 text-sm font-medium text-[#68736c] hover:border-aurea-gold hover:text-aurea-focus">Cancelar</a>
            <button type="submit" form="variant-form"
                class="aurea-focus inline-flex items-center justify-center rounded-lg bg-aurea-gold px-4 py-2.5 text-sm font-medium text-white shadow-sm hover:bg-[#9f793f]">Guardar variante</button>
        </x-slot:actions>
    </x-admin.page-header>

    <x-admin.product-context
        :product="$product"
        :edit-url="route('products.edit', $productModel)"
    />

    @include('products.variants._form', [
        'product' => $product,
        'productModel' => $productModel,
        'variant' => $variant,
        'isEditing' => false,
    ])
@endsection
