<?php

use App\Http\Controllers\Api\V1\AdminController;
use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\PaymentController;
use App\Http\Controllers\HomeController;
use Illuminate\Support\Facades\Artisan;
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
    return view('home');
})->name('home');
Route::get('/about', function () {
    return view('about');
})->name('about');
Route::get('/partners', function () {
    return view('partners');
})->name('partners');
Route::get('/contact-us', function () {
    return view('contact-us');
})->name('contact-us');

Route::post('/contact-us/submit', [HomeController::class, 'contactSubmit'])->name('submit.contact-us');

Route::get('/v1/orderer/payment/{user_id}/{order_id}', [PaymentController::class, 'store']);

Route::get('/paypal/checkout/success', function() {
    return view('paypal.success');
})->name('paypal.checkout.success');

Route::get('/paypal/checkout/failed', function() {
    return view('paypal.error', ['message' => NULL]);
})->name('paypal.checkout.failed');

Route::get('/v1/orderer/tip/payment/{order_id}/{amount}', [PaymentController::class, 'storeTip']);

Route::get('/privacy-policy', function () {
    return view('privacy-policy');
});

Route::get('/qr-code/{string?}', [AdminController::class, 'qrCode']);

Route::get('/account/{user_id}/delete', [AuthController::class, 'delete']);
Route::post('delete', [AuthController::class, 'confirmDelete'])->name('delete.confirmation');

require __DIR__.'/auth.php';

