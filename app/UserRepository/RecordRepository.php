<?php
namespace App\UserRepository;

use App\Models\User;
use App\Models\User_recode;
use App\TotalService;
use Illuminate\Support\Facades\Auth;

include_once "/var/www/html/din-ban-doan/app/TotalService.php";

class RecordRepository
{
    private $TotalService;
    public function __construct(TotalService $TotalService)
    {
        $this->TotalService = $TotalService;
    }
    public function GetRecord($offset, $limit)
    {
        
        $User = $this->TotalService->GetUserInfo();
        $UserId = $User->id;
        $Record = User_recode::select('ip', 'login', 'device')->offset($offset)->limit($limit)->orderBy('login', 'desc')->where('uid', '=', $UserId)->get();
        $Count = $Record->count();
        return response()->json(['count' => $Count, 'record' => $Record]);
    }
}



?>