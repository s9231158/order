<?php

namespace App\Http\Controllers;

use App\ErrorCodeService;
use App\RepositoryV2\User;
use App\TotalService;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Throwable;
use App\ServiceV2\CreateOrder as CreateOrderServiceV2;

class Pay extends Controller
{
    private $TotalService;
    private $Payment = [
        'ecpay' => 2,
        'local' => 1,
    ];
    private $Err;
    private $Keys;
    private $CreateOrderServiceV2;
    private $User;
    public function __construct(
        CreateOrderServiceV2 $CreateOrderServiceV2,
        ErrorCodeService $ErrorCodeService,
        TotalService $TotalService,
        User $User
    ) {
        $this->User = $User;
        $this->CreateOrderServiceV2 = $CreateOrderServiceV2;
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

            try {
                $User = GetUserInfo();
            } catch (\Throwable $e) {
                return response()->json([
                    'Err' => $this->Keys[22],
                    'Message' => $this->Err[22]
                ]);
            }

            //取出Request內Order         
            $RequestOrder = $Request['orders'];
            //訂單內餐廳是否都一致
            $AllOrderRid = array_column($RequestOrder, 'rid');
            $ReataurantIdUnique = collect($AllOrderRid)->unique()->toArray();
            $CheckSameRestaurantIdInOrders = $this->CheckSameArray($AllOrderRid, $ReataurantIdUnique);
            if (!$CheckSameRestaurantIdInOrders) {
                return response()->json([
                    'Err' => $this->Keys[22],
                    'Message' => $this->Err[22]
                ]);
            }

            //檢查Order內是否有一樣的菜單,有的話將一樣的菜單合併
            $AllOrderMealId = array_column($RequestOrder, 'id');
            $MealIdUnique = collect($AllOrderMealId)->unique()->toArray();
            $CheckSameMenuIdInOrders = $this->CheckSameArray($AllOrderMealId, $MealIdUnique);
            if ($CheckSameMenuIdInOrders) {
                $RequestOrder = $this->MergeOrdersBySameId($RequestOrder);
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
            $TotalPrice = $Request['total_price'];
            $CheckTotalPrice = $this->CheckTotalPrice($RequestOrder, $TotalPrice);
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
            $Money = $Request['total_price'];
            $Now = now();
            $TakeTime = $Request['take_time'];
            $Address = $Request['address'];
            $Phone = $Request['phone'];
            $UserInfo = $this->User->GetUserInfo();
            $UserId = $UserInfo->id;
            if ($Rid !== 4) {
                $OrderInfo = [
                    'name' => $Request['name'],
                    'phone' => $Request['phone'],
                    'taketime' => $Request['take_time'],
                    'total_price' => $Request['total_price']
                ];
                //如果非本地廠商需打Api傳送訂單      
                $Response = $this->CreateOrderServiceV2->SendApi($OrderInfo, $RequestOrder);
                if ($Response) {
                    //訂單傳送成功將訂單存至資料庫
                    $SaveOrderInfo = [
                        'ordertime' => $Now,
                        'taketime' => $TakeTime,
                        'total' => $TotalPrice,
                        'phone' => $Phone,
                        'address' => $Address,
                        'status' => 0,
                        'rid' => $Rid,
                        'uid' => $UserId,
                    ];
                    //儲存訂單
                    $Oid = $this->CreateOrderServiceV2->SaveOrder($SaveOrderInfo);
                    //整理訂單詳情資料
                    $CreateOrderInfo = $this->FixOrderInfo($RequestOrder, $Oid);
                    //儲存訂單詳情
                    $this->CreateOrderServiceV2->SaveOrderInfo($CreateOrderInfo);
                } else {
                    //訂單傳送失敗 將失敗訂單存置資料庫
                    $SaveOrderInfo = [
                        'ordertime' => $Now,
                        'taketime' => $TakeTime,
                        'total' => $TotalPrice,
                        'phone' => $Phone,
                        'address' => $Address,
                        'status' => 10, //變常數 寫近service 額外一個errstatusFile
                        'rid' => $Rid,
                        'uid' => $UserId,
                    ];
                    //儲存訂單
                    $this->CreateOrderServiceV2->SaveOrder($SaveOrderInfo);
                    return response()->json([
                        'Err' => $this->Keys[34],
                        'Message' => $this->Err[34]
                    ]);

                }
            } else {
                //本地餐廳訂單
                $SaveOrderInfo = [
                    'ordertime' => $Now,
                    'taketime' => $TakeTime,
                    'total' => $TotalPrice,
                    'phone' => $Phone,
                    'address' => $Address,
                    'status' => 0,
                    'rid' => $Rid,
                    'uid' => $UserId,
                ];
                //儲存訂單
                $Oid = $this->CreateOrderServiceV2->SaveOrder($SaveOrderInfo);
                //整理本地訂單詳情資料
                $CreateOrderInfo = $this->FixOrderInfo($RequestOrder, $Oid);
                //儲存訂單詳情
                $this->CreateOrderServiceV2->SaveOrderInfo($CreateOrderInfo);
            }

            //如果是本地付款
            if ($Request['payment'] === 'local') {
                //檢查User錢包是否足夠付款
                $Money = $Request['total_price'];
                $WalletMoney = $this->CreateOrderServiceV2->GetWalletMoney($UserId, $Money);
                if ($WalletMoney->balance < $Money) {
                    return response()->json([
                        'Err' => $this->Keys[18],
                        'Message' => $this->Err[18]
                    ]);
                }
                //將user錢包扣款
                $Balance = $WalletMoney->balance - $Money;
                $this->CreateOrderServiceV2->DeductMoney($UserId, $Balance);
                // 存入wallet record
                $WalletRecordInfo = [
                    'oid' => $Oid,
                    'out' => $Money,
                    'status' => 0,
                    'pid' => $this->Payment[$Request['payment']],
                    'uid' => $UserId,
                ];
                $this->CreateOrderServiceV2->SaveWalletRecord($WalletRecordInfo);
                return response()->json([
                    'name' => $Request['name'],
                    'phone' => $Request['phone'],
                    'take_time' => $Request['take_time'],
                    'total_price' => $Request['total_price'],
                    'orders' => $RequestOrder
                ]);
            }
            //如果是金流付款
            if ($Request['payment'] === 'ecpay') {
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
                    'trade_desc' => $UserId . '訂餐',
                ];
                //發送api訂單至金流方
                $SendEcpayApi = $this->CreateOrderServiceV2->SendEcpayApi($EcpayInfo);
                //將發送訂單存入資料庫
                $this->CreateOrderServiceV2->SaveEcpay($SendEcpayApi[1]);
                //將交易紀錄存進資料庫
                $WalletRecordInfo = [
                    'eid' => $Uuid,
                    'oid' => $Oid,
                    'out' => $Money,
                    'status' => 11,
                    'pid' => $this->Payment[$Request['payment']],
                    'uid' => $UserId,
                ];
                $this->CreateOrderServiceV2->SaveWalletRecord($WalletRecordInfo);
                if (isset($SendEcpayApi[0]->transaction_url)) { //-> []
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

    public function EcpayCallBack(Request $Request)
    {
        try {
            $Tradedate = Carbon::createFromFormat('d/M/y H:m:s', $Request['trade_date']);
            $Paymentdate = Carbon::createFromFormat('d/M/y H:m:s', $Request['payment_date']); //大小寫
            $EcpayBackInfo = [
                'merchant_id' => $Request['merchant_id'],
                'trade_date' => $Tradedate,
                'check_mac_value' => $Request['check_mac_value'],
                'rtn_code' => $Request['rtn_code'],
                'rtn_msg' => $Request['rtn_msg'],
                'amount' => $Request['amount'],
                'payment_date' => $Paymentdate,
                'merchant_trade_no' => $Request['merchant_trade_no']
            ];
            $this->CreateOrderServiceV2->SaveEcpayBack($EcpayBackInfo);
            $Oid = $this->CreateOrderServiceV2->GetOidByUuid($Request['merchant_trade_no'])->oid;
            if ($Request['rtn_code'] == 0) {
                //將WalletRecord的status改為false
                $this->CreateOrderServiceV2->UpdateWalletRecordFail($Request['merchant_trade_no']);
                //將order的status改為false
                $this->CreateOrderServiceV2->UpdateOrederFail($Oid);
            } else {
                $this->CreateOrderServiceV2->UpdateWalletRecordsuccess($Request['merchant_trade_no']);
                $this->CreateOrderServiceV2->UpdateOrederSuccess($Oid);
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
            $Oid = $Request['oid'];
            //取出訂單
            $UserInfo = $this->User->GetUserInfo();
            $UserId = $UserInfo->id;
            $Order = $this->CreateOrderServiceV2->GetOrder($UserId, $Oid, $OffsetLimit);
            $OrderCount = $Order->count();
            return response()->json([
                'Err' => $this->Keys[0],
                'Message' => $this->Err[0],
                'Count' => $OrderCount,
                'Order' => $Order
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
            $Oid = $Request['oid'];
            $UserInfo = $this->User->GetUserInfo();
            $UserId = $UserInfo->id;
            $OrderInfo = $this->CreateOrderServiceV2->GetOrderInfo($UserId, $Oid);
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
    public function CheckSameArray(array $Array1, array $Array2): bool
    {
        try {
            if (count($Array1) === count($Array2) && count($Array1) !== 1) {
                return false;
            }
            return true;
        } catch (Throwable $e) {
            return response()->json([
                'Err' => $this->Keys[26],
                'Message' => $this->Err[26],
                'OtherErr' => $e->getMessage()
            ]);
        }
    }

    public function MergeOrdersBySameId(array $Order): array
    {
        try {
            $GoodOrder = [];
            foreach ($Order as $Item) {
                $Key = $Item['id'];
                if (array_key_exists($Key, $GoodOrder)) {
                    $GoodOrder[$Key]['price'] += $GoodOrder[$Key]['price'];
                    $GoodOrder[$Key]['quanlity'] += $GoodOrder[$Key]['quanlity'];
                } else {
                    if (isset($Item['description'])) {
                        $GoodOrder[$Key] = [
                            'rid' => $Item['rid'],
                            'id' => $Item['id'],
                            'name' => $Item['name'],
                            'price' => $Item['price'],
                            'quanlity' => $Item['quanlity'],
                            'description' => $Item['description']
                        ];
                    } else {
                        $GoodOrder[$Key] = [
                            'rid' => $Item['rid'],
                            'id' => $Item['id'],
                            'name' => $Item['name'],
                            'price' => $Item['price'],
                            'quanlity' => $Item['quanlity'],
                        ];
                    }
                }
            }
            $GoodOrder = array_values($GoodOrder);
            return $GoodOrder;
        } catch (Throwable $e) {
            return response()->json([
                'Err' => $this->Keys[26],
                'Message' => $this->Err[26],
                'OtherErr' => $e->getMessage()
            ]);
        }
    }
    public function CheckTotalPrice(array $Order, int $OrderTotalPrice): bool
    {
        try {
            $OrderCollection = collect($Order);
            $RealTotalPrice = $OrderCollection->sum('price');
            if ($RealTotalPrice !== $OrderTotalPrice) {
                return false;
            }
            return true;
        } catch (Throwable $e) {
            return response()->json([
                'Err' => $this->Keys[26],
                'Message' => $this->Err[26],
                'OtherErr' => $e->getMessage()
            ]);
        }
    }

    public function FixOrderInfo($RequestOrder, $Oid)
    {
        try {
            $OrderInfoInfo = array_map(function ($Item) use ($Oid) {
                if (isset($Item['description'])) {
                    return [
                        'description' => $Item['description'],
                        'oid' => $Oid, 'name' => $Item['name'],
                        'price' => $Item['price'],
                        'quanlity' => $Item['quanlity'],
                        'created_at' => now(),
                        'updated_at' => now()
                    ];
                }
                return [
                    'description' => null,
                    'oid' => $Oid,
                    'name' => $Item['name'],
                    'price' => $Item['price'],
                    'quanlity' => $Item['quanlity'],
                    'created_at' => now(),
                    'updated_at' => now()
                ];
            }, $RequestOrder);
            return $OrderInfoInfo;
        } catch (Throwable $e) {
            return response()->json([
                'Err' => $this->Keys[26],
                'Message' => $this->Err[26],
                'OtherErr' => $e->getMessage()
            ]);
        }

    }
}
