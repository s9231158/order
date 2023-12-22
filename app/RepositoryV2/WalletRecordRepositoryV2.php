<?php

namespace App\RepositoryV2;

use App\Models\Wallet_Record;
use Illuminate\Support\Facades\Cache;
use Throwable;

class WalletRecordRepositoryV2
{
    public function Create($WalletRecord)
    {
        try {
            Wallet_Record::create($WalletRecord);
        } catch (Throwable $e) {
            throw new \Exception("RepossitoryErr:" . 500);
        }
    }
    public function UpdateFailByUid($Uuid)
    {
        try {
            return Wallet_Record::where('eid', '=', $Uuid)->update(['status' => 10]);
        } catch (Throwable $e) {
            throw new \Exception("RepossitoryErr:" . 500);
        }
    }
    public function UpdateSuccessById($Uuid)
    {
        try {
            return Wallet_Record::where('eid', '=', $Uuid)->update(['status' => 0]);
        } catch (Throwable $e) {
            throw new \Exception("RepossitoryErr:" . 500);
        }
    }
    public function GetById($Uuid)
    {
        try {
            return Wallet_Record::where('eid', '=', $Uuid)->get();
        } catch (Throwable $e) {
            throw new \Exception("RepossitoryErr:" . 500);
        }
    }
    public function GetUserIdByEidAndTime($Eid, $Option)
    {
        try {
            return Wallet_Record::select('uid')
                ->where('created_at', '>', $Option['StartTime'])
                ->where('created_at', '<', $Option['EndTime'])
                ->where('eid', '=', $Eid)
                ->get();
        } catch (Throwable $e) {
            throw new \Exception("RepossitoryErr:" . 500);
        }
    }
    public function GetByUidAndTypeOnRange($Option, $Type, $UserId)
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
