<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\InventoryMovement;
use App\Models\InventoryStock;
use App\Models\Material;
use App\Models\Product;
use App\Models\ProductImage;
use App\Models\ProductStone;
use App\Models\ProductVariant;
use App\Models\Size;
use App\Models\Stone;
use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Tests\TestCase;

class ProductVariantTest extends TestCase
{
    use RefreshDatabase;

    protected string $testUploadDirectory;

    protected string $testUploadUrlPrefix;

    protected function setUp(): void
    {
        parent::setUp();

        $testUploadId = (string) Str::uuid();
        $this->testUploadDirectory = public_path('uploads/products/testing/'.$testUploadId);
        $this->testUploadUrlPrefix = 'uploads/products/testing/'.$testUploadId;

        config([
            'aurea.uploads.products_path' => $this->testUploadDirectory,
            'aurea.uploads.products_url' => $this->testUploadUrlPrefix,
        ]);
    }

    protected function tearDown(): void
    {
        File::deleteDirectory($this->testUploadDirectory);

        parent::tearDown();
    }

    public function test_variant_tests_do_not_remove_preexisting_catalog_files(): void
    {
        $admin = User::factory()->create();
        [$product] = $this->createProductWithSize();
        $catalogDirectory = public_path('uploads/products');
        $before = collect(File::files($catalogDirectory))
            ->mapWithKeys(fn ($file): array => [$file->getFilename() => $file->getSize()])
            ->all();

        $this->actingAs($admin)->get(route('products.variants.index', $product))->assertOk();

        $after = collect(File::files($catalogDirectory))
            ->mapWithKeys(fn ($file): array => [$file->getFilename() => $file->getSize()])
            ->all();

        $this->assertSame($before, $after);
    }

    public function test_variant_context_uses_the_product_cover_image(): void
    {
        $admin = User::factory()->create();
        [$product] = $this->createProductWithSize();
        ProductImage::create([
            'product_id' => $product->id,
            'path' => 'cover-variant.jpg',
            'sort_order' => 4,
            'is_cover' => true,
        ]);
        ProductImage::create([
            'product_id' => $product->id,
            'path' => 'first-variant.jpg',
            'sort_order' => 0,
            'is_cover' => false,
        ]);
        File::ensureDirectoryExists($this->testUploadDirectory);
        File::put($this->testUploadDirectory.'/cover-variant.jpg', 'cover');
        File::put($this->testUploadDirectory.'/first-variant.jpg', 'first');

        $response = $this->actingAs($admin)->get(route('products.variants.index', $product));

        $response
            ->assertSee(asset($this->testUploadUrlPrefix.'/cover-variant.jpg'))
            ->assertViewHas('product', fn (array $context): bool => $context['imageUrl'] === asset($this->testUploadUrlPrefix.'/cover-variant.jpg'));
    }

    public function test_variant_context_uses_the_first_sorted_image_without_a_cover(): void
    {
        $admin = User::factory()->create();
        [$product] = $this->createProductWithSize();
        ProductImage::create([
            'product_id' => $product->id,
            'path' => 'later-variant.jpg',
            'sort_order' => 2,
            'is_cover' => false,
        ]);
        ProductImage::create([
            'product_id' => $product->id,
            'path' => 'first-variant.jpg',
            'sort_order' => 1,
            'is_cover' => false,
        ]);
        File::ensureDirectoryExists($this->testUploadDirectory);
        File::put($this->testUploadDirectory.'/later-variant.jpg', 'later');
        File::put($this->testUploadDirectory.'/first-variant.jpg', 'first');

        $response = $this->actingAs($admin)->get(route('products.variants.index', $product));

        $response
            ->assertSee(asset($this->testUploadUrlPrefix.'/first-variant.jpg'))
            ->assertViewHas('product', fn (array $context): bool => $context['imageUrl'] === asset($this->testUploadUrlPrefix.'/first-variant.jpg'));
    }

    public function test_variant_context_uses_placeholder_without_product_images(): void
    {
        $admin = User::factory()->create();
        [$product] = $this->createProductWithSize();

        $response = $this->actingAs($admin)->get(route('products.variants.index', $product));

        $response
            ->assertViewHas('product', fn (array $context): bool => $context['imageUrl'] === null && $context['imageExists'] === false)
            ->assertDontSee('<img src=""', false);
    }

