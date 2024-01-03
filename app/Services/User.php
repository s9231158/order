<?php

namespace App\Services;

use App\Models\User as UserModel;
use Throwable;

class User
{
    public function getObjByEamil($email)
    {
        try {
            return UserModel::where('email', '=', $email)->first();
        } catch (Throwable $e) {
            throw new \Exception("user_service_err:" . 500);
        }
    }

    public function phoneExist($phone)
    {
        try {
            return UserModel::Where('phone', '=', $phone)->exists();
        } catch (Throwable $e) {
            throw new \Exception("user_service_err:" . 500);
        }
    }

    public function create($userInfo)
    {
        try {
            return UserModel::create($userInfo);
        } catch (Throwable $e) {
            throw new \Exception("user_service_err:" . 500);
        }
    }
}
