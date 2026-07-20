<?php

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

// Catch-all: trả về SPA cho các route do Vue Router xử lý (vd /checkout)
Route::get('/{any}', function () {
    return view('welcome');
})->where('any', '^(?!api).*$');
