@extends('layouts.admin', ['title' => 'Registro de errores', 'sectionTitle' => 'Registro de errores'])

@section('content')
    @php
        $errorLogs = collect(config('aurea-demo.error_logs', []));
        $logModules = $errorLogs->pluck('module')->unique()->values()->all();
        $logSources = [
            'admin' => 'Administrador',
            'storefront' => 'Tienda',
            'system' => 'Sistema',
        ];
        $logStatuses = ['Pendiente', 'Revisado', 'Resuelto', 'Ignorado'];
        $logStatusClasses = [
            'Pendiente' => 'text-[#a46255]',
            'Revisado' => 'text-[#9a7445]',
            'Resuelto' => 'text-[#587262]',
            'Ignorado' => 'text-[#78716c]',
        ];
        $logCounts = [
            'total' => $errorLogs->count(),
            'pending' => $errorLogs->where('status', 'Pendiente')->count(),
            'reviewed' => $errorLogs->where('status', 'Revisado')->count(),
            'resolved' => $errorLogs->where('status', 'Resuelto')->count(),
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
                'url' => route('settings.errors'),
            ],
            [
                'label' => 'Registro de errores',
                'url' => null,
            ],
        ]"
        title="Registro de errores"
        description="Consulta los errores internos registrados por el sistema."
    />

    <section class="mb-7 grid gap-3 sm:grid-cols-2 xl:grid-cols-4" aria-label="Resumen del registro de errores">
        @foreach ([
            ['label' => 'Total', 'value' => $logCounts['total']],
            ['label' => 'Pendientes', 'value' => $logCounts['pending']],
            ['label' => 'Revisados', 'value' => $logCounts['reviewed']],
            ['label' => 'Resueltos', 'value' => $logCounts['resolved']],
        ] as $summary)
            <article class="rounded-xl border border-aurea-border bg-aurea-surface px-4 py-4">
                <p class="text-[11px] uppercase tracking-[.14em] text-aurea-muted">{{ $summary['label'] }}</p>
                <p class="mt-2 text-2xl font-semibold tracking-tight text-aurea-ink">{{ $summary['value'] }}</p>
            </article>
        @endforeach
    </section>

    <section data-filter-section class="mb-7 rounded-2xl border border-aurea-border bg-aurea-surface p-5 shadow-[0_5px_20px_rgba(35,42,37,0.03)] sm:p-6" aria-labelledby="log-filters-title">
        <div class="mb-5 flex flex-col justify-between gap-2 sm:flex-row sm:items-center">
            <div>
            <h3 id="log-filters-title" class="font-serif text-xl text-[#28302b]">Filtrar registro</h3>
            <p class="mt-1 text-xs text-aurea-muted">Busca un error por código, origen, módulo, usuario o mensaje.</p>
            </div>
            <button type="button" data-clear-filters class="aurea-focus inline-flex w-fit cursor-pointer items-center rounded-md px-2 py-1.5 text-xs font-medium text-aurea-focus hover:bg-aurea-paper">Limpiar filtros</button>
        </div>
        <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
            <label class="sm:col-span-2 xl:col-span-1">
                <span class="mb-2 block text-xs font-medium text-[#68736c]">Buscar error</span>
                <input id="log-search" type="search" placeholder="Ej. PROD-002 o stock" class="aurea-focus w-full rounded-lg border border-[#dfe2dc] bg-aurea-surface px-3 py-2.5 text-sm text-[#56625a] outline-none placeholder:text-[#adb4ae] focus:border-aurea-gold" />
            </label>
            <x-admin.filter-select id="log-module-filter" name="log_module" label="Módulo" :options="$logModules" />
            <label for="log-source-filter">
                <span class="mb-2 block text-xs font-medium text-[#68736c]">Origen</span>
                <select id="log-source-filter" name="log_source" class="aurea-focus w-full appearance-none rounded-lg border border-[#dfe2dc] bg-aurea-surface px-3 py-2.5 text-sm text-[#56625a] outline-none hover:border-aurea-gold-soft">
                    <option value="">Todos</option>
                    @foreach ($logSources as $sourceValue => $sourceLabel)
                        <option value="{{ $sourceValue }}">{{ $sourceLabel }}</option>
                    @endforeach
                </select>
            </label>
            <x-admin.filter-select id="log-status-filter" name="log_status" label="Estado" :options="$logStatuses" />
            <label>
                <span class="mb-2 block text-xs font-medium text-[#68736c]">Fecha desde</span>
                <input id="log-date-filter" type="date" class="aurea-focus w-full rounded-lg border border-[#dfe2dc] bg-aurea-surface px-3 py-2.5 text-sm text-[#56625a] outline-none focus:border-aurea-gold" />
            </label>
        </div>
    </section>

    <section class="overflow-hidden rounded-2xl border border-aurea-border bg-aurea-surface shadow-[0_5px_20px_rgba(35,42,37,0.03)]" aria-labelledby="log-list-title">
        <div class="border-b border-[#eef0ec] px-5 py-5 sm:px-6">
            <h3 id="log-list-title" class="font-serif text-xl text-[#28302b]">Errores registrados</h3>
            <p class="mt-1 text-xs text-aurea-muted">Los detalles técnicos son únicamente para administración.</p>
        </div>

        @if ($errorLogs->isEmpty())
            <x-admin.empty-state title="No hay errores registrados." description="Los errores internos aparecerán aquí cuando exista logging real." />
        @else
            <div class="hidden overflow-x-auto sm:block">
                <table class="admin-table w-full min-w-320 text-left text-sm">
                    <caption class="sr-only">Registro de errores internos</caption>
                    <thead class="bg-[#fbfbf9] text-[10px] uppercase tracking-wider text-[#9ca49e]">
                        <tr>
                            <th scope="col" class="px-5 py-3 font-medium sm:px-6">Fecha</th>
                            <th scope="col" class="px-3 py-3 font-medium">Código</th>
                            <th scope="col" class="px-3 py-3 font-medium">Origen</th>
                            <th scope="col" class="px-3 py-3 font-medium">Módulo</th>
                            <th scope="col" class="px-3 py-3 font-medium">Acción</th>
                            <th scope="col" class="px-3 py-3 font-medium">Usuario</th>
                            <th scope="col" class="px-3 py-3 font-medium">Mensaje</th>
                            <th scope="col" class="px-3 py-3 font-medium">Estado</th>
                            <th scope="col" class="px-5 py-3 text-right font-medium sm:px-6">Acción</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-[#f0f1ed]">
                        @foreach ($errorLogs as $log)
                            <tr class="log-row text-[#5b655e] hover:bg-[#fbfbf9]" data-search="{{ strtolower($log['code'] . ' ' . $log['module'] . ' ' . $log['action'] . ' ' . $log['user'] . ' ' . $log['message']) }}" data-source="{{ $log['source'] }}" data-module="{{ $log['module'] }}" data-status="{{ $log['status'] }}" data-date="{{ \Illuminate\Support\Carbon::parse($log['date'])->format('Y-m-d') }}">
                                <td class="whitespace-nowrap px-5 py-4 text-xs text-[#68736c] sm:px-6">{{ $log['date'] }}</td>
                                <td class="px-3 py-4 font-mono text-xs font-medium text-[#4f4a45]">{{ $log['code'] }}</td>
                                <td class="px-3 py-4 text-xs font-medium text-[#5b655e]">{{ $logSources[$log['source']] ?? $log['source'] }}</td>
                                <td class="px-3 py-4">{{ $log['module'] }}</td>
                                <td class="px-3 py-4">{{ $log['action'] }}</td>
                                <td class="px-3 py-4">{{ $log['user'] }}</td>
                                <td class="max-w-xs px-3 py-4 text-xs">{{ $log['message'] }}</td>
                                <td class="px-3 py-4"><span class="{{ $logStatusClasses[$log['status']] ?? 'text-aurea-muted' }}" aria-label="Estado: {{ $log['status'] }}">{{ $log['status'] }}</span></td>
                                <td class="px-5 py-4 text-right sm:px-6">
                                    <button type="button" class="log-detail-trigger aurea-focus cursor-pointer rounded-md px-2 py-1.5 text-xs font-medium text-aurea-focus hover:bg-aurea-paper" data-code="{{ $log['code'] }}" data-date="{{ $log['date'] }}" data-source="{{ $logSources[$log['source']] ?? $log['source'] }}" data-module="{{ $log['module'] }}" data-action="{{ $log['action'] }}" data-user="{{ $log['user'] }}" data-route="{{ $log['route'] }}" data-entity="{{ $log['entity'] }}" data-message="{{ $log['message'] }}" data-detail="{{ $log['internal_detail'] }}" data-exception="{{ $log['exception'] }}" data-file="{{ $log['file'] }}" data-line="{{ $log['line'] }}" data-status="{{ $log['status'] }}" data-product="{{ $log['product_context']['product'] ?? '' }}" data-sku="{{ $log['product_context']['sku'] ?? '' }}" data-selection="{{ $log['product_context']['selection'] ?? '' }}" aria-label="Ver detalle de {{ $log['code'] }}" title="Ver detalle">Ver</button>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="divide-y divide-[#f0f1ed] sm:hidden">
                @foreach ($errorLogs as $log)
                    <article class="log-row px-5 py-4" data-search="{{ strtolower($log['code'] . ' ' . $log['module'] . ' ' . $log['action'] . ' ' . $log['user'] . ' ' . $log['message']) }}" data-source="{{ $log['source'] }}" data-module="{{ $log['module'] }}" data-status="{{ $log['status'] }}" data-date="{{ \Illuminate\Support\Carbon::parse($log['date'])->format('Y-m-d') }}">
                        <div class="flex items-start justify-between gap-3">
                            <div class="min-w-0">
                                <p class="font-mono text-xs font-medium text-[#4f4a45]">{{ $log['code'] }}</p>
                                <p class="mt-1 text-sm font-medium text-[#343d37]">{{ $log['action'] }}</p>
                                <p class="mt-1 text-xs text-aurea-muted">{{ $log['date'] }} · {{ $logSources[$log['source']] ?? $log['source'] }} · {{ $log['module'] }}</p>
                            </div>
                            <span class="shrink-0 {{ $logStatusClasses[$log['status']] ?? 'text-aurea-muted' }} text-xs">{{ $log['status'] }}</span>
                        </div>
                        <div class="mt-3 flex items-center justify-between gap-3 text-xs text-aurea-muted">
                            <span class="truncate">{{ $log['message'] }}</span>
                            <button type="button" class="log-detail-trigger aurea-focus cursor-pointer rounded-md px-2 py-1.5 font-medium text-aurea-focus hover:bg-aurea-paper" data-code="{{ $log['code'] }}" data-date="{{ $log['date'] }}" data-source="{{ $logSources[$log['source']] ?? $log['source'] }}" data-module="{{ $log['module'] }}" data-action="{{ $log['action'] }}" data-user="{{ $log['user'] }}" data-route="{{ $log['route'] }}" data-entity="{{ $log['entity'] }}" data-message="{{ $log['message'] }}" data-detail="{{ $log['internal_detail'] }}" data-exception="{{ $log['exception'] }}" data-file="{{ $log['file'] }}" data-line="{{ $log['line'] }}" data-status="{{ $log['status'] }}" data-product="{{ $log['product_context']['product'] ?? '' }}" data-sku="{{ $log['product_context']['sku'] ?? '' }}" data-selection="{{ $log['product_context']['selection'] ?? '' }}" aria-label="Ver detalle de {{ $log['code'] }}" title="Ver detalle">Ver</button>
                        </div>
                    </article>
                @endforeach
            </div>
            <p id="log-no-results" class="hidden px-6 py-10 text-center text-sm text-aurea-muted">No encontramos resultados para los filtros seleccionados.</p>
        @endif
    </section>

    <div id="log-detail-modal" class="fixed inset-0 z-50 hidden items-center justify-center overflow-y-auto bg-[#222222]/25 px-5 py-6 backdrop-blur-sm" role="presentation">
        <div class="my-auto w-full max-w-2xl rounded-2xl border border-aurea-border bg-white p-6 shadow-[0_18px_50px_rgba(35,42,37,0.16)]" role="dialog" aria-modal="true" aria-labelledby="log-detail-title">
            <div class="flex items-start justify-between gap-4">
                <div>
                    <p id="log-detail-code" class="font-mono text-xs font-medium text-aurea-focus"></p>
                    <h2 id="log-detail-title" class="mt-2 font-serif text-2xl text-aurea-ink">Detalle del error</h2>
                </div>
                <button type="button" id="log-detail-close" class="aurea-focus cursor-pointer rounded-md p-2 text-aurea-muted hover:bg-aurea-paper hover:text-aurea-ink" aria-label="Cerrar detalle" title="Cerrar detalle"><x-admin.icon name="close" class="h-5 w-5" /></button>
            </div>
            <dl class="mt-6 grid gap-4 text-sm sm:grid-cols-3">
                <div><dt class="text-xs text-aurea-muted">Fecha y hora</dt><dd id="log-detail-date" class="mt-1 text-[#343d37]"></dd></div>
                <div><dt class="text-xs text-aurea-muted">Origen</dt><dd id="log-detail-source" class="mt-1 text-[#343d37]"></dd></div>
                <div><dt class="text-xs text-aurea-muted">Módulo</dt><dd id="log-detail-module" class="mt-1 text-[#343d37]"></dd></div>
                <div><dt class="text-xs text-aurea-muted">Estado</dt><dd id="log-detail-status" class="mt-1 text-[#587262]"></dd></div>
                <div><dt class="text-xs text-aurea-muted">Acción</dt><dd id="log-detail-action" class="mt-1 text-[#343d37]"></dd></div>
                <div><dt class="text-xs text-aurea-muted">Usuario</dt><dd id="log-detail-user" class="mt-1 text-[#343d37]"></dd></div>
                <div><dt class="text-xs text-aurea-muted">Entidad afectada</dt><dd id="log-detail-entity" class="mt-1 break-words text-[#343d37]"></dd></div>
            </dl>
            <div class="mt-6 space-y-4 border-t border-[#eef0ec] pt-5 text-sm">
                <div><h3 class="text-xs font-medium text-aurea-muted">Ruta</h3><p id="log-detail-route" class="mt-1 break-words font-mono text-xs text-[#343d37]"></p></div>
                <div><h3 class="text-xs font-medium text-aurea-muted">Mensaje mostrado al usuario</h3><p id="log-detail-message" class="mt-1 leading-6 text-[#343d37]"></p></div>
                <div><h3 class="text-xs font-medium text-aurea-muted">Detalle interno</h3><p id="log-detail-detail" class="mt-1 leading-6 text-[#343d37]"></p></div>
                <div id="log-detail-product-context" class="hidden rounded-xl border border-[#e7e2da] bg-[#fbf9f6] p-4"><h3 class="text-xs font-medium text-aurea-muted">Contexto de producto</h3><dl class="mt-3 grid gap-3 text-xs sm:grid-cols-3"><div><dt class="text-[#a39a8e]">Producto</dt><dd id="log-detail-product" class="mt-1 text-[#4f4a45]"></dd></div><div><dt class="text-[#a39a8e]">SKU</dt><dd id="log-detail-sku" class="mt-1 font-mono text-[#4f4a45]"></dd></div><div class="sm:col-span-1"><dt class="text-[#a39a8e]">Selección</dt><dd id="log-detail-selection" class="mt-1 text-[#4f4a45]"></dd></div></dl></div>
                <div class="rounded-xl bg-[#fbf9f6] p-4"><h3 class="text-xs font-medium text-aurea-muted">Información técnica</h3><dl class="mt-3 grid gap-3 text-xs sm:grid-cols-3"><div><dt class="text-[#a39a8e]">Tipo de excepción</dt><dd id="log-detail-exception" class="mt-1 break-words font-mono text-[#4f4a45]"></dd></div><div class="sm:col-span-2"><dt class="text-[#a39a8e]">Archivo</dt><dd id="log-detail-file" class="mt-1 break-words font-mono text-[#4f4a45]"></dd></div><div><dt class="text-[#a39a8e]">Línea</dt><dd id="log-detail-line" class="mt-1 font-mono text-[#4f4a45]"></dd></div></dl></div>
            </div>
            <div class="mt-6 flex justify-end"><button type="button" id="log-detail-close-bottom" class="aurea-focus cursor-pointer rounded-lg border border-aurea-border px-4 py-2.5 text-sm font-medium text-aurea-muted hover:bg-aurea-paper">Cerrar</button></div>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        const logSearch = document.getElementById('log-search');
        const logModuleFilter = document.getElementById('log-module-filter');
        const logSourceFilter = document.getElementById('log-source-filter');
        const logStatusFilter = document.getElementById('log-status-filter');
        const logDateFilter = document.getElementById('log-date-filter');
        const logRows = Array.from(document.querySelectorAll('.log-row'));
        const logNoResults = document.getElementById('log-no-results');
        const logModal = document.getElementById('log-detail-modal');
        const logModalClose = document.getElementById('log-detail-close');
        const logModalCloseBottom = document.getElementById('log-detail-close-bottom');
        let logDetailTrigger = null;

        const updateLogRows = () => {
            const search = logSearch?.value.trim().toLowerCase() ?? '';
            const module = logModuleFilter?.value ?? '';
            const source = logSourceFilter?.value ?? '';
            const status = logStatusFilter?.value ?? '';
            const date = logDateFilter?.value ?? '';
            let visibleRows = 0;

            logRows.forEach((row) => {
                const matches = (!search || row.dataset.search.includes(search)) &&
                    (!source || row.dataset.source === source) &&
                    (!module || row.dataset.module === module) &&
                    (!status || row.dataset.status === status) &&
                    (!date || row.dataset.date >= date);

                row.classList.toggle('hidden', !matches);
                visibleRows += matches ? 1 : 0;
            });

            logNoResults?.classList.toggle('hidden', visibleRows > 0);
        };

        const toggleLogModal = (open) => {
            logModal?.classList.toggle('hidden', !open);
            logModal?.classList.toggle('flex', open);

            if (!open) {
                logDetailTrigger?.focus();
                logDetailTrigger = null;
            }
        };

        document.querySelectorAll('.log-detail-trigger').forEach((trigger) => {
            trigger.addEventListener('click', () => {
                logDetailTrigger = trigger;
                const fields = ['code', 'date', 'source', 'module', 'action', 'user', 'route', 'entity', 'message', 'detail', 'exception', 'file', 'line', 'status'];

                fields.forEach((field) => {
                    document.getElementById(`log-detail-${field}`).textContent = trigger.dataset[field];
                });

                const productContext = document.getElementById('log-detail-product-context');
                const hasProductContext = trigger.dataset.product || trigger.dataset.sku || trigger.dataset.selection;

                productContext.classList.toggle('hidden', !hasProductContext);
                document.getElementById('log-detail-product').textContent = trigger.dataset.product;
                document.getElementById('log-detail-sku').textContent = trigger.dataset.sku;
                document.getElementById('log-detail-selection').textContent = trigger.dataset.selection;

                toggleLogModal(true);
                logModalClose?.focus();
            });
        });

        [logSearch, logModuleFilter, logSourceFilter, logStatusFilter, logDateFilter].forEach((control) => {
            control?.addEventListener('input', updateLogRows);
            control?.addEventListener('change', updateLogRows);
        });
        document.querySelector('[data-filter-section]')?.addEventListener('filters-cleared', updateLogRows);
        [logModalClose, logModalCloseBottom].forEach((control) => control?.addEventListener('click', () => toggleLogModal(false)));
        document.addEventListener('keydown', (event) => {
            if (event.key === 'Escape') {
                toggleLogModal(false);
            }
        });
    </script>
@endpush
