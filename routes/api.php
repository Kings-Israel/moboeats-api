<?php

use App\Http\Controllers\Api\V1\AdminController;
use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\CartController;
use App\Http\Controllers\Api\V1\CartItemController;
use App\Http\Controllers\Api\V1\DiscountController;
use App\Http\Controllers\Api\V1\FCategorySubCategoryController;
use App\Http\Controllers\Api\V1\FoodCommonCategoryController;
use App\Http\Controllers\Api\V1\FooSubCategoryController;
use App\Http\Controllers\Api\V1\MarketingController;
use App\Http\Controllers\Api\V1\MenuBookmarkController;
use App\Http\Controllers\Api\V1\MenuController;
use App\Http\Controllers\Api\V1\MenuPriceController;
use App\Http\Controllers\Api\V1\MessageController;
use App\Http\Controllers\Api\V1\NotificationController;
use App\Http\Controllers\Api\V1\OrderController;
use App\Http\Controllers\Api\V1\OrdererController;
use App\Http\Controllers\Api\V1\PaymentController;
use App\Http\Controllers\Api\V1\PromoCodesController;
use App\Http\Controllers\Api\V1\QuestionnaireController;
use App\Http\Controllers\Api\V1\RestaurantBookmarkController;
use App\Http\Controllers\Api\V1\RestaurantController;
use App\Http\Controllers\Api\V1\RestaurantDocumentsController;
use App\Http\Controllers\Api\V1\RestaurantOperatingHoursController;
use App\Http\Controllers\Api\V1\RiderController;
use App\Http\Controllers\Api\V1\SupplementController;
use App\Http\Controllers\Api\V1\DietController;
use App\Http\Controllers\FrequentlyAskedQuestionController;
use App\Http\Controllers\HomeController;
use App\Http\Middleware\HasRestaurant;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Jobs\SendSMS;

Route::middleware(['auth:sanctum'])->get('/user', function (Request $request) {
    return $request->user();
});

Route::group(['prefix' => 'v1'], function() {
    Route::post('login', [AuthController::class, 'login']);
    Route::post('register', [AuthController::class, 'register']);

    Route::post('forgot-password', [AuthController::class, 'forgotPassword']);
    Route::post('reset-password', [AuthController::class, 'resetPassword']);

    Route::get('/groceries', [MenuController::class, 'groceries']);
    Route::get('/groceries/subcategories/{foo_sub_category}', [MenuController::class, 'grocerySubcategories']);
    Route::get('/menu', [MenuController::class, 'index']);
    Route::get('/restaurants', [RestaurantController::class, 'index']);
    Route::get('/restaurants/{restaurant}', [RestaurantController::class, 'show']);

    Route::get('/categories/{food_common_category}', [FoodCommonCategoryController::class, 'show']);
    Route::apiResource('categories', FoodCommonCategoryController::class);

    Route::get('sub-categories/{food_sub_category}', [FooSubCategoryController::class, 'show']);
    Route::apiResource('sub-categories', FooSubCategoryController::class);

    Route::post('/stripe/webhook', [PaymentController::class, 'stripeWebhookCallback']);

    // Frequently Asked Questions
    Route::resource('/frequently-asked-questions', FrequentlyAskedQuestionController::class)->except('create', 'show', 'edit');
    Route::get('/ads', [MarketingController::class, 'index']);

    Route::get('/geocode/{lat}/{long}', [HomeController::class, 'geocode']);
    Route::get('/directions/{origin_lat}/{origin_long}/{dest_lat}/{dest_long}', [HomeController::class, 'directions']);
    Route::get('/autocomplete/{text}', [HomeController::class, 'autocomplete']);
    Route::get('/place/{place_id}', [HomeController::class, 'place']);
});

Route::group(['prefix' => 'v2'], function() {
    Route::post('login', [AuthController::class, 'otpLogin']);
    Route::post('login/otp/verify', [AuthController::class, 'verifyOtp']);
});

