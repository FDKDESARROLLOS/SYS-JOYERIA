@extends('layouts.admin', ['title' => 'Tallas', 'sectionTitle' => 'Tallas'])

@section('content')
    <x-admin.page-header
        :breadcrumbs="[
            [
                'label' => 'Catálogo',
                'url' => route('products.index'),
            ],
            [
                'label' => 'Tallas',
                'url' => null,
            ],
        ]"
        title="Tallas"
        description="Organiza las medidas disponibles para que cada pieza de {{ config('aurea-demo.store_settings.store_name', 'Tienda') }} encuentre su ajuste ideal."
    >
        <x-slot:actions>
            <a
                href="{{ route('sizes.create') }}"
                class="aurea-focus inline-flex w-fit items-center gap-2 rounded-lg bg-aurea-gold px-4 py-2.5 text-sm font-medium text-white shadow-sm transition hover:bg-[#9f793f]"
            >
                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7"
                    aria-hidden="true">
                    <path d="M12 5v14M5 12h14" />
                </svg>
                Nueva talla
            </a>
        </x-slot:actions>
    </x-admin.page-header>

    <x-admin.flash-messages />

    <section
        class="mb-7 rounded-2xl border border-aurea-border bg-aurea-surface p-5 shadow-[0_5px_20px_rgba(35,42,37,0.03)] sm:p-6"
        aria-labelledby="size-filters-title"
    >
        <div class="mb-5">
            <h3 id="size-filters-title" class="font-serif text-xl text-[#28302b]">Filtrar tallas</h3>
            <p class="mt-1 text-xs text-[#8a938d]">Encuentra rápidamente una talla por su medida o categoría.</p>
        </div>
        <form method="GET" action="{{ route('sizes.index') }}" class="grid gap-4 sm:grid-cols-2">
            <label>
                <span class="mb-2 block text-xs font-medium text-[#68736c]">Buscar talla</span>
                <input
                    type="search"
                    name="search"
                    value="{{ request('search') }}"
                    placeholder="Ej. 7 o 18 cm"
                    class="aurea-focus w-full rounded-lg border border-[#dfe2dc] bg-aurea-surface px-3 py-2.5 text-sm text-[#56625a] outline-none placeholder:text-[#adb4ae] focus:border-aurea-gold"
                />
            </label>
            <label>
                <span class="mb-2 block text-xs font-medium text-[#68736c]">Estado</span>
                <select
                    name="status"
                    class="aurea-focus w-full appearance-none rounded-lg border border-[#dfe2dc] bg-aurea-surface px-3 py-2.5 text-sm text-[#56625a] outline-none hover:border-aurea-gold-soft dark:border-[#4b4a45] dark:bg-[#292b29] dark:text-[#f1eee8]"
                >
                    <option value="">Todos</option>
                    <option value="1" @selected(request('status') === '1')>Activo</option>
                    <option value="0" @selected(request('status') === '0')>Inactivo</option>
                </select>
            </label>
            <div class="flex flex-col gap-3 sm:col-span-2 sm:flex-row sm:justify-end">
                <button
                    type="submit"
                    class="aurea-focus inline-flex w-full items-center justify-center rounded-lg bg-aurea-gold px-4 py-2.5 text-sm font-medium text-white shadow-sm transition hover:bg-[#9f793f] sm:w-auto"
                >
                    Aplicar filtros
                </button>
                <a
                    href="{{ route('sizes.index') }}"
                    class="aurea-focus inline-flex w-full items-center justify-center rounded-lg border border-[#dfe2dc] px-4 py-2.5 text-sm font-medium text-[#68736c] transition hover:border-aurea-gold hover:text-aurea-focus dark:border-[#4b4a45] dark:text-[#d0d4cf] dark:hover:border-[#b99562] dark:hover:text-[#e6cda9] sm:w-auto"
                >
                    Limpiar
                </a>
            </div>
        </form>
    </section>

    <section
        class="overflow-hidden rounded-2xl border border-aurea-border bg-aurea-surface shadow-[0_5px_20px_rgba(35,42,37,0.03)]"
        aria-labelledby="sizes-list-title"
    >
        <div class="border-b border-[#eef0ec] px-5 py-5 sm:px-6">
            <h3 id="sizes-list-title" class="font-serif text-xl text-[#28302b]">Listado de tallas</h3>
            <p class="mt-1 text-xs text-[#8a938d]">Mostrando {{ $sizes->total() }} tallas.</p>
        </div>

        @if ($sizes->isEmpty())
            <x-admin.empty-state
                title="No hay tallas todavía"
                description="Agrega una talla para definir las opciones disponibles por categoría."
                :action-url="route('sizes.create')"
                action-label="Agregar talla"
            />
        @else
        <div class="hidden overflow-x-auto sm:block">
            <table class="admin-table w-full min-w-190 text-left text-sm">
                <caption class="sr-only">Listado de tallas de {{ config('aurea-demo.store_settings.store_name', 'Tienda') }}</caption>
                <thead class="bg-[#fbfbf9] text-[10px] uppercase tracking-wider text-[#9ca49e]">
                    <tr>
                        <th scope="col" class="px-5 py-3 font-medium sm:px-6">Nombre</th>
                        <th scope="col" class="px-3 py-3 font-medium">Categorías</th>
                        <th scope="col" class="px-3 py-3 font-medium">Estado</th>
                        <th scope="col" class="px-5 py-3 text-right font-medium sm:px-6">Acciones</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-[#f0f1ed]">
                    @foreach ($sizes as $size)
                        <tr class="text-[#5b655e] hover:bg-[#fbfbf9]">
                            <td class="px-5 py-4 sm:px-6">
                                <p class="font-medium text-[#343d37]">{{ $size->name }}</p>
                            </td>
                            <td class="px-3 py-4">
                                @if ($size->categories->isEmpty())
                                    <span class="text-xs text-[#929b94]">Sin categorías</span>
                                @else
                                    <div class="flex flex-wrap gap-1.5">
                                        @foreach ($size->categories as $category)
                                            <span class="rounded-full bg-[#f6f3ed] px-2 py-1 text-xs text-[#6f634f]">
                                                {{ $category->name }}
                                            </span>
                                        @endforeach
                                    </div>
                                @endif
                            </td>
                            <td class="px-3 py-4">
                                <x-admin.status-badge
                                    :label="$size->status ? 'Activo' : 'Inactivo'"
                                    :status-class="$size->status ? 'text-[#5b8668]' : 'text-[#a27a3f]'"
                                />
                            </td>
                            <td class="px-5 py-4 text-right sm:px-6">
                                <x-admin.row-actions
                                    :label="$size->name"
                                    :edit-url="route('sizes.edit', $size)"
                                    delete-label="talla"
                                    :delete-url="route('sizes.destroy', $size)"
                                />
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div class="divide-y divide-[#f0f1ed] sm:hidden">
            @foreach ($sizes as $size)
                <article class="px-5 py-4">
                    <div class="flex items-start justify-between gap-3">
                        <div class="min-w-0">
                            <p class="text-sm font-medium text-[#343d37]">{{ $size->name }}</p>
                        </div>
                        <x-admin.status-badge
                            :label="$size->status ? 'Activo' : 'Inactivo'"
                            :status-class="$size->status ? 'text-[#5b8668]' : 'text-[#a27a3f]'"
                        />
                    </div>
                    <div class="mt-3 flex justify-end">
                        <div class="mr-auto text-xs text-[#929b94]">
                            <span>Categorías:</span>
                            <span class="ml-1 text-[#5b655e]">
                                {{ $size->categories->pluck('name')->join(', ') ?: 'Sin categorías' }}
                            </span>
                        </div>
                        <x-admin.row-actions
                            :label="$size->name"
                            :edit-url="route('sizes.edit', $size)"
                            delete-label="talla"
                            :delete-url="route('sizes.destroy', $size)"
                        />
                    </div>
                </article>
            @endforeach
        </div>

        <div class="border-t border-[#eef0ec] px-5 py-4 sm:px-6">
            {{ $sizes->links() }}
        </div>
        @endif
    </section>
@endsection
