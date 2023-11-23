<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Models\User;
use Illuminate\Support\Carbon;
use App\Models\User_recode;
use App\Models\Login_Total;

class UserRecordCount implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct()
    {

    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        //取出昨天00:00
        $Start = Carbon::yesterday();
        //取出今天00:00
        $End = Carbon::today();
        //取出昨天至今天所有資料
        $UserRecodeInfo = User_recode::select('created_at')->whereBetween('login', [$Start, $End])->get();
        //取出昨天00:00
        $Go = Carbon::yesterday();
        //取出昨天01:00
        $To = Carbon::yesterday()->addHour();
        $I = 0;
        for ($I = 0; $I < 24; $I++) {
            $Count = $UserRecodeInfo->whereBetween('created_at', [$Go, $To])->count();
            //儲存資料
            $Login_Total = new Login_Total();
            $Login_Total->count = $Count;
            $Login_Total->starttime = $Go;
            $Login_Total->endtime = $To;
            $Login_Total->save();
            //取出昨天00:00
            $Go = $Go->addHour();
            //取出昨天01:00
            $To = $To->addHour();
        }
    }
}