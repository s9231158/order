<?php
namespace App\UserService;

use App\ErrorCodeService;
use App\UserInterface\RecordInerface;
use App\UserRepository\RecordRepository;
use Illuminate\Support\Facades\Validator;
use App\TotalService;

class RecordService implements RecordInerface
{

    private $ErrorCodeService;
    private $err = [];
    private $keys = [];
    private $RecordRepository;
    private $TotalService;
    public function __construct(ErrorCodeService $ErrorCodeService, RecordRepository $RecordRepository, TotalService $TotalService)
    {
        $this->ErrorCodeService = $ErrorCodeService;
        $this->err = $this->ErrorCodeService->GetErrCode();
        $this->keys = $this->ErrorCodeService->GetErrKey();
        $this->RecordRepository = $RecordRepository;
        $this->TotalService = $TotalService;

    }
    public function Validator($Request)
    {
        //規則
        $ruls = [
            'limit' => ['regex:/^[0-9]+$/'],
            'offset' => ['regex:/^[0-9]+$/'],
        ];
        //什麼錯誤報什麼錯誤訊息
        $rulsMessage = [
            'limit.regex' => $this->keys['23'],
            'offset.regex' => $this->keys['23']
        ];
        $validator = Validator::make($Request->all(), $ruls, $rulsMessage);
        //驗證失敗回傳錯誤訊息
        if ($validator->fails()) {
            return response()->json(['err' => $validator->errors()->first(), 'message' => $this->err[$validator->errors()->first()]]);
        }
        return true;
    }
    public function GetRecord($offset, $limit)
    {
        try {
            $Record = $this->RecordRepository->GetRecord($offset, $limit);
            return response()->json(['err' => $this->keys[0], 'message' => $this->err[0], 'count' => $Record->original['count'], 'record' => $Record->original['record']]);
        } catch (\Throwable $e) {

        }

    }

    public function GetOffsetLimit($OffsetLimit)
    {
        try {
            $GetOffsetLimit = $this->TotalService->GetOffsetLimit($OffsetLimit);
            return $GetOffsetLimit;
        } catch (\Throwable $e) {
            return response()->json(['err' => $this->keys[26], 'message' => $this->err[26]]);
        }
    }




}


?>