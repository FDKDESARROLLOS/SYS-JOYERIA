@extends('layouts.admin', ['title' => 'Categorías', 'sectionTitle' => 'Categorías'])

@section('content')
    <x-admin.page-header
        :breadcrumbs="[
            [
                'label' => 'Catálogo',
                'url' => route('products.index'),
            ],
            [
                'label' => 'Categorías',
                'url' => null,
            ],
        ]"
        title="Categorías"
        description="Organiza el catálogo de {{ config('aurea-demo.store_settings.store_name', 'Tienda') }} para que cada pieza encuentre su lugar."
    >
        <x-slot:actions>
            <a
                href="{{ route('categories.create') }}"
                class="aurea-focus inline-flex w-fit items-center gap-2 rounded-lg bg-aurea-gold px-4 py-2.5 text-sm font-medium text-white shadow-sm transition hover:bg-[#9f793f]"
            >
                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7"
                    aria-hidden="true">
                    <path d="M12 5v14M5 12h14" />
                </svg>
                Nueva categoría
            </a>
        </x-slot:actions>
    </x-admin.page-header>

    <x-admin.flash-messages />

    <section
        class="mb-7 rounded-2xl border border-aurea-border bg-aurea-surface p-5 shadow-[0_5px_20px_rgba(35,42,37,0.03)] sm:p-6"
        aria-labelledby="category-filters-title"
    >
        <div class="mb-5">
            <h3 id="category-filters-title" class="font-serif text-xl text-[#28302b]">Filtrar categorías</h3>
            <p class="mt-1 text-xs text-[#8a938d]">Consulta rápidamente las categorías del catálogo.</p>
        </div>
        <form method="GET" action="{{ route('categories.index') }}" class="grid gap-4 sm:grid-cols-2">
            <label>
                <span class="mb-2 block text-xs font-medium text-[#68736c]">Buscar categoría</span>
                <input
                    type="search"
                    name="search"
                    value="{{ request('search') }}"
                    placeholder="Ej. Anillos"
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
                    href="{{ route('categories.index') }}"
                    class="aurea-focus inline-flex w-full items-center justify-center rounded-lg border border-[#dfe2dc] px-4 py-2.5 text-sm font-medium text-[#68736c] transition hover:border-aurea-gold hover:text-aurea-focus dark:border-[#4b4a45] dark:text-[#d0d4cf] dark:hover:border-[#b99562] dark:hover:text-[#e6cda9] sm:w-auto"
                >
                    Limpiar
                </a>
            </div>
        </form>
    </section>

    <section
        class="overflow-hidden rounded-2xl border border-aurea-border bg-aurea-surface shadow-[0_5px_20px_rgba(35,42,37,0.03)]"
        aria-labelledby="categories-list-title"
    >
        <div class="border-b border-[#eef0ec] px-5 py-5 sm:px-6">
            <h3 id="categories-list-title" class="font-serif text-xl text-[#28302b]">Listado de categorías</h3>
            <p class="mt-1 text-xs text-[#8a938d]">Mostrando {{ $categories->total() }} categorías.</p>
        </div>

        @if ($categories->isEmpty())
            <x-admin.empty-state
                title="No hay categorías todavía"
                description="Crea una categoría para organizar tu catálogo."
                :action-url="route('categories.create')"
                action-label="Agregar categoría"
            />
        @else
        <div class="hidden overflow-x-auto sm:block">
            <table class="admin-table w-full min-w-190 text-left text-sm">
                <caption class="sr-only">Listado de categorías del catálogo de {{ config('aurea-demo.store_settings.store_name', 'Tienda') }}</caption>
                <thead class="bg-[#fbfbf9] text-[10px] uppercase tracking-wider text-[#9ca49e]">
                    <tr>
                        <th scope="col" class="px-5 py-3 font-medium sm:px-6">Nombre</th>
                        <th scope="col" class="px-3 py-3 font-medium">Nivel</th>
                        <th scope="col" class="px-3 py-3 font-medium">Categoría padre</th>
                        <th scope="col" class="px-3 py-3 font-medium">Slug</th>
                        <th scope="col" class="px-3 py-3 font-medium">Estado</th>
                        <th scope="col" class="px-5 py-3 text-right font-medium sm:px-6">Acciones</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-[#f0f1ed]">
                    @foreach ($categories as $category)
                        @php
                            $isEffectivelyActive = $category->isEffectivelyActive();
                            $statusLabel = !$category->status
                                ? 'Inactivo'
                                : ($isEffectivelyActive ? 'Activo' : 'Inactivo por categoría padre');
                            $statusClass = !$category->status
                                ? 'text-[#a27a3f]'
                                : ($isEffectivelyActive ? 'text-[#5b8668]' : 'text-[#8f7654]');
                        @endphp
                        <tr class="text-[#5b655e] hover:bg-[#fbfbf9]">
                            <td class="px-5 py-4 sm:px-6">
                                <p class="font-medium text-[#343d37]">{{ $category->name }}</p>
                            </td>
                            <td class="px-3 py-4">
                                {{ $category->parent_id ? 'Subcategoría' : 'Principal' }}
                            </td>
                            <td class="px-3 py-4">{{ $category->parent?->name ?? '—' }}</td>
                            <td class="px-3 py-4 text-xs text-[#929b94]">{{ $category->slug }}</td>
                            <td class="px-3 py-4">
                                <x-admin.status-badge
                                    :label="$statusLabel"
                                    :status-class="$statusClass"
                                />
                            </td>
                            <td class="px-5 py-4 text-right sm:px-6">
                                <x-admin.row-actions
                                    :label="$category->name"
                                    :edit-url="route('categories.edit', $category)"
                                    delete-label="categoría"
                                    :delete-url="route('categories.destroy', $category)"
                                />
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div class="divide-y divide-[#f0f1ed] sm:hidden">
            @foreach ($categories as $category)
                @php
                    $isEffectivelyActive = $category->isEffectivelyActive();
                    $statusLabel = !$category->status
                        ? 'Inactivo'
                        : ($isEffectivelyActive ? 'Activo' : 'Inactivo por categoría padre');
                    $statusClass = !$category->status
                        ? 'text-[#a27a3f]'
                        : ($isEffectivelyActive ? 'text-[#5b8668]' : 'text-[#8f7654]');
                @endphp
                <article class="px-5 py-4">
                    <div class="flex items-start justify-between gap-3">
                        <div class="min-w-0">
                            <p class="text-sm font-medium text-[#343d37]">{{ $category->name }}</p>
                        </div>
                        <x-admin.status-badge
                            :label="$statusLabel"
                            :status-class="$statusClass"
                        />
                    </div>
                    <dl class="mt-3 grid grid-cols-2 gap-x-4 gap-y-2 text-xs">
                        <div>
                            <dt class="text-[#929b94]">Nivel</dt>
                            <dd class="mt-0.5 text-[#5b655e]">
                                {{ $category->parent_id ? 'Subcategoría' : 'Principal' }}
                            </dd>
                        </div>
                        <div>
                            <dt class="text-[#929b94]">Categoría padre</dt>
                            <dd class="mt-0.5 text-[#5b655e]">{{ $category->parent?->name ?? '—' }}</dd>
                        </div>
                    </dl>
                    <div class="mt-3 flex items-end justify-between gap-3">
                        <div class="text-xs text-[#929b94]">
                            <span>Slug</span>
                            <span class="ml-1">/{{ $category->slug }}</span>
                        </div>
                        <x-admin.row-actions
                            :label="$category->name"
                            :edit-url="route('categories.edit', $category)"
                            delete-label="categoría"
                            :delete-url="route('categories.destroy', $category)"
                        />
                    </div>
                </article>
            @endforeach
        </div>

        <div class="border-t border-[#eef0ec] px-5 py-4 sm:px-6">
            {{ $categories->links() }}
        </div>
        @endif
    </section>
@endsection
