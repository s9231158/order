<?php
namespace App\UserService;

use App\ErrorCodeService;
use App\TotalService;
use App\UserInterface\LogoutInterface;
use Illuminate\Support\Facades\Cache;
use Tymon\JWTAuth\Facades\JWTAuth;

include_once "/var/www/html/din-ban-doan/app/TotalService.php";

class LogoutService implements LogoutInterface
{
    private $ErrorCodeService;
    private $err = [];
    private $keys = [];
    public function __construct(ErrorCodeService $ErrorCodeService)
    {
        $this->ErrorCodeService = $ErrorCodeService;
        $this->err = $this->ErrorCodeService->GetErrCode();
        $this->keys = $this->ErrorCodeService->GetErrKey();
    }
    public function logout()
    {
        try {
            $UserInfo = JWTAuth::parseToken()->authenticate();
            $Email = $UserInfo->email;
            if (Cache::has($Email)) {
                Cache::forget($Email);
                return true;
            }
            if (Cache::has($Email) === false) {
                return response()->json(['err' => $this->keys[10], 'message' => $this->err[10]]);
            }

            return $UserInfo->email;
        } catch (\Throwable $e) {
            return response()->json(['err' => $this->keys[26], 'message' => $this->err[26]]);
        }
    }
}



?>