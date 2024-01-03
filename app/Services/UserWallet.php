<?php

namespace App\Services;

use App\Models\User_wallets as UserWalletModel;
use Throwable;

class UserWallet
{
    public function updateOrCreate($userId, $blance)
    {
        try {
            return UserWalletModel::updateOrCreate(
                ['id' => $userId, 'balance' => $blance],
                ['balance' => $blance, 'created_at' => now(), 'updated_at' => now()]
            );
        } catch (Throwable $e) {
            throw new \Exception("user_wallet_service_err:" . 500);
        }

    }
}
