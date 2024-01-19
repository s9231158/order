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

    public function firstComment($userId, $rid)
    {
        return RestaurantCommentModel::where('uid', $userId)->where('rid', $rid)->exists();
    }

    public function getJoinUserList($rid, $option)
    {
        $limit = $option['limit'] ?? 20;
        $offset = $option['offset'] ?? 0;
        //select
        $stmt = RestaurantCommentModel::select(
            'users.name',
            'restaurant_comments.point',
            'restaurant_comments.comment',
            'restaurant_comments.created_at'
        );
        //join
        $stmt->join('users', 'users.id', '=', 'restaurant_comments.uid');
        //where
        $stmt->where('rid', '=', $rid);
        //orderBy
        $stmt->orderby('restaurant_comments.created_at', 'desc');
        //range
        $stmt->limit($limit);
        $stmt->offset($offset);
        return $stmt->get()->toArray();
    }
}
