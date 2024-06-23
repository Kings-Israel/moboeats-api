<?php

use App\Http\Controllers\Api\V1\SupplementController;
use App\Http\Controllers\Api\V1\AdminController;
use App\Http\Controllers\Api\V1\DietController;
use App\Http\Controllers\Api\V1\FoodCommonCategoryController;
use App\Http\Controllers\Api\V1\MarketingController;
use App\Http\Controllers\Api\V1\PaymentController;
use Illuminate\Support\Facades\Route;

Route::group(['prefix' => 'v1/admin'], function() {
    Route::post('/login', [AdminController::class, 'login']);
    Route::group(['middleware' => 'auth:sanctum'], function () {
        Route::get('/dashboard', [AdminController::class, 'dashboard']);
        Route::get('/categories', [AdminController::class, 'categories']);
        Route::post('/categories/add', [FoodCommonCategoryController::class, 'store']);
        Route::post('/categories/update/{id}', [FoodCommonCategoryController::class, 'update']);
        Route::get('/sub-categories', [AdminController::class, 'subCategories']);
        Route::post('/sub-categories/add', [AdminController::class, 'addSubcategory']);
        Route::post('/sub-categories/update/{id}', [AdminController::class, 'updateSubcategory']);
        Route::get('/users/{role}', [AdminController::class, 'users']);
        Route::get('/users/customer/{id}/details', [AdminController::class, 'user']);
        Route::get('/users/restaurant-admin/{id}/details', [AdminController::class, 'restaurantAdmin']);
        Route::get('/users/rider/{id}/details', [AdminController::class, 'rider']);
        Route::post('/users/rider/{rider}/status/update', [AdminController::class, 'updateRiderStatus']);
        Route::post('/users/store', [AdminController::class, 'addUser']);
        Route::get('/orders', [AdminController::class, 'orders']);
        Route::get('/orders/{order}/details', [AdminController::class, 'order']);
        Route::get('/restaurants', [AdminController::class, 'restaurants']);
        Route::get('/restaurants/{id}', [AdminController::class, 'restaurant']);
        Route::get('/restaurants/{id}/reviews', [AdminController::class, 'restaurantReviews']);
        Route::post('/restaurants/{restaurant}/status/update', [AdminController::class, 'updateRestaurantStatus']);
        Route::get('/payments/{payment}/details', [PaymentController::class, 'show']);
        Route::get('/payments', [AdminController::class, 'payments']);

        // Supplements and Suppliers
        Route::group(['prefix' => '/supplements'], function () {
            Route::get('/', [SupplementController::class, 'index']);
            Route::post('/store', [SupplementController::class, 'store']);
            Route::get('/suppliers', [SupplementController::class, 'suppliers']);
            Route::post('/suppliers/store', [SupplementController::class, 'storeSupplier']);
            Route::get('/{supplement}/status/update', [SupplementController::class, 'updateSupplementStatus']);
            Route::get('/suppliers/{supplier}/status/update', [SupplementController::class, 'updateSupplierStatus']);
            Route::get('/orders', [SupplementController::class, 'orders']);
            Route::post('/order/{id}/status/update', [SupplementController::class, 'updateOrderStatus']);
        });

        Route::group(['prefix' => '/diet'], function () {
            Route::get('/packages', [DietController::class, 'packages']);
            Route::get('/plans', [AdminController::class, 'plans']);
            Route::get('/plans/subscribers', [AdminController::class, 'dietPlanSubscribers']);
            Route::get('/plans/subscribers/{id}/details', [AdminController::class, 'dietPlanSubscriber']);
            Route::post('/plans/store', [AdminController::class, 'storeDietPlan']);
            Route::get('/packages', [DietController::class, 'packages']);
            Route::post('/packages/store', [AdminController::class, 'storeDietPackage']);
        });

        // Update Settings
        Route::post('/delivery-rate/update', [AdminController::class, 'updateDeliveryRate']);
        Route::post('/base-rate/update', [AdminController::class, 'updateBaseRate']);
        Route::post('/base-rate/grocery/update', [AdminController::class, 'updateGroceryBaseRate']);

        Route::group(['prefix' => 'restaurant/{restaurant}/'], function () {
            Route::get('/', [AdminController::class, 'restaurant']);
            Route::get('/payments', [AdminController::class, 'restaurantPayments']);
            Route::get('/menu', [AdminController::class, 'restaurantMenu']);
            Route::get('/categories', [AdminController::class, 'restaurantCategories']);
            Route::get('/orders', [AdminController::class, 'restaurantOrders']);

            Route::post('/update/service-charge-agreement', [AdminController::class, 'updateServiceChargeAgreement']);
        });

        Route::group(['prefix' => '/discounts'], function () {
            Route::get('/', [AdminController::class, 'discounts']);
        });

        Route::group(['prefix' => '/marketing'], function () {
            Route::get('/', [MarketingController::class, 'index']);
            Route::post('/store', [MarketingController::class, 'store']);
            Route::post('/{advert_poster}/update', [MarketingController::class, 'update']);
            Route::delete('/{advert_poster}/delete', [MarketingController::class, 'delete']);
        });

        Route::group(['prefix' => '/payouts'], function () {
            Route::get('/riders', [AdminController::class, 'ridersPayouts']);
            Route::get('/partners', [AdminController::class, 'partnersPayouts']);
            Route::post('/upload', [AdminController::class, 'uploadPayouts']);
        });

        Route::get('/logs', [AdminController::class, 'logs']);

        Route::post('/qr-code', [AdminController::class, 'qrCode']);
    });
});

