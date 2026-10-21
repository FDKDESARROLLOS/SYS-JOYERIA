<?php

namespace Tests\Unit;

use App\Models\ShippingSetting;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Tests\TestCase;

class ShippingSettingTest extends TestCase
{
    public function test_shipping_setting_matches_its_model_configuration(): void
    {
        $shippingSetting = new ShippingSetting;

        $this->assertContains(HasFactory::class, class_uses_recursive(ShippingSetting::class));
        $this->assertNotContains(SoftDeletes::class, class_uses_recursive(ShippingSetting::class));
        $this->assertSame([
            'enabled',
            'method_name',
            'estimated_time',
            'shipping_cost',
            'free_shipping_enabled',
            'free_shipping_type',
            'free_shipping_minimum',
            'notes',
        ], $shippingSetting->getFillable());
        $this->assertSame('boolean', $shippingSetting->getCasts()['enabled']);
        $this->assertSame('decimal:2', $shippingSetting->getCasts()['shipping_cost']);
        $this->assertSame('boolean', $shippingSetting->getCasts()['free_shipping_enabled']);
        $this->assertSame('decimal:2', $shippingSetting->getCasts()['free_shipping_minimum']);
    }

    public function test_shipping_setting_does_not_define_relationships(): void
    {
        $this->assertFalse(method_exists(ShippingSetting::class, 'products'));
        $this->assertFalse(method_exists(ShippingSetting::class, 'orders'));
        $this->assertFalse(method_exists(ShippingSetting::class, 'payments'));
    }
}
