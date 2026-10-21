@extends('layouts.admin', ['title' => 'Dashboard'])

@section('content')
    @php
        $dashboard = config('aurea-demo.dashboard', []);
        $monthlySales = $dashboard['monthly_sales'];
        $monthlyValues = $monthlySales['values'];
        $chartWidth = 700;
        $chartHeight = 220;
        $chartTopPadding = 20;
        $chartBottomPadding = 0;
        $monthlyMinimum = min($monthlyValues);
        $monthlyMaximum = max($monthlyValues);
        $monthlyRange = max($monthlyMaximum - $monthlyMinimum, 1);
        $chartPoints = collect($monthlyValues)
            ->values()
            ->map(function ($value, $index) use (
                $chartWidth,
                $chartHeight,
                $chartTopPadding,
                $chartBottomPadding,
                $monthlyMinimum,
                $monthlyRange,
                $monthlyValues,
            ) {
                $lastIndex = count($monthlyValues) - 1;
                $x = $lastIndex > 0 ? ($index * $chartWidth) / $lastIndex : $chartWidth / 2;
                $availableHeight = $chartHeight - $chartTopPadding - $chartBottomPadding;
                $y = $chartTopPadding + (1 - ($value - $monthlyMinimum) / $monthlyRange) * $availableHeight;

                return [
                    'x' => round($x, 2),
                    'y' => round($y, 2),
                ];
            })
            ->all();
        $salesLinePath = collect($chartPoints)
            ->map(function ($point, $index) {
                return ($index === 0 ? 'M' : 'L') . $point['x'] . ' ' . $point['y'];
            })
            ->implode(' ');
        $salesAreaPath = $salesLinePath . " V {$chartHeight} H 0 Z";
        $catalogProducts = collect(config('aurea-demo.catalog_products', []));
        $inventoryStock = collect(config('aurea-demo.inventory_stock', []));
        $orders = collect(config('aurea-demo.orders', []));
        $orderStatusClasses = [
            'Pendiente' => 'text-[#8a7153]',
            'Procesando' => 'text-[#5e7180]',
            'Enviado' => 'text-[#5e7180]',
            'Entregado' => 'text-[#587262]',
            'Cancelado' => 'text-aurea-danger',
        ];
        $metricCards = $dashboard['stats'];
        $recentOrders = collect($dashboard['recent_orders'])
            ->map(function ($orderNumber) use ($orders, $orderStatusClasses) {
                $order = $orders->firstWhere('order_number', $orderNumber);

                if (!$order) {
                    return null;
                }

                return [
                    'number' => $order['order_number'],
                    'customer' => $order['customer_name'],
                    'date' => $order['date'],
                    'total' => '$' . number_format($order['total'], 2) . ' MXN',
                    'status' => $order['order_status'],
                    'statusClass' => $orderStatusClasses[$order['order_status']],
                ];
            })
            ->filter()
            ->values();
        $lowStockProducts = collect($dashboard['low_stock'])
            ->map(function ($sku) use ($inventoryStock, $catalogProducts) {
                $inventoryItem = $inventoryStock->firstWhere('sku', $sku);

                if (!$inventoryItem) {
                    return null;
                }

                $catalogProduct = $catalogProducts->first(function ($product) use ($sku) {
                    return $sku === $product['base_sku'] || str_starts_with($sku, $product['base_sku'] . '-');
                });

                return [
                    'name' => $inventoryItem['product'],
                    'sku' => $inventoryItem['sku'],
                    'stock' => $inventoryItem['current_stock'] . ' unidades',
                    'image' => $catalogProduct['image'] ?? '',
                ];
            })
            ->filter()
            ->values();
        $bestSellingProducts = collect($dashboard['best_sellers'])
            ->map(function ($bestSeller) use ($catalogProducts) {
                $product = $catalogProducts->firstWhere('base_sku', $bestSeller['base_sku']);

                if (!$product) {
                    return null;
                }

                return [
                    'rank' => $bestSeller['rank'],
                    'name' => $product['name'],
                    'collection' => $product['category'],
                    'sold' => $bestSeller['sold'],
                    'price' => $product['price'],
                    'image' => $product['image'],
                ];
            })
            ->filter()
            ->values();
        $visitAnalytics = $dashboard['visit_analytics'];
        $visitValues = $visitAnalytics['last_30_days']['values'];
        $visitChartWidth = 700;
        $visitChartHeight = 220;
        $visitChartTopPadding = 20;
        $visitMinimum = min($visitValues);
        $visitRange = max(max($visitValues) - $visitMinimum, 1);
        $visitChartPoints = collect($visitValues)
            ->values()
            ->map(function ($value, $index) use ($visitChartWidth, $visitChartHeight, $visitChartTopPadding, $visitMinimum, $visitRange, $visitValues) {
                $lastIndex = count($visitValues) - 1;
                $x = $lastIndex > 0 ? ($index * $visitChartWidth) / $lastIndex : $visitChartWidth / 2;
                $availableHeight = $visitChartHeight - $visitChartTopPadding;
                $y = $visitChartTopPadding + (1 - ($value - $visitMinimum) / $visitRange) * $availableHeight;

                return ['x' => round($x, 2), 'y' => round($y, 2)];
            })
            ->all();
        $visitLinePath = collect($visitChartPoints)
            ->map(fn ($point, $index) => ($index === 0 ? 'M' : 'L') . $point['x'] . ' ' . $point['y'])
            ->implode(' ');
        $visitAreaPath = $visitLinePath . " V {$visitChartHeight} H 0 Z";
        $mostVisitedProducts = collect($visitAnalytics['most_visited_products'])
            ->map(function ($mostVisitedProduct) use ($catalogProducts) {
                $product = $catalogProducts->firstWhere('base_sku', $mostVisitedProduct['base_sku']);

                if (!$product) {
                    return null;
                }

                return [
                    'name' => $product['name'],
                    'category' => $product['category'],
                    'image' => $product['image'],
                    'visits' => $mostVisitedProduct['visits'],
                ];
            })
            ->filter()
            ->values();
    @endphp

    <div class="mb-8 flex flex-col justify-between gap-4 sm:flex-row sm:items-end">
        <div>
            <div class="mb-2 flex flex-wrap items-center gap-2">
                <p class="text-sm text-[#8c958f]">Viernes, 02 de octubre de 2026</p>
            </div>
            <h2 class="font-serif text-3xl text-aurea-ink">Buenos días, Admin Prueba</h2>
            <p class="mt-2 text-sm text-aurea-muted">Aquí tienes lo más importante de tu tienda hoy.</p>
        </div>
        <button type="button"
            class="aurea-focus inline-flex w-fit items-center gap-2 rounded-lg bg-aurea-gold px-4 py-2.5 text-sm font-medium text-white shadow-sm transition hover:bg-[#9f793f]">
            <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7">
                <path d="M12 5v14M5 12h14" />
            </svg>
            Añadir producto
        </button>
    </div>

    <section class="mb-7 grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        @foreach ($metricCards as $metricCard)
            <x-admin.stat-card :title="$metricCard['title']" :value="$metricCard['value']" :change="$metricCard['change']" :description="$metricCard['description']" :icon-background="$metricCard['iconBackground']"
                :icon-color="$metricCard['iconColor']" :icon-path="$metricCard['iconPath']" :change-style="$metricCard['changeStyle']" />
        @endforeach
    </section>

    <section class="mt-7" aria-labelledby="visit-analytics-title">
        <div class="mb-4 flex items-end justify-between gap-4">
            <div>
                <h2 id="visit-analytics-title" class="font-serif text-2xl text-[#28302b]">Analítica de visitas</h2>
                <p class="mt-1 text-xs text-[#8a938d]">Una vista general del interés en tu tienda</p>
            </div>
            <span class="hidden text-[10px] uppercase tracking-[.16em] text-[#a0a7a2] sm:block">Datos demo</span>
        </div>

        <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
            @foreach ($visitAnalytics['stats'] as $metricCard)
                <x-admin.stat-card :title="$metricCard['title']" :value="$metricCard['value']" :change="$metricCard['change']" :description="$metricCard['description']" :icon-background="$metricCard['iconBackground']"
                    :icon-color="$metricCard['iconColor']" :icon-path="$metricCard['iconPath']" :change-style="$metricCard['changeStyle']" />
            @endforeach
        </div>

        <div class="mt-4 grid gap-4 xl:grid-cols-[1.65fr_1fr]">
            <section class="rounded-2xl border border-aurea-border bg-aurea-surface p-5 shadow-[0_5px_20px_rgba(35,42,37,0.03)] sm:p-6">
                <div class="mb-6">
                    <h3 class="font-serif text-xl text-[#28302b]">Visitas últimos 30 días</h3>
                    <p class="mt-1 text-xs text-[#8a938d]">Tendencia de tráfico en tu tienda</p>
                </div>
                <div class="relative h-56" role="img" aria-label="{{ $visitAnalytics['last_30_days']['description'] }}">
                    <div class="absolute inset-0 flex flex-col justify-between text-[10px] text-[#b0b6b1]">
                        <span>{{ number_format(max($visitValues)) }}</span>
                        <span>{{ number_format(round(max($visitValues) * .66)) }}</span>
                        <span>{{ number_format(round(max($visitValues) * .33)) }}</span>
                        <span>0</span>
                    </div>
                    <div class="absolute inset-y-0 left-12 right-0 flex flex-col justify-between">
                        <span class="border-t border-dashed border-[#eceee9]"></span>
                        <span class="border-t border-dashed border-[#eceee9]"></span>
                        <span class="border-t border-dashed border-[#eceee9]"></span>
                        <span class="border-t border-[#eceee9]"></span>
                    </div>
                    <svg class="absolute bottom-5 left-12 right-0 h-[calc(100%-20px)] w-[calc(100%-48px)] overflow-visible" viewBox="0 0 700 220" preserveAspectRatio="none" aria-hidden="true">
                        <defs>
                            <linearGradient id="visits-fill" x1="0" x2="0" y1="0" y2="1">
                                <stop offset="0" stop-color="#e8ddcf" stop-opacity=".42" />
                                <stop offset="1" stop-color="#e8ddcf" stop-opacity="0" />
                            </linearGradient>
                        </defs>
                        <path d="{{ $visitAreaPath }}" fill="url(#visits-fill)" />
                        <path d="{{ $visitLinePath }}" fill="none" stroke="#9a7445" stroke-linecap="round" stroke-linejoin="round" stroke-width="3" vector-effect="non-scaling-stroke" />
                    </svg>
                    <div class="absolute bottom-0 left-12 right-0 flex justify-between text-[10px] text-[#a2aaa4]">
                        @foreach ($visitAnalytics['last_30_days']['labels'] as $label)
                            <span>{{ $label }}</span>
                        @endforeach
                    </div>
                </div>
            </section>

            <section class="rounded-2xl border border-aurea-border bg-aurea-surface p-5 shadow-[0_5px_20px_rgba(35,42,37,0.03)]">
                <div class="mb-4">
                    <h3 class="font-serif text-xl text-[#28302b]">Productos más visitados</h3>
                    <p class="mt-1 text-xs text-[#8a938d]">Interés durante los últimos 30 días</p>
                </div>
                @foreach ($mostVisitedProducts as $mostVisitedProduct)
                    <div class="flex items-center gap-3 border-t border-[#f0f1ed] py-3">
                        <x-admin.product-thumbnail :image="$mostVisitedProduct['image'] ?? null" :alt="$mostVisitedProduct['name']" size="sm" />
                        <div class="min-w-0 flex-1">
                            <p class="text-sm font-medium text-[#3d4640]">{{ $mostVisitedProduct['name'] }}</p>
                            <p class="mt-0.5 text-[11px] text-[#929b94]">{{ $mostVisitedProduct['category'] }}</p>
                        </div>
                        <span class="text-right text-xs font-medium text-[#414a44]">{{ $mostVisitedProduct['visits'] }}</span>
                    </div>
                @endforeach
            </section>
        </div>
    </section>

    {{-- <div class="grid gap-7 xl:grid-cols-[1.65fr_1fr]">
        <section
            class="rounded-2xl border border-aurea-border bg-aurea-surface p-5 shadow-[0_5px_20px_rgba(35,42,37,0.03)] sm:p-6">
            <div class="mb-6 flex items-start justify-between">
                <div>
                    <h3 class="font-serif text-xl text-[#28302b]">Ventas</h3>
                    <p class="mt-1 text-xs text-[#8a938d]">Rendimiento de los últimos 7 meses</p>
                </div><button type="button"
                    class="aurea-focus flex items-center gap-2 rounded-lg border border-[#e7e8e2] px-3 py-2 text-xs text-[#68736c] hover:border-aurea-gold-soft hover:text-aurea-focus">Últimos
                    7 meses <svg class="h-3 w-3" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="m7 10 5 5 5-5" />
                    </svg></button>
            </div>
            <div class="relative h-64" role="img"
                aria-label="Gráfica de ventas netas de {{ $monthlySales['labels'][0] }} a {{ $monthlySales['labels'][count($monthlySales['labels']) - 1] }} de 2026."
                aria-describedby="sales-chart-description">
                <div class="absolute inset-0 flex flex-col justify-between text-[10px] text-[#b0b6b1]">
                    <span>$60k</span><span>$45k</span><span>$30k</span><span>$15k</span><span>$0</span>
                </div>
                <div class="absolute inset-y-0 left-10 right-0 flex flex-col justify-between"><span
                        class="border-t border-dashed border-[#eceee9]"></span><span
                        class="border-t border-dashed border-[#eceee9]"></span><span
                        class="border-t border-dashed border-[#eceee9]"></span><span
                        class="border-t border-dashed border-[#eceee9]"></span><span
                        class="border-t border-[#eceee9]"></span></div><svg
                    class="absolute bottom-5 left-10 right-0 h-[calc(100%-20px)] w-[calc(100%-40px)] overflow-visible"
                    viewBox="0 0 700 220" preserveAspectRatio="none" aria-hidden="true">
                    <defs>
                        <linearGradient id="sales-fill" x1="0" x2="0" y1="0" y2="1">
                            <stop offset="0" stop-color="#e8ddcf" stop-opacity=".42" />
                            <stop offset="1" stop-color="#e8ddcf" stop-opacity="0" />
                        </linearGradient>
                    </defs>
                    <path d="{{ $salesAreaPath }}" fill="url(#sales-fill)" />
                    <path d="{{ $salesLinePath }}" fill="none" stroke="#9a7445" stroke-linecap="round"
                        stroke-linejoin="round" stroke-width="3" vector-effect="non-scaling-stroke" />
                </svg>
                <div class="absolute bottom-0 left-10 right-0 flex justify-between text-[10px] text-[#a2aaa4]">
                    @foreach ($monthlySales['labels'] as $month)
                        <span>{{ $month }}</span>
                    @endforeach
                </div>
                <p id="sales-chart-description" class="sr-only">{{ $monthlySales['description'] }}</p>
            </div>
            <div class="mt-4 text-xs text-[#768079]">Ventas netas <strong
                    class="ml-1 text-[#333a35]">{{ $dashboard['stats'][0]['value'] }}</strong><span
                    class="ml-2 text-[#62906e]">↑ 12.8%</span></div>
        </section>

        <section
            class="rounded-2xl border border-[#e7e2da] bg-aurea-sidebar-deep p-6 text-[#222222] shadow-[0_5px_20px_rgba(92,72,45,0.06)]">
            <div class="flex items-start justify-between">
                <div>
                    <p class="text-[11px] uppercase tracking-[.18em] text-[#78716c]">Resumen anual</p>
                    <h3 class="mt-2 font-serif text-2xl">Crecimiento constante</h3>
                </div><span
                    class="text-[11px] font-medium tracking-[.18em] text-[#9a7445]">{{ $dashboard['annual_sales']['year'] }}</span>
            </div>
            <div class="my-8 flex items-end gap-3"><span
                    class="font-serif text-4xl">{{ $dashboard['annual_sales']['total'] }}</span><span
                    class="mb-1 text-xs text-[#6d8a72]">↑ {{ $dashboard['annual_sales']['change'] }}</span></div>
            <div class="flex h-24 items-end gap-2">
                @foreach ($dashboard['annual_sales']['heights'] as $height)
                    <div class="flex-1 rounded-t-sm bg-[#e8ddcf]"
                        style="height: {{ $height }}%; opacity: {{ $loop->last ? '0.9' : '0.35' }}"></div>
                @endforeach
            </div>
            <div class="mt-3 flex justify-between text-[10px] text-[#78716c]">
                <span>Ene</span><span>Jun</span><span>Dic</span>
            </div>
        </section>
    </div> --}}

    <div class="mt-7 grid gap-7 xl:grid-cols-[1.45fr_1fr]">
        <section
            class="overflow-hidden rounded-2xl border border-aurea-border bg-aurea-surface shadow-[0_5px_20px_rgba(35,42,37,0.03)]">
            <div class="flex items-center justify-between border-b border-[#eef0ec] px-5 py-5 sm:px-6">
                <div>
                    <h3 class="font-serif text-xl text-[#28302b]">Pedidos recientes</h3>
                    <p class="mt-1 text-xs text-[#8a938d]">Últimas transacciones de tu tienda</p>
                </div><button type="button"
                    class="aurea-focus rounded-md px-1 text-xs font-medium text-[#aa8046] hover:text-[#80602f]">Ver
                    todos</button>
            </div>

            <div class="hidden overflow-x-auto sm:block">

                <table class="admin-table w-full min-w-140 text-left text-sm">
                    <caption class="sr-only">Pedidos recientes del dashboard</caption>
                    <thead class="bg-[#fbfbf9] text-[10px] uppercase tracking-wider text-[#9ca49e]">
                        <tr>
                            <th scope="col" class="px-5 py-3 font-medium sm:px-6">Pedido</th>
                            <th scope="col" class="px-3 py-3 font-medium">Cliente</th>
                            <th scope="col" class="px-3 py-3 font-medium">Fecha</th>
                            <th scope="col" class="px-3 py-3 font-medium">Total</th>
                            <th scope="col" class="px-3 py-3 font-medium">Estado</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-[#f0f1ed]">
                        @foreach ($recentOrders as $recentOrder)
                            <tr class="text-[#5b655e]">
                                <td class="px-5 py-4 font-medium text-[#343d37] sm:px-6">{{ $recentOrder['number'] }}
                                </td>

                                <td class="px-3 py-4">
                                    {{ $recentOrder['customer'] }}
                                </td>

                                <td class="px-3 py-4 text-xs text-[#929b94]">
                                    {{ $recentOrder['date'] }}
                                </td>

                                <td class="px-3 py-4 font-medium text-[#343d37]">
                                    {{ $recentOrder['total'] }}
                                </td>

                                <td class="px-3 py-4">
                                    <x-admin.status-badge :label="$recentOrder['status']" :status-class="$recentOrder['statusClass']" />
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>

            </div>

            <div class="divide-y divide-[#f0f1ed] sm:hidden">
                @foreach ($recentOrders as $recentOrder)
                    <article class="px-5 py-4">
                        <div class="flex items-start justify-between gap-3">
                            <div>
                                <p class="text-sm font-semibold text-[#343d37]">{{ $recentOrder['number'] }}</p>
                                <p class="mt-1 text-xs text-[#6f7972]">{{ $recentOrder['customer'] }}</p>
                            </div>
                            <x-admin.status-badge :label="$recentOrder['status']" :status-class="$recentOrder['statusClass']" />
                        </div>
                        <div class="mt-3 flex justify-between text-[11px] text-[#929b94]">
                            <span>{{ $recentOrder['date'] }}</span><strong
                                class="text-sm font-medium text-[#343d37]">{{ $recentOrder['total'] }}</strong>
                        </div>
                    </article>
                @endforeach
            </div>
        </section>

        <div class="space-y-7">
            <section
                class="rounded-2xl border border-aurea-border bg-aurea-surface p-5 shadow-[0_5px_20px_rgba(35,42,37,0.03)]">
                <div class="mb-4 flex items-center justify-between">
                    <div>
                        <h3 class="font-serif text-xl text-[#28302b]">Stock bajo</h3>
                        <p class="mt-1 text-xs text-[#8a938d]">Productos que necesitan atención</p>
                    </div><button type="button"
                        class="aurea-focus rounded-md px-1 text-xs font-medium text-[#aa8046] hover:text-[#80602f]">Ver
                        inventario</button>
                </div>
                @foreach ($lowStockProducts as $lowStockProduct)
                    <div class="flex items-center gap-3 border-t border-[#f0f1ed] py-3">
                        <x-admin.product-thumbnail
                            :image="$lowStockProduct['image'] ?? null"
                            :alt="$lowStockProduct['name']"
                            size="sm"
                        />
                        <div class="min-w-0 flex-1">
                            <p class="text-sm font-medium text-[#3d4640]">{{ $lowStockProduct['name'] }}</p>
                            <p class="mt-0.5 text-[11px] text-[#929b94]">{{ $lowStockProduct['sku'] }}</p>
                        </div><span
                            class="text-right text-[11px] font-medium text-[#a06d5d]">{{ $lowStockProduct['stock'] }}<br><span
                                class="text-[10px] font-normal uppercase tracking-wider">Stock bajo</span></span>
                    </div>
                @endforeach
            </section>
            <section
                class="rounded-2xl border border-aurea-border bg-aurea-surface p-5 shadow-[0_5px_20px_rgba(35,42,37,0.03)]">
                <div class="mb-4 flex items-center justify-between">
                    <div>
                        <h3 class="font-serif text-xl text-[#28302b]">Más vendidos</h3>
                        <p class="mt-1 text-xs text-[#8a938d]">Esta semana</p>
                    </div><button type="button"
                        class="aurea-focus rounded-md px-1 text-xs font-medium text-[#aa8046] hover:text-[#80602f]">Ver
                        catálogo</button>
                </div>
                @foreach ($bestSellingProducts as $bestSellingProduct)
                    <div class="flex items-center gap-3 border-t border-[#f0f1ed] py-3"><span
                            class="w-5 text-xs font-semibold text-[#bca071]">{{ $bestSellingProduct['rank'] }}</span>
                        <x-admin.product-thumbnail
                            :image="$bestSellingProduct['image'] ?? null"
                            :alt="$bestSellingProduct['name']"
                            size="sm"
                        />
                        <div class="min-w-0 flex-1">
                            <p class="text-sm font-medium text-[#3d4640]">{{ $bestSellingProduct['name'] }}</p>
                            <p class="mt-0.5 text-[11px] text-[#929b94]">{{ $bestSellingProduct['collection'] }} ·
                                {{ $bestSellingProduct['sold'] }}</p>
                        </div><span class="text-sm font-medium text-[#414a44]">{{ $bestSellingProduct['price'] }}</span>
                    </div>
                @endforeach
            </section>
        </div>
    </div>
@endsection
