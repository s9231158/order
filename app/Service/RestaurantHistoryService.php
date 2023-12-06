<?php
namespace App\Service;

use App\Models\Restaurant_history;

class RestaurantHistoryService
{
    public function GetRestaurantHistoryOption($UserId, $Option)
    {
        $Offset = $Option['offset'];
        $Limit = $Option['limit'];
        $Rid = Restaurant_history::wherein('uid', $UserId)->limit($Limit)->offset($Offset)->get();
        return $Rid;
    }

    public function UpdateOrCreateHistory($UserId, $Rid)
    {
        Restaurant_history::updateOrCreate(
            ['uid' => $UserId, 'rid' => $Rid],
            ['created_at' => now(), 'updated_at' => now()]
        );
    }

}


?>