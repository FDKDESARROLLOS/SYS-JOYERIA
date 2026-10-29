<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'product_id',
    'stone_id',
    'position',
    'role',
    'distribution',
    'sort_order',
    'shape',
    'carats',
    'quantity',
    'stones_per_side',
    'origin',
    'certification',
    'color',
    'clarity',
    'cut',
])]
class ProductStone extends Model
{
    public const ROLE_MAIN = 'main';

    public const ROLE_ACCENT = 'accent';

    public const ROLE_HALO = 'halo';

    public const ROLE_PAVE = 'pave';

    public const ROLE_OTHER = 'other';

    public const DISTRIBUTION_SINGLE = 'single';

    public const DISTRIBUTION_SYMMETRIC = 'symmetric';

    public const DISTRIBUTION_DISTRIBUTED = 'distributed';

    use HasFactory;

    /**
     * @return array<int, string>
     */
    public static function roles(): array
    {
        return [
            self::ROLE_MAIN,
            self::ROLE_ACCENT,
            self::ROLE_HALO,
            self::ROLE_PAVE,
            self::ROLE_OTHER,
        ];
    }

    /**
     * @return array<int, string>
     */
    public static function distributions(): array
    {
        return [
            self::DISTRIBUTION_SINGLE,
            self::DISTRIBUTION_SYMMETRIC,
            self::DISTRIBUTION_DISTRIBUTED,
        ];
    }

    public function resolvedRole(): string
    {
        return $this->role ?? match ($this->position) {
            'central' => self::ROLE_MAIN,
            'lateral' => self::ROLE_ACCENT,
            default => self::ROLE_OTHER,
        };
    }

    public function resolvedDistribution(): string
    {
        return $this->distribution ?? match ($this->position) {
            'central' => self::DISTRIBUTION_SINGLE,
            'lateral' => self::DISTRIBUTION_SYMMETRIC,
            default => self::DISTRIBUTION_DISTRIBUTED,
        };
    }

    protected function casts(): array
    {
        return [
            'carats' => 'decimal:2',
            'quantity' => 'integer',
            'stones_per_side' => 'integer',
            'sort_order' => 'integer',
        ];
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'product_id');
    }

    public function stone(): BelongsTo
    {
        return $this->belongsTo(Stone::class, 'stone_id');
    }
}
