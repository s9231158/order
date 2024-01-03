<?php

namespace App\Services;

use App\Models\Restaurant as RestaurantModel;
use Throwable;

class Restaurant
{
    public function getObjByRid($rid)
    {
        try {
            return RestaurantModel::find($rid);
        } catch (Throwable $e) {
            throw new \Exception("restaurant_service_err:" . 500);
        }
    }
    public function getListByRid($rid)
    {
        return RestaurantModel::
            wherein('id', $rid)
            ->orderBy('created_at', 'desc')
            ->get()
            ->toArray();
    }
}