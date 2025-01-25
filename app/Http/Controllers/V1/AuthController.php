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
use Laravel\Sanctum\PersonalAccessToken;
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

        $user = User::where('mobile_number', $request->mobile_number)->first();

        $key = 'send-verification-code:' . $request->ip() . ":" . $request->mobile_number;

        if (!RateLimiter::attempt($key,3,function (){},300))
        {
            $seconds = RateLimiter::availableIn($key);
            return response()->json([
                'message' => "تعداد درخواست‌های شما بیش از حد مجاز است. لطفا پس از " . $seconds . " ثانیه دیگر تلاش کنید.",
                'retry_after' => $seconds
            ], Response::HTTP_TOO_MANY_REQUESTS);
        }
        $verificationCode = rand(100000, 999999);
        var_dump($verificationCode);
        if (!$user)
        {
           $user = $this->createNewUserWithCode($request->mobile_number,$verificationCode);
           return $this->sendVerificationCode($user, $verificationCode);

        }

        $this->checkToken($user);

        $user->update([
            'verification_code' => Hash::make($verificationCode),
            'expired_at' => now()->addMinutes(2),
        ]);

        $this->sendSms($user->mobile_number, $verificationCode);

        return $this->successResponse($user->token, 'کد تأیید ارسال شد.', Response::HTTP_OK);


    }

    private function sendVerificationCode($user, $verificationCode)
    {
        $token = $user->createToken('emza_cafe', ['*'], now()->addMonths(3))->plainTextToken;

        $user->update(['token' => $token]);
        $this->sendSms($user->mobile_number, $verificationCode);

        return $this->successResponse($token, 'کد تأیید ارسال شد.', Response::HTTP_OK);
    }

    private function createNewUserWithCode($mobileNumber, $verificationCode)
    {
        return User::create([
            'mobile_number' => $mobileNumber,
            'verification_code' => Hash::make($verificationCode),
            'expired_at' => now()->addMinutes(2),
        ]);
    }

    public function sendSms($mobileNumber, $verificationCode)
    {

    }

    public function checkToken($user)
    {
        $token = PersonalAccessToken::where('tokenable_id', $user->id)->latest('created_at')->first();

        if ($token && !$token->expires_at < now())
        {
            $newToken = $user->createToken('emza_cafe', ['*'], now()->addMonths(3))->plainTextToken;
            $user->update([
                'token' => $newToken,
            ]);
        }
    }

    public function resendCode(Request $request)
    {
        $user = User::where('token', $request->token)->first();

        $key = 'send-verification-code:' . $request->ip() . ":" . $user->mobile_number;

        $rateLimiter = RateLimiter::attempt($key, 2, function () use ($user) {
            $verificationCode = rand(100000, 999999);

            $user->update([
                'verification_code' => Hash::make($verificationCode),
                'expired_at' => now()->addMinutes(2)
            ]);

            $this->sendSms($user->mobile_number, $verificationCode);

        }, 600);

        if ($rateLimiter === true) {
            return $this->successResponse($user->token, 'کد تایید ارسال شد', Response::HTTP_OK);
        } else {
            $seconds = RateLimiter::availableIn($key);

            return response()->json([
                'message' => "تعداد درخواست‌های شما بیش از حد مجاز است. لطفا پس از " . $seconds . " ثانیه دیگر تلاش کنید.",
                'retry_after' => $seconds
            ], Response::HTTP_TOO_MANY_REQUESTS);
        }
    }

    private function extractToken($authorizationHeader)
    {
        if ($authorizationHeader && str_starts_with($authorizationHeader, 'Bearer ')) {
            return substr($authorizationHeader, 7);
        }
        return null;
    }


    public function verifyCode(Request $request)
    {
        $token = $this->extractToken($request->header('authorization'));

        if (!$token) {
            return $this->errorResponse('توکن معتبر نیست', Response::HTTP_UNAUTHORIZED);
        }

        $validator = Validator::make($request->all(), [
            'verify_code' => 'required'
        ]);

        if ($validator->fails()) {
            return $this->errorResponse($validator->errors(), Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $user = User::where('token', $token)->first();

        if (!$user) {
            return $this->errorResponse('چنین کاربری یافت نشد', Response::HTTP_NOT_FOUND);
        }
        $key = 'verify-verification-code:' . $request->ip() . ":" . $user->mobile_number;

        if (!RateLimiter::attempt($key, 3, function (){}, 300)) {
            $seconds = RateLimiter::availableIn($key);
            return response()->json([
                'message' => "تعداد درخواست‌های شما بیش از حد مجاز است. لطفا پس از " . $seconds . " ثانیه دیگر تلاش کنید.",
                'retry_after' => $seconds
            ], Response::HTTP_TOO_MANY_REQUESTS);
        }

        if (!Hash::check($request->verify_code, $user->verification_code)) {
            return $this->errorResponse('کد تایید وارد شده صحیح نیست', Response::HTTP_BAD_REQUEST);
        }

        if ($user->verify_at != null) {
            return $this->successResponse($user, ' کد تایید وارد شده صحیح است و نیازی به تکمیل ثبت نام نیست', Response::HTTP_OK);
        }

        return $this->successResponse($user->token, ' کد تایید وارد شده صحیح است و کاربر باید به صفحه تکمیل ثبت نام هدایت شود', Response::HTTP_SEE_OTHER);
    }

    public function completedRegister(Request $request)
    {
        $token = $this->extractToken($request->header('authorization'));

        if (!$token) {
            return $this->errorResponse('توکن معتبر نیست', Response::HTTP_UNAUTHORIZED);
        }

        $validator = Validator::make($request->all(), [
            'first_name' => 'required',
            'last_name' => 'required',
            'gender' => 'nullable',
            'birthday' => 'required',
            'province_id' => 'required',
            'city_id' => 'required',
        ]);

        if ($validator->fails()) {
            return $this->errorResponse($validator->errors(), Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $user = User::where('token', $token)->first();

        if (!$user) {
            return $this->errorResponse('چنین کاربری یافت نشد', Response::HTTP_NOT_FOUND);
        }

        $user->update([
            'first_name' => $request->first_name,
            'last_name' => $request->last_name,
            'gender' => ($request->has('gender') ? $request->gender : null),
            'birthday' => $request->birthday,
            'province_id' => $request->province_id,
            'city_id' => $request->city_id,
            'verify_at' => Carbon::now()
        ]);

        return $this->successResponse($user, 'ثبت نام تکمیل شد', Response::HTTP_OK);


    }

}





