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
use Illuminate\Support\Facades\Validator;
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
        '0' => 0, //成功
        '1' => 1, //資料填寫與規格不符
        '2' => 2, //必填資料未填
        '3' => 3, //email已註冊
        '4' => 4, //電話已註冊
        '5' => 5, //系統錯誤,請重新登入
        '6' => 6, //已登入
        '7' => 7, //短時間內登入次數過多
        '8' => 8, //帳號或密碼錯誤
        '9' => 9, //token錯誤
        '15' => 15, //重複新增我的最愛
        '16' => 16, //查無此餐廳
        '23' => 23, //無效的範圍
        '26' => 26, //系統錯誤
        '30' => 30 //菜單資訊有誤
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
        //規則
        $ruls = [
            'name' => ['required', 'max:25', 'min:3'],
            'address' => ['required', 'min:10', 'max:25'],
            'phone' => ['required', 'string', 'size:9', 'regex:/^[0-9]+$/'],
            'totalprice' => ['required', 'regex:/^[0-9]+$/'],
            'taketime' => ['required', 'date'],
            'orders' => ['required', 'array'],
            'orders.*.rid' => ['required', 'regex:/^[0-9]+$/'],
            'orders.*.id' => ['required'],
            'orders.*.name' => ['required', 'max:25', 'min:1'],
            'orders.*.price' => ['required'],
        ];
        //什麼錯誤報什麼錯誤訊息
        $rulsMessage = [
            'name.required' => $this->err['2'], 'name.max' => $this->err['1'], 'name.min' => $this->err['1'],
            'address.required' => $this->err['2'], 'address.min' => $this->err['1'], 'address.max' => $this->err['1'],
            'phone.required' => $this->err['2'], 'phone.string' => $this->err['1'], 'phone.size' => $this->err['1'], 'phone.regex' => $this->err['1'],
            'totalprice.required' => $this->err['2'], 'totalprice.regex' => $this->err['1'],
            'taketime.required' => $this->err['2'], 'taketime.date' => $this->err['1'],
            'orders.required' => $this->err['1'], 'orders.array' => $this->err['1'],
            'orders.*.rid.required' => $this->err['1'], 'orders.*.rid.regex' => $this->err['2'],
            'orders.*.id.required' => $this->err['2'], 'orders.*.id.regex' => $this->err['2'],
            'orders.*.name.required' => $this->err['1'], 'orders.*.name.max' => $this->err['1'], 'orders.*.name.min' => $this->err['1'],
            'orders.*.price.required' => $this->err['1'], 'orders.*.price.regex' => $this->err['1'],
        ];
        $validator = Validator::make($request->all(), $ruls, $rulsMessage);
        //如果有錯回報錯誤訊息
        if ($validator->fails()) {
            return response()->json(['err' => $validator->errors()->first()]);
        }
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
        // $odescription = $orders1[0]['description'];
        $ridString = strval($rid);

        try {
            $Factorise = Factorise::Setmenu($ridString);
            $Menucorrect = $Factorise->Menucorrect($orders1);
            if ($Menucorrect === false) {
                return response()->json(['err' => $this->err['30']]);
            }


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
            if ($changedata === false) {
                return response()->json(['err' => $this->err['1']]);
            }

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
            $item_name = '';

            $sameorder = [];
            foreach ($reqorder as $a) {
                $item_name = $a['name'] . '#' . $item_name;
                array_push($sameorder, $a['id']);

                if (isset($a['description'])) {
                    $orderinfo = new Order_info([
                        'price' => $a['price'],
                        'name' => $a['name'],
                        'description' => $a['description'],
                        'quanlity' => $a['quanlity']
                    ]);
                    $orderr->orderinfo()->save($orderinfo);
                } else {
                    $orderinfo = new Order_info([
                        'price' => $a['price'],
                        'name' => $a['name'],
                        'quanlity' => $a['quanlity'],
                    ]);
                    $orderr->orderinfo()->save($orderinfo);
                }
            }
            //是否有多筆一樣餐點
            $oldcount = count($sameorder);
            $sameorder = array_unique($sameorder);
            $newcount = count($sameorder);
            if ($oldcount != $newcount) {
                return '一樣的餐點';
            }

            $uid = (string)Str::uuid();

            $uuid20Char = substr($uid, 0, 20);
            $date = Carbon::now()->format('Y/m/d H:i:s');
            $key = '0dd22e31042fbbdd';
            $iv = 'e62f6e3bbd7c2e9d';
            $data = [
                "merchant_id" => 11,
                "merchant_trade_no" => $uuid20Char,
                "merchant_trade_date" => $date,
                "payment_type" => "aio",
                "amount" => $totalprice,
                "trade_desc" => $userid . '訂餐',
                "item_name" => $item_name,
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
            $errcode = $Factorise->Geterr($Sendapi);



            if ($errcode = false) {
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
        } catch (Exception $e) {
            return $e;
        }
    }


    public function qwe(Request $request)
    {

        try {
            $requestData = $request->all();
            Cache::set($request->merchant_trade_no, $requestData);
            $trade_date = Carbon::createFromFormat('d/M/y H:m:s', $request->trade_date);
            $payment_date = Carbon::createFromFormat('d/M/y H:m:s', $request->payment_date);
            $ecpay1 = Ecpay::find($requestData['merchant_trade_no']);

            $ecpayback = new Ecpay_back([
                'merchant_id'  => $request->merchant_id,
                'trade_date' => $trade_date,
                'check_mac_value' => $request->check_mac_value,
                'rtn_code' => $request->rtn_code,
                'rtn_msg' => $request->rtn_msg,
                'amount' => $request->amount,
                'payment_date' => $payment_date,
            ]);
            $ecpay1->ecpayback()->save($ecpayback);


            if ($request->rtn_code == 1) {
                $apple = $ecpay1->Record()->get();
                $apple[0]->status = '成功';
                $ecpay1->Record()->saveMany($apple);
                $recrd = Order::find($apple[0]->oid);
                $recrd->status = '成功';
                $recrd->save();
            }else{
                $apple = $ecpay1->Record()->get();
                $apple[0]->status = '失敗';
                $ecpay1->Record()->saveMany($apple);
                $recrd = Order::find($apple[0]->oid);
                $recrd->status = '失敗';
                $recrd->save();
            }
        } catch (Exception $e) {
            return $e;
        }
    }
}
