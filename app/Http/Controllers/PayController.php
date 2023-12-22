<?php

namespace App\Http\Controllers;

use App\ErrorCodeService;
use App\TotalService;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Cache;
use Throwable;
use App\ServiceV2\CreateOrderServiceV2;

class PayController extends Controller
{
    private $TotalService;
    private $ErrorCodeService;
    private $Payment = [
        'ecpay' => 2,
        'local' => 1,
    ];
    private $Err;
    private $Keys;
    private $CreateOrderServiceV2;
    public function __construct(
        CreateOrderServiceV2 $CreateOrderServiceV2,
        ErrorCodeService $ErrorCodeService,
        TotalService $TotalService
    ) {
        $this->CreateOrderServiceV2 = $CreateOrderServiceV2;
        $this->ErrorCodeService = $ErrorCodeService;
        $this->TotalService = $TotalService;
        $this->Err = $ErrorCodeService->GetErrCode();
        $this->Keys = $ErrorCodeService->GetErrKey();
    }
    public function CreateOrder(Request $Request)
    {
        //規則
        $Ruls = [
            'payment' => ['required', 'string', 'in:ecpay,local'],
            'name' => ['required', 'string', 'max:25', 'min:3'],
            'address' => ['required', 'string', 'min:10', 'max:25'],
            'phone' => ['required', 'string', 'digits_between:1,9'],
            'total_price' => ['required', 'numeric', 'min:0'],
            'take_time' => ['required', 'date'],
            'orders' => ['required', 'array'],
            'orders.*.rid' => ['required', 'integer'],
            'orders.*.id' => ['required', 'integer'],
            'orders.*.name' => ['required', 'string', 'max:25', 'min:1'],
            'orders.*.price' => ['required', 'integer'],
        ];
        //什麼錯誤報什麼錯誤訊息
        $RulsMessage = [
            'payment.required' => '資料填寫與規格不符',
            'payment.string' => '資料填寫與規格不符',
            'payment.in' => '請填入正確付款方式',
            'name.required' => '必填資料未填',
            'name.max' => '資料填寫與規格不符',
            'name.min' => '資料填寫與規格不符',
            'name.string' => '資料填寫與規格不符',
            'address.required' => '必填資料未填',
            'address.min' => '資料填寫與規格不符',
            'address.max' => '資料填寫與規格不符',
            'address.string' => '資料填寫與規格不符',
            'phone.required' => '必填資料未填',
            'phone.string' => '資料填寫與規格不符',
            'phone.digits_between' => '資料填寫與規格不符',
            'total_price.required' => '必填資料未填',
            'total_price.numeric' => '資料填寫與規格不符',
            'total_price.min' => '資料填寫與規格不符',
            'take_time.required' => '必填資料未填',
            'take_time.date' => '資料填寫與規格不符',
            'orders.required' => '資料填寫與規格不符',
            'orders.array' => '資料填寫與規格不符',
            'orders.*.rid.required' => '資料填寫與規格不符',
            'orders.*.rid.integer' => '必填資料未填',
            'orders.*.id.required' => '必填資料未填',
            'orders.*.id.integer' => '必填資料未填',
            'orders.*.name.required' => '資料填寫與規格不符',
            'orders.*.name.max' => '資料填寫與規格不符',
            'orders.*.name.min' => '資料填寫與規格不符',
            'orders.*.name.string' => '資料填寫與規格不符',
            'orders.*.price.required' => '資料填寫與規格不符',
            'orders.*.price.integer' => '資料填寫與規格不符',
        ];

        try {
            //如果有錯回報錯誤訊息
            $Validator = Validator::make($Request->all(), $Ruls, $RulsMessage);
            if ($Validator->fails()) {
                return response()->json([
                    'Err' => array_search($Validator->Errors()->first(), $this->Err),
                    'Message' => $Validator->Errors()->first()
                ]);
            }

            //取出Request內Order         
            $RequestOrder = $Request->orders;

            //訂單內餐廳是否都一致
            $AllOrderRid = array_column($RequestOrder, 'rid');
            $ReataurantIdUnique = collect($AllOrderRid)->unique()->toArray();
            $CheckSameRestaurantIdInOrders = $this->CreateOrderServiceV2->CheckSameArray($AllOrderRid, $ReataurantIdUnique);
            if (!$CheckSameRestaurantIdInOrders) {
                return response()->json([
                    'Err' => $this->Keys[22],
                    'Message' => $this->Err[22]
                ]);
            }

            //檢查Order內是否有一樣的菜單,有的話將一樣的菜單合併
            $AllOrderMealId = array_column($RequestOrder, 'id');
            $MealIdUnique = collect($AllOrderMealId)->unique()->toArray();
            $CheckSameMenuIdInOrders = $this->CreateOrderServiceV2->CheckSameArray($AllOrderMealId, $MealIdUnique);
            if ($CheckSameMenuIdInOrders) {
                $RequestOrder = $this->CreateOrderServiceV2->MergeOrdersBySameId($RequestOrder);
            }

            //餐廳是否存在且啟用
            $Rid = $RequestOrder[0]['rid'];
            $HasRestaurant = $this->CreateOrderServiceV2->CheckRestaurantInDatabase($Rid);
            if (!$HasRestaurant) {
                return response()->json([
                    'Err' => $this->Keys[16],
                    'Message' => $this->Err[16]
                ]);
            }

            //訂單總金額是否正確
            $TotalPrice = $Request->total_price;
            $CheckTotalPrice = $this->CreateOrderServiceV2->CheckTotalPrice($RequestOrder, $TotalPrice);
            if (!$CheckTotalPrice) {
                return response()->json([
                    'Err' => $this->Keys[20],
                    'Message' => $this->Err[20]
                ]);
            }

            //餐廳今天是否有營業
            $Today = date('l');
            $RestaurantOpen = $this->CreateOrderServiceV2->CheckRestaurantOpen($Rid, $Today);
            if (!$RestaurantOpen) {
                return response()->json([
                    'Err' => $this->Keys[17],
                    'Message' => $this->Err[17]
                ]);
            }

            //檢查菜單金額名稱id是否與店家一致
            $MenuCorrect = $this->CreateOrderServiceV2->MenuCorrect($Rid, $RequestOrder);
            if (!$MenuCorrect) {
                return response()->json([
                    'Err' => $this->Keys[30],
                    'Message' => $this->Err[30]
                ]);
            }

            // 餐點是否停用
            $AllMenuId = array_column($RequestOrder, 'id');
            $MenuEnable = $this->CreateOrderServiceV2->MenuEnable($AllMenuId);
            if (!$MenuEnable) {
                return response()->json([
                    'Err' => $this->Keys[25],
                    'Message' => $this->Err[25]
                ]);
            }

            $Money = $Request->total_price;
            $Now = now();
            $Taketime = $Request->take_time;
            $Address = $Request->address;
            $Phone = $Request->phone;

            if ($Rid !== 4) {
                $OrderInfo = [
                    'name' => $Request->name,
                    'phone' => $Request->phone,
                    'taketime' => $Request->take_time,
                    'total_price' => $Request->total_price
                ];
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
                        'status' => 11,
                        'rid' => $Rid,
                    ];
                    //儲存訂單
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
                        'status' => 10,
                        'rid' => $Rid,
                    ];
                    //儲存訂單
                    $this->CreateOrderServiceV2->SaveOrder($SaveOrderInfo);
                    return response()->json([
                        'Err' => $this->Keys[34],
                        'Message' => $this->Err[34]
                    ]);
                }
            } else {
                //本地餐廳
                $OrderInfo = [
                    'name' => $Request->name,
                    'phone' => $Request->phone,
                    'taketime' => $Request->take_time,
                    'total_price' => $Request->total_price
                ];
                $SaveOrderInfo = [
                    'ordertime' => $Now,
                    'taketime' => $Taketime,
                    'total' => $TotalPrice,
                    'phone' => $Phone,
                    'address' => $Address,
                    'status' => 11,
                    'rid' => $Rid,
                ];
                //儲存訂單
                $Oid = $this->CreateOrderServiceV2->SaveOrder($SaveOrderInfo);
                //儲存訂單詳情
                $this->CreateOrderServiceV2->SaveOrderInfo($RequestOrder, $Oid);
            }

            //如果是本地付款
            if ($Request->payment === 'local') {
                //檢查User錢包是否足夠付款
                $Money = $Request->total_price;
                $CheckWalletMoney = $this->CreateOrderServiceV2->CheckWalletMoney($Money);
                if ($CheckWalletMoney) {
                    return response()->json([
                        'Err' => $this->Keys[18],
                        'Message' => $this->Err[18]
                    ]);
                }
                //將user錢包扣款
                $this->CreateOrderServiceV2->DeductMoney($Money);
                // 存入wallet record
                $WalletRecordInfo = [
                    'oid' => $Oid,
                    'out' => $Money,
                    'status' => 0,
                    'pid' => $this->Payment[$Request->payment]
                ];
                $this->CreateOrderServiceV2->SaveWalletRecord($WalletRecordInfo);
                return response()->json([
                    'name' => $Request->name,
                    'phone' => $Request->phone,
                    'take_time' => $Request->take_time,
                    'total_price' => $Request->total_price,
                    'orders' => $RequestOrder
                ]);
            }
            //如果是金流付款
            if ($Request->payment === 'ecpay') {
                //將Ecpay資料存置資料庫
                $Uuid = substr(Str::uuid(), 0, 20);
                $Date = Carbon::now()->format('Y/m/d H:i:s');
                $AllOrderMenuName = array_column($RequestOrder, 'name');
                $Itemstring = implode(",", $AllOrderMenuName);
                $EcpayInfo = [
                    "merchant_trade_no" => $Uuid,
                    "merchant_trade_date" => $Date,
                    "amount" => $Money,
                    "item_name" => $Itemstring,
                ];
                //發送api訂單至金流方
                $SendEcpayApi = $this->CreateOrderServiceV2->SendEcpayApi($EcpayInfo);
                //將發送訂單存入資料庫
                $EcpayInfo = $this->CreateOrderServiceV2->SaveEcpay($SendEcpayApi[1]);
                //將交易紀錄存進資料庫
                $WalletRecordInfo = [
                    'eid' => $Uuid,
                    'oid' => $Oid,
                    'out' => $Money,
                    'status' => 11,
                    'pid' => $this->Payment[$Request->payment
                    ]];
                $this->CreateOrderServiceV2->SaveWalletRecord($WalletRecordInfo);
                if (isset($SendEcpayApi[0]->transaction_url)) {
                    return $SendEcpayApi[0];
                }
                if (!isset($SendEcpayApi[0]->transaction_url)) {
                    return response()->json([
                        'Err' => $SendEcpayApi[0]->error_code,
                        'Message' => '第三方金流錯誤'
                    ]);
                }
            }
        } catch (Exception $e) {
            return response()->json([
                $e->getMessage(),
                'Err' => $this->Keys[26],
                'Message' => $this->Err[26],
                'OtherErr' => $e->getMessage()
            ]);
        } catch (Throwable $e) {
            return response()->json([
                $e->getMessage(),
                'Err' => $this->Keys[26],
                'Message' => $this->Err[26],
                'OtherErr' => $e->getMessage()
            ]);
        }
    }

    public function EcpayCallBack(Request $Request)
    {
        try {
            $Trade_date = Carbon::createFromFormat('d/M/y H:m:s', $Request->trade_date);
            $Payment_date = Carbon::createFromFormat('d/M/y H:m:s', $Request->payment_date);
            $EcpayBackInfo = [
                'merchant_id' => $Request->merchant_id,
                'trade_date' => $Trade_date,
                'check_mac_value' => $Request->check_mac_value,
                'rtn_code' => $Request->rtn_code,
                'rtn_msg' => $Request->rtn_msg,
                'amount' => $Request->amount,
                'payment_date' => $Payment_date,
                'merchant_trade_no' => $Request->merchant_trade_no
            ];
            $this->CreateOrderServiceV2->SaveEcpayBack($EcpayBackInfo);
            if ($Request->rtn_code == 0) {
                //將WalletRecord的status改為false
                $this->CreateOrderServiceV2->UpdateWalletRecordFail($Request->merchant_trade_no);
                //將order的status改為false
                $this->CreateOrderServiceV2->UpdateOrederFail($Request->merchant_trade_no);
            } else {
                $this->CreateOrderServiceV2->UpdateWalletRecordsuccess($Request->merchant_trade_no);
                $this->CreateOrderServiceV2->UpdateOrederSuccess($Request->merchant_trade_no);
            }
        } catch (Exception $e) {
            return response()->json([
                'Err' => $this->Keys[26],
                'Message' => $this->Err[26],
                'OtherErr' => $e->getMessage()
            ]);
        } catch (Throwable $e) {
            return response()->json([
                'Err' => $this->Keys[26],
                'Message' => $this->Err[26],
                'OtherErr' => $e->getMessage()
            ]);
        }
    }

    public function GetOrder(Request $Request)
    {
        //規則
        $Ruls = [
            'limit' => ['integer'],
            'offset' => ['integer'],
            'oid' => ['integer'],
        ];
        //什麼錯誤報什麼錯誤訊息
        $RulsMessage = [
            'limit.integer' => '無效的範圍',
            'offset.integer' => '無效的範圍',
            'oid.integer' => '無效的範圍',
        ];
        try {
            //驗證參輸入數
            $Validator = Validator::make($Request->all(), $Ruls, $RulsMessage);
            if ($Validator->fails()) {
                return response()->json([
                    'Err' => array_search($Validator->Errors()->first(), $this->Err),
                    'Message' => $Validator->Errors()->first()
                ]);
            }
            //取得offset limit
            $OffsetLimit = ['limit' => $Request['limit'], 'offset' => $Request['offset']];
            $OffsetLimit = $this->TotalService->GetOffsetLimit($OffsetLimit);
            $Oid = $Request->oid;
            //取出訂單
            $Order = $this->CreateOrderServiceV2->GetOrder($Oid, $OffsetLimit);
            $OrderCount = $Order->count();
            return response()->json([
                'Err' => $this->Keys[0],
                'Message' => $this->Err[0],
                'count' => $OrderCount,
                'order' => $Order
            ]);
        } catch (Throwable $e) {
            return response()->json([
                'Err' => $this->Keys[26],
                'Message' => $this->Err[26],
                'OtherErr' => $e->getMessage()
            ]);
        }
    }
    public function GetOrderInfo(Request $Request)
    {
        //規則
        $Ruls = [
            'oid' => ['integer']
        ];
        //什麼錯誤報什麼錯誤訊息
        $RulsMessage = [
            'oid.integer' => '資料填寫與規格不符'
        ];
        try {
            //驗證參輸入數
            $Validator = Validator::make($Request->all(), $Ruls, $RulsMessage);
            if ($Validator->fails()) {
                return response()->json(['Err' => array_search($Validator->Errors()->first(), $this->Err), 'Message' => $Validator->Errors()->first()]);
            }
            $Oid = $Request->oid;
            $OrderInfo = $this->CreateOrderServiceV2->GetOrderInfo($Oid);
            if (!isset($OrderInfo[0])) {
                return response()->json([
                    'Err' => $this->Keys[19],
                    'Message' => $this->Err[19]
                ]);
            }
            return response()->json([
                'Err' => $this->Keys[0],
                'Message' => $this->Err[0],
                'ordersinfo' => $OrderInfo
            ]);
        } catch (Throwable $e) {
            return response()->json([
                'Err' => $this->Keys[26],
                'Message' => $this->Err[26],
                'OtherErr' => $e->getMessage()
            ]);
        }
    }
    public function AddWalletMoney(Request $Request)
    {
        //規則
        $Ruls = [
            'money' => ['required', 'numeric', 'min:0'],
        ];
        //什麼錯誤報什麼錯誤訊息
        $RulsMessage = [
            'money.numeric' => '無效的範圍',
            'money.required' => '必填資料未填',
            'money.min' => '無效的範圍'
        ];
        try {
            //驗證參輸入數
            $Validator = Validator::make($Request->all(), $Ruls, $RulsMessage);
            if ($Validator->fails()) {
                return response()->json([
                    'Err' => array_search($Validator->Errors()->first(), $this->Err),
                    'Message' => $Validator->Errors()->first()
                ]);
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
                "return_url" => env('AddWalletMoneyEcpay_ReturnUrl'),
                "choose_payment" => "Credit",
                "encrypt_type" => 1,
                "lang" => "en"
            ];
            $SendEcpayApi = $this->CreateOrderServiceV2->SendEcpayApi($EcpayInfo);
            $EcpayInfo = $this->CreateOrderServiceV2->SaveEcpay($SendEcpayApi[1]);
            $WalletRecordInfo = ['eid' => $Uuid, 'in' => $Money, 'status' => 11, 'pid' => 2];
            $this->CreateOrderServiceV2->SaveWalletRecord($WalletRecordInfo);
            if (isset($SendEcpayApi[0]->transaction_url)) {
                return $SendEcpayApi[0];
            }
            if (!isset($SendEcpayApi[0]->transaction_url)) {
                return response()->json([
                    'Err' => $SendEcpayApi[0]->error_code,
                    'Message' => '第三方金流錯誤'
                ]);
            }
        } catch (Throwable $e) {
            return response()->json([
                'Err' => $this->Keys[26],
                'Message' => $this->Err[26],
                'OtherErr' => $e->getMessage()
            ]);
        }
    }
    public function AddWalletMoneyCallBack(Request $Request)
    {
        try {
            $Money = $Request->amount;
            $Trade_date = Carbon::createFromFormat('d/M/y H:m:s', $Request->trade_date);
            $Payment_date = Carbon::createFromFormat('d/M/y H:m:s', $Request->payment_date);
            $EcpayBackInfo = [
                'merchant_id' => $Request->merchant_id,
                'trade_date' => $Trade_date,
                'check_mac_value' => $Request->check_mac_value,
                'rtn_code' => $Request->rtn_code,
                'rtn_msg' => $Request->rtn_msg,
                'amount' => $Request->amount,
                'payment_date' => $Payment_date,
                'merchant_trade_no' => $Request->merchant_trade_no
            ];
            $this->CreateOrderServiceV2->SaveEcpayBack($EcpayBackInfo);
            if ($Request->rtn_code == 0) {
                //將WalletRecord的 status改為false
                $this->CreateOrderServiceV2->UpdateWalletRecordFail($Request->merchant_trade_no);
            } else {
                $yesterday = date('Y-m-d H:i:s', strtotime('-1 day'));
                $today = date('Y-m-d H:i:s');
                $Option = [
                    'Eid' => $Request->merchant_trade_no,
                    'StartTime' => $yesterday,
                    'EndTime' => $today
                ];
                $this->CreateOrderServiceV2->AddMoney($Money, $Option);
                $this->CreateOrderServiceV2->UpdateWalletRecordSuccess($Request->merchant_trade_no);
            }
        } catch (Exception $e) {
            Cache::set('moneycallback', $e);
        } catch (Throwable $e) {
            Cache::set('moneycallback', $e);
        }
    }
    public function GetWallet(Request $Request)
    {
        //規則
        $Ruls = [
            'limit' => ['integer'],
            'offset' => ['integer'],
        ];
        //什麼錯誤報什麼錯誤訊息
        $RulsMessage = [
            'limit.integer' => '無效的範圍',
            'offset.integer' => '無效的範圍',
        ];
        try {
            $Validator = Validator::make($Request->all(), $Ruls, $RulsMessage);
            if ($Validator->fails()) {
                return response()->json([
                    'Err' => array_search($Validator->Errors()->first(), $this->Err),
                    'Message' => $Validator->Errors()->first()
                ]);
            }
            //取的抓取範圍&類型
            $OffsetLimit = ['limit' => $Request['limit'], 'offset' => $Request['offset']];
            $OffsetLimit = $this->TotalService->GetOffsetLimit($OffsetLimit);
            $Type = $Request->type;
            $GetWalletRecord = $this->CreateOrderServiceV2->GetWalletRecordOnRangeAndType($OffsetLimit, $Type);
            return response()->json([
                'Err' => $this->Keys[0],
                'Message' => $this->Err[0],
                'count' => $GetWalletRecord['count'],
                'data' => $GetWalletRecord['data']]);
        } catch (Throwable $e) {
            return response()->json([
                'Err' => $this->Keys[26],
                'Message' => $this->Err[26],
                'OtherErr' => $e->getMessage()
            ]);
        }
    }
}
