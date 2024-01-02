<?php

namespace App;

use Illuminate\Support\Facades\Cache;
use Tymon\JWTAuth\Facades\JWTAuth;

class TotalService
{
    public function __construct()
    {
    }
    public function GetOffsetLimit($Option)
    {
        $Offset = 0;
        $Limit = 20;
        if ($Option['offset'] != null) {
            $Offset = $Option['offset'];
        }
        if ($Option['limit'] != null) {
            $Limit = $Option['limit'];
        }
        return array('offset' => $Offset, 'limit' => $Limit);
    }
    
    public function GetUserInfo()
    {
        try {
            $User = JWTAuth::parseToken()->authenticate();
            return $User;
        } catch (\Throwable $e) {
            return $e;
        }
    }
    public function CheckHasLogin($Token, $Email)
    {
        try {
            $RedisToken = 'Bearer ' . Cache::get($Email);
            //有emial 有token 但token錯誤 系統錯誤
            if (Cache::has($Email) && $Token !== null && $Token !== $RedisToken) {
                Cache::forget($Email);
                return 5;
            }
            //有email 沒token 重別的裝置登入
            if (Cache::has($Email) && $Token === null) {
                Cache::forget($Email);
                return 31;
            }
            if (!Cache::has($Email)) {
                return 5;
            }
            return true;
        } catch (\Throwable $e) {
            return 'err';
        }

    }
    public function CheckToken($Token)
    {
        try {
            JWTAuth::parseToken()->authenticate();
            return true;
        } catch (\Throwable $e) {
            return false;
        }
    }
}
