<?php

namespace App\Services;

use App\Models\User_wallets as UserWalletModel;
use Throwable;
use Exception;

class UserWallet
{
    public function updateOrCreate($userId, $balance)
    {
        try {
            return UserWalletModel::updateOrCreate(
                ['id' => $userId],
                ['balance' => $balance, 'created_at' => now(), 'updated_at' => now()]
            );
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        } catch (Throwable $e) {
            throw new Exception("user_record_service_err:" . 500 . $e);
        }
    }
    
    public function get($where, $option)
    {
        //select
        $stmt = null;
        if (isset($option['column'])) {
            $stmt = UserWalletModel::select($option['column']);
        } else {
            $stmt = UserWalletModel::select('*');
        }
        //where
        if (!empty($where)) {
            $response = $stmt->find($where);
        }
        if (!$response) {
            return $response;
        }
        return $response->toArray();
    }
}
