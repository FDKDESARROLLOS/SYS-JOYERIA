<?php

use App\Http\Controllers\AdminUserController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\PasswordResetController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\CollectionController;
use App\Http\Controllers\MaterialController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\SizeController;
use App\Http\Controllers\StoneController;
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
    Route::view('/', 'dashboard');

    Route::view('/dashboard', 'dashboard')
        ->name('dashboard');

    Route::get('/profile', [ProfileController::class, 'show'])
        ->name('profile');

    Route::patch('/profile', [ProfileController::class, 'update'])
        ->name('profile.update');

    Route::get('/profile/password', [ProfileController::class, 'password'])
        ->name('profile.password');

    Route::post('/profile/password', [ProfileController::class, 'updatePassword'])
        ->name('profile.password.update');

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
});

Route::view('/products', 'products.index')->name('products.index');

Route::view('/products/create', 'products.create')->name('products.create');

Route::view('/products/{id}/edit', 'products.edit')->name('products.edit');

Route::view('/products/{id}/variants', 'products.variants.index')->name('products.variants.index');

Route::view('/products/{id}/variants/create', 'products.variants.create')->name('products.variants.create');

Route::view('/products/{id}/variants/{variant}/edit', 'products.variants.edit')->name('products.variants.edit');

Route::resource('collections', CollectionController::class)->except(['show']);

Route::resource('materials', MaterialController::class)->except(['show']);

Route::resource('stones', StoneController::class)->except(['show']);

Route::resource('sizes', SizeController::class)->except(['show']);

Route::view('/inventory/stock', 'inventory.stock')->name('inventory.stock');

Route::view('/inventory/movements', 'inventory.movements')->name('inventory.movements');

Route::view('/inventory/movements/create', 'inventory.movements.create')->name('inventory.movements.create');

Route::view('/orders', 'orders.index')->name('orders.index');

Route::view('/orders/{orderNumber}', 'orders.show')->name('orders.show');

Route::view('/orders/{orderNumber}/dispatch', 'orders.dispatch')->name('orders.dispatch');

Route::view('/settings/store', 'settings.store')->name('settings.store');

Route::view('/settings/payments', 'settings.payments')->name('settings.payments');

Route::view('/settings/shipping', 'settings.shipping')->name('settings.shipping');

Route::view('/settings/errors', 'settings.errors')->name('settings.errors');

Route::view('/settings/errors/logs', 'settings.error-logs')->name('settings.errors.logs');

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
