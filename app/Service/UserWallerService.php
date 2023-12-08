<?php
namespace App\Service;

use App\Models\User_wallets;

class UserWallerService
{
    public function GetWallet($UserId)
    {
        return User_wallets::find($UserId);
    }
    public function AddWalletMoney($Money, $UserId)
    {
        $UserWallet = $this->GetWallet($UserId);
        $UserWallet->balance += $Money;
        $UserWallet->save();
    }
}


?>