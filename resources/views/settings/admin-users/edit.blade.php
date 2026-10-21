@extends('layouts.admin', ['title' => 'Editar usuario administrativo', 'sectionTitle' => 'Editar usuario'])

@section('content')
    @php
        $isEditing = true;
    @endphp

    <x-admin.page-header :breadcrumbs="[
        [
            'label' => 'Usuarios administrativos',
            'url' => route('settings.admin-users.index'),
        ],
        [
            'label' => $user->name,
            'url' => null,
        ],
        [
            'label' => 'Editar usuario',
            'url' => null,
        ],
    ]" title="Editar usuario administrativo"
        description="Actualiza la información de {{ $user->name }} dentro de la administración de {{ config('aurea-demo.store_settings.store_name', 'Tienda') }}.">
        <x-slot:actions>
            <a href="{{ route('settings.admin-users.index') }}"
                class="aurea-focus inline-flex items-center justify-center rounded-lg border border-[#dfe2dc] px-4 py-2.5 text-sm font-medium text-[#68736c] hover:border-aurea-gold hover:text-aurea-focus">
                Cancelar
            </a>

            <button type="submit" form="admin-user-form"
                class="aurea-focus inline-flex items-center justify-center rounded-lg bg-aurea-gold px-4 py-2.5 text-sm font-medium text-white shadow-sm hover:bg-[#9f793f]">
                Guardar cambios
            </button>
        </x-slot:actions>
    </x-admin.page-header>

    <x-admin.flash-messages />

    @include('settings.admin-users._form', [
        'user' => $user,
        'isEditing' => $isEditing,
    ])
@endsection
