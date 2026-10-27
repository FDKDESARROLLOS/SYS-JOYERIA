@extends('layouts.admin', ['title' => 'Detalle de inventario', 'sectionTitle' => 'Detalle de inventario'])

@section('content')
    <x-admin.page-header :breadcrumbs="[
        ['label' => 'Inventario', 'url' => route('inventory.stock')],
        ['label' => 'Movimientos', 'url' => route('inventory.movements.index')],
        ['label' => $inventoryStock->productVariant->sku, 'url' => null],
    ]" :title="$inventoryStock->productVariant->product->name"
        description="Consulta el stock y el historial de movimientos de esta variante.">
        <x-slot:actions>
            <a href="{{ route('inventory.movements.index') }}"
                class="aurea-focus inline-flex items-center justify-center rounded-lg border border-[#dfe2dc] px-4 py-2.5 text-sm font-medium text-[#68736c] hover:border-aurea-gold hover:text-aurea-focus">
                Volver a movimientos
            </a>
            @if ($inventoryRow['is_out_of_catalog'])
                <p class="max-w-xs text-right text-xs text-[#947142]">
                    Este artículo está fuera de catálogo. El inventario se conserva solo para consulta.
                </p>
            @else
                <a href="{{ route('inventory.movements.create', ['variant' => $inventoryStock->id]) }}"
                    class="aurea-focus inline-flex w-fit items-center gap-2 rounded-lg bg-aurea-gold px-4 py-2.5 text-sm font-medium text-white shadow-sm transition hover:bg-[#9f793f]">
                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7"
                        aria-hidden="true">
                        <path d="M12 5v14M5 12h14" />
                    </svg>
                    Nuevo movimiento
                </a>
            @endif
        </x-slot:actions>
    </x-admin.page-header>

    <section
        class="overflow-hidden rounded-2xl border border-aurea-border bg-aurea-surface shadow-[0_5px_20px_rgba(35,42,37,0.03)]">
        <div class="border-b border-[#eef0ec] px-5 py-5 sm:px-6">
            <div class="flex items-start gap-3">
                <x-admin.product-thumbnail :image="$inventoryRow['image']" :alt="$inventoryStock->productVariant->product->name" size="sm" />
                <div class="min-w-0">
                    <h3 class="font-serif text-xl text-[#28302b]">Historial de inventario de esta variante</h3>
                    <p class="mt-1 text-xs text-[#8a938d]">
                        {{ $inventoryStock->productVariant->product->name }} · {{ $inventoryStock->productVariant->sku }}
                    </p>
                    <p class="mt-1 text-xs text-[#8a938d]">Atributos: {{ $variantLabel }}</p>
                    <p class="mt-1 text-xs text-[#8a938d]">Stock físico: {{ $inventoryStock->current_stock }}</p>
                    <p class="mt-1 text-xs text-[#8a938d]">Reservado: {{ $inventoryStock->reserved_stock }}</p>
                    <p class="mt-1 text-xs text-[#8a938d]">Disponible: {{ $inventoryStock->available_stock }}</p>
                </div>
            </div>
        </div>

        @if ($inventoryStock->movements->isEmpty())
            <x-admin.empty-state title="No hay movimientos todavía"
                description="Las entradas, salidas y ajustes de esta variante aparecerán aquí." />
        @else
            <div class="overflow-x-auto">
                <table class="admin-table w-full min-w-180 text-left text-sm">
                    <thead class="bg-[#fbfbf9] text-[10px] uppercase tracking-wider text-[#9ca49e]">
                        <tr>
                            <th class="px-5 py-3 font-medium">Fecha</th>
                            <th class="px-3 py-3 font-medium">Tipo</th>
                            <th class="px-3 py-3 font-medium">Cantidad</th>
                            <th class="px-3 py-3 font-medium">Stock resultante</th>
                            <th class="px-3 py-3 font-medium">Motivo / Referencia</th>
                            <th class="px-5 py-3 font-medium">Usuario</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-[#f0f1ed]">
                        @foreach ($inventoryStock->movements as $movement)
                            @php
                                $delta = match ($movement->type) {
                                    'Entrada', 'Devolución' => $movement->quantity,
                                    'Salida' => -$movement->quantity,
                                    'Ajuste' => $movement->resulting_stock - $movement->previous_stock,
                                    default => 0,
                                };
                            @endphp
                            <tr class="text-[#5b655e]">
                                <td class="px-5 py-4 text-xs text-[#68736c]">
                                    {{ $movement->movement_at->format('d/m/Y H:i') }}
                                </td>
                                <td class="px-3 py-4">{{ $movement->type }}</td>
                                <td
                                    class="px-3 py-4 font-semibold {{ $delta < 0 ? 'text-aurea-danger' : 'text-[#5b8668]' }}">
                                    {{ sprintf('%+d', $delta) }}
                                </td>
                                <td class="px-3 py-4 font-medium text-[#343d37]">{{ $movement->resulting_stock }}</td>
                                <td class="px-3 py-4">
                                    <p>{{ $movement->reason }}</p>
                                    <p class="mt-1 text-xs text-[#929b94]">{{ $movement->reference }}</p>
                                </td>
                                <td class="px-5 py-4 text-xs text-[#68736c]">{{ $movement->user?->name ?? 'Sistema' }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </section>
@endsection
