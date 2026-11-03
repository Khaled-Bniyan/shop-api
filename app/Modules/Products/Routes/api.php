<?php

use App\Modules\Products\Http\Controllers\CategoryController;
use App\Modules\Products\Http\Controllers\ProductController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth:api')->group(function () {
    Route::get('categories', [CategoryController::class, 'index']);
    Route::get('products', [ProductController::class, 'index']);
    Route::post('products', [ProductController::class, 'store'])
        ->middleware('role:admin');
});
