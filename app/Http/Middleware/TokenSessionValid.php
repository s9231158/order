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
        //成功
        '0' => 0,
        //資料填寫與規格不符
        '1' => 1,
        //必填資料未填
        '2' => 2,
        //email已註冊
        '3' => 3,
        //電話已註冊
        '4' => 4,
        //系統錯誤,請重新登入
        '5' => 5,
        //已登入
        '6' => 6,
        //短時間內登入次數過多
        '7' => 7,
        //帳號或密碼錯誤
        '8' => 8,
        //token錯誤
        '9' => 9,
        //無效的範圍
        '23' => 23,
        //系統錯誤,稍後在試
        '26' => 26,
        //未登入
        '10' => 10,
        //請重新登入
        '29' => 29,
    ];
    public function handle(Request $request, Closure $next)
    {
        try {
            $a = JWTAuth::parseToken()->authenticate();
            $email = $a->email;
            $clienttoken = $request->header('Authorization');
            $redietoken = 'Bearer ' . Cache::get($email);
            if (Cache::has($email) && $clienttoken !== $redietoken) {
                Cache::forget($email);
                return response()->json(['err' => $this->err['28']]);
            }
            // JWTAuth::parseToken()->authenticate();
            if (!Cache::has($email)) {
                return response()->json(['err' => $this->err['28']]);
            }
        } catch (Exception) {
            return response()->json(['err' => $this->err['5']]);
        }
        return $next($request);
    }
}
