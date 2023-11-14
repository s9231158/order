<?php
namespace App;

use App\Contract\RestaurantInterface;
use App\Models\Restaurant;
use App\Models\Tasty_menu;
use GuzzleHttp\Client;
use Illuminate\Support\Str;
class TAmenu implements RestaurantInterface{
    public function Getmenu($offset, $limit)
    //修改為從api取得
    {
        $a = 'http://neil.xincity.xyz:9998/tasty/api/menu' . '?limit=' . $limit . '&offset=' . $offset;
        try {
            $client  =  new  Client();
            $res = $client->request('GET', $a);
            $goodres = $res->getBody();
            $s = json_decode($goodres, true);

            $ss = $s['data']['list'];
            $targetData = [];
            foreach ($ss as $a) {
                if($a['enable']!='0'){
                    $menu = [
                    'rid' => 11,
                    'id' => $a['id'],
                    'info' => '',
                    'name' => $a['name'],
                    'price' => $a['price'],
                    'img' => ''
                ];
                $targetData[] = $menu;
                }
                else{
                    $menu = '870';
                }
                
            }
            return $targetData;
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
            'id' => $uid2,
            'name' => $order->name,
            'phone_number' => '0' . (string) $order->phone,
            'pickup_time' => '2016-06-01T14:41:36+08:00',
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
}
