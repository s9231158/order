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
        try {
            return RestaurantModel::
                wherein('id', $rid)
                ->orderBy('created_at', 'desc')
                ->get()
                ->toArray();
        } catch (Throwable $e) {
            throw new \Exception("restaurant_service_err:" . 500);
        }

    }
    public function getListByRange($option)
    {
        try {
            return RestaurantModel::join('restaurant_open_days', 'restaurant_open_days.id', '=', 'restaurants.id')
                ->where('restaurant_open_days.' . date('l'), '=', '1')
                ->limit($option['limit'])
                ->offset($option['offset'])
                ->get()
                ->toArray();
        } catch (Throwable $e) {
            throw new \Exception("restaurant_service_err:" . 500);
        }
    }
}