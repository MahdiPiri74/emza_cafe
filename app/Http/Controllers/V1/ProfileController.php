<?php

namespace App\Http\Controllers\V1;

use App\Http\Controllers\ApiController;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ProfileController extends ApiController
{
    public function index(Request $request)
    {
        $user = $this->getTokenAndUser($request);

        return $this->successResponse($user,'',Response::HTTP_OK);
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
