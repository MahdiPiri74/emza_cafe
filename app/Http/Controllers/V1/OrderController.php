<?php

namespace App\Http\Controllers\V1;

use App\Http\Controllers\ApiController;
use App\Http\Controllers\Controller;
use App\Models\orderItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Symfony\Component\HttpFoundation\Response;

class OrderController extends ApiController
{
    public function addToBasket(Request $request)
    {
        $validator = Validator::make($request->all(),
            [
                'product_id' => 'required',
                'size' => 'required',
                'quantity' => 'required',
                'user_id' => 'required',
                'template_id' => 'required',
                'sentence_id' => 'required',
                'order_sender' => 'nullable|string',
                'order_receiver' => 'nullable|string',
            ]);

        if ($validator->fails())
        {
            return $this->errorResponse($validator->errors(), Response::HTTP_UNPROCESSABLE_ENTITY);
        }
        orderItem::create([
            'product_id' => $request->product_id,
            'size' => $request->size,
            'quantity' => $request->quantity,
            'user_id' => $request->user_id,
            'template_id' => $request->template_id,
            'sentence_id' => $request->sentence_id,
            'order_sender' => ( $request->has('order_sender') ? $request->order_sender : null ),
            'order_receiver' => ( $request->has('order_receiver') ? $request->order_sender : null ),
        ]);

        return $this->successResponse(null,'محصول با موفقیت به سبد خرید اضافه شد',Response::HTTP_OK);
    }
}
