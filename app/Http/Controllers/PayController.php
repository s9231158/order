<?php

namespace App\Http\Controllers;

use App\ErrorCodeService;
use App\Factorise;
use App\Jobs\ProcessPodcast;
use App\Models\Ecpay;
use App\Models\Fail_Order_Count;
use App\Models\Login_Total;
use App\Models\Order;
use App\Models\Order_info;
use App\Models\PaymentCount;
use App\Models\Restaruant_Total_Money;
use App\Models\Restaurant;
use App\Models\Restaurant_history;
use App\Models\ServerMaintenance;
use App\Models\User;
use App\Models\User_favorite;
use App\Models\User_recode;
use App\Models\Wallet_Record;
use App\Service\OrderService;
use App\Service\UserWallerService;
use App\TotalService;
use App\UserService;
use Exception;
use Illuminate\Http\Request;
use App\DatabaseService;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Tymon\JWTAuth\Facades\JWTAuth;
use GuzzleHttp\Client;
use App\CheckMacValueService;
use App\EcpayService;
use Illuminate\Support\Facades\Cache;
use App\Models\Ecpay_back;
use PhpParser\JsonDecoder;
use Throwable;

class PayController extends Controller
{

    private $err = [
        0 => '成功',
        1 => '資料填寫與規格不符',
        2 => '必填資料未填',
        3 => 'email已註冊',
        4 => '電話已註冊',
        5 => '系統錯誤,請重新登入',
        6 => '重複登入,另一裝置請重複登入',
        7 => '短時間內登入次數過多',
        8 => '帳號或密碼錯誤',
        9 => 'token錯誤',
        10 => '未登入',
        11 => '餐廳已停用',
        12 => '請在訂餐後24內評論',
        13 => '未訂餐請勿評論',
        14 => '已評論過',
        15 => '重複新增我的最愛',
        16 => '查無此餐廳',
        17 => '餐廳未營業',
        18 => '錢包餘額不足',
        19 => '查無此訂單',
        20 => '金額錯誤',
        21 => '請勿混單',
        22 => '同張訂單內請選擇同餐廳餐點',
        23 => '無效的範圍',
        24 => '查無此餐點',
        25 => '餐點已停用',
        26 => '系統錯誤',
        27 => '訂單總金額錯誤',
        28 => '超過最大我的最愛筆數',
        29 => '請重新登入',
        30 => '菜單資訊有誤',
        31 => '就裝置以登出,請重新登入',
        32 => '登入時間過久,請重新登入',
    ];
    private $payment = [
        'ecpay' => 2,
        'local' => 1,
    ];
    // private $err = [
    //     '0' => 0, //成功
    //     '1' => 1, //資料填寫與規格不符
    //     '2' => 2, //必填資料未填
    //     '3' => 3, //email已註冊
    //     '4' => 4, //電話已註冊
    //     '5' => 5, //系統錯誤,請重新登入
    //     '6' => 6, //已登入
    //     '7' => 7, //短時間內登入次數過多
    //     '8' => 8, //帳號或密碼錯誤
    //     '9' => 9, //token錯誤
    //     '11' => 11, //餐廳已停用
    //     '15' => 15, //重複新增我的最愛
    //     '16' => 16, //查無此餐廳
    //     '17' => 17, //餐廳未營業
    //     '18' => 18, //錢包餘額不足
    //     '19' => 19, //查無此訂單
    //     '20' => 20, //金額錯誤
    //     '22' => 22, //同張訂單內請選擇同餐廳餐點
    //     '23' => 23, //無效的範圍
    //     '24' => 24, //查無此餐點
    //     '25' => 25, //餐點已停用
    //     '26' => 26, //系統錯誤
    //     '30' => 30 //菜單資訊有誤
    // ];
    private $traslate = [
        'Monday' => 1,
        'Tuesday' => 2,
        'Wednesday' => 3,
        'Thursday' => 4,
        'Friday' => 5,
        'Saturday' => 6,
        'Sunday' => 7
    ];
    private $OrderService;
    private $UserService;
    private $TotalService;
    private $UserWallerService;
    public function __construct(OrderService $OrderService, UserService $UserService, TotalService $TotalService, UserWallerService $UserWallerService)
    {
        $this->UserService = $UserService;
        $this->OrderService = $OrderService;
        $this->TotalService = $TotalService;
        $this->UserWallerService = $UserWallerService;
    }
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
            'name.required' => $this->err['2'],
            'name.max' => $this->err['1'],
            'name.min' => $this->err['1'],
            'address.required' => $this->err['2'],
            'address.min' => $this->err['1'],
            'address.max' => $this->err['1'],
            'phone.required' => $this->err['2'],
            'phone.string' => $this->err['1'],
            'phone.size' => $this->err['1'],
            'phone.regex' => $this->err['1'],
            'totalprice.required' => $this->err['2'],
            'totalprice.regex' => $this->err['1'],
            'taketime.required' => $this->err['2'],
            'taketime.date' => $this->err['1'],
            'orders.required' => $this->err['1'],
            'orders.array' => $this->err['1'],
            'orders.*.rid.required' => $this->err['1'],
            'orders.*.rid.regex' => $this->err['2'],
            'orders.*.id.required' => $this->err['2'],
            'orders.*.id.regex' => $this->err['2'],
            'orders.*.name.required' => $this->err['1'],
            'orders.*.name.max' => $this->err['1'],
            'orders.*.name.min' => $this->err['1'],
            'orders.*.price.required' => $this->err['1'],
            'orders.*.price.regex' => $this->err['1'],
        ];
        $validator = Validator::make($request->all(), $ruls, $rulsMessage);
        //如果有錯回報錯誤訊息
        if ($validator->fails()) {
            return response()->json(['err' => $validator->errors()->first()]);
        }
        $address = $request->address;
        $phone = $request->phone;
        $totalprice = $request->totalprice;
        $orders1 = $request->orders;
        $rid = $orders1[0]['rid'];
        $ridString = strval($rid);

        try {
            //是否有該餐廳
            foreach ($orders1 as $order) {
                $hasRestraunt = Restaurant::where('id', '=', $order['rid'])->count();
                if ($hasRestraunt != 1) {
                    return response()->json(['err' => $this->err['16']]);
                }
            }
            //檢查菜單金額名稱id是否與店家一致
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
                    return response()->json(['err' => $this->err['22']]);
                }
            }
            if ($realtotalprice != $totalprice) {
                return response()->json(['err' => $this->err['20']]);
            }
            $usertoken = JWTAuth::parseToken()->authenticate();
            $userid = $usertoken->id;
            $user = User::find($userid);
            $wallet = $user->wallet()->get();
            $balance = $wallet[0]['balance'];
            //錢包餘額是否大於totoprice
            if ($request->payment == 'local') {
                if ($balance <= $realtotalprice) {
                    return response()->json(['err' => $this->err['18']]);
                }
            }

            //餐點餐廳今天是否有營業
            $day = Carbon::now()->format('l');
            $daynumber = $this->traslate[$day];
            $Restaurantopen = Restaurant::where('id', '=', $rid)->where('openday', 'like', '%' . $daynumber . '%')->count();
            if ($Restaurantopen == 0) {
                return response()->json(['err' => $this->err['17']]);
            }
            $Factorise = Factorise::Setmenu($ridString);
            // 餐點是否停用
            $Menuenable = $Factorise->Menuenable($orders1);
            if ($Menuenable != 0) {
                return response()->json(['err' => $this->err['25']]);
            }
            //餐廳是否停用
            $Restrauntenable = $Factorise->Restrauntenable($ridString);
            if ($Restrauntenable != 0) {
                return response()->json(['err' => $this->err['11']]);
            }

            //是否有該餐點
            $Hasmenu = $Factorise->Hasmenu($orders1);
            // return reset($Hasmenu);
            $realmenu = $Hasmenu->original[0];
            $ordermenu = $Hasmenu->original[1];
            if ($realmenu != $ordermenu) {
                return response()->json(['err' => $this->err['24']]);
            }

            $Hasapi = Restaurant::where('id', '=', $rid)->where('api', '!=', null)->where('api', '!=', '')->count();



            if ($Hasapi != 0) {
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
            }
            $now = Carbon::now();
            $taketime = $request->taketime;

            //存入order資料庫
            $orderr = new Order([
                'ordertime' => $now,
                'taketime' => $taketime,
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

            $uid = (string) Str::uuid();

            $uuid20Char = substr($uid, 0, 20);
            if ($request->payment == 'local') {
                try {
                    //將user錢包扣款
                    $wallet[0]->balance -= $totalprice;
                    $user->wallet()->saveMany($wallet);
                    // 存入wallet record
                    $wrecord = new Wallet_Record([
                        'out' => $totalprice,
                        'uid' => $userid,
                        'status' => '成功',
                        'pid' => $this->payment[$request->payment],
                    ]);
                    $orderr->record()->save($wrecord);
                    $orderr->status = '成功';
                    $orderr->save();

                    return response()->json(['err' => $this->err['0'], 'oid' => $orderr->id]);
                } catch (Throwable $e) {
                    $wrecord->status = '失敗';
                    $wrecord->save();
                    $orderr->status = '失敗';
                    $orderr->save();
                    return '訂單失敗';
                }
            }
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
                'pid' => $this->payment[$request->payment],
            ]);
            $orderr->record()->save($wrecord);

            //是否有api  是否響應成功
            if ($Hasapi != 0) {
                $errcode = $Factorise->Geterr($Sendapi);
                if ($errcode = false) {
                    $wrecord->status = '失敗';
                    $wrecord->save();
                    $orderr->status = '失敗';
                    $orderr->save();
                    return '訂單失敗';
                }
            }

            //將資訊傳至第三方付款資訊
            $CheckMacValueService = new CheckMacValueService($key, $iv);
            $CheckMacValue = $CheckMacValueService->generate($data);
            $data['check_mac_value'] = $CheckMacValue;
            $client = new Client();
            $res = $client->request('POST', 'http://neil.xincity.xyz:9997/api/Cashier/AioCheckOut', ['json' => $data]);
            $goodres = $res->getBody();
            $s = json_decode($goodres);
            return $s;
        } catch (Exception $e) {
            return response()->json(['err' => $this->err['26']]);
        } catch (Throwable $e) {
            return response()->json(['err' => $this->err['26']]);
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
                'merchant_id' => $request->merchant_id,
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
            } else {
                $apple = $ecpay1->Record()->get();
                $apple[0]->status = '失敗';
                $ecpay1->Record()->saveMany($apple);
                $recrd = Order::find($apple[0]->oid);
                $recrd->status = '失敗';
                $recrd->save();
            }
        } catch (Exception $e) {
            return $e;
        } catch (Throwable $e) {
            return response()->json([$e, 'err' => $this->err['26']]);
        }
    }


    public function order(Request $request)
    {
        //規則
        $ruls = [
            'limit' => ['regex:/^[0-9]+$/'],
            'offset' => ['regex:/^[0-9]+$/'],
            'rid' => ['regex:/^[0-9]+$/'],
        ];
        //什麼錯誤報什麼錯誤訊息
        $rulsMessage = [
            'limit.regex' => $this->err['23'],
            'offset.regex' => $this->err['23'],
            'rid.regex' => $this->err['23'],
            'rid.required' => $this->err['2'],
        ];

        try {
            //驗證參輸入數
            $validator = Validator::make($request->all(), $ruls, $rulsMessage);
            if ($validator->fails()) {
                return response()->json(['err' => $validator->errors()->first()]);
            }

            $userinfo = JWTAuth::parseToken()->authenticate();
            $user = User::find($userinfo->id);
            //是否有填入oid
            if ($request->oid != null) {
                $oid = $request->oid;
                $order = $user->order()->select('id', 'ordertime', 'taketime', 'total', 'status')->where('id', '=', $oid)->get();
                $count = $user->order()->where('id', '=', $oid)->count();
                if ($count == 0) {
                    return response()->json(['err' => $this->err['19']]);
                }
                return response()->json(['err' => $this->err['0'], 'order' => $order[0]]);
            }

            if ($request->limit == null) {
                $limit = '20';
            } else {
                $limit = $request->limit;
            }
            if ($request->offset === null) {
                $offset = '0';
            } else {
                $offset = $request->offset;
            }
            $order = $user->order()->select('id', 'ordertime', 'taketime', 'total', 'status')->limit($limit)->offset($offset)->orderBy('ordertime', 'desc')->get();
            $count = $user->order()->select('id', 'ordertime', 'taketime', 'total', 'status')->count();
            return response()->json(['err' => $this->err['0'], 'count' => $count, 'order' => $order]);
        } catch (Throwable $e) {
            return response()->json([$e, 'err' => $this->err['26']]);
        }
    }
    public function orderinfo(Request $request)
    {
        //規則
        $ruls = [
            'oid' => ['regex:/^[0-9]+$/'],
        ];
        //什麼錯誤報什麼錯誤訊息
        $rulsMessage = [
            'oid.regex' => $this->err['1']
        ];

        try {
            //驗證參輸入數
            $validator = Validator::make($request->all(), $ruls, $rulsMessage);
            if ($validator->fails()) {
                return response()->json(['err' => $validator->errors()->first()]);
            }
            $oid = $request->oid;
            $orderinfno = Order_info::select('name', 'quanlity', 'price', 'description')->where('oid', '=', $oid)->get();
            $count = Order_info::select('id', 'name', 'quanlity', 'price', 'description')->where('oid', '=', $oid)->count();
            if ($count === 0) {
                return response()->json(['err' => $this->err['19']]);
            }
            return response()->json(['err' => $this->err['0'], 'ordersinfo' => $orderinfno]);
        } catch (Throwable $e) {
            return response()->json([$e, 'err' => $this->err['26']]);
        }
    }
    public function AddWalletMoney(Request $request)
    {
        try {
            $err = new ErrorCodeService;
            $errr = $this->err;
            $token = $request->header('Authorization');
            //取得UserId
            $UserInfo = $this->TotalService->GetUserInfo();
            $UserId = $UserInfo['id'];
            //送api至Ecpay
            $EcpayService = new EcpayService();
            $Uuid = $EcpayService->GetUuid();
            $Date = $EcpayService->GetDate();
            $Money = $request['money'];
            $Data = [
                "merchant_id" => 11,
                "merchant_trade_no" => $Uuid,
                "merchant_trade_date" => $Date,
                "payment_type" => "aio",
                "amount" => $Money,
                "trade_desc" => $UserId . '加值',
                "item_name" => '加值',
                "return_url" => "http://192.168.83.26:9999/api/moneycallback",
                "choose_payment" => "Credit",
                "encrypt_type" => 1,
                "lang" => "en"
            ];
            $CheckMacValue = $EcpayService->GetCheckMacValue($Data);
            $Data['check_mac_value'] = $CheckMacValue;
            //將Ecpay資料存入Ecpays
            $DatabaseService = new DatabaseService();
            $DatabaseService->SaveEcpay($Data);
            $WalletRecord = [
                'in' => $Money,
                'uid' => $UserId,
                'eid' => $Uuid,
                'status' => '交易中',
                'pid' => 1,
            ];
            //將資料存入WalletRecord
            $DatabaseService->SaveRecord($WalletRecord);
            //將資料傳至Ecapy
            $Response = $EcpayService->SendApi($Data);
            return $Response;
        } catch (Throwable $e) {
            return response()->json(['err' => array_search('系統錯誤', $errr), 'message' => $errr['26']]);
        }
    }
    public function moneycallback(Request $request)
    {
        try {
            $err = new ErrorCodeService;
            $errr = $this->err;
            //取得交易編號
            $merchant_trade_no = $request->merchant_trade_no;
            $TradeDate = Carbon::createFromFormat('d/M/y H:m:s', $request->trade_date);
            $PaymentDate = Carbon::createFromFormat('d/M/y H:m:s', $request->payment_date);
            //取的該交易編號Ecpay Collection
            $DatabaseService = new DatabaseService();
            $Ecpay = $DatabaseService->GetEcpayCollection($merchant_trade_no);

            //將EcapyCallBack存至Ecpay關聯資料庫
            $Ecpayback = new Ecpay_back([
                'merchant_id' => $request->merchant_id,
                'trade_date' => $TradeDate,
                'check_mac_value' => $request->check_mac_value,
                'rtn_code' => $request->rtn_code,
                'rtn_msg' => $request->rtn_msg,
                'amount' => $request->amount,
                'payment_date' => $PaymentDate,
            ]);
            $DatabaseService->SaveEcpayCallBack($Ecpay, $Ecpayback);
            //如果EcpayCallBack回傳的rtn_code為1
            if ($request->rtn_code == 1) {
                //取得此Ecpay關聯的WalletRecord
                $Record = $DatabaseService->GetRecordCollenction($Ecpay);
                //將關聯walletRecord status改成0
                $Record[0]->status = '成功';
                //將更新後WalletRecord儲存
                $DatabaseService->SaveEcpayRecord($Ecpay, $Record);
                $UserInfo = $this->TotalService->GetUserInfo();
                $UserId = $UserInfo['id'];
                $Money = $request->amount;
                $AddWallet = $this->UserWallerService->AddWallet($Money, $UserId);
            } else {
                $Record = $DatabaseService->GetRecordCollenction($Ecpay);
                $Record[0]->status = '失敗';
                $DatabaseService->SaveEcpayRecord($Ecpay, $Record);
            }
        } catch (Exception $e) {
            return $e;
        } catch (Throwable $e) {
            return response()->json(['err' => array_search('系統錯誤', $errr), 'message' => $errr['26']]);
        }
    }
    public function wallet(Request $request)
    {
        //取得總ErrCode
        $err = new ErrorCodeService;
        $errr = $this->err;
        //規則
        $ruls = [
            'limit' => ['regex:/^[0-9]+$/'],
            'offset' => ['regex:/^[0-9]+$/'],
        ];
        //什麼錯誤報什麼錯誤訊息
        $rulsMessage = [
            'limit.regex' => array_search('無效的範圍', $errr),
            'offset.regex' => array_search('無效的範圍', $errr),
        ];
        try {
            //驗證參輸入數
            $validator = Validator::make($request->all(), $ruls, $rulsMessage);
            if ($validator->fails()) {
                return response()->json(['err' => $validator->errors()->first(), 'message' => $errr[$validator->errors()->first()]]);
            }

            //取的抓取範圍&類型
            $Range = ['offset' => $request->offset, 'limit' => $request->limit];
            $Type = $request->type;
            //取得該使用者Service
            $token = $request->header('Authorization');
            //取得該使用者WalletRecord
            $WalletRecord = $this->UserService->GetUserWallet($Range, $Type);
            return response()->json(['err' => array_search('成功', $errr), 'message' => $errr[0], 'count' => $WalletRecord['count'], 'data' => $WalletRecord['wallet']]);
        } catch (Exception $e) {
            return response()->json([$e, 'err' => array_search('系統錯誤', $errr), 'message' => $errr[26]]);
        }

    }

    public function apple()
    {

        //取出昨天00:00
        $Start = Carbon::yesterday();
        //取出今天00:00
        $End = Carbon::today();
        //取出昨天至今天所有訂單資料
        $Order = $this->OrderService->GetSomeTimeOrder($Start, $End);
        $Yesterday = Carbon::yesterday();
        $YesterdayAddHour = Carbon::yesterday()->addHour();
        $Orderlist = [];
        for ($I = 0; $I < 24; $I++) {
            // //取得每小時失敗的所有訂單
            $EveryHourFailOrder = $Order->whereBetween('created_at', [$Yesterday, $YesterdayAddHour])->where('status', '=', '失敗');
            // //取得每小時的所有訂單
            $EveryHourOrder = $Order->whereBetween('created_at', [$Yesterday, $YesterdayAddHour])->where('status', '=', '失敗');
            //取得每小時失敗訂單次數
            $EveryHourFailOrderCount = $EveryHourFailOrder->count();
            //取得每小時訂單次數
            $EveryHourOrderCount = $EveryHourOrder->count();
            //將開始時間放入Orderlist
            $Orderlist[$I]['starttime'] = $Yesterday->copy();
            //將結束時間放入Orderlist
            $Orderlist[$I]['endtime'] = $YesterdayAddHour->copy();
            //將失敗訂單次數放入Orderlist
            $Orderlist[$I]['failcount'] = $EveryHourFailOrderCount;
            $Orderlist[$I]['totalcount'] = $EveryHourOrderCount;
            $Orderlist[$I]['created_at'] = Carbon::now();
            $Orderlist[$I]['updated_at'] = Carbon::now();
            //對起始時間加一小
            $Yesterday = $Yesterday->addHour();
            //對終止時間加一小
            $YesterdayAddHour = $YesterdayAddHour->addHour();
        }
        //存入資料庫
        Fail_Order_Count::insert($Orderlist);

    }

}
