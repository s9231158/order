<?php

namespace App\UserRepository;

use App\Models\User;
use App\Models\User_recode;
use Throwable;

class LoginRepository
{
    public function CreatrLoginRecord($RocordInfo)
    {
        try {
            $UserId = User::select("id")->where("email", '=', $RocordInfo['email'])->first();
            $RocordInfo['uid'] = $UserId['id'];
            unset($RocordInfo['email']);
            User_recode::create($RocordInfo);
            return true;
        } catch (Throwable $e) {
        }
    }

    public function GetUserInfo($email)
    {
        try {
            $UserId = User::select("id")->where("email", '=', $email)->first();
            $User = User::find($UserId['id']);
            return $User;
        } catch (Throwable $e) {
        }
    }
}



?>