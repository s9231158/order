<?php

namespace App\Services;

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Throwable;
use Tymon\JWTAuth\Facades\JWTAuth;

class Token
{
    private $token;
    private $authorizationHeader;
    public function __construct()
    {
        $authorizationHeader = $_SERVER['HTTP_AUTHORIZATION'] ?? null;
        $this->token = str_replace('Bearer ', '', $authorizationHeader);
    }
    public function checkToken($email)
    {
        try {
            $RedisToken = Cache::get($email);
            //有emial 有token 但token錯誤 系統錯誤
            if (Cache::has($email) && $this->token !== null && $this->token !== $RedisToken) {
                Cache::forget($email);
                return 5;
            }
            //有email 沒token 重別的裝置登入
            if (Cache::has($email) && $this->token === null) {
                Cache::forget($email);
                return 31;
            }
            if (!Cache::has($email)) {
                Cache::forget($email);
                return 5;
            }
            return true;
        } catch (Throwable $e) {
            throw new \Exception("token_service_err:" . 500);
        }
    }
    public function create($userInfo)
    {
        try {
            $id = $userInfo->id;
            $name = $userInfo->name;
            $email = $userInfo->email;
            $time = Carbon::now()->addDay();
            $userClaims = [
                'id' => $id,
                'name' => $name,
                'email' => $email,
                'exp' => $time
            ];
            $token = JWTAuth::claims($userClaims)->fromUser($userInfo);
            Cache::put($email, $token, 60 * 60 * 24);
            return $token;
        } catch (Throwable $e) {
            throw new \Exception("token_service_err:" . 500);
        }
    }
    public function forget($email)
    {
        try {
            if (Cache::has($email)) {
                Cache::forget($email);
                return true;
            }
            return false;

        } catch (Throwable $e) {
            throw new \Exception("token_service_err:" . 500);
        }
    }
    public function getEamil()
    {
        try {
            $payload = JWTAuth::parseToken($this->token)->getPayload();
            $email = $payload['email'];
            return $email;
        } catch (Throwable $e) {
            throw new \Exception("token_service_err:" . '請重新登入');
        }
    }
    public function getUserId()
    {
        try {
            $payload = JWTAuth::parseToken($this->token)->getPayload();
            $userId = $payload['id'];
            return $userId;
        } catch (Throwable $e) {
            throw new \Exception("token_service_err:" . '請重新登入');
        }
    }
}