    public function test_variant_context_normalizes_legacy_product_image_paths(): void
    {
        $admin = User::factory()->create();
        [$product] = $this->createProductWithSize();
        ProductImage::create([
            'product_id' => $product->id,
            'path' => $this->testUploadUrlPrefix.'/uploads/products/legacy-variant.jpg',
            'sort_order' => 0,
            'is_cover' => true,
        ]);
        File::ensureDirectoryExists($this->testUploadDirectory.'/uploads/products');
        File::put($this->testUploadDirectory.'/uploads/products/legacy-variant.jpg', 'legacy');

        $response = $this->actingAs($admin)->get(route('products.variants.index', $product));

        $response
            ->assertSee(asset($this->testUploadUrlPrefix.'/uploads/products/legacy-variant.jpg'))
            ->assertDontSee($this->testUploadUrlPrefix.'/uploads/products/uploads/products/legacy-variant.jpg');
    }

    public function test_authenticated_user_can_view_variants_with_eager_loaded_relations_and_pagination(): void
    {
        $admin = User::factory()->create();
        [$product, $size] = $this->createProductWithSize();

        foreach (range(1, 11) as $number) {
            ProductVariant::create($this->variantPayload($product, $size, [
                'sku' => "SKU-{$number}",
                'metal_color' => "Color {$number}",
            ]));
        }

        $response = $this->actingAs($admin)->get(route('products.variants.index', $product));

        $response
            ->assertOk()
            ->assertViewIs('products.variants.index')
            ->assertViewHas('productVariants', function ($variants): bool {
                return $variants instanceof LengthAwarePaginator
                    && $variants->perPage() === 10
                    && $variants->total() === 11
                    && $variants->first()->relationLoaded('size')
                    && $variants->first()->relationLoaded('material')
                    && $variants->first()->relationLoaded('stone')
                    && $variants->first()->relationLoaded('inventoryStock');
            });
    }

    public function test_variant_index_displays_effective_inherited_and_overridden_values(): void
    {
        $admin = User::factory()->create();
        [$product, $baseSize] = $this->createProductWithSize([
            'price' => '1250.00',
            'sale_price' => '1100.00',
            'cost' => '500.00',
        ]);
        $material = $this->createMaterial();
        $stone = $this->createStone();
        $product->update([
            'base_size_id' => $baseSize->id,
            'material_id' => $material->id,
        ]);
        ProductStone::create([
            'product_id' => $product->id,
            'stone_id' => $stone->id,
            'position' => 'central',
        ]);
        ProductVariant::create($this->variantPayload($product, null, [
            'sku' => 'VAR-INHERITED',
            'price' => null,
            'sale_price' => null,
            'cost' => null,
        ]));
        ProductVariant::create($this->variantPayload($product, $baseSize, [
            'sku' => 'VAR-OVERRIDE',
            'price' => '1600.00',
            'sale_price' => '1450.00',
            'cost' => '700.00',
            'metal_color' => 'Blanco',
        ]));

        $response = $this->actingAs($admin)->get(route('products.variants.index', $product));

        $response
            ->assertOk()
            ->assertSee('VAR-INHERITED')
            ->assertSee('VAR-OVERRIDE')
            ->assertSee('$1,250.00 MXN')
            ->assertSee('$1,600.00 MXN')
            ->assertSee($baseSize->name)
            ->assertSee($material->name)
            ->assertSee('—')
            ->assertDontSee($stone->name);
    }

    public function test_variant_index_displays_only_the_direct_commercial_stone(): void
    {
        $admin = User::factory()->create();
        [$product, $size] = $this->createProductWithSize();
        $physicalStone = $this->createStone(['name' => 'Zirconia física', 'slug' => 'zirconia-fisica']);
        $commercialStone = $this->createStone([
            'name' => 'Diamante comercial',
            'slug' => 'diamante-comercial',
        ]);
        ProductStone::create([
            'product_id' => $product->id,
            'stone_id' => $physicalStone->id,
            'position' => 'central',
        ]);
        ProductVariant::create($this->variantPayload($product, $size, [
            'sku' => 'VAR-SIN-PIEDRA',
        ]));
        ProductVariant::create($this->variantPayload($product, $size, [
            'sku' => 'VAR-PIEDRA-COMERCIAL',
            'stone_id' => $commercialStone->id,
        ]));

        $response = $this->actingAs($admin)->get(route('products.variants.index', $product));

        $response
            ->assertOk()
            ->assertSee('Diamante comercial')
            ->assertSee('—')
            ->assertDontSee('Zirconia física');
    }

