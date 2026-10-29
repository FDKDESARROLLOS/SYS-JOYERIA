<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\InventoryStock;
use App\Models\Product;
use App\Models\ProductImage;
use App\Models\ProductStone;
use App\Models\ProductVariant;
use App\Models\Stone;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Tests\TestCase;

class PublicCatalogApiTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @var list<string>
     */
    private array $testImagePaths = [];

    protected function tearDown(): void
    {
        foreach ($this->testImagePaths as $path) {
            File::delete(public_path('uploads/products/'.$path));
        }

        parent::tearDown();
    }

    public function test_product_index_is_paginated_and_returns_public_catalog_data(): void
    {
        $product = $this->createProduct([
            'name' => 'Anillo Público',
            'slug' => 'anillo-publico',
            'status' => 'active',
        ], [
            'current_stock' => 4,
        ]);
        $image = $this->createImage($product, 'cover.jpg', 2, true);
        $this->createImage($product, 'secondary.jpg', 1);

        $response = $this->getJson('/api/v1/products', [
            'Origin' => 'http://localhost:5173',
        ]);

        $response
            ->assertOk()
            ->assertHeader('Access-Control-Allow-Origin', 'http://localhost:5173')
            ->assertJsonStructure([
                'data' => [[
                    'slug',
                    'name',
                    'subtitle',
                    'short_description',
                    'category' => ['name', 'slug'],
                    'image' => ['url', 'alt', 'is_cover', 'sort_order'],
                    'price' => ['from', 'to', 'currency'],
                    'availability' => ['available', 'status'],
                ]],
                'links',
                'meta',
            ])
            ->assertJsonPath('data.0.slug', $product->slug)
            ->assertJsonPath('data.0.image.url', $image->publicUrl())
            ->assertJsonPath('data.0.availability.status', 'in_stock');
    }

    public function test_only_active_products_with_active_categories_and_sellable_variants_are_published(): void
    {
        $active = $this->createProduct(['slug' => 'visible', 'status' => 'active']);
        $this->createProduct(['slug' => 'draft', 'status' => 'draft']);
        $this->createProduct(['slug' => 'inactive', 'status' => 'inactive']);
        $deleted = $this->createProduct(['slug' => 'deleted', 'status' => 'active']);
        $deleted->delete();
        $this->createProduct(
            ['slug' => 'inactive-category', 'status' => 'active'],
            [],
            ['status' => false],
        );
        $this->createProduct(
            ['slug' => 'no-valid-variant', 'status' => 'active'],
            [],
            [],
            ['status' => 'draft'],
        );

        $response = $this->getJson('/api/v1/products');

        $response
            ->assertOk()
            ->assertJsonPath('data.0.slug', $active->slug)
            ->assertJsonCount(1, 'data');
    }

    public function test_detail_returns_only_active_variants_with_inventory(): void
    {
        $product = $this->createProduct(['slug' => 'variantes-publicas']);
        $this->createVariant($product, ['sku' => 'ACTIVE-SKU', 'status' => 'active'], ['current_stock' => 2]);
        $this->createVariant($product, ['sku' => 'DRAFT-SKU', 'status' => 'draft'], ['current_stock' => 2]);
        $this->createVariant($product, ['sku' => 'INACTIVE-SKU', 'status' => 'inactive'], ['current_stock' => 2]);
        $deleted = $this->createVariant($product, ['sku' => 'DELETED-SKU', 'status' => 'active'], ['current_stock' => 2]);
        $deleted->delete();
        $withoutStock = $this->createVariant($product, ['sku' => 'WITHOUT-STOCK', 'status' => 'active']);
        $withoutStock->inventoryStock()->delete();

        $response = $this->getJson('/api/v1/products/'.$product->slug);

        $response
            ->assertOk()
            ->assertJsonCount(2, 'data.variants')
            ->assertJsonMissing(['sku' => 'DRAFT-SKU'])
            ->assertJsonMissing(['sku' => 'INACTIVE-SKU'])
            ->assertJsonMissing(['sku' => 'DELETED-SKU'])
            ->assertJsonMissing(['sku' => $withoutStock->sku]);
    }

    public function test_exhausted_products_are_published_and_reserved_stock_reduces_availability(): void
    {
        $product = $this->createProduct(
            ['slug' => 'agotado', 'status' => 'active'],
            ['current_stock' => 5, 'reserved_stock' => 5],
        );

        $response = $this->getJson('/api/v1/products/'.$product->slug);

        $response
            ->assertOk()
            ->assertJsonPath('data.variants.0.available_stock', 0)
            ->assertJsonPath('data.variants.0.availability.available', false)
            ->assertJsonPath('data.variants.0.availability.status', 'out_of_stock');
    }

    public function test_detail_uses_slug_and_returns_not_found_for_missing_or_unpublishable_products(): void
    {
        $publicProduct = $this->createProduct(['slug' => 'slug-publico']);
        $draftProduct = $this->createProduct(['slug' => 'slug-borrador', 'status' => 'draft']);

        $this->getJson('/api/v1/products/'.$publicProduct->slug)
            ->assertOk()
            ->assertJsonPath('data.slug', 'slug-publico');

        $this->getJson('/api/v1/products/does-not-exist')->assertNotFound();
        $this->getJson('/api/v1/products/'.$draftProduct->slug)->assertNotFound();
    }

    public function test_effective_prices_and_attributes_are_exposed_without_internal_fields(): void
    {
        $product = $this->createProduct([
            'slug' => 'precio-heredado',
            'price' => '1000.00',
            'sale_price' => '800.00',
            'karat' => 14,
            'metal_color' => 'Amarillo',
        ]);
        $variant = $this->createVariant($product, [
            'sku' => 'PRICE-OVERRIDE',
            'price' => '1500.00',
            'sale_price' => '1200.00',
            'karat' => null,
            'metal_color' => null,
        ], ['current_stock' => 1]);
        $inherited = $this->createVariant($product, [
            'sku' => 'PRICE-INHERITED',
            'price' => null,
            'sale_price' => null,
        ], ['current_stock' => 1]);

        $response = $this->getJson('/api/v1/products/'.$product->slug);

        $response->assertOk();
        $variants = $response->json('data.variants');
        $override = collect($variants)->firstWhere('sku', $variant->sku);
        $inheritedData = collect($variants)->firstWhere('sku', $inherited->sku);

        $this->assertSame('1500.00', $override['price']['amount']);
        $this->assertSame('1200.00', $override['sale_price']['amount']);
        $this->assertSame('1000.00', $inheritedData['price']['amount']);
        $this->assertSame('800.00', $inheritedData['sale_price']['amount']);
        $response
            ->assertJsonMissingPath('data.variants.0.cost')
            ->assertJsonMissingPath('data.variants.0.current_stock')
            ->assertJsonMissingPath('data.variants.0.reserved_stock')
            ->assertJsonMissingPath('data.variants.0.minimum_stock')
            ->assertJsonMissingPath('data.deleted_at');
    }

    public function test_zero_sale_price_is_not_published_as_an_offer(): void
    {
        $product = $this->createProduct([
            'slug' => 'oferta-cero',
            'price' => '5000.00',
        ]);
        DB::table('products')->where('id', $product->id)->update(['sale_price' => '0.00']);
        DB::table('product_variants')->where('product_id', $product->id)->update(['sale_price' => '0.00']);

        $response = $this->getJson('/api/v1/products/'.$product->slug);

        $response
            ->assertOk()
            ->assertJsonPath('data.sale_price', null)
            ->assertJsonPath('data.variants.0.sale_price', null);
    }

    public function test_valid_sale_price_is_published_and_normal_price_range_is_preserved(): void
    {
        $product = $this->createProduct([
            'slug' => 'rango-precios',
            'price' => '5000.00',
            'sale_price' => null,
        ], [], [], [
            'price' => '5000.00',
            'sale_price' => null,
        ]);
        $this->createVariant($product, [
            'sku' => 'RANGO-6000',
            'price' => '6000.00',
            'sale_price' => null,
        ], ['current_stock' => 1]);
        $this->createVariant($product, [
            'sku' => 'RANGO-7000',
            'price' => '7000.00',
            'sale_price' => '6500.00',
        ], ['current_stock' => 1]);

        $response = $this->getJson('/api/v1/products/'.$product->slug);

        $response
            ->assertOk()
            ->assertJsonPath('data.price.from', '5000.00')
            ->assertJsonPath('data.price.to', '7000.00')
            ->assertJsonPath('data.sale_price.from', '6500.00')
            ->assertJsonPath('data.sale_price.to', '6500.00');
    }

    public function test_detail_exposes_public_central_and_lateral_stone_composition(): void
    {
        $product = $this->createProduct(['slug' => 'piedras-publicas']);
        $central = Stone::create(['name' => 'Zirconia', 'slug' => 'zirconia', 'status' => true]);
        $lateral = Stone::create(['name' => 'Diamante', 'slug' => 'diamante', 'status' => true]);
        ProductStone::create([
            'product_id' => $product->id,
            'stone_id' => $central->id,
            'position' => 'central',
            'shape' => 'Redondo',
            'carats' => '1.00',
            'origin' => 'Laboratorio',
            'certification' => 'IGF',
            'color' => 'D',
            'clarity' => 'VS1',
            'cut' => 'Bueno',
        ]);
        ProductStone::create([
            'product_id' => $product->id,
            'stone_id' => $lateral->id,
            'position' => 'lateral',
            'shape' => 'Cuadrada',
            'carats' => '0.25',
            'quantity' => 4,
            'stones_per_side' => 2,
        ]);

        $response = $this->getJson('/api/v1/products/'.$product->slug);

        $response
            ->assertOk()
            ->assertJsonPath('data.stones.center.name', 'Zirconia')
            ->assertJsonPath('data.stones.center.carats_per_stone', '1.00')
            ->assertJsonPath('data.stones.center.stones_per_side', null)
            ->assertJsonPath('data.stones.center.certification', 'IGF')
            ->assertJsonPath('data.stones.side.0.name', 'Diamante')
            ->assertJsonPath('data.stones.side.0.carats_per_stone', '0.25')
            ->assertJsonPath('data.stones.side.0.quantity', 4)
            ->assertJsonPath('data.stones.side.0.stones_per_side', 2)
            ->assertJsonMissingPath('data.stones.center.id')
            ->assertJsonMissingPath('data.stones.side.product_id')
            ->assertJsonMissingPath('data.stones.center.created_at');
    }

    public function test_variant_commercial_stone_does_not_fallback_to_product_stone_composition(): void
    {
        $product = $this->createProduct(['slug' => 'piedra-comercial-separada']);
        $physicalStone = Stone::create([
            'name' => 'Zirconia física',
            'slug' => 'zirconia-fisica',
            'status' => true,
        ]);
        $commercialStone = Stone::create([
            'name' => 'Diamante comercial',
            'slug' => 'diamante-comercial',
            'status' => true,
        ]);
        ProductStone::create([
            'product_id' => $product->id,
            'stone_id' => $physicalStone->id,
            'position' => 'central',
        ]);
        $unassignedVariant = $product->variants()->firstOrFail();
        $assignedVariant = $this->createVariant($product, [
            'sku' => 'COMERCIAL-ASIGNADA',
            'stone_id' => $commercialStone->id,
        ]);

        $response = $this->getJson('/api/v1/products/'.$product->slug);
        $variants = collect($response->json('data.variants'))
            ->keyBy('sku');

        $this->assertNull($variants->get($unassignedVariant->sku)['attributes']['stone']);
        $this->assertSame(
            'Diamante comercial',
            $variants->get($assignedVariant->sku)['attributes']['stone'],
        );
        $this->assertNotSame(
            'Zirconia física',
            $variants->get($assignedVariant->sku)['attributes']['stone'],
        );
    }

    public function test_detail_represents_missing_lateral_stones_without_placeholders(): void
    {
        $product = $this->createProduct(['slug' => 'sin-piedras-laterales']);
        $stone = Stone::create(['name' => 'Zirconia', 'slug' => 'zirconia', 'status' => true]);
        ProductStone::create([
            'product_id' => $product->id,
            'stone_id' => $stone->id,
            'position' => 'central',
        ]);

        $response = $this->getJson('/api/v1/products/'.$product->slug);

        $response
            ->assertOk()
            ->assertJsonPath('data.stones.side', [])
            ->assertJsonMissing(['Ej. Laboratorio', 'Ej. IGI', 'Ej. G']);
    }

    public function test_detail_exposes_all_lateral_stone_groups_in_sort_order(): void
    {
        $product = $this->createProduct(['slug' => 'varias-piedras-laterales']);
        $diamond = Stone::create(['name' => 'Diamante', 'slug' => 'diamante', 'status' => true]);
        $sapphire = Stone::create(['name' => 'Zafiro', 'slug' => 'zafiro', 'status' => true]);
        ProductStone::create([
            'product_id' => $product->id,
            'stone_id' => $sapphire->id,
            'position' => 'lateral',
            'sort_order' => 2,
            'carats' => '0.15',
            'quantity' => 2,
            'stones_per_side' => 1,
        ]);
        ProductStone::create([
            'product_id' => $product->id,
            'stone_id' => $diamond->id,
            'position' => 'lateral',
            'sort_order' => 1,
            'carats' => '0.20',
            'quantity' => 2,
            'stones_per_side' => 1,
        ]);

        $this->getJson('/api/v1/products/'.$product->slug)
            ->assertOk()
            ->assertJsonPath('data.stones.side.0.name', 'Diamante')
            ->assertJsonPath('data.stones.side.0.carats_per_stone', '0.20')
            ->assertJsonPath('data.stones.side.0.stones_per_side', 1)
            ->assertJsonPath('data.stones.side.1.name', 'Zafiro')
            ->assertJsonPath('data.stones.side.1.carats_per_stone', '0.15')
            ->assertJsonPath('data.stones.side.1.stones_per_side', 1);
    }

    public function test_images_use_public_urls_and_the_cover_sort_order(): void
    {
        $product = $this->createProduct(['slug' => 'imagenes-ordenadas']);
        $secondary = $this->createImage($product, 'secondary-api.jpg', 1);
        $cover = $this->createImage($product, 'cover-api.jpg', 4, true);
        ProductImage::create([
            'product_id' => $product->id,
            'path' => 'missing-api.jpg',
            'sort_order' => 0,
        ]);

        $response = $this->getJson('/api/v1/products/'.$product->slug);

        $response
            ->assertOk()
            ->assertJsonPath('data.images.0.url', $cover->publicUrl())
            ->assertJsonPath('data.images.1.url', $secondary->publicUrl())
            ->assertJsonMissing(['url' => asset('uploads/products/missing-api.jpg')])
            ->assertJsonMissingPath('data.images.0.path');
    }

    public function test_cors_allows_the_configured_public_frontend_without_credentials(): void
    {
        config(['cors.allowed_origins' => ['http://localhost:5173']]);

        $response = $this->options('/api/v1/products', [
            'Origin' => 'http://localhost:5173',
            'Access-Control-Request-Method' => 'GET',
        ]);

        $response
            ->assertOk()
            ->assertHeader('Access-Control-Allow-Origin', 'http://localhost:5173')
            ->assertHeaderMissing('Access-Control-Allow-Credentials');
    }

    public function test_catalog_queries_do_not_lazy_load_relations_for_multiple_products(): void
    {
        $this->createProduct(['slug' => 'n-plus-one-a']);
        $this->createProduct(['slug' => 'n-plus-one-b']);
        DB::enableQueryLog();

        $this->getJson('/api/v1/products');

        $this->assertLessThan(12, count(DB::getQueryLog()));
    }

    /**
     * @param  array<string, mixed>  $productOverrides
     * @param  array<string, mixed>  $stockOverrides
     * @param  array<string, mixed>  $categoryOverrides
     * @param  array<string, mixed>  $variantOverrides
     */
    private function createProduct(
        array $productOverrides = [],
        array $stockOverrides = [],
        array $categoryOverrides = [],
        array $variantOverrides = [],
    ): Product {
        $suffix = Str::lower(Str::random(8));
        $category = Category::create(array_merge([
            'name' => 'Categoría '.$suffix,
            'slug' => 'categoria-'.$suffix,
            'status' => true,
            'sort_order' => 1,
        ], $categoryOverrides));
        $product = Product::create(array_merge([
            'category_id' => $category->id,
            'name' => 'Producto '.$suffix,
            'model' => 'Modelo '.$suffix,
            'code' => 'CODE-'.$suffix,
            'base_sku' => 'SKU-'.$suffix,
            'slug' => 'producto-'.$suffix,
            'subtitle' => 'Subtítulo',
            'short_description' => 'Descripción comercial.',
            'price' => '1000.00',
            'sale_price' => null,
            'karat' => 14,
            'metal_color' => 'Amarillo',
            'status' => 'active',
        ], $productOverrides));
        $this->createVariant($product, array_merge([
            'sku' => 'VAR-'.$suffix,
            'status' => 'active',
        ], $variantOverrides), $stockOverrides);

        return $product;
    }

    /**
     * @param  array<string, mixed>  $variantOverrides
     * @param  array<string, mixed>  $stockOverrides
     */
    private function createVariant(
        Product $product,
        array $variantOverrides = [],
        array $stockOverrides = [],
    ): ProductVariant {
        $variant = ProductVariant::create(array_merge([
            'product_id' => $product->id,
            'sku' => 'VAR-'.Str::lower(Str::random(8)),
            'status' => 'active',
        ], $variantOverrides));

        if ($stockOverrides !== [] || ($variantOverrides['status'] ?? 'active') === 'active') {
            InventoryStock::create(array_merge([
                'product_variant_id' => $variant->id,
                'current_stock' => 1,
                'reserved_stock' => 0,
            ], $stockOverrides));
        }

        return $variant;
    }

    private function createImage(Product $product, string $filename, int $sortOrder, bool $isCover = false): ProductImage
    {
        $this->testImagePaths[] = $filename;
        File::ensureDirectoryExists(public_path('uploads/products'));
        File::put(public_path('uploads/products/'.$filename), 'image');

        return ProductImage::create([
            'product_id' => $product->id,
            'path' => $filename,
            'alt' => 'Imagen pública',
            'sort_order' => $sortOrder,
            'is_cover' => $isCover,
        ]);
    }
}
