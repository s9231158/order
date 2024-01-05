<?php

namespace App\Services;

use App\Models\RestaurantComment as RestaurantCommentModel;
use Exception;
use Illuminate\Support\Facades\Cache;
use Throwable;

class ResturantComment
{
    public function create($commentIfo)
    {
        try {
            $needColumn = ['uid', 'rid', 'comment', 'point'];
            foreach ($needColumn as $colunm) {
                if (!isset($commentIfo[$colunm]) || empty($commentIfo[$colunm])) {
                    throw new Exception('資料缺失');
                }
            }
            $goodInfo = [
                'uid' => $commentIfo['uid'],
                'rid' => $commentIfo['rid'],
                'comment' => $commentIfo['comment'],
                'point' => $commentIfo['point'],
            ];
            return RestaurantCommentModel::create($goodInfo);
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        } catch (Throwable $e) {
            throw new Exception("user_record_service_err:" . 500);
        }
    }

    public function get($where, $option)
    {
        //check Redis
        $redisKey = $this->generateCacheKey($where, $option);
        if (Cache::get($redisKey)) {
            return Cache::get($redisKey);
        }
        //select
        $stmt = null;
        if (isset($option['column'])) {
            $stmt = RestaurantCommentModel::select($option['column']);
        } else {
            $stmt = RestaurantCommentModel::select('*');
        }
        //join
        if (isset($option['join'])) {
            $joinChunks = array_chunk($option['join'], 4);
            foreach ($joinChunks as $joinChunk) {
                $stmt->join($joinChunk[0], $joinChunk[1], $joinChunk[2], $joinChunk[3]);
            }
        }
        //where
        $chunks = array_chunk($where, 3);
        if (!empty($where)) {
            foreach ($chunks as $chunk) {
                $stmt->where($chunk[0], $chunk[1], $chunk[2]);
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
            Cache::put($redisKey, $response);
            return $response;
        } else {
            $response = $stmt->first();
            if (!$response) {
                return [];
            } else {
                $response->toArray();
                Cache::put($redisKey, $response);
                return $response;
            }
        }
    }
    private function generateCacheKey($where, $option)
    {
        return md5(serialize([$where, $option]));
    }
}
