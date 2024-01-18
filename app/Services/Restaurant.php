<?php

namespace App\Services;

use App\Models\Restaurant as RestaurantModel;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\Redis;
use PDOException;
use Throwable;
use Exception;

class Restaurant
{
    public function getList($rids)
    {
        try {
            return RestaurantModel::wherein('id', $rids)
                ->orderBy('created_at', 'desc')
                ->get()
                ->toArray();
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        } catch (Throwable $e) {
            throw new Exception("restaurant_service_err:" . 500);
        }
    }

    public function get($rid)
    {
        try {
            return RestaurantModel::findorfail($rid)->toArray();
        } catch (ModelNotFoundException $e) {
            return [];
        } catch (PDOException $e) {
            return [];
        }
    }

    public function getJoinist($where, $option)
    {
        $offset = $option['offset'] ?? 0;
        $limit = $option['limit'] ?? 20;
        $column = $option['column'] ?? '*';
        //select
        $stmt = RestaurantModel::select($column);
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
        //range
        $stmt->limit($limit);
        $stmt->offset($offset);
        return $stmt->get()->toArray();
    }
}
