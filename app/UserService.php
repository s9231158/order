<?php

namespace App;

use App\Models\Wallet_Record;
use PhpParser\Node\Stmt\Catch_;
use App\TotalService;
use Illuminate\Support\Facades\Validator;
use Throwable;
use Tymon\JWTAuth\Facades\JWTAuth;
use Tymon\JWTAuth\Exceptions\TokenInvalidException;
use App\Models\User;

class UserService
{
    private $token;
    private $userinfo;
    public function __construct($token)
    {
        $this->token = $token;
        $this->userinfo = JWTAuth::parseToken()->authenticate();
    }

    public function UserCheck()
    {
        try {
            JWTAuth::parseToken()->authenticate();
        } catch (\Exception $e) {
            return false;
        } catch (Throwable $e) {
            return false;
        }
    }
    public function UserInfo()
    {
        try {
            $userInfo = JWTAuth::parseToken()->authenticate();
            return $userInfo;
        } catch (Throwable $e) {
            return false;
            //null
        }
    }


    /**
     * 取的該用戶交易紀錄
     *
     * @param array $request
     * @return array
     */
    public function GetUserWallet($request)
    {
        try {
            //預設offser&limit
            $TotalService = new TotalService;
            $result = $TotalService->GetOffsetLimit($request);
            $offset = $result['offset'];
            $limit = $result['limit'];

            //取出充值紀錄資料
            if ($request['type'] === 'in') {
                $WalletRecord = Wallet_Record::select('type', 'in', 'wallet__records.created_at')->join('payments', 'wallet__records.pid', '=', 'payments.id')->where("uid", '=', $this->userinfo->id)->where('in', '!=', 'NULL')->offset($offset)->limit($limit)->orderBy('wallet__records.created_at', 'desc')->get();
                $Count = $WalletRecord->count();
                return array('count' => $Count, 'wallet' => $WalletRecord);
            }
            //取出支紀錄資料
            if ($request['type'] === 'out') {
                $WalletRecord = Wallet_Record::select('type', 'out', 'oid', 'wallet__records.created_at')->join('payments', 'wallet__records.pid', '=', 'payments.id')->where("uid", '=', $this->userinfo->id)->where('out', '!=', 'NULL')->offset($offset)->limit($limit)->orderBy('wallet__records.created_at', 'desc')->get();
                $Count = $WalletRecord->count();
                return array('count' => $Count, 'wallet' => $WalletRecord);
            }
            //取出充值與支付紀錄
            $WalletRecord = [];
            $Count = 0;
            $WalletRecord['in'] = Wallet_Record::select('type', 'in', 'wallet__records.created_at')->join('payments', 'wallet__records.pid', '=', 'payments.id')->where("uid", '=', $this->userinfo->id)->where('in', '!=', 'NULL')->offset($offset)->limit($limit)->orderBy('wallet__records.created_at', 'desc')->get();
            $WalletRecord['out'] = Wallet_Record::select('type', 'out', 'wallet__records.created_at')->join('payments', 'wallet__records.pid', '=', 'payments.id')->where("uid", '=', $this->userinfo->id)->where('out', '!=', 'NULL')->offset($offset)->limit($limit)->orderBy('wallet__records.created_at', 'desc')->get();
            $Count += $WalletRecord['in']->count();
            $Count += $WalletRecord['out']->count();
            return array('count' => $Count, 'wallet' => $WalletRecord);
        } catch (Throwable $e) {
            return array($e, 'count' => $Count, 'wallet' => $WalletRecord);
        }
    }


}
