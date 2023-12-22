<?php

namespace App\RepositoryV2;

use App\Models\User_favorite;
use Throwable;

class UserFavoriteRepositoryV2
{
    public function GetByUserId($UserId)
    {
        try {
            return User_favorite::where("uid", "=", $UserId)->get();
        } catch (Throwable $e) {
            throw new \Exception("RepossitoryErr:" . 500);
        }
    }
    public function CheckRidExist($UserId, $Rid)
    {
        try {
            return User_favorite::where("uid", "=", $UserId)->where('rid', '=', $Rid)->exists();
        } catch (Throwable $e) {
            throw new \Exception("RepossitoryErr:" . 500);
        }
    }
    public function Create($FavoriteInfo)
    {
        try {
            User_favorite::create($FavoriteInfo);
        } catch (Throwable $e) {
            throw new \Exception("RepossitoryErr:" . 500);
        }
    }
    public function GetRidByUserIdOnRange($UserId, $Option)
    {
        try {
            return User_favorite::select('rid')
                ->where('uid', '=', $UserId)
                ->limit($Option['limit'])
                ->offset($Option['offset'])
                ->get();
        } catch (Throwable $e) {
            throw new \Exception("RepossitoryErr:" . 500);
        }
    }
    public function Delete($UserId, $Rid)
    {
        try {
            User_favorite::select('id')->where('uid', '=', $UserId)->where('rid', '=', $Rid)->delete();
        } catch (Throwable $e) {
            throw new \Exception("RepossitoryErr:" . 500);
        }
    }
}
