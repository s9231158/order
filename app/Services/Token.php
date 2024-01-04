<?php

namespace App\Services;

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Throwable;
use \Firebase\JWT\JWT;
use Firebase\JWT\Key;

class Token
{
    private $token;
    public function __construct()
    {
        $authorizationHeader = $_SERVER['HTTP_AUTHORIZATION'] ?? null;
        $this->token = str_replace('Bearer ', '', $authorizationHeader);
    }
    public function checkToken($email) //邏輯問題
    {
        try {
            $RedisToken = Cache::get($email);
            //有emial 有token 但token錯誤 系統錯誤
            if (Cache::has($email) && $this->token && $this->token !== $RedisToken) {
                Cache::forget($email);
                throw new \Exception('系統錯誤請重新登入');
            }
            //有email 沒token 重別的裝置登入
            if (Cache::has($email) && $this->token === null) {
                Cache::forget($email);
                throw new \Exception('已重其他裝置登入');
            }
            if (!Cache::has($email)) {
                Cache::forget($email);
                throw new \Exception('請先登入');
            }
            return true;
        } catch (Throwable $e) {
            throw new \Exception($e->getMessage());
        }
    }
    public function create($userInfo)
    {
        try {
            $time = Carbon::now()->addDay();
            $payload = [
                'id' => $userInfo['id'],
                'name' => $userInfo['name'],
                'email' => $userInfo['email'],
                'exp' => $time
            ];
            $token = JWT::encode($payload, env('JWT_SECRET'), 'HS256');
            Cache::put($userInfo['email'], $token, 60 * 60 * 24);
            return $token;
        } catch (Throwable $e) {
            throw new \Exception("token_service_err:" . 500 . $e);
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
            $payload = JWT::decode($this->token, new Key(env('JWT_SECRET'), 'HS256'));
            $email = $payload->email;
            return $email;
        } catch (Throwable $e) {
            throw new \Exception('系統錯誤請重新登入');
        }
    }
    public function getUserId()
    {
        try {
            $payload = JWT::decode($this->token, new Key(env('JWT_SECRET'), 'HS256'));
            $userId = $payload->id;
            return $userId;
        } catch (Throwable $e) {
            throw new \Exception('系統錯誤請重新登入');
        }
    }
}
