<?php

namespace App\Http\Controllers;

use App\RepositoryV2\User;
use App\TotalService;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Throwable;
use App\ServiceV2\CreateOrder as CreateOrderServiceV2;

//new
use App\Services\ErrorCode;
use App\Services\Token;
use App\Services\Restaurant;
use App\Factorise;
use App\Services\Order;
use App\Services\OrderInfo;

//new




class Pay extends Controller
{
    private $TotalService;
    private $Payment = [
        'ecpay' => 2,
        'local' => 1,
    ];
    private $CreateOrderServiceV2;
    private $User;

    //new
    private $err;
    private $keys;
    private $tokenService;
    private $restaurantService;
    private $orderService;
    //new
    public function __construct(
        CreateOrderServiceV2 $CreateOrderServiceV2,
        TotalService $TotalService,
        User $User,

        //new
        ErrorCode $errorCodeService,
        Token $tokenService,
        Restaurant $restaurantService,
        Order $orderService,
        //new
    ) {
        $this->User = $User;
        $this->CreateOrderServiceV2 = $CreateOrderServiceV2;
        $this->TotalService = $TotalService;

        //new
        $this->tokenService = $tokenService;
        $this->err = $errorCodeService->getErrCode();
        $this->keys = $errorCodeService->getErrKey();
        $this->restaurantService = $restaurantService;
        $this->orderService = $orderService;
        //new 
    }
    public function CreateOrder(Request $request)
    {
        $aa = [
            '全錯' => 11111, 'b' => 222222,
            111111 => 'a', 2222222 => 'b'
        ];
        return array_keys($aa);


        //規則
        $ruls = [
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
        $rulsMessage = [
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
            $validator = Validator::make($request->all(), $ruls, $rulsMessage);
            if ($validator->fails()) {
                return response()->json([
                    'Err' => array_search($validator->Errors()->first(), $this->err),
                    'Message' => $validator->Errors()->first()
                ]);
            }
            //取出Request內Order         
            $order = $request['orders'];

            //訂單內餐廳是否都一致
            $orderRids = array_column($order, 'rid');
            if (count(array_unique($orderRids)) !== 1) {
                return response()->json([
                    'Err' => $this->keys[22],
                    'Message' => $this->err[22]
                ]);
            }
            //檢查Order內是否有一樣的菜單,有的話將一樣的菜單合併
            $goodOrder = [];
            foreach ($order as $item) {
                $description = $item['description'] ?? null;
                $key = $item['id'] . $description;
                if (array_key_exists($key, $goodOrder)) {
                    $goodOrder[$key]['quanlity'] += $item['quanlity'];
                } else {
                    $goodOrder[$key] = $item;
                }
            }
            $goodOrder = array_values($goodOrder);
            //餐廳是否存在且啟用
            $rid = $order[0]['rid'];
            $where = ['restaurants.id', '=', $rid];
            $option = [
                'first' => 1,
                'join' => ['restaurant_open_days', 'restaurant_open_days.id', '=', 'restaurants.id'],
            ];
            $restaurantInfo = $this->restaurantService->get($where, $option);
            if (!$restaurantInfo || $restaurantInfo['enable'] != 1) {
                return response()->json([
                    'err' => $this->keys[16],
                    'message' => $this->err[16]
                ]);
            }
            //訂單總金額是否正確
            $orderCollection = collect($order);
            $realTotalPrice = $orderCollection->sum('price');
            if ($realTotalPrice !== $request['total_price']) {
                return response()->json([
                    'err' => $this->keys[20],
                    'message' => $this->err[20]
                ]);
            }
            //餐廳今天是否有營業
            if ($restaurantInfo[date('l')] !== 1) {
                return response()->json([
                    'err' => $this->keys[17],
                    'message' => $this->err[17]
                ]);
            }
            // //檢查菜單金額名稱id是否與店家一致
            $factorise = new Factorise;
            $restaurant = $factorise->setMenu($rid);
            // $menuCorrect = $restaurant->menuCorrect($order);
            // if (!$menuCorrect) {
            //     return response()->json([
            //         'err' => $this->keys[30],
            //         'message' => $this->err[30]
            //     ]);
            // }
            // // 餐點是否停用
            // $menuIds = array_column($order, 'id');
            // $menuEnable = $this->CreateOrderServiceV2->MenuEnable($menuIds);
            // if (!$menuEnable) {
            //     return response()->json([
            //         'Err' => $this->keys[25],
            //         'Message' => $this->err[25]
            //     ]);
            // }
            $money = $request['total_price'];
            $now = now();
            $takeTime = $request['take_time'];
            $address = $request['address'];
            $phone = $request['phone'];
            $tokenService = new Token;
            $UserId = $tokenService->getUserId();
            $uuid = Str::uuid();
            $orderInfoService = new OrderInfo;
            //非本地餐廳訂餐
            if ($restaurantInfo['api']) {
                $apiOrderInfo = [
                    'uuid' => $uuid,
                    'name' => $request['name'],
                    'phone' => $request['phone'],
                    'taketime' => $request['take_time'],
                    'total_price' => $request['total_price']
                ];
                //將訂餐資訊傳至供應商     
                $response = $restaurant->SendApi($apiOrderInfo, $order);
                if ($response) {
                    //訂單傳送成功將訂單存至資料庫
                    $SaveOrderInfo = [
                        'ordertime' => $now,
                        'taketime' => $takeTime,
                        'total' => $realTotalPrice,
                        'phone' => $phone,
                        'address' => $address,
                        'status' => 0,
                        'rid' => $rid,
                        'uid' => $UserId,
                    ];
                    //儲存訂單
                    $response = $this->orderService->create($SaveOrderInfo);
                    $oid = $response['id'];
                    //整理訂單詳情資料
                    $CreateOrderInfo = $this->FixOrderInfo($order, $oid);
                    //儲存訂單詳情
                    $orderInfoService->create($CreateOrderInfo);
                } else {
                    //訂單傳送失敗 將失敗訂單存置資料庫
                    $SaveOrderInfo = [
                        'ordertime' => $now,
                        'taketime' => $takeTime,
                        'total' => $realTotalPrice,
                        'phone' => $phone,
                        'address' => $address,
                        'status' => 10,
                        'rid' => $rid,
                        'uid' => $UserId,
                    ];
                    $response = $this->orderService->create($SaveOrderInfo);
                    return response()->json([
                        'err' => $this->keys[34],
                        'message' => $this->err[34]
                    ]);
                }
            } else {
                //本地餐廳訂單
                $SaveOrderInfo = [
                    'ordertime' => $now,
                    'taketime' => $takeTime,
                    'total' => $realTotalPrice,
                    'phone' => $phone,
                    'address' => $address,
                    'status' => 0,
                    'rid' => $rid,
                    'uid' => $UserId,
                ];
                //儲存訂單
                $response = $this->orderService->create($SaveOrderInfo);
                $oid = $response['id'];
                //整理本地訂單詳情資料
                $CreateOrderInfo = $this->FixOrderInfo($order, $oid);
                //儲存訂單詳情
                $orderInfoService->create($CreateOrderInfo);
            }

            //如果是本地付款
            if ($request['payment'] === 'local') {
                //檢查User錢包是否足夠付款
                $money = $request['total_price'];
                $WalletMoney = $this->CreateOrderServiceV2->GetWalletMoney($UserId, $money);
                if ($WalletMoney->balance < $money) {
                    return response()->json([
                        'Err' => $this->keys[18],
                        'Message' => $this->err[18]
                    ]);
                }
                //將user錢包扣款
                $Balance = $WalletMoney->balance - $money;
                $this->CreateOrderServiceV2->DeductMoney($UserId, $Balance);
                // 存入wallet record
                $WalletRecordInfo = [
                    'oid' => $Oid,
                    'out' => $money,
                    'status' => 0,
                    'pid' => $this->Payment[$request['payment']],
                    'uid' => $UserId,
                ];
                $this->CreateOrderServiceV2->SaveWalletRecord($WalletRecordInfo);
                return response()->json([
                    'name' => $request['name'],
                    'phone' => $request['phone'],
                    'take_time' => $request['take_time'],
                    'total_price' => $request['total_price'],
                    'orders' => $order
                ]);
            }
            //如果是金流付款
            if ($request['payment'] === 'ecpay') {
                //將Ecpay資料存置資料庫
                $Uuid = substr(Str::uuid(), 0, 20);
                $Date = Carbon::now()->format('Y/m/d H:i:s');
                $AllOrderMenuName = array_column($order, 'name');
                $Itemstring = implode(",", $AllOrderMenuName);
                $EcpayInfo = [
                    "merchant_trade_no" => $Uuid,
                    "merchant_trade_date" => $Date,
                    "amount" => $money,
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
                    'out' => $money,
                    'status' => 11,
                    'pid' => $this->Payment[$request['payment']],
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
                'Err' => $this->keys[26],
                'Message' => $this->err[26],
                'OtherErr' => $e->getMessage()
            ]);
        } catch (Throwable $e) {
            return response()->json([
                'Err' => $this->keys[26],
                'Message' => $this->err[26],
                'OtherErr' => $e->getMessage()
            ]);
        }
    }

    public function EcpayCallBack(Request $request)
    {
        try {
            $Tradedate = Carbon::createFromFormat('d/M/y H:m:s', $request['trade_date']);
            $Paymentdate = Carbon::createFromFormat('d/M/y H:m:s', $request['payment_date']); //大小寫
            $EcpayBackInfo = [
                'merchant_id' => $request['merchant_id'],
                'trade_date' => $Tradedate,
                'check_mac_value' => $request['check_mac_value'],
                'rtn_code' => $request['rtn_code'],
                'rtn_msg' => $request['rtn_msg'],
                'amount' => $request['amount'],
                'payment_date' => $Paymentdate,
                'merchant_trade_no' => $request['merchant_trade_no']
            ];
            $this->CreateOrderServiceV2->SaveEcpayBack($EcpayBackInfo);
            $Oid = $this->CreateOrderServiceV2->GetOidByUuid($request['merchant_trade_no'])->oid;
            if ($request['rtn_code'] == 0) {
                //將WalletRecord的status改為false
                $this->CreateOrderServiceV2->UpdateWalletRecordFail($request['merchant_trade_no']);
                //將order的status改為false
                $this->CreateOrderServiceV2->UpdateOrederFail($Oid);
            } else {
                $this->CreateOrderServiceV2->UpdateWalletRecordsuccess($request['merchant_trade_no']);
                $this->CreateOrderServiceV2->UpdateOrederSuccess($Oid);
            }
        } catch (Exception $e) {
            return response()->json([
                'Err' => $this->keys[26],
                'Message' => $this->err[26],
                'OtherErr' => $e->getMessage()
            ]);
        } catch (Throwable $e) {
            return response()->json([
                'Err' => $this->keys[26],
                'Message' => $this->err[26],
                'OtherErr' => $e->getMessage()
            ]);
        }
    }

    public function GetOrder(Request $request)
    {
        //規則
        $ruls = [
            'limit' => ['integer'],
            'offset' => ['integer'],
            'oid' => ['integer'],
        ];
        //什麼錯誤報什麼錯誤訊息
        $rulsMessage = [
            'limit.integer' => '無效的範圍',
            'offset.integer' => '無效的範圍',
            'oid.integer' => '無效的範圍',
        ];
        try {
            //驗證參輸入數
            $validator = Validator::make($request->all(), $ruls, $rulsMessage);
            if ($validator->fails()) {
                return response()->json([
                    'Err' => array_search($validator->Errors()->first(), $this->err),
                    'Message' => $validator->Errors()->first()
                ]);
            }
            //取得offset limit
            $OffsetLimit = ['limit' => $request['limit'], 'offset' => $request['offset']];
            $OffsetLimit = $this->TotalService->GetOffsetLimit($OffsetLimit);
            $Oid = $request['oid'];
            //取出訂單
            $userInfo = $this->User->GetUserInfo();
            $UserId = $userInfo->id;
            $Order = $this->CreateOrderServiceV2->GetOrder($UserId, $Oid, $OffsetLimit);
            $OrderCount = $Order->count();
            return response()->json([
                'Err' => $this->keys[0],
                'Message' => $this->err[0],
                'Count' => $OrderCount,
                'Order' => $Order
            ]);
        } catch (Throwable $e) {
            return response()->json([
                'Err' => $this->keys[26],
                'Message' => $this->err[26],
                'OtherErr' => $e->getMessage()
            ]);
        }
    }
    public function GetOrderInfo(Request $request)
    {
        //規則
        $ruls = [
            'oid' => ['integer']
        ];
        //什麼錯誤報什麼錯誤訊息
        $rulsMessage = [
            'oid.integer' => '資料填寫與規格不符'
        ];
        try {
            //驗證參輸入數
            $validator = Validator::make($request->all(), $ruls, $rulsMessage);
            if ($validator->fails()) {
                return response()->json(['Err' => array_search($validator->Errors()->first(), $this->err), 'Message' => $validator->Errors()->first()]);
            }
            $Oid = $request['oid'];
            $userInfo = $this->User->GetUserInfo();
            $UserId = $userInfo->id;
            $orderInfo = $this->CreateOrderServiceV2->GetOrderInfo($UserId, $Oid);
            if (!isset($orderInfo[0])) {
                return response()->json([
                    'Err' => $this->keys[19],
                    'Message' => $this->err[19]
                ]);
            }
            return response()->json([
                'Err' => $this->keys[0],
                'Message' => $this->err[0],
                'ordersinfo' => $orderInfo
            ]);
        } catch (Throwable $e) {
            return response()->json([
                'Err' => $this->keys[26],
                'Message' => $this->err[26],
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
                'Err' => $this->keys[26],
                'Message' => $this->err[26],
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
                'Err' => $this->keys[26],
                'Message' => $this->err[26],
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
                'Err' => $this->keys[26],
                'Message' => $this->err[26],
                'OtherErr' => $e->getMessage()
            ]);
        }
    }

    public function FixOrderInfo($order, $oid)
    {
        try {
            $orderInfoInfo = array_map(function ($Item) use ($oid) {
                if (isset($Item['description'])) {
                    return [
                        'description' => $Item['description'],
                        'name' => $Item['name'],
                        'oid' => $oid,
                        'price' => $Item['price'],
                        'quanlity' => $Item['quanlity'],
                        'created_at' => now(),
                        'updated_at' => now()
                    ];
                }
                return [
                    'description' => null,
                    'oid' => $oid,
                    'name' => $Item['name'],
                    'price' => $Item['price'],
                    'quanlity' => $Item['quanlity'],
                    'created_at' => now(),
                    'updated_at' => now()
                ];
            }, $order);
            return $orderInfoInfo;
        } catch (Throwable $e) {
            return response()->json([
                'Err' => $this->keys[26],
                'Message' => $this->err[26],
                'OtherErr' => $e->getMessage()
            ]);
        }

    }
}
