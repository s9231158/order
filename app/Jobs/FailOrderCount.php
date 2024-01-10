<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Carbon;
use App\Models\FailOrderCount as Fail_Order_Count;
use App\Models\Order;

class FailOrderCount implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;
    private $orderService;
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
        //取出昨天至今天所有訂單資料
        $order = Order::select('status', 'created_at')->whereBetween('created_at', [$start, $end])->get();
        $yesterday = Carbon::yesterday();
        $yesterdayAddHour = Carbon::yesterday()->addHour();
        $orderList = [];
        for ($i = 0; $i < 24; $i++) {
            //取得每小時失敗的所有訂單
            $everyHourFailOrder = $order->whereBetween('created_at', [$yesterday, $yesterdayAddHour])
                ->where('status', '!=', 0);
            //取得每小時的所有訂單
            $everyHourOrder = $order->whereBetween('created_at', [$yesterday, $yesterdayAddHour])
                ->where('status', '=', 0);
            //取得每小時失敗訂單次數
            $everyHourFailOrderCount = $everyHourFailOrder->count();
            //取得每小時訂單次數
            $everyHourOrderCount = $everyHourOrder->count();
            if ($everyHourFailOrderCount !== 0) {
                $orderList[] = ['starttime' => $yesterday->copy(),
                    'endtime' => $yesterdayAddHour->copy(),
                    'failcount' => $everyHourFailOrderCount,
                    'totalcount' => $everyHourOrderCount,
                    'created_at' => Carbon::now(),
                    'updated_at' => Carbon::now(),
                ];
            }
            //對起始時間加一小
            $yesterday = $yesterday->addHour();
            //對終止時間加一小
            $yesterdayAddHour = $yesterdayAddHour->addHour();
        }
        //存入資料庫
        Fail_Order_Count::insert($orderList);
    }
}
