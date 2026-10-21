@extends('layouts.admin', ['title' => 'Editar talla', 'sectionTitle' => 'Editar talla'])

@section('content')
    <x-admin.page-header
        :breadcrumbs="[
            [
                'label' => 'Tallas',
                'url' => route('sizes.index'),
            ],
            [
                'label' => $size->name,
                'url' => route('sizes.edit', $size),
            ],
            [
                'label' => 'Editar talla',
                'url' => null,
            ],
        ]"
        title="Editar talla"
        description="Actualiza la información de la talla {{ $size->name }} dentro del catálogo de {{ config('aurea-demo.store_settings.store_name', 'Tienda') }}."
        :required="true"
    >
        <x-slot:actions>
            <a
                href="{{ route('sizes.index') }}"
                class="aurea-focus inline-flex items-center justify-center rounded-lg border border-[#dfe2dc] px-4 py-2.5 text-sm font-medium text-[#68736c] hover:border-aurea-gold hover:text-aurea-focus"
            >Cancelar</a>
            <button
                type="submit"
                form="size-form"
                class="aurea-focus inline-flex items-center justify-center rounded-lg bg-aurea-gold px-4 py-2.5 text-sm font-medium text-white shadow-sm hover:bg-[#9f793f]"
            >Guardar cambios</button>
        </x-slot:actions>
    </x-admin.page-header>

    <x-admin.flash-messages />

    @include('sizes._form', ['size' => $size])
@endsection
