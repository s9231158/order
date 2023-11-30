<?php
namespace App\UserService;

use App\ErrorCodeService;
use App\Models\User;
use App\TotalService;
use App\UserInterface\LoginInterface;
use App\UserRepository\LoginRepository;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Throwable;
use Tymon\JWTAuth\Facades\JWTAuth;

include_once "/var/www/html/din-ban-doan/app/TotalService.php";

class LoginService implements LoginInterface
{
    private $LoginRepository;
    private $ErrorCodeService;
    private $err = [];
    private $keys = [];
    private $key = '';
    private $ruls = [];
    private $rulsMessage = [];
    public function __construct(LoginRepository $LoginRepository, ErrorCodeService $ErrorCodeService)
    {
        $this->LoginRepository = $LoginRepository;
        $this->ErrorCodeService = $ErrorCodeService;
        $this->err = $this->ErrorCodeService->GetErrCode();
        $this->keys = $this->ErrorCodeService->GetErrKey();
        $this->ruls = [
            'email' => ['required', 'min:15', 'max:50', 'regex:/^[_a-z0-9-]+(\.[_a-z0-9-]+)*@[a-z0-9-]+(\.[a-z0-9-]+)*(\.[a-z]{2,})$/i'],
            'password' => ['required', 'min:10', 'max:25', 'regex:/^[A-Za-z0-9]+$/'],
        ];
        $this->rulsMessage = [
            'email.required' => $this->keys[2],
            'email.min' => $this->keys[1],
            'email.max' => $this->keys[1],
            'email.regex' => $this->keys[1],
            'password.required' => $this->keys[2],
            'password.min' => $this->keys[1],
            'password.max' => $this->keys[1],
            'password.regex' => $this->keys[1]
        ];
    }
    public function LoginValidator($Request)
    {
        try {
            $validator = Validator::make($Request->all(), $this->ruls, $this->rulsMessage);
            if ($validator->fails()) {
                return response()->json(['err' => $validator->errors()->first(), 'message' => $this->err[$validator->errors()->first()]]);
            }
            return true;
        } catch (Throwable $e) {
            return response()->json(['err' => $this->keys[26], 'message' => $this->err[26]]);
        }
    }

    public function LoginCheckTooManyAttempts($MakeKeyInfo)
    {
        try {
            $this->key = $this->MakeKey($MakeKeyInfo['email'], $MakeKeyInfo['ip']);
            if (RateLimiter::tooManyAttempts($this->key, 5)) {
                return response()->json(['err' => $this->keys[7], 'message' => $this->err[7]]);
            }
            return true;
        } catch (Throwable $e) {
            return response()->json(['err' => $this->keys[26], 'message' => $this->err[26]]);
        }
    }
    public function LoginCheckAccountPassword($Account)
    {
        try {
            if (Auth::attempt($Account)) {
                return true;
            }
            RateLimiter::hit($this->key);
            return response()->json(['err' => $this->keys[8], 'message' => $this->err[8]]);

        } catch (Throwable $e) {
            return response()->json([$e, 'err' => $this->keys[26], 'message' => $this->err[26]]);
        }
    }

    public function CheckHasLogin($TokenEmail)
    {
        try {
            $result = TotalService::CheckHasLogin($TokenEmail);
            if ($result === true) {
                return $result;
            }
            if ($result === 5) {
                return response()->json(['err' => $this->keys[5], 'message' => $this->err[5]]);
            } else {
                return response()->json(['err' => $this->keys[26], 'message' => $this->err[26]]);
            }
        } catch (Throwable $e) {
            return response()->json(['err' => $this->keys[26], 'message' => $this->err[26]]);
        }
    }

    public function CreatrLoginRecord($RocordInfo)
    {
        try {
            $UserId = Auth::id();
            $RocordInfo['uid'] = $UserId;
            $LoginRepository = $this->LoginRepository->CreatrLoginRecord($RocordInfo);
            if ($LoginRepository === true) {
                return $LoginRepository;
            }
        } catch (Throwable $e) {
            return response()->json(['err' => $this->keys[26], 'message' => $this->err[26]]);
        }

    }

    public function CreateToken()
    {
        try {



            $user = User::find(Auth::id());
            $id = $user->id;
            $name = $user->name;
            $email = $user->email;
            $time = Carbon::now()->addDay();

            $userClaims = [
                'id' => $id,
                'name' => $name,
                'email' => $email,
                'exp' => $time
            ];

            $token = JWTAuth::claims($userClaims)->fromUser($user);
            Cache::put($email, $token, 60 * 60 * 24);
            return true;
        } catch (Throwable $e) {
            return response()->json([$e, 'err' => $this->keys[26], 'message' => $this->err[26]]);

        }

    }
    public function MakeKey($Email, $Ip)
    {
        return Str::lower($Email) . '|' . $Ip;
    }
}


?>