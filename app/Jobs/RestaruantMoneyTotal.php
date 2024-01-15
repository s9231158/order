<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Carbon;
use App\Models\RestaruantTotalMoney as Restaruant_Total_Money;
use Illuminate\Support\Facades\Cache;

class RestaruantMoneyTotal implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct()
    {
        //
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $restaurantTotalMoney = Cache::get('restaurant_total_money');
        $go = Carbon::today();
        $to = Carbon::today()->addHour();
        $list = [];
        for ($i = 0; $i < 25; $i++) {
            if (!$restaurantTotalMoney[$i]) {
                $go = $go->addHour();
                $to = $to->addHour();
                continue;
            }
            foreach ($restaurantTotalMoney[$i] as $key => $value) {
                $list[] = [
                    'rid' => $key,
                    'money' => $value,
                    'starttime' => $go->copy(),
                    'endtime' => $to->copy(),
                    'created_at' => Carbon::now(),
                    'updated_at' => Carbon::now()
                ];
            }
            $go = $go->addHour();
            $to = $to->addHour();
        }
        //存入資料庫
        Restaruant_Total_Money::insert($list);
    }
}