<?php

namespace App\RepositoryV2;

use App\Models\User_wallets;
use Cache;

class UserWalletRepositoryV2
{
    public function GetOnId($UserId)
    {
        try {
            return User_wallets::where('id', '=', $UserId)->first();
        } catch (\Throwable $e) {
            Cache::set('GetUserWallet', 'GetUserWallet');
        }
    }
    public function UpdateOnId($UserId, $Money)
    {
        try {
            $apple = $this->GetOnId($UserId);
            $apple->update(['balance' => $Money]);
        } catch (\Throwable $e) {
            Cache::set('UpdateUserWalletBalance', $e->getMessage());
        }
    }

}