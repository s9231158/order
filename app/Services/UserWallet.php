<?php

namespace App\Services;

use App\Models\User_wallets as UserWalletModel;
use Exception;
use Throwable;

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
            throw new Exception("user_wallet_service_err:" . 500 . $e->getMessage());
        }
    }

    public function get($userId)
    {
        try {
            return UserWalletModel::find($userId)->toArray();
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        } catch (Throwable $e) {
            throw new Exception("user_wallet_service_err:" . 500 . $e->getMessage());
        }
    }
}
