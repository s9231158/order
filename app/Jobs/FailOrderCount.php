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
    private $OrderService;
    /**
     * Create a new job instance.
     *
     * @return void
     */
    private $Order;
    private $OrderRepositoryV2;
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
        //取出昨天至今天所有訂單資料
        $Order = Order::select('status', 'created_at')->whereBetween('created_at', [$Start, $End])->get();
        $Yesterday = Carbon::yesterday();
        $YesterdayAddHour = Carbon::yesterday()->addHour();
        $OrderList = [];
        for ($I = 0; $I < 24; $I++) {
            //取得每小時失敗的所有訂單
            $EveryHourFailOrder = $Order->whereBetween('created_at', [$Yesterday, $YesterdayAddHour])
                ->where('status', '!=', 0);
            //取得每小時的所有訂單
            $EveryHourOrder = $Order->whereBetween('created_at', [$Yesterday, $YesterdayAddHour])
                ->where('status', '=', 0);
            //取得每小時失敗訂單次數
            $EveryHourFailOrderCount = $EveryHourFailOrder->count();
            //取得每小時訂單次數
            $EveryHourOrderCount = $EveryHourOrder->count();
            if ($EveryHourFailOrderCount !== 0) {
                $OrderList[] = ['starttime' => $Yesterday->copy(),
                    'endtime' => $YesterdayAddHour->copy(),
                    'failcount' => $EveryHourFailOrderCount,
                    'totalcount' => $EveryHourOrderCount,
                    'created_at' => Carbon::now(),
                    'updated_at' => Carbon::now(),
                ];
            }
            //對起始時間加一小
            $Yesterday = $Yesterday->addHour();
            //對終止時間加一小
            $YesterdayAddHour = $YesterdayAddHour->addHour();
        }
        //存入資料庫
        Fail_Order_Count::insert($OrderList);
    }
}
