<?php

namespace App\Http\Middleware;

use Closure;
use App\ErrorCodeService;
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
    private $err = [];
    private $keys = [];
    public function __construct(ErrorCodeService $errorCodeService)
    {
        $this->err = $errorCodeService->GetErrCode();
        $this->keys = $errorCodeService->GetErrKey();
    }
    public function handle(Request $Request, Closure $next)
    {
        try {
            $User = JWTAuth::parseToken()->authenticate();
            $Email = $User->email;
            $ClientToken = $Request->header('Authorization');
            $RedisToken = 'Bearer ' . Cache::get($Email);
            if (Cache::has($Email) && $ClientToken !== $RedisToken) {
                Cache::forget($Email);
                return response()->json([
                    'err' => $this->keys[29],
                    'message' => $this->err[29],
                ]);
            }
            // JWTAuth::parseToken()->authenticate();
            if (!Cache::has($Email)) {
                return response()->json([
                    'err' => $this->keys[29],
                    'message' => $this->err[29],
                ]);
            }
        } catch (Exception) {
            return response()->json([
                'err' => $this->keys[29],
                'message' => $this->err[29],
            ]);
        }
        return $next($Request);
    }
}
