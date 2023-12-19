<?php

namespace App\RepositoryV2;

use App\Models\Wallet_Record;
use Illuminate\Support\Facades\Cache;
use Throwable;

class WalletRecordRepositoryV2
{
    public function SaveWalletRecord($WalletRecord)
    {
        Wallet_Record::create($WalletRecord);
    }
    public function FindAndUpdateFailRecord($Uuid)
    {
        return Wallet_Record::where('eid', '=', $Uuid)->update(['status' => 10]);
    }
    public function FindAndUpdatesuccessRecord($Uuid)
    {
        return Wallet_Record::where('eid', '=', $Uuid)->update(['status' => 0]);
    }
    public function GetWalletRecord($Uuid)
    {
        return Wallet_Record::where('eid', '=', $Uuid)->get();
    }
    public function GetUserIdFormWallerRecordOnEid($Option)
    {
        try {
            return Wallet_Record::select('uid')
                ->where('created_at', '>', $Option['StartTime'])
                ->where('created_at', '<', $Option['EndTime'])
                ->where('eid', '=', $Option['Eid'])
                ->get();
        } catch (Throwable $e) {
            Cache::set('GetUserIdFormWallerRecordOnEid', $e->getMessage());
        }

    }
    public function GetWalletRecordOnRangeAndType($Option, $Type, $UserId)
    {
        try {
            return Wallet_Record::select('type', $Type, 'wallet__records.created_at')
                ->join('payments', 'wallet__records.pid', '=', 'payments.id')
                ->where("uid", '=', $UserId)
                ->where($Type, '!=', 'NULL')
                ->offset($Option['offset'])
                ->limit($Option['limit'])
                ->orderBy('wallet__records.created_at', 'desc')
                ->get();
        } catch (Throwable $e) {
            throw new \Exception("RepossitoryErr:" . 500);
        }
    }
}