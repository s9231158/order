<?php

namespace App\Services;

use App\Models\User as UserModel;

class User
{
    public function getObjByEamil($email)
    {
        return UserModel::where('email', '=', $email)->first();
    }
    public function phoneExist($phone)
    {
        return UserModel::Where('phone', '=', $phone)->exists();
    }
    public function create($userInfo)
    {
        return UserModel::create($userInfo);
    }
}