    public function test_authenticated_user_can_access_create_and_edit_variant_views(): void
    {
        $admin = User::factory()->create();
        [$product, $size] = $this->createProductWithSize();
        $variant = ProductVariant::create($this->variantPayload($product, $size));

        $this->actingAs($admin)
            ->get(route('products.variants.create', $product))
            ->assertOk()
            ->assertViewIs('products.variants.create')
            ->assertViewHas('applicableSizes', fn ($sizes): bool => $sizes->contains('id', $size->id));

        $this->actingAs($admin)
            ->get(route('products.variants.edit', [$product, $variant]))
            ->assertOk()
            ->assertViewIs('products.variants.edit')
            ->assertViewHas('variant', fn (ProductVariant $viewVariant): bool => $viewVariant->is($variant));
    }

    public function test_variant_form_only_shows_attributes_that_can_override_the_product(): void
    {
        $admin = User::factory()->create();
        [$product, $baseSize] = $this->createProductWithSize();
        $baseMaterial = $this->createMaterial();
        $alternativeMaterial = $this->createMaterial([
            'name' => 'Plata',
            'slug' => 'plata-'.Str::lower(Str::random(5)),
        ]);
        $baseStone = $this->createStone();
        $alternativeStone = $this->createStone([
            'name' => 'Rubi',
            'slug' => 'rubi-'.Str::lower(Str::random(5)),
        ]);
        $product->update([
            'base_size_id' => $baseSize->id,
            'material_id' => $baseMaterial->id,
            'karat' => 14,
            'metal_color' => 'Amarillo',
        ]);
        ProductStone::create([
            'product_id' => $product->id,
            'stone_id' => $baseStone->id,
            'position' => 'central',
        ]);

        $response = $this->actingAs($admin)->get(route('products.variants.create', $product));

        $response
            ->assertOk()
            ->assertSee('Usar material del producto (Oro)')
            ->assertSee('Usar kilataje del producto (14K)')
            ->assertSee('Usar color del producto (Amarillo)')
            ->assertSee('Usar talla de referencia (7)')
            ->assertSee('Sin piedra comercial asignada')
            ->assertViewHas('materials', fn ($materials): bool => $materials->contains('id', $alternativeMaterial->id)
                && ! $materials->contains('id', $baseMaterial->id))
            ->assertViewHas('stones', fn ($stones): bool => $stones->contains('id', $alternativeStone->id)
                && $stones->contains('id', $baseStone->id))
            ->assertViewHas('karatOptions', fn (array $options): bool => ! array_key_exists(14, $options)
                && array_key_exists(10, $options)
                && array_key_exists(18, $options))
            ->assertViewHas('metalColorOptions', fn (array $options): bool => ! in_array('Amarillo', $options, true)
                && in_array('Blanco', $options, true));
    }

    public function test_equal_inherited_attributes_are_normalized_but_commercial_stone_remains_direct(): void
    {
        $admin = User::factory()->create();
        [$product, $baseSize] = $this->createProductWithSize([
            'base_sku' => 'ANI-AUR-001',
            'code' => 'ANI-AUR-001',
        ]);
        $newSize = $this->createSize($product->category, '8');
        $baseMaterial = $this->createMaterial();
        $baseStone = $this->createStone();
        $product->update([
            'base_size_id' => $baseSize->id,
            'material_id' => $baseMaterial->id,
            'karat' => 14,
            'metal_color' => 'Amarillo',
        ]);
        ProductStone::create([
            'product_id' => $product->id,
            'stone_id' => $baseStone->id,
            'position' => 'central',
        ]);

        $this->actingAs($admin)
            ->post(route('products.variants.store', $product), [
                'size_id' => $newSize->id,
                'material_id' => $baseMaterial->id,
                'stone_id' => $baseStone->id,
                'karat' => 14,
                'metal_color' => 'Amarillo',
                'status' => 'active',
            ])
            ->assertRedirect();

        $variant = ProductVariant::firstOrFail();

        $this->assertNull($variant->material_id);
        $this->assertSame($baseStone->id, $variant->stone_id);
        $this->assertNull($variant->karat);
        $this->assertNull($variant->metal_color);
        $this->assertSame($newSize->id, $variant->size_id);
        $this->assertSame('ANI-AUR-001-T8-DIA', $variant->sku);
    }

    public function test_edit_shows_overrides_and_inherited_values_as_null(): void
    {
        $admin = User::factory()->create();
        [$product, $baseSize] = $this->createProductWithSize();
        $baseMaterial = $this->createMaterial();
        $alternativeMaterial = $this->createMaterial([
            'name' => 'Plata',
            'slug' => 'plata-'.Str::lower(Str::random(5)),
        ]);
        $product->update([
            'base_size_id' => $baseSize->id,
            'material_id' => $baseMaterial->id,
            'karat' => 14,
            'metal_color' => 'Amarillo',
        ]);
        $variant = ProductVariant::create($this->variantPayload($product, null, [
            'material_id' => $alternativeMaterial->id,
            'karat' => null,
            'metal_color' => null,
        ]));

        $response = $this->actingAs($admin)->get(route('products.variants.edit', [$product, $variant]));
        $html = $response->getContent();

        $this->actingAs($admin)
            ->get(route('products.variants.index', $product))
            ->assertOk();

        $response
            ->assertOk()
            ->assertSee('Usar material del producto (Oro)')
            ->assertSee('Usar kilataje del producto (14K)')
            ->assertSee('Usar talla de referencia (7)');
        $this->assertStringContainsString('value="'.$alternativeMaterial->id.'" selected', $html);
        $this->assertStringNotContainsString('value="'.$baseMaterial->id.'"', $html);
    }

