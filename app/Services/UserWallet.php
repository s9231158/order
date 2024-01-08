<?php

namespace App\Services;

use App\Models\User_wallets as UserWalletModel;
use Throwable;
use Exception;

class UserWallet
{
    public function updateOrCreate($userId, $blance)
    {
        try {
            return UserWalletModel::updateOrCreate(
                ['id' => $userId, 'balance' => $blance],
                ['balance' => $blance, 'created_at' => now(), 'updated_at' => now()]
            );
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        } catch (Throwable $e) {
            throw new Exception("user_record_service_err:" . 500 . $e);
        }

    }
}
