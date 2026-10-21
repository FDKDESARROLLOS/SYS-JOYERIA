<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

#[Fillable([
    'store_name',
    'email',
    'phone',
    'whatsapp',
    'address',
    'city',
    'state',
    'postal_code',
    'country',
])]
class StoreSetting extends Model
{
    use HasFactory;
}
