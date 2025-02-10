<?php

use App\Http\Controllers\V1\AddressController;
use App\Http\Controllers\V1\AuthController;
use App\Http\Controllers\V1\CartController;
use App\Http\Controllers\v1\CityProvinceController;

use App\Http\Controllers\V1\HomeController;
use App\Http\Controllers\V1\MessageController;
use App\Http\Controllers\V1\OrderController;
use App\Http\Controllers\V1\PaymentController;
use App\Http\Controllers\V1\ProfileController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::prefix('/v1/')->group(function (){

    Route::prefix('register')->group(function (){

        Route::post('/send-code', [AuthController::class,'sendCode']);

        Route::post('/verify-code', [AuthController::class,'verifyCode']);

        Route::post('/completed-register', [AuthController::class,'completedRegister']);

        Route::post('resend-code',[[AuthController::class,'resendCode']]);
    });

    Route::get('cities-and-provinces', [CityProvinceController::class,'index']);

    Route::prefix('home')->group(function (){

        Route::get('/coffee-menu', [HomeController::class,'index'])->middleware('auth.api');

        Route::get('/banners',[HomeController::class,'showBanner']);

        Route::get('/search',[HomeController::class,'search']);

        Route::get('/sentences', [HomeController::class,'showSentenceCategories']);

        Route::get('/sentences/show', [HomeController::class,'showSentences']);
    });

    Route::get('banner/products',[HomeController::class,'showProductsForBanner']);

    Route::get('products',[HomeController::class,'showProduct']);

    Route::post('orders',[OrderController::class,'addToBasket'])->middleware('auth.api');

    Route::prefix('address')->group(function (){

        Route::get('/',[AddressController::class,'index'])->middleware('auth.api');

        Route::post('/',[AddressController::class,'createAddress'])->middleware('auth.api');

        Route::put('/',[AddressController::class,'updateAddress']);
    });

    Route::prefix('profile')->group(function (){

        Route::get('/',[ProfileController::class,'index'])->middleware('auth.api');
        Route::get('/order-history',[ProfileController::class,'showAllOrderHistory']);
        Route::get('/order-history/show',[ProfileController::class,'showOrder']);
        Route::put('/user/edit',[ProfileController::class,'userEdit']);

    });

    Route::prefix('messages')->middleware('auth.api')->group(function (){
        Route::get('/',[MessageController::class,'index']);
        Route::get('/show',[MessageController::class,'showMessage']);
    });

    Route::prefix('cart')->middleware('auth.api')->group(function (){

        Route::get('/',[CartController::class,'index']);

        Route::post('/increase-quantity',[CartController::class,'increaseQuantity']);

        Route::post('/decrease-quantity',[CartController::class,'decreaseQuantity']);

        Route::post('/update-sentence-info',[CartController::class,'updateSenderAndReceiver']);

        Route::post('/discount',[CartController::class,'calculateDiscount']);
    });

});

Route::prefix('/payment')->group(function (){

    Route::post('/send',[PaymentController::class,'send']);

    Route::get('/verify',[PaymentController::class,'verify']);
});

