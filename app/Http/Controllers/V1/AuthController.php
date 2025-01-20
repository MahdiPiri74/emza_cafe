<?php

namespace App\Http\Controllers\V1;

use App\Http\Controllers\ApiController;
use App\Http\Controllers\Controller;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Validator;
use Symfony\Component\HttpFoundation\Response;

class AuthController extends ApiController
{
    public function sendCode(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'mobile_number' => "required|regex:/^09[0-9]{2}[0-9]{7}$/"
        ]);

        if ($validator->fails()) {
            return $this->errorResponse($validator->errors(), 422);
        }

        $key = 'send-verification-code:' . $request->ip();

        $verificationCode = Hash::make(rand(100000, 999999));
        $user = User::where('mobile_number', $request->mobile_number)->first();
        if (!$user) {
            $user = User::create([
                'mobile_number' => $request->mobile_number,
                'verification_code' => $verificationCode
            ]);
            $mobileNumber = $user->mobile_number;

            $executed = RateLimiter::attempt(
                $key,
                2,
                function () use ($mobileNumber) {
                    //send code

                    //end send code

                }, 300);

            if ($executed === true) {
                $token = $user->createToken('emza_cafe', ['*'], now()->addMonths(3))->plainTextToken;

                $user->update([
                    'token' => $token
                ]);

                return $this->successResponse($token, 'کد تایید برای کاربر ارسال شد', Response::HTTP_OK);

            } else {
                $seconds = RateLimiter::availableIn($key);
                return response()->json([
                    'message' => "تعداد درخواست‌های شما بیش از حد مجاز است. لطفا پس از " . $seconds . " ثانیه دیگر تلاش کنید.",
                    'retry_after' => $seconds
                ], Response::HTTP_TOO_MANY_REQUESTS);
            }

        }
        else
        {
            if ($user->verify_at == null)
            {
                return $this->errorResponse(' قبلا با این شماره ثبت نام شده است اما ثبت نام هنوز تکمیل نشده است',403);
            }
            else
            {
                return $this->successResponse($user->token,'این کاربر قبلا تایید شده است و نیازی به تکمیل ثبت نام نیست',300);
            }
        }
    }
}
