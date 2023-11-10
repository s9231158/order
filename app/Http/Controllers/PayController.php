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
use Illuminate\Support\Str;
use Tymon\JWTAuth\Facades\JWTAuth;
use GuzzleHttp\Client;
use App\CheckMacValueService;
use App\Models\Ecpay_back;
use Illuminate\Support\Facades\Cache;

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
            //存入order orderiofo資料庫
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
            foreach ($reqorder as $a) {
                $orderinfo = new Order_info([
                    'price' => $a['price'],
                    'name' => $a['name'],
                    'description' => $a['description'],
                    'quanlity' => $a['quanlity']
                ]);
                $orderr->orderinfo()->save($orderinfo);
            }
            //是否響應成功
            $CallbackStatus = $Sendapi->error_code;
            if ($CallbackStatus != 0) {
                return '訂單失敗';
            }
            //將資訊傳至第三方付款資訊
            $uid = (string)Str::uuid();
            $uuid20Char = substr($uid, 0, 20);
            $key = '0dd22e31042fbbdd';
            $iv = 'e62f6e3bbd7c2e9d';
            $a = [
                "merchant_id" => 11,
                "merchant_trade_no" => $uuid20Char,
                "merchant_trade_date" => "2023/10/20 11:59:59",
                "payment_type" => "aio",
                "amount" => 123,
                "trade_desc" => "購買商品",
                "item_name" => "尬雞堡#尬雞堡",
                "return_url" => "http://192.168.83.26:9999/api/qwe",
                "choose_payment" => "Credit",
                "check_mac_value" => "6CC73080A3CF1EA1A844F1EEF96A873FA4D1DD485BDA6517696A4D8EF0EAC94E",
                "encrypt_type" => 1,
                "lang" => "en"
            ];

            $d = new CheckMacValueService($key, $iv);
            $e = $d->generate($a);
            $a['check_mac_value'] = $e;
            $client  =  new  Client();
            $res = $client->request('POST', 'http://neil.xincity.xyz:9997/api/Cashier/AioCheckOut', ['json' => $a]);
            $goodres = $res->getBody();
            $s = json_decode($goodres);
            echo '123';
            return $s;



            // return $Sendapi;
        } catch (Exception $e) {
            return $e;
        }
    }


    public function qwe(Request $request)
    {

        try {
            $requestData = $request->all();
            Cache::set($request->merchant_trade_no, $requestData);
            $ecpayback = new Ecpay_back([
                'merchant_trade_no' => $request->merchant_trade_no,
                'merchant_id'  => $request->merchant_id,
                'trade_date' => $request->trade_date,
                'check_mac_value' => $request->check_mac_value,
                'rtn_code' => $request->rtn_code,
                'rtn_msg' => $request->rtn_msg,
                'amount' => $request->amount,
                'payment_date' => $request->payment_date,
            ]);
            $ecpayback->save();
        } catch (Exception $e) {
            return $e;
        }
    }
    public function tt()
    {

        try {
            $uid = (string)Str::uuid();
            $uuid20Char = substr($uid, 0, 20);
            $key = '0dd22e31042fbbdd';
            $iv = 'e62f6e3bbd7c2e9d';
            $a = [
                "merchant_id" => 11,
                "merchant_trade_no" => $uuid20Char,
                "merchant_trade_date" => "2023/10/20 11:59:59",
                "payment_type" => "aio",
                "amount" => 123,
                "trade_desc" => "購買商品",
                "item_name" => "product#dskjf",
                "return_url" => "http://192.168.83.26:9999/api/qwe",
                "choose_payment" => "Credit",
                "check_mac_value" => "6CC73080A3CF1EA1A844F1EEF96A873FA4D1DD485BDA6517696A4D8EF0EAC94E",
                "encrypt_type" => 1,
                "lang" => "en"
            ];

            $d = new CheckMacValueService($key, $iv);
            $e = $d->generate($a);
            $a['check_mac_value'] = $e;
            $client  =  new  Client();
            $res = $client->request('POST', 'http://neil.xincity.xyz:9997/api/Cashier/AioCheckOut', ['json' => $a]);
            $goodres = $res->getBody();
            $s = json_decode($goodres);
            echo '123';
            return $s;
        } catch (\GuzzleHttp\Exception\BadResponseException $e) {
        }
    }
}