<?php

namespace App\Services;

use App\Models\Restaurant as RestaurantModel;
use Illuminate\Support\Facades\Cache;
use Throwable;
use Exception;

class Restaurant
{
    public function getListByRid($rids)
    {
        try {
            if (!isset($rids)) {
                throw new Exception('資料缺失');
            }
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
        // //check Redis
        $redisKey = $this->cacheKey($where, $option);
        if (Cache::get($redisKey)) {
            return Cache::get($redisKey);
        }
        //select
        $stmt = null;
        if (isset($option['column'])) {
            $stmt = RestaurantModel::select($option['column']);
        } else {
            $stmt = RestaurantModel::select('*');
        }
        //join
        if (isset($option['join'])) {
            $joinChunks = array_chunk($option['join'], 4);
            foreach ($joinChunks as $joinChunk) {
                $stmt->join($joinChunk[0], $joinChunk[1], $joinChunk[2], $joinChunk[3]);
            }
        }
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
        //get
        if (isset($option['get'])) {
            $response = $stmt->get()->toArray();
            Cache::set($redisKey, $response);
            return $response;
        } else {
            $response = $stmt->first();
            if (!$response) {
                return [];
            } else {
                $response->toArray();
                Cache::set($redisKey, $response);
                return $response;
            }
        }
    }
    private function cacheKey($where, $option)
    {
        $where = $this->sort($where);
        $option = $this->sort($option);
        return md5(serialize([$where, $option]));
    }
    public static function sort($soure)
    {
        ksort($soure);
        foreach ($soure as &$value) {
            if (is_array($value)) {
                usort($value, function ($a, $b) {
                    $comparison = strcasecmp(preg_replace('/[=<>]/', '', $a), preg_replace('/[=<>]/', '', $b));
                    if (strpos($a, '=') !== false) {
                        return ($comparison === 0) ? 1 : $comparison;
                    }
                    return $comparison;
                });
            }
        }
        return $soure;
    }
}