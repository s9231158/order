<?php

namespace App\Http\Controllers;

use App\Services\Ecpay;
use App\Services\EcpayBack;
use App\Services\UserWallet;
use App\Services\WalletRecord;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Exception;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Throwable;
use App\Services\ErrorCode;
use App\Services\Token;
use App\Services\EcpayApi;

class Wallet extends Controller
{
    private $err;
    private $keys;
    private $tokenService;
    private $walletRecordService;
    public function __construct(
        ErrorCode $errorCodeService,
        Token $tokenService,
        WalletRecord $walletRecordService,
    ) {
        $this->err = $errorCodeService->GeterrCode();
        $this->keys = $errorCodeService->GeterrKey();
        $this->tokenService = $tokenService;
        $this->walletRecordService = $walletRecordService;
    }
    public function addWalletMoney(Request $request)
    {
        //規則
        $ruls = [
            'money' => ['required', 'numeric', 'min:0'],
        ];
        //什麼錯誤報什麼錯誤訊息
        $rulsmessage = [
            'money.numeric' => '無效的範圍',
            'money.required' => '必填資料未填',
            'money.min' => '無效的範圍'
        ];
        try {
            //驗證參輸入數
            $validator = Validator::make($request->all(), $ruls, $rulsmessage);
            if ($validator->fails()) {
                return response()->json([
                    'err' => array_search($validator->errors()->first(), $this->err),
                    'message' => $validator->errors()->first()
                ]);
            }
            $userId = $this->tokenService->getUserId();
            $uuid = substr(Str::uuid(), 0, 20);
            $date = Carbon::now()->format('Y/m/d H:i:s');
            $money = $request['money'];
            $ecpayInfo = [
                "merchant_id" => 11,
                "merchant_trade_no" => $uuid,
                "merchant_trade_date" => $date,
                "payment_type" => "aio",
                "amount" => $money,
                "item_name" => '加值',
                'trade_desc' => $userId . '訂餐',
                "return_url" => env('ADD_WALLET_MONEY_ECPAY_RETURNURL'),
                "choose_payment" => "Credit",
                "encrypt_type" => 1,
                "lang" => "en"
            ];
            $ecpayApiService = new EcpayApi;
            $sendEcpayResponse = $ecpayApiService->sendEcpayApi($ecpayInfo);
            $ecpayService = new Ecpay;
            $ecpayService->create($sendEcpayResponse[1]);
            $walletRecordInfo = ['uid' => $userId, 'eid' => $uuid, 'in' => $money, 'status' => 11, 'pid' => 2];
            $this->walletRecordService->create($walletRecordInfo);
            if (isset($sendEcpayResponse[0]->transaction_url)) {
                return $sendEcpayResponse[0];
            }
            if (!isset($sendEcpayResponse[0]->transaction_url)) {
                return response()->json([
                    'err' => $sendEcpayResponse[0]->error_code,
                    'message' => '第三方金流錯誤'
                ]);
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

    public function addWalletMoneyCallBack(Request $request)
    {
        try {
            $money = $request['amount'];
            $tradeDate = Carbon::createFromFormat('Y/m/d H:i:s', $request['trade_date']);
            $paymentDate = Carbon::createFromFormat('Y/m/d H:i:s', $request['payment_date']);
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
            $ecpayBackService = new EcpayBack;
            $ecpayBackService->create($ecpayBackInfo);
            if ($request['rtn_code'] == 0) {
                //將WalletRecord的 status改為false
                $status = ['status' => 10];
                $this->walletRecordService->update(['eid' => $request['merchant_id']], $status);
            } else {
                //將WalletRecord的 status改為success
                $status = ['status' => 0];
                $this->walletRecordService->update(['oid' => $request['merchant_id']], $status);
                $userId = $this->tokenService->getUserId();
                //將金額加入使用者錢包
                $userWalletService = new UserWallet;
                $userWalletData = ['option' => ['column' => ['balance']]];
                $walletMoney = $userWalletService->get($userId, $userWalletData['option']);
                $balance = $walletMoney['balance'] + $money;
                $userWalletService->updateOrCreate($userId, $balance);
            }
        } catch (Exception $e) {
            Cache::set('moneycallback', $e);
        } catch (Throwable $e) {
            Cache::set('moneycallback', $e);
        }
    }
    public function getWallet(Request $request)
    {
        //規則
        $ruls = [
            'limit' => ['integer'],
            'offset' => ['integer'],
        ];
        //什麼錯誤報什麼錯誤訊息
        $rulsmessage = [
            'limit.integer' => '無效的範圍',
            'offset.integer' => '無效的範圍',
        ];
        try {
            $validator = Validator::make($request->all(), $ruls, $rulsmessage);
            if ($validator->fails()) {
                return response()->json([
                    'err' => array_search($validator->errors()->first(), $this->err),
                    'message' => $validator->errors()->first()
                ]);
            }
            //取的抓取範圍&類型
            $limit = $request['limit'] ?? 20;
            $offset = $request['offset'] ?? 0;
            $userId = $this->tokenService->getUserId();
            $type = $request['type'];
            $where = isset($request['type']) ? ["uid", '=', $userId, $type, '!=', $type] : ["uid", '=', $userId];
            $option = [
                'column' => isset($request['type']) ? ['type', $type, 'wallet__records.created_at'] : ['type', 'in', 'out', 'wallet__records.created_at'],
                'limit' => $limit,
                'offset' => $offset,
                'orderby' => ['wallet__records.created_at', 'desc'],
            ];
            $walletRecord = $this->walletRecordService->getJoinList($where, $option);
            $count = count($walletRecord);
            return response()->json([
                'err' => $this->keys[0],
                'message' => $this->err[0],
                'count' => $count,
                'data' => $walletRecord
            ]);
        } catch (Throwable $e) {
            return response()->json([
                'err' => $this->keys[26],
                'message' => $this->err[26],
                'other_err' => $e->getmessage()
            ]);
        }
    }
}
