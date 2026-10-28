<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\InventoryStock;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\ShippingSetting;
use App\Models\Size;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Routing\Route;
use Tests\TestCase;

class ShippingQuoteFeatureTest extends TestCase
{
    use RefreshDatabase;

    public function test_valid_mx_request_returns_server_side_quote(): void
    {
        $this->createVariant('ANI-AUR-001-BLANCO', '2500.00');
        $this->createShippingSetting();

        $response = $this->postJson('/api/v1/shipping/quote', [
            'country_code' => 'MX',
            'items' => [
                ['sku' => 'ANI-AUR-001-BLANCO', 'quantity' => 1],
            ],
        ]);

        $response
            ->assertOk()
            ->assertJsonPath('data.available', true)
            ->assertJsonPath('data.country_code', 'MX')
            ->assertJsonPath('data.method.name', 'Envío estándar')
            ->assertJsonPath('data.method.estimated_time', '3 a 6 días hábiles')
            ->assertJsonPath('data.subtotal.amount', '2500.00')
            ->assertJsonPath('data.subtotal.currency', 'MXN')
            ->assertJsonPath('data.shipping.amount', '150.00')
            ->assertJsonPath('data.shipping.currency', 'MXN')
            ->assertJsonPath('data.free_shipping', false);
    }

    public function test_shipping_cost_comes_from_shipping_setting_and_ignores_client_cost(): void
    {
        $this->createVariant('SKU-001', '1000.00');
        $this->createShippingSetting(['shipping_cost' => '125.50']);

        $response = $this->postJson('/api/v1/shipping/quote', [
            'country_code' => 'MX',
            'shipping_cost' => '0.01',
            'items' => [['sku' => 'SKU-001', 'quantity' => 1]],
        ]);

        $response
            ->assertOk()
            ->assertJsonPath('data.shipping.amount', '125.50')
            ->assertJsonMissingPath('data.shipping_cost');
    }

    public function test_sale_price_is_used_for_the_subtotal(): void
    {
        $this->createVariant('ANI-AUR-001-DIA-10K', '6000.00', '1000.00');
        $this->createShippingSetting();

        $this->postJson('/api/v1/shipping/quote', [
            'country_code' => 'MX',
            'items' => [['sku' => 'ANI-AUR-001-DIA-10K', 'quantity' => 1]],
        ])
            ->assertOk()
            ->assertJsonPath('data.subtotal.amount', '1000.00');
    }

    public function test_normal_price_is_used_when_there_is_no_valid_sale(): void
    {
        $this->createVariant('SKU-NORMAL', '6000.00', '0.00');
        $this->createShippingSetting();

        $this->postJson('/api/v1/shipping/quote', [
            'country_code' => 'MX',
            'items' => [['sku' => 'SKU-NORMAL', 'quantity' => 1]],
        ])
            ->assertOk()
            ->assertJsonPath('data.subtotal.amount', '6000.00');
    }

    public function test_multiple_items_and_quantity_are_calculated_without_float_rounding(): void
    {
        $this->createVariant('SKU-ONE', '10.10');
        $this->createVariant('SKU-TWO', '20.20');
        $this->createShippingSetting();

        $this->postJson('/api/v1/shipping/quote', [
            'country_code' => 'MX',
            'items' => [
                ['sku' => 'SKU-ONE', 'quantity' => 3],
                ['sku' => 'SKU-TWO', 'quantity' => 2],
            ],
        ])
            ->assertOk()
            ->assertJsonPath('data.subtotal.amount', '70.70');
    }

    public function test_duplicate_skus_are_rejected(): void
    {
        $this->postJson('/api/v1/shipping/quote', [
            'country_code' => 'MX',
            'items' => [
                ['sku' => 'SKU-001', 'quantity' => 1],
                ['sku' => 'SKU-001', 'quantity' => 2],
            ],
        ])
            ->assertStatus(422)
            ->assertJsonValidationErrors('items.1.sku');
    }

    public function test_invalid_quantities_are_rejected(): void
    {
        foreach ([0, -1, 1.5, 'invalid'] as $quantity) {
            $this->postJson('/api/v1/shipping/quote', [
                'country_code' => 'MX',
                'items' => [['sku' => 'SKU-001', 'quantity' => $quantity]],
            ])->assertStatus(422)->assertJsonValidationErrors('items.0.quantity');
        }
    }

    public function test_non_mx_country_is_rejected(): void
    {
        $this->postJson('/api/v1/shipping/quote', [
            'country_code' => 'US',
            'items' => [['sku' => 'SKU-001', 'quantity' => 1]],
        ])
            ->assertStatus(422)
            ->assertJsonValidationErrors('country_code');
    }

    public function test_missing_or_unavailable_sku_is_rejected_without_partial_quote(): void
    {
        $this->createShippingSetting();

        $this->postJson('/api/v1/shipping/quote', [
            'country_code' => 'MX',
            'items' => [['sku' => 'SKU-MISSING', 'quantity' => 1]],
        ])
            ->assertStatus(422)
            ->assertJsonValidationErrors('items');
    }

    public function test_inactive_variant_and_product_are_not_commercially_valid(): void
    {
        $inactiveVariant = $this->createVariant('SKU-INACTIVE-VARIANT', '100.00');
        $inactiveVariant->update(['status' => 'draft']);

        $this->createVariant('SKU-INACTIVE-PRODUCT', '100.00', null, [
            'product_status' => 'draft',
        ]);
        $this->createShippingSetting();

        foreach (['SKU-INACTIVE-VARIANT', 'SKU-INACTIVE-PRODUCT'] as $sku) {
            $this->postJson('/api/v1/shipping/quote', [
                'country_code' => 'MX',
                'items' => [['sku' => $sku, 'quantity' => 1]],
            ])->assertStatus(422)->assertJsonValidationErrors('items');
        }
    }

