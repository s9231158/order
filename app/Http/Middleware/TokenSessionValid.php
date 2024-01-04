<?php

namespace App\Http\Middleware;

use \Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Closure;
use App\ErrorCodeService;
use Exception;
use Illuminate\Http\Request;
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
    private $token;
    public function __construct(ErrorCodeService $errorCodeService)
    {
        $authorizationHeader = $_SERVER['HTTP_AUTHORIZATION'] ?? null;
        $this->token = str_replace('Bearer ', '', $authorizationHeader);
        $this->err = $errorCodeService->GetErrCode();
        $this->keys = $errorCodeService->GetErrKey();
    }
    public function handle(Request $Request, Closure $next)
    {
        try {
            $payload = JWT::decode($this->token, new Key(env('JWT_SECRET'), 'HS256'));
            $Email = $payload->email;
            $RedisToken = Cache::get($Email);
            if (Cache::has($Email) && $this->token !== $RedisToken) {
                Cache::forget($Email);
                return response()->json([
                    'err' => $this->keys[29],
                    'message' => $this->err[29],
                ]);
            }
            if (!Cache::has($Email)) {
                Cache::forget($Email);
                return response()->json([
                    'err' => $this->keys[29],
                    'message' => $this->err[29],
                ]);
            }
        } catch (Exception $e) {
            Cache::forget($Email);
            return response()->json([
                'err' => $this->keys[29],
                'message' => $this->err[29],
            ]);
        }
        return $next($Request);
    }
}
