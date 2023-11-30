<?php

namespace App\UserRepository;

use App\Models\User_recode;
use Throwable;

class LoginRepository
{
    public function CreatrLoginRecord($RocordInfo)
    {
        try {
            User_recode::create($RocordInfo);
            return true;
        } catch (Throwable $e) {
        }
    }
}



?>