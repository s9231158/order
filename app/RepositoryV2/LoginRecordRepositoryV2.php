<?php

namespace App\RepositoryV2;

use App\Models\User_recode;

class LoginRecordRepositoryV2
{
    public function Create($LoginInfo)
    {
        User_recode::create($LoginInfo);
    }
    public function GetById($UserId, $Option)
    {
        return User_recode::offset($Option['offset'])
            ->limit($Option['limit'])
            ->orderBy('login', 'desc')
            ->where('uid', '=', $UserId)
            ->get();
    }
}