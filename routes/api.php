<?php

use App\Http\Controllers\V1\AuthController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::post('/v1/register/send-code', [AuthController::class,'sendCode']);
Route::post('/v1/register/verify-code', [AuthController::class,'verifyCode']);

