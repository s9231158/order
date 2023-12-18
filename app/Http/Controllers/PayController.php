<?php

namespace App\Http\Controllers;

use App\ErrorCodeService;
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
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
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
                return response()->json(['name' => $Request->name, 'phone' => $Request->phone, 'taketime' => $Request->taketime, 'totalprice' => $Request->totalprice, 'orders' => $GoodOrder]);
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
                //發送api訂單至金流方
                $SendEcpayApi = $this->CreateOrderServiceV2->SendEcpayApi($EcpayInfo);
                //將發送訂單存入資料庫
                $EcpayInfo = $this->CreateOrderServiceV2->SaveEcpay($SendEcpayApi[1]);
                //將交易紀錄存進資料庫
                $WalletRecordInfo = ['eid' => $Uuid, 'oid' => $Oid, 'out' => $Money, 'status' => '付款中', 'pid' => $this->Payment[$Request->payment]];
                $this->CreateOrderServiceV2->SaveWalletRecord($WalletRecordInfo);
                if (isset($SendEcpayApi[0]->transaction_url)) {
                    return $SendEcpayApi[0];
                }
                if (!isset($SendEcpayApi[0]->transaction_url)) {
                    return response()->json(['Err' => $SendEcpayApi[0]->error_code, 'Message' => '第三方金流錯誤']);
                }
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
            return response()->json(['Err' => $this->Keys[26], 'Message' => $this->Err[26]]);
        }
    }


    public function Order(Request $Request)
    {
        //規則
        $Ruls = [
            'limit' => ['regex:/^[0-9]+$/'],
            'offset' => ['regex:/^[0-9]+$/'],
            'oid' => ['regex:/^[0-9]+$/'],
        ];
        //什麼錯誤報什麼錯誤訊息
        $RulsMessage = [
            'limit.regex' => 23,
            'offset.regex' => 23,
            'oid.regex' => 23,
        ];
        try {
            //驗證參輸入數
            $Validator = Validator::make($Request->all(), $Ruls, $RulsMessage);
            if ($Validator->fails()) {
                return response()->json(['Err' => $Validator->errors()->first(), 'Message' => $this->Err[$Validator->Errors()->first()]]);
            }
            //取得offset limit
            $OffsetLimit = ['limit' => $Request['limit'], 'offset' => $Request['offset']];
            $OffsetLimit = $this->TotalService->GetOffsetLimit($OffsetLimit);
            $Oid = $Request->oid;
            //取出訂單
            $Order = $this->CreateOrderServiceV2->GetOrder($Oid, $OffsetLimit);
            $OrderCount = $Order->count();
            return response()->json(['Err' => $this->Keys[0], 'Message' => $this->Err[0], 'count' => $OrderCount, 'order' => $Order]);
        } catch (Throwable $e) {
            return response()->json(['Err' => $this->Keys[26], 'Message' => $this->Err[26]]);
        }
    }
    public function orderinfo(Request $Request)
    {
        //規則
        $Ruls = [
            'oid' => ['regex:/^[0-9]+$/'],
        ];
        //什麼錯誤報什麼錯誤訊息
        $RulsMessage = [
            'oid.regex' => 1
        ];
        try {
            //驗證參輸入數
            $Validator = Validator::make($Request->all(), $Ruls, $RulsMessage);
            if ($Validator->fails()) {
                return response()->json(['Err' => $Validator->errors()->first(), 'Message' => $this->Err[$Validator->Errors()->first()]]);
            }
            $Oid = $Request->oid;
            $OrderInfo = $this->CreateOrderServiceV2->GetOrderInfo($Oid);
            if (!isset($OrderInfo[0])) {
                return response()->json(['Err' => $this->Keys[19], 'Message' => $this->Err[19]]);
            }
            return response()->json(['Err' => $this->Keys[0], 'Message' => $this->Err[0], 'ordersinfo' => $OrderInfo]);
        } catch (Throwable $e) {
            return response()->json(['Err' => $this->Keys[26], 'Message' => $this->Err[26]]);
        }
    }
    public function AddWalletMoney(Request $Request)
    {
        //規則
        $Ruls = [
            'money' => ['regex:/^[0-9]+$/', 'required'],
        ];
        //什麼錯誤報什麼錯誤訊息
        $RulsMessage = [
            'money.regex' => 23,
            'money.required' => 2
        ];
        try {
            //驗證參輸入數
            $Validator = Validator::make($Request->all(), $Ruls, $RulsMessage);
            if ($Validator->fails()) {
                return response()->json(['Err' => $Validator->errors()->first(), 'Message' => $this->Err[$Validator->Errors()->first()]]);
            }
            $Uuid = substr(Str::uuid(), 0, 20);
            $Date = Carbon::now()->format('Y/m/d H:i:s');
            $Money = $Request['money'];
            $EcpayInfo = [
                "merchant_id" => 11,
                "merchant_trade_no" => $Uuid,
                "merchant_trade_date" => $Date,
                "payment_type" => "aio",
                "amount" => $Money,
                "item_name" => '加值',
                "return_url" => "http://192.168.83.26:9999/api/moneycallback",
                "choose_payment" => "Credit",
                "encrypt_type" => 1,
                "lang" => "en"
            ];
            $SendEcpayApi = $this->CreateOrderServiceV2->SendEcpayApi($EcpayInfo);
            $EcpayInfo = $this->CreateOrderServiceV2->SaveEcpay($SendEcpayApi[1]);
            $WalletRecordInfo = ['eid' => $Uuid, 'in' => $Money, 'status' => '付款中', 'pid' => 2];
            $this->CreateOrderServiceV2->SaveWalletRecord($WalletRecordInfo);
            if (isset($SendEcpayApi[0]->transaction_url)) {
                return $SendEcpayApi[0];
            }
            if (!isset($SendEcpayApi[0]->transaction_url)) {
                return response()->json(['Err' => $SendEcpayApi[0]->error_code, 'Message' => '第三方金流錯誤']);
            }
        } catch (Throwable $e) {
            return response()->json(['Err' => $this->Keys[26], 'Message' => $this->Err[26]]);
        }
    }
    public function moneycallback(Request $Request)
    {
        try {
            $Money = $Request->amount;
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
            } else {
                $yesterday = date('Y-m-d H:i:s', strtotime('-1 day'));
                $today = date('Y-m-d H:i:s');
                $Option = ['Eid' => $Request->merchant_trade_no, 'StartTime' => $yesterday, 'EndTime' => $today];
                $this->CreateOrderServiceV2->AddMoney($Money, $Option);
                $this->CreateOrderServiceV2->UpdateWalletRecordsuccess($Request->merchant_trade_no);
            }
        } catch (Exception $e) {
            Cache::set('AddWalletErrMessage' . $today, $e);
        } catch (Throwable $e) {
            Cache::set('AddWalletErrMessage' . $today, $e);
        }
    }
    public function wallet(Request $Request)
    {
        //規則
        $ruls = [
            'limit' => ['regex:/^[0-9]+$/'],
            'offset' => ['regex:/^[0-9]+$/'],
        ];
        //什麼錯誤報什麼錯誤訊息
        $rulsMessage = [
            'limit.regex' => 23,
            'offset.regex' => 23,
        ];
        try {
            $validator = Validator::make($Request->all(), $ruls, $rulsMessage);
            if ($validator->fails()) {
                return response()->json(['Err' => $validator->Errors()->first(), 'Message' => $this->Err[$validator->Errors()->first()]]);
            }
            //取的抓取範圍&類型
            $OffsetLimit = ['limit' => $Request['limit'], 'offset' => $Request['offset']];
            $OffsetLimit = $this->TotalService->GetOffsetLimit($OffsetLimit);
            $Type = $Request->type;
            $GetWalletRecord = $this->CreateOrderServiceV2->GetWalletRecordOnRangeAndType($OffsetLimit, $Type);
            return response()->json(['Err' => $this->Keys[0], 'Message' => $this->Err[0], 'count' => $GetWalletRecord['count'], 'data' => $GetWalletRecord['data']]);
        } catch (Throwable $e) {
            return response()->json(['Err' => $this->Keys[26], 'Message' => $this->Err[26], 'OtherErr' => $e->getMessage()]);
        }
    }
}