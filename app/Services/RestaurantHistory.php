<?php

namespace App\Services;

use App\Models\Restaurant_history;
use Throwable;
use Exception;

class RestaurantHistory
{
    public function getListByUser($userId)
    {
        try {
            if (!isset($userId)) {
                throw new Exception('資料缺失');
            }
            return Restaurant_history::where('uid', '=', $userId)->get()->toArray();
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        } catch (Throwable $e) {
            throw new Exception("restaurant_history_service_err:" . 500);
        }
    }
    public function updateOrCreate($userId, $rid)
    {
        try {
            if (!isset($userId) || !isset($rid)) {
                throw new Exception('資料缺失');
            }
            return Restaurant_history::updateOrCreate(
                ['uid' => $userId, 'rid' => $rid],
                ['created_at' => now(), 'updated_at' => now()]
            );
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        } catch (Throwable $e) {
            throw new Exception("restaurant_history_service_err:" . 500);
        }

    }
}