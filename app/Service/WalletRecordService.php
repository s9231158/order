<?php
namespace App\Service;

use App\Models\Wallet_Record;

class WalletRecordService
{
    public function GetUserId($Eid)
    {
        return Wallet_Record::select('uid')->Where('eid', '=', $Eid)->get();
    }
}



?>