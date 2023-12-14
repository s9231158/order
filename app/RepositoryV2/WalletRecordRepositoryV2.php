<?php

namespace App\RepositoryV2;

use App\Models\Wallet_Record;

class WalletRecordRepositoryV2
{
    public function SaveWalletRecord($WalletRecord)
    {
        Wallet_Record::create($WalletRecord);
    }
    public function FindAndUpdateFailRecord($Uuid)
    {
        return Wallet_Record::where('eid', '=', $Uuid)->update(['status' => 0]);
    }
    public function FindAndUpdatesuccessRecord($Uuid)
    {
        return Wallet_Record::where('eid', '=', $Uuid)->update(['status' => 1]);
    }
    public function GetWalletRecord($Uuid)
    {
        return Wallet_Record::where('eid', '=', $Uuid)->get();
    }
}