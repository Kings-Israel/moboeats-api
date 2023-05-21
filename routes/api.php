<?php

use App\Http\Controllers\Api\V1\FCategorySubCategoryController;
use App\Http\Controllers\Api\V1\FoodCommonCategoryController;
use App\Http\Controllers\Api\V1\FooSubCategoryController;
use App\Http\Controllers\Api\V1\MenuController;
use App\Http\Controllers\Api\V1\RestaurantController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::middleware(['auth:sanctum'])->get('/user', function (Request $request) {
    return $request->user();
});

Route::group(['prefix' => 'v1'], function() {
    Route::apiResource('restaurants', RestaurantController::class);
    Route::apiResource('food-categories', FoodCommonCategoryController::class);
    Route::apiResource('food-sub-categories', FooSubCategoryController::class);
    Route::post('food-sub-categories/bulk', [FooSubCategoryController::class, 'bulkStore']);

    Route::apiResource('menu', MenuController::class);
});