    public function test_store_generates_the_inventory_sku_and_creates_zeroed_stock(): void
    {
        $admin = User::factory()->create();
        [$product, $size] = $this->createProductWithSize([
            'base_sku' => 'ANI-AUR-001',
            'code' => 'ANI-AUR-001',
        ]);
        $material = $this->createMaterial();

        $response = $this->actingAs($admin)->post(route('products.variants.store', $product), [
            'size_id' => $size?->id,
            'material_id' => $material->id,
            'karat' => 14,
            'metal_color' => 'Amarillo',
            'cost' => '500.00',
            'price' => '1250.00',
            'sale_price' => null,
            'status' => 'active',
        ]);

        $variant = ProductVariant::firstOrFail();

        $response
            ->assertRedirect(route('products.variants.index', $product))
            ->assertSessionHas('status', 'Variante creada correctamente.');
        $this->assertSame('ANI-AUR-001-T7-ORO-14K-AMARILLO', $variant->sku);
        $this->assertDatabaseHas('inventory_stocks', [
            'product_variant_id' => $variant->id,
            'current_stock' => 0,
            'minimum_stock' => 0,
        ]);
    }

    public function test_variant_zero_sale_price_is_normalized_to_null(): void
    {
        $admin = User::factory()->create();
        [$product, $size] = $this->createProductWithSize();
        $material = $this->createMaterial();

        $this->actingAs($admin)
            ->post(route('products.variants.store', $product), [
                'size_id' => $size->id,
                'material_id' => $material->id,
                'price' => '1000.00',
                'sale_price' => '0.00',
                'status' => 'active',
            ])
            ->assertRedirect();

        $variant = ProductVariant::query()->where('product_id', $product->id)->firstOrFail();

        $this->assertNull($variant->sale_price);
    }

    public function test_store_resolves_a_duplicate_sku_with_an_incremental_suffix(): void
    {
        $admin = User::factory()->create();
        [$product, $size] = $this->createProductWithSize([
            'base_sku' => 'ANI-AUR-001',
            'code' => 'ANI-AUR-001',
        ]);
        $material = $this->createMaterial();

        ProductVariant::create($this->variantPayload($product, $size, [
            'sku' => 'ANI-AUR-001-T7-ORO-14K-AMARILLO',
            'material_id' => null,
            'karat' => 14,
            'metal_color' => 'Amarillo',
        ]));

        $this->actingAs($admin)->post(route('products.variants.store', $product), [
            'size_id' => $size?->id,
            'material_id' => $material->id,
            'karat' => 14,
            'metal_color' => 'Amarillo',
            'status' => 'active',
        ])->assertRedirect();

        $this->assertDatabaseHas('product_variants', ['sku' => 'ANI-AUR-001-T7-ORO-14K-AMARILLO-2']);
    }

    public function test_variants_without_size_can_differ_by_material_stone_color_or_karat(): void
    {
        $admin = User::factory()->create();
        [$product] = $this->createProductWithSize([
            'base_sku' => 'ANI-AUR-001',
            'code' => 'ANI-AUR-001',
        ]);
        $oro = $this->createMaterial();
        $plata = $this->createMaterial(['name' => 'Plata', 'slug' => 'plata-'.Str::lower(Str::random(5))]);
        $stone = $this->createStone();

        $payloads = [
            [
                'material_id' => $oro->id,
            ],
            [
                'stone_id' => $stone->id,
            ],
            [
                'material_id' => $plata->id,
            ],
            [
                'karat' => 18,
            ],
            [
                'metal_color' => 'Rosa',
            ],
        ];

        foreach ($payloads as $payload) {
            $this->actingAs($admin)
                ->post(route('products.variants.store', $product), [
                    ...$payload,
                    'status' => 'active',
                ])
                ->assertRedirect(route('products.variants.index', $product));
        }

        $this->assertSame(5, ProductVariant::whereBelongsTo($product)->count());
        $this->assertSame(5, $product->variants()->with('inventoryStock')->get()->filter(fn ($variant): bool => $variant->inventoryStock !== null)->count());
        $this->assertDatabaseHas('product_variants', ['sku' => 'ANI-AUR-001-ORO']);
        $this->assertDatabaseHas('product_variants', ['sku' => 'ANI-AUR-001-DIA']);
        $this->assertDatabaseHas('product_variants', ['sku' => 'ANI-AUR-001-PLATA']);
        $this->assertDatabaseHas('product_variants', ['sku' => 'ANI-AUR-001-18K']);
        $this->assertDatabaseHas('product_variants', ['sku' => 'ANI-AUR-001-ROSA']);
    }

