<?php

namespace App\RepositoryV2;

use App\Models\Restaurant_history;
use Throwable;

class ResraurantHistory
{
    public function UpdateOrCreate($UserId, $Rid)
    {
        try {
            Restaurant_history::updateOrCreate(
                ['uid' => $UserId, 'rid' => $Rid],
                ['created_at' => now(), 'updated_at' => now()]
            );
        } catch (Throwable $e) {
            throw new \Exception("RepossitoryErr:" . 500);
        }
    }
    public function GetRidByUserIdOnOption($UserId, $Option)
    {
        try {
            $Offset = $Option['offset'];
            $Limit = $Option['limit'];
            return Restaurant_history::select('rid')
                ->where('uid', '=', $UserId)
                ->limit($Limit)
                ->offset($Offset)
                ->get();
        } catch (Throwable $e) {
            throw new \Exception("RepossitoryErr:" . 500);
        }
    }
}
