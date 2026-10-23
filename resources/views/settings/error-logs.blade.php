@php
    use App\Support\ErrorLogStatus;

    $sourceLabels = [
        'backend' => 'Backend',
        'admin' => 'Administrador',
        'storefront' => 'Tienda',
        'system' => 'Sistema',
    ];
    $statusClasses = [
        ErrorLogStatus::PENDING => 'text-[#a46255] dark:text-[#e59b8b]',
        ErrorLogStatus::REVIEWED => 'text-[#9a7445] dark:text-[#d8b274]',
        ErrorLogStatus::RESOLVED => 'text-[#587262] dark:text-[#9ac7a5]',
        ErrorLogStatus::IGNORED => 'text-[#78716c] dark:text-[#aaa79f]',
    ];
@endphp

@extends('layouts.admin', ['title' => 'Registro de errores', 'sectionTitle' => 'Registro de errores'])

@section('content')
    <x-admin.page-header
        :breadcrumbs="[
            ['label' => 'Configuración', 'url' => null],
            ['label' => 'Errores del sistema', 'url' => route('settings.errors')],
            ['label' => 'Registro de errores', 'url' => null],
        ]"
        title="Registro de errores"
        description="Consulta los errores internos registrados por el sistema."
    />

    <x-admin.flash-messages />

    <section class="mb-7 grid gap-3 sm:grid-cols-2 xl:grid-cols-4" aria-label="Resumen del registro de errores">
        @foreach ([
            ['label' => 'Total', 'value' => $summary['total']],
            ['label' => 'Pendientes', 'value' => $summary['pending']],
            ['label' => 'Revisados', 'value' => $summary['reviewed']],
            ['label' => 'Resueltos', 'value' => $summary['resolved']],
        ] as $card)
            <article class="rounded-xl border border-aurea-border bg-aurea-surface px-4 py-4">
                <p class="text-[11px] uppercase tracking-[.14em] text-aurea-muted">{{ $card['label'] }}</p>
                <p class="mt-2 text-2xl font-semibold tracking-tight text-aurea-ink">{{ $card['value'] }}</p>
            </article>
        @endforeach
    </section>

    <section class="mb-7 rounded-2xl border border-aurea-border bg-aurea-surface p-5 shadow-[0_5px_20px_rgba(35,42,37,0.03)] sm:p-6" aria-labelledby="log-filters-title">
        <div class="mb-5 flex flex-col justify-between gap-2 sm:flex-row sm:items-center">
            <div>
                <h3 id="log-filters-title" class="font-serif text-xl text-[#28302b] dark:text-[#f1eee8]">Filtrar registro</h3>
                <p class="mt-1 text-xs text-aurea-muted">Busca errores por sus datos persistidos.</p>
            </div>
            <a href="{{ route('settings.errors.logs') }}" class="aurea-focus inline-flex w-fit rounded-md px-2 py-1.5 text-xs font-medium text-aurea-focus hover:bg-aurea-paper dark:hover:bg-[#3a3b37]">Limpiar filtros</a>
        </div>

        <form method="GET" action="{{ route('settings.errors.logs') }}" class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
            <label class="sm:col-span-2 xl:col-span-1">
                <span class="mb-2 block text-xs font-medium text-[#68736c] dark:text-[#c2c6c1]">Buscar error</span>
                <input type="search" name="search" value="{{ request('search') }}" placeholder="Código, módulo, usuario o mensaje" class="aurea-focus w-full rounded-lg border border-[#dfe2dc] bg-aurea-surface px-3 py-2.5 text-sm text-[#56625a] outline-none placeholder:text-[#adb4ae] focus:border-aurea-gold dark:border-[#4b4a45] dark:text-[#f1eee8] dark:placeholder:text-[#8f958f]" />
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
                <span class="mb-2 block text-xs font-medium text-[#68736c] dark:text-[#c2c6c1]">Origen</span>
                <select name="source" class="aurea-focus w-full appearance-none rounded-lg border border-[#dfe2dc] bg-aurea-surface px-3 py-2.5 text-sm text-[#56625a] outline-none dark:border-[#4b4a45] dark:bg-[#292b29] dark:text-[#f1eee8]">
                    <option value="">Todos</option>
                    @foreach ($sources as $source)
                        <option value="{{ $source }}" @selected(request('source') === $source)>{{ $sourceLabels[$source] ?? $source }}</option>
                    @endforeach
                </select>
            </label>
            <label>
                <span class="mb-2 block text-xs font-medium text-[#68736c] dark:text-[#c2c6c1]">Estado</span>
                <select name="status" class="aurea-focus w-full appearance-none rounded-lg border border-[#dfe2dc] bg-aurea-surface px-3 py-2.5 text-sm text-[#56625a] outline-none dark:border-[#4b4a45] dark:bg-[#292b29] dark:text-[#f1eee8]">
                    <option value="">Todos</option>
                    @foreach ($statuses as $value => $label)
                        <option value="{{ $value }}" @selected(request('status') === $value)>{{ $label }}</option>
                    @endforeach
                </select>
            </label>
            <label>
                <span class="mb-2 block text-xs font-medium text-[#68736c] dark:text-[#c2c6c1]">Fecha desde</span>
                <input type="date" name="date_from" value="{{ request('date_from') }}" class="aurea-focus w-full rounded-lg border border-[#dfe2dc] bg-aurea-surface px-3 py-2.5 text-sm text-[#56625a] outline-none focus:border-aurea-gold dark:border-[#4b4a45] dark:text-[#f1eee8]" />
            </label>
            <label>
                <span class="mb-2 block text-xs font-medium text-[#68736c] dark:text-[#c2c6c1]">Fecha hasta</span>
                <input type="date" name="date_to" value="{{ request('date_to') }}" class="aurea-focus w-full rounded-lg border border-[#dfe2dc] bg-aurea-surface px-3 py-2.5 text-sm text-[#56625a] outline-none focus:border-aurea-gold dark:border-[#4b4a45] dark:text-[#f1eee8]" />
            </label>
            <div class="flex flex-col gap-3 sm:col-span-2 xl:col-span-2 sm:flex-row sm:justify-end sm:self-end">
                <button type="submit" class="aurea-focus inline-flex w-full items-center justify-center rounded-lg bg-aurea-gold px-4 py-2.5 text-sm font-medium text-white shadow-sm transition hover:bg-[#9f793f] sm:w-auto">Aplicar filtros</button>
            </div>
        </form>
    </section>

    <section class="overflow-hidden rounded-2xl border border-aurea-border bg-aurea-surface shadow-[0_5px_20px_rgba(35,42,37,0.03)]" aria-labelledby="log-list-title">
        <div class="border-b border-[#eef0ec] px-5 py-5 dark:border-[#3b3d39] sm:px-6">
            <h3 id="log-list-title" class="font-serif text-xl text-[#28302b] dark:text-[#f1eee8]">Errores registrados</h3>
            <p class="mt-1 text-xs text-aurea-muted">Mostrando {{ $errorLogs->total() }} registros persistidos.</p>
        </div>

        @if ($errorLogs->isEmpty())
            <x-admin.empty-state title="No hay errores registrados." description="Los errores relevantes aparecerán aquí cuando sean capturados." />
        @else
            <div class="hidden overflow-x-auto sm:block">
                <table class="admin-table w-full min-w-320 text-left text-sm">
                    <caption class="sr-only">Registro persistente de errores internos</caption>
                    <thead class="bg-[#fbfbf9] text-[10px] uppercase tracking-wider text-[#9ca49e] dark:bg-[#292b29]">
                        <tr>
                            <th scope="col" class="px-5 py-3 font-medium sm:px-6">Fecha</th>
                            <th scope="col" class="px-3 py-3 font-medium">Código</th>
                            <th scope="col" class="px-3 py-3 font-medium">Origen</th>
                            <th scope="col" class="px-3 py-3 font-medium">Módulo</th>
                            <th scope="col" class="px-3 py-3 font-medium">Acción</th>
                            <th scope="col" class="px-3 py-3 font-medium">Usuario</th>
                            <th scope="col" class="px-3 py-3 font-medium">Mensaje</th>
                            <th scope="col" class="px-3 py-3 font-medium">Estado</th>
                            <th scope="col" class="px-5 py-3 text-right font-medium sm:px-6">Detalle</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-[#f0f1ed] dark:divide-[#3b3d39]">
                        @foreach ($errorLogs as $errorLog)
                            @php
                                $availableStatuses = collect($statuses)->filter(fn ($label, $value): bool => ErrorLogStatus::canTransition($errorLog->status, $value));
                                $contextJson = json_encode($errorLog->context ?? [], JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT);
                            @endphp
                            <tr class="text-[#5b655e] hover:bg-[#fbfbf9] dark:text-[#c2c6c1] dark:hover:bg-[#30322f]">
                                <td class="whitespace-nowrap px-5 py-4 text-xs text-[#68736c] dark:text-[#c2c6c1] sm:px-6">{{ $errorLog->occurred_at?->format('d M Y, H:i') ?? '—' }}</td>
                                <td class="px-3 py-4 font-mono text-xs font-medium text-[#4f4a45] dark:text-[#e6cda9]">{{ $errorLog->errorCode?->code ?? '—' }}</td>
                                <td class="px-3 py-4 text-xs font-medium">{{ $sourceLabels[$errorLog->source] ?? $errorLog->source }}</td>
                                <td class="px-3 py-4">{{ $errorLog->module ?? '—' }}</td>
                                <td class="px-3 py-4">{{ $errorLog->action ?? '—' }}</td>
                                <td class="px-3 py-4">{{ $errorLog->user?->name ?? 'Sistema' }}</td>
                                <td class="max-w-xs px-3 py-4 text-xs">{{ $errorLog->message }}</td>
                                <td class="px-3 py-4"><span class="{{ $statusClasses[$errorLog->status] ?? 'text-aurea-muted' }}">{{ $statuses[$errorLog->status] ?? $errorLog->status }}</span></td>
                                <td class="px-5 py-4 text-right sm:px-6">
                                    <div class="flex items-center justify-end gap-2">
                                        <button type="button" class="log-detail-trigger aurea-focus rounded-md px-2 py-1.5 text-xs font-medium text-aurea-focus hover:bg-aurea-paper dark:hover:bg-[#3a3b37]" data-date="{{ $errorLog->occurred_at?->format('d M Y, H:i') ?? '—' }}" data-code="{{ $errorLog->errorCode?->code ?? '—' }}" data-source="{{ $sourceLabels[$errorLog->source] ?? $errorLog->source }}" data-module="{{ $errorLog->module ?? '—' }}" data-action="{{ $errorLog->action ?? '—' }}" data-user="{{ $errorLog->user?->name ?? 'Sistema' }}" data-entity="{{ trim(($errorLog->entity_type ?? '').' '.($errorLog->entity_id ?? '')) ?: '—' }}" data-route="{{ $errorLog->route ?? '—' }}" data-message="{{ $errorLog->message }}" data-exception="{{ $errorLog->exception_type ?? '—' }}" data-file="{{ $errorLog->file ?? '—' }}" data-line="{{ $errorLog->line ?? '—' }}" data-status="{{ $statuses[$errorLog->status] ?? $errorLog->status }}" data-context="{{ $contextJson }}" aria-label="Ver detalle del error" title="Ver detalle">Ver</button>
                                        @if ($availableStatuses->isNotEmpty())
                                            <form method="POST" action="{{ route('settings.errors.logs.status', $errorLog) }}" class="flex items-center gap-1">
                                                @csrf
                                                @method('PATCH')
                                                <select name="status" class="aurea-focus rounded-md border border-[#dfe2dc] bg-aurea-surface px-2 py-1.5 text-xs text-[#56625a] dark:border-[#4b4a45] dark:bg-[#292b29] dark:text-[#f1eee8]" aria-label="Nuevo estado">
                                                    @foreach ($availableStatuses as $value => $label)
                                                        <option value="{{ $value }}">{{ $label }}</option>
                                                    @endforeach
                                                </select>
                                                <button type="submit" class="aurea-focus rounded-md px-2 py-1.5 text-xs font-medium text-[#68736c] hover:bg-aurea-paper dark:text-[#d0d4cf] dark:hover:bg-[#3a3b37]">Guardar</button>
                                            </form>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="divide-y divide-[#f0f1ed] dark:divide-[#3b3d39] sm:hidden">
                @foreach ($errorLogs as $errorLog)
                    @php
                        $availableStatuses = collect($statuses)->filter(fn ($label, $value): bool => ErrorLogStatus::canTransition($errorLog->status, $value));
                        $contextJson = json_encode($errorLog->context ?? [], JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT);
                    @endphp
                    <article class="px-5 py-4">
                        <div class="flex items-start justify-between gap-3">
                            <div class="min-w-0">
                                <p class="font-mono text-xs font-medium text-[#4f4a45] dark:text-[#e6cda9]">{{ $errorLog->errorCode?->code ?? 'Sin código' }}</p>
                                <p class="mt-1 text-sm font-medium text-[#343d37] dark:text-[#f1eee8]">{{ $errorLog->message }}</p>
                                <p class="mt-1 text-xs text-aurea-muted">{{ $errorLog->occurred_at?->format('d M Y, H:i') ?? '—' }} · {{ $sourceLabels[$errorLog->source] ?? $errorLog->source }} · {{ $errorLog->module ?? '—' }}</p>
                            </div>
                            <span class="shrink-0 text-xs {{ $statusClasses[$errorLog->status] ?? 'text-aurea-muted' }}">{{ $statuses[$errorLog->status] ?? $errorLog->status }}</span>
                        </div>
                        <div class="mt-3 flex justify-end">
                            <button type="button" class="log-detail-trigger aurea-focus rounded-md px-2 py-1.5 text-xs font-medium text-aurea-focus hover:bg-aurea-paper dark:hover:bg-[#3a3b37]" data-date="{{ $errorLog->occurred_at?->format('d M Y, H:i') ?? '—' }}" data-code="{{ $errorLog->errorCode?->code ?? '—' }}" data-source="{{ $sourceLabels[$errorLog->source] ?? $errorLog->source }}" data-module="{{ $errorLog->module ?? '—' }}" data-action="{{ $errorLog->action ?? '—' }}" data-user="{{ $errorLog->user?->name ?? 'Sistema' }}" data-entity="{{ trim(($errorLog->entity_type ?? '').' '.($errorLog->entity_id ?? '')) ?: '—' }}" data-route="{{ $errorLog->route ?? '—' }}" data-message="{{ $errorLog->message }}" data-exception="{{ $errorLog->exception_type ?? '—' }}" data-file="{{ $errorLog->file ?? '—' }}" data-line="{{ $errorLog->line ?? '—' }}" data-status="{{ $statuses[$errorLog->status] ?? $errorLog->status }}" data-context="{{ $contextJson }}" aria-label="Ver detalle del error" title="Ver detalle">Ver detalle</button>
                            @if ($availableStatuses->isNotEmpty())
                                <form method="POST" action="{{ route('settings.errors.logs.status', $errorLog) }}" class="flex items-center gap-1">
                                    @csrf
                                    @method('PATCH')
                                    <select name="status" class="aurea-focus rounded-md border border-[#dfe2dc] bg-aurea-surface px-2 py-1.5 text-xs text-[#56625a] dark:border-[#4b4a45] dark:bg-[#292b29] dark:text-[#f1eee8]" aria-label="Nuevo estado">
                                        @foreach ($availableStatuses as $value => $label)
                                            <option value="{{ $value }}">{{ $label }}</option>
                                        @endforeach
                                    </select>
                                    <button type="submit" class="aurea-focus rounded-md px-2 py-1.5 text-xs font-medium text-[#68736c] dark:text-[#d0d4cf]">Guardar</button>
                                </form>
                            @endif
                        </div>
                    </article>
                @endforeach
            </div>

            <div class="border-t border-[#eef0ec] px-5 py-4 dark:border-[#3b3d39] sm:px-6">
                {{ $errorLogs->links() }}
            </div>
        @endif
    </section>

    <div id="log-detail-modal" class="fixed inset-0 z-50 hidden items-center justify-center overflow-y-auto bg-[#222222]/25 px-5 py-6 backdrop-blur-sm" role="presentation">
        <div class="my-auto w-full max-w-2xl rounded-2xl border border-aurea-border bg-white p-6 shadow-[0_18px_50px_rgba(35,42,37,0.16)] dark:bg-[#2d302e]" role="dialog" aria-modal="true" aria-labelledby="log-detail-title">
            <div class="flex items-start justify-between gap-4">
                <div>
                    <p id="log-detail-code" class="font-mono text-xs font-medium text-aurea-focus"></p>
                    <h2 id="log-detail-title" class="mt-2 font-serif text-2xl text-aurea-ink dark:text-[#f1eee8]">Detalle del error</h2>
                </div>
                <button type="button" id="log-detail-close" class="aurea-focus rounded-md p-2 text-aurea-muted hover:bg-aurea-paper dark:hover:bg-[#3a3b37]" aria-label="Cerrar detalle" title="Cerrar detalle"><x-admin.icon name="close" class="h-5 w-5" /></button>
            </div>
            <dl class="mt-6 grid gap-4 text-sm sm:grid-cols-3">
                <div><dt class="text-xs text-aurea-muted">Fecha y hora</dt><dd id="log-detail-date" class="mt-1 text-[#343d37] dark:text-[#e5e8e3]"></dd></div>
                <div><dt class="text-xs text-aurea-muted">Origen</dt><dd id="log-detail-source" class="mt-1 text-[#343d37] dark:text-[#e5e8e3]"></dd></div>
                <div><dt class="text-xs text-aurea-muted">Módulo</dt><dd id="log-detail-module" class="mt-1 text-[#343d37] dark:text-[#e5e8e3]"></dd></div>
                <div><dt class="text-xs text-aurea-muted">Estado</dt><dd id="log-detail-status" class="mt-1 text-[#587262] dark:text-[#9ac7a5]"></dd></div>
                <div><dt class="text-xs text-aurea-muted">Acción</dt><dd id="log-detail-action" class="mt-1 text-[#343d37] dark:text-[#e5e8e3]"></dd></div>
                <div><dt class="text-xs text-aurea-muted">Usuario</dt><dd id="log-detail-user" class="mt-1 text-[#343d37] dark:text-[#e5e8e3]"></dd></div>
                <div><dt class="text-xs text-aurea-muted">Entidad afectada</dt><dd id="log-detail-entity" class="mt-1 break-words text-[#343d37] dark:text-[#e5e8e3]"></dd></div>
            </dl>
            <div class="mt-6 space-y-4 border-t border-[#eef0ec] pt-5 text-sm dark:border-[#3b3d39]">
                <div><h3 class="text-xs font-medium text-aurea-muted">Ruta</h3><p id="log-detail-route" class="mt-1 break-words font-mono text-xs text-[#343d37] dark:text-[#e5e8e3]"></p></div>
                <div><h3 class="text-xs font-medium text-aurea-muted">Mensaje</h3><p id="log-detail-message" class="mt-1 leading-6 text-[#343d37] dark:text-[#e5e8e3]"></p></div>
                <div class="rounded-xl bg-[#fbf9f6] p-4 dark:bg-[#343633]"><h3 class="text-xs font-medium text-aurea-muted">Información técnica</h3><dl class="mt-3 grid gap-3 text-xs sm:grid-cols-3"><div><dt class="text-[#a39a8e] dark:text-[#b9ad9c]">Tipo de excepción</dt><dd id="log-detail-exception" class="mt-1 break-words font-mono text-[#4f4a45] dark:text-[#e5e8e3]"></dd></div><div class="sm:col-span-2"><dt class="text-[#a39a8e] dark:text-[#b9ad9c]">Archivo</dt><dd id="log-detail-file" class="mt-1 break-words font-mono text-[#4f4a45] dark:text-[#e5e8e3]"></dd></div><div><dt class="text-[#a39a8e] dark:text-[#b9ad9c]">Línea</dt><dd id="log-detail-line" class="mt-1 font-mono text-[#4f4a45] dark:text-[#e5e8e3]"></dd></div></dl></div>
                <div><h3 class="text-xs font-medium text-aurea-muted">Contexto sanitizado</h3><pre id="log-detail-context" class="mt-1 max-h-48 overflow-auto whitespace-pre-wrap break-words rounded-lg bg-[#f5f3ef] p-3 font-mono text-xs text-[#4f4a45] dark:bg-[#343633] dark:text-[#e5e8e3]"></pre></div>
            </div>
            <div class="mt-6 flex justify-end"><button type="button" id="log-detail-close-bottom" class="aurea-focus rounded-lg border border-aurea-border px-4 py-2.5 text-sm font-medium text-aurea-muted hover:bg-aurea-paper dark:hover:bg-[#3a3b37]">Cerrar</button></div>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        const logModal = document.getElementById('log-detail-modal');
        const logModalClose = document.getElementById('log-detail-close');
        const logModalCloseBottom = document.getElementById('log-detail-close-bottom');
        let logDetailTrigger = null;

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
                const fields = ['code', 'date', 'source', 'module', 'action', 'user', 'entity', 'route', 'message', 'exception', 'file', 'line', 'status'];

                fields.forEach((field) => {
                    document.getElementById(`log-detail-${field}`).textContent = trigger.dataset[field] || '—';
                });

                let context = {};

                try {
                    context = JSON.parse(trigger.dataset.context || '{}');
                } catch (error) {
                    context = { error: 'No fue posible mostrar el contexto.' };
                }

                document.getElementById('log-detail-context').textContent = JSON.stringify(context, null, 2);
                toggleLogModal(true);
                logModalClose?.focus();
            });
        });

        [logModalClose, logModalCloseBottom].forEach((control) => control?.addEventListener('click', () => toggleLogModal(false)));
        document.addEventListener('keydown', (event) => {
            if (event.key === 'Escape') {
                toggleLogModal(false);
            }
        });
    </script>
@endpush