    public function test_variant_without_size_and_without_differentiating_attributes_is_rejected(): void
    {
        $admin = User::factory()->create();
        [$product] = $this->createProductWithSize();

        $this->actingAs($admin)
            ->post(route('products.variants.store', $product), ['status' => 'active'])
            ->assertSessionHasErrors('material_id');

        $this->assertDatabaseCount('product_variants', 0);
    }

    public function test_duplicate_combinations_with_null_size_are_rejected(): void
    {
        $admin = User::factory()->create();
        [$product] = $this->createProductWithSize();
        $material = $this->createMaterial();
        ProductVariant::create($this->variantPayload($product, null, [
            'size_id' => null,
            'material_id' => $material->id,
        ]));

        $this->actingAs($admin)
            ->post(route('products.variants.store', $product), [
                'material_id' => $material->id,
                'status' => 'active',
            ])
            ->assertSessionHasErrors('size_id');

        $variant = ProductVariant::whereBelongsTo($product)->firstOrFail();

        $this->assertDatabaseHas('product_variants', [
            'id' => $variant->id,
            'size_id' => null,
            'material_id' => $material->id,
        ]);
        $this->assertSame(1, ProductVariant::whereBelongsTo($product)->count());
    }

    public function test_duplicate_attribute_combinations_are_rejected(): void
    {
        $admin = User::factory()->create();
        [$product, $size] = $this->createProductWithSize();
        $material = $this->createMaterial();
        $payload = [
            'size_id' => $size->id,
            'material_id' => $material->id,
            'karat' => 14,
            'metal_color' => 'Amarillo',
            'status' => 'active',
        ];
        ProductVariant::create($this->variantPayload($product, $size, [
            'material_id' => $material->id,
            'karat' => 14,
            'metal_color' => 'Amarillo',
        ]));

        $this->actingAs($admin)
            ->post(route('products.variants.store', $product), $payload)
            ->assertSessionHasErrors('size_id');

        $this->assertSame(1, ProductVariant::whereBelongsTo($product)->count());
    }

    public function test_inactive_or_deleted_material_and_stone_are_rejected(): void
    {
        $admin = User::factory()->create();
        [$product, $size] = $this->createProductWithSize();
        $inactiveMaterial = $this->createMaterial(['status' => false]);
        $deletedStone = $this->createStone();
        $deletedStone->delete();

        $this->actingAs($admin)
            ->post(route('products.variants.store', $product), [
                'size_id' => $size->id,
                'material_id' => $inactiveMaterial->id,
                'stone_id' => $deletedStone->id,
                'status' => 'active',
            ])
            ->assertSessionHasErrors(['material_id', 'stone_id']);
    }

    public function test_size_must_be_applicable_to_the_product_category(): void
    {
        $admin = User::factory()->create();
        [$product, $size] = $this->createProductWithSize();
        $otherCategory = $this->createCategory('Pulseras');
        $otherSize = $this->createSize($otherCategory, '18');

        $this->assertNotSame($size->id, $otherSize->id);

        $this->actingAs($admin)
            ->post(route('products.variants.store', $product), [
                'size_id' => $otherSize->id,
                'status' => 'active',
            ])
            ->assertSessionHasErrors('size_id');
    }

    public function test_update_changes_variant_attributes_and_rebuilds_sku(): void
    {
        $admin = User::factory()->create();
        [$product, $size] = $this->createProductWithSize([
            'base_sku' => 'ANI-AUR-001',
            'code' => 'ANI-AUR-001',
        ]);
        $newSize = $this->createSize($product->category, '8');
        $variant = ProductVariant::create($this->variantPayload($product, $size));

        $this->actingAs($admin)
            ->patch(route('products.variants.update', [$product, $variant]), [
                'size_id' => $newSize->id,
                'karat' => 18,
                'metal_color' => 'Blanco',
                'price' => '1600.00',
                'status' => 'inactive',
            ])
            ->assertRedirect(route('products.variants.index', $product));

        $variant->refresh();
        $this->assertSame('ANI-AUR-001-T8-18K-BLANCO', $variant->sku);
        $this->assertSame('1600.00', $variant->price);
        $this->assertSame('inactive', $variant->status);
    }

