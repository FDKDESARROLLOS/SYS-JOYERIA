<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

#[Fillable([
    'product_id',
    'path',
    'alt',
    'sort_order',
    'is_cover',
])]
class ProductImage extends Model
{
    use HasFactory;

    protected function casts(): array
    {
        return [
            'sort_order' => 'integer',
            'is_cover' => 'boolean',
        ];
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'product_id');
    }

    public function publicUrl(): string
    {
        $path = $this->normalizedPath();
        $configuredUploadUrl = trim((string) config('aurea.uploads.products_url', 'uploads/products'), '/');

        if (str_starts_with($path, $configuredUploadUrl.'/')) {
            return asset($path);
        }

        if (str_starts_with($path, 'public/')) {
            $path = Str::after($path, 'public/');
        }

        if (str_starts_with($path, 'uploads/products/') || str_starts_with($path, 'storage/')) {
            return asset($path);
        }

        if (str_starts_with($path, 'products/')) {
            return asset('storage/'.$path);
        }

        return asset($configuredUploadUrl.'/'.basename($path));
    }

    public function publicFilePath(): string
    {
        $path = $this->normalizedPath();
        $configuredUploadUrl = trim((string) config('aurea.uploads.products_url', 'uploads/products'), '/');

        if (str_starts_with($path, $configuredUploadUrl.'/')) {
            return public_path($path);
        }

        if (str_starts_with($path, 'public/')) {
            $path = Str::after($path, 'public/');
        }

        if (str_starts_with($path, 'uploads/products/') || str_starts_with($path, 'storage/')) {
            return public_path($path);
        }

        if (str_starts_with($path, 'products/')) {
            return public_path('storage/'.$path);
        }

        return rtrim((string) config('aurea.uploads.products_path', public_path('uploads/products')), DIRECTORY_SEPARATOR)
            .DIRECTORY_SEPARATOR.basename($path);
    }

    public function hasPublicFile(): bool
    {
        return is_file($this->publicFilePath());
    }

    private function normalizedPath(): string
    {
        $path = ltrim(str_replace('\\', '/', (string) $this->path), '/');

        while (str_starts_with($path, 'uploads/products/uploads/products/')) {
            $path = Str::after($path, 'uploads/products/');
        }

        return $path;
    }
}
