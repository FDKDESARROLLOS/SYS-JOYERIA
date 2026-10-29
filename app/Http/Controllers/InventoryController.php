<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\InventoryMovement;
use App\Models\InventoryStock;
use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class InventoryController extends Controller
{
    public function index(Request $request): View
    {
        $search = trim((string) $request->query('search', ''));
        $categoryId = $request->integer('category_id');
        $stockStatus = $request->query('stock_status');
        $catalogStatus = $request->query('catalog_status');

        $inventoryStocks = $this->applyStockFilters(
            InventoryStock::query()->with($this->stockRelations()),
            $search,
            $categoryId,
        )
            ->whereHas('productVariant.product')
            ->when(in_array($catalogStatus, ['active', 'draft', 'inactive'], true), function ($query) use ($catalogStatus): void {
                $query->where(function ($query) use ($catalogStatus): void {
                    $query
                        ->whereHas('productVariant', fn ($query) => $query->where('status', $catalogStatus))
                        ->orWhereHas('productVariant.product', fn ($query) => $query->where('status', $catalogStatus));
                });
            })
            ->when($stockStatus === 'available', fn ($query) => $query->whereColumn('current_stock', '>', 'reserved_stock'))
            ->when($stockStatus === 'out', fn ($query) => $query->whereColumn('current_stock', '<=', 'reserved_stock'))
            ->orderBy('id')
            ->paginate(10)
            ->withQueryString();

        $archivedInventoryStocks = $this->applyStockFilters(
            InventoryStock::query()->with($this->stockRelations(true)),
            $search,
            $categoryId,
            true,
        )
            ->where('current_stock', '>', 0)
            ->where(function ($query): void {
                $query->whereHas('productVariant', fn ($query) => $query->onlyTrashed())
                    ->orWhereHas('productVariant.product', fn ($query) => $query->onlyTrashed());
            })
            ->orderBy('id')
            ->paginate(10, ['*'], 'archived_page')
            ->withQueryString();

        return view('inventory.stock', [
            'inventoryStocks' => $inventoryStocks,
            'categories' => Category::query()
                ->where('status', true)
                ->whereNull('parent_id')
                ->orderBy('name')
                ->get(['id', 'name', 'parent_id']),
            'selectedCategoryId' => $categoryId > 0 ? $categoryId : null,
            'selectedStockStatus' => $stockStatus,
            'selectedCatalogStatus' => $catalogStatus,
            'inventoryRows' => $inventoryStocks->getCollection()->map(
                fn (InventoryStock $inventoryStock): array => $this->stockRow($inventoryStock)
            ),
            'archivedInventoryStocks' => $archivedInventoryStocks,
            'archivedInventoryRows' => $archivedInventoryStocks->getCollection()->map(
                fn (InventoryStock $inventoryStock): array => $this->stockRow($inventoryStock)
            ),
        ]);
    }

    public function movements(Request $request): View
    {
        $search = trim((string) $request->query('search', ''));
        $type = $request->query('type');
        $date = $request->query('date');

        $inventoryMovements = InventoryMovement::query()
            ->with($this->movementRelations())
            ->when($search !== '', function ($query) use ($search): void {
                $query->where(function ($query) use ($search): void {
                    $query->where('reference', 'like', "%{$search}%")
                        ->orWhere('reason', 'like', "%{$search}%")
                        ->orWhereHas('inventoryStock.productVariant', function ($query) use ($search): void {
                            $query->withTrashed()
                                ->where('sku', 'like', "%{$search}%")
                                ->orWhereHas('product', function ($query) use ($search): void {
                                    $query->withTrashed()
                                        ->where('name', 'like', "%{$search}%")
                                        ->orWhere('code', 'like', "%{$search}%");
                                });
                        });
                });
            })
            ->when(in_array($type, ['Entrada', 'Salida', 'Ajuste', 'Devolución'], true), fn ($query) => $query->where('type', $type))
            ->when($date !== null && $date !== '', fn ($query) => $query->whereDate('movement_at', $date))
            ->latest('movement_at')
            ->latest('id')
            ->paginate(10)
            ->withQueryString();

        return view('inventory.movements', [
            'inventoryMovements' => $inventoryMovements,
            'movementRows' => $inventoryMovements->getCollection()->map(
                fn (InventoryMovement $movement): array => $this->movementRow($movement)
            ),
        ]);
    }

    public function create(Request $request): View
    {
        $contextInventoryStock = null;
        $contextInventoryStockId = $request->integer('variant');

        if ($contextInventoryStockId > 0) {
            $contextInventoryStock = InventoryStock::query()
                ->with($this->stockRelations())
                ->findOrFail($contextInventoryStockId);
            $this->ensureStockIsVisible($contextInventoryStock);
        }

        $inventoryStocks = InventoryStock::query()
            ->with($this->stockRelations())
            ->whereHas('productVariant.product')
            ->orderBy('id')
            ->get();

        return view('inventory.movements.create', [
            'inventoryItems' => $inventoryStocks->map(function (InventoryStock $inventoryStock): array {
                return [
                    ...$this->stockRow($inventoryStock),
                    'id' => $inventoryStock->id,
                ];
            }),
            'contextInventoryStock' => $contextInventoryStock,
            'selectedInventoryStockId' => $contextInventoryStock?->id
                ?? old('inventory_stock_id'),
        ]);
    }

    public function storeFromCreate(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'inventory_stock_id' => ['required', 'integer', 'exists:inventory_stocks,id'],
            'adjustment_type' => ['nullable', Rule::in(['increase', 'decrease'])],
        ]);
        $inventoryStock = InventoryStock::query()->findOrFail($validated['inventory_stock_id']);

        return $this->storeMovement($request, $inventoryStock, true);
    }

    public function storeContextualMovement(Request $request, InventoryStock $inventoryStock): RedirectResponse
    {
        $this->ensureStockIsVisible($inventoryStock);

        $validated = $request->validate([
            'inventory_stock_id' => ['required', 'integer'],
        ]);

        if ((int) $validated['inventory_stock_id'] !== $inventoryStock->id) {
            throw ValidationException::withMessages([
                'inventory_stock_id' => 'La variante del movimiento no coincide con el contexto actual.',
            ]);
        }

        return $this->storeMovement($request, $inventoryStock, true);
    }

    public function show(InventoryStock $inventoryStock): View
    {
        $inventoryStock->load([
            ...$this->stockRelations(true),
            'movements' => fn ($query) => $query
                ->with('user:id,name')
                ->latest('movement_at')
                ->latest('id'),
        ]);
        $this->ensureStockIsVisible($inventoryStock, true);

        return view('inventory.show', [
            'inventoryStock' => $inventoryStock,
            'variantLabel' => $this->variantLabel($inventoryStock->productVariant),
            'inventoryRow' => $this->stockRow($inventoryStock),
        ]);
    }

    public function storeMovement(
        Request $request,
        InventoryStock $inventoryStock,
        bool $calculateAdjustmentFromQuantity = false,
    ): RedirectResponse {
        $this->ensureStockIsVisible($inventoryStock);

        $validated = $request->validate([
            'type' => ['required', Rule::in(['Entrada', 'Salida', 'Ajuste', 'Devolución'])],
            'quantity' => ['nullable', 'integer', 'min:1'],
            'adjustment_type' => ['nullable', Rule::in(['increase', 'decrease'])],
            'adjustment_stock' => ['nullable', 'integer', 'min:0'],
            'reason' => ['required', 'string', 'max:255'],
            'reference' => ['nullable', 'string', 'max:255'],
            'movement_at' => ['required', 'date'],
        ], [
            'type.in' => 'El tipo de movimiento seleccionado no es válido.',
            'quantity.min' => 'La cantidad debe ser mayor que cero.',
            'adjustment_stock.min' => 'La existencia ajustada no puede ser negativa.',
        ]);

        if ($validated['type'] === 'Ajuste') {
            if ($calculateAdjustmentFromQuantity && ! filled($validated['adjustment_type'] ?? null)) {
                throw ValidationException::withMessages([
                    'adjustment_type' => 'Indica cómo debe aplicarse el ajuste.',
                ]);
            }

            if ($calculateAdjustmentFromQuantity && ! filled($validated['quantity'] ?? null)) {
                throw ValidationException::withMessages([
                    'quantity' => 'Indica una cantidad para el ajuste.',
                ]);
            }

            if (! $calculateAdjustmentFromQuantity && ! filled($validated['adjustment_stock'] ?? null)) {
                throw ValidationException::withMessages([
                    'adjustment_stock' => 'Indica la nueva existencia real para el ajuste.',
                ]);
            }
        }

        if ($validated['type'] !== 'Ajuste' && ! filled($validated['quantity'] ?? null)) {
            throw ValidationException::withMessages([
                'quantity' => 'Indica una cantidad para el movimiento.',
            ]);
        }

        DB::transaction(function () use ($validated, $inventoryStock, $calculateAdjustmentFromQuantity): void {
            $stock = InventoryStock::query()
                ->lockForUpdate()
                ->findOrFail($inventoryStock->getKey());
            $this->ensureStockIsCatalogEntry($stock);
            $previousStock = (int) $stock->current_stock;

            if ($validated['type'] === 'Ajuste') {
                if ($calculateAdjustmentFromQuantity) {
                    $quantity = (int) $validated['quantity'];
                    $resultingStock = $validated['adjustment_type'] === 'decrease'
                        ? $previousStock - $quantity
                        : $previousStock + $quantity;
                } else {
                    $resultingStock = (int) $validated['adjustment_stock'];
                    $quantity = abs($resultingStock - $previousStock);
                }

                if ($resultingStock === $previousStock) {
                    throw ValidationException::withMessages([
                        'adjustment_stock' => 'La existencia indicada es igual al stock actual.',
                    ]);
                }
            } else {
                $quantity = (int) $validated['quantity'];
                $resultingStock = in_array($validated['type'], ['Entrada', 'Devolución'], true)
                    ? $previousStock + $quantity
                    : $previousStock - $quantity;
            }

            if ($resultingStock < (int) $stock->reserved_stock) {
                throw ValidationException::withMessages([
                    'quantity' => 'La operación no puede reducir el stock físico por debajo del stock reservado.',
                ]);
            }

            if ($resultingStock < 0) {
                throw ValidationException::withMessages([
                    'quantity' => 'La salida no puede dejar el stock por debajo de cero.',
                ]);
            }

            $stock->update(['current_stock' => $resultingStock]);
            $stock->movements()->create([
                'user_id' => auth()->id(),
                'type' => $validated['type'],
                'quantity' => $quantity,
                'previous_stock' => $previousStock,
                'resulting_stock' => $resultingStock,
                'reason' => $validated['reason'],
                'reference' => $validated['reference'] ?? null,
                'movement_at' => $validated['movement_at'],
            ]);
        });

        return redirect()
            ->route('inventory.movements.show', $inventoryStock)
            ->with('status', 'Movimiento registrado correctamente.');
    }

    public function updateMinimumStock(Request $request, InventoryStock $inventoryStock): RedirectResponse
    {
        $this->ensureStockIsVisible($inventoryStock);

        $validated = $request->validate([
            'minimum_stock' => ['required', 'integer', 'min:0'],
        ]);

        DB::transaction(function () use ($inventoryStock, $validated): void {
            $inventoryStock->update($validated);
        });

        return redirect()
            ->route('inventory.movements.show', $inventoryStock)
            ->with('status', 'Stock mínimo actualizado correctamente.');
    }

    private function applyStockFilters(
        Builder $query,
        string $search,
        int $categoryId,
        bool $includeTrashed = false,
    ): Builder {
        return $query->whereHas('productVariant', function ($query) use ($search, $categoryId, $includeTrashed): void {
            if ($includeTrashed) {
                $query->withTrashed();
            }

            $query
                ->when($search !== '', function ($query) use ($search, $includeTrashed): void {
                    $query->where(function ($query) use ($search, $includeTrashed): void {
                        $query->where('sku', 'like', "%{$search}%")
                            ->orWhereHas('product', function ($query) use ($search, $includeTrashed): void {
                                if ($includeTrashed) {
                                    $query->withTrashed();
                                }

                                $query->where('name', 'like', "%{$search}%")
                                    ->orWhere('code', 'like', "%{$search}%");
                            });
                    });
                })
                ->when($categoryId > 0, function ($query) use ($categoryId, $includeTrashed): void {
                    $query->whereHas('product', function ($query) use ($categoryId, $includeTrashed): void {
                        if ($includeTrashed) {
                            $query->withTrashed();
                        }

                        $query->where(function ($query) use ($categoryId): void {
                            $query->where('category_id', $categoryId)
                                ->orWhereHas('category', fn ($query) => $query->where('parent_id', $categoryId));
                        });
                    });
                });
        });
    }

    /**
     * @return array<int, string|\Closure>
     */
    private function stockRelations(bool $includeTrashed = false): array
    {
        $relations = [
            'productVariant.size:id,name',
            'productVariant.material:id,name',
            'productVariant.stone:id,name',
            'productVariant.product.category:id,name,parent_id',
            'productVariant.product.category.parent:id,name',
            'productVariant.product.baseSize:id,name',
            'productVariant.product.material:id,name',
            'productVariant.product.images' => fn ($query) => $query
                ->orderByDesc('is_cover')
                ->orderBy('sort_order')
                ->orderBy('id'),
        ];

        if ($includeTrashed) {
            $relations['productVariant'] = fn ($query) => $query->withTrashed();
            $relations['productVariant.product'] = fn ($query) => $query->withTrashed();
        }

        return $relations;
    }

    /**
     * @return array<int, string|\Closure>
     */
    private function movementRelations(): array
    {
        return [
            'user:id,name',
            'inventoryStock.productVariant.size:id,name',
            'inventoryStock.productVariant.material:id,name',
            'inventoryStock.productVariant.stone:id,name',
            'inventoryStock.productVariant.product.baseSize:id,name',
            'inventoryStock.productVariant.product.material:id,name',
            'inventoryStock.productVariant.product.category:id,name,parent_id',
            'inventoryStock.productVariant.product.images' => fn ($query) => $query
                ->orderByDesc('is_cover')
                ->orderBy('sort_order')
                ->orderBy('id'),
            'inventoryStock.productVariant' => fn ($query) => $query->withTrashed(),
            'inventoryStock.productVariant.product' => fn ($query) => $query->withTrashed(),
        ];
    }

    private function ensureStockIsVisible(InventoryStock $inventoryStock, bool $includeTrashed = false): void
    {
        $inventoryStock->loadMissing($this->stockRelations($includeTrashed));

        abort_unless($inventoryStock->productVariant?->product !== null, 404);

        if (! $includeTrashed) {
            $this->ensureStockIsCatalogEntry($inventoryStock);
        }
    }

    private function ensureStockIsCatalogEntry(InventoryStock $inventoryStock): void
    {
        $inventoryStock->load([
            'productVariant' => fn ($query) => $query->withTrashed(),
            'productVariant.product' => fn ($query) => $query->withTrashed(),
        ]);

        $variant = $inventoryStock->productVariant;
        $product = $variant?->product;

        abort_unless(
            $variant !== null
            && $product !== null
            && ! $variant->trashed()
            && ! $product->trashed(),
            404,
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function stockRow(InventoryStock $inventoryStock): array
    {
        $variant = $inventoryStock->productVariant;
        $product = $variant?->product;
        $currentStock = (int) $inventoryStock->current_stock;
        $reservedStock = (int) $inventoryStock->reserved_stock;
        $availableStock = $inventoryStock->available_stock;
        $isOutOfCatalog = (bool) ($variant?->trashed() || $product?->trashed());
        [$catalogStatus, $catalogStatusClass] = $this->effectiveCatalogStatus($product, $variant);
        [$stockStatus, $stockStatusClass] = match (true) {
            $availableStock <= 0 => ['Sin stock', 'text-aurea-danger'],
            $isOutOfCatalog => ['Con existencias', 'text-[#5b8668]'],
            default => ['Disponible', 'text-[#5b8668]'],
        };

        return [
            'product' => $product?->name ?? 'Producto eliminado',
            'variant' => $this->variantLabel($variant),
            'sku' => $variant?->sku ?? '—',
            'category' => $product?->category?->parent?->name
                ?? $product?->category?->name
                ?? '—',
            'current_stock' => $currentStock,
            'reserved_stock' => $reservedStock,
            'available_stock' => $availableStock,
            'stock_status' => $stockStatus,
            'stock_status_class' => $stockStatusClass,
            'catalog_status' => $catalogStatus,
            'catalog_status_class' => $catalogStatusClass,
            'is_out_of_catalog' => $isOutOfCatalog,
            'image' => $this->imagePath($product),
            'inventory_stock_id' => $inventoryStock->id,
        ];
    }

    /**
     * Borrador has precedence over Inactivo when the product and variant differ.
     *
     * @return array{0: string, 1: string}
     */
    private function effectiveCatalogStatus(?Product $product, ?ProductVariant $variant): array
    {
        $statuses = [$product?->status, $variant?->status];

        return match (true) {
            in_array('draft', $statuses, true) => ['Borrador', 'text-[#806441]'],
            in_array('inactive', $statuses, true) => ['Inactivo', 'text-[#a27a3f]'],
            default => ['Activo', 'text-[#5b8668]'],
        };
    }

    /**
     * @return array<string, mixed>
     */
    private function movementRow(InventoryMovement $movement): array
    {
        $variant = $movement->inventoryStock?->productVariant;
        $product = $variant?->product;
        $delta = match ($movement->type) {
            'Entrada', 'Devolución' => $movement->quantity,
            'Salida' => -$movement->quantity,
            'Ajuste' => $movement->resulting_stock - $movement->previous_stock,
            default => 0,
        };

        return [
            'product' => $product?->name ?? 'Producto eliminado',
            'variant' => $this->variantLabel($variant),
            'sku' => $variant?->sku ?? '—',
            'type' => $movement->type,
            'quantity_label' => sprintf('%+d', $delta),
            'quantity_class' => $delta < 0 ? 'text-aurea-danger' : 'text-[#5b8668]',
            'type_class' => [
                'Entrada' => 'text-[#5b8668]',
                'Salida' => 'text-aurea-danger',
                'Ajuste' => 'text-[#947142]',
                'Devolución' => 'text-[#61758b]',
            ][$movement->type] ?? 'text-[#68736c]',
            'previous_stock' => $movement->previous_stock,
            'resulting_stock' => $movement->resulting_stock,
            'reason' => $movement->reason,
            'reference' => $movement->reference,
            'date' => $movement->movement_at?->format('d/m/Y H:i'),
            'user' => $movement->user?->name ?? 'Sistema',
            'image' => $this->imagePath($product),
            'inventory_stock_id' => $movement->inventory_stock_id,
        ];
    }

    private function variantLabel(?ProductVariant $variant): string
    {
        if ($variant === null) {
            return 'Variante eliminada';
        }

        $product = $variant->product;

        if ($product === null) {
            return $variant->sku ?: 'Variante eliminada';
        }
        $size = $variant->size?->name ?? $product->baseSize?->name;
        $material = $variant->material?->name ?? $product->material?->name;
        $karat = $variant->karat ?? $product->karat;
        $metalColor = $variant->metal_color ?? $product->metal_color;
        $stone = $variant->stone?->name;

        return collect([
            $size !== null ? 'Talla '.$size : null,
            $material,
            $karat !== null ? $karat.'K' : null,
            $metalColor,
            $stone,
        ])->filter()->join(' / ') ?: '—';
    }

    private function imagePath(?Product $product): ?string
    {
        if ($product === null) {
            return null;
        }

        $image = $product->images->first();

        return $image !== null
            ? ltrim((string) parse_url($image->publicUrl(), PHP_URL_PATH), '/')
            : null;
    }
}
