<?php

namespace App\Http\Middleware;

use App\Services\Token;
use Closure;
use Exception;
use App\Services\ErrorCode;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use UnexpectedValueException;
use LogicException;
use \Firebase\JWT\JWT;
use Firebase\JWT\Key;

class TokenSessionValid
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure(\Illuminate\Http\Request): (\Illuminate\Http\Response|\Illuminate\Http\RedirectResponse)  $next
     * @return \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse
     */
    private $err = [];
    private $keys = [];
    private $token;
    private $authorizationHeader;
    public function __construct(ErrorCode $errorCodeService)
    {
        $this->authorizationHeader = $_SERVER['HTTP_AUTHORIZATION'] ?? null;
        $this->token = str_replace('Bearer ', '', $this->authorizationHeader);
        $this->err = $errorCodeService->GetErrCode();
        $this->keys = $errorCodeService->GetErrKey();
    }
    public function handle(Request $request, Closure $next)
    {
        try {
            if (!$this->authorizationHeader) {
                return response()->json([
                    'err' => $this->keys[10],
                    'message' => $this->err[10],
                ]);
            }
            JWT::decode($this->token, new Key(env('JWT_SECRET'), 'HS256'));
            $tokenService = new Token;
            $email = $tokenService->getEamil();
            $redisToken = Cache::get($email);
            if (Cache::has($email) && $this->token !== $redisToken) {
                Cache::forget($email);
                return response()->json([
                    'err' => $this->keys[29],
                    'message' => $this->err[29],
                ]);
            }
            if (!Cache::has($email)) {
                Cache::forget($email);
                return response()->json([
                    'err' => $this->keys[29],
                    'message' => $this->err[29],
                ]);
            }
        } catch (LogicException $e) {
            return response()->json([
                'err' => $this->keys[9],
                'message' => $this->err[9],
            ]);
        } catch (UnexpectedValueException $e) {
            return response()->json([
                'err' => $this->keys[9],
                'message' => $this->err[9],
            ]);
        } catch (Exception $e) {
            Cache::forget($email);
            return response()->json([
                'err' => $this->keys[29],
                'message' => $this->err[29],
            ]);
        }
        return $next($request);
    }
}
