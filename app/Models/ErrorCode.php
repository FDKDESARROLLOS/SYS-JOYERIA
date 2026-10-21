<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'code',
    'module',
    'title',
    'description',
    'severity',
    'status',
])]
class ErrorCode extends Model
{
    use HasFactory;

    protected function casts(): array
    {
        return [
            'status' => 'boolean',
        ];
    }

    public function logs(): HasMany
    {
        return $this->hasMany(ErrorLog::class, 'error_code_id');
    }
}
