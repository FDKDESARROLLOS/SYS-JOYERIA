@extends('layouts.admin', ['title' => 'Editar código de error', 'sectionTitle' => 'Editar código de error'])

@section('content')
    <x-admin.page-header
        :breadcrumbs="[
            ['label' => 'Configuración', 'url' => null],
            ['label' => 'Catálogo de errores', 'url' => route('settings.errors')],
            ['label' => 'Editar código', 'url' => null],
        ]"
        title="Editar código de error"
        description="Actualiza la información persistida del código seleccionado."
    />

    <x-admin.flash-messages />

    <div class="mx-auto max-w-4xl">
        @include('settings.error-codes._form', ['isEditing' => true])
    </div>
@endsection