    public function test_soft_deleted_variant_is_not_commercially_valid(): void
    {
        $variant = $this->createVariant('SKU-DELETED', '100.00');
        $variant->delete();
        $this->createShippingSetting();

        $this->postJson('/api/v1/shipping/quote', [
            'country_code' => 'MX',
            'items' => [['sku' => 'SKU-DELETED', 'quantity' => 1]],
        ])
            ->assertStatus(422)
            ->assertJsonValidationErrors('items');
    }

    public function test_disabled_shipping_returns_an_explicit_unavailable_response(): void
    {
        $this->createVariant('SKU-001', '1000.00');
        $this->createShippingSetting(['enabled' => false]);

        $this->postJson('/api/v1/shipping/quote', [
            'country_code' => 'MX',
            'items' => [['sku' => 'SKU-001', 'quantity' => 1]],
        ])
            ->assertOk()
            ->assertJsonPath('data.available', false)
            ->assertJsonPath('data.shipping', null)
            ->assertJsonPath('data.method', null)
            ->assertJsonPath('data.free_shipping', false);
    }

    public function test_free_shipping_rules_are_reused(): void
    {
        $this->createVariant('SKU-001', '1000.00');
        $this->createShippingSetting([
            'free_shipping_enabled' => true,
            'free_shipping_type' => 'minimum',
            'free_shipping_minimum' => '1000.00',
        ]);

        $this->postJson('/api/v1/shipping/quote', [
            'country_code' => 'MX',
            'items' => [['sku' => 'SKU-001', 'quantity' => 1]],
        ])
            ->assertOk()
            ->assertJsonPath('data.shipping.amount', '0.00')
            ->assertJsonPath('data.free_shipping', true);
    }

    public function test_free_shipping_minimum_not_reached_keeps_fixed_cost(): void
    {
        $this->createVariant('SKU-001', '999.99');
        $this->createShippingSetting([
            'free_shipping_enabled' => true,
            'free_shipping_type' => 'minimum',
            'free_shipping_minimum' => '1000.00',
        ]);

        $this->postJson('/api/v1/shipping/quote', [
            'country_code' => 'MX',
            'items' => [['sku' => 'SKU-001', 'quantity' => 1]],
        ])
            ->assertOk()
            ->assertJsonPath('data.shipping.amount', '150.00')
            ->assertJsonPath('data.free_shipping', false);
    }

    public function test_quote_does_not_change_inventory_or_create_reservations(): void
    {
        $variant = $this->createVariant('SKU-001', '1000.00', null, [
            'current_stock' => 5,
            'reserved_stock' => 2,
        ]);
        $this->createShippingSetting();

        $this->postJson('/api/v1/shipping/quote', [
            'country_code' => 'MX',
            'items' => [['sku' => 'SKU-001', 'quantity' => 3]],
        ])->assertOk();

        $this->assertDatabaseHas('inventory_stocks', [
            'product_variant_id' => $variant->id,
            'current_stock' => 5,
            'reserved_stock' => 2,
        ]);
        $this->assertDatabaseCount('inventory_reservations', 0);
    }

    public function test_shipping_quote_route_is_rate_limited(): void
    {
        $route = app('router')->getRoutes()->getByName('api.v1.shipping.quote');

        $this->assertInstanceOf(Route::class, $route);
        $this->assertContains('throttle:60,1', $route->middleware());
    }

    /**
     * @param  array{product_status?: string, current_stock?: int, reserved_stock?: int}  $options
     */
    private function createVariant(
        string $sku,
        string $price,
        ?string $salePrice = null,
        array $options = [],
    ): ProductVariant {
        $category = Category::create([
            'name' => 'Categoría '.$sku,
            'slug' => strtolower($sku),
            'status' => true,
        ]);
        $size = Size::create([
            'name' => 'Talla '.$sku,
            'status' => true,
        ]);
        $product = Product::create([
            'category_id' => $category->id,
            'name' => 'Producto '.$sku,
            'model' => 'Modelo '.$sku,
            'code' => 'CODE-'.$sku,
            'slug' => 'producto-'.strtolower($sku),
            'price' => $price,
            'status' => $options['product_status'] ?? 'active',
        ]);
        $variant = ProductVariant::create([
            'product_id' => $product->id,
            'size_id' => $size->id,
            'sku' => $sku,
            'price' => $price,
            'sale_price' => $salePrice,
            'status' => 'active',
        ]);
        InventoryStock::create([
            'product_variant_id' => $variant->id,
            'current_stock' => $options['current_stock'] ?? 5,
            'reserved_stock' => $options['reserved_stock'] ?? 0,
        ]);

        return $variant;
    }

    /**
     * @param  array<string, mixed>  $overrides
     */
    private function createShippingSetting(array $overrides = []): ShippingSetting
    {
        return ShippingSetting::create(array_merge([
            'enabled' => true,
            'method_name' => 'Envío estándar',
            'estimated_time' => '3 a 6 días hábiles',
            'shipping_cost' => '150.00',
            'free_shipping_enabled' => false,
        ], $overrides));
    }
}
