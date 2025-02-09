<?php

namespace App\Http\Controllers\V1;

use App\Http\Controllers\ApiController;
use App\Http\Controllers\Controller;
use App\Models\Message;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Symfony\Component\HttpFoundation\Response;

class MessageController extends ApiController
{
    public function index(Request $request)
    {
       $user = $this->getTokenAndUser($request);

       $messagesForUser = Message::where('user_id',$user->id)->where('expired_at','>',now())->get();
       $publicMessages = Message::where('user_id',null)->where('expired_at','>',now())->get();
       $data = [$publicMessages,$messagesForUser];

       if ( $messagesForUser->isEmpty() && $publicMessages->isEmpty() )
       {
            return $this->errorResponse('پیامی جهت نمایش وجود ندارد',Response::HTTP_NOT_FOUND);
       }

       return $this->successResponse($data,'پیام ها جهت نمایش به کاربر',Response::HTTP_OK);
    }

    public function showMessage(Request $request)
    {
        $user = $this->getTokenAndUser($request);

        $validator = Validator::make($request->all(),[
            'message_id' => 'required'
        ]);

        if ($validator->fails())
        {
            return $this->errorResponse($validator->errors(),Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $messageId = $request->message_id;

        $message = Message::where('id',$messageId)->where('expired_at','>',now())->first();

        if ($message == null)
        {
            return $this->errorResponse('پیامی جهت نمایش وجود ندارد',Response::HTTP_NOT_FOUND);
        }

        $message->update([
            'is_read' => 1
        ]);

        return $this->successResponse($message,'پیام یافت شد',Response::HTTP_OK);
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
