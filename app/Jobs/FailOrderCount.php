<?php

namespace App\Jobs;

use App\Service\OrderService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Carbon;
use App\Models\Order;
use App\Models\Fail_Order_Count;

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
    public function __construct(OrderService $OrderService)
    {
        // //取出昨天00:00
        // $Start = Carbon::yesterday();
        // //取出今天00:00
        // $End = Carbon::today();
        // $this->Order = $this->OrderService->GetSomeTimeOrder($Start, $End);
        $this->OrderService = $OrderService;
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
        $Order = $this->OrderService->GetSomeTimeOrder($Start, $End);
        $Yesterday = Carbon::yesterday();
        $YesterdayAddHour = Carbon::yesterday()->addHour();
        $Orderlist = [];
        for ($I = 0; $I < 24; $I++) {
            // //取得每小時失敗的所有訂單
            $EveryHourFailOrder = $Order->whereBetween('created_at', [$Yesterday, $YesterdayAddHour])->where('status', '=', '失敗');
            // //取得每小時的所有訂單
            $EveryHourOrder = $Order->whereBetween('created_at', [$Yesterday, $YesterdayAddHour])->where('status', '=', '失敗');
            //取得每小時失敗訂單次數
            $EveryHourFailOrderCount = $EveryHourFailOrder->count();
            //取得每小時訂單次數
            $EveryHourOrderCount = $EveryHourOrder->count();
            //將開始時間放入Orderlist
            $Orderlist[$I]['starttime'] = $Yesterday->copy();
            //將結束時間放入Orderlist
            $Orderlist[$I]['endtime'] = $YesterdayAddHour->copy();
            //將失敗訂單次數放入Orderlist
            $Orderlist[$I]['failcount'] = $EveryHourFailOrderCount;
            $Orderlist[$I]['totalcount'] = $EveryHourOrderCount;
            $Orderlist[$I]['created_at'] = Carbon::now();
            $Orderlist[$I]['updated_at'] = Carbon::now();
            //對起始時間加一小
            $Yesterday = $Yesterday->addHour();
            //對終止時間加一小
            $YesterdayAddHour = $YesterdayAddHour->addHour();
        }
        //存入資料庫
        Fail_Order_Count::insert($Orderlist);
    }
}
