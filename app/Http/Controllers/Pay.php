<?php

namespace App\Http\Controllers;

use App\Services\EcpayBack;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use App\Factorise;
use App\Services\ErrorCode;
use App\Services\Token;
use App\Services\Restaurant;
use App\Services\Order;
use App\Services\OrderInfo;
use App\Services\EcpayApi;
use App\Services\UserWallet;
use App\Services\WalletRecord;
use App\Services\Ecpay;
use App\Services\StatusCode;
use Exception;
use Throwable;

class Pay extends Controller
{
    private $payment = [
        'ecpay' => 2,
        'local' => 1,
    ];
    private $statusCode;
    private $err;
    private $keys;
    private $tokenService;
    private $orderService;
    public function __construct(
        ErrorCode $errorCodeService,
        Token $tokenService,
        Order $orderService,
        StatusCode $statusCode,
    ) {
        $this->statusCode = $statusCode->getStatus();
        $this->tokenService = $tokenService;
        $this->err = $errorCodeService->getErrCode();
        $this->keys = $errorCodeService->getErrKey();
        $this->orderService = $orderService;
    }
    public function createOrder(Request $request)
    {
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
                    'err' => array_search($validator->Errors()->first(), $this->err),
                    'message' => $validator->Errors()->first()
                ]);
            }
            //取出Request內Order         
            $order = $request['orders'];
            //訂單內餐廳是否都一致
            $orderRids = array_column($order, 'rid');
            if (count(array_unique($orderRids)) !== 1) {
                return response()->json([
                    'err' => $this->keys[22],
                    'message' => $this->err[22]
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
            $option = [];
            $restaurantService = new Restaurant();
            $restaurantInfo = $restaurantService->getJoinist($where, $option)[0];
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
            $restaurant = Factorise::setMenu($rid);
            $menuCorrect = $restaurant->menuCorrect($order);
            if (!$menuCorrect) {
                return response()->json([
                    'err' => $this->keys[30],
                    'message' => $this->err[30]
                ]);
            }
            // 餐點是否停用
            $menuIds = array_column($order, 'id');
            $menuEnable = $restaurant->MenuEnable($menuIds);
            if (!$menuEnable) {
                return response()->json([
                    'err' => $this->keys[25],
                    'message' => $this->err[25]
                ]);
            }
            $money = $request['total_price'];
            $now = now();
            $takeTime = $request['take_time'];
            $address = $request['address'];
            $phone = $request['phone'];
            $tokenService = new Token();
            $userId = $tokenService->getUserId();
            $uuid = Str::uuid();
            $orderInfoService = new OrderInfo();
            $walltRecordService = new WalletRecord();
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
                    $saveOrderInfo = [
                        'payment' => $request['payment'],
                        'ordertime' => $now,
                        'taketime' => $takeTime,
                        'total' => $realTotalPrice,
                        'phone' => $phone,
                        'address' => $address,
                        'status' => $this->statusCode['waitPay'],
                        'rid' => $rid,
                        'uid' => $userId,
                    ];
                    //儲存訂單
                    $response = $this->orderService->create($saveOrderInfo);
                    $oid = $response['id'];
                    //整理訂單詳情資料
                    $createOrderInfo = $this->fixOrderInfo($order, $oid);
                    //儲存訂單詳情
                    $orderInfoService->create($createOrderInfo);
                } else {
                    //訂單傳送失敗 將失敗訂單存置資料庫
                    $saveOrderInfo = [
                        'payment' => $request['payment'],
                        'ordertime' => $now,
                        'taketime' => $takeTime,
                        'total' => $realTotalPrice,
                        'phone' => $phone,
                        'address' => $address,
                        'status' => $this->statusCode['sendApiFail'],
                        'rid' => $rid,
                        'uid' => $userId,
                    ];
                    $response = $this->orderService->create($saveOrderInfo);
                    return response()->json([
                        'err' => $this->keys[34],
                        'message' => $this->err[34]
                    ]);
                }
            } else {
                //本地餐廳訂單
                $saveOrderInfo = [
                    'payment' => $request['payment'],
                    'ordertime' => $now,
                    'taketime' => $takeTime,
                    'total' => $realTotalPrice,
                    'phone' => $phone,
                    'address' => $address,
                    'status' => $this->statusCode['waitPay'],
                    'rid' => $rid,
                    'uid' => $userId,
                ];
                //儲存訂單
                $response = $this->orderService->create($saveOrderInfo);
                $oid = $response['id'];
                //整理本地訂單詳情資料
                $createOrderInfo = $this->fixOrderInfo($order, $oid);
                //儲存訂單詳情
                $orderInfoService->create($createOrderInfo);
            }
            //如果是本地付款
            if ($request['payment'] === 'local') {
                //檢查User錢包是否足夠付款
                $money = $request['total_price'];
                $userWalletService = new UserWallet();
                $userWalletData = ['option' => ['column' => ['balance']]];
                $walletMoney = $userWalletService->get($userId, $userWalletData['option']);
                if ($walletMoney['balance'] < $money) {
                    $status = $this->statusCode['walletNoMoneyFail'];
                    $this->orderService->update($oid, $status);
                    return response()->json([
                        'err' => $this->keys[18],
                        'message' => $this->err[18]
                    ]);
                }
                //將user錢包扣款
                $balance = $walletMoney['balance'] - $money;
                $userWalletService->updateOrCreate($userId, $balance);
                // 存入wallet record
                $walletRecordInfo = [
                    'oid' => $oid,
                    'out' => $money,
                    'status' => $this->statusCode['success'],
                    'pid' => $this->payment[$request['payment']],
                    'uid' => $userId,
                ];
                $walltRecordService->create($walletRecordInfo);
                //修改訂單狀態
                $status = $this->statusCode['success'];
                $this->orderService->update($oid, $status);
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
                $uuid = substr(Str::uuid(), 0, 20);
                $date = date('Y/m/d H:i:s');
                $orderMenuNames = array_column($order, 'name');
                $itemsString = implode(",", $orderMenuNames);
                $ecpayApiService = new EcpayApi();
                $ecpayService = new Ecpay();
                $ecpayInfo = [
                    "merchant_trade_no" => $uuid,
                    "merchant_trade_date" => $date,
                    "amount" => $money,
                    "item_name" => $itemsString,
                    'trade_desc' => $userId . '訂餐',
                ];
                //發送api訂單至金流方
                $sendEcpayResponse = $ecpayApiService->sendEcpayApi($ecpayInfo);
                //將發送訂單存入資料庫
                $ecpayService->create($sendEcpayResponse[1]);
                //將交易紀錄存進資料庫
                $walletRecordInfo = [
                    'eid' => $uuid,
                    'oid' => $oid,
                    'out' => $money,
                    'status' => $this->statusCode['waitEcpayReponse'],
                    'pid' => $this->payment[$request['payment']],
                    'uid' => $userId,
                ];
                $walltRecordService->create($walletRecordInfo);
                if (isset($sendEcpayResponse[0]->transaction_url)) {
                    return $sendEcpayResponse[0];
                }
                if (!isset($sendEcpayResponse[0]->transaction_url)) {
                    $walletRecordService = new WalletRecord();
                    $status = ['status' => $this->statusCode['ecpayFail']];
                    $walletRecordService->update(['oid', $oid], $status);
                    return response()->json([
                        'err' => $sendEcpayResponse[0]->error_code,
                        'message' => '第三方金流錯誤'
                    ]);
                }
            }
        } catch (Exception $e) {
            return response()->json([
                'err' => $this->keys[26],
                'message' => $this->err[26],
                'other_err' => $e->getMessage()
            ]);
        } catch (Throwable $e) {
            return response()->json([
                'rrr' => $this->keys[26],
                'message' => $this->err[26],
                'other_err' => $e->getMessage()
            ]);
        }
    }

    public function ecpayCallBack(Request $request)
    {
        try {
            $tradeDate = Carbon::createFromFormat('d/M/y H:m:s', $request['trade_date']);
            $paymentDate = Carbon::createFromFormat('d/M/y H:m:s', $request['payment_date']);
            $ecpayBackInfo = [
                'merchant_id' => $request['merchant_id'],
                'trade_date' => $tradeDate,
                'check_mac_value' => $request['check_mac_value'],
                'rtn_code' => $request['rtn_code'],
                'rtn_msg' => $request['rtn_msg'],
                'amount' => $request['amount'],
                'payment_date' => $paymentDate,
                'merchant_trade_no' => $request['merchant_trade_no']
            ];
            $orderService = new Order();
            $ecpayBackService = new EcpayBack();
            $ecpayBackService->create($ecpayBackInfo);
            $walletRecordService = new WalletRecord();
            $option = [];
            $wallerRecord = $walletRecordService->get($request['merchant_trade_no'], $option);
            $oid = $wallerRecord['oid'];
            if ($request['rtn_code'] == 0) {
                //將WalletRecord的status改為失敗代碼
                $status = ['status' => $this->statusCode['ecpayFail']];
                $walletRecordService->update(['oid', $oid], $status);
                //將order的status改為失敗代碼
                $orderService->update($oid, $status);
            } else {
                $status = ['status' => $this->statusCode['success']];
                $walletRecordService->update(['oid', $oid], $status);
                $orderService->update($oid, $status);
            }
        } catch (Exception $e) {
            return response()->json([
                'err' => $this->keys[26],
                'message' => $this->err[26],
                'other_err' => $e->getMessage()
            ]);
        } catch (Throwable $e) {
            return response()->json([
                'err' => $this->keys[26],
                'message' => $this->err[26],
                'other_err' => $e->getMessage()
            ]);
        }
    }

    public function getOrder(Request $request)
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
                    'err' => array_search($validator->Errors()->first(), $this->err),
                    'message' => $validator->Errors()->first()
                ]);
            }
            //取得offset limit
            $limit = $request['limit'] ?? 20;
            $offset = $request['offset'] ?? 0;
            //取出訂單
            $userId = $this->tokenService->getUserId();
            $orderService = new Order();
            $oid = $request['oid'] ?? null;
            if ($oid) {
                $where = [
                    'id',
                    $oid,
                    'uid',
                    $userId
                ];
                $option = ['column' => ['id', 'ordertime', 'taketime', 'total', 'status']];
                $order = $orderService->get($where, $option);
                return response()->json([
                    'err' => $this->keys[0],
                    'message' => $this->err[0],
                    'order' => $order
                ]);
            } else {
                $where = ['uid', '=', $userId];
                $option = [
                    'column' => ['id', 'ordertime', 'taketime', 'total', 'status'],
                    'offser' => $offset,
                    'limit' => $limit
                ];
                $orders = $orderService->getList($where, $option);
                $count = count($orders);
                return response()->json([
                    'err' => $this->keys[0],
                    'message' => $this->err[0],
                    'count' => $count,
                    'orders' => $orders
                ]);
            }
        } catch (Throwable $e) {
            return response()->json([
                'err' => $this->keys[26],
                'message' => $this->err[26],
                'other_err' => $e->getMessage()
            ]);
        }
    }

    public function getOrderInfo(Request $request)
    {
        //規則
        $ruls = [
            'oid' => ['integer', 'required']
        ];
        //什麼錯誤報什麼錯誤訊息
        $rulsMessage = [
            'oid.required' => '必填資料未填',
            'oid.integer' => '資料填寫與規格不符'
        ];
        try {
            //驗證參輸入數
            $validator = Validator::make($request->all(), $ruls, $rulsMessage);
            if ($validator->fails()) {
                return response()->json([
                    'err' => array_search($validator->Errors()->first(), $this->err),
                    'message' => $validator->Errors()->first()
                ]);
            }
            $oid = $request['oid'];
            $userId = $this->tokenService->getUserId();
            $orderData = [
                'where' => ['id', $oid, 'uid', $userId],
                'option' => []
            ];
            $isUser = $this->orderService->get($orderData['where'], $orderData['option']);
            if (!$isUser) {
                return response()->json([
                    'err' => $this->keys[19],
                    'message' => $this->err[19]
                ]);
            }
            $orderInfoData = [
                'where' => ['oid', '=', $oid],
                'option' => [
                    'column' => [
                        'name',
                        'quanlity',
                        'price',
                        'description'
                    ]
                ]
            ];
            $orderInfoService = new OrderInfo();
            $orderInfo = $orderInfoService->getList($orderInfoData['where'], $orderInfoData['option']);
            return response()->json([
                'err' => $this->keys[0],
                'message' => $this->err[0],
                'orders_info' => $orderInfo
            ]);
        } catch (Throwable $e) {
            return response()->json([
                'err' => $this->keys[26],
                'message' => $this->err[26],
                'other_err' => $e->getMessage()
            ]);
        }
    }

    public function fixOrderInfo($order, $oid)
    {
        try {
            $orderInfoInfo = array_map(function ($item) use ($oid) {
                if (isset($item['description'])) {
                    return [
                        'description' => $item['description'],
                        'name' => $item['name'],
                        'oid' => $oid,
                        'price' => $item['price'],
                        'quanlity' => $item['quanlity'],
                        'created_at' => now(),
                        'updated_at' => now()
                    ];
                }
                return [
                    'description' => null,
                    'oid' => $oid,
                    'name' => $item['name'],
                    'price' => $item['price'],
                    'quanlity' => $item['quanlity'],
                    'created_at' => now(),
                    'updated_at' => now()
                ];
            }, $order);
            return $orderInfoInfo;
        } catch (Throwable $e) {
            return response()->json([
                'err' => $this->keys[26],
                'message' => $this->err[26],
                'OtherErr' => $e->getMessage()
            ]);
        }

    }
}
