<?php

namespace App\Services;

use App\Models\Restaurant as RestaurantModel;
use Illuminate\Support\Facades\Redis;
use Throwable;
use Exception;

class Restaurant
{
    public function getListByRids($rids)
    {
        try {
            /* redis內已有的所有keys */
            $redisKeys = redis::hkeys('restaurantInfo');
            /* 扣除redis內已有的keys 還需要的keys */
            $needKeys = array_values(array_diff($rids, $redisKeys));
            /* 需要尋找的keys 但redis內已有的keys */
            $redisKeys = array_values(array_intersect($redisKeys, $rids));
            /* 如果還需要到database找資料 */
            $need = false;
            $response = [];
            if (empty($needKeys)) {
                foreach (Redis::hmget('restaurantInfo', $rids) as $item) {
                    $response[] = json_decode($item, true);
                }
                return $response;
            }
            if (!empty($needKeys)) {
                $need = true;
            }
            if (!empty($redisKeys)) {
                foreach (Redis::hmget('restaurantInfo', $redisKeys) as $item) {
                    $response[] = json_decode($item, true);
                }
            }
            if ($need) {
                $dataResponse = RestaurantModel::
                    wherein('id', $needKeys)
                    ->orderBy('created_at', 'desc')
                    ->get()
                    ->toArray();
                foreach ($dataResponse as $item) {
                    Redis::hset('restaurantInfo', $item['id'], json_encode($item));
                    $response[] = $item;
                }
            }
            return $response;
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        } catch (Throwable $e) {
            throw new Exception("restaurant_service_err:" . 500);
        }
    }

    public function get($where, $option)
    {
        //redis內是否有資料
        if (Redis::HEXISTS('restaurantInfo', $where)) {
            return json_decode(Redis::hget('restaurantInfo', $where), true);
        }
        //select
        $stmt = null;
        if (isset($option['column'])) {
            $stmt = RestaurantModel::select($option['column']);
        } else {
            $stmt = RestaurantModel::select('*');
        }
        //where
        if (!empty($where)) {
            $response = $stmt->find($where);
        }
        if (!$response) {
            return $response;
        }
        //將從database抓出的資料存入redis
        Redis::hset('restaurantInfo', $where, json_encode($response));
        return $response->toArray();
    }

    public function getJoinist($where, $option)
    {
        //帶offset limit的
        /* 需要尋找的keys */$keys = range($option['offset'] + 1, $option['limit'] + $option['offset']);
        /* redis內已有的所有keys */$redisKeys = redis::hkeys('restaurantjoinInfo');
        /* 扣除redis內已有的keys 還需要的keys */$needKeys = array_values(array_diff($keys, $redisKeys));
        /* 需要尋找的keys 但redis內已有的keys */$redisKeys = array_values(array_intersect($redisKeys, $keys));
        /* 如果還需要到database找資料 */$need = false;
        $response = [];
        if (empty($needKeys)) {
            foreach (Redis::hmget('restaurantjoinInfo', $keys) as $item) {
                $response[] = json_decode($item, true);
            }
            return $response;
        }
        if (!empty($needKeys)) {
            $need = true;
        }
        if (!empty($redisKeys)) {
            foreach (Redis::hmget('restaurantjoinInfo', $redisKeys) as $item) {

                $response[] = json_decode($item, true);
            }
        }
        //select
        $stmt = null;
        if (isset($option['column'])) {
            $stmt = RestaurantModel::select($option['column']);
        } else {
            $stmt = RestaurantModel::select('*');
        }
        //join
        $stmt->join('restaurant_open_days', 'restaurant_open_days.id', '=', 'restaurants.id');
        if ($need) {
            $stmt->whereIn('restaurants.id', $needKeys);
        } else {
            //where
            $whereChunks = array_chunk($where, 3);
            if (!empty($where)) {
                foreach ($whereChunks as $whereChunk) {
                    $stmt->where($whereChunk[0], $whereChunk[1], $whereChunk[2]);
                }
            }
        }
        //orderBy
        if (isset($option['orderby'])) {
            $stmt->orderby($option['orderby'][0], $option['orderby'][1]);
        }
        //limit
        if ($need) {
            if (isset($option['limit'])) {
                $stmt->limit($option['limit']);
            }
            if (isset($option['offset'])) {
                $stmt->offset($option['offset']);
            }
        }
        $dataResponse = $stmt->get();
        foreach ($dataResponse as $item) {
            $response[] = $item;
            Redis::hset('restaurantjoinInfo', $item['id'], $item);
        }
        return $response;
    }
}