Route::group(['prefix' => 'v1', 'middleware' => 'auth:sanctum'], function() {
    Route::post('logout', [AuthController::class, 'logout']);
    Route::get('user', [AuthController::class, 'authUser']);

    // Delete Account
    Route::get('delete', [AuthController::class, 'delete']);

    Route::get('/notifications', [NotificationController::class, 'index']);
    Route::get('/notifications/{notification}/mark-read', [NotificationController::class, 'markAsRead']);
    Route::get('/notifications/all/read', [NotificationController::class, 'markAllAsRead']);
    Route::get('/seating-areas', [RestaurantController::class, 'seatingAreas']);

    // Chat
    Route::get('/conversations', [MessageController::class, 'index']);
    Route::get('/conversations/{id}', [MessageController::class, 'show']);
    Route::post('/message/send', [MessageController::class, 'store']);
    Route::post('/conversation/{id}/reply', [MessageController::class, 'update']);
    Route::get('/conversations/{id}/read', [MessageController::class, 'markConversationAsRead']);
    Route::get('/message/{id}/read', [MessageController::class, 'markMessageAsRead']);
    Route::get('/conversations/{id}/unread/count', [MessageController::class, 'getCoversationUnreadMessagesCount']);
    Route::get('/messages/unread/count', [MessageController::class, 'getUnreadMessagesCount']);
    Route::delete('/message/{id}/delete', [MessageController::class, 'deleteMessage']);
    Route::delete('/conversation/{id}/delete', [MessageController::class, 'deleteConversation']);
});

Route::group(['prefix' => 'v1/orderer'], function() {
    Route::get('/groceries', [MenuController::class, 'groceries']);
    Route::get('/groceries/categories', [MenuController::class, 'grocerySubcategories']);

    Route::get('/orderer-restaurants/rated', [RestaurantController::class, 'rating']);
    Route::get('orderer-restaurants/{restaurant}', [RestaurantController::class, 'show']);
    Route::get('orderer-restaurants/{restaurant?}/menu', [MenuController::class, 'restaurantMenu']);
    Route::get('orderer-grocery-shops/', [RestaurantController::class, 'groceryShops']);
    Route::apiResource('orderer-restaurants', RestaurantController::class);
    Route::apiResource('orderer-food-categories', FoodCommonCategoryController::class);
    Route::apiResource('orderer-food-sub-categories', FooSubCategoryController::class);

    Route::apiResource('orderer-menu', MenuController::class);

    Route::group(['prefix' => '/supplements'], function () {
        Route::get('/', [SupplementController::class, 'index']);
        Route::get('/categories', [SupplementController::class, 'categories']);
        Route::get('/categories/{supplement_category}/details', [SupplementController::class, 'category']);
        Route::get('/suppliers', [SupplementController::class, 'suppliers']);
    });

    // Diet and Diet planning
    Route::group(['prefix' => '/diet'], function () {
        Route::get('/packages', [DietController::class, 'packages']);
        Route::get('/subscription', [DietController::class, 'subscription']);
    });

    // Tables
    Route::get('/orderer-restaurants/{restaurant}/tables', [RestaurantController::class, 'restaurantTables']);

    Route::group(['middleware' => 'auth:sanctum'], function () {
        Route::apiResource('orderers', OrdererController::class);

        Route::get('/menu-bookmark/{menuId}/delete', [MenuBookmarkController::class, 'destroy']);
        Route::apiResource('menu-bookmark', MenuBookmarkController::class)->except(['update']);
        Route::apiResource('restaurant-bookmark', RestaurantBookmarkController::class)->except(['update']);

        Route::apiResource('cart', CartController::class)->except(['update']);
        Route::apiResource('cart-items', CartItemController::class);

        Route::apiResource('orders', OrderController::class)->except(['update']);

        // Supplements
        Route::group(['prefix' => '/supplements'], function () {
            Route::get('/orders', [SupplementController::class, 'orders']);
            Route::post('/orders', [SupplementController::class, 'storeOrder']);
            Route::get('/orders/{id}/pay', [PaymentController::class, 'stripeSupplementCheckout']);
        });

        // Diet Plans
        Route::group(['prefix' => '/diet'], function () {
            Route::post('/data/store', [OrdererController::class, 'storeDietPlanUserData']);
            Route::get('/plans', [AdminController::class, 'plans']);
            Route::post('/subscribe/{diet_subscription_package}', [PaymentController::class, 'stripeDietPlanCheckout']);
        });

        // Route::apiResource('payment', PaymentController::class)->except(['update']);
        Route::get('/stripe/checkout/{order_id}', [PaymentController::class, 'stripeCheckout']);
        Route::get('/stripe/checkout/{order_id}/{amount}', [PaymentController::class, 'stripeTipCheckout']);

        // Reviews
        Route::post('/order/reviews/store', [OrderController::class, 'storeReview']);
        Route::post('/restaurant/reviews/store', [RestaurantController::class, 'storeReview']);
        Route::post('/rider/reviews/store', [RiderController::class, 'storeReview']);
        Route::post('/menu/reviews/store', [MenuController::class, 'storeReview']);
    });
});

