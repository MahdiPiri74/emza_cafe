<?php

namespace App\Http\Middleware\V1;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RedirectIfNotAuthenticatedForApi
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if ( !auth()->check() )
        {
            return response()->json([
                'status' => 'error',
                'message' => 'لطفا در ابتدا وارد حساب کاربری خود شوید'
            ],401);
        }
        return $next($request);
    }
}
