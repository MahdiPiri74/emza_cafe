<?php

namespace App\Traits;

trait ApiResponse
{
    protected function successResponse( $data, $message = null , $code)
    {
        return response()->json([
            'status' => 'success',
            'message' => $message,
            'data' => $data
        ],$code);
    }

    protected function errorResponse( $message , $code)
    {
        return response()->json([
            'status' => 'error',
            'message' => $message,
        ],$code);
    }
}
