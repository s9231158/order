<?php

namespace App;

use GuzzleHttp\Client;
use Illuminate\Support\Facades\Date;
use App\Contract\RestaurantInterface;
use App\Models\Restaurant;
use App\Models\Steakhome_menu;

class SHmenu implements RestaurantInterface
{
    public function Getmenu($Offset, $Limit)
    //修改為從api取得
    {
        $url = 'http://neil.xincity.xyz:9998/steak_home/api/menu/ls' . '?LT=' . $Limit . '&PG=' . $Offset;
        try {
            $client = new Client();
            $res = $client->request('GET', $url);
            $goodres = $res->getBody();
            $s = json_decode($goodres, true);
            $ss = $s['LS'];
            $targetData = [];
            foreach ($ss as $a) {
                $menu = [
                    'rid' => 3,
                    'id' => $a['ID'],
                    'info' => '',
                    'name' => $a['NA'],
                    'price' => $a['PRC'],
                    'img' => ''
                ];
                $targetData[] = $menu;
            }
            return $targetData;
        } catch (\GuzzleHttp\Exception\BadResponseException $e) {
        }
    }
    public function Menuenable($order) //修改改為傳入id陣列
    {
        $Menu = Steakhome_menu::wherein('id', $order)->get();
        $OrderCount = count($order);
        $NotEnableCount = $Menu->where('enable', '=', 1)->count();
        if ($OrderCount !== $NotEnableCount) {
            return false;
        }
        return true;
    }
    public function Restrauntenable($rid)
    {
        $renable = Restaurant::where('id', '=', $rid)->where('enable', '=', 0)->count();
        return $renable;
    }
    public function Hasmenu($order)
    {
        $realmenu = 0;
        $ordermenu = 0;
        foreach ($order as $v) {
            $ordermenu += 1;
            $realmenu += Steakhome_menu::where('id', '=', $v['id'])->count();
        }
        return response([$realmenu, $ordermenu]);
    }


    public function SendApi($OrderInfo, $Order)
    {
        try {
            $TargetData = [
                'OID' => $OrderInfo['uid'],
                'NA' => $OrderInfo['name'],
                'PH_NUM' => '0' . $OrderInfo['phone'],
                'TOL_PRC' => $OrderInfo['totalprice'],
                'LS' => [],
            ];

            foreach ($Order as $Item) {
                if (isset($Item['description'])) {
                    $LS = [
                        'ID' => $Item['id'],
                        'NOTE' => $Item['description'],
                    ];
                } else {
                    return false;
                }
                $TargetData['LS'][] = $LS;
            }
            //發送Api
            $Client = new Client();
            $Response = $Client->request('POST', 'http://neil.xincity.xyz:9998/steak_home/api/mk/order', ['json' => $TargetData]);
            $GoodResponse = $Response->getBody();
            $ArrayGoodResponse = json_decode($GoodResponse);
            //取得結果
            if ($ArrayGoodResponse->ERR === 0) {
                return true;
            }
            return false;
        } catch (\Throwable) {
            return false;
        }

    }


    public function HasRestraunt($rid)
    {
        $hasRestraunt = Restaurant::where('id', '=', $rid)->count();
        if ($hasRestraunt != 1) {
            return false;
        }
    }
    public function Menucorrect($order)
    {
        foreach ($order as $a) {
            $client = new Client();
            $res = $client->request('GET', 'http://neil.xincity.xyz:9998/steak_home/api/menu/ls?ID=' . $a['id']);
            $goodres = $res->getBody();
            $s = json_decode($goodres, true);
            if ($s['LS'] === []) {
                return false;
            }
            $ordername = $a['name'];
            $orderprice = $a['price'];
            $orderid = $a['id'];

            $realname = $s['LS'][0]['NA'];
            $realid = $s['LS'][0]['ID'];
            $realprice = $s['LS'][0]['PRC'];

            if ($ordername != $realname) {
                return false;
            }
            if ($orderprice != $realprice) {
                return false;
            }
            if ($orderid != $realid) {
                return false;
            }
        }
        return true;
    }

}
