<?php

namespace App;

use App\Models\Restaurant;
use Illuminate\Support\Facades\Cache;
use Tymon\JWTAuth\Facades\JWTAuth;

class TotalService
{

    /**
     * 設定offsey&limit預設值,直接把客戶端requset丟進來
     *
     * @return array
     */
    //名稱有問題
    public static function GetOffsetLimit($request)
    {
        $offset = 0;
        $limit = 20;
        if ($request['offset'] != null) {
            $offset = $request['offset'];
        }
        if ($request['limit'] != null) {
            $limit = $request['limit'];
        }
        return array('offset' => $offset, 'limit' => $limit);
    }
    public static function CheckHasLogin($TokenEmail)
    {
        try {
            $Token = $TokenEmail['Token'];
            $Redistoken = 'Bearer ' . Cache::get($TokenEmail['Email']);
            //有emial 有token 但token錯誤 系統錯誤
            if (Cache::has($TokenEmail['Email']) && $Token !== null && $Token !== $Redistoken) {
                Cache::forget($TokenEmail['Email']);
                return 5;
            }
            //有email 沒token 重別的裝置登入
            if (Cache::has($TokenEmail['Email']) && $Token === null) {
                Cache::forget($TokenEmail['Email']);
                return 31;
            }
            return true;
        } catch (\Throwable $e) {
            return 'err';
        }

    }
    public static function GetUserInfo()
    {
        try {
            $User = JWTAuth::parseToken()->authenticate();
            return $User;
        } catch (\Throwable $e) {
            return 'err';

        }
    }

    public static function CheckRestaurantInDatabase($rid)
    {
        return Restaurant::where('id', '=', $rid)->count();
    }
}
