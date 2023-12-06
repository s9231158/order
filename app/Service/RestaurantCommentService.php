<?php
namespace App\Service;

use App\Models\Restaurant_comment;

class RestaurantCommentService
{
    public function GetComment($UserId)
    {
        return Restaurant_comment::where("uid", '=', $UserId)->get();
    }
}



?>