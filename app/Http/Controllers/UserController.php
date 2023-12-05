<?php

namespace App\Http\Controllers;

use App\CustomerService;
use App\ErrorCodeService;
use App\Service\RestaurantHistoryService;
use App\Service\RestaurantService;
use App\TotalService;
use App\UserInterface\FavoriteInterface;
use App\UserInterface\LoginInterface;
use App\UserInterface\LogoutInterface;
use App\UserInterface\RecordInerface;
use App\UserRepository\RecordRepository;
use App\UserService\LogoutService;
use Tymon\JWTAuth\Facades\JWTAuth;
use App\Models\User;
use App\Models\User_recode;
use App\Models\User_wallets;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use Throwable;
use PDOException;
use App\UserService;
use App\Models\Restaurant;
use App\UserService\CreateService;
use App\UserRepository\CreateRepository;
use App\UserInterface\CreateInrerface;

include_once "/var/www/html/din-ban-doan/app/TotalService.php";


class UserController extends Controller
{
    //錯誤訊息統整
    private $err = [];
    private $keys = [];

    private $CreatrService;
    private $LoginService;
    private $LogoutService;
    private $RecordService;
    private $FavoriteService;
    private $TotalService;
    private $RestaurantService;

    private $RecordHistoryService;
    public function __construct(ErrorCodeService $ErrorCodeService, LoginInterface $LoginService, CreateInrerface $CreatrService, LogoutInterface $LogoutService, RecordInerface $RecordService, FavoriteInterface $FavoriteService, TotalService $TotalService, RestaurantHistoryService $RecordHistoryService, RestaurantService $RestaurantService)
    {
        $this->keys = $ErrorCodeService->GetErrKey();
        $this->err = $ErrorCodeService->GetErrCode();
        $this->TotalService = $TotalService;
        $this->CreatrService = $CreatrService;
        $this->LoginService = $LoginService;
        $this->LogoutService = $LogoutService;
        $this->RecordService = $RecordService;
        $this->FavoriteService = $FavoriteService;
        $this->RecordHistoryService = $RecordHistoryService;
        $this->RestaurantService = $RestaurantService;
    }
    public function create(Request $request)
    {
        try {
            //驗證輸入格式
            $validator = $this->CreatrService->CreateValidator($request);
            if ($validator != null) {
                return $validator;
            }
            //沒錯的話存入資料庫
            else {
                //Request將資料取出
                $UserInfo = [
                    'password' => $request->password,
                    'email' => $request->email,
                    'name' => $request->name,
                    'phone' => $request->phone,
                    'address' => $request->address,
                    'age' => $request->age
                ];
                // 將使用者資訊存入Users
                if ($this->CreatrService->CreateUser($UserInfo) === null) {
                    return response()->json(['err' => $this->keys[26], 'message' => $this->err[26]]);
                }
                // 將使用者資訊存入UserWallet
                if ($this->CreatrService->CreateWallet($UserInfo['email']) === null) {
                    return response()->json(['err' => $this->keys[26], 'message' => $this->err[26]]);
                }
                return response()->json(['err' => $this->keys[0], 'message' => $this->err[0]]);
            }
        } catch (Exception $e) {
            return response()->json(['err' => $this->keys[26], 'message' => $this->err[26]]);
        } catch (Throwable $e) {
            return response()->json([$e, 'err' => $this->keys[26], 'message' => $this->err[26]]);
        }
    }



