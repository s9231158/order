<?php

namespace App\Http\Controllers;

use App\ErrorCodeService;
use App\RepositoryV2\User;
use App\TotalService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Exception;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Throwable;
use App\ServiceV2\Wallet as WalletServiceV2;

class Wallet extends Controller
{
    private $TotalService;
    private $Err;
    private $Keys;
    private $WalletServiceV2;
    private $User;
    public function __construct(
        WalletServiceV2 $WalletServiceV2,
        ErrorCodeService $ErrorCodeService,
        TotalService $TotalService,
        User $User
    ) {
        $this->User = $User;
        $this->WalletServiceV2 = $WalletServiceV2;
        $this->TotalService = $TotalService;
        $this->Err = $ErrorCodeService->GetErrCode();
        $this->Keys = $ErrorCodeService->GetErrKey();
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
            $UserInfo = $this->User->GetUserInfo();
            $UserId = $UserInfo->id;
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
                'trade_desc' => $UserId . '訂餐',
                "return_url" => env('AddWalletMoneyEcpay_ReturnUrl'),
                "choose_payment" => "Credit",
                "encrypt_type" => 1,
                "lang" => "en"
            ];
            $SendEcpayApi = $this->WalletServiceV2->SendEcpayApi($EcpayInfo);
            $this->WalletServiceV2->SaveEcpay($SendEcpayApi[1]);
            $WalletRecordInfo = ['uid' => $UserId, 'eid' => $Uuid, 'in' => $Money, 'status' => 11, 'pid' => 2];
            $this->WalletServiceV2->SaveWalletRecord($WalletRecordInfo);
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
            $Money = $Request['amount'];
            $Tradedate = Carbon::createFromFormat('Y/m/d H:i:s', $Request['trade_date']);
            $Paymentdate = Carbon::createFromFormat('Y/m/d H:i:s', $Request['payment_date']);
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

            $this->WalletServiceV2->SaveEcpayBack($EcpayBackInfo);
            if ($Request['rtn_code'] == 0) {
                //將WalletRecord的 status改為false
                $this->WalletServiceV2->UpdateWalletRecordFail($Request['merchant_trade_no']);
            } else {
                $Yesterday = date('Y-m-d H:i:s', strtotime('-1 day'));
                $Today = date('Y-m-d H:i:s');
                $Option = [
                    'Eid' => $Request['merchant_trade_no'],
                    'StartTime' => $Yesterday,
                    'EndTime' => $Today
                ];
                $this->WalletServiceV2->AddMoney($Money, $Option);
                $this->WalletServiceV2->UpdateWalletRecordSuccess($Request['merchant_trade_no']);
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
            $Type = $Request['type'];
            $GetWalletRecord = $this->WalletServiceV2->GetWalletRecordOnRangeAndType($OffsetLimit, $Type);
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
