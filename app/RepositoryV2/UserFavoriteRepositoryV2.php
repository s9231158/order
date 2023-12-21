<?php

namespace App\RepositoryV2;

use App\Models\User_favorite;

class UserFavoriteRepositoryV2
{
    public function GetByUserId($UserId)
    {
        return User_favorite::where("uid", "=", $UserId)->get();
    }
    public function CheckRidExist($UserId, $Rid)
    {
        return User_favorite::where("uid", "=", $UserId)->where('rid', '=', $Rid)->exists();
    }
    public function Create($FavoriteInfo)
    {
        User_favorite::create($FavoriteInfo);
    }
    public function GetByUserIdOnRange($UserId, $Option)
    {
        return User_favorite::select('rid')->where('uid', '=', $UserId)->limit($Option['limit'])->offset($Option['offset'])->get();
    }
}