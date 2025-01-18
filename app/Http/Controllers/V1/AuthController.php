<?php

namespace App\Http\Controllers\V1;

use App\Http\Controllers\ApiController;
use App\Http\Controllers\Controller;
use App\Models\User;
use Carbon\Carbon;
use http\Env\Response;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Validator;

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

        $verificationCode = rand(100000, 999999);

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
                $token = $user->createToken('emza_cafe', ['*'], Carbon::now()->addMonths(3))->plainTextToken;

                $user->update([
                    'token' => $token
                ]);

                return $this->successResponse($token, 'ثبت نام انجام شد', 200);

            } else {
                $seconds = RateLimiter::availableIn($key);
                return response()->json([
                    'message' => "تعداد درخواست‌های شما بیش از حد مجاز است. لطفا پس از " . $seconds . " ثانیه دیگر تلاش کنید.",
                    'retry_after' => $seconds
                ], 429);
            }

        }
        else
        {
            return $this->errorResponse('قبلا با این شماره ثبت نام شده است',422);
        }
    }

    public function verifyCode(Request $request)
    {
        $authorization = $request->header('authorization');
        if ($authorization)
        {
            $token = substr($authorization,7);

            $validator = Validator::make($request->all(),[
                'verify_code' => 'required'
            ]);

            if ($validator->fails())
            {
                return $this->errorResponse($validator->errors(),422);
            }

            $user = User::where('token',$token)->first();

            if (!$user)
            {
                return $this->errorResponse('چنین کاربری یافت نشد',422);
            }
            else
            {
                $key = 'verify-verification-code:' . $request->ip();

                $executed = RateLimiter::attempt($key,10,function (){},300);

                if ($executed === true)
                {
                    if ($user->verification_code == $request->verify_code)
                    {
                        return $this->successResponse('','کد تایید وارد شده صحیح است',200);
                    }
                    else
                    {
                        return $this->errorResponse('کد تایید وارد شده صحیح نیست',422);
                    }
                }
                if ($executed === false)
                {
                    $seconds = RateLimiter::availableIn($key);
                    return response()->json([
                        'message' => "تعداد درخواست‌های شما بیش از حد مجاز است. لطفا پس از " . $seconds . " ثانیه دیگر تلاش کنید.",
                        'retry_after' => $seconds
                    ], 429);
                }
            }
        }
        else
        {
            return $this->errorResponse('مشکلی در درخواست ارسال شده وجود دارد',422);
        }

    }
}