Route::group(['prefix' => 'v1/rider', 'middleware' => 'auth:sanctum'], function() {
    Route::apiResource('restaurants', RestaurantController::class);
    Route::post('/profile/create', [RiderController::class, 'create']);
    Route::post('/profile/update', [RiderController::class, 'update']);
    Route::get('/profile', [RiderController::class, 'profile']);
    Route::get('/orders', [RiderController::class, 'orders']);
    Route::post('/orders/update', [RiderController::class, 'updateOrder']);
    Route::post('/orders/{order_id}/delivery/location/update', [RiderController::class, 'updateDeliveryLocation'])->middleware(['throttle:location']);
    Route::post('/location/update', [RiderController::class, 'updateLocation']);
    Route::get('/tips', [RiderController::class, 'getTips']);
    Route::get('/earnings', [RiderController::class, 'earnings']);
});

Route::get('/v1/orderer/payment/{user_id}/{order_id}', [PaymentController::class, 'store']);

/**Restaurant owners management and employees */
Route::group(['prefix' => 'v1/restaurant', 'middleware' => 'auth:sanctum'], function() {
    Route::post('/restaurant', [RestaurantController::class, 'store']);
    Route::middleware(['has_restaurant'])->group(function () {
        Route::get('/dashboard', [RestaurantController::class, 'dashboard']);
        Route::get('/restaurants/{restaurant}', [RestaurantController::class, 'show']);
        Route::get('/restaurants/{restaurant}/reviews', [RestaurantController::class, 'reviews']);
        Route::apiResource('restaurants', RestaurantController::class);
        Route::get('/restaurants/export/data', [RestaurantController::class, 'export']);
        Route::post('/restaurants/{restaurant}/update', [RestaurantController::class, 'update']);
        Route::apiResource('food-categories', FoodCommonCategoryController::class);
        Route::apiResource('food-sub-categories', FooSubCategoryController::class);
        Route::post('food-sub-categories/bulk', [FooSubCategoryController::class, 'bulkStore']);
        Route::apiResource('more-info', QuestionnaireController::class);

        Route::apiResource('menu', MenuController::class);
        Route::get('/menu/{menu}/reviews', [MenuController::class, 'reviews']);
        Route::get('/groceries', [MenuController::class, 'groceries']);
        Route::get('/groceries/export/data', [MenuController::class, 'exportGroceries']);
        Route::get('{restaurant}/groceries', [MenuController::class, 'restaurantGroceries']);
        Route::post('/{id}/menu/add', [MenuController::class, 'store']);
        Route::post('/menu/{id}/update', [MenuController::class, 'update']);
        Route::post('/menu/{id}/images/update', [MenuController::class, 'updateImages']);
        Route::apiResource('menu-prices', MenuPriceController::class);
        Route::post('/menu-prices/{id}/update', [MenuPriceController::class, 'update']);
        Route::delete('/menu-prices/{id}/delete', [MenuPriceController::class, 'destroy']);
        Route::get('/menus', [MenuController::class, 'commonMenus']);
        Route::get('menus/export/data', [MenuController::class, 'export']);
        Route::post('/menu/create', [MenuController::class, 'addMenu']);
        Route::post('/menus/{menu}/update', [MenuController::class, 'updateMenu']);
        Route::get('/menus/{menu}/orders', [MenuController::class, 'menuOrders']);

        Route::get('/orders/{order}', [OrderController::class, 'show']);
        Route::post('/orders/reservation/{order}/assign', [OrderController::class, 'assignReservationToTables']);
        Route::post('/orders/reservation/{order}/status/update', [OrderController::class, 'updateReservedOrder']);
        Route::get('/pending-orders', [OrderController::class, 'pendingOrders']);
        Route::get('/pending-dineins', [OrderController::class, 'pendingDineins']);
        Route::apiResource('orders', OrderController::class)->except(['store']);
        Route::post('/orders/{order}/update', [OrderController::class, 'update']);
        Route::post('/orders/assign', [OrderController::class, 'assignorder']);
        Route::get('/orders/export/data', [OrderController::class, 'export']);

        Route::get('/riders/{id}', [RestaurantController::class, 'riders']);

        Route::get('/payments', [RestaurantController::class, 'payments']);
        Route::get('/payments/export/data', [RestaurantController::class, 'exportPayments']);
        Route::get('/reservations', [RestaurantController::class, 'reservations']);
        Route::get('/tables', [RestaurantController::class, 'tables']);

        Route::get('/restaurant/{restaurant}/tables', [RestaurantController::class, 'restaurantTables']);
        Route::post('{restaurant}/tables/add', [RestaurantController::class, 'addTable']);
        Route::post('{restaurant}/tables/{id}/update', [RestaurantController::class, 'updateTable']);

        Route::get('/restaurant/{restaurant}/payments', [RestaurantController::class, 'restaurantPayments']);
        Route::get('/restaurant/{restaurant}/sitting-areas', [RestaurantController::class, 'restaurantSeatingAreas']);
        Route::get('/restaurant/{restaurant}/reservations', [RestaurantController::class, 'restaurantReservations']);
        Route::get('/restaurant/{restaurant}/orders', [RestaurantController::class, 'restaurantOrders']);
        Route::get('/restaurant/{restaurant}/menu', [RestaurantController::class, 'restaurantMenu']);

        Route::get('{restaurant}/categories', [RestaurantController::class, 'categories']);
        Route::post('{restaurant}/categories/add', [RestaurantController::class, 'addCategory']);
        Route::post('{restaurant}/categories/{id}/update', [RestaurantController::class, 'updateCategory']);

        // Employees
        Route::group(['prefix' => 'employees',], function () {
            Route::get('/', [RestaurantController::class, 'employees']);
            Route::get('/{restaurant}', [RestaurantController::class, 'restaurantEmployees']);
            Route::post('/{restaurant}/add', [RestaurantController::class, 'addEmployee']);
            Route::post('/{user}/update', [RestaurantController::class, 'updateEmployee']);
        });

        // Operating Hours
        Route::get('/{id}/operating-hours', [RestaurantOperatingHoursController::class, 'index']);
        Route::post('/{uuid}/operating-hours', [RestaurantOperatingHoursController::class, 'store']);
        Route::post('/{uuid}/operating-hours/update', [RestaurantOperatingHoursController::class, 'update']);

        // Documents
        Route::get('/{id}/documents', [RestaurantDocumentsController::class, 'index']);
        Route::post('/{uuid}/documents/store', [RestaurantDocumentsController::class, 'store']);
        Route::post('/{uuid}/documents/update', [RestaurantDocumentsController::class, 'update']);
        Route::get('/documents/{id}/download', [RestaurantDocumentsController::class, 'download']);

        // Promo codes
        Route::get('/promo-codes', [PromoCodesController::class, 'index']);
        Route::get('/promo-codes/export/data', [PromoCodesController::class, 'export']);
        Route::post('/promo-codes/store', [PromoCodesController::class, 'store']);
        Route::post('/promo-codes/{promo_code}/update', [PromoCodesController::class, 'update']);

        // Discounts
        Route::group(['prefix' => 'discounts'], function () {
            Route::get('/', [DiscountController::class, 'index']);
            Route::post('/store', [DiscountController::class, 'store']);
            Route::post('/{discount}/update', [DiscountController::class, 'update']);
            Route::get('/{discount}/delete', [DiscountController::class, 'destroy']);
        });
    });
});

Route::post('/v1/order/payment/create-paypal-order', [PaymentController::class, 'createPaypalOrder']);
Route::post('/v1/order/payment/capture-paypal-order', [PaymentController::class, 'capturePaypalPayment']);

Route::post('/v1/order/tip/payment/create-tip-paypal-order', [PaymentController::class, 'createTipPaypalOrder']);
Route::post('/v1/order/tip/payment/capture-tip-paypal-order', [PaymentController::class, 'captureTipPaypalPayment']);

// Route::group(['prefix' => 'v1/customer', 'middleware' => 'auth:sanctum'], function() {
//     // Define customer-specific routes here
// });
// Route::group(['prefix' => 'v1/driver', 'middleware' => 'auth:sanctum'], function() {
//     // Define driver-specific routes here
// });
// Route::group(['prefix' => 'v1/admin', 'middleware' => 'auth:sanctum'], function() {
//     // Define admin-specific routes here
// });

Route::post('/sms/test', function(Request $request) {
    SendSMS::dispatchAfterResponse($request->phone_number, 'Test');
});

Route::post('/sms/test/callback', function(Request $request) {
    info($request->all());
});

require __DIR__.'/admin.php';
