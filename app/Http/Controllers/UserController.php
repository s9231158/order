<?php

namespace App\Http\Controllers;

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
use App\Models\Restaurant;


class UserController extends Controller
{
    //錯誤訊息統整
    private $err = [
        '0' => 0, //成功
        '1' => 1, //資料填寫與規格不符
        '2' => 2, //必填資料未填
        '3' => 3, //email已註冊
        '4' => 4, //電話已註冊
        '5' => 5, //系統錯誤,請重新登入
        '6' => 6, //已登入
        '7' => 7, //短時間內登入次數過多
        '8' => 8, //帳號或密碼錯誤
        '9' => 9, //token錯誤
        '23' => 23, //無效的範圍
        '26' => 26, //系統錯誤
    ];

    public function create(Request $request)
    {
        //規則
        $ruls = [
            'name' => ['required', 'max:25', 'min:3', 'regex:/^[A-Za-z0-9\s]+$/'],
            'email' => ['required', 'unique:users,email', 'min:15', 'max:50', 'regex:/^[_a-z0-9-]+(\.[_a-z0-9-]+)*@[a-z0-9-]+(\.[a-z0-9-]+)*(\.[a-z]{2,})$/i'],
            'password' => ['required', 'min:10', 'max:25', 'regex:/^[A-Za-z0-9]+$/'],
            'phone' => ['required', 'string', 'size:9', 'regex:/^[0-9]+$/', 'unique:users,phone'],
            'address' => ['required', 'min:10', 'max:25'],
            'age' => ['required', 'before:2023-08-08', 'date'],
        ];
        //什麼錯誤報什麼錯誤訊息
        $rulsMessage = [
            'name.required' => $this->err['2'], 'name.max' => $this->err['1'], 'name.min' => $this->err['1'], 'name.regex' => $this->err['1'],
            'email.required' => $this->err['2'], 'email.unique' => $this->err['3'], 'email.min' => $this->err['1'], 'email.max' => $this->err['1'], 'email.regex' => $this->err['1'],
            'password.required' => $this->err['2'], 'password.min' => $this->err['1'], 'password.max' => $this->err['1'], 'password.regex' => $this->err['1'],
            'phone.required' => $this->err['2'], 'phone.string' => $this->err['1'], 'phone.size' => $this->err['1'], 'phone.regex' => $this->err['1'], 'phone.unique' => $this->err['4'],
            'address.required' => $this->err['2'], 'address.min' => $this->err['1'], 'address.max' => $this->err['1'],
            'age.required' => $this->err['2'], 'age.before' => $this->err['1'], 'age.date' => $this->err['1'],
        ];
        try {
            //驗證輸入數值
            $validator = Validator::make($request->all(), $ruls, $rulsMessage);
            //如果有錯回報錯誤訊息
            if ($validator->fails()) {
                return response()->json(['err' => $validator->errors()->first()]);
            }
            //沒錯的話存入資料庫
            else {
                $password = Hash::make($request->input('password'));
                $user = User::create([
                    'email' => $request->input('email'),
                    'name' => $request->input('name'),
                    'password' => $password,
                    'phone' => $request->input('phone'),
                    'address' => $request->input('address'),
                    'age' => $request->input('age'),
                ]);
                //將使用者關聯錢包初始化
                $wallet = new User_wallets();
                $wallet->balance = 0;
                $user->wallet()->save($wallet);
                return response()->json(['err' => $this->err['0']]);
            }
        } catch (Throwable) {
            return response()->json(['err' => $this->err['26']]);
        }
    }



