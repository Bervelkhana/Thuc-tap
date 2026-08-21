<?php

use App\Http\Controllers\Api\CategoryController;
use App\Http\Controllers\Api\OrderController;
use App\Http\Controllers\Api\ProductController;
use App\Http\Controllers\Api\AdminOrderController;
use App\Http\Controllers\Api\AdminAuthController;
use App\Http\Controllers\Api\PCBuilderController;
use App\Http\Controllers\Api\ChatController;
use App\Http\Controllers\Api\PrebuiltConfigController;
use Illuminate\Support\Facades\Route;

// Test endpoint
Route::get('/test', function () {
    return response()->json(['status' => 'ok', 'message' => 'API working']);
});

Route::get('/categories', [CategoryController::class, 'index']);

// Products - Frontend (GET only)
Route::get('/products/search', [ProductController::class, 'search']);
Route::get('/products/sales', [ProductController::class, 'sales']);
Route::get('/products/newest', [ProductController::class, 'newest']);
Route::get('/products/recent-discounts', [ProductController::class, 'recentDiscounts']);
Route::get('/products', [ProductController::class, 'index']);
Route::get('/products/{product}', [ProductController::class, 'show']);

// Products - Backend (CRUD)
Route::middleware(['auth:sanctum', 'role:admin,super_admin'])->group(function () {
    Route::post('/products', [ProductController::class, 'store']);
    Route::put('/products/{product}', [ProductController::class, 'update']);
    Route::delete('/products/{product}', [ProductController::class, 'destroy']);
});

Route::post('/orders', [OrderController::class, 'store'])->middleware('auth:sanctum');

// Admin Auth routes
Route::post('/admin/login', [AdminAuthController::class, 'login'])->middleware('api');
Route::post('/admin/logout', [AdminAuthController::class, 'logout'])->middleware('auth:sanctum');
Route::get('/admin/me', [AdminAuthController::class, 'me'])->middleware('auth:sanctum', 'role:admin,super_admin');

// Admin routes
Route::middleware(['auth:sanctum', 'role:admin,super_admin'])->group(function () {
    Route::get('/admin/stats', [AdminOrderController::class, 'stats']);
    Route::get('/admin/orders', [AdminOrderController::class, 'index']);
    Route::get('/admin/orders/{order}', [AdminOrderController::class, 'show']);
    Route::patch('/admin/orders/{order}', [AdminOrderController::class, 'updateStatus']);
    Route::patch('/admin/orders/{order}/status', [AdminOrderController::class, 'updateStatus']);
    Route::post('/admin/orders/{order}/cancel', [AdminOrderController::class, 'cancel']);
    Route::delete('/admin/orders/{order}', [AdminOrderController::class, 'destroy']);
});

// PC Builder routes
Route::get('/pc-builder/components', [PCBuilderController::class, 'getComponentsByCategory']);
Route::get('/pc-builder/categories', [PCBuilderController::class, 'getBuildCategories']);
Route::get('/pc-builder/search', [PCBuilderController::class, 'searchComponents']);
Route::get('/pc-builder/compatible-mainboards', [PCBuilderController::class, 'getCompatibleMainboards']);
Route::get('/pc-builder/compatible-cases', [PCBuilderController::class, 'getCompatibleCases']);
Route::post('/pc-builder/validate', [PCBuilderController::class, 'checkCompatibility']);
Route::post('/pc-builder/recommend', [PCBuilderController::class, 'recommend']);

// Chat AI routes
Route::post('/chat', [ChatController::class, 'sendMessage']);
Route::post('/chat/stream', [ChatController::class, 'streamMessage']);

// Prebuilt Config routes - Frontend (GET)
Route::get('/prebuilt-configs', [PrebuiltConfigController::class, 'index']);
Route::get('/prebuilt-configs/{id}', [PrebuiltConfigController::class, 'show']);

// Prebuilt Config routes - Backend (CRUD + toggle)
Route::middleware(['auth:sanctum', 'role:admin,super_admin'])->group(function () {
    Route::post('/prebuilt-configs', [PrebuiltConfigController::class, 'store']);
    Route::put('/prebuilt-configs/{id}', [PrebuiltConfigController::class, 'update']);
    Route::delete('/prebuilt-configs/{id}', [PrebuiltConfigController::class, 'destroy']);
    Route::patch('/prebuilt-configs/{id}/toggle-active', [PrebuiltConfigController::class, 'toggleActive']);
    Route::patch('/prebuilt-configs/{id}/toggle-featured', [PrebuiltConfigController::class, 'toggleFeatured']);
});

