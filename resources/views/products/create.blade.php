@extends('layouts.admin', ['title' => 'Nuevo producto', 'sectionTitle' => 'Nuevo producto'])

@section('content')
    @php
        $product = config('aurea-demo.product_form.defaults', []);
    @endphp

    <x-admin.page-header
        :breadcrumbs="[
            [
                'label' => 'Productos',
                'url' => route('products.index'),
            ],
            [
                'label' => 'Nuevo producto',
                'url' => null,
            ],
        ]"
        title="Nuevo producto"
        description="Crea una nueva pieza para el catálogo de {{ config('aurea-demo.store_settings.store_name', 'Tienda') }} y prepara todos sus detalles antes de publicarla."
        :required="true"
    >
        <x-slot:actions>
            <a
                href="{{ route('products.index') }}"
                class="aurea-focus inline-flex items-center justify-center rounded-lg border border-[#dfe2dc] px-4 py-2.5 text-sm font-medium text-[#68736c] hover:border-aurea-gold hover:text-aurea-focus"
            >Cancelar</a>
            <button
                type="button"
                class="aurea-focus inline-flex items-center justify-center gap-2 rounded-lg bg-aurea-gold px-4 py-2.5 text-sm font-medium text-white shadow-sm hover:bg-[#9f793f]"
            >Guardar producto</button>
        </x-slot:actions>
    </x-admin.page-header>

    @include('products._form', ['product' => $product, 'isEditing' => false])
@endsection
