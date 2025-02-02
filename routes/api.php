<?php

use App\Http\Controllers\V1\AddressController;
use App\Http\Controllers\V1\AuthController;
use App\Http\Controllers\v1\CityProvinceController;

use App\Http\Controllers\V1\HomeController;
use App\Http\Controllers\V1\OrderController;
use App\Http\Controllers\V1\ProfileController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::post('/v1/register/send-code', [AuthController::class,'sendCode']);

Route::post('/v1/register/verify-code', [AuthController::class,'verifyCode']);

Route::post('/v1/register/completed-register', [AuthController::class,'completedRegister']);

Route::get('/v1/cities-and-provinces', [CityProvinceController::class,'index']);

Route::post('/v1/resend-code',[[AuthController::class,'resendCode']]);

Route::get('/v1/home/coffee-menu', [HomeController::class,'index'])->middleware('auth.api');

Route::get('/v1/home/banners',[HomeController::class,'showBanner']);

Route::get('/v1/banner/products',[HomeController::class,'showProductsForBanner']);

Route::get('/v1/home/search',[HomeController::class,'search']);

Route::get('/v1/home/sentences', [HomeController::class,'showSentenceCategories']);

Route::get('/v1/home/sentences/show', [HomeController::class,'showSentences']);

Route::get('/v1/products',[HomeController::class,'showProduct']);

Route::post('/v1/orders',[OrderController::class,'addToBasket'])->middleware('auth.api');

Route::get('/v1/address',[AddressController::class,'index'])->middleware('auth.api');

Route::post('/v1/address',[AddressController::class,'createAddress'])->middleware('auth.api');

Route::put('/v1/address',[AddressController::class,'updateAddress']);

Route::get('/v1/profile',[ProfileController::class,'index']);

Route::prefix('/v1/profile')->group(function (){

    Route::get('/',[ProfileController::class,'index'])->middleware('auth.api');

});
