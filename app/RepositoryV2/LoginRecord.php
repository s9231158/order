<?php

namespace App\RepositoryV2;

use App\Models\User_recode;
use Throwable;

class LoginRecord
{
    public function Create($LoginInfo)
    {
        try {
            User_recode::create($LoginInfo);
        } catch (Throwable $e) {
            throw new \Exception("RepossitoryErr:" . 500);
        }
    }
    public function GetById($UserId, $Option)
    {
        try {
            return User_recode::offset($Option['offset'])
                ->limit($Option['limit'])
                ->orderBy('login', 'desc')
                ->where('uid', '=', $UserId)
                ->get();
        } catch (Throwable $e) {
            throw new \Exception("RepossitoryErr:" . 500);
        }
    }
}