    public function login(Request $request)
    {
        //規則
        $ruls = [
            'email' => ['required', 'min:15', 'max:50', 'regex:/^[_a-z0-9-]+(\.[_a-z0-9-]+)*@[a-z0-9-]+(\.[a-z0-9-]+)*(\.[a-z]{2,})$/i'],
            'password' => ['required', 'min:10', 'max:25', 'regex:/^[A-Za-z0-9]+$/'],
        ];
        //什麼錯誤報什麼錯誤訊息
        $rulsMessage = [
            'email.required' => $this->err['2'], 'email.min' => $this->err['1'], 'email.max' => $this->err['1'], 'email.regex' => $this->err['1'],
            'password.required' => $this->err['2'], 'password.min' => $this->err['1'], 'password.max' => $this->err['1'], 'password.regex' => $this->err['1']
        ];
        try {
            $ip = $request->ip();
            $email = $request->email;

            //驗證輸入數值
            $validator = Validator::make($request->all(), $ruls, $rulsMessage);
            //驗證失敗回傳錯誤訊息
            if ($validator->fails()) {
                return response()->json(['err' => $validator->errors()->first()]);
            }
            //從redis檢查key是否超過次數
            if (RateLimiter::tooManyAttempts($this->makekey($email, $ip), 5, 1)) {
                return response()->json(['err' => $this->err['7']]);
            }
            //檢查是否有該使用者且密碼符合
            if (Auth::attempt($request->only('email', 'password'))) {
                $token = $request->header('Authorization');
                $redietoken = 'Bearer ' . Cache::get($email);
                if (Cache::has($email) && $token === $redietoken) {
                    return response()->json(['err' => $this->err['6']]);
                }
                // 清除該key錯誤次數
                RateLimiter::clear($this->makekey($email, $ip));
                //取得使用者elequent
                $user = User::find(Auth::user()->id);
                //取得使用者登入資訊
                $device = $request->header('User-Agent');
                $login = date('Y-m-d H:i:s', time());
                //將使用者登入資訊存入該使用者user_recode
                $recode = new User_recode([
                    'login' => $login,
                    'ip' => $ip,
                    'device' => $device,
                ]);
                //取得使用者資訊製作payload
                $id = $user->id;
                $name = $user->name;
                $time = Carbon::now()->addDay();
                $user->recode()->save($recode);
                //payload資訊
                $userClaims = [
                    'id' => $id,
                    'name' => $name,
                    'email' => $email,
                    'exp' => $time
                ];
                //將payload 製成token
                $token = JWTAuth::claims($userClaims)->fromUser($user);
                //設定redis內存活時間
                Cache::put($email, $token, 60 * 60 * 24);
                $value = Cache::get($email);
                return response()->json(['err' => $this->err['0'], 'token' => $token]);
            }
            RateLimiter::hit($this->makekey($email, $ip));
            return response()->json(['err' => $this->err['8']]);
        } catch (Exception $e) {
            return response()->json(['err' => $this->err['5']]);
        } catch (Throwable) {
            return response()->json(['err' => $this->err['26']]);
        }
    }



    public function logout(Request $request)
    {

        try {
            JWTAuth::parseToken()->authenticate();
            $payload = JWTAuth::getpayload();
            $email = $payload['email'];
            $token = $request->header('Authorization');
            $redietoken = 'Bearer ' . Cache::get($email);
            if (Cache::has($email) && $token == $redietoken) {
                Cache::forget($email);
                return response()->json(['err' => $this->err['0']]);
            } else {
                Cache::forget($email);
                return response()->json(['err' => $this->err['0']]);
            }
        } catch (Exception) {
            Cache::forget($email);
            return response()->json(['err' => $this->err['0']]);
        } catch (Throwable) {
            return response()->json(['err' => $this->err['26']]);
        }
    }

    public function profile()
    {
        try {
            $user = JWTAuth::parseToken()->authenticate();
            $email = $user->email;
            $name = $user->name;
            $address = $user->address;
            $phone = $user->phone;
            $age = $user->age;
            $result = ['err' => $this->err['0'], 'email' => $email, 'name' => $name, 'address' => $address, 'phone' => $phone, 'age' => $age];
            return response()->json($result);
        } catch (Exception) {
            return response()->json(['err' => $this->err['5']]);
        } catch (Throwable) {
            return response()->json(['err' => $this->err['26']]);
        }
    }

    public function record(Request $request)
    {
        //規則
        $ruls = [
            'limit' => ['regex:/^[0-9]+$/'],
            'offset' => ['regex:/^[0-9]+$/'],
        ];
        //什麼錯誤報什麼錯誤訊息
        $rulsMessage = [
            'limit.regex' => $this->err['23'],
            'offset.regex' => $this->err['23']
        ];
        try {
            //設定limit與offset預設
            if ($request->limit === null) {
                $limit = 20;
            } else {
                $limit = $request->limit;
            }
            if ($request->offset === null) {
                $offset = 0;
            } else {
                $offset = $request->offset;
            }
            if ($limit || $offset) {
                //驗證輸入數值
                $validator = Validator::make($request->all(), $ruls, $rulsMessage);
                //驗證失敗回傳錯誤訊息
                if ($validator->fails()) {
                    return response()->json(['err' => $validator->errors()->first()]);
                }
                //取得使用者紀錄
                $user = User::find(Auth::id());
                $recode = $user->recode()->select('ip', 'login', 'device')->offset($offset)->limit($limit)->orderBy('login', 'desc')->get();
                $count = $user->recode()->get()->count();
                return response()->json(['err' => $this->err['0'], 'count' => $count, 'data' => $recode]);
            }
        } catch (Exception) {
            return response()->json(['err' => $this->err['26']]);
        } catch (Throwable) {
            return response()->json(['err' => $this->err['26']]);
        }
    }


