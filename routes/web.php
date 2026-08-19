<?php

use App\Http\Controllers\AiBuilderController;
use App\Http\Controllers\BuildController;
use App\Http\Controllers\ProductBrowserController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::get('/', function () {
    return view('welcome');
});

Route::get('/pc-build', [BuildController::class, 'index']);
Route::get('/ai-build', [AiBuilderController::class, 'index']);
Route::post('/ai-build/process', [AiBuilderController::class, 'process']);
Route::get('/ai-build/result', [AiBuilderController::class, 'result'])->name('ai-build.result');
Route::get('/browser-{slug}', [ProductBrowserController::class, 'showByCategory']);

// Admin SPA entry points - Vue Router handles auth
Route::get('/admin/dashboard', function () {
    return view('welcome');
})->name('admin.dashboard');

Route::get('/admin/orders', function () {
    return view('welcome');
})->name('admin.orders');

Route::get('/admin/products', function () {
    return view('welcome');
})->name('admin.products');

// Catch-all: trả về SPA cho các route do Vue Router xử lý (vd /browse, /home, /checkout)
// Phải đặt sau tất cả routes khác
// Regex ^(?!api/) đảm bảo không match /api/* requests
Route::get('/{any}', function () {
    return view('welcome');
})->where('any', '^(?!api/).*$');
