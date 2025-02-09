<?php

namespace App\Http\Controllers\V1;

use App\Http\Controllers\ApiController;
use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\orderItem;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Symfony\Component\HttpFoundation\Response;

class ProfileController extends ApiController
{
    public function index(Request $request)
    {
        $user = $this->getTokenAndUser($request);

        return $this->successResponse($user,'',Response::HTTP_OK);
    }

    public function showAllOrderHistory(Request $request)
    {
        $user = $this->getTokenAndUser($request);

        $orders = Order::where('user_id', $user->id)->where('status', 1)->get();

        if ($orders->isEmpty() || count($orders) <= 0)
        {
            return $this->successResponse('','این کاربر هیچگونه تاریخچه خریدی ندارد',Response::HTTP_OK);
        }

        return $this->successResponse($orders,'تمام تاریخچه خرید کاربر',Response::HTTP_OK);
    }

    public function showOrder(Request $request)
    {
        $validator = Validator::make($request->all(),[
            'order_id' => 'required'
        ]);

        if ($validator->fails())
        {
            return $this->errorResponse($validator->errors(),Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $orders = Order::where('id',$request->order_id)->with('orderItems')->get();

        return $this->successResponse($orders,'تمام محصولات این سفارش',Response::HTTP_OK);
    }

    public function userEdit(Request $request)
    {
        $validator = Validator::make($request->all(),[
            'gender' => 'nullable',
            'first_name' => 'required|string',
            'last_name' => 'required|string',
            'birthday' => 'nullable|string',
            'city_id' => 'required',
            'province_id' => 'required'
        ]);

        if ($validator->fails())
        {
            return $this->errorResponse($validator->errors(),Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $user = $this->getTokenAndUser($request);

        $user->update([
            'gender' => ($request->has('gender') ? $request->gender : $user->gender),
            'first_name' => ($request->has('first_name') ? $request->first_name : $user->first_name),
            'last_name' => ($request->has('last_name') ? $request->last_name : $user->last_name),
            'birthday' => ($request->has('birthday') ? $request->birthday : $user->birthday),
            'city_id' => ($request->has('city_id') ? $request->city_id : $user->city_id),
            'province_id' => ($request->has('province_id') ? $request->province_id : $user->province_id),
        ]);

        return $this->successResponse($user,'آپدیت مشخصات کاربر با موفقیت انجام شد',Response::HTTP_OK);
    }

    private function getTokenAndUser($request)
    {
        $token = substr($request->header('authorization'),7);

        $user = User::where('token',$token)->first();

        if ( !$user )
        {
            return $this->errorResponse('کاربری یافت نشد',Response::HTTP_NOT_FOUND);
        }

        return $user;
    }
}
