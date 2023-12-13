<?php

namespace App\Http\Controllers;

use App\ErrorCodeService;
use App\Factorise;
use App\Models\Ecpay;
use App\Models\Order;
use App\Models\Order_info;
use App\Models\Restaurant;
use App\Models\User;
use App\Models\Wallet_Record;
use App\Service\OrderInfoService;
use App\Service\OrderService;
use App\Service\RestaurantService;
use App\Service\UserWallerService;
use App\Service\WalletRecordService;
use App\ServiceV2\CreateOrderV2;
use App\TotalService;
use App\UserService;
use Exception;
use Illuminate\Http\Request;
use App\DatabaseService;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Tymon\JWTAuth\Facades\JWTAuth;
use GuzzleHttp\Client;
use App\CheckMacValueService;
use App\EcpayService;
use Illuminate\Support\Facades\Cache;
use App\Models\Ecpay_back;
use Throwable;
use App\ServiceV2\CreateOrderServiceV2;

class PayController extends Controller
{
    private $OrderService;
    private $UserService;
    private $TotalService;
    private $UserWallerService;
    private $WalletRecordService;
    private $ErrorCodeService;
    private $OrderInfoService;
    private $Payment = [
        'ecpay' => 2,
        'local' => 1,
    ];

    //new
    private $Err;
    private $Keys;
    private $RestaurantService;
    private $CreateOrderServiceV2;
    public function __construct(CreateOrderServiceV2 $CreateOrderServiceV2, OrderInfoService $OrderInfoService, RestaurantService $RestaurantService, ErrorCodeService $ErrorCodeService, WalletRecordService $WalletRecordService, OrderService $OrderService, UserService $UserService, TotalService $TotalService, UserWallerService $UserWallerService)
    {
        $this->CreateOrderServiceV2 = $CreateOrderServiceV2;
        $this->OrderInfoService = $OrderInfoService;
        $this->RestaurantService = $RestaurantService;
        $this->ErrorCodeService = $ErrorCodeService;
        $this->UserService = $UserService;
        $this->OrderService = $OrderService;
        $this->TotalService = $TotalService;
        $this->UserWallerService = $UserWallerService;
        $this->WalletRecordService = $WalletRecordService;
        $this->Err = $ErrorCodeService->GetErrCode();
        $this->Keys = $ErrorCodeService->GetErrKey();
    }
    public function otherpay(Request $Request)
    {
        //規則
        $Ruls = [
            'payment' => ['required', 'in:ecpay,local'],
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
        $RulsMessage = [
            'payment' => 2,
            'payment.in' => 33,
            'name.required' => 2,
            'name.max' => 1,
            'name.min' => 1,
            'address.required' => 2,
            'address.min' => 1,
            'address.max' => 1,
            'phone.required' => 2,
            'phone.string' => 1,
            'phone.size' => 1,
            'phone.regex' => 1,
            'totalprice.required' => 2,
            'totalprice.regex' => 1,
            'taketime.required' => 2,
            'taketime.date' => 1,
            'orders.required' => 1,
            'orders.array' => 1,
            'orders.*.rid.required' => 1,
            'orders.*.rid.regex' => 2,
            'orders.*.id.required' => 2,
            'orders.*.id.regex' => 2,
            'orders.*.name.required' => 1,
            'orders.*.name.max' => 1,
            'orders.*.name.min' => 1,
            'orders.*.price.required' => 1,
            'orders.*.price.regex' => 1,
        ];

        try {
            //new            
            //如果有錯回報錯誤訊息
            $Validator = Validator::make($Request->all(), $Ruls, $RulsMessage);
            if ($Validator->fails()) {
                return response()->json(['Err' => $Validator->Errors()->first(), 'Message' => $this->Err[$Validator->Errors()->first()]]);
            }

            //取出Request內Order         
            $RequestOrder = $Request->orders;

            //訂單內餐廳是否都一致
            $AllOrderRid = array_column($RequestOrder, 'rid');
            $ReataurantIdUnique = collect($AllOrderRid)->unique()->toArray();
            $CheckSameRestaurantIdInOrders = $this->CreateOrderServiceV2->CheckSameArray($AllOrderRid, $ReataurantIdUnique);
            if (!$CheckSameRestaurantIdInOrders) {
                return response()->json(['Err' => $this->Keys[22], 'Message' => $this->Err[22]]);
            }

            //檢查Order內是否有一樣的菜單,有的話將一樣菜單合併
            $GoodOrder = $this->CreateOrderServiceV2->MergeOrdersBySameId($RequestOrder);

            //餐廳是否存在且啟用
            $Rid = $GoodOrder[0]['rid'];
            $HasRestaurant = $this->CreateOrderServiceV2->CheckRestaurantInDatabase($Rid);
            if (!$HasRestaurant) {
                return response()->json(['Err' => $this->Keys[16], 'Message' => $this->Err[16]]);
            }

            //訂單總金額是否正確
            $TotalPrice = $Request->totalprice;
            $CheckTotalPrice = $this->CreateOrderServiceV2->CheckTotalPrice($GoodOrder, $TotalPrice);
            if (!$CheckTotalPrice) {
                return response()->json(['Err' => $this->Keys[20], 'Message' => $this->Err[20]]);
            }

            //餐廳今天是否有營業
            $Today = date('l');
            $RestaurantOpen = $this->CreateOrderServiceV2->CheckRestaurantOpen($Rid, $Today);
            if (!$RestaurantOpen) {
                return response()->json(['Err' => $this->Keys[17], 'Message' => $this->Err[17]]);
            }
            //檢查菜單金額名稱id是否與店家一致
            $Menucorrect = $this->CreateOrderServiceV2->Menucorrect($Rid, $RequestOrder);
            if (!$Menucorrect) {
                return response()->json(['Err' => $this->Keys[30], 'Message' => $this->Err[30]]);
            }

            // 餐點是否停用
            $AllMenuId = array_column($GoodOrder, 'id');
            $MenuEnable = $this->CreateOrderServiceV2->Menuenable($AllMenuId);
            if (!$MenuEnable) {
                return response()->json(['Err' => $this->Keys[25], 'Message' => $this->Err[25]]);
            }



            // //如果選擇本地付款錢包餘額是否大於totoprice
            // $UserInfo = $this->TotalService->GetUserInfo();
            // $UserId = $UserInfo->id;
            // $UserWalletInfo = $this->UserWallerService->GetWallet($UserId);
            // $UserWalletBalance = $UserWalletInfo['balance'];
            // if ($Request->payment == 'local' && $TotalPrice > $UserWalletBalance) {
            //     return response()->json(['Err' => $this->Keys[18], 'Message' => $this->Err[18]]);
            // }


            // new
            //如果非本地廠商需打Api傳送訂單        
            if ($Rid !== 4) {
                $OrderInfo = ['name' => $Request->name, 'phone' => $Request->phone, 'taketime' => $Request->taketime, 'totalprice' => $Request->totalprice];
                //傳送訂單Api給餐廳
                $Response = $this->CreateOrderServiceV2->SendApi($OrderInfo, $RequestOrder);
                if ($Response) {
                    //if payment = ecpay
//ecpay save
//ecpayback save


                    //if payment = local
                    //userwallet save

                    //else
                    //walletrecoed save

                }
                return response()->json(['Err' => $this->Keys[34], 'Message' => $this->Err[34]]);
            }
            //order save
//orderinfo save
            return 1;






            //轉換店家要求api格式
            // $AlreadyData = $Restaurant->Change($Request, $Order);
            // if (!$AlreadyData) {
            //     return response()->json(['Err' => $this->Keys[26], 'Message' => $this->Err[26]]);
            // }
            // //     //傳送訂單至餐廳Api
            // $Sendapi = $Restaurant->Sendapi($AlreadyData);

            // //將訂單存入資料庫
            // $Now = now();
            // $Taketime = $Request->taketime;
            // $Address = $Request->address;
            // $Phone = $Request->phone;
            // $Orderinfo = [
            //     'ordertime' => $Now,
            //     'taketime' => $Taketime,
            //     'total' => $TotalPrice,
            //     'phone' => $Phone,
            //     'address' => $Address,
            //     'status' => '付款中',
            //     'rid' => $Rid,
            //     'uid' => $UserId
            // ];
            // $Oid = $this->OrderService->AddOrder($Orderinfo);

            // $ItemName = collect($Order)->pluck('name')->implode(',');





            // //將OrderInfoInfo存入資料庫
            // $OrderInfoInfo = array_map(function ($item) use ($Oid) {
            //     if (isset($item['description'])) {
            //         return ['description' => $item['description'], 'oid' => $Oid, 'name' => $item['name'], 'price' => $item['price'], 'quanlity' => $item['quanlity'], 'created_at' => now(), 'updated_at' => now()];
            //     }
            //     return ['description' => null, 'oid' => $Oid, 'name' => $item['name'], 'price' => $item['price'], 'quanlity' => $item['quanlity'], 'created_at' => now(), 'updated_at' => now()];
            // }, $Order);
            // $AddOrderInfoInfo = $this->OrderInfoService->AddOrderInfo($OrderInfoInfo);
            // return $Order;
//new







            //如果選擇Ecpay且是外面廠商
            $Hasapi = Restaurant::where('id', '=', $Rid)->where('api', '!=', null)->where('api', '!=', '')->count();

            if ($Hasapi != 0) {
                //轉換店家要求api格式           
                $changedata = $Restaurant->Change($Request, $GoodOrder);
                if ($changedata === false) {
                    return response()->json(['Err' => $this->Err['1']]);
                }
                $Sendapi = $Restaurant->Sendapi($changedata);
                $usertoken = JWTAuth::parseToken()->authenticate();
                $user = User::find($UserId);
                $userid = $user->id;
                $now = Carbon::now();
                $taketime = $Request->taketime;
            }


            $user = User::find($UserId);
            $wallet = $user->wallet()->get();
            $now = Carbon::now();
            $taketime = $Request->taketime;
            $address = $Request->address;
            $phone = $Request->phone;
            // 存入order資料庫
            $OrderR = new Order([
                'ordertime' => $now,
                'taketime' => $taketime,
                'total' => $TotalPrice,
                'phone' => $phone,
                'address' => $address,
                'status' => '付款中',
                'rid' => $Rid,
            ]);

            $user->order()->save($OrderR);

            // 存入orderiofo資料庫
            $orderinfo = new Order_info();
            $reqorder = $Request['orders'];
            $item_name = '';

            $sameorder = [];
            foreach ($GoodOrder as $a) {
                $item_name = $a['name'] . '#' . $item_name;
                array_push($sameorder, $a['id']);

                if (isset($a['description'])) {
                    $orderinfo = new Order_info([
                        'price' => $a['price'],
                        'name' => $a['name'],
                        'description' => $a['description'],
                        'quanlity' => $a['quanlity']
                    ]);
                    $OrderR->orderinfo()->save($orderinfo);
                } else {
                    $orderinfo = new Order_info([
                        'price' => $a['price'],
                        'name' => $a['name'],
                        'quanlity' => $a['quanlity'],
                    ]);
                    $OrderR->orderinfo()->save($orderinfo);
                }
            }


            $uid = (string) Str::uuid();

            $uuid20Char = substr($uid, 0, 20);

            if ($Request->payment == 'local') {
                try {
                    //將user錢包扣款

                    $wallet[0]->balance -= $TotalPrice;
                    $user->wallet()->saveMany($wallet);

                    // 存入wallet record
                    $wrecord = new Wallet_Record([
                        'out' => $TotalPrice,
                        'uid' => $UserId,
                        'status' => '成功',
                        'pid' => $this->Payment[$Request->payment],
                    ]);
                    $OrderR->record()->save($wrecord);
                    $OrderR->status = '成功';
                    $OrderR->save();

                    return response()->json(['Err' => $this->Err['0'], 'oid' => $OrderR->id]);
                } catch (Throwable $e) {
                    $wrecord->status = '失敗';
                    $wrecord->save();
                    $OrderR->status = '失敗';
                    $OrderR->save();
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
                "amount" => $TotalPrice,
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
                'out' => $TotalPrice,
                'uid' => $userid,
                'eid' => $uuid20Char,
                'status' => '交易中',
                'pid' => $this->Payment[$Request->payment],
            ]);
            $OrderR->record()->save($wrecord);

            //是否有api  是否響應成功
            if ($Hasapi != 0) {
                $Errcode = $Restaurant->GetErr($Sendapi);
                if ($Errcode = false) {
                    $wrecord->status = '失敗';
                    $wrecord->save();
                    $OrderR->status = '失敗';
                    $OrderR->save();
                    return '訂單失敗';
                }
            }

            //將資訊傳至第三方付款資訊
            $CheckMacValueService = new CheckMacValueService($key, $iv);
            $CheckMacValue = $CheckMacValueService->generate($data);
            $data['check_mac_value'] = $CheckMacValue;
            $client = new Client();
            $res = $client->Request('POST', 'http://neil.xincity.xyz:9997/api/Cashier/AioCheckOut', ['json' => $data]);
            $goodres = $res->getBody();
            $s = json_decode($goodres);
            return $s;



        } catch (Exception $e) {
            return response()->json([$e, 'Err' => $this->Err['26']]);
        } catch (Throwable $e) {
            return response()->json([$e, 'Err' => $this->Err['26']]);
        }
    }


    public function qwe(Request $Request)
    {

        try {
            $RequestData = $Request->all();
            Cache::set($Request->merchant_trade_no, $RequestData);
            $trade_date = Carbon::createFromFormat('d/M/y H:m:s', $Request->trade_date);
            $payment_date = Carbon::createFromFormat('d/M/y H:m:s', $Request->payment_date);
            $ecpay1 = Ecpay::find($RequestData['merchant_trade_no']);

            $ecpayback = new Ecpay_back([
                'merchant_id' => $Request->merchant_id,
                'trade_date' => $trade_date,
                'check_mac_value' => $Request->check_mac_value,
                'rtn_code' => $Request->rtn_code,
                'rtn_msg' => $Request->rtn_msg,
                'amount' => $Request->amount,
                'payment_date' => $payment_date,
            ]);
            $ecpay1->ecpayback()->save($ecpayback);


            if ($Request->rtn_code == 1) {
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
            return response()->json([$e, 'Err' => $this->Err['26']]);
        }
    }


    public function order(Request $Request)
    {
        //規則
        $ruls = [
            'limit' => ['regex:/^[0-9]+$/'],
            'offset' => ['regex:/^[0-9]+$/'],
            'rid' => ['regex:/^[0-9]+$/'],
        ];
        //什麼錯誤報什麼錯誤訊息
        $rulsMessage = [
            'limit.regex' => $this->Err['23'],
            'offset.regex' => $this->Err['23'],
            'rid.regex' => $this->Err['23'],
            'rid.required' => $this->Err['2'],
        ];

        try {
            //驗證參輸入數
            $validator = Validator::make($Request->all(), $ruls, $rulsMessage);
            if ($validator->fails()) {
                return response()->json(['Err' => $validator->Errors()->first()]);
            }

            $userinfo = JWTAuth::parseToken()->authenticate();
            $user = User::find($userinfo->id);
            //是否有填入oid
            if ($Request->oid != null) {
                $oid = $Request->oid;
                $order = $user->order()->select('id', 'ordertime', 'taketime', 'total', 'status')->where('id', '=', $oid)->get();
                $count = $user->order()->where('id', '=', $oid)->count();
                if ($count == 0) {
                    return response()->json(['Err' => $this->Err['19']]);
                }
                return response()->json(['Err' => $this->Err['0'], 'order' => $order[0]]);
            }

            if ($Request->limit == null) {
                $limit = '20';
            } else {
                $limit = $Request->limit;
            }
            if ($Request->offset === null) {
                $offset = '0';
            } else {
                $offset = $Request->offset;
            }
            $order = $user->order()->select('id', 'ordertime', 'taketime', 'total', 'status')->limit($limit)->offset($offset)->orderBy('ordertime', 'desc')->get();
            $count = $user->order()->select('id', 'ordertime', 'taketime', 'total', 'status')->count();
            return response()->json(['Err' => $this->Err['0'], 'count' => $count, 'order' => $order]);
        } catch (Throwable $e) {
            return response()->json([$e, 'Err' => $this->Err['26']]);
        }
    }
    public function orderinfo(Request $Request)
    {
        //規則
        $ruls = [
            'oid' => ['regex:/^[0-9]+$/'],
        ];
        //什麼錯誤報什麼錯誤訊息
        $rulsMessage = [
            'oid.regex' => $this->Err['1']
        ];

        try {
            //驗證參輸入數
            $validator = Validator::make($Request->all(), $ruls, $rulsMessage);
            if ($validator->fails()) {
                return response()->json(['Err' => $validator->Errors()->first()]);
            }
            $oid = $Request->oid;
            $orderinfno = Order_info::select('name', 'quanlity', 'price', 'description')->where('oid', '=', $oid)->get();
            $count = Order_info::select('id', 'name', 'quanlity', 'price', 'description')->where('oid', '=', $oid)->count();
            if ($count === 0) {
                return response()->json(['Err' => $this->Err['19']]);
            }
            return response()->json(['Err' => $this->Err['0'], 'ordersinfo' => $orderinfno]);
        } catch (Throwable $e) {
            return response()->json([$e, 'Err' => $this->Err['26']]);
        }
    }
    public function AddWalletMoney(Request $Request)
    {
        //規則
        $ruls = [
            'money' => ['regex:/^[0-9]+$/', 'required'],
        ];
        //什麼錯誤報什麼錯誤訊息
        $rulsMessage = [
            'money.regex' => $this->Err['23'],
            'money.required' => $this->Err['2']
        ];

        try {
            //驗證參輸入數
            $validator = Validator::make($Request->all(), $ruls, $rulsMessage);
            if ($validator->fails()) {
                return response()->json(['Err' => $validator->Errors()->first()]);
            }
            $Err = new ErrorCodeService;
            $Errr = $this->Err;
            $token = $Request->header('Authorization');
            //取得UserId
            $UserInfo = $this->TotalService->GetUserInfo();
            $UserId = $UserInfo['id'];
            //送api至Ecpay
            $EcpayService = new EcpayService();
            $Uuid = $EcpayService->GetUuid();
            $Date = $EcpayService->GetDate();
            $Money = $Request['money'];
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
                'pid' => 2,
            ];
            //將資料存入WalletRecord
            $DatabaseService->SaveRecord($WalletRecord);
            //將資料傳至Ecapy
            $Response = $EcpayService->SendApi($Data);
            return $Response;
        } catch (Throwable $e) {
            return response()->json(['Err' => array_search('系統錯誤', $Errr), 'Message' => $Errr['26']]);
        }
    }
    public function moneycallback(Request $Request)
    {
        try {
            $Err = new ErrorCodeService;
            $Errr = $this->Err;
            //取得交易編號
            $merchant_trade_no = $Request->merchant_trade_no;
            $TradeDate = Carbon::createFromFormat('d/M/y H:m:s', $Request->trade_date);
            $PaymentDate = Carbon::createFromFormat('d/M/y H:m:s', $Request->payment_date);
            //取的該交易編號Ecpay Collection
            $DatabaseService = new DatabaseService();
            $Ecpay = $DatabaseService->GetEcpayCollection($merchant_trade_no);
            //將EcapyCallBack存至Ecpay關聯資料庫
            $Ecpayback = new Ecpay_back([
                'merchant_id' => $Request->merchant_id,
                'trade_date' => $TradeDate,
                'check_mac_value' => $Request->check_mac_value,
                'rtn_code' => $Request->rtn_code,
                'rtn_msg' => $Request->rtn_msg,
                'amount' => $Request->amount,
                'payment_date' => $PaymentDate,
            ]);
            $DatabaseService->SaveEcpayCallBack($Ecpay, $Ecpayback);
            //如果EcpayCallBack回傳的rtn_code為1
            if ($Request->rtn_code == 1) {
                //取得此Ecpay關聯的WalletRecord
                $Record = $DatabaseService->GetRecordCollenction($Ecpay);
                //將關聯walletRecord status改成0
                $Record[0]->status = '成功';
                //將更新後WalletRecord儲存
                $DatabaseService->SaveEcpayRecord($Ecpay, $Record);
                $Money = $Request->amount;
                //取得UserWallet  
                $UserId = $this->WalletRecordService->GetUserId($merchant_trade_no)[0]['uid'];
                //儲值金額至錢包
                $this->UserWallerService->AddWalletMoney($Money, $UserId);
            } else {
                $Record = $DatabaseService->GetRecordCollenction($Ecpay);
                $Record[0]->status = '失敗';
                $DatabaseService->SaveEcpayRecord($Ecpay, $Record);
            }
        } catch (Exception $e) {
            Cache::set('apple', $e);
            return $e;
        } catch (Throwable $e) {
            Cache::set('apple', $e);
            return response()->json([$e, 'Err' => array_search('系統錯誤', $Errr), 'Message' => $Errr['26']]);
        }
    }
    public function wallet(Request $Request)
    {
        //取得總ErrCode
        $Err = new ErrorCodeService;
        $Errr = $this->Err;
        //規則
        $ruls = [
            'limit' => ['regex:/^[0-9]+$/'],
            'offset' => ['regex:/^[0-9]+$/'],
        ];
        //什麼錯誤報什麼錯誤訊息
        $rulsMessage = [
            'limit.regex' => array_search('無效的範圍', $Errr),
            'offset.regex' => array_search('無效的範圍', $Errr),
        ];
        try {
            //驗證參輸入數
            $validator = Validator::make($Request->all(), $ruls, $rulsMessage);
            if ($validator->fails()) {
                return response()->json(['Err' => $validator->Errors()->first(), 'Message' => $Errr[$validator->Errors()->first()]]);
            }

            //取的抓取範圍&類型
            $Range = ['offset' => $Request->offset, 'limit' => $Request->limit];
            $Type = $Request->type;
            //取得該使用者Service
            $token = $Request->header('Authorization');
            //取得該使用者WalletRecord
            $WalletRecord = $this->UserService->GetUserWallet($Range, $Type);
            return response()->json(['Err' => array_search('成功', $Errr), 'Message' => $Errr[0], 'count' => $WalletRecord['count'], 'data' => $WalletRecord['wallet']]);
        } catch (Exception $e) {
            return response()->json([$e, 'Err' => array_search('系統錯誤', $Errr), 'Message' => $Errr[26]]);
        }

    }

    public function apple()
    {
        DB::enableQueryLog();
        $user = User::all();
        $apple = $user->where('id', '=', '1');
        $queryLog = DB::getQueryLog();
        dd($queryLog);

    }
}
