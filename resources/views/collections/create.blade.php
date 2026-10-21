@extends('layouts.admin', ['title' => 'Nueva colección', 'sectionTitle' => 'Nueva colección'])

@section('content')
    <x-admin.page-header
        :breadcrumbs="[
            [
                'label' => 'Colecciones',
                'url' => route('collections.index'),
            ],
            [
                'label' => 'Nueva colección',
                'url' => null,
            ],
        ]"
        title="Nueva colección"
        description="Crea una nueva selección para presentar piezas con una historia común."
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
            >Guardar colección</button>
        </x-slot:actions>
    </x-admin.page-header>

    <x-admin.flash-messages />

    @include('collections._form', ['collection' => $collection, 'isEditing' => false])
@endsection