    public function favorite(Request $request)
    {
        try {
            $a = JWTAuth::parseToken()->authenticate();
            $email = $a->email;
            $rid = $request->rid;
            $user = User::find(Auth::id());
            $databaserestruant = Restaurant::select('id')->where('id', '=', $rid)->count();
            if ($databaserestruant === 0) {
                return response()->json(['err' => $this->err['16']]);
            }
            $exzest = $user->favorite()->select('rid')->where('rid', '=', $rid)->get()->count();
            $count = $user->favorite()->count();
            if ($count >= 20) {
                return response()->json(['err' => $this->err['28']]);
            }
            if (!$exzest) {
                $user->favorite()->attach($rid);
                return response()->json(['err' => 0]);
            } else {
                return response()->json(['err' => $this->err['15']]);
            }
        } catch (PDOException $e) {
            return response()->json([$databaserestruant, $e, 'err' => $this->err['1']]);
        } catch (Exception $e) {
            return response()->json([$e, 'err' => $this->err['26']]);
        } catch (Throwable) {
            return response()->json(['err' => $this->err['26']]);
        }
    }

    public function getfavorite(Request $request)
    {
        //規則
        $ruls = [
            'limit' => ['regex:/^[0-9]+$/'],
            'offset' => ['regex:/^[0-9]+$/'],
        ];
        //什麼錯誤報什麼錯誤訊息
        $rulsMessage = [
            'limit.regex' => $this->err['23'],
            'offset.regex' => $this->err['23']
        ];
        try {

            $validator = Validator::make($request->all(), $ruls, $rulsMessage);
            //驗證失敗回傳錯誤訊息
            if ($validator->fails()) {
                return response()->json(['err' => $validator->errors()->first()]);
            }
            if ($request->limit === null) {
                $limit = 20;
            } else {
                $limit = $request->limit;
            }
            if ($request->offset === null) {
                $offset = 0;
            } else {
                $offset = $request->offset;
            }
            $user = User::find(Auth::id());
            $count = $user->favorite()->count();
            $result = $user->favorite()->select('rid', 'totalpoint', 'countpoint', 'title', 'img')->limit($limit)->offset($offset)->orderBy('user_favorites.created_at', 'desc')->get()->map(function ($item) {
                unset($item->pivot);
                return $item;
            });
            return response()->json(['err' => $this->err['0'], 'count' => $count, 'data' => $result]);
        } catch (Exception $e) {
            return response()->json([$e, 'err' => $this->err['26']]);
        } catch (Throwable) {
            return response()->json(['err' => $this->err['26']]);
        }
    }

    public function deletefavorite(Request $request)
    {
        try {
            $rid = $request->rid;
            $user = User::find(Auth::id());
            $myfavorite = $user->favorite()->where('rid', '=', $rid)->count();
            if ($myfavorite) {
                $user->favorite()->detach($rid);
                return response()->json(['err' => $this->err['0']]);
            } else {
                return response()->json(['err' => $this->err['16']]);
            }
        } catch (PDOException) {
            return response()->json(['err' => $this->err['1']]);
        } catch (Exception) {
            return response()->json(['err' => $this->err['26']]);
        }
    }

    public function history(Request $request)
    {
        //規則
        $ruls = [
            'limit' => ['regex:/^[0-9]+$/'],
            'offset' => ['regex:/^[0-9]+$/'],
        ];
        //什麼錯誤報什麼錯誤訊息
        $rulsMessage = [
            'limit.regex' => $this->err['23'],
            'offset.regex' => $this->err['23']
        ];
        try {

            $validator = Validator::make($request->all(), $ruls, $rulsMessage);
            //驗證失敗回傳錯誤訊息
            if ($validator->fails()) {
                return response()->json(['err' => $validator->errors()->first()]);
            }
            if ($request->limit === null) {
                $limit = 20;
            } else {
                $limit = $request->limit;
            }
            if ($request->offset === null) {
                $offset = 0;
            } else {
                $offset = $request->offset;
            }
            $user = User::find(Auth::id());
            $count = $user->history()->count();
            $result = $user->history()->select('rid', 'totalpoint', 'countpoint', 'title', 'img')->limit($limit)->offset($offset)->orderBy('restaurant_histories.updated_at', 'desc')->get()->map(function ($item) {
                unset($item->pivot);
                return $item;
            });

            return response()->json(['err' => $this->err['0'], 'count' => $count, 'data' => $result]);
        } catch (Exception $e) {
            return $e;
        } catch (Throwable $e) {
            return response()->json([$e, 'err' => $this->err['26']]);
        }
    }


    protected function makekey($email, $ip)
    {
        return Str::lower($email) . '|' . $ip;
    }
}
