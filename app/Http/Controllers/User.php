<?php

namespace App\Http\Controllers;

use App\ErrorCodeService;
use App\ServiceV2\User as UserServiceV2;
use App\TotalService;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Validator;
use Throwable;

class User extends Controller
{
    //錯誤訊息統整
    private $Err = [];
    private $Keys = [];
    private $TotalService;
    private $UserServiceV2;
    public function __construct(
        UserServiceV2 $UserServiceV2,
        ErrorCodeService $ErrorCodeService,
        TotalService $TotalService,
    ) {
        $this->UserServiceV2 = $UserServiceV2;
        $this->Keys = $ErrorCodeService->GetErrKey();
        $this->Err = $ErrorCodeService->GetErrCode();
        $this->TotalService = $TotalService;
    }
    public function CreateUser(Request $Request)
    {
        try {
            //規則
            $Ruls = [
                'name' => ['required', 'string', 'max:25', 'min:3'],
                'email' => ['required', 'string', 'unique:users,email', 'min:15', 'max:50', 'email'],
                'password' => ['required', 'min:10', 'max:25', 'string'],
                'phone' => ['required', 'string', 'size:9', 'digits_between:1,9', 'unique:users,phone'],
                'address' => ['required', 'string', 'min:10', 'max:25'],
                'age' => ['required', 'before:' . Carbon::now()->subYears(12)->format('Y-m-d'), 'date'],
            ];
            //什麼錯誤報什麼錯誤訊息
            $RulsMessage = [
                'name.required' => '資料填寫與規格不符',
                'name.max' => '資料填寫與規格不符',
                'name.min' => '資料填寫與規格不符',
                'name.string' => '資料填寫與規格不符',
                'email.required' => '必填資料未填',
                'email.unique' => 'email已註冊',
                'email.min' => '資料填寫與規格不符',
                'email.max' => '資料填寫與規格不符',
                'email.email' => '資料填寫與規格不符',
                'email.string' => '資料填寫與規格不符',
                'password.required' => '必填資料未填',
                'password.min' => '資料填寫與規格不符',
                'password.max' => '資料填寫與規格不符',
                'password.string' => '資料填寫與規格不符',
                'phone.required' => '必填資料未填',
                'phone.string' => '資料填寫與規格不符',
                'phone.size' => '資料填寫與規格不符',
                'phone.digits_between' => '資料填寫與規格不符',
                'phone.unique' => '電話已註冊',
                'address.required' => '必填資料未填',
                'address.min' => '資料填寫與規格不符',
                'address.string' => '資料填寫與規格不符',
                'address.max' => '資料填寫與規格不符',
                'age.required' => '必填資料未填',
                'age.before' => '資料填寫與規格不符',
                'age.date' => '資料填寫與規格不符',
            ];
            $Validator = Validator::make($Request->all(), $Ruls, $RulsMessage);
            if ($Validator->fails()) {
                return response()->json([
                    'Err' => array_search($Validator->Errors()->first(), $this->Err),
                    'Message' => $Validator->Errors()->first()
                ]);
            }

            //Request將資料取出
            $UserInfo = [
                'password' => $Request['password'],
                'email' => $Request['email'],
                'name' => $Request['name'],
                'phone' => $Request['phone'],
                'address' => $Request['address'],
                'age' => $Request['age']
            ];

            // 將使用者資訊存入Users
            $this->UserServiceV2->CreateUser($UserInfo);

            // 將使用者資訊存入UserWallet
            $Email = $Request['email'];
            $this->UserServiceV2->CreatrWallet($Email);
            return response()->json([
                'Err' => $this->Keys[0],
                'Message' => $this->Err[0]
            ]);
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
    public function Login(Request $Request)
    {
        try {
            //規則
            $Ruls = [
                'email' => ['required', 'string', 'min:15', 'max:50', 'email'],
                'password' => ['required', 'min:10', 'max:25', 'string'],
            ];
            //什麼錯誤報什麼錯誤訊息
            $RulsMessage = [
                'email.required' => '必填資料未填',
                'email.min' => '資料填寫與規格不符',
                'email.string' => '資料填寫與規格不符',
                'email.max' => '資料填寫與規格不符',
                'email.email' => '資料填寫與規格不符',
                'password.required' => '必填資料未填',
                'password.min' => '資料填寫與規格不符',
                'password.max' => '資料填寫與規格不符',
                'password.string' => '資料填寫與規格不符',
            ];
            $Validator = Validator::make($Request->all(), $Ruls, $RulsMessage);
            if ($Validator->fails()) {
                return response()->json([
                    'Err' => array_search($Validator->Errors()->first(), $this->Err),
                    'Message' => $Validator->Errors()->first()
                ]);
            }

            //檢查此組Key是否一定時間內登入多次            
            $Ip = $Request->ip();
            $Email = $Request['email'];
            $LoginToManyTimes = $this->UserServiceV2->LoginCheckTooManyAttempts($Ip, $Email);
            if ($LoginToManyTimes) {
                return response()->json([
                    'Err' => $this->Keys[7],
                    'Message' => $this->Err[7]
                ]);
            }

            // 檢查是否有重複登入
            $Token = $Request->header('Authorization');
            if ($Token !== null) {
                $AlreadyLogin = $this->UserServiceV2->CheckHasLogin($Token, $Email);
                if ($AlreadyLogin) {
                    return response()->json([
                        'Err' => $this->Keys[$AlreadyLogin],
                        'Message' => $this->Err[$AlreadyLogin]
                    ]);
                }
            }

            //驗證帳號密碼
            $Account = ['email' => $Email, 'password' => $Request['password']];
            $CheckUser = $this->UserServiceV2->CheckUser($Account);
            if (!$CheckUser) {
                return response()->json([
                    'Err' => $this->Keys[8],
                    'Message' => $this->Err[8]
                ]);
            }

            //將使用者登入資訊存入該使用者user_recode
            $Device = $Request->header('User-Agent');
            $Time = date('Y-m-d H:i:s', time());
            $RocordInfo = [
                'login' => $Time,
                'ip' => $Ip,
                'device' => $Device,
                'email' => $Email,
            ];
            $this->UserServiceV2->SaveLoginRecord($RocordInfo);

            //製作token 
            $Token = $this->UserServiceV2->CreatToken($Email);
            return response()->json([
                'Err' => $this->Keys[0],
                'Message' => $this->Err[0],
                'token' => $Token
            ]);
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

    public function LogOut(Request $Request)
    {
        try {
            $Logout = $this->UserServiceV2->Logout();
            if (!$Logout) {
                return response()->json([
                    'Err' => $this->Keys[10],
                    'Message' => $this->Err[10]
                ]);
            }
            return response()->json([
                'Err' => $this->Keys[0],
                'Message' => $this->Err[0]
            ]);
        } catch (Throwable $e) {
            return response()->json([
                'Err' => $this->Keys[26],
                'Message' => $this->Err[26]
            ]);
        }
    }

    public function GetProfile()
    {
        try {
            $UserInfo = $this->UserServiceV2->GetUserInfo();
            return response()->json([
                'Err' => $this->Keys[0],
                'Message' => $this->Err[0],
                'UserInfo' => $UserInfo
            ]);
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

    public function GetRecord(Request $Request)
    {
        try {
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
            $Validator = Validator::make($Request->all(), $Ruls, $RulsMessage);
            if ($Validator->fails()) {
                return response()->json([
                    'Err' => array_search($Validator->Errors()->first(), $this->Err),
                    'Message' => $Validator->Errors()->first()
                ]);
            }

            //取得offset&limit預設
            $OffsetLimit = ['limit' => $Request['limit'], 'offset' => $Request['offset']];
            $OffsetLimit = $this->TotalService->GetOffsetLimit($OffsetLimit);

            //取得登入紀錄
            $Record = $this->UserServiceV2->GetRecord($OffsetLimit);
            return response()->json([
                'Err' => $this->Keys[0],
                'Message' => $this->Err[0],
                'Record_List' => $Record
            ]);
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

    public function AddFavorite(Request $Request)
    {
        try {
            //餐廳是否存在且啟用
            $Rid = $Request['rid'];
            $HasRestaurant = $this->UserServiceV2->CheckRestaurantInDatabase($Rid);
            if (!$HasRestaurant) {
                return response()->json([
                    'Err' => $this->Keys[16],
                    'Message' => $this->Err[16]
                ]);
            }

            //檢查使用者我的最愛資料表內是否超過20筆
            $CheckFavoriteTooMuch = $this->UserServiceV2->CheckFavoriteTooMuch();
            if ($CheckFavoriteTooMuch) {
                return response()->json([
                    'Err' => $this->Keys[28],
                    'Message' => $this->Err[28]
                ]);
            }

            //檢查是否重複新增我的最愛
            $CheckAlreadyAddFavorite = $this->UserServiceV2->RidExistFavorite($Rid);
            if ($CheckAlreadyAddFavorite) {
                return response()->json([
                    'Err' => $this->Keys[15],
                    'Message' => $this->Err[15]
                ]);
            }

            //新增至我的最愛
            $this->UserServiceV2->CreateFavorite($Rid);
            return response()->json([
                'Err' => $this->Keys[0],
                'Message' => $this->Err[0]
            ]);
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

    public function GetFavorite(Request $Request)
    {
        try {
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
            $Validator = Validator::make($Request->all(), $Ruls, $RulsMessage);
            if ($Validator->fails()) {
                return response()->json([
                    'Err' => array_search($Validator->Errors()->first(), $this->Err),
                    'Message' => $Validator->Errors()->first()
                ]);
            }

            //取得Offset&Limit預設
            $OffsetLimit = ['limit' => $Request['limit'], 'offset' => $Request['offset']];
            $OffsetLimit = $this->TotalService->GetOffsetLimit($OffsetLimit);

            //取的我的最愛
            $Favorite = $this->UserServiceV2->GetFavoriteInfo($OffsetLimit);
            return response()->json([
                'Err' => $this->Keys['0'],
                'Message' => $this->Err[0],
                'count' => $Favorite['count'],
                'data' => $Favorite['data']
            ]);
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

    public function DeleteFavorite(Request $Request)
    {
        try {
            //規則
            $Ruls = [
                'rid' => ['required', 'integer'],
            ];
            //什麼錯誤報什麼錯誤訊息
            $RulsMessage = [
                'rid.required' => '必填資料未填',
                'rid.integer' => '無效的範圍',
            ];
            //驗證輸入
            $Validator = Validator::make($Request->all(), $Ruls, $RulsMessage);
            if ($Validator->fails()) {
                return response()->json([
                    'Err' => array_search($Validator->Errors()->first(), $this->Err),
                    'Message' => $Validator->Errors()->first()
                ]);
            }

            //檢查此餐廳是否存在我的最愛內
            $Rid = $Request['rid'];
            $RidExistFavorite = $this->UserServiceV2->RidExistFavorite($Rid);
            if (!$RidExistFavorite) {
                return response()->json([
                    'Err' => $this->Keys[16],
                    'Message' => $this->Err[16]
                ]);
            }

            //將此餐廳從我的最愛內刪除
            $this->UserServiceV2->DeleteFavorite($Rid);
            return response()->json([
                'Err' => $this->Keys[0],
                'Message' => $this->Err[0]
            ]);
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

    public function GetHistory(Request $Request)
    {
        try {
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
            $Validator = Validator::make($Request->all(), $Ruls, $RulsMessage);
            if ($Validator->fails()) {
                return response()->json([
                    'Err' => array_search($Validator->Errors()->first(), $this->Err),
                    'Message' => $Validator->Errors()->first()
                ]);
            }

            //取得offset&limit預設
            $OffsetLimit = ['limit' => $Request['limit'], 'offset' => $Request['offset']];
            $OffsetLimit = $this->TotalService->GetOffsetLimit($OffsetLimit);

            //取出歷史紀錄餐廳的資訊
            $RestaurantInfo = $this->UserServiceV2->GetFavoriteRestaurantInfo($OffsetLimit);
            return response()->json([
                'Err' => $this->Keys[0],
                'Message' => $this->Err[0],
                'count' => $RestaurantInfo['count'],
                'data' => $RestaurantInfo['data']
            ]);
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
}
