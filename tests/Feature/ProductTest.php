<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Collection;
use App\Models\InventoryStock;
use App\Models\Material;
use App\Models\Product;
use App\Models\ProductImage;
use App\Models\ProductStone;
use App\Models\ProductVariant;
use App\Models\Size;
use App\Models\Stone;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\File\UploadedFile as SymfonyUploadedFile;
use Tests\TestCase;

class ProductTest extends TestCase
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

    public function test_product_tests_do_not_remove_preexisting_catalog_files(): void
    {
        $admin = User::factory()->create();
        $category = $this->createCategory();
        $catalogDirectory = public_path('uploads/products');
        $before = collect(File::files($catalogDirectory))
            ->mapWithKeys(fn ($file): array => [$file->getFilename() => $file->getSize()])
            ->all();

        $this->actingAs($admin)
            ->post(route('products.store'), $this->productPayload($category, null, null, null, [
                'images' => [UploadedFile::fake()->image('isolated.jpg')],
            ]))
            ->assertRedirect(route('products.index'));

        $after = collect(File::files($catalogDirectory))
            ->mapWithKeys(fn ($file): array => [$file->getFilename() => $file->getSize()])
            ->all();

        $this->assertSame($before, $after);
    }

    public function test_authenticated_user_can_view_products_index_with_eager_loaded_relations(): void
    {
        $admin = User::factory()->create();
        $category = $this->createCategory();
        $collection = $this->createCollection();
        $material = $this->createMaterial();
        $size = $this->createSize($category);
        $product = Product::create($this->productPayload($category, $collection, $material, $size));

        $response = $this->actingAs($admin)->get(route('products.index'));

        $response
            ->assertOk()
            ->assertViewIs('products.index')
            ->assertViewHas('products', function ($products) use ($product): bool {
                $listedProduct = $products->firstWhere('id', $product->id);

                return $listedProduct !== null
                    && $listedProduct->relationLoaded('category')
                    && $listedProduct->relationLoaded('collection')
                    && $listedProduct->relationLoaded('material')
                    && $listedProduct->relationLoaded('baseSize')
                    && $listedProduct->relationLoaded('images');
            });
    }

    public function test_product_index_uses_cover_image_then_sort_order_and_exposes_variant_action(): void
    {
        $admin = User::factory()->create();
        $category = $this->createCategory();
        $product = Product::create($this->productPayload($category));
        $firstBySort = $this->createProductImage($product, 0, ['path' => 'first.jpg']);
        $cover = $this->createProductImage($product, 4, ['path' => 'cover.jpg', 'is_cover' => true]);
        File::ensureDirectoryExists($this->testUploadDirectory);
        File::put($this->testUploadDirectory.'/first.jpg', 'image');
        File::put($this->testUploadDirectory.'/cover.jpg', 'image');

        $response = $this->actingAs($admin)->get(route('products.index'));

        $response
            ->assertOk()
            ->assertSee($cover->publicUrl())
            ->assertSee(route('products.variants.index', $product))
            ->assertViewHas('products', function ($products) use ($product, $cover, $firstBySort): bool {
                $listedProduct = $products->firstWhere('id', $product->id);

                return $listedProduct->images->first()->id === $cover->id
                    && $listedProduct->images->last()->id === $firstBySort->id;
            });
    }

    public function test_product_index_uses_sort_order_fallback_and_handles_products_without_images(): void
    {
        $admin = User::factory()->create();
        $category = $this->createCategory();
        $withImages = Product::create($this->productPayload($category));
        $withoutImages = Product::create($this->productPayload($category, null, null, null, [
            'code' => 'PRD-002',
            'base_sku' => 'PRD-002',
            'slug' => 'producto-sin-imagen',
        ]));
        $first = $this->createProductImage($withImages, 1, ['path' => 'ordered-first.jpg']);
        $second = $this->createProductImage($withImages, 3, ['path' => 'ordered-second.jpg']);
        File::ensureDirectoryExists($this->testUploadDirectory);
        File::put($this->testUploadDirectory.'/ordered-first.jpg', 'image');
        File::put($this->testUploadDirectory.'/ordered-second.jpg', 'image');

        $response = $this->actingAs($admin)->get(route('products.index'));

        $response
            ->assertOk()
            ->assertSee($first->publicUrl())
            ->assertSee('Sin imagen');
        $response->assertViewHas('products', function ($products) use ($withImages, $withoutImages, $first, $second): bool {
            $listedWithImages = $products->firstWhere('id', $withImages->id);
            $listedWithoutImages = $products->firstWhere('id', $withoutImages->id);

            return $listedWithImages->images->first()->id === $first->id
                && $listedWithImages->images->last()->id === $second->id
                && $listedWithoutImages->images->isEmpty();
        });
    }

    public function test_authenticated_user_can_view_product_creation_form(): void
    {
        $admin = User::factory()->create();

        $response = $this->actingAs($admin)->get(route('products.create'));

        $response
            ->assertOk()
            ->assertViewIs('products.create')
            ->assertViewHas('product', fn (Product $product): bool => $product->status === 'draft');
    }

    public function test_global_flash_messages_mark_success_and_errors_for_their_respective_behaviors(): void
    {
        $admin = User::factory()->create();

        $successHtml = $this->actingAs($admin)
            ->withSession(['status' => 'Producto creado correctamente.'])
            ->get(route('products.index'))
            ->getContent();

        $this->assertStringContainsString('data-flash-success', $successHtml);
        $this->assertStringContainsString('data-flash-dismiss', $successHtml);
        $this->assertStringContainsString('aria-label="Cerrar mensaje"', $successHtml);
        $this->assertStringContainsString('4000', $successHtml);

        $errorHtml = $this->actingAs($admin)
            ->withViewErrors(['name' => 'El nombre es obligatorio.'])
            ->get(route('products.create'))
            ->getContent();

        $this->assertStringContainsString('data-flash-error', $errorHtml);
        $this->assertStringContainsString('data-flash-dismiss', $errorHtml);
    }

    public function test_product_form_uses_one_native_submit_form_on_create_and_edit(): void
    {
        $admin = User::factory()->create();
        $category = $this->createCategory();
        $product = Product::create($this->productPayload($category));

        $createHtml = $this->actingAs($admin)->get(route('products.create'))->getContent();
        $editHtml = $this->actingAs($admin)->get(route('products.edit', $product))->getContent();

        $this->assertSame(1, substr_count($createHtml, '<form id="product-form"'));
        $this->assertSame(1, substr_count($editHtml, '<form id="product-form"'));
        $this->assertStringContainsString('action="'.route('products.store').'"', $createHtml);
        $this->assertStringContainsString('action="'.route('products.update', $product).'"', $editHtml);
        $this->assertStringContainsString('<button type="submit" form="product-form"', $createHtml);
        $this->assertStringContainsString('<button type="submit" form="product-form"', $editHtml);
        $this->assertSame(2, substr_count($createHtml, 'form="product-form"'));
        $this->assertSame(2, substr_count($editHtml, 'form="product-form"'));
        $this->assertStringContainsString('name="_token"', $createHtml);
        $this->assertStringContainsString('name="_method" value="PATCH"', $editHtml);
        $this->assertStringContainsString('name="category_id"', $createHtml);
        $this->assertStringContainsString('name="base_size_id"', $editHtml);
        $this->assertStringContainsString('name="has_side_stones" value="0"', $createHtml);
        $this->assertStringNotContainsString('name="featured"', $createHtml);
        $this->assertStringNotContainsString('name="is_new"', $createHtml);
        $this->assertStringNotContainsString('name="bestseller"', $createHtml);
        $this->assertStringContainsString('id="product-code"', $createHtml);
        $this->assertStringContainsString('suggestProductCode', $createHtml);
        $this->assertStringNotContainsString('name="base_sku"', $createHtml);
        $this->assertStringNotContainsString('name="base_sku"', $editHtml);
    }

    public function test_product_switches_render_a_peer_track_and_checked_state(): void
    {
        $admin = User::factory()->create();
        $category = $this->createCategory();
        $product = Product::create($this->productPayload($category, null, null, null, [
            'featured' => true,
            'is_new' => true,
            'bestseller' => true,
        ]));

        $html = $this->actingAs($admin)->get(route('products.edit', $product))->getContent();

        $this->assertSame(1, substr_count($html, 'class="peer sr-only"'));
        $this->assertSame(1, substr_count($html, 'admin-toggle-track'));
        $this->assertStringNotContainsString('id="product-featured"', $html);
        $this->assertStringNotContainsString('id="product-new"', $html);
        $this->assertStringNotContainsString('id="product-bestseller"', $html);
        $this->assertStringContainsString('id="product-has-side-stones"', $html);
        $this->assertStringContainsString('peer-checked:after:translate-x-4', $html);
    }

    public function test_product_form_only_shows_active_non_deleted_relations_and_applicable_sizes(): void
    {
        $admin = User::factory()->create();
        $category = $this->createCategory();
        $inactiveCategory = $this->createCategory(['name' => 'Inactiva', 'slug' => 'inactiva', 'status' => false]);
        $deletedCategory = $this->createCategory(['name' => 'Eliminada', 'slug' => 'eliminada']);
        $deletedCategory->delete();

        $collection = $this->createCollection();
        $inactiveCollection = $this->createCollection(['name' => 'Colección inactiva', 'slug' => 'coleccion-inactiva', 'status' => false]);
        $deletedCollection = $this->createCollection(['name' => 'Colección eliminada', 'slug' => 'coleccion-eliminada']);
        $deletedCollection->delete();

        $material = $this->createMaterial();
        $inactiveMaterial = $this->createMaterial(['name' => 'Material inactivo', 'slug' => 'material-inactivo', 'status' => false]);
        $deletedMaterial = $this->createMaterial(['name' => 'Material eliminado', 'slug' => 'material-eliminado']);
        $deletedMaterial->delete();

        $size = $this->createSize($category);
        $inactiveSize = $this->createSize($category, ['name' => '8', 'status' => false]);
        $deletedSize = $this->createSize($category, ['name' => '9']);
        $deletedSize->delete();

        $response = $this->actingAs($admin)->get(route('products.create', ['category_id' => $category->id]));

        $response
            ->assertViewHas('categories', fn ($categories): bool => $categories->pluck('id')->all() === [$category->id])
            ->assertViewHas('collections', fn ($collections): bool => $collections->pluck('id')->all() === [$collection->id])
            ->assertViewHas('materials', fn ($materials): bool => $materials->pluck('id')->all() === [$material->id])
            ->assertViewHas('applicableSizes', fn ($sizes): bool => $sizes->pluck('id')->all() === [$size->id]);

        $this->assertNotSame($inactiveCategory->id, $category->id);
        $this->assertNotSame($inactiveCollection->id, $collection->id);
        $this->assertNotSame($inactiveMaterial->id, $material->id);
        $this->assertNotSame($inactiveSize->id, $size->id);
    }

    public function test_product_form_only_shows_active_non_deleted_stones(): void
    {
        $admin = User::factory()->create();
        $activeStone = $this->createStone();
        $this->createStone(['name' => 'Rubí', 'slug' => 'rubi', 'status' => false]);
        $deletedStone = $this->createStone(['name' => 'Zafiro', 'slug' => 'zafiro']);
        $deletedStone->delete();

        $response = $this->actingAs($admin)->get(route('products.create'));

        $response->assertViewHas('stones', fn ($stones): bool => $stones->pluck('id')->all() === [$activeStone->id]);
    }

    public function test_product_can_be_created_with_an_automatic_slug_and_normalized_booleans(): void
    {
        $admin = User::factory()->create();
        $category = $this->createCategory();
        $payload = $this->productPayload($category);
        $payload['name'] = 'Anillo Aura Oro';
        unset($payload['model']);
        $payload['featured'] = '1';
        $payload['is_new'] = '0';
        $payload['bestseller'] = '0';

        $response = $this->actingAs($admin)->post(route('products.store'), $payload);

        $response
            ->assertRedirect(route('products.index'))
            ->assertSessionHas('status', 'Producto creado correctamente.');

        $this->assertDatabaseHas('products', [
            'name' => 'Anillo Aura Oro',
            'model' => '',
            'slug' => 'anillo-aura-oro',
            'featured' => 1,
            'is_new' => 0,
            'bestseller' => 0,
        ]);
    }

    public function test_product_visibility_switches_can_be_updated_in_edit(): void
    {
        $admin = User::factory()->create();
        $category = $this->createCategory();
        $product = Product::create($this->productPayload($category));

        $response = $this->actingAs($admin)
            ->patch(route('products.update', $product), [
                ...$this->productPayload($category),
                'code' => $product->code,
                'base_sku' => $product->base_sku,
                'featured' => '1',
                'is_new' => '1',
                'bestseller' => '1',
            ])
            ->assertRedirect(route('products.index'));

        $this->assertDatabaseHas('products', [
            'id' => $product->id,
            'featured' => 1,
            'is_new' => 1,
            'bestseller' => 1,
        ]);

        $response = $this->actingAs($admin)
            ->patch(route('products.update', $product), [
                ...$this->productPayload($category),
                'code' => $product->code,
                'base_sku' => $product->base_sku,
                'featured' => '0',
                'is_new' => '0',
                'bestseller' => '0',
            ])
            ->assertRedirect(route('products.index'));

        $this->assertDatabaseHas('products', [
            'id' => $product->id,
            'featured' => 0,
            'is_new' => 0,
            'bestseller' => 0,
        ]);
    }

    public function test_product_creation_defaults_hidden_visibility_fields_to_false(): void
    {
        $admin = User::factory()->create();
        $category = $this->createCategory();
        $payload = $this->productPayload($category);
        unset($payload['featured'], $payload['is_new'], $payload['bestseller']);

        $this->actingAs($admin)
            ->post(route('products.store'), $payload)
            ->assertRedirect(route('products.index'));

        $this->assertDatabaseHas('products', [
            'featured' => 0,
            'is_new' => 0,
            'bestseller' => 0,
        ]);
    }

    public function test_product_edit_preserves_visibility_fields_when_the_form_does_not_send_them(): void
    {
        $admin = User::factory()->create();
        $category = $this->createCategory();
        $product = Product::create($this->productPayload($category, null, null, null, [
            'featured' => true,
            'is_new' => true,
            'bestseller' => true,
        ]));
        $payload = $this->productPayload($category, null, null, null, [
            'code' => $product->code,
            'base_sku' => $product->base_sku,
        ]);
        unset($payload['featured'], $payload['is_new'], $payload['bestseller']);

        $this->actingAs($admin)
            ->patch(route('products.update', $product), $payload)
            ->assertRedirect(route('products.index'));

        $this->assertDatabaseHas('products', [
            'id' => $product->id,
            'featured' => 1,
            'is_new' => 1,
            'bestseller' => 1,
        ]);
    }

    public function test_has_side_stones_accepts_zero_and_one_boolean_values(): void
    {
        $admin = User::factory()->create();
        $category = $this->createCategory();
        $stone = $this->createStone();

        $this->actingAs($admin)
            ->post(route('products.store'), $this->productPayload($category, null, null, null, [
                'has_side_stones' => '0',
            ]))
            ->assertRedirect(route('products.index'));

        $this->actingAs($admin)
            ->post(route('products.store'), $this->productPayload($category, null, null, null, [
                'code' => 'PRD-002',
                'base_sku' => 'PRD-002',
                'has_side_stones' => '1',
                'side_stone' => [
                    'stone_id' => $stone->id,
                    'quantity' => 2,
                ],
            ]))
            ->assertRedirect(route('products.index'));

        $this->assertDatabaseHas('product_stones', [
            'position' => 'lateral',
            'stone_id' => $stone->id,
            'quantity' => 2,
        ]);
    }

    public function test_product_can_be_created_without_product_stones(): void
    {
        $admin = User::factory()->create();
        $category = $this->createCategory();

        $this->actingAs($admin)
            ->post(route('products.store'), $this->productPayload($category))
            ->assertRedirect(route('products.index'));

        $this->assertDatabaseCount('product_stones', 0);
    }

    public function test_product_can_be_created_with_a_central_stone(): void
    {
        $admin = User::factory()->create();
        $category = $this->createCategory();
        $stone = $this->createStone();
        $payload = $this->productPayload($category, null, null, null, [
            'central_stone' => [
                'stone_id' => $stone->id,
            ],
        ]);

        $this->actingAs($admin)
            ->post(route('products.store'), $payload)
            ->assertRedirect(route('products.index'));

        $product = Product::where('code', $payload['code'])->firstOrFail();

        $this->assertDatabaseHas('product_stones', [
            'product_id' => $product->id,
            'stone_id' => $stone->id,
            'position' => 'central',
            'quantity' => 1,
        ]);
    }

    public function test_product_can_be_created_with_a_complete_central_stone(): void
    {
        $admin = User::factory()->create();
        $category = $this->createCategory();
        $stone = $this->createStone();
        $payload = $this->productPayload($category, null, null, null, [
            'central_stone' => [
                'stone_id' => $stone->id,
                'shape' => 'Redondo',
                'carats' => '0.50',
                'origin' => 'Laboratorio',
                'certification' => 'IGI',
                'color' => 'D',
                'clarity' => 'VS1',
                'cut' => 'Excellent',
            ],
        ]);

        $this->actingAs($admin)
            ->post(route('products.store'), $payload)
            ->assertRedirect(route('products.index'));

        $product = Product::where('code', $payload['code'])->firstOrFail();

        $this->assertDatabaseHas('product_stones', [
            'product_id' => $product->id,
            'stone_id' => $stone->id,
            'position' => 'central',
            'shape' => 'Redondo',
            'carats' => '0.50',
            'quantity' => 1,
            'origin' => 'Laboratorio',
            'certification' => 'IGI',
            'color' => 'D',
            'clarity' => 'VS1',
            'cut' => 'Excellent',
        ]);
    }

    public function test_product_can_be_created_with_lateral_stones_and_quantity(): void
    {
        $admin = User::factory()->create();
        $category = $this->createCategory();
        $stone = $this->createStone();
        $payload = $this->productPayload($category, null, null, null, [
            'has_side_stones' => '1',
            'side_stone' => [
                'stone_id' => $stone->id,
                'shape' => 'Redondo',
                'carats' => '0.10',
                'quantity' => 6,
                'origin' => 'Laboratorio',
                'certification' => 'IGI',
                'color' => 'G',
                'clarity' => 'VS2',
                'cut' => 'Excellent',
            ],
        ]);

        $this->actingAs($admin)
            ->post(route('products.store'), $payload)
            ->assertRedirect(route('products.index'));

        $product = Product::where('code', $payload['code'])->firstOrFail();

        $this->assertDatabaseHas('product_stones', [
            'product_id' => $product->id,
            'stone_id' => $stone->id,
            'position' => 'lateral',
            'quantity' => 6,
        ]);
    }

    public function test_product_edit_loads_product_stones_with_their_stones(): void
    {
        $admin = User::factory()->create();
        $category = $this->createCategory();
        $stone = $this->createStone();
        $product = Product::create($this->productPayload($category));
        ProductStone::create([
            'product_id' => $product->id,
            'stone_id' => $stone->id,
            'position' => 'central',
            'quantity' => 1,
        ]);

        $response = $this->actingAs($admin)->get(route('products.edit', $product));

        $response->assertViewHas('product', function (Product $loadedProduct): bool {
            return $loadedProduct->relationLoaded('productStones')
                && $loadedProduct->productStones->first()->relationLoaded('stone');
        });
    }

    public function test_product_update_changes_the_central_stone(): void
    {
        $admin = User::factory()->create();
        $category = $this->createCategory();
        $oldStone = $this->createStone();
        $newStone = $this->createStone(['name' => 'Esmeralda', 'slug' => 'esmeralda']);
        $product = Product::create($this->productPayload($category));
        $productStone = ProductStone::create([
            'product_id' => $product->id,
            'stone_id' => $oldStone->id,
            'position' => 'central',
            'quantity' => 1,
        ]);

        $payload = $this->productPayload($category, null, null, null, [
            'central_stone' => [
                'stone_id' => $newStone->id,
                'shape' => 'Oval',
            ],
        ]);

        $this->actingAs($admin)
            ->patch(route('products.update', $product), $payload)
            ->assertRedirect(route('products.index'));

        $this->assertDatabaseHas('product_stones', [
            'id' => $productStone->id,
            'stone_id' => $newStone->id,
            'shape' => 'Oval',
            'position' => 'central',
        ]);
        $this->assertDatabaseMissing('product_stones', [
            'product_id' => $product->id,
            'stone_id' => $oldStone->id,
        ]);
    }

    public function test_product_update_can_activate_lateral_stones(): void
    {
        $admin = User::factory()->create();
        $category = $this->createCategory();
        $stone = $this->createStone();
        $product = Product::create($this->productPayload($category));
        $payload = $this->productPayload($category, null, null, null, [
            'has_side_stones' => '1',
            'side_stone' => [
                'stone_id' => $stone->id,
                'quantity' => 3,
            ],
        ]);

        $this->actingAs($admin)
            ->patch(route('products.update', $product), $payload)
            ->assertRedirect(route('products.index'));

        $this->assertDatabaseHas('product_stones', [
            'product_id' => $product->id,
            'stone_id' => $stone->id,
            'position' => 'lateral',
            'quantity' => 3,
        ]);
    }

    public function test_product_update_removes_lateral_stones_when_switch_is_disabled(): void
    {
        $admin = User::factory()->create();
        $category = $this->createCategory();
        $stone = $this->createStone();
        $product = Product::create($this->productPayload($category));
        ProductStone::create([
            'product_id' => $product->id,
            'stone_id' => $stone->id,
            'position' => 'lateral',
            'quantity' => 2,
        ]);

        $this->actingAs($admin)
            ->patch(route('products.update', $product), $this->productPayload($category))
            ->assertRedirect(route('products.index'));

        $this->assertDatabaseMissing('product_stones', [
            'product_id' => $product->id,
            'position' => 'lateral',
        ]);
    }

    public function test_product_update_removes_the_central_stone_when_it_is_cleared(): void
    {
        $admin = User::factory()->create();
        $category = $this->createCategory();
        $stone = $this->createStone();
        $product = Product::create($this->productPayload($category));
        ProductStone::create([
            'product_id' => $product->id,
            'stone_id' => $stone->id,
            'position' => 'central',
            'quantity' => 1,
        ]);

        $this->actingAs($admin)
            ->patch(route('products.update', $product), $this->productPayload($category))
            ->assertRedirect(route('products.index'));

        $this->assertDatabaseMissing('product_stones', [
            'product_id' => $product->id,
            'position' => 'central',
        ]);
    }

    public function test_product_does_not_create_an_empty_product_stone(): void
    {
        $admin = User::factory()->create();
        $category = $this->createCategory();
        $payload = $this->productPayload($category, null, null, null, [
            'central_stone' => [
                'shape' => 'Oval',
                'carats' => '0.50',
            ],
        ]);

        $this->actingAs($admin)
            ->post(route('products.store'), $payload)
            ->assertRedirect(route('products.index'));

        $this->assertDatabaseCount('product_stones', 0);
    }

    public function test_product_stones_must_reference_active_non_deleted_stones(): void
    {
        $admin = User::factory()->create();
        $category = $this->createCategory();
        $inactiveStone = $this->createStone(['name' => 'Rubí', 'slug' => 'rubi', 'status' => false]);
        $deletedStone = $this->createStone(['name' => 'Zafiro', 'slug' => 'zafiro']);
        $deletedStone->delete();

        foreach ([$inactiveStone->id, $deletedStone->id, 999999] as $stoneId) {
            $payload = $this->productPayload($category, null, null, null, [
                'central_stone' => ['stone_id' => $stoneId],
            ]);

            $this->actingAs($admin)
                ->post(route('products.store'), $payload)
                ->assertSessionHasErrors(['central_stone.stone_id']);
        }

        $this->assertDatabaseCount('products', 0);
        $this->assertDatabaseCount('product_stones', 0);
    }

    public function test_lateral_stone_requires_the_switch_and_quantity_fields(): void
    {
        $admin = User::factory()->create();
        $category = $this->createCategory();
        $stone = $this->createStone();
        $payload = $this->productPayload($category, null, null, null, [
            'has_side_stones' => '1',
            'side_stone' => [
                'stone_id' => $stone->id,
            ],
        ]);

        $response = $this->actingAs($admin)->post(route('products.store'), $payload);

        $response->assertSessionHasErrors(['side_stone.quantity']);
        $this->assertDatabaseCount('products', 0);
    }

    public function test_duplicate_product_names_receive_incremental_slugs_including_soft_deleted_products(): void
    {
        $admin = User::factory()->create();
        $category = $this->createCategory();
        $first = Product::create($this->productPayload($category, null, null, null, [
            'name' => 'Producto repetido',
            'slug' => 'producto-repetido',
        ]));
        $first->delete();

        $secondPayload = $this->productPayload($category, null, null, null, [
            'name' => 'Producto repetido',
            'code' => 'PRD-002',
            'base_sku' => 'SKU-002',
        ]);

        $this->actingAs($admin)->post(route('products.store'), $secondPayload);

        $this->assertDatabaseHas('products', [
            'code' => 'PRD-002',
            'slug' => 'producto-repetido-2',
        ]);
    }

    public function test_product_validation_rejects_invalid_required_and_numeric_fields(): void
    {
        $admin = User::factory()->create();

        $response = $this->actingAs($admin)->post(route('products.store'), [
            'name' => '',
            'code' => '',
            'category_id' => 999999,
            'price' => -1,
            'status' => 'unknown',
        ]);

        $response->assertSessionHasErrors(['name', 'category_id', 'price', 'status']);
        $this->assertDatabaseCount('products', 0);
    }

    public function test_product_code_must_be_unique(): void
    {
        $admin = User::factory()->create();
        $category = $this->createCategory();
        Product::create($this->productPayload($category));
        $payload = $this->productPayload($category, null, null, null, [
            'name' => 'Otro producto',
        ]);

        $response = $this->actingAs($admin)->post(route('products.store'), $payload);

        $response
            ->assertSessionHasErrors(['code'])
            ->assertSessionHas('errors', function ($errors): bool {
                return $errors->first('code') === 'El código ya está en uso.';
            });
        $this->assertDatabaseCount('products', 1);
    }

    public function test_product_creation_generates_base_sku_from_code_without_requiring_it(): void
    {
        $admin = User::factory()->create();
        $category = $this->createCategory();
        $payload = $this->productPayload($category, null, null, null, [
            'code' => 'AUR-001',
        ]);
        unset($payload['base_sku']);

        $this->actingAs($admin)
            ->post(route('products.store'), $payload)
            ->assertRedirect(route('products.index'));

        $this->assertDatabaseHas('products', [
            'code' => 'AUR-001',
            'base_sku' => 'AUR-001',
        ]);
    }

    public function test_product_code_is_generated_in_a_compact_unique_format(): void
    {
        $admin = User::factory()->create();
        $category = $this->createCategory([
            'name' => 'Anillos',
            'slug' => 'anillos',
        ]);
        $subcategory = $this->createCategory([
            'name' => 'Compromiso',
            'slug' => 'compromiso',
            'parent_id' => $category->id,
        ]);
        $payload = $this->productPayload($category, null, null, null, [
            'name' => 'Anillo Aurea Lumea',
            'code' => null,
            'code_generated' => true,
            'subcategory_id' => $subcategory->id,
        ]);
        unset($payload['base_sku']);

        $this->actingAs($admin)
            ->post(route('products.store'), $payload)
            ->assertRedirect(route('products.index'));

        $this->assertDatabaseHas('products', [
            'name' => 'Anillo Aurea Lumea',
            'code' => 'ANI-AUR-001',
            'base_sku' => 'ANI-AUR-001',
        ]);
    }

    public function test_compact_automatic_codes_receive_the_next_available_correlative(): void
    {
        $admin = User::factory()->create();
        $category = $this->createCategory();
        $firstPayload = $this->productPayload($category, null, null, null, [
            'name' => 'Anillo Aura',
            'code' => null,
            'code_generated' => true,
        ]);
        $secondPayload = $firstPayload;
        $secondPayload['code'] = null;

        $this->actingAs($admin)->post(route('products.store'), $firstPayload);
        $this->actingAs($admin)->post(route('products.store'), $secondPayload);

        $thirdPayload = $secondPayload;

        $this->actingAs($admin)->post(route('products.store'), $thirdPayload);

        $this->assertDatabaseHas('products', ['code' => 'ANI-AUR-001']);
        $this->assertDatabaseHas('products', ['code' => 'ANI-AUR-002']);
        $this->assertDatabaseHas('products', ['code' => 'ANI-AUR-003']);
    }

    public function test_code_suggestion_uses_the_next_available_code_and_checks_base_sku(): void
    {
        $admin = User::factory()->create();
        $category = $this->createCategory();
        Product::create($this->productPayload($category, null, null, null, [
            'name' => 'Anillo Aura',
            'code' => 'ANI-AUR-001',
            'base_sku' => 'ANI-AUR-001',
            'slug' => 'anillo-aura-1',
        ]));
        Product::create($this->productPayload($category, null, null, null, [
            'name' => 'Otro producto',
            'code' => 'OTR-001',
            'base_sku' => 'ANI-AUR-002',
            'slug' => 'otro-producto',
        ]));

        $this->actingAs($admin)
            ->getJson(route('products.code-suggestion', [
                'name' => 'Anillo Aura',
                'category_id' => $category->id,
            ]))
            ->assertOk()
            ->assertJson(['code' => 'ANI-AUR-003']);
    }

    public function test_changing_subcategory_does_not_change_the_automatic_code(): void
    {
        $admin = User::factory()->create();
        $category = $this->createCategory();
        $firstSubcategory = $this->createCategory([
            'name' => 'Compromiso',
            'slug' => 'compromiso',
            'parent_id' => $category->id,
        ]);
        $secondSubcategory = $this->createCategory([
            'name' => 'Boda',
            'slug' => 'boda',
            'parent_id' => $category->id,
        ]);
        $payload = $this->productPayload($category, null, null, null, [
            'name' => 'Anillo Aurea',
            'code' => null,
            'code_generated' => true,
            'subcategory_id' => $firstSubcategory->id,
        ]);

        $this->actingAs($admin)->post(route('products.store'), $payload);
        $product = Product::firstOrFail();
        $originalCode = $product->code;

        $response = $this->actingAs($admin)
            ->patch(route('products.update', $product), [
                ...$payload,
                'code' => $originalCode,
                'subcategory_id' => $secondSubcategory->id,
            ])
            ->assertRedirect(route('products.index'));

        $this->assertDatabaseHas('products', [
            'id' => $product->id,
            'code' => $originalCode,
            'category_id' => $secondSubcategory->id,
        ]);
    }

    public function test_changing_main_category_updates_the_automatic_code_suggestion(): void
    {
        $admin = User::factory()->create();
        $rings = $this->createCategory();
        $bracelets = $this->createCategory([
            'name' => 'Pulseras',
            'slug' => 'pulseras',
        ]);
        $payload = $this->productPayload($rings, null, null, null, [
            'name' => 'Anillo Aurea',
            'code' => null,
            'code_generated' => true,
        ]);

        $this->actingAs($admin)->post(route('products.store'), $payload);
        $product = Product::firstOrFail();

        $this->actingAs($admin)
            ->patch(route('products.update', $product), [
                ...$payload,
                'category_id' => $bracelets->id,
                'code' => $product->code,
                'code_generated' => true,
            ])
            ->assertRedirect(route('products.index'));

        $this->assertDatabaseHas('products', [
            'id' => $product->id,
            'code' => 'PUL-ANI-001',
            'base_sku' => 'PUL-ANI-001',
        ]);
    }

    public function test_manual_product_code_is_preserved_and_base_sku_uses_the_final_code(): void
    {
        $admin = User::factory()->create();
        $category = $this->createCategory();
        $payload = $this->productPayload($category, null, null, null, [
            'code' => 'CUSTOM-9',
            'code_generated' => false,
        ]);

        $this->actingAs($admin)
            ->post(route('products.store'), $payload)
            ->assertRedirect(route('products.index'));

        $this->assertDatabaseHas('products', [
            'code' => 'CUSTOM-9',
            'base_sku' => 'CUSTOM-9',
        ]);
    }

    public function test_updating_code_regenerates_base_sku(): void
    {
        $admin = User::factory()->create();
        $category = $this->createCategory();
        $product = Product::create($this->productPayload($category, null, null, null, [
            'code' => 'AUR-001',
            'base_sku' => 'AUR-001',
        ]));

        $this->actingAs($admin)
            ->patch(route('products.update', $product), [
                ...$this->productPayload($category, null, null, null, [
                    'code' => 'AUR-002',
                    'base_sku' => 'MANUAL-SKU',
                ]),
                'code' => 'AUR-002',
            ])
            ->assertRedirect(route('products.index'));

        $this->assertDatabaseHas('products', [
            'id' => $product->id,
            'code' => 'AUR-002',
            'base_sku' => 'AUR-002',
        ]);
    }

    public function test_updating_without_changing_code_preserves_base_sku_and_ignores_manual_input(): void
    {
        $admin = User::factory()->create();
        $category = $this->createCategory();
        $product = Product::create($this->productPayload($category, null, null, null, [
            'code' => 'AUR-001',
            'base_sku' => 'INTERNAL-AUR-001',
        ]));

        $this->actingAs($admin)
            ->patch(route('products.update', $product), [
                ...$this->productPayload($category, null, null, null, [
                    'base_sku' => 'FORCED-SKU',
                ]),
                'code' => 'AUR-001',
            ])
            ->assertRedirect(route('products.index'));

        $this->assertDatabaseHas('products', [
            'id' => $product->id,
            'code' => 'AUR-001',
            'base_sku' => 'INTERNAL-AUR-001',
        ]);
    }

    public function test_base_size_must_be_applicable_to_the_selected_category(): void
    {
        $admin = User::factory()->create();
        $category = $this->createCategory();
        $otherCategory = $this->createCategory(['name' => 'Collares', 'slug' => 'collares']);
        $validSize = $this->createSize($category);
        $invalidSize = $this->createSize($otherCategory, ['name' => '18 cm']);
        $payload = $this->productPayload($category, null, null, $invalidSize);

        $response = $this->actingAs($admin)->post(route('products.store'), $payload);

        $response->assertSessionHasErrors(['base_size_id']);
        $this->assertDatabaseCount('products', 0);
        $this->assertNotSame($validSize->id, $invalidSize->id);
    }

    public function test_base_size_inherited_from_a_parent_category_is_accepted(): void
    {
        $admin = User::factory()->create();
        $parent = $this->createCategory();
        $child = $this->createCategory([
            'parent_id' => $parent->id,
            'name' => 'Compromiso',
            'slug' => 'compromiso',
        ]);
        $size = $this->createSize($parent);
        $payload = $this->productPayload($parent, null, null, $size);
        $payload['subcategory_id'] = $child->id;

        $response = $this->actingAs($admin)->post(route('products.store'), $payload);

        $response->assertRedirect(route('products.index'));
        $this->assertDatabaseHas('products', [
            'category_id' => $child->id,
            'base_size_id' => $size->id,
        ]);
    }

    public function test_product_stores_a_selected_subcategory_as_its_effective_category(): void
    {
        $admin = User::factory()->create();
        $parent = $this->createCategory();
        $child = $this->createCategory([
            'name' => 'Compromiso',
            'slug' => 'compromiso',
            'parent_id' => $parent->id,
        ]);

        $this->actingAs($admin)
            ->post(route('products.store'), $this->productPayload($parent, null, null, null, [
                'subcategory_id' => $child->id,
            ]))
            ->assertRedirect(route('products.index'));

        $this->assertDatabaseHas('products', [
            'category_id' => $child->id,
            'code' => 'PRD-001',
        ]);
    }

    public function test_product_rejects_a_subcategory_from_another_parent_category(): void
    {
        $admin = User::factory()->create();
        $parent = $this->createCategory();
        $otherParent = $this->createCategory([
            'name' => 'Collares',
            'slug' => 'collares',
        ]);
        $child = $this->createCategory([
            'name' => 'Compromiso',
            'slug' => 'compromiso',
            'parent_id' => $otherParent->id,
        ]);

        $this->actingAs($admin)
            ->post(route('products.store'), $this->productPayload($parent, null, null, null, [
                'subcategory_id' => $child->id,
            ]))
            ->assertSessionHasErrors('subcategory_id');

        $this->assertDatabaseCount('products', 0);
    }

    public function test_product_images_can_be_uploaded_set_as_cover_and_removed(): void
    {
        $admin = User::factory()->create();
        $category = $this->createCategory();
        $payload = $this->productPayload($category, null, null, null, [
            'images' => [UploadedFile::fake()->image('front.jpg')],
            'new_image_alts' => [0 => 'Vista frontal'],
            'cover_new_index' => 0,
        ]);

        $this->actingAs($admin)
            ->post(route('products.store'), $payload)
            ->assertRedirect(route('products.index'));

        $image = ProductImage::firstOrFail();
        $this->assertTrue($image->is_cover);
        $this->assertSame('Vista frontal', $image->alt);
        $imagePath = $this->testUploadDirectory.'/'.$image->path;

        $this->assertFileExists($imagePath);
        $this->assertSame($image->path, basename($image->path));
        $this->assertStringNotContainsString('uploads/products/', $image->path);
        $this->assertFalse(str_starts_with($image->path, '/'));

        $this->actingAs($admin)
            ->patch(route('products.update', $image->product), [
                ...$this->productPayload($category, null, null, null, [
                    'code' => 'PRD-002',
                    'base_sku' => 'SKU-002',
                ]),
                'remove_image_ids' => [$image->id],
            ])
            ->assertRedirect(route('products.index'));

        $this->assertDatabaseMissing('product_images', ['id' => $image->id]);
        $this->assertFileDoesNotExist($imagePath);
    }

    public function test_edit_keeps_existing_images_and_adds_a_valid_new_image(): void
    {
        $admin = User::factory()->create();
        $category = $this->createCategory();
        $product = Product::create($this->productPayload($category));
        $existingImage = $this->createProductImage($product, 0, [
            'path' => 'existing-edit.jpg',
            'is_cover' => true,
        ]);
        File::ensureDirectoryExists($this->testUploadDirectory);
        File::put($this->testUploadDirectory.'/existing-edit.jpg', 'existing image');

        $this->actingAs($admin)
            ->patch(route('products.update', $product), [
                ...$this->productPayload($category),
                'code' => $product->code,
                'base_sku' => $product->base_sku,
                'images' => [UploadedFile::fake()->image('new-edit.webp')],
                'new_image_alts' => [0 => 'Nueva imagen'],
            ])
            ->assertRedirect(route('products.index'));

        $images = ProductImage::query()->where('product_id', $product->id)->orderBy('sort_order')->get();
        $newImage = $images->firstWhere('id', '!=', $existingImage->id);

        $this->assertCount(2, $images);
        $this->assertNotNull($newImage);
        $this->assertFileExists($this->testUploadDirectory.'/'.$newImage->path);
        $this->assertDatabaseHas('product_images', ['id' => $existingImage->id]);
    }

    public function test_invalid_upload_does_not_delete_existing_images(): void
    {
        $admin = User::factory()->create();
        $category = $this->createCategory();
        $product = Product::create($this->productPayload($category));
        $existingImage = $this->createProductImage($product, 0, [
            'path' => 'existing-invalid-upload.jpg',
            'is_cover' => true,
        ]);
        File::ensureDirectoryExists($this->testUploadDirectory);
        $existingPath = $this->testUploadDirectory.'/existing-invalid-upload.jpg';
        File::put($existingPath, 'existing image');

        $temporaryPath = tempnam(sys_get_temp_dir(), 'invalid-upload-');
        file_put_contents($temporaryPath, 'invalid upload');
        $invalidUpload = new SymfonyUploadedFile(
            $temporaryPath,
            'too-large.webp',
            'image/webp',
            UPLOAD_ERR_INI_SIZE,
            true,
        );

        $response = $this->actingAs($admin)
            ->patch(route('products.update', $product), [
                ...$this->productPayload($category),
                'code' => $product->code,
                'base_sku' => $product->base_sku,
                'images' => [$invalidUpload],
            ])
            ->assertSessionHasErrors('images.0');

        $response->assertSessionHas('errors', function ($errors): bool {
            return in_array(
                'No se pudo subir la imagen seleccionada. Verifica su tamaño e inténtalo nuevamente.',
                $errors->get('images.0'),
                true,
            );
        });

        if (file_exists($temporaryPath)) {
            unlink($temporaryPath);
        }

        $this->assertDatabaseHas('product_images', ['id' => $existingImage->id]);
        $this->assertFileExists($existingPath);
        $this->assertSame(1, ProductImage::where('product_id', $product->id)->count());
    }

    public function test_multiple_uploaded_images_keep_the_received_order(): void
    {
        $admin = User::factory()->create();
        $category = $this->createCategory();

        $this->actingAs($admin)
            ->post(route('products.store'), $this->productPayload($category, null, null, null, [
                'images' => [
                    UploadedFile::fake()->image('front.jpg'),
                    UploadedFile::fake()->image('detail.jpg'),
                ],
                'new_image_alts' => [
                    0 => 'Frente',
                    1 => 'Detalle',
                ],
                'image_order' => 'new-1,new-0',
            ]))
            ->assertRedirect(route('products.index'));

        $this->assertSame(
            ['Detalle', 'Frente'],
            ProductImage::query()->orderBy('sort_order')->pluck('alt')->all(),
        );
        $this->assertSame([0, 1], ProductImage::query()->orderBy('sort_order')->pluck('sort_order')->all());
    }

    public function test_product_image_render_uses_the_public_uploads_url_for_new_and_legacy_paths(): void
    {
        $admin = User::factory()->create();
        $category = $this->createCategory();
        $product = Product::create($this->productPayload($category));
        ProductImage::create([
            'product_id' => $product->id,
            'path' => 'anillo-aurea-01.webp',
            'sort_order' => 0,
            'is_cover' => true,
        ]);
        ProductImage::create([
            'product_id' => $product->id,
            'path' => $this->testUploadUrlPrefix.'/legacy.webp',
            'sort_order' => 1,
            'is_cover' => false,
        ]);
        File::ensureDirectoryExists($this->testUploadDirectory);
        File::put($this->testUploadDirectory.'/anillo-aurea-01.webp', 'image');
        File::put($this->testUploadDirectory.'/legacy.webp', 'image');

        $response = $this->actingAs($admin)->get(route('products.edit', $product));

        $response
            ->assertOk()
            ->assertSee(asset($this->testUploadUrlPrefix.'/anillo-aurea-01.webp'))
            ->assertSee(asset($this->testUploadUrlPrefix.'/legacy.webp'));
    }

    public function test_product_edit_uses_a_placeholder_when_the_image_file_is_missing(): void
    {
        $admin = User::factory()->create();
        $category = $this->createCategory();
        $product = Product::create($this->productPayload($category));
        $image = $this->createProductImage($product, 0, [
            'path' => 'missing-image.webp',
            'is_cover' => true,
        ]);

        $response = $this->actingAs($admin)->get(route('products.edit', $product));

        $response
            ->assertOk()
            ->assertSee('Sin imagen')
            ->assertDontSee($image->publicUrl());
    }

    public function test_first_uploaded_image_becomes_cover_and_blank_alts_use_the_product_name(): void
    {
        $admin = User::factory()->create();
        $category = $this->createCategory();

        $this->actingAs($admin)
            ->post(route('products.store'), $this->productPayload($category, null, null, null, [
                'name' => 'Anillo Solitario Aurora',
                'images' => [
                    UploadedFile::fake()->image('front.jpg'),
                    UploadedFile::fake()->image('detail.jpg'),
                ],
                'new_image_alts' => [0 => '', 1 => ''],
            ]))
            ->assertRedirect(route('products.index'));

        $images = ProductImage::query()->orderBy('sort_order')->get();

        $this->assertTrue($images->first()->is_cover);
        $this->assertCount(1, $images->where('is_cover', true));
        $this->assertSame(['Anillo Solitario Aurora', 'Anillo Solitario Aurora'], $images->pluck('alt')->all());
    }

    public function test_marking_another_image_as_cover_unmarks_the_previous_cover(): void
    {
        $admin = User::factory()->create();
        $category = $this->createCategory();
        $product = Product::create($this->productPayload($category));
        $first = $this->createProductImage($product, 0, ['is_cover' => true]);
        $second = $this->createProductImage($product, 1);

        $this->actingAs($admin)
            ->patch(route('products.update', $product), [
                ...$this->productPayload($category),
                'code' => $product->code,
                'base_sku' => $product->base_sku,
                'cover_image_id' => $second->id,
            ])
            ->assertRedirect(route('products.index'));

        $this->assertDatabaseHas('product_images', ['id' => $first->id, 'is_cover' => false]);
        $this->assertDatabaseHas('product_images', ['id' => $second->id, 'is_cover' => true]);
        $this->assertSame(1, ProductImage::where('product_id', $product->id)->where('is_cover', true)->count());
    }

    public function test_product_image_upload_rejects_invalid_file_types(): void
    {
        $admin = User::factory()->create();
        $category = $this->createCategory();

        $this->actingAs($admin)
            ->post(route('products.store'), $this->productPayload($category, null, null, null, [
                'images' => [UploadedFile::fake()->create('manual.pdf', 100, 'application/pdf')],
            ]))
            ->assertSessionHasErrors('images.0');

        $this->assertDatabaseCount('products', 0);
    }

    public function test_product_form_supports_file_drop_and_data_transfer_sync(): void
    {
        $admin = User::factory()->create();
        $response = $this->actingAs($admin)->get(route('products.create'));
        $html = $response->getContent();

        $response->assertOk();
        $this->assertStringContainsString('id="product-image-dropzone"', $html);
        $this->assertStringContainsString("addEventListener('dragenter'", $html);
        $this->assertStringContainsString("addEventListener('dragover'", $html);
        $this->assertStringContainsString("addEventListener('dragleave'", $html);
        $this->assertStringContainsString("addEventListener('drop'", $html);
        $this->assertStringContainsString('new DataTransfer()', $html);
        $this->assertStringContainsString('productImageUpload.files = transfer.files', $html);
        $this->assertStringContainsString('Suelta las imágenes aquí', $html);
    }

    public function test_product_images_are_loaded_in_sort_order_then_id_order(): void
    {
        $admin = User::factory()->create();
        $category = $this->createCategory();
        $product = Product::create($this->productPayload($category));
        $first = $this->createProductImage($product, 2);
        $second = $this->createProductImage($product, 0);
        $third = $this->createProductImage($product, 1);

        $response = $this->actingAs($admin)->get(route('products.edit', $product));

        $response->assertViewHas('product', function (Product $loadedProduct) use ($second, $third, $first): bool {
            return $loadedProduct->images->pluck('id')->all() === [$second->id, $third->id, $first->id];
        });
    }

    public function test_product_image_reordering_updates_sort_order_and_preserves_cover(): void
    {
        $admin = User::factory()->create();
        $category = $this->createCategory();
        $product = Product::create($this->productPayload($category));
        $first = $this->createProductImage($product, 0, ['is_cover' => true]);
        $second = $this->createProductImage($product, 1);
        $third = $this->createProductImage($product, 2);

        $this->actingAs($admin)
            ->patchJson(route('products.images.reorder', $product), [
                'images' => [$third->id, $first->id, $second->id],
            ])
            ->assertOk();

        $this->assertDatabaseHas('product_images', ['id' => $third->id, 'sort_order' => 0]);
        $this->assertDatabaseHas('product_images', ['id' => $first->id, 'sort_order' => 1, 'is_cover' => true]);
        $this->assertDatabaseHas('product_images', ['id' => $second->id, 'sort_order' => 2]);
        $this->assertSame(
            [$third->id, $first->id, $second->id],
            $product->images()->orderBy('sort_order')->pluck('id')->all(),
        );

        $this->actingAs($admin)
            ->get(route('products.edit', $product))
            ->assertViewHas('product', function (Product $loadedProduct) use ($third, $first, $second): bool {
                return $loadedProduct->images->pluck('id')->all() === [$third->id, $first->id, $second->id]
                    && $loadedProduct->images->firstWhere('id', $first->id)->is_cover;
            });
    }

    public function test_product_image_reordering_rejects_foreign_and_duplicate_ids(): void
    {
        $admin = User::factory()->create();
        $category = $this->createCategory();
        $product = Product::create($this->productPayload($category));
        $otherProduct = Product::create($this->productPayload($category, null, null, null, [
            'code' => 'PRD-002',
            'base_sku' => 'SKU-002',
            'slug' => 'producto-base-2',
        ]));
        $image = $this->createProductImage($product);
        $foreignImage = $this->createProductImage($otherProduct);
        $secondImage = $this->createProductImage($product, 1);

        $this->actingAs($admin)
            ->patchJson(route('products.images.reorder', $product), [
                'images' => [$image->id, $foreignImage->id],
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('images');

        $this->actingAs($admin)
            ->patchJson(route('products.images.reorder', $product), [
                'images' => [$image->id, $image->id],
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('images.1');

        $this->actingAs($admin)
            ->patchJson(route('products.images.reorder', $product), [
                'images' => [$image->id],
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('images');

        $this->assertDatabaseHas('product_images', ['id' => $secondImage->id]);
    }

    public function test_removing_a_product_image_normalizes_the_remaining_order(): void
    {
        $admin = User::factory()->create();
        $category = $this->createCategory();
        $product = Product::create($this->productPayload($category));
        $first = $this->createProductImage($product, 0, ['is_cover' => true]);
        $middle = $this->createProductImage($product, 4);
        $last = $this->createProductImage($product, 9);

        $this->actingAs($admin)
            ->patch(route('products.update', $product), [
                ...$this->productPayload($category),
                'code' => $product->code,
                'base_sku' => $product->base_sku,
                'remove_image_ids' => [$middle->id],
            ])
            ->assertRedirect(route('products.index'));

        $this->assertDatabaseMissing('product_images', ['id' => $middle->id]);
        $this->assertSame(
            [$first->id, $last->id],
            $product->images()->orderBy('sort_order')->pluck('id')->all(),
        );
        $this->assertDatabaseHas('product_images', ['id' => $first->id, 'sort_order' => 0]);
        $this->assertDatabaseHas('product_images', ['id' => $last->id, 'sort_order' => 1]);
    }

    public function test_removing_the_cover_assigns_the_first_remaining_image_as_cover(): void
    {
        $admin = User::factory()->create();
        $category = $this->createCategory();
        $product = Product::create($this->productPayload($category));
        $cover = $this->createProductImage($product, 0, ['is_cover' => true]);
        $remaining = $this->createProductImage($product, 1);

        $this->actingAs($admin)
            ->patch(route('products.update', $product), [
                ...$this->productPayload($category),
                'code' => $product->code,
                'base_sku' => $product->base_sku,
                'remove_image_ids' => [$cover->id],
            ])
            ->assertRedirect(route('products.index'));

        $this->assertDatabaseMissing('product_images', ['id' => $cover->id]);
        $this->assertDatabaseHas('product_images', ['id' => $remaining->id, 'is_cover' => true]);
        $this->assertSame(1, ProductImage::where('product_id', $product->id)->where('is_cover', true)->count());
    }

    public function test_product_without_images_has_no_cover(): void
    {
        $category = $this->createCategory();
        $product = Product::create($this->productPayload($category));

        $this->assertSame(0, ProductImage::where('product_id', $product->id)->where('is_cover', true)->count());
    }

    public function test_editing_a_product_does_not_overwrite_an_existing_alt(): void
    {
        $admin = User::factory()->create();
        $category = $this->createCategory();
        $product = Product::create($this->productPayload($category));
        $image = $this->createProductImage($product, 0, ['alt' => 'Alt personalizado', 'is_cover' => true]);

        $this->actingAs($admin)
            ->patch(route('products.update', $product), [
                ...$this->productPayload($category),
                'code' => $product->code,
                'base_sku' => $product->base_sku,
            ])
            ->assertRedirect(route('products.index'));

        $this->assertDatabaseHas('product_images', [
            'id' => $image->id,
            'alt' => 'Alt personalizado',
        ]);
    }

    public function test_authenticated_user_can_edit_and_update_a_product_regenerating_its_slug(): void
    {
        $admin = User::factory()->create();
        $category = $this->createCategory();
        $product = Product::create($this->productPayload($category, null, null, null, [
            'name' => 'Nombre original',
            'slug' => 'nombre-original',
        ]));

        $editResponse = $this->actingAs($admin)->get(route('products.edit', $product));

        $editResponse
            ->assertOk()
            ->assertViewIs('products.edit')
            ->assertViewHas('product', $product);

        $payload = $this->productPayload($category, null, null, null, [
            'name' => 'Nombre actualizado',
            'code' => $product->code,
            'base_sku' => $product->base_sku,
            'status' => 'active',
        ]);

        $this->actingAs($admin)
            ->patch(route('products.update', $product), $payload)
            ->assertRedirect(route('products.index'))
            ->assertSessionHas('status', 'Producto actualizado correctamente.');

        $this->assertDatabaseHas('products', [
            'id' => $product->id,
            'name' => 'Nombre actualizado',
            'slug' => 'nombre-actualizado',
            'status' => 'active',
        ]);
    }

    public function test_product_index_filters_by_search_category_collection_and_status(): void
    {
        $admin = User::factory()->create();
        $category = $this->createCategory();
        $otherCategory = $this->createCategory(['name' => 'Collares', 'slug' => 'collares']);
        $collection = $this->createCollection();
        $otherCollection = $this->createCollection(['name' => 'Bridal', 'slug' => 'bridal']);
        $product = Product::create($this->productPayload($category, $collection, null, null, [
            'name' => 'Producto buscable',
            'model' => 'Modelo buscable',
            'code' => 'BUS-001',
            'base_sku' => 'BUS-SKU',
            'status' => 'active',
        ]));
        Product::create($this->productPayload($otherCategory, $otherCollection, null, null, [
            'name' => 'Producto oculto',
            'code' => 'OCU-001',
            'base_sku' => 'OCU-SKU',
            'status' => 'draft',
        ]));

        foreach (['Producto buscable', 'Modelo buscable', 'BUS-001', 'BUS-SKU', 'producto-buscable'] as $search) {
            $response = $this->actingAs($admin)->get(route('products.index', ['search' => $search]));
            $response->assertViewHas('products', fn ($products): bool => $products->total() === 1 && $products->first()->id === $product->id);
        }

        $this->actingAs($admin)
            ->get(route('products.index', ['category_id' => $category->id]))
            ->assertViewHas('products', fn ($products): bool => $products->total() === 1 && $products->first()->id === $product->id);

        $this->actingAs($admin)
            ->get(route('products.index', ['collection_id' => $collection->id]))
            ->assertViewHas('products', fn ($products): bool => $products->total() === 1 && $products->first()->id === $product->id);

        $this->actingAs($admin)
            ->get(route('products.index', ['status' => 'active']))
            ->assertViewHas('products', fn ($products): bool => $products->total() === 1 && $products->first()->id === $product->id);
    }

    public function test_product_index_paginates_ten_records_and_preserves_query_string(): void
    {
        $admin = User::factory()->create();
        $category = $this->createCategory();

        for ($index = 1; $index <= 11; $index++) {
            Product::create($this->productPayload($category, null, null, null, [
                'name' => "Producto {$index}",
                'code' => "PRD-{$index}",
                'base_sku' => "SKU-{$index}",
            ]));
        }

        $response = $this->actingAs($admin)->get(route('products.index', [
            'search' => 'Producto',
            'status' => 'draft',
        ]));

        $response
            ->assertOk()
            ->assertSee('search=Producto')
            ->assertSee('status=draft')
            ->assertViewHas('products', function ($products): bool {
                return $products instanceof LengthAwarePaginator
                    && $products->total() === 11
                    && $products->perPage() === 10;
            });
    }

    public function test_product_is_soft_deleted(): void
    {
        $admin = User::factory()->create();
        $category = $this->createCategory();
        $product = Product::create($this->productPayload($category));

        $this->actingAs($admin)
            ->delete(route('products.destroy', $product))
            ->assertRedirect(route('products.index'))
            ->assertSessionHas('status', 'Producto eliminado correctamente.');

        $this->assertSoftDeleted('products', ['id' => $product->id]);
        $this->assertNotNull(Product::withTrashed()->find($product->id));
    }

    public function test_soft_deleting_a_product_preserves_its_children_and_physical_images(): void
    {
        $admin = User::factory()->create();
        $category = $this->createCategory();
        $size = $this->createSize($category);
        $stone = $this->createStone();
        $product = Product::create($this->productPayload($category));
        $image = $this->createProductImage($product, 0, [
            'path' => 'soft-delete.jpg',
            'is_cover' => true,
        ]);
        ProductStone::create([
            'product_id' => $product->id,
            'stone_id' => $stone->id,
            'position' => 'central',
            'quantity' => 1,
        ]);
        $variant = ProductVariant::create([
            'product_id' => $product->id,
            'size_id' => $size->id,
            'sku' => 'SOFT-DELETE-1',
            'status' => 'active',
        ]);
        InventoryStock::create([
            'product_variant_id' => $variant->id,
            'current_stock' => 0,
            'minimum_stock' => 0,
        ]);
        File::ensureDirectoryExists($this->testUploadDirectory);
        $imagePath = $this->testUploadDirectory.'/soft-delete.jpg';
        File::put($imagePath, 'catalog image');

        $this->actingAs($admin)
            ->delete(route('products.destroy', $product))
            ->assertRedirect(route('products.index'));

        $this->assertSoftDeleted('products', ['id' => $product->id]);
        $this->assertDatabaseHas('product_images', ['id' => $image->id, 'product_id' => $product->id]);
        $this->assertDatabaseHas('product_stones', ['product_id' => $product->id, 'position' => 'central']);
        $this->assertDatabaseHas('product_variants', ['id' => $variant->id, 'product_id' => $product->id]);
        $this->assertDatabaseHas('inventory_stocks', ['product_variant_id' => $variant->id]);
        $this->assertFileExists($imagePath);
    }

    public function test_updating_product_data_without_multimedia_changes_preserves_the_image_file(): void
    {
        $admin = User::factory()->create();
        $category = $this->createCategory();
        $product = Product::create($this->productPayload($category));
        $image = $this->createProductImage($product, 0, [
            'path' => 'preserve-on-update.jpg',
            'is_cover' => true,
        ]);
        File::ensureDirectoryExists($this->testUploadDirectory);
        $imagePath = $this->testUploadDirectory.'/'.$image->path;
        File::put($imagePath, 'catalog image');

        $this->actingAs($admin)
            ->patch(route('products.update', $product), $this->productPayload($category, null, null, null, [
                'name' => 'Producto actualizado',
                'price' => '1800.00',
                'status' => 'active',
                'code' => $product->code,
                'base_sku' => $product->base_sku,
            ]))
            ->assertRedirect(route('products.index'));

        $this->assertDatabaseHas('product_images', ['id' => $image->id, 'path' => $image->path]);
        $this->assertFileExists($imagePath);
    }

    public function test_manual_code_cannot_collide_with_another_product_base_sku(): void
    {
        $admin = User::factory()->create();
        $category = $this->createCategory();
        Product::create($this->productPayload($category, null, null, null, [
            'code' => 'ORIGINAL-1',
            'base_sku' => 'BASE-001',
        ]));

        $this->actingAs($admin)
            ->post(route('products.store'), $this->productPayload($category, null, null, null, [
                'name' => 'Otro producto',
                'code' => 'BASE-001',
                'code_generated' => false,
                'base_sku' => 'BASE-002',
            ]))
            ->assertSessionHasErrors('code');

        $this->assertDatabaseCount('products', 1);
    }

    public function test_edit_keeps_automatic_codes_marked_as_generated_and_manual_codes_marked_as_custom(): void
    {
        $admin = User::factory()->create();
        $category = $this->createCategory();
        $automaticProduct = Product::create($this->productPayload($category, null, null, null, [
            'name' => 'Anillo Aura',
            'code' => 'ANI-AUR-001',
            'base_sku' => 'ANI-AUR-001',
        ]));
        $manualProduct = Product::create($this->productPayload($category, null, null, null, [
            'name' => 'Anillo Aura Manual',
            'code' => 'CUSTOM-9',
            'base_sku' => 'CUSTOM-9',
            'slug' => 'anillo-aura-manual',
        ]));

        $this->actingAs($admin)
            ->get(route('products.edit', $automaticProduct))
            ->assertViewHas('codeWasGenerated', true)
            ->assertSee('data-code-generated="true"', false);

        $this->actingAs($admin)
            ->get(route('products.edit', $manualProduct))
            ->assertViewHas('codeWasGenerated', false)
            ->assertSee('data-code-generated="false"', false);
    }

    public function test_product_image_preview_does_not_interpolate_untrusted_values_as_html(): void
    {
        $admin = User::factory()->create();
        $category = $this->createCategory();
        $maliciousText = '"><script>alert(1)</script>';
        $product = Product::create($this->productPayload($category, null, null, null, [
            'name' => $maliciousText,
        ]));
        $this->createProductImage($product, 0, [
            'path' => 'safe-preview.jpg',
            'alt' => $maliciousText,
        ]);

        $html = $this->actingAs($admin)->get(route('products.edit', $product))->getContent();

        $this->assertStringNotContainsString('<script>alert(1)</script>', $html);
        $this->assertStringNotContainsString('card.innerHTML', $html);
        $this->assertStringContainsString('textContent', $html);
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function productPayload(
        Category $category,
        ?Collection $collection = null,
        ?Material $material = null,
        ?Size $size = null,
        array $overrides = [],
    ): array {
        $payload = array_merge([
            'category_id' => $category->id,
            'collection_id' => $collection?->id,
            'material_id' => $material?->id,
            'base_size_id' => $size?->id,
            'name' => 'Producto base',
            'model' => 'Modelo base',
            'code' => 'PRD-001',
            'base_sku' => 'SKU-001',
            'subtitle' => 'Subtítulo',
            'short_description' => 'Descripción corta.',
            'karat' => 14,
            'metal_color' => 'Amarillo',
            'price' => '1250.00',
            'sale_price' => null,
            'cost' => '500.00',
            'status' => 'draft',
            'featured' => false,
            'is_new' => false,
            'bestseller' => false,
        ], $overrides);

        $payload['slug'] ??= Str::slug($payload['name']);

        return $payload;
    }

    /**
     * @param  array<string, mixed>  $overrides
     */
    private function createCategory(array $overrides = []): Category
    {
        return Category::create(array_merge([
            'name' => 'Anillos',
            'slug' => 'anillos',
            'status' => true,
            'sort_order' => 1,
        ], $overrides));
    }

    /**
     * @param  array<string, mixed>  $overrides
     */
    private function createCollection(array $overrides = []): Collection
    {
        return Collection::create(array_merge([
            'name' => 'Eternal',
            'slug' => 'eternal',
            'status' => true,
        ], $overrides));
    }

    /**
     * @param  array<string, mixed>  $overrides
     */
    private function createMaterial(array $overrides = []): Material
    {
        return Material::create(array_merge([
            'name' => 'Oro',
            'slug' => 'oro',
            'status' => true,
        ], $overrides));
    }

    /**
     * @param  array<string, mixed>  $overrides
     */
    private function createSize(Category $category, array $overrides = []): Size
    {
        $size = Size::create(array_merge([
            'name' => '7',
            'sort_order' => 1,
            'status' => true,
        ], $overrides));
        $size->categories()->attach($category);

        return $size;
    }

    /**
     * @param  array<string, mixed>  $overrides
     */
    private function createProductImage(Product $product, int $sortOrder = 0, array $overrides = []): ProductImage
    {
        return ProductImage::create(array_merge([
            'product_id' => $product->id,
            'path' => "products/{$product->id}-{$sortOrder}.jpg",
            'alt' => 'Imagen de prueba',
            'sort_order' => $sortOrder,
            'is_cover' => false,
        ], $overrides));
    }

    /**
     * @param  array<string, mixed>  $overrides
     */
    private function createStone(array $overrides = []): Stone
    {
        return Stone::create(array_merge([
            'name' => 'Diamante',
            'slug' => 'diamante',
            'status' => true,
        ], $overrides));
    }
}
