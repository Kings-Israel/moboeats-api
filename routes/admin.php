<?php

use App\Http\Controllers\Api\V1\AdminController;
use Illuminate\Support\Facades\Route;

Route::group(['prefix' => 'v1/admin'], function() {
    Route::post('/login', [AdminController::class, 'login']);
    Route::group(['middleware' => 'auth:sanctum'], function () {
        Route::get('/dashboard', [AdminController::class, 'dashboard']);
        Route::get('/users/{role}', [AdminController::class, 'users']);
        Route::get('/orders', [AdminController::class, 'orders']);
        Route::get('/restaurants', [AdminController::class, 'restaurants']);
        Route::get('/payments', [AdminController::class, 'payments']);
    });
});

