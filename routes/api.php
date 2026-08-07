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
Route::get('/products', [ProductController::class, 'index']);
Route::get('/products/sales', [ProductController::class, 'sales']);
Route::get('/products/newest', [ProductController::class, 'newest']);
Route::get('/products/{product}', [ProductController::class, 'show']);

// Products - Backend (CRUD)
Route::post('/products', [ProductController::class, 'store']);
Route::put('/products/{product}', [ProductController::class, 'update']);
Route::delete('/products/{product}', [ProductController::class, 'destroy']);

Route::post('/orders', [OrderController::class, 'store']);

// Admin Auth routes
Route::post('/admin/login', [AdminAuthController::class, 'login']);
Route::post('/admin/logout', [AdminAuthController::class, 'logout']);

// Admin routes
Route::get('/admin/stats', [AdminOrderController::class, 'stats']);
Route::get('/admin/orders', [AdminOrderController::class, 'index']);
Route::get('/admin/orders/{order}', [AdminOrderController::class, 'show']);
Route::patch('/admin/orders/{order}/status', [AdminOrderController::class, 'updateStatus']);
Route::delete('/admin/orders/{order}', [AdminOrderController::class, 'cancel']);

// PC Builder routes
Route::post('/pc-builder/validate', [PCBuilderController::class, 'validate']);
Route::post('/pc-builder/recommend', [PCBuilderController::class, 'recommend']);

// Chat AI routes
Route::post('/chat', [ChatController::class, 'sendMessage']);

// Prebuilt Config routes - Frontend (GET)
Route::get('/prebuilt-configs', [PrebuiltConfigController::class, 'index']);
Route::get('/prebuilt-configs/{id}', [PrebuiltConfigController::class, 'show']);

// Prebuilt Config routes - Backend (CRUD + toggle)
Route::post('/prebuilt-configs', [PrebuiltConfigController::class, 'store']);
Route::put('/prebuilt-configs/{id}', [PrebuiltConfigController::class, 'update']);
Route::delete('/prebuilt-configs/{id}', [PrebuiltConfigController::class, 'destroy']);
Route::patch('/prebuilt-configs/{id}/toggle-active', [PrebuiltConfigController::class, 'toggleActive']);
Route::patch('/prebuilt-configs/{id}/toggle-featured', [PrebuiltConfigController::class, 'toggleFeatured']);

