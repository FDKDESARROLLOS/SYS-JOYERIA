@extends('layouts.admin', ['title' => 'Datos de la tienda', 'sectionTitle' => 'Datos de la tienda'])

@section('content')
    <x-admin.page-header
        :breadcrumbs="[
            [
                'label' => 'Configuración',
                'url' => null,
            ],
            [
                'label' => 'Datos de la tienda',
                'url' => null,
            ],
        ]"
        title="Datos de la tienda"
        description="Configura la información general que identifica a {{ $storeSetting->store_name ?: 'Tienda' }}."
        :required="true"
    >
        <x-slot:actions>
            <button
                type="submit"
                form="store-setting-form"
                class="aurea-focus inline-flex items-center justify-center rounded-lg bg-aurea-gold px-4 py-2.5 text-sm font-medium text-white shadow-sm hover:bg-[#9f793f]"
            >Guardar configuración</button>
        </x-slot:actions>
    </x-admin.page-header>

    <x-admin.flash-messages />

    <form id="store-setting-form" method="POST" action="{{ route('settings.store.update') }}" class="space-y-7">
        @csrf
        @method('PATCH')

        <x-admin.form-section
            number="01"
            title="Información de la tienda"
            description="Define el nombre, correo y dirección de {{ $storeSetting->store_name ?: 'la tienda' }}."
            id="store-information-title"
        >
            <div class="grid gap-5 sm:grid-cols-2">
                <label>
                    <span class="admin-form-label admin-form-label-required">Nombre de la tienda</span>
                    <input
                        type="text"
                        name="store_name"
                        value="{{ old('store_name', $storeSetting->store_name) }}"
                        placeholder="Ej. Nombre de la tienda"
                        class="admin-form-input"
                        required
                    />
                    @error('store_name')
                        <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                    @enderror
                </label>

                <label>
                    <span class="admin-form-label">Correo</span>
                    <input
                        type="email"
                        name="email"
                        value="{{ old('email', $storeSetting->email) }}"
                        placeholder="hola@aureajewelry.com"
                        class="admin-form-input"
                    />
                    @error('email')
                        <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                    @enderror
                </label>

                <label>
                    <span class="admin-form-label">WhatsApp</span>
                    <input
                        type="tel"
                        name="whatsapp"
                        value="{{ old('whatsapp', $storeSetting->whatsapp) }}"
                        placeholder="+52 55 0000 0000"
                        class="admin-form-input"
                    />
                    @error('whatsapp')
                        <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                    @enderror
                </label>

                <label class="sm:col-span-2">
                    <span class="admin-form-label">Dirección</span>
                    <input
                        type="text"
                        name="address"
                        value="{{ old('address', $storeSetting->address) }}"
                        placeholder="Ej. Avenida Reforma 123"
                        class="admin-form-input"
                    />
                    @error('address')
                        <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                    @enderror
                </label>
            </div>
        </x-admin.form-section>
    </form>
@endsection
