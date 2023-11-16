<?php

namespace App;

use App\Contract\RestaurantInterface;
use App\Models\Local_menu;
use App\Models\Restaurant;
use App\Models\Tasty_menu;
use GuzzleHttp\Client;
use Illuminate\Support\Str;
use Throwable;

class Localmenu implements RestaurantInterface
{
    public function Getmenu($offset, $limit)
    //修改為從api取得
    {
        try {
            $menu = Local_menu::select('rid', 'id', 'info', 'name', 'price', 'img')->limit($limit)->offset($offset)->get();
            return $menu;
        } catch (\GuzzleHttp\Exception\BadResponseException $e) {
        }
    }
    public function Menuenable($order) //修改改為傳入id陣列
    {
        $menu  = 0;
        foreach ($order as $v) {
            $menu += Tasty_menu::where('id', '=', $v['id'])->where('enable', '=', 0)->count();
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
            $realmenu += Tasty_menu::where('id', '=', $v['id'])->count();
        }
        return response([$realmenu, $ordermenu]);
    }


    public function Change($order, $order2)
    {
        $uid2 = (string)Str::uuid();

        $targetData = [
            'order_id' => $uid2,
            'name' => $order->name,
            'phone_number' => '0' . (string) $order->phone,
            'pickup_time' => '2016-06-01T14:41:36+08:00',
            'total_price' => $order->totalprice,
            'order' => ['list' => []],
        ];

        foreach ($order2 as $a) {
            if (isset($a['description'])) {
                $list = [
                    'id' => $a['id'],
                    'count' => $a['quanlity'],
                    'description' => $a['description'],
                ];
            } else {
                $list = [
                    'id' => $a['id'],
                    'count' => $a['quanlity'],
                ];
            }

            $targetData['order']['list'][] = $list;
        }
        return $targetData;
    }

    public function Sendapi($order)
    {
        $client  =  new  Client();
        $res = $client->request('POST', 'http://neil.xincity.xyz:9998/tasty/api/order', ['json' => $order]);
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
        try {
            foreach ($order as $a) {
                $menu = Local_menu::where('id', '=', $a['id'])->get();
                $ordername = $a['name'];
                $orderprice = $a['price'];
                $orderid = $a['id'];
                $realname = $menu[0]['name'];
                $realid = $menu[0]['id'];
                $realprice = $menu[0]['price'];
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
        } catch (Throwable $e) {
            return false;
        }
    }
    public function Geterr($callbcak)
    {
        if ($callbcak->error_code == 0) {
            return true;
        }
        return false;
    }


}
