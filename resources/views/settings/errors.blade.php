@php
    $severityLabels = [
        'error' => 'Error',
        'warning' => 'Advertencia',
        'info' => 'Informativo',
    ];
    $severityClasses = [
        'error' => 'text-[#a46255] dark:text-[#e59b8b]',
        'warning' => 'text-[#9a7445] dark:text-[#d8b274]',
        'info' => 'text-[#557184] dark:text-[#9dbdce]',
    ];
@endphp

@extends('layouts.admin', ['title' => 'Errores del sistema', 'sectionTitle' => 'Errores del sistema'])

@section('content')
    <x-admin.page-header
        :breadcrumbs="[
            ['label' => 'Configuración', 'url' => null],
            ['label' => 'Errores del sistema', 'url' => null],
        ]"
        title="Catálogo de errores"
        description="Consulta y administra los códigos utilizados por el sistema."
    >
        <x-slot:actions>
            <a
                href="{{ route('settings.errors.create') }}"
                class="aurea-focus inline-flex w-fit items-center gap-2 rounded-lg bg-aurea-gold px-4 py-2.5 text-sm font-medium text-white shadow-sm transition hover:bg-[#9f793f]"
            >
                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" aria-hidden="true">
                    <path d="M12 5v14M5 12h14" />
                </svg>
                Nuevo código
            </a>
        </x-slot:actions>
    </x-admin.page-header>

    <x-admin.flash-messages />

    <section class="mb-7 grid gap-3 sm:grid-cols-2 xl:grid-cols-4" aria-label="Resumen del catálogo de errores">
        @foreach ([
            ['label' => 'Total de códigos', 'value' => $summary['total']],
            ['label' => 'Errores', 'value' => $summary['error']],
            ['label' => 'Advertencias', 'value' => $summary['warning']],
            ['label' => 'Informativos', 'value' => $summary['info']],
        ] as $card)
            <article class="rounded-xl border border-aurea-border bg-aurea-surface px-4 py-4">
                <p class="text-[11px] uppercase tracking-[.14em] text-aurea-muted">{{ $card['label'] }}</p>
                <p class="mt-2 text-2xl font-semibold tracking-tight text-aurea-ink">{{ $card['value'] }}</p>
            </article>
        @endforeach
    </section>

    <section class="mb-7 rounded-2xl border border-aurea-border bg-aurea-surface p-5 shadow-[0_5px_20px_rgba(35,42,37,0.03)] sm:p-6" aria-labelledby="error-filters-title">
        <div class="mb-5 flex flex-col justify-between gap-2 sm:flex-row sm:items-center">
            <div>
                <h3 id="error-filters-title" class="font-serif text-xl text-[#28302b] dark:text-[#f1eee8]">Filtrar catálogo</h3>
                <p class="mt-1 text-xs text-aurea-muted">Busca un código por sus datos persistidos.</p>
            </div>
            <a href="{{ route('settings.errors') }}" class="aurea-focus inline-flex w-fit rounded-md px-2 py-1.5 text-xs font-medium text-aurea-focus hover:bg-aurea-paper dark:hover:bg-[#3a3b37]">Limpiar filtros</a>
        </div>

        <form method="GET" action="{{ route('settings.errors') }}" class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
            <label class="sm:col-span-2 xl:col-span-1">
                <span class="mb-2 block text-xs font-medium text-[#68736c] dark:text-[#c2c6c1]">Buscar código</span>
                <input
                    type="search"
                    name="search"
                    value="{{ request('search') }}"
                    placeholder="Código, título o descripción"
                    class="aurea-focus w-full rounded-lg border border-[#dfe2dc] bg-aurea-surface px-3 py-2.5 text-sm text-[#56625a] outline-none placeholder:text-[#adb4ae] focus:border-aurea-gold dark:border-[#4b4a45] dark:text-[#f1eee8] dark:placeholder:text-[#8f958f]"
                />
            </label>
            <label>
                <span class="mb-2 block text-xs font-medium text-[#68736c] dark:text-[#c2c6c1]">Módulo</span>
                <select name="module" class="aurea-focus w-full appearance-none rounded-lg border border-[#dfe2dc] bg-aurea-surface px-3 py-2.5 text-sm text-[#56625a] outline-none dark:border-[#4b4a45] dark:bg-[#292b29] dark:text-[#f1eee8]">
                    <option value="">Todos</option>
                    @foreach ($modules as $module)
                        <option value="{{ $module }}" @selected(request('module') === $module)>{{ $module }}</option>
                    @endforeach
                </select>
            </label>
            <label>
                <span class="mb-2 block text-xs font-medium text-[#68736c] dark:text-[#c2c6c1]">Severidad</span>
                <select name="severity" class="aurea-focus w-full appearance-none rounded-lg border border-[#dfe2dc] bg-aurea-surface px-3 py-2.5 text-sm text-[#56625a] outline-none dark:border-[#4b4a45] dark:bg-[#292b29] dark:text-[#f1eee8]">
                    <option value="">Todas</option>
                    @foreach ($severityLabels as $value => $label)
                        <option value="{{ $value }}" @selected(request('severity') === $value)>{{ $label }}</option>
                    @endforeach
                </select>
            </label>
            <label>
                <span class="mb-2 block text-xs font-medium text-[#68736c] dark:text-[#c2c6c1]">Estado</span>
                <select name="status" class="aurea-focus w-full appearance-none rounded-lg border border-[#dfe2dc] bg-aurea-surface px-3 py-2.5 text-sm text-[#56625a] outline-none dark:border-[#4b4a45] dark:bg-[#292b29] dark:text-[#f1eee8]">
                    <option value="">Todos</option>
                    <option value="1" @selected(request('status') === '1')>Activo</option>
                    <option value="0" @selected(request('status') === '0')>Inactivo</option>
                </select>
            </label>
            <div class="flex flex-col gap-3 sm:col-span-2 xl:col-span-4 sm:flex-row sm:justify-end">
                <button type="submit" class="aurea-focus inline-flex w-full items-center justify-center rounded-lg bg-aurea-gold px-4 py-2.5 text-sm font-medium text-white shadow-sm transition hover:bg-[#9f793f] sm:w-auto">Aplicar filtros</button>
            </div>
        </form>
    </section>

    <section class="overflow-hidden rounded-2xl border border-aurea-border bg-aurea-surface shadow-[0_5px_20px_rgba(35,42,37,0.03)]" aria-labelledby="error-list-title">
        <div class="border-b border-[#eef0ec] px-5 py-5 dark:border-[#3b3d39] sm:px-6">
            <h3 id="error-list-title" class="font-serif text-xl text-[#28302b] dark:text-[#f1eee8]">Códigos registrados</h3>
            <p class="mt-1 text-xs text-aurea-muted">Mostrando {{ $errorCodes->total() }} códigos persistidos.</p>
        </div>

        @if ($errorCodes->isEmpty())
            <x-admin.empty-state title="No hay códigos de error disponibles." description="Crea un código para comenzar el catálogo." :action-url="route('settings.errors.create')" action-label="Agregar código" />
        @else
            <div class="hidden overflow-x-auto sm:block">
                <table class="admin-table w-full min-w-220 text-left text-sm">
                    <caption class="sr-only">Catálogo persistente de errores del sistema</caption>
                    <thead class="bg-[#fbfbf9] text-[10px] uppercase tracking-wider text-[#9ca49e] dark:bg-[#292b29]">
                        <tr>
                            <th scope="col" class="px-5 py-3 font-medium sm:px-6">Código</th>
                            <th scope="col" class="px-3 py-3 font-medium">Título</th>
                            <th scope="col" class="px-3 py-3 font-medium">Módulo</th>
                            <th scope="col" class="px-3 py-3 font-medium">Severidad</th>
                            <th scope="col" class="px-3 py-3 font-medium">Estado</th>
                            <th scope="col" class="px-5 py-3 text-right font-medium sm:px-6">Acciones</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-[#f0f1ed] dark:divide-[#3b3d39]">
                        @foreach ($errorCodes as $errorCode)
                            <tr class="text-[#5b655e] hover:bg-[#fbfbf9] dark:text-[#c2c6c1] dark:hover:bg-[#30322f]">
                                <td class="px-5 py-4 font-mono text-xs font-medium text-[#4f4a45] dark:text-[#e6cda9] sm:px-6">{{ $errorCode->code }}</td>
                                <td class="px-3 py-4 font-medium text-[#343d37] dark:text-[#f1eee8]">{{ $errorCode->title }}</td>
                                <td class="px-3 py-4">{{ $errorCode->module }}</td>
                                <td class="px-3 py-4"><span class="{{ $severityClasses[$errorCode->severity] ?? 'text-aurea-muted' }}">{{ $severityLabels[$errorCode->severity] ?? $errorCode->severity }}</span></td>
                                <td class="px-3 py-4"><span class="{{ $errorCode->status ? 'text-[#587262] dark:text-[#9ac7a5]' : 'text-[#927148] dark:text-[#d8b274]' }}">{{ $errorCode->status ? 'Activo' : 'Inactivo' }}</span></td>
                                <td class="px-5 py-4 text-right sm:px-6">
                                    <div class="flex justify-end gap-2">
                                        <button type="button" class="error-detail-trigger aurea-focus rounded-md px-2 py-1.5 text-xs font-medium text-aurea-focus hover:bg-aurea-paper dark:hover:bg-[#3a3b37]" data-code="{{ $errorCode->code }}" data-title="{{ $errorCode->title }}" data-module="{{ $errorCode->module }}" data-severity="{{ $severityLabels[$errorCode->severity] ?? $errorCode->severity }}" data-status="{{ $errorCode->status ? 'Activo' : 'Inactivo' }}" data-description="{{ $errorCode->description ?? '' }}">Ver</button>
                                        <a href="{{ route('settings.errors.edit', $errorCode) }}" class="aurea-focus rounded-md px-2 py-1.5 text-xs font-medium text-aurea-focus hover:bg-aurea-paper dark:hover:bg-[#3a3b37]">Editar</a>
                                        <form method="POST" action="{{ route('settings.errors.status', $errorCode) }}">
                                            @csrf
                                            @method('PATCH')
                                            <button type="submit" class="aurea-focus rounded-md px-2 py-1.5 text-xs font-medium text-[#68736c] hover:bg-aurea-paper dark:text-[#d0d4cf] dark:hover:bg-[#3a3b37]">{{ $errorCode->status ? 'Inactivar' : 'Activar' }}</button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="divide-y divide-[#f0f1ed] dark:divide-[#3b3d39] sm:hidden">
                @foreach ($errorCodes as $errorCode)
                    <article class="px-5 py-4">
                        <div class="flex items-start justify-between gap-3">
                            <div class="min-w-0">
                                <p class="font-mono text-xs font-medium text-[#4f4a45] dark:text-[#e6cda9]">{{ $errorCode->code }}</p>
                                <p class="mt-1 text-sm font-medium text-[#343d37] dark:text-[#f1eee8]">{{ $errorCode->title }}</p>
                                <p class="mt-1 text-xs text-aurea-muted">{{ $errorCode->module }} · {{ $severityLabels[$errorCode->severity] ?? $errorCode->severity }}</p>
                            </div>
                            <span class="shrink-0 text-xs {{ $errorCode->status ? 'text-[#587262] dark:text-[#9ac7a5]' : 'text-[#927148] dark:text-[#d8b274]' }}">{{ $errorCode->status ? 'Activo' : 'Inactivo' }}</span>
                        </div>
                        <div class="mt-3 flex justify-end gap-2">
                            <button type="button" class="error-detail-trigger aurea-focus rounded-md px-2 py-1.5 text-xs font-medium text-aurea-focus hover:bg-aurea-paper dark:hover:bg-[#3a3b37]" data-code="{{ $errorCode->code }}" data-title="{{ $errorCode->title }}" data-module="{{ $errorCode->module }}" data-severity="{{ $severityLabels[$errorCode->severity] ?? $errorCode->severity }}" data-status="{{ $errorCode->status ? 'Activo' : 'Inactivo' }}" data-description="{{ $errorCode->description ?? '' }}">Ver</button>
                            <a href="{{ route('settings.errors.edit', $errorCode) }}" class="aurea-focus rounded-md px-2 py-1.5 text-xs font-medium text-aurea-focus hover:bg-aurea-paper dark:hover:bg-[#3a3b37]">Editar</a>
                            <form method="POST" action="{{ route('settings.errors.status', $errorCode) }}">
                                @csrf
                                @method('PATCH')
                                <button type="submit" class="aurea-focus rounded-md px-2 py-1.5 text-xs font-medium text-[#68736c] dark:text-[#d0d4cf]">{{ $errorCode->status ? 'Inactivar' : 'Activar' }}</button>
                            </form>
                        </div>
                    </article>
                @endforeach
            </div>

            <div class="border-t border-[#eef0ec] px-5 py-4 dark:border-[#3b3d39] sm:px-6">
                {{ $errorCodes->links() }}
            </div>
        @endif
    </section>

    <div id="error-detail-modal" class="fixed inset-0 z-50 hidden items-center justify-center bg-[#222222]/25 px-5 backdrop-blur-sm" role="presentation">
        <div class="w-full max-w-lg rounded-2xl border border-aurea-border bg-white p-6 shadow-[0_18px_50px_rgba(35,42,37,0.16)] dark:bg-[#2d302e]" role="dialog" aria-modal="true" aria-labelledby="error-detail-title">
            <div class="flex items-start justify-between gap-4">
                <div>
                    <p id="error-detail-code" class="font-mono text-xs font-medium text-aurea-focus"></p>
                    <h2 id="error-detail-title" class="mt-2 font-serif text-2xl text-aurea-ink dark:text-[#f1eee8]"></h2>
                </div>
                <button type="button" id="error-detail-close" class="aurea-focus rounded-md p-2 text-aurea-muted hover:bg-aurea-paper dark:hover:bg-[#3a3b37]" aria-label="Cerrar detalle" title="Cerrar detalle"><x-admin.icon name="close" class="h-5 w-5" /></button>
            </div>
            <dl class="mt-6 grid gap-4 text-sm sm:grid-cols-3">
                <div><dt class="text-xs text-aurea-muted">Módulo</dt><dd id="error-detail-module" class="mt-1 text-[#343d37] dark:text-[#e5e8e3]"></dd></div>
                <div><dt class="text-xs text-aurea-muted">Severidad</dt><dd id="error-detail-severity" class="mt-1 text-[#343d37] dark:text-[#e5e8e3]"></dd></div>
                <div><dt class="text-xs text-aurea-muted">Estado</dt><dd id="error-detail-status" class="mt-1 text-[#587262] dark:text-[#9ac7a5]"></dd></div>
            </dl>
            <div class="mt-6 border-t border-[#eef0ec] pt-5 text-sm dark:border-[#3b3d39]">
                <h3 class="text-xs font-medium text-aurea-muted">Descripción</h3>
                <p id="error-detail-description" class="mt-1 leading-6 text-[#343d37] dark:text-[#e5e8e3]"></p>
            </div>
            <div class="mt-6 flex justify-end"><button type="button" id="error-detail-close-bottom" class="aurea-focus rounded-lg border border-aurea-border px-4 py-2.5 text-sm font-medium text-aurea-muted hover:bg-aurea-paper dark:hover:bg-[#3a3b37]">Cerrar</button></div>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        const errorModal = document.getElementById('error-detail-modal');
        const errorModalClose = document.getElementById('error-detail-close');
        const errorModalCloseBottom = document.getElementById('error-detail-close-bottom');
        let errorDetailTrigger = null;

        const toggleErrorModal = (open) => {
            errorModal?.classList.toggle('hidden', !open);
            errorModal?.classList.toggle('flex', open);

            if (!open) {
                errorDetailTrigger?.focus();
                errorDetailTrigger = null;
            }
        };

        document.querySelectorAll('.error-detail-trigger').forEach((trigger) => {
            trigger.addEventListener('click', () => {
                errorDetailTrigger = trigger;
                document.getElementById('error-detail-code').textContent = trigger.dataset.code;
                document.getElementById('error-detail-title').textContent = trigger.dataset.title;
                document.getElementById('error-detail-module').textContent = trigger.dataset.module;
                document.getElementById('error-detail-severity').textContent = trigger.dataset.severity;
                document.getElementById('error-detail-status').textContent = trigger.dataset.status;
                document.getElementById('error-detail-description').textContent = trigger.dataset.description || 'Sin descripción.';
                toggleErrorModal(true);
                errorModalClose?.focus();
            });
        });

        [errorModalClose, errorModalCloseBottom].forEach((control) => control?.addEventListener('click', () => toggleErrorModal(false)));
        document.addEventListener('keydown', (event) => {
            if (event.key === 'Escape') {
                toggleErrorModal(false);
            }
        });
    </script>
@endpush
