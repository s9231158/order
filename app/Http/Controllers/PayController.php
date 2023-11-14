<?php

namespace App\Http\Controllers;

use App\Factorise;
use App\Models\Ecpay;
use App\Models\Order;
use App\Models\Order_info;
use App\Models\Restaurant;
use App\Models\User;
use App\Models\Wallet_Record;
use Exception;
use Illuminate\Http\Request;

use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use Tymon\JWTAuth\Facades\JWTAuth;
use GuzzleHttp\Client;
use App\CheckMacValueService;
use Illuminate\Support\Facades\Cache;
use App\Models\Ecpay_back;
use PhpParser\JsonDecoder;

class PayController extends Controller
{
    private $err = [
        '16' => 16 //查無此餐廳
    ];
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
                //是否有該餐廳
                $hasrestaurant = Restaurant::where('id', '=', $rid)->count();
                if ($hasrestaurant === 0) {
                    return response()->json(['err' => $this->err['16']]);
                }
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
                return '不要點菜單沒有的';
            }

            //轉換店家要求api格式
            $changedata = $Factorise->Change($request, $orders1);
            //寄送api
            $Sendapi = $Factorise->Sendapi($changedata);
            $usertoken = JWTAuth::parseToken()->authenticate();
            $user = User::find($userid);
            $userid = $user->id;
            $now = Carbon::now();
            $taketime = $request->taketime;
            //存入order資料庫
            $orderr = new Order([
                'ordertime' => $now,
                'taketime'  => $taketime,
                'total' => $totalprice,
                'phone' => $phone,
                'address' => $address,
                'status' => '付款中',
                'rid' => $rid,
            ]);
            $user->order()->save($orderr);
            //存入orderiofo資料庫
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
            $uid = (string)Str::uuid();

            $uuid20Char = substr($uid, 0, 20);


            $key = '0dd22e31042fbbdd';
            $iv = 'e62f6e3bbd7c2e9d';
            $data = [
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
            $ecpay = new Ecpay($data);
            $ecpay->save();

            // 存入wallet record
            $wrecord = new Wallet_Record([
                'out' => $totalprice,
                'uid' => $userid,
                'eid' => $uuid20Char,
                'status' => '交易中',
            ]);
            $orderr->record()->save($wrecord);


            //是否響應成功
            $CallbackStatus = $Sendapi->error_code;
            if ($CallbackStatus != 0) {
                $wrecord->status = '交易失敗';
                $wrecord->save();
                $orderr->status = '交易失敗';
                $orderr->save();
                return '訂單失敗';
            }



            //將資訊傳至第三方付款資訊

            $CheckMacValueService = new CheckMacValueService($key, $iv);
            $CheckMacValue = $CheckMacValueService->generate($data);
            $data['check_mac_value'] = $CheckMacValue;
            $client  =  new  Client();
            $res = $client->request('POST', 'http://neil.xincity.xyz:9997/api/Cashier/AioCheckOut', ['json' => $data]);
            $goodres = $res->getBody();
            $s = json_decode($goodres);
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
            $json = json_encode($requestData);
            $json = json_decode($json);
            Cache::set($request->merchant_trade_no, $requestData);
            $data = Cache::get($request->merchant_trade_no);
            // $ecpayback =Ecpay_back::create([
            //     'merchant_trade_no' => $requestData['merchant_trade_no'],
            //     'merchant_id'  => $requestData['merchant_id'],
            //     'trade_date' => $requestData['trade_date'],
            //     'check_mac_value' => $requestData['check_mac_value'],
            //     'rtn_code' => $requestData['rtn_code'],
            //     'rtn_msg' => $requestData['rtn_msg'],
            //     'amount' => $requestData['amount'],
            //     'payment_date' => $requestData['payment_date'],
            // ]);
            $ecpay1 = Ecpay::select('merchant_trade_no')->where('merchant_trade_no','=','1111111111')->get();
            $ecpay = Ecpay::select('merchant_trade_no')->where('merchant_trade_no','=','5bbef417-39df-49f4-a')->get();
            $ecpay3 = Ecpay::select('merchant_trade_no')->where('merchant_trade_no','=','1111-1111-cccc')->get();
            $ecpay4 = Ecpay::select('merchant_trade_no')->where('merchant_trade_no','=','123a456b789-123')->get();
            return response([$ecpay,$ecpay1,$ecpay3,$ecpay4]) ;  
            $ecpayback =new Ecpay_back([
                'merchant_trade_no' => $data['merchant_trade_no'],
                'merchant_id'  => $data['merchant_id'],
                'trade_date' => $data['trade_date'],
                'check_mac_value' => $data['check_mac_value'],
                'rtn_code' => $data['rtn_code'],
                'rtn_msg' => $data['rtn_msg'],
                'amount' => $data['amount'],
                'payment_date' => $data['payment_date'],
            ]);
            $ecpay->ecpayback()->save($ecpayback);
            // $ecpayback = new Ecpay_back([
            //     'merchant_trade_no' => $data->merchant_trade_no,
            //     'merchant_id'  => $data->merchant_id,
            //     'trade_date' => $data->trade_date,
            //     'check_mac_value' => $data->check_mac_value,
            //     'rtn_code' => $data->rtn_code,
            //     'rtn_msg' => $data->rtn_msg,
            //     'amount' => $data->amount,
            //     'payment_date' => $data->payment_date,
            // ]);

            // $ecpayback = new Ecpay_back([
            //     'merchant_trade_no' => $request->merchant_trade_no,
            //     'merchant_id'  => $request->merchant_id,
            //     'trade_date' => $request->trade_date,
            //     'check_mac_value' => $request->check_mac_value,
            //     'rtn_code' => $request->rtn_code,
            //     'rtn_msg' => $request->rtn_msg,
            //     'amount' => $request->amount,
            //     'payment_date' => $request->payment_date,
            // ]);
            // $ecpayback->save();
        } catch (Exception $e) {
            return $e;
        }
    }
}
