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
                'name' => $info['name'],
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

    public function get($rid, $uid)
    {
        return RestaurantCommentModel::where('rid', $rid)->where('uid', $uid)->get()->toArray();
    }

    public function getList($rid, $option = null)
    {
        $limit = $option['limit'] ?? 20;
        $offset = $option['offset'] ?? 0;
        //where
        $stmt = RestaurantCommentModel::where('rid', '=', $rid);
        //orderBy
        $stmt->orderby('restaurant_comments.created_at', 'desc');
        //range
        $stmt->limit($limit);
        $stmt->offset($offset);
        return $stmt->get()->toArray();
    }
}
