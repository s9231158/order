<?php

namespace App\Http\Controllers;

use App\ErrorCodeService;
use App\Models\Ecpay;
use App\Models\Order;
use App\Models\Order_info;
use App\Models\User;
use App\RepositoryV2\UserRepositoryV2;
use App\Service\OrderInfoService;
use App\Service\OrderService;
use App\Service\RestaurantService;
use App\Service\UserWallerService;
use App\Service\WalletRecordService;
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
    private $UserRepositoryV2;
    public function __construct(UserRepositoryV2 $UserRepositoryV2, CreateOrderServiceV2 $CreateOrderServiceV2, OrderInfoService $OrderInfoService, RestaurantService $RestaurantService, ErrorCodeService $ErrorCodeService, WalletRecordService $WalletRecordService, OrderService $OrderService, UserService $UserService, TotalService $TotalService, UserWallerService $UserWallerService)
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
        $this->UserRepositoryV2 = $UserRepositoryV2;
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

            // new
            $Money = $Request->totalprice;
            $Now = now();
            $Taketime = $Request->taketime;
            $Address = $Request->address;
            $Phone = $Request->phone;
            if ($Rid !== 4) {
                $OrderInfo = ['name' => $Request->name, 'phone' => $Request->phone, 'taketime' => $Request->taketime, 'totalprice' => $Request->totalprice];
                //如果非本地廠商需打Api傳送訂單      
                $Response = $this->CreateOrderServiceV2->SendApi($OrderInfo, $RequestOrder);
                if ($Response) {
                    //訂單傳送成功將訂單存至資料庫
                    $SaveOrderInfo = [
                        'ordertime' => $Now,
                        'taketime' => $Taketime,
                        'total' => $TotalPrice,
                        'phone' => $Phone,
                        'address' => $Address,
                        'status' => '付款中',
                        'rid' => $Rid,
                    ];
                    $Oid = $this->CreateOrderServiceV2->SaveOrder($SaveOrderInfo);
                    //儲存訂單詳情
                    $this->CreateOrderServiceV2->SaveOrderInfo($RequestOrder, $Oid);
                } else {
                    //訂單傳送失敗 將失敗訂單存置資料庫
                    $SaveOrderInfo = [
                        'ordertime' => $Now,
                        'taketime' => $Taketime,
                        'total' => $TotalPrice,
                        'phone' => $Phone,
                        'address' => $Address,
                        'status' => '0',
                        'rid' => $Rid,
                    ];
                    $this->CreateOrderServiceV2->SaveOrder($SaveOrderInfo);
                    return response()->json(['Err' => $this->Keys[34], 'Message' => $this->Err[34]]);
                }

            } else {
                $OrderInfo = ['name' => $Request->name, 'phone' => $Request->phone, 'taketime' => $Request->taketime, 'totalprice' => $Request->totalprice];
                $SaveOrderInfo = [
                    'ordertime' => $Now,
                    'taketime' => $Taketime,
                    'total' => $TotalPrice,
                    'phone' => $Phone,
                    'address' => $Address,
                    'status' => '付款中',
                    'rid' => $Rid,
                ];
                $Oid = $this->CreateOrderServiceV2->SaveOrder($SaveOrderInfo);
                //儲存訂單詳情
                $this->CreateOrderServiceV2->SaveOrderInfo($RequestOrder, $Oid);
            }

            //如果是本地付款
            if ($Request->payment === 'local') {
                //檢查User錢包是否足夠付款
                $Money = $Request->totalprice;
                $CheckWalletMoney = $this->CreateOrderServiceV2->CheckWalletMoney($Money);
                if ($CheckWalletMoney) {
                    return response()->json(['Err' => $this->Keys[18], 'Message' => $this->Err[18]]);
                }
                //將user錢包扣款
                $this->CreateOrderServiceV2->DeductMoney($Money);
                // 存入wallet record
                $WalletRecordInfo = ['oid' => $Oid, 'out' => $Money, 'status' => '付款中', 'pid' => $this->Payment[$Request->payment]];
                $this->CreateOrderServiceV2->SaveWalletRecord($WalletRecordInfo);
            }
            //如果是金流付款
            if ($Request->payment === 'ecpay') {
                //將Ecpay資料存置資料庫
                $Uuid = substr(Str::uuid(), 0, 20);
                $Date = Carbon::now()->format('Y/m/d H:i:s');
                $AllOrderMenuName = array_column($RequestOrder, 'name');
                $ItemString = implode(",", $AllOrderMenuName);
                $EcpayInfo = [
                    "merchant_id" => 11,
                    "merchant_trade_no" => $Uuid,
                    "merchant_trade_date" => $Date,
                    "payment_type" => "aio",
                    "amount" => $Money,
                    "item_name" => $ItemString,
                    "return_url" => "http://192.168.83.26:9999/api/EcpayCallBack",
                    "choose_payment" => "Credit",
                    "check_mac_value" => "6CC73080A3CF1EA1A844F1EEF96A873FA4D1DD485BDA6517696A4D8EF0EAC94E",
                    "encrypt_type" => 1,
                    "lang" => "en"
                ];
                $this->CreateOrderServiceV2->SaveEcpay($EcpayInfo);
                //發送api訂單至金流方
                $SendEcpayApi = $this->CreateOrderServiceV2->SendEcpayApi($EcpayInfo);
                $WalletRecordInfo = ['eid' => $Uuid, 'oid' => $Oid, 'out' => $Money, 'status' => '付款中', 'pid' => $this->Payment[$Request->payment]];
                return $this->CreateOrderServiceV2->SaveWalletRecord($WalletRecordInfo);
            }

        } catch (Exception $e) {
            return response()->json(['Err' => $this->Keys[26], 'Message' => $this->Err[26]]);
        } catch (Throwable $e) {
            return response()->json(['Err' => $this->Keys[26], 'Message' => $this->Err[26]]);
        }
    }


    public function EcpayCallBack(Request $Request)
    {
        try {
            $Trade_date = Carbon::createFromFormat('d/M/y H:m:s', $Request->trade_date);
            $Payment_date = Carbon::createFromFormat('d/M/y H:m:s', $Request->payment_date);
            $EcpayBackInfo = ['merchant_id' => $Request->merchant_id,
                'trade_date' => $Trade_date,
                'check_mac_value' => $Request->check_mac_value,
                'rtn_code' => $Request->rtn_code,
                'rtn_msg' => $Request->rtn_msg,
                'amount' => $Request->amount,
                'payment_date' => $Payment_date,
                'merchant_trade_no' => $Request->merchant_trade_no];
            $this->CreateOrderServiceV2->SaveEcpayBack($EcpayBackInfo);
            if ($Request->rtn_code == 0) {
                //將WalletRecord的status改為false
                $this->CreateOrderServiceV2->UpdateWalletRecordFail($Request->merchant_trade_no);
                //將order的status改為false
                $this->CreateOrderServiceV2->UpdateOrederFail($Request->merchant_trade_no);
            } else {
                $this->CreateOrderServiceV2->UpdateWalletRecordsuccess($Request->merchant_trade_no);
                $this->CreateOrderServiceV2->UpdateOredersuccess($Request->merchant_trade_no);
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
