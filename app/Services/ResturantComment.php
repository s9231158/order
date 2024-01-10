<?php

namespace App\Services;

use App\Models\RestaurantComment as RestaurantCommentModel;
use Exception;
use Throwable;

class ResturantComment
{
    public function create($info)
    {
        try {
            $goodInfo = [
                'uid' => $info['uid'],
                'rid' => $info['rid'],
                'comment' => $info['comment'],
                'point' => $info['point'],
            ];
            return RestaurantCommentModel::create($goodInfo);
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        } catch (Throwable $e) {
            throw new Exception("user_record_service_err:" . 500);
        }
    }

    public function getJoin($where, $option)
    {
        //select
        $stmt = null;
        if (isset($option['column'])) {
            $stmt = RestaurantCommentModel::select($option['column']);
        } else {
            $stmt = RestaurantCommentModel::select('*');
        }
        //where
        $chunks = array_chunk($where, 2);
        if (!empty($where)) {
            foreach ($chunks as $chunk) {
                $stmt->where($chunk[0], $chunk[1]);
            }
        }
        $response = $stmt->first();
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
            $stmt = RestaurantCommentModel::select($option['column']);
        } else {
            $stmt = RestaurantCommentModel::select('*');
        }
        //join
        $stmt->join('users', 'users.id', '=', 'restaurant_comments.uid');
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
        return $stmt->get()->toArray();
    }

}