    public function test_variant_routes_are_scoped_to_the_parent_product_and_destroy_is_soft_delete(): void
    {
        $admin = User::factory()->create();
        [$firstProduct, $firstSize] = $this->createProductWithSize();
        [$secondProduct, $secondSize] = $this->createProductWithSize([
            'code' => 'PRD-002',
            'base_sku' => 'SKU-002',
            'slug' => 'producto-2',
        ]);
        $variant = ProductVariant::create($this->variantPayload($secondProduct, $secondSize));

        $this->actingAs($admin)
            ->get(route('products.variants.edit', [$firstProduct, $variant]))
            ->assertNotFound();

        $this->actingAs($admin)
            ->delete(route('products.variants.destroy', [$secondProduct, $variant]))
            ->assertRedirect(route('products.variants.index', $secondProduct))
            ->assertSessionHas('status', 'Variante eliminada correctamente.');

        $this->assertSoftDeleted('product_variants', ['id' => $variant->id]);
        $this->assertNotNull(ProductVariant::withTrashed()->find($variant->id));
        $this->assertNotNull($firstSize->fresh());
    }

    public function test_variant_with_stock_exposes_catalog_removal_warning_and_preserves_inventory(): void
    {
        $admin = User::factory()->create();
        [$product, $size] = $this->createProductWithSize();
        $variant = ProductVariant::create($this->variantPayload($product, $size, [
            'sku' => 'STOCK-VARIANT',
        ]));
        $stock = InventoryStock::create([
            'product_variant_id' => $variant->id,
            'current_stock' => 6,
            'minimum_stock' => 0,
        ]);
        $movement = InventoryMovement::create([
            'inventory_stock_id' => $stock->id,
            'type' => 'Entrada',
            'quantity' => 6,
            'previous_stock' => 0,
            'resulting_stock' => 6,
            'reason' => 'Carga inicial',
            'movement_at' => now(),
        ]);

        $this->actingAs($admin)
            ->get(route('products.variants.index', $product))
            ->assertSee('data-delete-stock="6"', false);

        DB::table('inventory_stocks')
            ->where('id', $stock->id)
            ->update(['current_stock' => 4]);

        $this->actingAs($admin)
            ->delete(route('products.variants.destroy', [$product, $variant]))
            ->assertRedirect(route('products.variants.index', $product))
            ->assertSessionHas('status', 'Variante retirada del catálogo. Se conservaron 4 existencias físicas.');

        $this->assertSoftDeleted('product_variants', ['id' => $variant->id]);
        $this->assertDatabaseHas('inventory_stocks', [
            'id' => $stock->id,
            'current_stock' => 4,
        ]);
        $this->assertDatabaseHas('inventory_movements', [
            'id' => $movement->id,
            'inventory_stock_id' => $stock->id,
        ]);
        $this->assertSame(1, InventoryMovement::where('inventory_stock_id', $stock->id)->count());

        $this->actingAs($admin)
            ->get(route('inventory.stock'))
            ->assertViewHas('archivedInventoryRows', function ($rows) use ($stock): bool {
                return $rows->contains('inventory_stock_id', $stock->id)
                    && $rows->firstWhere('inventory_stock_id', $stock->id)['current_stock'] === 4;
            });
    }

    public function test_deleted_variants_are_listed_only_for_the_product_and_show_physical_stock(): void
    {
        $admin = User::factory()->create();
        [$product, $size] = $this->createProductWithSize();
        $activeVariant = ProductVariant::create($this->variantPayload($product, $size, [
            'sku' => 'ACTIVE-VARIANT',
        ]));
        $deletedVariant = ProductVariant::create($this->variantPayload($product, $size, [
            'sku' => 'DELETED-VARIANT',
        ]));
        $stock = InventoryStock::create([
            'product_variant_id' => $deletedVariant->id,
            'current_stock' => 8,
            'minimum_stock' => 0,
        ]);
        $movement = InventoryMovement::create([
            'inventory_stock_id' => $stock->id,
            'type' => 'Entrada',
            'quantity' => 8,
            'previous_stock' => 0,
            'resulting_stock' => 8,
            'reason' => 'Carga inicial',
            'movement_at' => now(),
        ]);
        $deletedVariant->delete();

        $response = $this->actingAs($admin)->get(route('products.variants.deleted', $product));

        $response
            ->assertOk()
            ->assertViewIs('products.variants.deleted')
            ->assertSee('DELETED-VARIANT')
            ->assertSee('Con existencias')
            ->assertSee('Restaurar')
            ->assertViewHas('productVariants', function ($variants) use ($deletedVariant, $activeVariant): bool {
                return $variants->contains('id', $deletedVariant->id)
                    && ! $variants->contains('id', $activeVariant->id);
            });

        $this->actingAs($admin)
            ->get(route('products.variants.index', $product))
            ->assertSee('Variantes eliminadas')
            ->assertSee('href="'.route('products.variants.deleted', $product).'"', false);

        $this->assertDatabaseHas('inventory_stocks', ['id' => $stock->id, 'current_stock' => 8]);
        $this->assertDatabaseHas('inventory_movements', ['id' => $movement->id]);
    }

