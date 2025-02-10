<?php

namespace App\Http\Controllers\V1;

use App\Http\Controllers\ApiController;
use App\Http\Resources\V1\ProductResource;
use App\Http\Resources\V1\SentenceResource;
use App\Models\Coupon;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Symfony\Component\HttpFoundation\Response;

class CartController extends ApiController
{
    const COUPON_TYPE_FIXED = 0;
    const COUPON_TYPE_PERCENTAGE = 1;
    public function index(Request $request)
    {
        $user = $this->getTokenAndUser($request);

        $cart = OrderItem::where('user_id',$user->id)->where('status',0)->with(['product','sentence'])->get();

        if ($cart->isEmpty())
        {
            return $this->errorResponse('سبد خرید شما خالیست',Response::HTTP_NOT_FOUND);
        }

        $data = $cart->map(function ($cart){
            return [
                'id' => $cart->id,
                'product' => new ProductResource($cart->product),
                'sentence' => new SentenceResource($cart->sentence),
                'size' => $cart->size,
                'quantity' => $cart->quantity,
                'price' => $cart->product->price * $cart->quantity,
                'order_sender' => $cart->order_sender,
                'order_receiver' => $cart->order_receiver,
            ];
        });

        $totalPrice = $data->sum('price');

        return response()->json([
            'items' => $data,
            'total_price' => $totalPrice
        ],Response::HTTP_OK);
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
    public function increaseQuantity(Request $request)
    {
        $validator = Validator::make($request->all(),[
            'order_item_id' => 'required'
        ]);

        if ($validator->fails())
        {
            return $this->errorResponse($validator->errors(),Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $orderItem = OrderItem::where('id',$request->order_item_id)->first();

        if (!$orderItem)
        {
            return $this->errorResponse('چنین محصولی به سبد شما اضافه نشده است',Response::HTTP_NOT_FOUND);
        }

        $orderItem->update([
            'quantity' => $orderItem->quantity + 1
        ]);

        return $this->successResponse('','تعداد محصول موجود در سبد خرید شما افزایش پیدا کرد',Response::HTTP_OK);
    }
    public function decreaseQuantity(Request $request)
    {
        $validator = Validator::make($request->all(),[
            'order_item_id' => 'required'
        ]);

        if ($validator->fails())
        {
            return $this->errorResponse($validator->errors(),Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $orderItem = OrderItem::where('id',$request->order_item_id)->first();

        if (!$orderItem)
        {
            return $this->errorResponse('چنین محصولی به سبد شما اضافه نشده است',Response::HTTP_NOT_FOUND);
        }
        elseif ($orderItem->quantity == 1)
        {
            return $this->errorResponse('کاهش امکان پذیر نیست',Response::HTTP_BAD_REQUEST);
        }

        $orderItem->update([
            'quantity' =>  $orderItem->quantity - 1
        ]);

        return $this->successResponse('','تعداد محصول موجود در سبد خرید شما کاهش پیدا کرد',Response::HTTP_OK);
    }
    public function updateSenderAndReceiver(Request $request)
    {
        $validator = Validator::make($request->all(),[
            'order_item_id' => 'required',
            'order_sender' => 'nullable|string',
            'order_receiver' => 'nullable|string',
        ]);

        if ($validator->fails())
        {
            return $this->errorResponse($validator->errors(),Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $orderItem = OrderItem::where('id',$request->order_item_id)->first();

        if (!$orderItem)
        {
            return $this->errorResponse('چنین محصولی به سبد شما اضافه نشده است',Response::HTTP_NOT_FOUND);
        }

        $orderItem->update([
            'order_sender' => ( $request->has('order_sender') ? $request->order_sender : $orderItem->order_sender),
            'order_receiver' => ( $request->has('order_receiver') ? $request->order_receiver : $orderItem->order_receiver),
        ]);

        return $this->successResponse($orderItem,'امضا تغییر کرد',Response::HTTP_OK);
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
