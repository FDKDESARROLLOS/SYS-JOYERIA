<?php

use App\Http\Controllers\AdminUserController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\PasswordResetController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\CollectionController;
use App\Http\Controllers\ErrorCodeController;
use App\Http\Controllers\ErrorLogController;
use App\Http\Controllers\InventoryController;
use App\Http\Controllers\MaterialController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\ProductVariantController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ShippingSettingController;
use App\Http\Controllers\SizeController;
use App\Http\Controllers\StoneController;
use App\Http\Controllers\StoreSettingController;
use Illuminate\Support\Facades\Route;

Route::middleware('guest')->group(function () {
    Route::get('/login', [LoginController::class, 'create'])
        ->name('login');

    Route::post('/login', [LoginController::class, 'store'])
        ->name('login.store');

    Route::get('/forgot-password', [PasswordResetController::class, 'request'])
        ->name('password.request');

    Route::post('/forgot-password', [PasswordResetController::class, 'email'])
        ->name('password.email');

    Route::get('/reset-password/{token}', [PasswordResetController::class, 'reset'])
        ->name('password.reset');

    Route::post('/reset-password', [PasswordResetController::class, 'update'])
        ->name('password.update');
});

Route::post('/logout', [LoginController::class, 'destroy'])
    ->middleware('auth')
    ->name('logout');

Route::middleware('auth')->group(function () {
    Route::view('/', 'dashboard')
        ->middleware('admin');

    Route::view('/dashboard', 'dashboard')
        ->middleware('admin')
        ->name('dashboard');

    Route::get('/profile', [ProfileController::class, 'show'])
        ->name('profile');

    Route::patch('/profile', [ProfileController::class, 'update'])
        ->name('profile.update');

    Route::get('/profile/password', [ProfileController::class, 'password'])
        ->name('profile.password');

    Route::post('/profile/password', [ProfileController::class, 'updatePassword'])
        ->name('profile.password.update');

    Route::middleware('admin')->group(function () {
        Route::get('/settings/admin-users', [AdminUserController::class, 'index'])
            ->name('settings.admin-users.index');

        Route::get('/settings/admin-users/create', [AdminUserController::class, 'create'])
            ->name('settings.admin-users.create');

        Route::post('/settings/admin-users', [AdminUserController::class, 'store'])
            ->name('settings.admin-users.store');

        Route::get('/settings/admin-users/{user}/edit', [AdminUserController::class, 'edit'])
            ->name('settings.admin-users.edit');

        Route::patch('/settings/admin-users/{user}', [AdminUserController::class, 'update'])
            ->name('settings.admin-users.update');

        Route::delete('/settings/admin-users/{user}', [AdminUserController::class, 'destroy'])
            ->name('settings.admin-users.destroy');

        Route::resource('categories', CategoryController::class)
            ->except(['show']);

        Route::patch('/products/{product}/images/reorder', [ProductController::class, 'reorderImages'])
            ->name('products.images.reorder');

        Route::get('/products/code-suggestion', [ProductController::class, 'suggestCode'])
            ->name('products.code-suggestion');

        Route::resource('products', ProductController::class)
            ->except(['show', 'destroy']);

        Route::get('/products/deleted', [ProductController::class, 'deleted'])
            ->name('products.deleted');

        Route::patch('/products/{product}/restore', [ProductController::class, 'restore'])
            ->withTrashed()
            ->name('products.restore');

        Route::delete('/products/{product}', [ProductController::class, 'destroy'])
            ->name('products.destroy');

        Route::scopeBindings()->group(function () {
            Route::resource('products.variants', ProductVariantController::class)
                ->except(['show', 'destroy']);

            Route::get('/products/{product}/variants/deleted', [ProductVariantController::class, 'deleted'])
                ->name('products.variants.deleted');

            Route::patch('/products/{product}/variants/{variant}/restore', [ProductVariantController::class, 'restore'])
                ->withTrashed()
                ->name('products.variants.restore');

            Route::delete('/products/{product}/variants/{variant}', [ProductVariantController::class, 'destroy'])
                ->name('products.variants.destroy');
        });

        Route::get('/inventory/stock', [InventoryController::class, 'index'])
            ->name('inventory.stock');

        Route::get('/inventory/movements', [InventoryController::class, 'movements'])
            ->name('inventory.movements.index');

        Route::get('/inventory/movements/create', [InventoryController::class, 'create'])
            ->name('inventory.movements.create');

        Route::post('/inventory/movements', [InventoryController::class, 'storeFromCreate'])
            ->name('inventory.movements.store');

        Route::post('/inventory/movements/{inventoryStock}/context', [InventoryController::class, 'storeContextualMovement'])
            ->name('inventory.movements.context-store');

        Route::get('/inventory/movements/{inventoryStock}', [InventoryController::class, 'show'])
            ->name('inventory.movements.show');

        Route::post('/inventory/movements/{inventoryStock}', [InventoryController::class, 'storeMovement'])
            ->name('inventory.movements.variant-store');

        Route::patch('/inventory/movements/{inventoryStock}/minimum-stock', [InventoryController::class, 'updateMinimumStock'])
            ->name('inventory.minimum-stock.update');
    });
});

Route::middleware(['auth', 'admin'])->group(function () {
    Route::resource('collections', CollectionController::class)->except(['show']);

    Route::resource('materials', MaterialController::class)->except(['show']);

    Route::resource('stones', StoneController::class)->except(['show']);

    Route::resource('sizes', SizeController::class)->except(['show']);

    Route::view('/orders', 'orders.index')->name('orders.index');

    Route::view('/orders/{orderNumber}', 'orders.show')->name('orders.show');

    Route::view('/orders/{orderNumber}/dispatch', 'orders.dispatch')->name('orders.dispatch');

    Route::get('/settings/store', [StoreSettingController::class, 'edit'])
        ->name('settings.store');

    Route::patch('/settings/store', [StoreSettingController::class, 'update'])
        ->name('settings.store.update');

    Route::get('/settings/shipping', [ShippingSettingController::class, 'edit'])
        ->name('settings.shipping');

    Route::patch('/settings/shipping', [ShippingSettingController::class, 'update'])
        ->name('settings.shipping.update');
});

Route::middleware(['auth', 'technical.access'])->group(function () {
    Route::get('/settings/errors', [ErrorCodeController::class, 'index'])
        ->name('settings.errors');

    Route::get('/settings/errors/create', [ErrorCodeController::class, 'create'])
        ->name('settings.errors.create');

    Route::post('/settings/errors', [ErrorCodeController::class, 'store'])
        ->name('settings.errors.store');

    Route::get('/settings/errors/{errorCode}/edit', [ErrorCodeController::class, 'edit'])
        ->name('settings.errors.edit');

    Route::patch('/settings/errors/{errorCode}', [ErrorCodeController::class, 'update'])
        ->name('settings.errors.update');

    Route::patch('/settings/errors/{errorCode}/status', [ErrorCodeController::class, 'toggleStatus'])
        ->name('settings.errors.status');

    Route::get('/settings/errors/logs', [ErrorLogController::class, 'index'])
        ->name('settings.errors.logs');

    Route::patch('/settings/errors/logs/{errorLog}/status', [ErrorLogController::class, 'updateStatus'])
        ->name('settings.errors.logs.status');
});

if (app()->environment('local')) {
    Route::get('/preview/errors/403', function () {
        abort(403);
    });

    Route::get('/preview/errors/404', function () {
        abort(404);
    });

    Route::get('/preview/errors/419', function () {
        abort(419);
    });

    Route::get('/preview/errors/500', function () {
        abort(500);
    });
}
