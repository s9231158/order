<?php

namespace App\Http\Middleware;

use Closure;
use Exception;
use Illuminate\Http\Request;
use Tymon\JWTAuth\Facades\JWTAuth;
use Illuminate\Support\Facades\Cache;

class TokenSessionValid
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure(\Illuminate\Http\Request): (\Illuminate\Http\Response|\Illuminate\Http\RedirectResponse)  $next
     * @return \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse
     */
    private $err = [
        //系統錯誤,請重新登入
        '5' => 5,
        //請重新登入
        '29' => 29,
    ];
    public function handle(Request $request, Closure $next)
    {
        try {
            $user = JWTAuth::parseToken()->authenticate();
            $email = $user->email;
            $clienttoken = $request->header('Authorization');
            $redietoken = 'Bearer ' . Cache::get($email);
            if (Cache::has($email) && $clienttoken !== $redietoken) {
                Cache::forget($email);
                return response()->json(['err' => $this->err['29']]);
            }
            // JWTAuth::parseToken()->authenticate();
            if (!Cache::has($email)) {
                return response()->json(['err' => $this->err['29']]);
            }
        } catch (Exception) {
            return response()->json(['err' => $this->err['5']]);
        }
        return $next($request);
    }
}
