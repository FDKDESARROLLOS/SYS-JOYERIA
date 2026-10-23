@extends('layouts.admin', ['title' => 'Nuevo código de error', 'sectionTitle' => 'Nuevo código de error'])

@section('content')
    <x-admin.page-header
        :breadcrumbs="[
            ['label' => 'Configuración', 'url' => null],
            ['label' => 'Catálogo de errores', 'url' => route('settings.errors')],
            ['label' => 'Nuevo código', 'url' => null],
        ]"
        title="Nuevo código de error"
        description="Define un código persistente para identificar un comportamiento del sistema."
    />

    <x-admin.flash-messages />

    <div class="mx-auto max-w-4xl">
        @include('settings.error-codes._form', ['isEditing' => false])
    </div>
@endsection
