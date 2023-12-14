<?php

namespace App\RepositoryV2;

use Tymon\JWTAuth\Facades\JWTAuth;

class UserRepositoryV2
{
    public function GetUserInfo()
    {
        try {
            return JWTAuth::parseToken()->authenticate();
        } catch (\Throwable $e) {
            false;
        }
    }
}