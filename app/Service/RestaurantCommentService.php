<?php
namespace App\Service;

use App\Models\Restaurant_comment;

class RestaurantCommentService
{
    public function GetUserComment($UserId)
    {
        return Restaurant_comment::where("uid", '=', $UserId)->get();
    }
    public function AddComment($Comment)
    {
        Restaurant_comment::create($Comment);
    }
    public function GetRestaurantComment($Rid, $Option)
    {
        $offset = $Option['offset'];
        $limit = $Option['limit'];
        return Restaurant_comment::select('users.name', 'restaurant_comments.point', 'restaurant_comments.comment', 'restaurant_comments.created_at')
            ->join('users', 'users.id', '=', 'restaurant_comments.uid')->where('restaurant_comments.rid', '=', $Rid)
            ->offset($offset)->limit($limit)->get();
    }


}



?>