    public function login(Request $request)
    {
        try {
            $ip = $request->ip();
            $email = $request->email;
            $Token = $request->header('Authorization');
            $MakeKeyInfo = ['ip' => $request->ip(), 'email' => $email, 'count' => 5];

            //new 驗證輸入
            $validator = $this->LoginService->LoginValidator($request);
            if ($validator !== true) {
                return $validator;
            }

            //new 檢查此組Key是否一定時間內登入多次
            $CheckTooManyAttempts = $this->LoginService->LoginCheckTooManyAttempts($MakeKeyInfo);
            if ($CheckTooManyAttempts !== true) {
                return $CheckTooManyAttempts;
            }

            //new 檢查是否有重複登入
            $TokenEmail = ['Token' => $Token, 'Email' => $email];
            $CheckHasLogin = $this->LoginService->CheckHasLogin($TokenEmail);
            if ($CheckHasLogin !== true) {
                return $CheckHasLogin;
            }

            //new 驗證帳號密碼
            $EmailPassword = ['email' => $email, 'password' => $request->password];
            $LoginCheckAccountPassword = $this->LoginService->LoginCheckAccountPassword($EmailPassword);
            if ($LoginCheckAccountPassword !== true) {
                return $LoginCheckAccountPassword;
            }

            //new 將使用者登入資訊存入該使用者user_recode
            $device = $request->header('User-Agent');
            $login = date('Y-m-d H:i:s', time());
            $RocordInfo = [
                'login' => $login,
                'ip' => $ip,
                'device' => $device,
                'email' => $email,
            ];
            $CreatrLoginRecord = $this->LoginService->CreatrLoginRecord($RocordInfo);
            if ($CreatrLoginRecord !== true) {
                return $CreatrLoginRecord;
            }

            //製作token 
            $CreateToken = $this->LoginService->CreateToken($email);
            if ($CreateToken == !true) {
                $CreateToken;
            }
            return $CreateToken;
        } catch (Exception $e) {
            return response()->json([$e, 'err' => $this->keys[5], 'message' => $this->err[5]]);
        } catch (Throwable $e) {
            return response()->json([$e, 'err' => $this->keys[26], 'message' => $this->err[26]]);
        }
    }



    public function logout(Request $request)
    {
        try {
            $LogoutService = $this->LogoutService->Logout();
            if ($LogoutService === true) {
                return response()->json(['err' => $this->keys[0], 'message' => $this->err[0]]);
            }
            return $LogoutService;
        } catch (Throwable $e) {
            return response()->json(['err' => $this->keys[26], 'message' => $this->err[26]]);
        }
    }

    public function profile()
    {
        try {
            $user = $this->TotalService->GetUserInfo();
            $email = $user->email;
            $name = $user->name;
            $address = $user->address;
            $phone = $user->phone;
            $age = $user->age;
            return response()->json(['err' => $this->keys[0], 'message' => $this->err[0], 'email' => $email, 'name' => $name, 'address' => $address, 'phone' => $phone, 'age' => $age]);
        } catch (Exception) {
            return response()->json(['err' => $this->keys[5], 'message' => $this->err[5]]);
        } catch (Throwable) {
            return response()->json(['err' => $this->keys[26], 'message' => $this->err[26]]);
        }
    }

    public function record(Request $request)
    {
        try {
            //驗證輸入數值
            $Vaildator = $this->RecordService->Validator($request);
            if ($Vaildator !== true) {
                return $Vaildator;
            }
            //設定limit與offset預設
            $OffsetLimit = ['offset' => $request->offset, 'limit' => $request->limit];
            $OffsetLimit = $this->RecordService->GetOffsetLimit($OffsetLimit);
            $offset = $OffsetLimit['offset'];
            $limit = $OffsetLimit['limit'];
            //取得登入紀錄
            return $this->RecordService->GetRecord($offset, $limit);
        } catch (Exception) {
            return response()->json(['err' => $this->keys[26], 'message' => $this->err[26]]);
        } catch (Throwable) {
            return response()->json(['err' => $this->keys[26], 'message' => $this->err[26]]);
        }
    }


