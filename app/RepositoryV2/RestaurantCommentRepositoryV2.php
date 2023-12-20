<?php

namespace App\RepositoryV2;

use App\Models\Restaurant_comment;

class RestaurantCommentRepositoryV2
{
    public function ExistByUidAndRid($UserId, $Rid)
    {
        return Restaurant_comment::where("uid", '=', $UserId)->where('rid', '=', $Rid)->exists();
    }
    public function Create($Comment)
    {
        Restaurant_comment::create($Comment);
    }
    public function GetByRid($Rid, $Option)
    {
        $offset = $Option['offset'];
        $limit = $Option['limit'];
        return Restaurant_comment::select('users.name', 'restaurant_comments.point', 'restaurant_comments.comment', 'restaurant_comments.created_at')
            ->join('users', 'users.id', '=', 'restaurant_comments.uid')->where('restaurant_comments.rid', '=', $Rid)
            ->offset($offset)->limit($limit)->get();
    }
}