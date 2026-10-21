<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'error_code_id',
    'user_id',
    'source',
    'module',
    'action',
    'message',
    'status',
    'entity_type',
    'entity_id',
    'route',
    'exception_type',
    'file',
    'line',
    'context',
    'occurred_at',
])]
class ErrorLog extends Model
{
    use HasFactory;

    protected function casts(): array
    {
        return [
            'entity_id' => 'integer',
            'line' => 'integer',
            'context' => 'array',
            'occurred_at' => 'datetime',
        ];
    }

    public function errorCode(): BelongsTo
    {
        return $this->belongsTo(ErrorCode::class, 'error_code_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
