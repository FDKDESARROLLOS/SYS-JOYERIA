<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\InventoryMovement;
use App\Models\InventoryStock;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class InventoryTest extends TestCase
{
    use RefreshDatabase;

    public function test_inventory_index_lists_variants_with_pagination_and_eager_loaded_relations(): void
    {
        $admin = User::factory()->create();
        $category = $this->createCategory();

        foreach (range(1, 11) as $number) {
            $this->createStock($category, [
                'sku' => "ANI-AUR-{$number}",
            ]);
        }

        $response = $this->actingAs($admin)->get(route('inventory.stock'));

        $response
            ->assertOk()
            ->assertViewIs('inventory.stock')
            ->assertViewHas('inventoryStocks', function ($stocks): bool {
                return $stocks->perPage() === 10
                    && $stocks->total() === 11
                    && $stocks->first()->relationLoaded('productVariant')
                    && $stocks->first()->productVariant->relationLoaded('product')
                    && $stocks->first()->productVariant->product->relationLoaded('category')
                    && $stocks->first()->productVariant->product->category->relationLoaded('parent');
            });
    }

    public function test_inventory_index_searches_by_product_name_code_and_variant_sku(): void
    {
        $admin = User::factory()->create();
        $category = $this->createCategory();
        $target = $this->createStock($category, [
            'product_name' => 'Anillo Aurora',
            'product_code' => 'ANI-AUR-001',
            'sku' => 'ANI-AUR-001-ORO',
        ]);
        $other = $this->createStock($category, [
            'product_name' => 'Collar Nacar',
            'product_code' => 'COL-NAC-001',
            'sku' => 'COL-NAC-001',
        ]);

        foreach (['Anillo Aurora', 'ANI-AUR-001', 'ANI-AUR-001-ORO'] as $search) {
            $response = $this->actingAs($admin)
                ->get(route('inventory.stock', ['search' => $search]));

            $response->assertViewHas('inventoryStocks', function ($stocks) use ($target, $other): bool {
                return $stocks->contains('id', $target->id) && ! $stocks->contains('id', $other->id);
            });
        }
    }

    public function test_inventory_index_filters_available_and_out_of_stock_without_minimum_stock(): void
    {
        $admin = User::factory()->create();
        $category = $this->createCategory();
        $available = $this->createStock($category, ['sku' => 'AVAILABLE', 'current_stock' => 5, 'minimum_stock' => 2]);
        $positiveBelowFormerMinimum = $this->createStock($category, [
            'sku' => 'POSITIVE-BELOW-FORMER-MINIMUM',
            'current_stock' => 1,
            'minimum_stock' => 99,
        ]);
        $out = $this->createStock($category, ['sku' => 'OUT', 'current_stock' => 0, 'minimum_stock' => 2]);

        foreach ([
            'available' => [$available, [$out]],
            'out' => [$out, [$available, $positiveBelowFormerMinimum]],
        ] as $status => [$included, $excluded]) {
            $response = $this->actingAs($admin)
                ->get(route('inventory.stock', ['stock_status' => $status]));

            $response->assertViewHas('inventoryStocks', function ($stocks) use ($included, $excluded): bool {
                return $stocks->contains('id', $included->id)
                    && collect($excluded)->every(fn (InventoryStock $stock): bool => ! $stocks->contains('id', $stock->id));
            });
        }
    }

    public function test_inventory_hides_minimum_stock_and_stock_bajo_from_the_interface(): void
    {
        $admin = User::factory()->create();
        $category = $this->createCategory();
        $available = $this->createStock($category, [
            'current_stock' => 1,
            'minimum_stock' => 99,
            'sku' => 'AVAILABLE-WITHOUT-MINIMUM',
        ]);
        $out = $this->createStock($category, [
            'current_stock' => 0,
            'minimum_stock' => 99,
            'sku' => 'OUT-WITHOUT-MINIMUM',
        ]);

        $response = $this->actingAs($admin)->get(route('inventory.stock'));

        $response
            ->assertOk()
            ->assertSee('Disponible')
            ->assertSee('Sin stock')
            ->assertDontSee('Stock bajo')
            ->assertDontSee('Stock mínimo')
            ->assertDontSee('mínimo')
            ->assertViewHas('inventoryRows', function ($rows) use ($available, $out): bool {
                return $rows->firstWhere('inventory_stock_id', $available->id)['stock_status'] === 'Disponible'
                    && $rows->firstWhere('inventory_stock_id', $out->id)['stock_status'] === 'Sin stock';
            });

        $this->assertDatabaseHas('inventory_stocks', [
            'id' => $available->id,
            'minimum_stock' => 99,
        ]);
    }

    public function test_inventory_index_filters_by_category_and_links_to_real_movements(): void
    {
        $admin = User::factory()->create();
        $targetCategory = $this->createCategory();
        $otherCategory = $this->createCategory();
        $target = $this->createStock($targetCategory, ['sku' => 'TARGET-SKU']);
        $other = $this->createStock($otherCategory, ['sku' => 'OTHER-SKU']);

        $response = $this->actingAs($admin)
            ->get(route('inventory.stock', ['category_id' => $targetCategory->id]));

        $response
            ->assertOk()
            ->assertDontSee('ficticios')
            ->assertViewHas('inventoryRows')
            ->assertViewHas('inventoryStocks', function ($stocks) use ($target, $other): bool {
                return $stocks->contains('id', $target->id)
                    && ! $stocks->contains('id', $other->id);
            })
            ->assertSee('href="'.route('inventory.movements.show', $target).'"', false);
    }

    public function test_inventory_uses_parent_category_for_display_and_filtering(): void
    {
        $admin = User::factory()->create();
        $parentCategory = $this->createCategory([
            'name' => 'Anillos',
            'slug' => 'anillos',
        ]);
        $childCategory = $this->createCategory([
            'name' => 'Compromiso',
            'slug' => 'compromiso',
            'parent_id' => $parentCategory->id,
        ]);
        $otherCategory = $this->createCategory([
            'name' => 'Collares',
            'slug' => 'collares',
        ]);
        $parentStock = $this->createStock($parentCategory, ['sku' => 'PARENT-CATEGORY']);
        $childStock = $this->createStock($childCategory, ['sku' => 'CHILD-CATEGORY']);
        $otherStock = $this->createStock($otherCategory, ['sku' => 'OTHER-CATEGORY']);

        $response = $this->actingAs($admin)
            ->get(route('inventory.stock', ['category_id' => $parentCategory->id]));

        $response
            ->assertOk()
            ->assertSee('Anillos')
            ->assertDontSee('Compromiso')
            ->assertViewHas('inventoryRows', function ($rows) use ($parentStock, $childStock, $otherStock): bool {
                return $rows->firstWhere('inventory_stock_id', $parentStock->id)['category'] === 'Anillos'
                    && $rows->firstWhere('inventory_stock_id', $childStock->id)['category'] === 'Anillos'
                    && ! $rows->contains('inventory_stock_id', $otherStock->id);
            })
            ->assertViewHas('categories', function ($categories) use ($parentCategory, $childCategory): bool {
                return $categories->contains('id', $parentCategory->id)
                    && ! $categories->contains('id', $childCategory->id);
            });
    }

    public function test_inventory_detail_shows_history_and_effective_stock_data(): void
    {
        $admin = User::factory()->create();
        $stock = $this->createStock($this->createCategory(), [
            'current_stock' => 4,
            'minimum_stock' => 2,
        ]);
        $stock->movements()->create([
            'user_id' => $admin->id,
            'type' => 'Entrada',
            'quantity' => 4,
            'previous_stock' => 0,
            'resulting_stock' => 4,
            'reason' => 'Carga inicial',
            'movement_at' => now(),
        ]);
        $otherStock = $this->createStock($this->createCategory(), ['sku' => 'OTHER-VARIANT']);
        $otherStock->movements()->create([
            'user_id' => $admin->id,
            'type' => 'Entrada',
            'quantity' => 2,
            'previous_stock' => 0,
            'resulting_stock' => 2,
            'reason' => 'Otro historial',
            'movement_at' => now(),
        ]);

        $response = $this->actingAs($admin)
            ->get(route('inventory.movements.show', $stock));

        $response
            ->assertOk()
            ->assertViewIs('inventory.show')
            ->assertSee($stock->productVariant->sku)
            ->assertSee('Carga inicial')
            ->assertSee('4')
            ->assertDontSee('OTHER-VARIANT')
            ->assertSee('Historial de inventario', false);
    }

    public function test_inventory_detail_uses_cover_then_sort_order_and_placeholder_for_product_images(): void
    {
        $admin = User::factory()->create();
        $category = $this->createCategory();
        $withCover = $this->createStock($category, ['sku' => 'WITH-COVER']);
        $withCover->productVariant->product->images()->createMany([
            ['path' => 'fallback.jpg', 'sort_order' => 0, 'is_cover' => false],
            ['path' => 'cover.jpg', 'sort_order' => 5, 'is_cover' => true],
        ]);
        $withoutCover = $this->createStock($category, ['sku' => 'WITHOUT-COVER']);
        $withoutCover->productVariant->product->images()->createMany([
            ['path' => 'later.jpg', 'sort_order' => 4, 'is_cover' => false],
            ['path' => 'first.jpg', 'sort_order' => 1, 'is_cover' => false],
        ]);
        $withoutImages = $this->createStock($category, ['sku' => 'WITHOUT-IMAGES']);

        $this->actingAs($admin)
            ->get(route('inventory.movements.show', $withCover))
            ->assertViewHas('inventoryRow.image', 'uploads/products/cover.jpg');

        $this->actingAs($admin)
            ->get(route('inventory.movements.show', $withoutCover))
            ->assertViewHas('inventoryRow.image', 'uploads/products/first.jpg');

        $this->actingAs($admin)
            ->get(route('inventory.movements.show', $withoutImages))
            ->assertViewHas('inventoryRow.image', null)
            ->assertSee('<rect x="3.5" y="4.5" width="17" height="15"', false);
    }

    public function test_movements_index_uses_real_data_with_filters_pagination_and_signed_quantities(): void
    {
        $admin = User::factory()->create(['name' => 'Lucía Mendoza']);
        $category = $this->createCategory();
        $target = $this->createStock($category, [
            'product_name' => 'Anillo Real',
            'sku' => 'REAL-SKU',
            'current_stock' => 6,
        ]);
        $target->movements()->create([
            'user_id' => $admin->id,
            'type' => 'Entrada',
            'quantity' => 5,
            'previous_stock' => 1,
            'resulting_stock' => 6,
            'reason' => 'Reposición real',
            'reference' => 'ENT-REAL',
            'movement_at' => '2026-10-02 10:30:00',
        ]);
        $target->movements()->create([
            'user_id' => $admin->id,
            'type' => 'Salida',
            'quantity' => 2,
            'previous_stock' => 8,
            'resulting_stock' => 6,
            'reason' => 'Venta real',
            'reference' => 'PED-REAL',
            'movement_at' => '2026-10-03 10:30:00',
        ]);

        foreach (range(1, 9) as $number) {
            $stock = $this->createStock($category, ['sku' => "OTHER-{$number}"]);
            $stock->movements()->create([
                'user_id' => $admin->id,
                'type' => 'Entrada',
                'quantity' => 1,
                'previous_stock' => 0,
                'resulting_stock' => 1,
                'reason' => 'Carga',
                'movement_at' => '2026-10-01 10:30:00',
            ]);
        }

        $response = $this->actingAs($admin)->get(route('inventory.movements.index'));

        $response
            ->assertOk()
            ->assertViewIs('inventory.movements')
            ->assertViewHas('inventoryMovements', function ($movements): bool {
                return $movements->perPage() === 10
                    && $movements->total() === 11
                    && $movements->first()->relationLoaded('inventoryStock')
                    && $movements->first()->inventoryStock->productVariant->relationLoaded('product')
                    && $movements->first()->user->exists;
            })
            ->assertViewHas('movementRows')
            ->assertDontSee('ficticios')
            ->assertSee('href="'.route('inventory.movements.show', $target).'"', false);

        $this->actingAs($admin)
            ->get(route('inventory.movements.create', ['variant' => $target->id]))
            ->assertSee('option value="'.$target->id.'"', false)
            ->assertSee('selected', false);

        $this->actingAs($admin)
            ->get(route('inventory.movements.create'))
            ->assertDontSee('value="'.$target->id.'" selected', false);

        $this->actingAs($admin)
            ->get(route('inventory.movements.index', ['type' => 'Salida']))
            ->assertViewHas('inventoryMovements', function ($movements) use ($target): bool {
                return $movements->count() === 1
                    && $movements->first()->inventoryStock->id === $target->id;
            });

        $this->actingAs($admin)
            ->get(route('inventory.movements.index', ['date' => '2026-10-02']))
            ->assertViewHas('inventoryMovements', function ($movements): bool {
                return $movements->count() === 1
                    && $movements->first()->reference === 'ENT-REAL';
            });

        foreach (['Anillo Real', 'REAL-SKU', 'Venta real', 'PED-REAL'] as $search) {
            $this->actingAs($admin)
                ->get(route('inventory.movements.index', ['search' => $search]))
                ->assertViewHas('inventoryMovements', function ($movements) use ($target): bool {
                    return $movements->contains('inventory_stock_id', $target->id);
                });
        }

        $this->actingAs($admin)
            ->get(route('inventory.movements.index', ['search' => 'REAL-SKU']))
            ->assertSee('+5')
            ->assertSee('-2')
            ->assertSee('Lucía Mendoza');
    }

    public function test_entry_updates_stock_and_records_the_authenticated_user(): void
    {
        $admin = User::factory()->create();
        $stock = $this->createStock($this->createCategory(), ['current_stock' => 2]);

        $this->actingAs($admin)
            ->post(route('inventory.movements.variant-store', $stock), $this->movementPayload([
                'type' => 'Entrada',
                'quantity' => 5,
            ]))
            ->assertRedirect(route('inventory.movements.show', $stock))
            ->assertSessionHas('status', 'Movimiento registrado correctamente.');

        $this->assertDatabaseHas('inventory_stocks', ['id' => $stock->id, 'current_stock' => 7]);
        $this->assertDatabaseHas('inventory_movements', [
            'inventory_stock_id' => $stock->id,
            'user_id' => $admin->id,
            'type' => 'Entrada',
            'quantity' => 5,
            'previous_stock' => 2,
            'resulting_stock' => 7,
        ]);
    }

    public function test_new_movement_route_uses_real_inventory_and_registers_the_movement(): void
    {
        $admin = User::factory()->create();
        $stock = $this->createStock($this->createCategory(), [
            'sku' => 'CREATE-SKU',
            'current_stock' => 2,
        ]);

        $this->actingAs($admin)
            ->get(route('inventory.movements.create'))
            ->assertOk()
            ->assertSee('CREATE-SKU')
            ->assertSee('action="'.route('inventory.movements.store').'"', false)
            ->assertSee('name="_token"', false)
            ->assertSee('id="register-movement" type="submit"', false)
            ->assertDontSee('id="inventory-item" name="inventory_stock_id" class="admin-form-input" required disabled', false)
            ->assertDontSee('Esta acción es solo visual por ahora.')
            ->assertDontSee('aurea-demo');

        $this->actingAs($admin)
            ->post(route('inventory.movements.store'), [
                'inventory_stock_id' => $stock->id,
            ])
            ->assertSessionHasErrors(['type', 'reason', 'movement_at']);

        $this->actingAs($admin)
            ->post(route('inventory.movements.store'), [
                'inventory_stock_id' => $stock->id,
                'type' => 'Entrada',
                'reason' => 'Entrada incompleta',
                'movement_at' => '2026-10-04 10:30:00',
            ])
            ->assertSessionHasErrors('quantity');

        $this->actingAs($admin)
            ->post(route('inventory.movements.store'), [
                'inventory_stock_id' => $stock->id,
                'type' => 'Devolución',
                'quantity' => 2,
                'reason' => 'Devolución real',
                'reference' => 'DEV-REAL',
                'movement_at' => '2026-10-04 10:30:00',
            ])
            ->assertRedirect(route('inventory.movements.show', $stock));

        $this->assertDatabaseHas('inventory_movements', [
            'inventory_stock_id' => $stock->id,
            'type' => 'Devolución',
            'quantity' => 2,
            'user_id' => $admin->id,
        ]);
    }

    public function test_contextual_movement_locks_variant_and_rejects_a_tampered_stock(): void
    {
        $admin = User::factory()->create();
        $category = $this->createCategory();
        $contextStock = $this->createStock($category, [
            'sku' => 'CONTEXT-SKU',
            'current_stock' => 4,
        ]);
        $otherStock = $this->createStock($category, [
            'sku' => 'OTHER-CONTEXT-SKU',
            'current_stock' => 9,
        ]);

        $this->actingAs($admin)
            ->get(route('inventory.movements.create', ['variant' => $contextStock->id]))
            ->assertOk()
            ->assertSee('action="'.route('inventory.movements.context-store', $contextStock).'"', false)
            ->assertSee('name="inventory_stock_id" value="'.$contextStock->id.'"', false)
            ->assertSee('id="inventory-item" name="inventory_stock_id" class="admin-form-input" required disabled', false)
            ->assertSee('CONTEXT-SKU')
            ->assertSee('data-stock="'.$contextStock->current_stock.'"', false);

        $payload = $this->movementPayload([
            'inventory_stock_id' => $otherStock->id,
            'type' => 'Entrada',
            'quantity' => 2,
        ]);

        $this->actingAs($admin)
            ->post(route('inventory.movements.context-store', $contextStock), $payload)
            ->assertSessionHasErrors('inventory_stock_id');

        $this->assertDatabaseCount('inventory_movements', 0);

        $this->actingAs($admin)
            ->post(route('inventory.movements.context-store', $contextStock), array_merge($payload, [
                'inventory_stock_id' => $contextStock->id,
            ]))
            ->assertRedirect(route('inventory.movements.show', $contextStock));

        $this->assertDatabaseHas('inventory_movements', [
            'inventory_stock_id' => $contextStock->id,
            'resulting_stock' => 6,
        ]);
        $this->assertDatabaseMissing('inventory_movements', [
            'inventory_stock_id' => $otherStock->id,
        ]);
    }

    public function test_exit_updates_stock_and_cannot_make_it_negative(): void
    {
        $admin = User::factory()->create();
        $stock = $this->createStock($this->createCategory(), ['current_stock' => 5]);

        $this->actingAs($admin)
            ->post(route('inventory.movements.variant-store', $stock), $this->movementPayload([
                'type' => 'Salida',
                'quantity' => 3,
            ]))
            ->assertRedirect();

        $this->assertDatabaseHas('inventory_stocks', ['id' => $stock->id, 'current_stock' => 2]);

        $this->actingAs($admin)
            ->post(route('inventory.movements.variant-store', $stock), $this->movementPayload([
                'type' => 'Salida',
                'quantity' => 3,
            ]))
            ->assertSessionHasErrors('quantity');

        $this->assertDatabaseHas('inventory_stocks', ['id' => $stock->id, 'current_stock' => 2]);
        $this->assertSame(1, InventoryMovement::where('inventory_stock_id', $stock->id)->count());
    }

    public function test_adjustment_sets_the_real_stock_and_records_the_difference(): void
    {
        $admin = User::factory()->create();
        $stock = $this->createStock($this->createCategory(), ['current_stock' => 7]);

        $this->actingAs($admin)
            ->post(route('inventory.movements.variant-store', $stock), $this->movementPayload([
                'type' => 'Ajuste',
                'adjustment_stock' => 3,
            ]))
            ->assertRedirect();

        $this->assertDatabaseHas('inventory_stocks', ['id' => $stock->id, 'current_stock' => 3]);
        $this->assertDatabaseHas('inventory_movements', [
            'inventory_stock_id' => $stock->id,
            'type' => 'Ajuste',
            'quantity' => 4,
            'previous_stock' => 7,
            'resulting_stock' => 3,
        ]);
    }

    public function test_minimum_stock_can_be_updated_without_creating_a_movement(): void
    {
        $admin = User::factory()->create();
        $stock = $this->createStock($this->createCategory(), ['minimum_stock' => 2]);

        $this->actingAs($admin)
            ->patch(route('inventory.minimum-stock.update', $stock), ['minimum_stock' => 6])
            ->assertRedirect(route('inventory.movements.show', $stock));

        $this->assertDatabaseHas('inventory_stocks', ['id' => $stock->id, 'minimum_stock' => 6]);
        $this->assertDatabaseCount('inventory_movements', 0);
    }

    public function test_inventory_operations_are_isolated_between_variants(): void
    {
        $admin = User::factory()->create();
        $category = $this->createCategory();
        $first = $this->createStock($category, ['sku' => 'FIRST', 'current_stock' => 2]);
        $second = $this->createStock($category, ['sku' => 'SECOND', 'current_stock' => 8]);

        $this->actingAs($admin)
            ->post(route('inventory.movements.variant-store', $first), $this->movementPayload([
                'type' => 'Entrada',
                'quantity' => 3,
            ]))
            ->assertRedirect();

        $this->assertDatabaseHas('inventory_stocks', ['id' => $first->id, 'current_stock' => 5]);
        $this->assertDatabaseHas('inventory_stocks', ['id' => $second->id, 'current_stock' => 8]);
        $this->assertDatabaseHas('inventory_movements', ['inventory_stock_id' => $first->id]);
        $this->assertDatabaseMissing('inventory_movements', ['inventory_stock_id' => $second->id]);
    }

    public function test_inventory_requires_authentication(): void
    {
        $stock = $this->createStock($this->createCategory());

        $this->get(route('inventory.stock'))->assertRedirect(route('login'));
        $this->get(route('inventory.movements.show', $stock))->assertRedirect(route('login'));
        $this->post(route('inventory.movements.variant-store', $stock), $this->movementPayload())
            ->assertRedirect(route('login'));
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function movementPayload(array $overrides = []): array
    {
        return array_merge([
            'type' => 'Entrada',
            'quantity' => 1,
            'reason' => 'Prueba de inventario',
            'reference' => 'TEST-001',
            'movement_at' => now()->format('Y-m-d H:i:s'),
        ], $overrides);
    }

    private function createCategory(array $overrides = []): Category
    {
        return Category::create(array_merge([
            'name' => 'Anillos '.Str::upper(Str::random(4)),
            'slug' => 'anillos-'.Str::lower(Str::random(8)),
            'status' => true,
            'sort_order' => 1,
        ], $overrides));
    }

    /**
     * @param  array<string, mixed>  $overrides
     */
    private function createStock(Category $category, array $overrides = []): InventoryStock
    {
        $suffix = Str::lower(Str::random(8));
        $product = Product::create([
            'category_id' => $category->id,
            'name' => $overrides['product_name'] ?? 'Anillo Aurora',
            'model' => 'Modelo Aurora',
            'code' => $overrides['product_code'] ?? 'PRD-'.$suffix,
            'base_sku' => $overrides['base_sku'] ?? 'SKU-'.$suffix,
            'slug' => 'producto-'.$suffix,
            'price' => '1250.00',
            'status' => 'active',
        ]);
        $variant = ProductVariant::create([
            'product_id' => $product->id,
            'sku' => $overrides['sku'] ?? 'VAR-'.$suffix,
            'status' => 'active',
        ]);

        return InventoryStock::create([
            'product_variant_id' => $variant->id,
            'current_stock' => $overrides['current_stock'] ?? 0,
            'minimum_stock' => $overrides['minimum_stock'] ?? 0,
        ]);
    }
}
