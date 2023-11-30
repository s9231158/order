<?php

namespace App;

use Illuminate\Support\Facades\Cache;

class TotalService
{

    /**
     * 設定offsey&limit預設值,直接把客戶端requset丟進來
     *
     * @return array
     */
    //名稱有問題
    public function GetOffsetLimit($request)
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
            if (Cache::has($TokenEmail['Email']) && $Token === $Redistoken) {
                Cache::forget($TokenEmail['Email']);
                return 5;
            }
            return true;
        } catch (\Throwable $e) {
            return null;
        }


    }
}
