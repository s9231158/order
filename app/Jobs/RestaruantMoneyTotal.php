<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Carbon;
use App\Models\Order;
use App\Models\Restaruant_Total_Money;

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
        $RestaruantTotalOrder = Order::select('total', 'rid', 'created_at', 'status')->whereBetween('created_at', [$Start, $End])->get();
        //取出交易成功訂單資料
        $OnlyGoodRestaruantTotalOrder = $RestaruantTotalOrder->where('status', '=', '成功');
        $Date = [];
        $TotalOrder = [];
        $Timelist = [];
        $Yesterday = Carbon::yesterday();
        $YesterdayAddHour = Carbon::yesterday()->addHour();
        for ($I = 0; $I < 24; $I++) {
            // //取得每小時的所有訂單
            $EveryHourOnlyGoodRestaruantOrder = $OnlyGoodRestaruantTotalOrder->whereBetween('created_at', [$Yesterday, $YesterdayAddHour]);
            //計算每小時內所有訂單各間餐廳收入總額
            //取出昨天00:00
            $Date[$I]['starttime'] = $Yesterday->copy();
            //取出昨天01:00
            $Date[$I]['endtime'] = $YesterdayAddHour->copy();
            $Timelist[] = [
                'starttime' => $Date[$I]['starttime'],
                'endtime' => $Date[$I]['endtime'],
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ];

            foreach ($EveryHourOnlyGoodRestaruantOrder as $key => $value) {
                $TotalOrder[] = ['rid' => $value['rid'], 'money' => $value['total'], 'starttime' => $Date[$I]['starttime'], 'endtime' => $Date[$I]['endtime']];
            }
            //對起始時間加一小
            $Yesterday = $Yesterday->addHour();
            //對終止時間加一小
            $YesterdayAddHour = $YesterdayAddHour->addHour();

        }

        //將相同時間與相同餐廳金額加總
        $sums = [];
        foreach ($TotalOrder as $item) {
            $key = $item['starttime'] . $item['rid'];
            if (array_key_exists($key, $sums)) {
                $sums[$key]['money'] += $item['money'];
            } else {
                $sums[$key] = [
                    'rid' => $item['rid'],
                    'money' => $item['money'],
                    'starttime' => $item['starttime'],
                    'endtime' => $item['endtime']
                ];
            }
        }
        //將結果整理後存進資料庫
        $result = [];
        foreach ($sums as $item) {
            $result[] = [
                'rid' => $item['rid'],
                'money' => $item['money'],
                'starttime' => $item['starttime'],
                'endtime' => $item['endtime'],
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ];
        }
        $ResultTimelist = [];
        foreach ($Timelist as $elementA) {
            $exists = false;
            foreach ($result as $elementB) {
                if ($elementA['starttime'] === $elementB['starttime'] && $elementA['endtime'] === $elementB['endtime']) {
                    $exists = true;
                }
            }
            if (!$exists) {
                $ResultTimelist[] = $elementA;
            }
        }
        //存入資料庫
        Restaruant_Total_Money::insert($result);
        Restaruant_Total_Money::insert($ResultTimelist);
    }
}
