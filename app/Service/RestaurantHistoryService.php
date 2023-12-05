<?php
namespace App\Service;

use App\Models\Restaurant_history;

class RestaurantHistoryService
{
    public function GetRestaurantHistory($UserId, $Option)
    {
        $Offset = $Option['offset'];
        $Limit = $Option['limit'];
        $Rid = Restaurant_history::wherein('uid', $UserId)->limit($Limit)->offset($Offset)->get();
        return $Rid;
    }
}


?>