<?php

namespace App;

use Illuminate\Support\Str;
use Illuminate\Support\Carbon;
use GuzzleHttp\Client;
use App\Models\Ecpay;
class EcpayService
{
    private $Key = '0dd22e31042fbbdd';
    private  $Iv = 'e62f6e3bbd7c2e9d';

    public function SendApi($Data)
    {
        try {
            $CheckMacValueService = new CheckMacValueService($this->Key, $this->Iv);
            $CheckMacValue = $CheckMacValueService->generate($Data);
            $Data['check_mac_value'] = $CheckMacValue;
            $Client  =  new  Client();
            $Response = $Client->request('POST', 'http://neil.xincity.xyz:9997/api/Cashier/AioCheckOut', ['json' => $Data]);
            $Response = $Response->getBody();
            $Response = json_decode($Response);
            return $Response;
        } catch (\Exception $e) {
            return 'err';
        }
    }
    public function GetCheckMacValue($Data)
    {
        $CheckMacValueService = new CheckMacValueService($this->Key, $this->Iv);
        $CheckMacValue = $CheckMacValueService->generate($Data);
        return  $CheckMacValue;
    }
    public function GetUuid()
    {
        $uid = (string)Str::uuid();
        $uuid20Char = substr($uid, 0, 20);
        return $uuid20Char;
    }
    public function GetDate()
    {
        return Carbon::now()->format('Y/m/d H:i:s');
    }

};
