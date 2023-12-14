<?php

namespace App;

use App\Contract\RestaurantInterface;
use App\Models\Restaurant;
use App\Models\Tasty_menu;
use GuzzleHttp\Client;
use Illuminate\Support\Facades\Date;
use Throwable;

class TAmenu implements RestaurantInterface
{
    public function Getmenu($Offset, $Limit)
    //修改為從api取得
    {
        $a = 'http://neil.xincity.xyz:9998/tasty/api/menu' . '?limit=' . $Limit . '&offset=' . $Offset;
        try {
            $client = new Client();
            $res = $client->request('GET', $a);
            $goodres = $res->getBody();
            $s = json_decode($goodres, true);

            $ss = $s['data']['list'];
            $targetData = [];
            foreach ($ss as $a) {
                if ($a['enable'] != '0') {
                    $menu = [
                        'rid' => 2,
                        'id' => $a['id'],
                        'info' => '',
                        'name' => $a['name'],
                        'price' => $a['price'],
                        'img' => ''
                    ];
                    $targetData[] = $menu;
                } else {
                    $menu = '870';
                }
            }
            return $targetData;
        } catch (\GuzzleHttp\Exception\BadResponseException $e) {
        }
    }
    public function Menuenable($order)
    {
        $Menu = Tasty_menu::wherein('id', $order)->get();
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
            $realmenu += Tasty_menu::where('id', '=', $v['id'])->count();
        }
        return response([$realmenu, $ordermenu]);
    }


    public function SendApi($OrderInfo, $Order)
    {
        try {
            $DateTime = Date::createFromFormat('Y-m-d H:i:s', $OrderInfo['taketime']);
            $Iso8601String = $DateTime->format('c');
            $TargetData = [
                'order_id' => $OrderInfo['uid'],
                'name' => $OrderInfo['name'],
                'phone_number' => '0' . $OrderInfo['phone'],
                'pickup_time' => "2016-06-01T14:41:36+08:00",
                'total_price' => $OrderInfo['totalprice'],
                'order' => ['list' => []],
            ];
            foreach ($Order as $Item) {
                if (isset($Item['description'])) {
                    $List = [
                        'id' => $Item['id'],
                        'count' => $Item['quanlity'],
                        'description' => $Item['description'],
                    ];
                } else {
                    $List = [
                        'id' => $Item['id'],
                        'count' => $Item['quanlity'],
                    ];
                }
                $TargetData['order']['list'][] = $List;
            }
            //發送Api
            $Client = new Client();
            $Response = $Client->request('POST', 'http://neil.xincity.xyz:9998/tasty/api/order', ['json' => $TargetData]);
            $GoodResponse = $Response->getBody();
            $ArrayGoodResponse = json_decode($GoodResponse);
            //取得結果
            if ($ArrayGoodResponse->error_code === 0) {
                return true;
            }
            return false;
        } catch (Throwable $e) {
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
            $res = $client->request('GET', 'http://neil.xincity.xyz:9998/tasty/api/menu?id=' . $a['id']);
            $goodres = $res->getBody();
            $s = json_decode($goodres, true);

            if ($s['data']['list'] === []) {
                return false;
            }
            $ordername = $a['name'];
            $orderprice = $a['price'];
            $orderid = $a['id'];

            $realname = $s['data']['list'][0]['name'];
            $realid = $s['data']['list'][0]['id'];
            $realprice = $s['data']['list'][0]['price'];
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
