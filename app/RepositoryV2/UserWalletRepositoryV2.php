<?php

namespace App\RepositoryV2;

use App\Models\User_wallets;
use Cache;
use Throwable;

class UserWalletRepositoryV2
{
    public function GetOnId($UserId)
    {
        try {
            return User_wallets::where('id', '=', $UserId)->first();
        } catch (Throwable $e) {
            throw new \Exception("RepossitoryErr:" . 500);
        }
    }
    public function UpdateOnId($UserId, $Money)
    {
        try {
            $UserWallet = $this->GetOnId($UserId);
            $UserWallet->update(['balance' => $Money]);
        } catch (Throwable $e) {
            throw new \Exception("RepossitoryErr:" . 500);
        }
    }

    public function Create($UserId)
    {
        try {
            User_wallets::create(['id' => $UserId, 'balance' => 0]);
        } catch (Throwable $e) {
            throw new \Exception("RepossitoryErr:" . 500);
        }
    }
}