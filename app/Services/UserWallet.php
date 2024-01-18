<?php

namespace App\Services;

use App\Models\User_wallets as UserWalletModel;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use PDOException;
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

    public function get($userId)
    {
        try {
            return UserWalletModel::findorfail($userId)->toArray();
        } catch (ModelNotFoundException $e) {
            return [];
        } catch (PDOException $e) {
            return [];
        }
    }
}
