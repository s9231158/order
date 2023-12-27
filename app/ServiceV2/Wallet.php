<?php

namespace App\ServiceV2;

use App\RepositoryV2\UserWallet;
use App\RepositoryV2\WalletRecord;
use App\ServiceV2\Ecpay;
use Throwable;


class Wallet
{
    private $Ecpay;
    private $UserRepositoryV2;
    private $WalletRecordRepositoryV2;
    private $UserWalletRepositoryV2;
    public function __construct(
        UserWallet $UserWalletRepositoryV2,
        Ecpay $Ecpay,
        User $UserRepositoryV2,
        WalletRecord $WalletRecordRepositoryV2,
    ) {
        $this->UserWalletRepositoryV2 = $UserWalletRepositoryV2;
        $this->UserRepositoryV2 = $UserRepositoryV2;
        $this->WalletRecordRepositoryV2 = $WalletRecordRepositoryV2;
        $this->Ecpay = $Ecpay;
    }
    public function SendEcpayApi($EcpayInfo)
    {
        return $this->Ecpay->SendEcpayApi($EcpayInfo);
    }
    public function SaveEcpay($EcpayInfo)
    {
        $this->Ecpay->SaveEcpay($EcpayInfo);
    }
    public function SaveWalletRecord($WalletRecord)
    {
        $this->Ecpay->SaveWalletRecord($WalletRecord);
    }
    public function SaveEcpayBack($EcpayBackInfo)
    {
        $this->Ecpay->SaveEcpayBack($EcpayBackInfo);
    }
    public function UpdateWalletRecordFail($Uuid)
    {
        return $this->Ecpay->UpdateWalletRecordFail($Uuid);
    }
    public function AddMoney($Money, $Option)
    {
        try {
            $Eid = $Option['Eid'];
            $UserId = $this->WalletRecordRepositoryV2->GetUserIdByEidAndTime($Eid, $Option);
            $UserWallet = $this->UserWalletRepositoryV2->GetOnId($UserId[0]['uid']);
            $Balance = $UserWallet->balance += $Money;
            $this->UserWalletRepositoryV2->UpdateOnId($UserId[0]['uid'], $Balance);
        } catch (Throwable $e) {
            throw new \Exception("ServiceErr:" . 500);
        }
    }
    public function UpdateWalletRecordSuccess($Uuid)
    {
        $this->Ecpay->UpdateWalletRecordSuccess($Uuid);
    }
    public function GetWalletRecordOnRangeAndType($Option, $Type)
    {
        try {
            $UserInfo = $this->UserRepositoryV2->GetUserInfo();
            $UserId = $UserInfo['id'];
            if ($Type === 'in') {
                $WalletRecord = $this->WalletRecordRepositoryV2->GetByUidAndTypeOnRange($Option, $Type, $UserId);
                $Count = $WalletRecord->count();
                return array('count' => $Count, 'data' => $WalletRecord);
            }
            if ($Type === 'out') {
                $WalletRecord = $this->WalletRecordRepositoryV2->GetByUidAndTypeOnRange($Option, $Type, $UserId);
                $Count = $WalletRecord->count();
                return array('count' => $Count, 'data' => $WalletRecord);
            } else {
                $WalletRecord = [];
                $Count = 0;
                $WalletRecord['in'] = $this->WalletRecordRepositoryV2->GetByUidAndTypeOnRange($Option, 'in', $UserId);
                $WalletRecord['out'] = $this->WalletRecordRepositoryV2->GetByUidAndTypeOnRange($Option, 'out', $UserId);
                $Count += $WalletRecord['in']->count();
                $Count += $WalletRecord['out']->count();
                return array('count' => $Count, 'data' => $WalletRecord);
            }
        } catch (Throwable $e) {
            throw new \Exception("ServiceErr:" . 500 . $e);
        }
    }

}