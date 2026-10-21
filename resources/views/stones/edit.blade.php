@extends('layouts.admin', ['title' => 'Editar piedra', 'sectionTitle' => 'Editar piedra'])

@section('content')
    <x-admin.page-header
        :breadcrumbs="[
            [
                'label' => 'Piedras',
                'url' => route('stones.index'),
            ],
            [
                'label' => $stone->name,
                'url' => route('stones.edit', $stone),
            ],
            [
                'label' => 'Editar piedra',
                'url' => null,
            ],
        ]"
        title="Editar piedra"
        description="Actualiza la información de {{ $stone->name }} dentro del catálogo de {{ config('aurea-demo.store_settings.store_name', 'Tienda') }}."
        :required="true"
    >
        <x-slot:actions>
            <a
                href="{{ route('stones.index') }}"
                class="aurea-focus inline-flex items-center justify-center rounded-lg border border-[#dfe2dc] px-4 py-2.5 text-sm font-medium text-[#68736c] hover:border-aurea-gold hover:text-aurea-focus"
            >Cancelar</a>
            <button
                type="submit"
                form="stone-form"
                class="aurea-focus inline-flex items-center justify-center rounded-lg bg-aurea-gold px-4 py-2.5 text-sm font-medium text-white shadow-sm hover:bg-[#9f793f]"
            >Guardar cambios</button>
        </x-slot:actions>
    </x-admin.page-header>

    <x-admin.flash-messages />

    @include('stones._form', ['stone' => $stone, 'isEditing' => true])
@endsection
