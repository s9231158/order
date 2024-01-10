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
        $start = Carbon::yesterday();
        //取出今天00:00
        $end = Carbon::today();
        //取出昨天至今天所有資料
        $userRecodeInfo = User_recode::select('created_at')->whereBetween('login', [$start, $end])->get();
        //取出昨天00:00
        $go = Carbon::yesterday();
        //取出昨天01:00
        $to = Carbon::yesterday()->addHour();
        $i = 0;
        $list = [];
        for ($i = 0; $i < 24; $i++) {
            $count = $userRecodeInfo->whereBetween('created_at', [$go, $to])->count();
            //取出資料
            if ($count !== 0) {
                $list[] = [
                    'count' => $count,
                    'starttime' => $go->copy(),
                    'endtime' => $to->copy(),
                    'created_at' => Carbon::now(),
                    'updated_at' => Carbon::now()
                ];
            }
            //取出昨天00:00
            $go = $go->addHour();
            //取出昨天01:00
            $to = $to->addHour();
        }
        //存至資料庫
        Login_Total::insert($list);
    }
}
