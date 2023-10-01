<?php

use App\Http\Controllers\Api\V1\AdminController;
use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\CartController;
use App\Http\Controllers\Api\V1\CartItemController;
use App\Http\Controllers\Api\V1\FCategorySubCategoryController;
use App\Http\Controllers\Api\V1\FoodCommonCategoryController;
use App\Http\Controllers\Api\V1\FooSubCategoryController;
use App\Http\Controllers\Api\V1\MenuBookmarkController;
use App\Http\Controllers\Api\V1\MenuController;
use App\Http\Controllers\Api\V1\MenuPriceController;
use App\Http\Controllers\Api\V1\OrderController;
use App\Http\Controllers\Api\V1\OrdererController;
use App\Http\Controllers\Api\V1\PaymentController;
use App\Http\Controllers\Api\V1\QuestionnaireController;
use App\Http\Controllers\Api\V1\RestaurantBookmarkController;
use App\Http\Controllers\Api\V1\RestaurantController;
use App\Http\Controllers\Api\V1\RiderController;
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

/**
 *Auth
*/
Route::group(['prefix' => 'v1'], function() {
    Route::post('login', [AuthController::class, 'login']);
    Route::post('register', [AuthController::class, 'register']);

});


Route::group(['prefix' => 'v1', 'middleware' => 'auth:sanctum'], function() {
    Route::post('logout', [AuthController::class, 'logout']);
    Route::get('user', [AuthController::class, 'authUser']);
});
/**Basically a customer who will be ordering food/drinks */
Route::group(['prefix' => 'v1/orderer', 'middleware' => 'auth:sanctum'], function() {
    Route::apiResource('orderers', OrdererController::class);

    Route::apiResource('orderer-restaurants', RestaurantController::class);
    Route::apiResource('orderer-food-categories', FoodCommonCategoryController::class);
    Route::apiResource('orderer-food-sub-categories', FooSubCategoryController::class);

    Route::apiResource('orderer-menu', MenuController::class);

    Route::apiResource('menu-bookmark', MenuBookmarkController::class)->except(['update']);
    Route::apiResource('restaurant-bookmark', RestaurantBookmarkController::class)->except(['update']);

    Route::apiResource('cart', CartController::class)->except(['update']);
    Route::apiResource('cart-items', CartItemController::class);

    Route::apiResource('orders', OrderController::class)->except(['update']);

    // Route::apiResource('payment', PaymentController::class)->except(['update']);
});

Route::group(['prefix' => 'v1/rider', 'middleware' => 'auth:sanctum'], function() {
    Route::get('/orders', [RiderController::class, 'orders']);
    Route::post('/orders/update', [RiderController::class, 'updateOrder']);
    Route::post('/orders/{order_id}/delivery/location/update', [RiderController::class, 'updateDeliveryLocation'])->middleware(['throttle:location']);
    Route::post('/location/update', [RiderController::class, 'updateLocation']);
});

Route::get('/v1/orderer/payment/{user_id}/{order_id}', [PaymentController::class, 'store']);

/**Restaurant owners management */
Route::group(['prefix' => 'v1/restaurant', 'middleware' => 'auth:sanctum'], function() {
    Route::apiResource('restaurants', RestaurantController::class);
    Route::apiResource('food-categories', FoodCommonCategoryController::class);
    Route::apiResource('food-sub-categories', FooSubCategoryController::class);
    Route::post('food-sub-categories/bulk', [FooSubCategoryController::class, 'bulkStore']);
    Route::apiResource('more-info', QuestionnaireController::class);

    Route::apiResource('menu', MenuController::class);
    Route::apiResource('menu-prices', MenuPriceController::class);

    Route::apiResource('orders', OrderController::class)->except(['store']);
    Route::post('/orders/assign', [OrderController::class, 'assignorder']);
});

Route::group(['prefix' => 'admin'], function() {
    Route::get('/dashboard', [AdminController::class, 'dashboard']);
    Route::get('/users', [AdminController::class, 'users']);
    Route::get('/orders', [AdminController::class, 'orders']);
    Route::get('/payments', [AdminController::class, 'payments']);
});

Route::post('/v1/order/payment/create-paypal-order', [PaymentController::class, 'createPaypalOrder']);
Route::post('/v1/order/payment/capture-paypal-order', [PaymentController::class, 'capturePaypalPayment']);

// Route::group(['prefix' => 'v1/customer', 'middleware' => 'auth:sanctum'], function() {
//     // Define customer-specific routes here
// });
// Route::group(['prefix' => 'v1/driver', 'middleware' => 'auth:sanctum'], function() {
//     // Define driver-specific routes here
// });
// Route::group(['prefix' => 'v1/admin', 'middleware' => 'auth:sanctum'], function() {
//     // Define admin-specific routes here
// });
