<?php

namespace Tests\Unit;

use App\Models\PaymentMethod;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Tests\TestCase;

class PaymentMethodTest extends TestCase
{
    public function test_payment_method_matches_its_model_configuration(): void
    {
        $paymentMethod = new PaymentMethod;

        $this->assertContains(HasFactory::class, class_uses_recursive(PaymentMethod::class));
        $this->assertNotContains(SoftDeletes::class, class_uses_recursive(PaymentMethod::class));
        $this->assertSame([
            'type',
            'name',
            'provider',
            'enabled',
            'instructions',
            'account_holder',
            'bank_name',
            'account_number',
            'clabe',
        ], $paymentMethod->getFillable());
        $this->assertSame('boolean', $paymentMethod->getCasts()['enabled']);
    }

    public function test_payment_method_defines_its_payments_relationship(): void
    {
        $payments = (new PaymentMethod)->payments();

        $this->assertInstanceOf(HasMany::class, $payments);
        $this->assertSame('payment_method_id', $payments->getForeignKeyName());
    }
}
