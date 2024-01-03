<?php

namespace App\Services;

use App\Models\User_recode;
use Throwable;

class UserRecord
{
    public function create($recordInfo)
    {
        try {
            return User_recode::create($recordInfo);
        } catch (Throwable $e) {
            throw new \Exception("user_record_service_err:" . 500);
        }
    }

    public function getListByUserIdOnRange($userId, $option)
    {
        try {
            return User_recode::where('uid', '=', $userId)
                ->limit($option['limit'])
                ->offset($option['offset'])
                ->orderBy('login', 'desc')
                ->get()
                ->toArray();
        } catch (Throwable $e) {
            throw new \Exception("user_record_service_err:" . 500);
        }

    }
}