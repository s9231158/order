<?php

namespace App\Services;

use App\Models\Restaurant_history;
use Throwable;

class RestaurantHistory
{
    public function getListByUser($userId)
    {
        try {
            return Restaurant_history::where('uid', '=', $userId)->get()->toArray();
        } catch (Throwable $e) {
            throw new \Exception("restaurant_history_service_err:" . 500);
        }
    }
    public function updateOrCreate($userId, $rid)
    {
        try {
            return Restaurant_history::updateOrCreate(
                ['uid' => $userId, 'rid' => $rid],
                ['created_at' => now(), 'updated_at' => now()]
            );
        } catch (Throwable $e) {
            throw new \Exception("restaurant_history_service_err:" . 500);
        }

    }
}