    public function test_restoring_a_variant_preserves_stock_history_and_returns_it_to_active_inventory(): void
    {
        $admin = User::factory()->create();
        [$product, $size] = $this->createProductWithSize();
        $variant = ProductVariant::create($this->variantPayload($product, $size, [
            'sku' => 'RESTORE-VARIANT',
        ]));
        $stock = InventoryStock::create([
            'product_variant_id' => $variant->id,
            'current_stock' => 10,
            'minimum_stock' => 0,
        ]);
        $movement = InventoryMovement::create([
            'inventory_stock_id' => $stock->id,
            'type' => 'Entrada',
            'quantity' => 10,
            'previous_stock' => 0,
            'resulting_stock' => 10,
            'reason' => 'Carga inicial',
            'movement_at' => now(),
        ]);
        $variant->delete();

        $this->actingAs($admin)
            ->patch(route('products.variants.restore', [$product, $variant]))
            ->assertRedirect(route('products.variants.index', $product))
            ->assertSessionHas('status', 'Variante restaurada correctamente.');

        $this->assertNotNull(ProductVariant::find($variant->id));
        $this->assertSame('RESTORE-VARIANT', $variant->fresh()->sku);
        $this->assertDatabaseHas('inventory_stocks', ['id' => $stock->id, 'current_stock' => 10]);
        $this->assertDatabaseHas('inventory_movements', ['id' => $movement->id]);
        $this->assertSame(1, InventoryMovement::where('inventory_stock_id', $stock->id)->count());

        $this->actingAs($admin)
            ->get(route('products.variants.index', $product))
            ->assertSee('RESTORE-VARIANT');
        $this->actingAs($admin)
            ->get(route('inventory.stock'))
            ->assertViewHas('inventoryRows', function ($rows) use ($stock): bool {
                return $rows->contains('inventory_stock_id', $stock->id)
                    && ! $rows->contains('is_out_of_catalog', true);
            })
            ->assertViewHas('archivedInventoryRows', function ($rows) use ($stock): bool {
                return ! $rows->contains('inventory_stock_id', $stock->id);
            });
    }

    public function test_restoring_a_variant_is_blocked_when_the_parent_product_is_deleted(): void
    {
        $admin = User::factory()->create();
        [$product, $size] = $this->createProductWithSize();
        $variant = ProductVariant::create($this->variantPayload($product, $size, [
            'sku' => 'PARENT-DELETED-VARIANT',
        ]));
        $variant->delete();
        $product->delete();

        $this->actingAs($admin)
            ->patch(route('products.variants.restore', [$product, $variant]))
            ->assertRedirect(route('products.index'))
            ->assertSessionHas('error', 'No se puede restaurar la variante porque su producto está eliminado.');

        $this->assertSoftDeleted('product_variants', ['id' => $variant->id]);
    }

    public function test_restoring_a_variant_is_blocked_when_an_active_variant_uses_the_same_sku(): void
    {
        $admin = User::factory()->create();
        [$product, $size] = $this->createProductWithSize();
        $deletedVariant = ProductVariant::create($this->variantPayload($product, $size, [
            'sku' => 'CONFLICT-SKU',
        ]));
        $deletedVariant->delete();

        Schema::table('product_variants', function (Blueprint $table): void {
            $table->dropUnique('product_variants_sku_unique');
        });

        $activeVariant = null;

        try {
            $activeVariant = ProductVariant::create($this->variantPayload($product, $size, [
                'sku' => 'CONFLICT-SKU',
            ]));

            $this->actingAs($admin)
                ->patch(route('products.variants.restore', [$product, $deletedVariant]))
                ->assertRedirect(route('products.variants.deleted', $product))
                ->assertSessionHas('error', 'No se puede restaurar la variante porque su SKU ya está en uso.');

            $this->assertSoftDeleted('product_variants', ['id' => $deletedVariant->id]);
        } finally {
            if ($activeVariant !== null) {
                DB::table('product_variants')
                    ->where('id', $activeVariant->id)
                    ->update(['sku' => 'CONFLICT-CLEANUP-'.$activeVariant->id]);
            }

            Schema::table('product_variants', function (Blueprint $table): void {
                $table->unique('sku');
            });
        }
    }

