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
    Route::post('register/send-code', [AuthController::class,'sendCode']);

    Route::post('register/verify-code', [AuthController::class,'verifyCode']);

    Route::post('register/completed-register', [AuthController::class,'completedRegister']);

    Route::get('cities-and-provinces', [CityProvinceController::class,'index']);

    Route::post('resend-code',[[AuthController::class,'resendCode']]);

    Route::get('home/coffee-menu', [HomeController::class,'index'])->middleware('auth.api');

    Route::get('home/banners',[HomeController::class,'showBanner']);

    Route::get('home/search',[HomeController::class,'search']);

    Route::get('home/sentences', [HomeController::class,'showSentenceCategories']);

    Route::get('home/sentences/show', [HomeController::class,'showSentences']);

    Route::get('banner/products',[HomeController::class,'showProductsForBanner']);

    Route::get('products',[HomeController::class,'showProduct']);

    Route::post('orders',[OrderController::class,'addToBasket'])->middleware('auth.api');

    Route::get('address',[AddressController::class,'index'])->middleware('auth.api');

    Route::post('address',[AddressController::class,'createAddress'])->middleware('auth.api');

    Route::put('address',[AddressController::class,'updateAddress']);

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
        Route::post('/order',[CartController::class,'createOrder']);
    });

});

Route::prefix('/payment')->group(function (){
    Route::post('/send',[PaymentController::class,'send']);
    Route::get('/verify',[PaymentController::class,'verify']);
});

Route::get('/test',function (){

});

