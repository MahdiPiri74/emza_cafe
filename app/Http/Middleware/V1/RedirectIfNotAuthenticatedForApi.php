<?php

namespace App\Http\Middleware\V1;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Laravel\Sanctum\PersonalAccessToken;
use Symfony\Component\HttpFoundation\Response;
use function Symfony\Component\String\u;

class RedirectIfNotAuthenticatedForApi
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        //check token and expired token
        $token = substr($request->header('authorization'),7);
        $user = User::where('token',$token)->first();
        $personalToken = PersonalAccessToken::where('tokenable_id', $user->id)->latest('created_at')->first();

        if ( !$user || $personalToken->expires_at < now() )
        {
            return \response()->json([
                'status' => 'error',
                'message' => 'ابتدا وارد حساب کاربری خود شوید'
            ],401);
        }
        return $next($request);
    }
}
