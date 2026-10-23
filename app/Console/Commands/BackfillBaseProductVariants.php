<?php

namespace App\Console\Commands;

use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class BackfillBaseProductVariants extends Command
{
    protected $signature = 'products:backfill-base-variants
        {--product= : Limita la revisión a un producto específico}
        {--apply : Crea la variante base para los productos elegibles}';

    protected $description = 'Revisa y regulariza productos sin variantes base';

    public function handle(): int
    {
        $products = Product::query()
            ->withTrashed()
            ->with([
                'variants' => fn ($query) => $query->withTrashed(),
            ])
            ->when($this->option('product'), fn ($query, $productId) => $query->whereKey($productId))
            ->orderBy('id')
            ->get();

        if ($products->isEmpty()) {
            $this->info('No se encontraron productos para revisar.');

            return self::SUCCESS;
        }

        $created = 0;

        foreach ($products as $product) {
            $variants = $product->variants;
            $activeVariants = $variants->whereNull('deleted_at');

            if ($activeVariants->isNotEmpty()) {
                $this->line("Producto {$product->id} ({$product->name}): ya tiene variantes activas.");

                continue;
            }

            if ($variants->isNotEmpty()) {
                $this->warn("Producto {$product->id} ({$product->name}): solo tiene variantes soft deleted; no se modifica.");

                continue;
            }

            if ($product->trashed()) {
                $this->warn("Producto {$product->id} ({$product->name}): está soft deleted; no se modifica.");

                continue;
            }

            $this->line("Producto {$product->id} ({$product->name}): no tiene variantes.");

            if (! $this->option('apply')) {
                continue;
            }

            if (! filled($product->base_sku)) {
                $this->error("Producto {$product->id}: no tiene base_sku; no se puede regularizar.");

                continue;
            }

            if (ProductVariant::withTrashed()->where('sku', $product->base_sku)->exists()) {
                $this->error("Producto {$product->id}: el SKU {$product->base_sku} ya está en uso; no se modifica.");

                continue;
            }

            DB::transaction(function () use ($product): void {
                $variant = $product->variants()->create([
                    'sku' => $product->base_sku,
                    'size_id' => null,
                    'material_id' => null,
                    'stone_id' => null,
                    'karat' => null,
                    'metal_color' => null,
                    'cost' => null,
                    'price' => null,
                    'sale_price' => null,
                    'status' => $product->status,
                ]);

                $variant->inventoryStock()->create([
                    'current_stock' => 0,
                    'minimum_stock' => 0,
                ]);
            });

            $created++;
            $this->info("Producto {$product->id}: variante base e inventario creados.");
        }

        if (! $this->option('apply')) {
            $this->comment('Modo revisión: no se realizaron cambios. Usa --apply para regularizar los casos elegibles.');
        }

        $this->info("Variantes base creadas: {$created}.");

        return self::SUCCESS;
    }
}
