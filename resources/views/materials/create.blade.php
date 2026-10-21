@extends('layouts.admin', ['title' => 'Nuevo material', 'sectionTitle' => 'Nuevo material'])

@section('content')
    <x-admin.page-header
        :breadcrumbs="[
            [
                'label' => 'Materiales',
                'url' => route('materials.index'),
            ],
            [
                'label' => 'Nuevo material',
                'url' => null,
            ],
        ]"
        title="Nuevo material"
        description="Agrega un material para completar las especificaciones de las piezas de {{ config('aurea-demo.store_settings.store_name', 'Tienda') }}."
        :required="true"
    >
        <x-slot:actions>
            <a
                href="{{ route('materials.index') }}"
                class="aurea-focus inline-flex items-center justify-center rounded-lg border border-[#dfe2dc] px-4 py-2.5 text-sm font-medium text-[#68736c] hover:border-aurea-gold hover:text-aurea-focus"
            >Cancelar</a>
            <button
                type="submit"
                form="material-form"
                class="aurea-focus inline-flex items-center justify-center rounded-lg bg-aurea-gold px-4 py-2.5 text-sm font-medium text-white shadow-sm hover:bg-[#9f793f]"
            >Guardar material</button>
        </x-slot:actions>
    </x-admin.page-header>

    <x-admin.flash-messages />

    @include('materials._form', ['material' => $material, 'isEditing' => false])
@endsection
