@extends('layouts.admin', ['title' => 'Editar colección', 'sectionTitle' => 'Editar colección'])

@section('content')
    <x-admin.page-header
        :breadcrumbs="[
            [
                'label' => 'Colecciones',
                'url' => route('collections.index'),
            ],
            [
                'label' => $collection->name,
                'url' => route('collections.edit', $collection),
            ],
            [
                'label' => 'Editar colección',
                'url' => null,
            ],
        ]"
        title="Editar colección"
        description="Actualiza la configuración de {{ $collection->name }} dentro del catálogo de {{ config('aurea-demo.store_settings.store_name', 'Tienda') }}."
        :required="true"
    >
        <x-slot:actions>
            <a
                href="{{ route('collections.index') }}"
                class="aurea-focus inline-flex items-center justify-center rounded-lg border border-[#dfe2dc] px-4 py-2.5 text-sm font-medium text-[#68736c] hover:border-aurea-gold hover:text-aurea-focus"
            >Cancelar</a>
            <button
                type="submit"
                form="collection-form"
                class="aurea-focus inline-flex items-center justify-center rounded-lg bg-aurea-gold px-4 py-2.5 text-sm font-medium text-white shadow-sm hover:bg-[#9f793f]"
            >Guardar cambios</button>
        </x-slot:actions>
    </x-admin.page-header>

    <x-admin.flash-messages />

    @include('collections._form', ['collection' => $collection, 'isEditing' => true])
@endsection
