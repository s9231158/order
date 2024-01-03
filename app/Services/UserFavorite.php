<?php

namespace App\Services;

use App\Models\User_favorite;
use Throwable;

class UserFavorite
{
    public function create($favoriteInfo)
    {
        try {
            return User_favorite::create($favoriteInfo);
        } catch (Throwable $e) {
            throw new \Exception("user_favorite_service_err:" . 500 . $e);
        }
    }
    public function getListByUser($userId)
    {
        try {
            return User_favorite::where('uid', '=', $userId)->get()->toArray();
        } catch (Throwable $e) {
            throw new \Exception("user_favorite_service_err:" . 500);
        }
    }
    public function delByUserIdAndRid($userId, $rid)
    {
        try {
            return User_favorite::where('uid', '=', $userId)->where('rid', '=', $rid)->delete();
        } catch (Throwable $e) {
            throw new \Exception("user_favorite_service_err:" . 500);
        }
    }
}