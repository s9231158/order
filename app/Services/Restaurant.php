<?php

namespace App\Services;

use App\Models\Restaurant as RestaurantModel;
use Illuminate\Support\Facades\Cache;
use Throwable;
use Exception;

class Restaurant
{
    public function getListByRids($rids)
    {
        try {
            return RestaurantModel::
                wherein('id', $rids)
                ->orderBy('created_at', 'desc')
                ->get()
                ->toArray();
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        } catch (Throwable $e) {
            throw new Exception("restaurant_service_err:" . 500);
        }
    }

    public function get($where, $option)
    {
        //select
        $stmt = null;
        if (isset($option['column'])) {
            $stmt = RestaurantModel::select($option['column']);
        } else {
            $stmt = RestaurantModel::select('*');
        }
        //where
        if (!empty($where)) {
            $response = $stmt->find($where)->first();
        }
        if (!$response) {
            return $response;
        }
        return $response->toArray();
    }

    public function getList($where, $option)
    {
        //select
        $stmt = null;
        if (isset($option['column'])) {
            $stmt = RestaurantModel::select($option['column']);
        } else {
            $stmt = RestaurantModel::select('*');
        }
        //join
        $stmt->join('restaurant_open_days', 'restaurant_open_days.id', '=', 'restaurants.id');
        //where
        $whereChunks = array_chunk($where, 3);
        if (!empty($where)) {
            foreach ($whereChunks as $whereChunk) {
                $stmt->where($whereChunk[0], $whereChunk[1], $whereChunk[2]);
            }
        }
        //orderBy
        if (isset($option['orderby'])) {
            $stmt->orderby($option['orderby'][0], $option['orderby'][1]);
        }
        //limit
        if (isset($option['limit'])) {
            $stmt->limit($option['limit']);
        }
        if (isset($option['offset'])) {
            $stmt->offset($option['offset']);
        }
        return $stmt->get()->toArray();
    }
}
