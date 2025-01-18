<?php

use App\Http\Controllers\V1\AuthController;
use App\Http\Controllers\v1\CityProvinceController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::post('/v1/register/send-code', [AuthController::class,'sendCode']);
Route::post('/v1/register/verify-code', [AuthController::class,'verifyCode']);
Route::post('/v1/register/completed-register', [AuthController::class,'completedRegister']);
Route::get('/v1/cities-and-provinces', [CityProvinceController::class,'index']);

