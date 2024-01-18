<?php

namespace App\Services;

use App\Models\User_favorite as UserFavoriteModel;
use Illuminate\Support\Facades\Cache;
use Throwable;
use Exception;

class UserFavorite
{
    public function create($info)
    {
        try {
            $goodInfo = [
                'uid' => $info['uid'],
                'rid' => $info['rid'],
            ];
            $response = UserFavoriteModel::create($goodInfo);
            $hour = intval(date('H', strtotime(now())));
            $restaurantFavorite = Cache::get('restaurant_favorite');
            if (isset($restaurantFavorite[$hour][$info['rid']])) {
                $restaurantFavorite[$hour][$info['rid']] += 1;
            } else {
                $restaurantFavorite[$hour][$info['rid']] = 1;
            }
            Cache::put('restaurant_favorite', $restaurantFavorite);
            return $response;
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        } catch (Throwable $e) {
            throw new Exception("user_favorite_service_err:" . 500 . $e);
        }
    }

    public function getList($userId)
    {
        try {
            return UserFavoriteModel::where('uid', '=', $userId)->get()->toArray();
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        } catch (Throwable $e) {
            throw new Exception("user_favorite_service_err:" . 500);
        }
    }

    public function delete($userId, $rid)
    {
        try {
            return UserFavoriteModel::where('uid', '=', $userId)->where('rid', '=', $rid)->delete();
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        } catch (Throwable $e) {
            throw new Exception("user_favorite_service_err:" . 500);
        }
    }
}
