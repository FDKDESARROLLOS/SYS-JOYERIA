@extends('layouts.admin', ['title' => 'Nueva categoría', 'sectionTitle' => 'Nueva categoría'])

@section('content')
    <x-admin.page-header
        :breadcrumbs="[
            [
                'label' => 'Categorías',
                'url' => route('categories.index'),
            ],
            [
                'label' => 'Nueva categoría',
                'url' => null,
            ],
        ]"
        title="Nueva categoría"
        description="Crea una nueva sección para ordenar el catálogo de {{ config('aurea-demo.store_settings.store_name', 'Tienda') }}."
        :required="true"
    >
        <x-slot:actions>
            <a
                href="{{ route('categories.index') }}"
                class="aurea-focus inline-flex items-center justify-center rounded-lg border border-[#dfe2dc] px-4 py-2.5 text-sm font-medium text-[#68736c] hover:border-aurea-gold hover:text-aurea-focus"
            >Cancelar</a>
            <button
                type="submit"
                form="category-form"
                class="aurea-focus inline-flex items-center justify-center rounded-lg bg-aurea-gold px-4 py-2.5 text-sm font-medium text-white shadow-sm hover:bg-[#9f793f]"
            >Guardar categoría</button>
        </x-slot:actions>
    </x-admin.page-header>

    <x-admin.flash-messages />

    @include('categories._form', [
        'category' => $category,
        'parentCategories' => $parentCategories,
        'isEditing' => false,
    ])
@endsection