    public function test_restoring_a_variant_is_blocked_when_an_active_variant_uses_the_same_combination(): void
    {
        $admin = User::factory()->create();
        [$product, $size] = $this->createProductWithSize();
        $deletedVariant = ProductVariant::create($this->variantPayload($product, $size, [
            'sku' => 'COMBINATION-DELETED',
        ]));
        $deletedVariant->delete();

        ProductVariant::create($this->variantPayload($product, $size, [
            'sku' => 'COMBINATION-ACTIVE',
        ]));

        $this->actingAs($admin)
            ->patch(route('products.variants.restore', [$product, $deletedVariant]))
            ->assertRedirect(route('products.variants.deleted', $product))
            ->assertSessionHas('error', 'No se puede restaurar esta variante porque ya existe una variante activa con las mismas características.');

        $this->assertSoftDeleted('product_variants', ['id' => $deletedVariant->id]);
    }

    public function test_technical_users_cannot_view_or_restore_deleted_variants(): void
    {
        $technical = User::factory()->create(['role' => 'technical']);
        [$product, $size] = $this->createProductWithSize();
        $variant = ProductVariant::create($this->variantPayload($product, $size, [
            'sku' => 'TECHNICAL-DELETED-VARIANT',
        ]));
        $variant->delete();
        $activeVariant = ProductVariant::create($this->variantPayload($product, $size, [
            'sku' => 'TECHNICAL-ACTIVE-VARIANT',
        ]));

        $this->actingAs($technical)
            ->get(route('products.variants.deleted', $product))
            ->assertForbidden();
        $this->actingAs($technical)
            ->get(route('products.variants.index', $product))
            ->assertForbidden();
        $this->actingAs($technical)
            ->get(route('products.variants.create', $product))
            ->assertForbidden();
        $this->actingAs($technical)
            ->get(route('products.variants.edit', [$product, $activeVariant]))
            ->assertForbidden();
        $this->actingAs($technical)
            ->post(route('products.variants.store', $product), [])
            ->assertForbidden();
        $this->actingAs($technical)
            ->patch(route('products.variants.update', [$product, $activeVariant]), [])
            ->assertForbidden();
        $this->actingAs($technical)
            ->delete(route('products.variants.destroy', [$product, $activeVariant]))
            ->assertForbidden();
        $this->actingAs($technical)
            ->patch(route('products.variants.restore', [$product, $variant]))
            ->assertForbidden();
        $this->assertSoftDeleted('product_variants', ['id' => $variant->id]);
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array{0: Product, 1: Size}
     */
    private function createProductWithSize(array $overrides = []): array
    {
        $category = $this->createCategory();
        $size = $this->createSize($category);
        $product = Product::create(array_merge([
            'category_id' => $category->id,
            'name' => 'Anillo Aurora',
            'model' => 'Modelo Aurora',
            'code' => 'PRD-001',
            'base_sku' => 'ANI-AUR-001',
            'slug' => 'anillo-aurora',
            'price' => '1250.00',
            'status' => 'active',
        ], $overrides));

        return [$product, $size];
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function variantPayload(Product $product, ?Size $size, array $overrides = []): array
    {
        return array_merge([
            'product_id' => $product->id,
            'size_id' => $size?->id,
            'sku' => 'VAR-'.Str::upper(Str::random(8)),
            'status' => 'active',
        ], $overrides);
    }

    private function createCategory(string $name = 'Anillos'): Category
    {
        return Category::create([
            'name' => $name,
            'slug' => Str::slug($name).'-'.Str::lower(Str::random(5)),
            'status' => true,
            'sort_order' => 1,
        ]);
    }

    private function createSize(Category $category, string $name = '7'): Size
    {
        $size = Size::create([
            'name' => $name,
            'sort_order' => 1,
            'status' => true,
        ]);
        $size->categories()->attach($category);

        return $size;
    }

    private function createMaterial(array $overrides = []): Material
    {
        return Material::create(array_merge([
            'name' => 'Oro',
            'slug' => 'oro-'.Str::lower(Str::random(5)),
            'status' => true,
        ], $overrides));
    }

    private function createStone(array $overrides = []): Stone
    {
        return Stone::create(array_merge([
            'name' => 'Diamante',
            'slug' => 'diamante-'.Str::lower(Str::random(5)),
            'status' => true,
        ], $overrides));
    }
}
