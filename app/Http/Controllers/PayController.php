<?php

namespace App\Http\Controllers;

use App\Factorise;
use App\Models\Order;
use App\Models\Order_info;
use App\Models\Restaurant;
use App\Models\User;
use Exception;
use Illuminate\Http\Request;

use Illuminate\Support\Carbon;
use Tymon\JWTAuth\Facades\JWTAuth;
use GuzzleHttp\Client;

class PayController extends Controller
{
    private $traslate = [
        'Monday' => 1,
        'Tuesday' => 2,
        'Wednesday' => 3,
        'Thursday' => 4,
        'Friday' => 5,
        'Saturday' => 6,
        'Sunday' => 7
    ];
    public function otherpay(Request $request)
    {
        $name = $request->name;
        $address = $request->address;
        $phone = $request->phone;
        $totalprice = $request->totalprice;
        $orders1 = $request->orders;
        $oname = $orders1[0]['name'];
        $rid = $orders1[0]['rid'];
        $oid = $orders1[0]['id'];
        $oprice = $orders1[0]['price'];
        $oquanlity = $orders1[0]['quanlity'];
        $odescription = $orders1[0]['description'];
        $ridString = strval($rid);

        try {

            //訂單總金額是否正確
            $realtotalprice = 0;
            foreach ($orders1 as $a) {
                $realtotalprice += $a['price'] * $a['quanlity'];
                //訂單是否都來自同一間餐廳
                if ($a['rid'] != $rid) {
                    return '點同一間好嗎';
                }
            }
            if ($realtotalprice != $totalprice) {
                return response()->json(['err' => '價格錯誤']);
            }
            //錢包餘額是否大於totoprice
            $usertoken = JWTAuth::parseToken()->authenticate();
            $userid = $usertoken->id;
            $user = User::find($userid);
            $wallet = $user->wallet()->get();
            $balance =  $wallet[0]['balance'];
            if ($balance < $realtotalprice) {
                return '錢不夠好嗎';
            }
            //餐點餐廳今天是否有營業
            $day = Carbon::now()->format('l');
            $daynumber = $this->traslate[$day];
            $Restaurantopen = Restaurant::where('id', '=', $rid)->where('openday', 'like', '%' . $daynumber . '%')->count();
            if ($Restaurantopen == 0) {
                return '就沒開好嗎';
            }
            $Factorise = Factorise::Setmenu($ridString);
            // 餐點是否停用
            $Menuenable = $Factorise->Menuenable($orders1);
            if ($Menuenable != 0) {
                return '就停用好嗎';
            }
            //餐廳是否停用
            $Restrauntenable = $Factorise->Restrauntenable($ridString);
            if ($Restrauntenable != 0) {
                return '餐廳就停用好嗎';
            }

            //是否有該餐點
            $Hasmenu = $Factorise->Hasmenu($orders1);
            $realmenu = $Hasmenu->original[0];
            $ordermenu = $Hasmenu->original[1];
            if ($realmenu != $ordermenu) {
                return '幹你娘不要點菜單沒有的';
            }

            //轉換店家要求api格式
            $changedata = $Factorise->Change($request, $orders1);


            //寄送api
            $Sendapi = $Factorise->Sendapi($changedata);
            $user = JWTAuth::parseToken()->authenticate();
            $aa = User::find($userid);
            $userid = $user->id;
            $now = Carbon::now();
            $taketime = $request->taketime;
            $orderr = new Order([
                'ordertime' => $now,
                'taketime'  => $taketime,
                'total' => $totalprice,
                'phone' => $phone,
                'address' => $address,
                'status' => '付款中',
                'rid' => $rid,
            ]);
            $aa->order()->save($orderr);
            $orderinfo = new Order_info();
            $reqorder = $request['orders'];
            // // return gettype($reqorder);
            foreach ($reqorder as $a) {
                $orderinfo = new Order_info([
                    'price' => $a['price'],
                    'name' => $a['name'],
                    'description' => $a['description'],
                    'quanlity' => $a['quanlity']
                ]);
                $orderr->orderinfo()->save($orderinfo);
            }



            // $orderinfo = Order_info::create([
            //     'price'=>$oprice,
            //     'quanlity'=>$oquanlity,
            //     'name'=>$oname,
            //     'oid'=>$oid,
            //     'description'=>$odescription
            // ]);

            $CallbackStatus = $Sendapi->error_code;

            return $Sendapi;
        } catch (Exception $e) {
            return $e;
        }
    }
    public function tt()
    {

        try {
            $client  =  new  Client();
            $res = $client->request('POST', 'http://neil.xincity.xyz:9997/api/Cashier/AioCheckOut');
            $goodres = $res->getBody();
            $s = json_decode($goodres);
            return $s;
        } catch (\GuzzleHttp\Exception\BadResponseException $e) {
        }
    }
}
