<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Carbon;
use App\Models\Order;
use App\Models\RestaruantTotalMoney as Restaruant_Total_Money;

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
        //取出昨天00:00
        $Start = Carbon::yesterday();
        //取出今天00:00
        $End = Carbon::today();
        //取出昨天至今天所有訂單資料
        $RestaruantTotalOrder = Order::select('total', 'rid', 'created_at', 'status')
            ->whereBetween('created_at', [$Start, $End])->get();
        //取出交易成功訂單資料
        $OnlyGoodRestaruantTotalOrder = $RestaruantTotalOrder->where('status', '=', '0');
        $Date = [];
        $TotalOrder = [];
        $Yesterday = Carbon::yesterday();
        $YesterdayAddHour = Carbon::yesterday()->addHour();
        for ($I = 0; $I < 24; $I++) {
            // //取得每小時的所有訂單
            $EveryHourOnlyGoodRestaruantOrder = $OnlyGoodRestaruantTotalOrder
                ->whereBetween('created_at', [$Yesterday, $YesterdayAddHour]);
            //計算每小時內所有訂單各間餐廳收入總額
            //取出昨天00:00
            $Date[$I]['starttime'] = $Yesterday->copy();
            //取出昨天01:00
            $Date[$I]['endtime'] = $YesterdayAddHour->copy();
            foreach ($EveryHourOnlyGoodRestaruantOrder as $Key => $Value) {
                $TotalOrder[] = [
                    'rid' => $Value['rid'],
                    'money' => $Value['total'],
                    'starttime' => $Date[$I]['starttime'],
                    'endtime' => $Date[$I]['endtime']
                ];
            }
            //對起始時間加一小
            $Yesterday = $Yesterday->addHour();
            //對終止時間加一小
            $YesterdayAddHour = $YesterdayAddHour->addHour();
        }
        //將相同時間與相同餐廳金額加總
        $Sums = [];
        foreach ($TotalOrder as $Item) {
            $Key = $Item['starttime'] . $Item['rid'];
            if (array_key_exists($Key, $Sums)) {
                $Sums[$Key]['money'] += $Item['money'];
            } else {
                $Sums[$Key] = [
                    'rid' => $Item['rid'],
                    'money' => $Item['money'],
                    'starttime' => $Item['starttime'],
                    'endtime' => $Item['endtime']
                ];
            }
        }
        //將結果整理後存進資料庫
        $Result = [];
        foreach ($Sums as $Item) {
            $Result[] = [
                'rid' => $Item['rid'],
                'money' => $Item['money'],
                'starttime' => $Item['starttime'],
                'endtime' => $Item['endtime'],
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ];
        }
        //存入資料庫
        Restaruant_Total_Money::insert($Result);
    }
}
