<?php

namespace App\RepositoryV2;

use App\Models\User as UserModel;
use Throwable;
use Tymon\JWTAuth\Facades\JWTAuth;

class User
{
    public function GetUserInfo()
    {
        try {
            return JWTAuth::parseToken()->authenticate();
        } catch (Throwable $e) {
            throw new \Exception("RepossitoryErr:" . 500);
        }
    }
    public function Create($UserInfo)
    {
        try {
            UserModel::create($UserInfo);
        } catch (Throwable $e) {
            throw new \Exception("Repository:" . 500);
        }
    }
    public function GetInfoByEmil($Email)
    {
        try {
            return UserModel::where("email", '=', $Email)->get();
        } catch (Throwable $e) {
            throw new \Exception("Repository:" . 500);
        }
    }
}
