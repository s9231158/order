<?php

namespace App\ServiceV2;

use App\Factorise;
use App\RepositoryV2\EcpayBack as EcpayBackRepositoryV2;
use App\RepositoryV2\Ecpay as EcpayRepositoryV2;
use App\RepositoryV2\WalletRecord as WalletRecordRepositoryV2;
use App\CheckMacValueService;
use GuzzleHttp\Client;
use Throwable;

class Ecpay
{
    private $WalletRecordRepositoryV2;
    private $EcpayRepositoryV2;
    private $EcpayBackRepositoryV2;
    public function __construct(
        EcpayBackRepositoryV2 $EcpayBackRepositoryV2,
        EcpayRepositoryV2 $EcpayRepositoryV2,
        WalletRecordRepositoryV2 $WalletRecordRepositoryV2,
    ) {
        $this->WalletRecordRepositoryV2 = $WalletRecordRepositoryV2;
        $this->EcpayRepositoryV2 = $EcpayRepositoryV2;
        $this->EcpayBackRepositoryV2 = $EcpayBackRepositoryV2;
    }
    public function SendEcpayApi($EcpayInfo)
    {
        try {
            $Key = env('Ecpay_Key');
            $Iv = env('Ecpay_Iv');
            $CheckMacValueService = new CheckMacValueService($Key, $Iv);
            $EcpayRestaurantInfo = [
                "merchant_id" => 11,
                "payment_type" => "aio",
                "return_url" => env('Ecpay_ReturnUrl'),
                "encrypt_type" => 1,
                "lang" => "en",
                "choose_payment" => "Credit",
            ];
            $EcpayInfo = array_merge($EcpayInfo, $EcpayRestaurantInfo);
            $EcpayInfo['check_mac_value'] = $CheckMacValueService->Generate($EcpayInfo);
            $Client = new Client();
            $Response = $Client->Request('POST', env('Ecpay_ApiUrl'), ['json' => $EcpayInfo]);
            $ArrayGoodResponse = json_decode($Response->getBody());
            return [$ArrayGoodResponse, $EcpayInfo];
        } catch (Throwable $e) {
            throw new \Exception("EcpayServiceErr:" . 500);
        }
    }
    public function SaveEcpay($EcpayInfo)
    {
        try {
            $this->EcpayRepositoryV2->Create($EcpayInfo);
        } catch (Throwable $e) {
            throw new \Exception("EcpayServiceErr:" . 500);
        }
    }
    public function SaveWalletRecord($WalletRecord)
    {
        try {
            $this->WalletRecordRepositoryV2->Create($WalletRecord);
        } catch (Throwable $e) {
            throw new \Exception("EcpayServiceErr:" . 500 . $e);
        }
    }
    public function SaveEcpayBack($EcpayBackInfo)
    {
        try {
            $this->EcpayBackRepositoryV2->Create($EcpayBackInfo);
        } catch (Throwable $e) {
            throw new \Exception("EcpayServiceErr:" . 500);
        }
    }
    public function UpdateWalletRecordFail($Uuid)
    {
        try {
            return $this->WalletRecordRepositoryV2->UpdateFailByUid($Uuid);
        } catch (Throwable $e) {
            throw new \Exception("EcpayServiceErr:" . 500);
        }
    }
    public function UpdateWalletRecordSuccess($Uuid)
    {
        try {
            return $this->WalletRecordRepositoryV2->UpdateSuccessById($Uuid);
        } catch (Throwable $e) {
            throw new \Exception("EcpayServiceErr:" . 500);
        }
    }
}
