<?php

use App\Http\Controllers\Api\V1\ProductController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->name('api.v1.')->group(function (): void {
    Route::get('/products', [ProductController::class, 'index'])
        ->name('products.index');

    Route::get('/products/{slug}', [ProductController::class, 'show'])
        ->where('slug', '[A-Za-z0-9-]+')
        ->name('products.show');
});
