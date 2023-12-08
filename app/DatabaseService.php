<?php

namespace App;

use App\Models\Ecpay;
use App\Models\Wallet_Record;

class DatabaseService
{
    public function SaveEcpay($Data) //將資料存入Ecpay
    {
        $Ecpay = new Ecpay($Data);
        $Ecpay->save();
    }
    public function SaveRecord($Data) //將資料存入WalletRecord
    {
        $WalletRecord = new Wallet_Record($Data);
        $WalletRecord->save();
    }
    public function GetEcpayCollection($Uuid) //取得某Ecpay的Collrction
    {
        return  Ecpay::find($Uuid);
    }



    /**
     * 將EcpayBack儲存在某Ecpay關聯資料表
     *
     * @param [type] $Ecpay
     * @param [type] $Ecpayback
     * @return void
     */
    public function SaveEcpayCallBack($Ecpay, $Ecpayback)
    {
        $Ecpay->ecpayback()->save($Ecpayback);
    }

    public function SaveEcpayRecord($Ecpay, $EcpayRecord) //將WalletRecord儲存在某Ecpay關聯資料表
    {
        $Ecpay->Record()->saveMany($EcpayRecord);
    }

    public function GetRecordCollenction($Ecpay) //取得某Ecpay關聯的WalletRecord
    {
        return $Ecpay->record()->get();
    }
}
