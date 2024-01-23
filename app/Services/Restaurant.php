<?php

namespace App\Services;

use App\Models\Restaurant as RestaurantModel;
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
            throw new Exception("restaurant_service_err:" . 500 . $e->getMessage());
        } catch (Throwable $e) {
            throw new Exception("restaurant_service_err:" . 500 . $e->getMessage());
        }
    }

    public function get($rid)
    {
        try {
            return RestaurantModel::find($rid)->first()->toArray();
        } catch (Exception $e) {
            throw new Exception("restaurant_service_err:" . 500 . $e->getMessage());
        } catch (Throwable $e) {
            throw new Exception("restaurant_service_err:" . 500 . $e->getMessage());
        }
    }

    public function getJoinList($where = null, $option = null)
    {
        try {
            $offset = $option['offset'] ?? 0;
            $limit = $option['limit'] ?? 20;
            //select
            $stmt = RestaurantModel::select('*');
            //join
            $stmt->join('restaurant_open_days', 'restaurant_open_days.id', '=', 'restaurants.id');
            //where
            if (count($where) % 3 != 0) {
                throw new Exception('where參數數量除三應餘為0,where參數正確示範[0]:uid,[1]:=[3]:2');
            }
            $whereChunks = array_chunk($where, 3);
            if (!empty($where)) {
                foreach ($whereChunks as $whereChunk) {
                    $stmt->where($whereChunk[0], $whereChunk[1], $whereChunk[2]);
                }
            }
            //range
            $stmt->limit($limit);
            $stmt->offset($offset);
            return $stmt->get()->toArray();
        } catch (Exception $e) {
            throw new Exception("restaurant_service_err:" . 500 . $e->getMessage());
        } catch (Throwable $e) {
            throw new Exception("restaurant_service_err:" . 500 . $e->getMessage());
        }
    }
}
