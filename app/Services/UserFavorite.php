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
            throw new Exception("user_favorite_service_err:" . 500);
        }
    }

    public function getList($where)
    {
        try {
            $stmt = UserFavoriteModel::select('*');
            if (count($where) % 3 != 0) {
                throw new Exception('where參數數量除三應餘為0,where參數正確示範[0]:uid,[1]:=[3]:2');
            }
            $chunks = array_chunk($where, 3);
            if (!empty($where)) {
                foreach ($chunks as $chunk) {
                    $stmt->where($chunk[0], $chunk[1], $chunk[2]);
                }
            }
            return $stmt->get()->toArray();
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
