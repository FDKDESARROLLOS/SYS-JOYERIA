@extends('layouts.admin', ['title' => 'Editar producto', 'sectionTitle' => 'Editar producto'])

@section('content')
    <x-admin.page-header
        :breadcrumbs="[
            ['label' => 'Productos', 'url' => route('products.index')],
            ['label' => $product->name, 'url' => route('products.edit', $product)],
            ['label' => 'Editar producto', 'url' => null],
        ]"
        title="Editar producto"
        description="Actualiza la información de {{ $product->name }} dentro del catálogo de {{ config('aurea-demo.store_settings.store_name', 'Tienda') }}."
        :required="true"
    >
        <x-slot:actions>
            <a href="{{ route('products.index') }}"
                class="aurea-focus inline-flex items-center justify-center rounded-lg border border-[#dfe2dc] px-4 py-2.5 text-sm font-medium text-[#68736c] hover:border-aurea-gold hover:text-aurea-focus">Cancelar</a>
            <button type="submit" form="product-form"
                class="aurea-focus inline-flex items-center justify-center rounded-lg bg-aurea-gold px-4 py-2.5 text-sm font-medium text-white shadow-sm hover:bg-[#9f793f]">Guardar cambios</button>
        </x-slot:actions>
    </x-admin.page-header>

    <x-admin.flash-messages />

    @include('products._form', ['product' => $product, 'isEditing' => true])
@endsection
