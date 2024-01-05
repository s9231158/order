<?php

namespace App\Services;

use App\Models\User_favorite;
use Throwable;
use Exception;

class UserFavorite
{
    public function create($favoriteInfo)
    {
        try {
            if (!isset($favoriteInfo['uid']) || !isset($favoriteInfo['rid'])) {
                throw new Exception('資料缺失');
            }
            $goodInfo = [
                'uid' => $favoriteInfo['uid'],
                'rid' => $favoriteInfo['rid'],
            ];
            return User_favorite::create($goodInfo);
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        } catch (Throwable $e) {
            throw new Exception("user_favorite_service_err:" . 500 . $e);
        }
    }
    public function get($userId)
    {
        try {
            if (!isset($userId)) {
                throw new Exception('資料缺失');
            }
            return User_favorite::where('uid', '=', $userId)->get()->toArray();
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        } catch (Throwable $e) {
            throw new Exception("user_favorite_service_err:" . 500);
        }
    }
    public function delete($userId, $rid)
    {
        try {
            if (!isset($userId) || !isset($rid)) {
                throw new Exception('資料缺失');
            }
            return User_favorite::where('uid', '=', $userId)->where('rid', '=', $rid)->delete();
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        } catch (Throwable $e) {
            throw new Exception("user_favorite_service_err:" . 500);
        }
    }
}