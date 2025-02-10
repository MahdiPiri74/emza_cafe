<?php

namespace App\Http\Controllers\V1;

use App\Http\Controllers\ApiController;
use App\Http\Controllers\Controller;
use App\Models\Address;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Symfony\Component\HttpFoundation\Response;

class AddressController extends ApiController
{
    public function index(Request $request)
    {
        $user = $this->getTokenAndUser($request);

        $addresses = Address::where('user_id',$user->id)->get();

        if ( $addresses->isEmpty() )
        {
            return $this->errorResponse('هیچ آدرسی ثبت نشده است',Response::HTTP_OK);
        }

        return $this->successResponse($addresses,'آدرس های کاربر بازگشت داده شدند',Response::HTTP_OK);
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

    public function createAddress(Request $request)
    {
        $validator = Validator::make($request->all(),
            [
                'user_id' => 'required',
                'address' => 'required',
                'order_receiver' => 'required|string',
                'call_number' => 'nullable',
            ]);

        if ($validator->fails())
        {
            return $this->errorResponse($validator->errors(), Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $user = $this->getTokenAndUser($request);

        Address::create([
            'user_id' => $user->id,
            'address' => $request->address,
            'order_receiver' => $request->order_receiver,
            'call_number' => $request->has('call_number') ? $request->call_number : null
        ]);

        return $this->successResponse(null,'آدرس ثبت شد',Response::HTTP_OK);
    }

    public function updateAddress(Request $request)
    {
        $validator = Validator::make($request->all(),
            [
                'address_id' => 'required',
                'address' => 'required|string',
                'order_receiver' => 'required|string',
                'call_number' => 'nullable',
            ]);

        if ($validator->fails())
        {
            return $this->errorResponse($validator->errors(), Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $address = Address::where('id',$request->address_id)->first();

        if (!$address)
        {
            return $this->errorResponse('کاربر هیچ آدرسی ثبت نکرده است',Response::HTTP_NOT_FOUND);
        }

        $address->update([
            'address' => $request->address,
            'order_receiver' => $request->order_receiver,
            'call_number' => $request->has('call_number') ? $request->call_number : $address->call_number
        ]);

        return $this->successResponse(null,'آدرس کاربر آپدیت شد',Response::HTTP_OK);
    }
}
