<?php

use App\Http\Controllers\Api\V1\AdminController;
use App\Http\Controllers\Api\V1\FoodCommonCategoryController;
use Illuminate\Support\Facades\Route;

Route::group(['prefix' => 'v1/admin'], function() {
    Route::post('/login', [AdminController::class, 'login']);
    Route::group(['middleware' => 'auth:sanctum'], function () {
        Route::get('/dashboard', [AdminController::class, 'dashboard']);
        Route::get('/categories', [AdminController::class, 'categories']);
        Route::post('/categories/add', [FoodCommonCategoryController::class, 'store']);
        Route::post('/categories/update/{id}', [FoodCommonCategoryController::class, 'update']);
        Route::get('/users/{role}', [AdminController::class, 'users']);
        Route::get('/users/customer/{id}/details', [AdminController::class, 'user']);
        Route::get('/users/restaurant-admin/{id}/details', [AdminController::class, 'restaurantAdmin']);
        Route::get('/users/rider/{id}/details', [AdminController::class, 'rider']);
        Route::get('/orders', [AdminController::class, 'orders']);
        Route::get('/restaurants', [AdminController::class, 'restaurants']);
        Route::get('/restaurants/{id}', [AdminController::class, 'restaurant']);
        Route::get('/payments', [AdminController::class, 'payments']);
    });
});

