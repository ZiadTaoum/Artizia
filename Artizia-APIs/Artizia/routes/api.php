<?php
// routes/api.php

use App\Http\Controllers\Api\AdminController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\CustomerController;
use App\Http\Controllers\Api\VendorController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
*/

// Public routes
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

// Public product browsing
Route::get('/products', [CustomerController::class, 'products']);
Route::get('/products/{slug}', [CustomerController::class, 'product']);
Route::get('/vendors', [CustomerController::class, 'vendors']);
Route::get('/vendors/{id}', [CustomerController::class, 'vendor']);
Route::get('/categories', [CustomerController::class, 'categories']);
Route::get('/categories/{slug}', [CustomerController::class, 'category']);

// Protected routes
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/user', [AuthController::class, 'user']);

    // Admin routes
    Route::middleware('role:admin')->prefix('admin')->group(function () {
        Route::get('/dashboard', [AdminController::class, 'dashboard']);
        Route::get('/users', [AdminController::class, 'users']);
        Route::get('/vendors', [AdminController::class, 'vendors']);
        Route::post('/vendors/{vendor}/approve', [AdminController::class, 'approveVendor']);
        Route::post('/vendors/{vendor}/reject', [AdminController::class, 'rejectVendor']);
        Route::get('/products', [AdminController::class, 'products']);
        Route::post('/products/{product}/toggle-status', [AdminController::class, 'toggleProductStatus']);
    });

    // Vendor routes
    Route::middleware('role:vendor')->prefix('vendor')->group(function () {
        Route::get('/dashboard', [VendorController::class, 'dashboard']);
        Route::get('/products', [VendorController::class, 'products']);
        Route::post('/products', [VendorController::class, 'storeProduct']);
        Route::get('/products/{product}', [VendorController::class, 'showProduct']);
        Route::put('/products/{product}', [VendorController::class, 'updateProduct']);
        Route::delete('/products/{product}', [VendorController::class, 'deleteProduct']);
        Route::get('/categories', [VendorController::class, 'categories']);
        Route::get('/profile', [VendorController::class, 'profile']);
        Route::put('/profile', [VendorController::class, 'updateProfile']);
    });

    // Customer routes
    Route::middleware('role:customer')->prefix('customer')->group(function () {
        Route::get('/dashboard', [CustomerController::class, 'dashboard']);
        // Add customer-specific routes like wishlist, orders, etc.
    });
});