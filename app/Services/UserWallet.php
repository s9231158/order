<?php

namespace App\Services;

use App\Models\User_wallets as UserWallerModel;
use Throwable;

class UserWallet
{
    public function updateOrCreate($userId, $blance)
    {
        try {
            return UserWallerModel::updateOrCreate(
                ['id' => $userId, 'balance' => $blance],
                ['balance' => $blance, 'created_at' => now(), 'updated_at' => now()]
            );
        } catch (Throwable $e) {
            throw new \Exception("user_service_err:" . 500);
        }

    }
}