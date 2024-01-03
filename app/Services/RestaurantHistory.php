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
}