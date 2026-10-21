<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'type',
    'name',
    'provider',
    'enabled',
    'instructions',
    'account_holder',
    'bank_name',
    'account_number',
    'clabe',
])]
class PaymentMethod extends Model
{
    use HasFactory;

    protected function casts(): array
    {
        return [
            'enabled' => 'boolean',
        ];
    }

    public function payments(): HasMany
    {
        return $this->hasMany(OrderPayment::class, 'payment_method_id');
    }
}
