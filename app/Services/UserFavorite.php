<?php

namespace App\Services;

use App\Models\User_favorite as UserFavoriteModel;
use Throwable;
use Exception;

class UserFavorite
{
    public function create($userId, $rid)
    {
        try {
            $goodInfo = [
                'uid' => $userId,
                'rid' => $rid,
            ];
            return UserFavoriteModel::create($goodInfo);
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
