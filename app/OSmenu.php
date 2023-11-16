<?php

namespace App;

use Illuminate\Support\Str;

use App\Models\Oishii_menu;

use App\Contract\RestaurantInterface;
use App\Models\Restaurant;
use Faker\Core\Uuid;
use Symfony\Component\Uid\UuidV8;
use GuzzleHttp\Client;
use PhpParser\Node\Stmt\Return_;
use Spatie\FlareClient\Http\Exceptions\NotFound;

use function PHPUnit\Framework\returnSelf;

class OSmenu implements RestaurantInterface
{
    public function Getmenu($offset, $limit)
    //修改為從api取得
    {
        $a = 'http://neil.xincity.xyz:9998/oishii/api/menu/all' . '?limit=' . $limit . '&offset=' . $offset;
        try {
            $client  =  new  Client();
            $res = $client->request('GET', $a);
            $goodres = $res->getBody();
            $s = json_decode($goodres, true);
            $ss = $s['menu'];
            $targetData = [];
            foreach ($ss as $a) {
                $menu = [
                    'rid' => 1,
                    'id' => $a['meal_id'],
                    'info' => $a['meal_type'],
                    'name' => $a['meal_name'],
                    'price' => $a['price'],
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
        $menu  = 0;
        foreach ($order as $v) {
            $menu += Oishii_menu::where('id', '=', $v['id'])->where('enable', '=', 0)->count();
        }
        return $menu;
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
            $realmenu += Oishii_menu::where('id', '=', $v['id'])->count();
        }
        return response([$realmenu, $ordermenu]);
    }


    public function Change($order, $order2)
    {
        $uid2 = (string)Str::uuid();

        $targetData = [
            'id' => $uid2,
            'name' => $order->name,
            'phone_number' => '0' . (string) $order->phone,
            'pickup_time' => '2016-06-01T14:41:36+08:00',
            'total_price' => $order->totalprice,
            'orders' => [],
        ];

        foreach ($order2 as $a) {
            if (isset($a['description'])) {
                $orders = [
                    'meal_id' => $a['id'],
                    'count' => $a['quanlity'],
                    'memo' => $a['description'],
                ];
            } else {
                $orders = [
                    'meal_id' => $a['id'],
                    'count' => $a['quanlity'],
                ];
            }



            $targetData['orders'][] = $orders;
        }
        return $targetData;
    }

    public function Sendapi($order)
    {
        $client  =  new  Client();
        $res = $client->request('POST', 'http://neil.xincity.xyz:9998/oishii/api/notify/order', ['json' => $order]);
        $goodres = $res->getBody();
        $s = json_decode($goodres);
        return $s;
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
            $client  =  new  Client();
            $res = $client->request('GET', 'http://neil.xincity.xyz:9998/oishii/api/menu/all?meal_id=' . $a['id']);
            $goodres = $res->getBody();
            $s = json_decode($goodres, true);
            $ordername = $a['name'];
            $orderprice = $a['price'];
            $orderid = $a['id'];

            $realname = $s['menu'][0]['meal_name'];
            $realid = $s['menu'][0]['meal_id'];
            $realprice = $s['menu'][0]['price'];

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
        return;
    }
    public function Geterr($callbcak)
    {
        if ($callbcak->error_code == 0) {
            return true;
        }
        return false;
    }
}
