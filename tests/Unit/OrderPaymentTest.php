<?php

namespace Tests\Unit;

use App\Models\OrderPayment;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class OrderPaymentTest extends TestCase
{
    use RefreshDatabase;

    public function test_order_payment_matches_its_model_configuration(): void
    {
        $payment = new OrderPayment;

        $this->assertContains(HasFactory::class, class_uses_recursive(OrderPayment::class));
        $this->assertNotContains(SoftDeletes::class, class_uses_recursive(OrderPayment::class));
        $this->assertSame([
            'order_id',
            'payment_method_id',
            'verified_by',
            'status',
            'reference',
            'proof_path',
            'amount',
            'currency',
            'paid_at',
            'verified_at',
            'rejection_reason',
        ], $payment->getFillable());
        $this->assertSame('decimal:2', $payment->getCasts()['amount']);
        $this->assertSame('datetime', $payment->getCasts()['paid_at']);
        $this->assertSame('datetime', $payment->getCasts()['verified_at']);
        $this->assertContains('status', $payment->getFillable());
        $this->assertArrayNotHasKey('status', $payment->getCasts());
    }

    public function test_order_payment_defines_its_relationships(): void
    {
        $payment = new OrderPayment;

        $order = $payment->order();
        $paymentMethod = $payment->paymentMethod();
        $verifier = $payment->verifier();

        $this->assertInstanceOf(BelongsTo::class, $order);
        $this->assertSame('order_id', $order->getForeignKeyName());

        $this->assertInstanceOf(BelongsTo::class, $paymentMethod);
        $this->assertSame('payment_method_id', $paymentMethod->getForeignKeyName());

        $this->assertInstanceOf(BelongsTo::class, $verifier);
        $this->assertSame('verified_by', $verifier->getForeignKeyName());
    }

    public function test_verified_by_is_nullable_in_the_database(): void
    {
        $columns = collect(Schema::getColumns('order_payments'))
            ->keyBy('name');

        $this->assertTrue($columns['verified_by']['nullable']);
    }
}
