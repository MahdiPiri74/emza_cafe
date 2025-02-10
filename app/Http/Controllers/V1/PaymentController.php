<?php

namespace App\Http\Controllers\V1;

use App\Http\Controllers\ApiController;
use App\Http\Controllers\Controller;
use App\Models\Coupon;
use App\Models\Message;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Symfony\Component\HttpFoundation\Response;

class PaymentController extends ApiController
{
    const COUPON_TYPE_FIXED = 0;
    const COUPON_TYPE_PERCENTAGE = 1;
    public function send(Request $request)
    {

        $user = $this->getTokenAndUser($request);

        $order = $this->createOrder($request);

        $api = env('API_KEY_PAY');

        $amount = $order->total_price * 10;

        $redirect = env('PAY_CALLBACK_URL');

        $result = $this->sendRequest($api,$amount,$redirect);

        $result = json_decode($result);

        if($result->status) {
            $go = "https://pay.ir/pg/$result->token";

            Transaction::create([
               'user_id' => $user->id,
               'order_id' => $order->id,
               'amount' => $amount,
                'token' => $result->token,
                'status' => 0,
            ]);

            return $this->successResponse($go,'',Response::HTTP_OK);
        } else {
            return $this->errorResponse($result->errorMessage,Response::HTTP_BAD_REQUEST);
        }

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

    public function verify()
    {
        $api = env('API_KEY_PAY');

        $token = $_GET['token'];

        $result = json_decode($this->verifyRequest($api,$token));

        if(isset($result->status)){

            if($result->status == 1){

                $transaction = Transaction::where('ref_number',$result->transId)->first();

                if ($transaction)
                {
                    return $this->errorResponse('قبلا این تراکنش انجام شده است',Response::HTTP_BAD_REQUEST);
                }

                $transaction = Transaction::where('token',$token)->first();

                $transaction->update([
                    'status' => 1 ,
                    'ref_number' => $result->transId,
                    'updated_at' => now()

                ]);

                $order = Order::where('id',$transaction->order_id)->first();

                $order->update([
                    'status' => 1 ,
                ]);

                OrderItem::where('order_id',$order->id)->update([
                    'status' => 1
                ]);

                Message::create([
                   'user_id' => $order->user_id,
                   'title' => 'تکمیل سفارش',
                    'content' => 'سفارش شما با شماره سفارش'.$transaction->ref_number.'در حال آماده سازی است'
                ]);

                echo "<h1>تراکنش با موفقیت انجام شد</h1>";

            } else {
                echo "<h1>تراکنش با خطا مواجه شد</h1>";
            }
        } else {
            if($_GET['status'] == 0){
                echo "<h1>تراکنش با خطا مواجه شد</h1>";
            }
        }
    }
    private function sendRequest($api, $amount, $redirect, $mobile = null, $factorNumber = null, $description = null) {
        return $this->curl_post('https://pay.ir/pg/send', [
            'api'          => $api,
            'amount'       => $amount,
            'redirect'     => $redirect,
            'mobile'       => $mobile,
            'factorNumber' => $factorNumber,
            'description'  => $description,
        ]);
    }

    private function verifyRequest($api, $token) {
        return $this->curl_post('https://pay.ir/pg/verify', [
            'api' 	=> $api,
            'token' => $token,
        ]);
    }

    private function curl_post($url, $params)
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($params));
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
        ]);
        $res = curl_exec($ch);
        curl_close($ch);

        return $res;
    }

    public function createOrder(Request $request)
    {

        $validator = Validator::make($request->all(),[
            'coupon_id' => 'nullable',
            'payment_method' => 'required',
            'delivery_address_id'  => 'nullable',
            'delivery_cost'  => 'nullable'
        ]);

        if ($validator->fails())
        {
            return $this->errorResponse($validator->errors(),Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $user = $this->getTokenAndUser($request);

        $cart = OrderItem::where('user_id',$user->id)->where('status',0)->with(['product','sentence'])->get();

        if ($cart->isEmpty())
        {
            return $this->errorResponse('سبد خرید شما خالیست',Response::HTTP_NOT_FOUND);
        }

        $price = 0;

        foreach ($cart as $item)
        {
            $price += $item->product->price * $item->quantity;
        }

        $discountPrice = 0;

        if ($request->has('coupon_id'))
        {
            $coupon = Coupon::where('id',$request->coupon_id)->first();

            if (!$coupon)
            {
                return $this->errorResponse('چنین کدی یافت نشد',Response::HTTP_NOT_FOUND);
            }

            $infoDiscount = $this->calculateDiscount(new Request([
                'code' => $coupon->code,
                'total_price' => $price
            ]));

            if ($infoDiscount->getStatusCode() != 200)
            {
                $errorData = json_decode($infoDiscount->getContent(), true);
                $errorMessage = $errorData['message'] ?? 'خطا در محاسبه تخفیف';
                return $this->errorResponse($errorMessage, $infoDiscount->getStatusCode());
            }
            $response = json_decode($infoDiscount->getContent(),true);

            $discountPrice = $response['discount_price'];
        }

        $totalPrice = $price-$discountPrice;

        $order = Order::create([
            'user_id' => $user->id,
            'payment_method' => $request->payment_method,
            'price' => $price,
            'discount_price' => $discountPrice,
            'total_price' => $totalPrice,
            'coupon_id' => $request->has('coupon_id') ? $request->coupon_id : null,
            'delivery_address_id'  => ( $request->payment_method == 0 ? null : $request->delivery_address_id ),
            'delivery_cost'  => ( $request->payment_method == 0 ? null : $request->delivery_cost ),
            'status' => 0
        ]);

        return $order;
    }

    public function calculateDiscount(Request $request)
    {
        $validator = Validator::make($request->all(),[
            'code' => 'required',
            'total_price' => 'required'
        ]);

        if ($validator->fails())
        {
            return $this->errorResponse($validator->errors(),Response::HTTP_UNPROCESSABLE_ENTITY);
        }
        $code = Coupon::where('code',$request->code)->where('expired_at','>',now())->first();
        if (!$code)
        {
            return $this->errorResponse('چنین کدی یافت نشد',Response::HTTP_NOT_FOUND);
        }

        $statusCoupon = $this->checkCoupon($code);

        if ($statusCoupon)
        {
            return $this->errorResponse('قبلا از این کد استفاده کرده اید',Response::HTTP_BAD_REQUEST);
        }

        if ($code->type == self::COUPON_TYPE_FIXED && $request->total_price < $code->discount_amount)
        {
            return $this->errorResponse('برای استفاده از این کد تخفیف حداقل خرید شما باید'.$code->price.'تومان است',Response::HTTP_BAD_REQUEST);
        }

        if ($code->type == self::COUPON_TYPE_FIXED)
        {
            $discountPrice = $code->discount_amount;
            $totalPrice = $request->total_price - $code->discount_amount;
        }
        else
        {
            $discountPrice = ($request->total_price * $code->percentage / 100);

            $totalPrice = $request->total_price - $discountPrice;
        }

        if ($totalPrice < 0)
        {
            $totalPrice = 0;
        }

        return response()->json([
            'total_price' => $totalPrice,
            'coupon_id' => $code->id,
            'discount_price' => $discountPrice
        ],Response::HTTP_OK);
    }

    private function checkCoupon($code)
    {

        $order = Order::where('coupon_id',$code->id)->where('status',1)->first();

        if ($order)
        {
            return true;
        }

        return false;
    }
}
