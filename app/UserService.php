<?php

namespace App;

use App\Models\Wallet_Record;
use PhpParser\Node\Stmt\Catch_;
use Throwable;
use Tymon\JWTAuth\Facades\JWTAuth;
use Tymon\JWTAuth\Exceptions\TokenInvalidException;

class UserService
{
    private $token;
    private $userinfo;
    public function __construct($token)
    {
        $this->token = $token;
        $this->userinfo = JWTAuth::parseToken()->authenticate();
    }

    public function UserCheck()
    {
        try {
            JWTAuth::parseToken()->authenticate();
        } catch (\Exception $e) {
            return false;
        } catch (Throwable $e) {
            return false;
        }
    }
    public function UserInfo()
    {
        try {
            $userInfo = JWTAuth::parseToken()->authenticate();
            return $userInfo;
        } catch (Throwable $e) {
            return false;
        }
    }

    public function GetUserWallet($request)
    {
        try {
            $offset = 20;
            $limit = 50;
            if ($request->offset != null) {
                $offset = $request->offset;
            }
            if ($request->limit != null) {
                $limit = $request->limit;
            }
            if ($request->all === null) {
                $WalletRecord =  Wallet_Record::where("uid", '=', $this->userinfo->id)->get();
                return $request;
            }
        } catch (\Exception $e) {
        }
    }
}
