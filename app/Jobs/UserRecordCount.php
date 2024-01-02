<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Carbon;
use App\Models\User_recode;
use App\Models\LoginTotal as Login_Total;

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
        $List = [];
        for ($I = 0; $I < 24; $I++) {
            $Count = $UserRecodeInfo->whereBetween('created_at', [$Go, $To])->count();
            //取出資料
            if ($Count !== 0) {
                $List[] = [
                    'count' => $Count,
                    'starttime' => $Go->copy(),
                    'endtime' => $To->copy(),
                    'created_at' => Carbon::now(),
                    'updated_at' => Carbon::now()
                ];
            }
            //取出昨天00:00
            $Go = $Go->addHour();
            //取出昨天01:00
            $To = $To->addHour();
        }
        //存至資料庫
        Login_Total::insert($List);
    }
}
