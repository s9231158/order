<?php

namespace App;

use App\Models\Restaurant;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Validator;
use Tymon\JWTAuth\Facades\JWTAuth;
use App\ErrorCodeService;

class TotalService
{

    private $ErrorCodeService;
    private $err = [];
    private $keys = [];
    public function __construct(ErrorCodeService $ErrorCodeService)
    {
        $this->ErrorCodeService = $ErrorCodeService;
        $this->err = $this->ErrorCodeService->GetErrCode();
        $this->keys = $this->ErrorCodeService->GetErrKey();
    }

    public function LimitOffsetValidator($Request)
    {
        try { //規則
            $ruls = [
                'limit' => ['regex:/^[0-9]+$/'],
                'offset' => ['regex:/^[0-9]+$/'],
            ];
            //什麼錯誤報什麼錯誤訊息
            $rulsMessage = [
                'limit.regex' => $this->keys['23'],
                'offset.regex' => $this->keys['23']
            ];
            $validator = Validator::make($Request->all(), $ruls, $rulsMessage);
            //驗證失敗回傳錯誤訊息
            if ($validator->fails()) {
                return response()->json(['err' => $validator->errors()->first(), 'message' => $this->err[$validator->errors()->first()]]);
            }
            return true;
        } catch (\Throwable $e) {
            return $e;
        }
    }

    public function GetOffsetLimit($OffsetLimit)
    {
        $offset = 0;
        $limit = 20;
        if ($OffsetLimit['offset'] != null) {
            $offset = $OffsetLimit['offset'];
        }
        if ($OffsetLimit['limit'] != null) {
            $limit = $OffsetLimit['limit'];
        }
        return array('offset' => $offset, 'limit' => $limit);
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


    public function CheckHasLogin($TokenEmail)
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
    // public static function GetUserInfo()
    // {
    //     try {
    //         $User = JWTAuth::parseToken()->authenticate();
    //         return $User;
    //     } catch (\Throwable $e) {
    //         return 'err';

    //     }
    // }

    public static function CheckRestaurantInDatabase($rid)
    {
        return Restaurant::where('id', '=', $rid)->count();
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