    public function favorite(Request $request)
    {
        try {
            //檢查此餐廳是否在資料表內
            $Rid = $request->rid;
            $CheckRestaurantInDatabase = TotalService::CheckRestaurantInDatabase($Rid);
            if ($CheckRestaurantInDatabase === 0) {
                return response()->json(['err' => $this->keys[16], 'message' => $this->err[16]]);
            }

            //檢查使用者我的最愛資料表內是否超過20筆
            $CheckFavoriteTooMuch = $this->FavoriteService->CheckFavoriteTooMuch();
            if ($CheckFavoriteTooMuch !== true) {
                return $CheckFavoriteTooMuch;
            }

            //檢查是否重複新增我的最愛
            $CheckAlreadyAddFavorite = $this->FavoriteService->CheckAlreadyAddFavorite($Rid);
            if ($CheckAlreadyAddFavorite !== true) {
                return $CheckAlreadyAddFavorite;
            }

            //新增至我的最愛
            $AddFavorite = $this->FavoriteService->AddFavorite($Rid);
            if ($AddFavorite !== true) {
                return $AddFavorite;
            }
            return response()->json(['err' => $this->keys[0], 'message' => $this->err[0]]);
        } catch (Exception $e) {
            return response()->json(['err' => $this->keys['26'], 'message' => $this->err[26]]);
        } catch (Throwable) {
            return response()->json(['err' => $this->keys['26'], 'message' => $this->err[26]]);
        }
    }

    public function getfavorite(Request $request)
    {
        try {

            //驗證輸入數值
            $Validator = $this->FavoriteService->LimitOffsetValidator($request);
            if ($Validator !== true) {
                return $Validator;
            }

            //設定limit與offset預設
            $OffsetLimit = ['offset' => $request->offset, 'limit' => $request->limit];
            $OffsetLimit = $this->FavoriteService->GetOffsetLimit($OffsetLimit);

            //取的我的最愛
            $UserFavorite = $this->FavoriteService->GetUserFavorite($OffsetLimit);
            return response()->json(['err' => $this->keys['0'], 'message' => $this->err[0], 'count' => $UserFavorite->original['count'], 'data' => $UserFavorite->original['data']]);
        } catch (Exception $e) {
            return response()->json(['err' => $this->keys['26'], 'message' => $this->err[26]]);
        } catch (Throwable $e) {
            return response()->json(['err' => $this->keys['26'], 'message' => $this->err[26]]);
        }
    }

    public function deletefavorite(Request $request)
    {
        try {
            $Rid = $request->rid;
            $Deletefavorite = $this->FavoriteService->DeleteFavorite($Rid);
            return $Deletefavorite;
        } catch (PDOException) {
            return response()->json(['err' => $this->err['1']]);
        } catch (Exception) {
            return response()->json(['err' => $this->err['26']]);
        } catch (Throwable) {
            return response()->json(['err' => $this->err['26']]);
        }
    }

    public function history(Request $request)
    {
        try {
            //Validator
            $Validator = $this->TotalService->LimitOffsetValidator($request);
            if ($Validator !== true) {
                return $Validator;
            }

            //取得OffsetLimit
            $OffsetLimit = ['offset' => $request->offset, 'limit' => $request->limit];
            $OffsetLimit = $this->TotalService->GetOffsetLimit($OffsetLimit);

            //從token取出個人資料
            $UserInfo = $this->TotalService->GetUserInfo();
            $UserId[] = $UserInfo['id'];

            //取出歷史紀錄餐廳id
            $RestaurantHistoryId = $this->RecordHistoryService->GetRestaurantHistory($UserId, $OffsetLimit)->toArray();

            //將歷史紀錄餐廳id轉換為Array
            $ArrayRestaurantHistoryId = array_map(function ($item) {
                return $item['rid'];
            }, $RestaurantHistoryId);

            //取出歷史紀錄餐廳的資訊
            $RestaurantInfo = $this->RestaurantService->GetRestaurantInfo($ArrayRestaurantHistoryId);

            //取出回傳筆數
            $RestaurantInfoCount = $RestaurantInfo->count();

            //取出回傳需要資料
            $NeedRestaurantInfo = $RestaurantInfo->map->only(['id', 'totalpoint', 'countpoint', 'title', 'img']);

            //回傳
            return response()->json(['message' => $this->keys[0], 'err' => $this->err['0'], 'count' => $RestaurantInfoCount, 'data' => $NeedRestaurantInfo]);
        } catch (Exception $e) {
            return response()->json(['err' => $this->keys['26'], 'message' => $this->err[26]]);
        } catch (Throwable $e) {
            return response()->json(['err' => $this->keys['26'], 'message' => $this->err[26]]);
        }
    }


    protected function makekey($email, $ip)
    {
        return Str::lower($email) . '|' . $ip;
    }
}
