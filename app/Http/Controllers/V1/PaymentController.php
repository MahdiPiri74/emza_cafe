<?php

namespace App\Http\Controllers\V1;

use App\Http\Controllers\ApiController;
use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class PaymentController extends ApiController
{
    public function send(Request $request)
    {

        $user = $this->getTokenAndUser($request);

        $order = Order::where('user_id',$user->id)->where('status',0)->first();

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
                $transaction = Transaction::where('token',$token)->first();

                $transaction->update([
                    'status' => 1 ,
                    'ref_number' => $result->transId,
                    'updated_at' => now()

                ]);

                Order::where('id',$transaction->order_id)->update([
                    'status' => 1 ,
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
}
