<?php

namespace App\RepositoryV2;

use App\Models\RestaurantComment as Restaurant_comment;
use Throwable;

class RestaurantComment
{
    public function ExistByUidAndRid($UserId, $Rid)
    {
        try {
            return Restaurant_comment::where("uid", '=', $UserId)->where('rid', '=', $Rid)->exists();
        } catch (Throwable $e) {
            throw new \Exception("RepossitoryErr:" . 500);
        }
    }
    public function Create($Comment)
    {
        try {
            Restaurant_comment::create($Comment);
        } catch (Throwable $e) {
            throw new \Exception("RepossitoryErr:" . 500);
        }
    }
    public function GetByRid($Rid, $Option)
    {
        try {
            $Offset = $Option['offset'];
            $Limit = $Option['limit'];
            return Restaurant_comment::select(
                'users.name',
                'restaurant_comments.point',
                'restaurant_comments.comment',
                'restaurant_comments.created_at'
            )
                ->join('users', 'users.id', '=', 'restaurant_comments.uid')
                ->where('restaurant_comments.rid', '=', $Rid)
                ->offset($Offset)
                ->limit($Limit)
                ->get();
        } catch (Throwable $e) {
            throw new \Exception("RepossitoryErr:" . 500);
        }
    }
}
