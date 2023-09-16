<?php

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
    return ['Laravel' => app()->version()];
});
//create a symlink to storage folder
Route::get('/storage-link', function() {
    Artisan::call('storage:link');
    return redirect('/');
});
Route::get('/paypal/checkout/success', function() {
    return view('paypal.success');
})->name('paypal.checkout.success');
Route::get('/paypal/checkout/failed', function() {
    return view('paypal.error');
})->name('paypal.checkout.failed');
require __DIR__.'/auth.php';

