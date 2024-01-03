<?php

namespace App\Services;

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Throwable;
use Tymon\JWTAuth\Facades\JWTAuth;

class Token
{
    public function checkToken($token, $email)
    {
        try {
            $RedisToken = 'Bearer ' . Cache::get($email);
            //有emial 有token 但token錯誤 系統錯誤
            if (Cache::has($email) && $token !== null && $token !== $RedisToken) {
                Cache::forget($email);
                return 5;
            }
            //有email 沒token 重別的裝置登入
            if (Cache::has($email) && $token === null) {
                Cache::forget($email);
                return 31;
            }
            if (!Cache::has($email)) {
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
}