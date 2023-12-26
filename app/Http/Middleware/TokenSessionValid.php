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
     * @param  \Illuminate\Http\Request  $Request
     * @param  \Closure(\Illuminate\Http\Request): (\Illuminate\Http\Response|\Illuminate\Http\RedirectResponse)  $next
     * @return \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse
     */
    private $Err = [
        //系統錯誤,請重新登入
        '5' => 5,
        //請重新登入
        '29' => 29,
    ];
    public function handle(Request $Request, Closure $next)
    {
        try {
            $User = JWTAuth::parseToken()->authenticate();
            $Email = $User->email;
            $ClientToken = $Request->header('Authorization');
            $RedisToken = 'Bearer ' . Cache::get($Email);
            if (Cache::has($Email) && $ClientToken !== $RedisToken) {
                Cache::forget($Email);
                return response()->json(['err' => $this->Err['29']]);
            }
            // JWTAuth::parseToken()->authenticate();
            if (!Cache::has($Email)) {
                return response()->json(['err' => $this->Err['29']]);
            }
        } catch (Exception) {
            return response()->json(['err' => $this->Err['5']]);
        }
        return $next($Request);
    }
}
