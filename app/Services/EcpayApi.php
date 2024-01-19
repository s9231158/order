<?php

namespace App\Services;

use App\Services\CheckMacValueService;
use GuzzleHttp\Client;
use Throwable;

class EcpayApi
{
    public function sendEcpayApi($ecpayInfo)
    {
        try {
            $key = env('ECPAY_KEY');
            $iv = env('ECPAY_IV');
            $checkMacValueService = new CheckMacValueService($key, $iv);
            $ecpayRestaurantInfo = [
                "merchant_id" => 11,
                "payment_type" => "aio",
                "return_url" => env('ECPAY_RETURNURL'),
                "encrypt_type" => 1,
                "lang" => "en",
                "choose_payment" => "Credit",
            ];
            $ecpayInfo = array_merge($ecpayInfo, $ecpayRestaurantInfo);
            $ecpayInfo['check_mac_value'] = $checkMacValueService->generate($ecpayInfo);
            $client = new Client();
            $response = $client->Request('POST', env('ECPAY_APIURL'), ['json' => $ecpayInfo]);
            $arrayGoodResponse = json_decode($response->getBody());
            return [$arrayGoodResponse, $ecpayInfo];
        } catch (Throwable $e) {
            throw new \Exception("EcpayServiceErr:" . 500);
        }
    }
}
