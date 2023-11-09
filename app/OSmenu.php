<?php

namespace App;

use Illuminate\Support\Str;

use App\Models\Oishii_menu;

use App\Contract\OSmenu as ContractOSmenu;
use App\Models\Restaurant;
use Faker\Core\Uuid;
use Symfony\Component\Uid\UuidV8;
use GuzzleHttp\Client;
use PhpParser\Node\Stmt\Return_;

use function PHPUnit\Framework\returnSelf;

class OSmenu implements ContractOSmenu
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
            $targetData =[];
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
        $uid = (string)Str::uuid();

        $targetData = [
            'id' => $uid,
            'name' => $order->name,
            'phone_number' => (string) $order->phone,
            'pickup_time' => now()->toIso8601String(),
            'total_price' => $order->totalprice,
            'orders' => [],
        ];

        foreach ($order2 as $a) {
            $orders = [
                'meal_id' => $a['id'],
                'count' => $a['quanlity'],
                'memo' => $a['description'],
            ];
            $targetData['orders'][] = $orders;
        }
        return $targetData;
    }

    public function Sendapi($order)
    {
        $client  =  new  Client();
        $res = $client->request('POST', 'http://neil.xincity.xyz:9998/tasty/api/order', $order);
        $goodres = $res->getBody();
        $s = json_decode($goodres);
        return $s;
    }
}
