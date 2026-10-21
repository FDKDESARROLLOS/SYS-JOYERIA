@extends('layouts.admin', ['title' => 'Errores del sistema', 'sectionTitle' => 'Errores del sistema'])

@section('content')
    @php
        $errorCatalog = collect(config('aurea-demo.error_catalog', []));
        $errorModules = $errorCatalog->pluck('module')->unique()->values()->all();
        $errorTypes = ['Error', 'Advertencia', 'Información'];
        $errorTypeClasses = [
            'Error' => 'text-[#a46255]',
            'Advertencia' => 'text-[#9a7445]',
            'Información' => 'text-[#557184]',
        ];
        $errorCounts = [
            'total' => $errorCatalog->count(),
            'errors' => $errorCatalog->where('type', 'Error')->count(),
            'warnings' => $errorCatalog->where('type', 'Advertencia')->count(),
            'info' => $errorCatalog->where('type', 'Información')->count(),
        ];
    @endphp

    <x-admin.page-header
        :breadcrumbs="[
            [
                'label' => 'Configuración',
                'url' => null,
            ],
            [
                'label' => 'Errores del sistema',
                'url' => null,
            ],
        ]"
        title="Catálogo de errores"
        description="Consulta los códigos y mensajes utilizados por el sistema."
    />

    <section class="mb-7 grid gap-3 sm:grid-cols-2 xl:grid-cols-4" aria-label="Resumen del catálogo de errores">
        @foreach ([
            ['label' => 'Total de códigos', 'value' => $errorCounts['total']],
            ['label' => 'Errores', 'value' => $errorCounts['errors']],
            ['label' => 'Advertencias', 'value' => $errorCounts['warnings']],
            ['label' => 'Informativos', 'value' => $errorCounts['info']],
        ] as $summary)
            <article class="rounded-xl border border-aurea-border bg-aurea-surface px-4 py-4">
                <p class="text-[11px] uppercase tracking-[.14em] text-aurea-muted">{{ $summary['label'] }}</p>
                <p class="mt-2 text-2xl font-semibold tracking-tight text-aurea-ink">{{ $summary['value'] }}</p>
            </article>
        @endforeach
    </section>

    <section data-filter-section class="mb-7 rounded-2xl border border-aurea-border bg-aurea-surface p-5 shadow-[0_5px_20px_rgba(35,42,37,0.03)] sm:p-6" aria-labelledby="error-filters-title">
        <div class="mb-5 flex flex-col justify-between gap-2 sm:flex-row sm:items-center">
            <div>
            <h3 id="error-filters-title" class="font-serif text-xl text-[#28302b]">Filtrar catálogo</h3>
            <p class="mt-1 text-xs text-aurea-muted">Busca rápidamente un código o revisa los errores por módulo.</p>
            </div>
            <button type="button" data-clear-filters class="aurea-focus inline-flex w-fit cursor-pointer items-center rounded-md px-2 py-1.5 text-xs font-medium text-aurea-focus hover:bg-aurea-paper">Limpiar filtros</button>
        </div>
        <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
            <label class="sm:col-span-2 xl:col-span-1">
                <span class="mb-2 block text-xs font-medium text-[#68736c]">Buscar por código, título o mensaje</span>
                <input id="error-search" type="search" placeholder="Ej. INV-001 o stock insuficiente" class="aurea-focus w-full rounded-lg border border-[#dfe2dc] bg-aurea-surface px-3 py-2.5 text-sm text-[#56625a] outline-none placeholder:text-[#adb4ae] focus:border-aurea-gold" />
            </label>
            <x-admin.filter-select id="error-module-filter" name="error_module" label="Módulo" :options="$errorModules" />
            <x-admin.filter-select id="error-type-filter" name="error_type" label="Tipo" :options="$errorTypes" />
        </div>
    </section>

    <section class="overflow-hidden rounded-2xl border border-aurea-border bg-aurea-surface shadow-[0_5px_20px_rgba(35,42,37,0.03)]" aria-labelledby="error-list-title">
        <div class="border-b border-[#eef0ec] px-5 py-5 sm:px-6">
            <h3 id="error-list-title" class="font-serif text-xl text-[#28302b]">Códigos registrados</h3>
            <p class="mt-1 text-xs text-aurea-muted">Los códigos son definidos y mantenidos por desarrollo.</p>
        </div>

        @if ($errorCatalog->isEmpty())
            <x-admin.empty-state title="No hay códigos de error disponibles." description="Los códigos definidos por desarrollo aparecerán aquí." />
        @else
            <div id="error-table-wrapper" class="hidden overflow-x-auto sm:block">
                <table class="admin-table w-full min-w-190 text-left text-sm">
                    <caption class="sr-only">Catálogo de errores del sistema</caption>
                    <thead class="bg-[#fbfbf9] text-[10px] uppercase tracking-wider text-[#9ca49e]">
                        <tr>
                            <th scope="col" class="px-5 py-3 font-medium sm:px-6">Código</th>
                            <th scope="col" class="px-3 py-3 font-medium">Título</th>
                            <th scope="col" class="px-3 py-3 font-medium">Módulo</th>
                            <th scope="col" class="px-3 py-3 font-medium">Tipo</th>
                            <th scope="col" class="px-3 py-3 font-medium">Estado</th>
                            <th scope="col" class="px-5 py-3 text-right font-medium sm:px-6">Acción</th>
                        </tr>
                    </thead>
                    <tbody id="error-table-body" class="divide-y divide-[#f0f1ed]">
                        @foreach ($errorCatalog as $error)
                            <tr class="error-row text-[#5b655e] hover:bg-[#fbfbf9]" data-search="{{ strtolower($error['code'] . ' ' . $error['title'] . ' ' . $error['user_message']) }}" data-module="{{ $error['module'] }}" data-type="{{ $error['type'] }}">
                                <td class="px-5 py-4 font-mono text-xs font-medium text-[#4f4a45] sm:px-6">{{ $error['code'] }}</td>
                                <td class="px-3 py-4 font-medium text-[#343d37]">{{ $error['title'] }}</td>
                                <td class="px-3 py-4">{{ $error['module'] }}</td>
                                <td class="px-3 py-4"><span class="{{ $errorTypeClasses[$error['type']] ?? 'text-aurea-muted' }}" aria-label="Tipo: {{ $error['type'] }}">{{ $error['type'] }}</span></td>
                                <td class="px-3 py-4"><span class="text-[#587262]">{{ $error['status'] }}</span></td>
                                <td class="px-5 py-4 text-right sm:px-6">
                                    <button type="button" class="error-detail-trigger aurea-focus cursor-pointer rounded-md px-2 py-1.5 text-xs font-medium text-aurea-focus hover:bg-aurea-paper" data-code="{{ $error['code'] }}" data-title="{{ $error['title'] }}" data-module="{{ $error['module'] }}" data-type="{{ $error['type'] }}" data-status="{{ $error['status'] }}" data-user-message="{{ $error['user_message'] }}" data-internal-usage="{{ $error['internal_usage'] }}" aria-label="Ver {{ $error['code'] }}" title="Ver detalle">Ver</button>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div id="error-mobile-list" class="divide-y divide-[#f0f1ed] sm:hidden">
                @foreach ($errorCatalog as $error)
                    <article class="error-row px-5 py-4" data-search="{{ strtolower($error['code'] . ' ' . $error['title'] . ' ' . $error['user_message']) }}" data-module="{{ $error['module'] }}" data-type="{{ $error['type'] }}">
                        <div class="flex items-start justify-between gap-3">
                            <div class="min-w-0">
                                <p class="font-mono text-xs font-medium text-[#4f4a45]">{{ $error['code'] }}</p>
                                <p class="mt-1 text-sm font-medium text-[#343d37]">{{ $error['title'] }}</p>
                            </div>
                            <span class="shrink-0 {{ $errorTypeClasses[$error['type']] ?? 'text-aurea-muted' }} text-xs" aria-label="Tipo: {{ $error['type'] }}">{{ $error['type'] }}</span>
                        </div>
                        <div class="mt-3 flex items-center justify-between gap-3 text-xs text-aurea-muted">
                            <span>{{ $error['module'] }} · {{ $error['status'] }}</span>
                            <button type="button" class="error-detail-trigger aurea-focus cursor-pointer rounded-md px-2 py-1.5 font-medium text-aurea-focus hover:bg-aurea-paper" data-code="{{ $error['code'] }}" data-title="{{ $error['title'] }}" data-module="{{ $error['module'] }}" data-type="{{ $error['type'] }}" data-status="{{ $error['status'] }}" data-user-message="{{ $error['user_message'] }}" data-internal-usage="{{ $error['internal_usage'] }}" aria-label="Ver {{ $error['code'] }}" title="Ver detalle">Ver</button>
                        </div>
                    </article>
                @endforeach
            </div>
            <p id="error-no-results" class="hidden px-6 py-10 text-center text-sm text-aurea-muted">No encontramos resultados para los filtros seleccionados.</p>
        @endif
    </section>

    <div id="error-detail-modal" class="fixed inset-0 z-50 hidden items-center justify-center bg-[#222222]/25 px-5 backdrop-blur-sm" role="presentation">
        <div class="w-full max-w-lg rounded-2xl border border-aurea-border bg-white p-6 shadow-[0_18px_50px_rgba(35,42,37,0.16)]" role="dialog" aria-modal="true" aria-labelledby="error-detail-title">
            <div class="flex items-start justify-between gap-4">
                <div>
                    <p id="error-detail-code" class="font-mono text-xs font-medium text-aurea-focus"></p>
                    <h2 id="error-detail-title" class="mt-2 font-serif text-2xl text-aurea-ink"></h2>
                </div>
                <button type="button" id="error-detail-close" class="aurea-focus cursor-pointer rounded-md p-2 text-aurea-muted hover:bg-aurea-paper hover:text-aurea-ink" aria-label="Cerrar detalle" title="Cerrar detalle">
                    <x-admin.icon name="close" class="h-5 w-5" />
                </button>
            </div>
            <dl class="mt-6 grid gap-4 text-sm sm:grid-cols-3">
                <div><dt class="text-xs text-aurea-muted">Módulo</dt><dd id="error-detail-module" class="mt-1 text-[#343d37]"></dd></div>
                <div><dt class="text-xs text-aurea-muted">Tipo</dt><dd id="error-detail-type" class="mt-1 text-[#343d37]"></dd></div>
                <div><dt class="text-xs text-aurea-muted">Estado</dt><dd id="error-detail-status" class="mt-1 text-[#587262]"></dd></div>
            </dl>
            <div class="mt-6 space-y-4 border-t border-[#eef0ec] pt-5 text-sm">
                <div><h3 class="text-xs font-medium text-aurea-muted">Mensaje para el usuario</h3><p id="error-detail-user-message" class="mt-1 leading-6 text-[#343d37]"></p></div>
                <div><h3 class="text-xs font-medium text-aurea-muted">Uso interno</h3><p id="error-detail-internal-usage" class="mt-1 leading-6 text-[#343d37]"></p></div>
            </div>
            <div class="mt-6 flex justify-end"><button type="button" id="error-detail-close-bottom" class="aurea-focus cursor-pointer rounded-lg border border-aurea-border px-4 py-2.5 text-sm font-medium text-aurea-muted hover:bg-aurea-paper">Cerrar</button></div>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        const errorSearch = document.getElementById('error-search');
        const errorModuleFilter = document.getElementById('error-module-filter');
        const errorTypeFilter = document.getElementById('error-type-filter');
        const errorRows = Array.from(document.querySelectorAll('.error-row'));
        const errorNoResults = document.getElementById('error-no-results');
        const errorModal = document.getElementById('error-detail-modal');
        const errorModalClose = document.getElementById('error-detail-close');
        const errorModalCloseBottom = document.getElementById('error-detail-close-bottom');
        let errorDetailTrigger = null;

        const updateErrorRows = () => {
            const search = errorSearch?.value.trim().toLowerCase() ?? '';
            const module = errorModuleFilter?.value ?? '';
            const type = errorTypeFilter?.value ?? '';
            let visibleRows = 0;

            errorRows.forEach((row) => {
                const matches = (!search || row.dataset.search.includes(search)) &&
                    (!module || row.dataset.module === module) &&
                    (!type || row.dataset.type === type);

                row.classList.toggle('hidden', !matches);
                visibleRows += matches ? 1 : 0;
            });

            errorNoResults?.classList.toggle('hidden', visibleRows > 0);
        };

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
                document.getElementById('error-detail-type').textContent = trigger.dataset.type;
                document.getElementById('error-detail-status').textContent = trigger.dataset.status;
                document.getElementById('error-detail-user-message').textContent = trigger.dataset.userMessage;
                document.getElementById('error-detail-internal-usage').textContent = trigger.dataset.internalUsage;
                toggleErrorModal(true);
                errorModalClose?.focus();
            });
        });

        [errorSearch, errorModuleFilter, errorTypeFilter].forEach((control) => {
            control?.addEventListener('input', updateErrorRows);
            control?.addEventListener('change', updateErrorRows);
        });
        document.querySelector('[data-filter-section]')?.addEventListener('filters-cleared', updateErrorRows);
        [errorModalClose, errorModalCloseBottom].forEach((control) => control?.addEventListener('click', () => toggleErrorModal(false)));
        document.addEventListener('keydown', (event) => {
            if (event.key === 'Escape') {
                toggleErrorModal(false);
            }
        });
    </script>
@endpush
