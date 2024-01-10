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
        $start = Carbon::yesterday();
        //取出今天00:00
        $end = Carbon::today();
        //取出昨天至今天所有訂單資料
        $restaruantTotalOrder = Order::select('total', 'rid', 'created_at', 'status')
            ->whereBetween('created_at', [$start, $end])->get();
        //取出交易成功訂單資料
        $onlyGoodRestaruantTotalOrder = $restaruantTotalOrder->where('status', '=', '0');
        $date = [];
        $totalOrder = [];
        $yesterday = Carbon::yesterday();
        $yesterdayAddHour = Carbon::yesterday()->addHour();
        for ($i = 0; $i < 24; $i++) {
            // //取得每小時的所有訂單
            $everyHourOnlyGoodRestaruantOrder = $onlyGoodRestaruantTotalOrder
                ->whereBetween('created_at', [$yesterday, $yesterdayAddHour]);
            //計算每小時內所有訂單各間餐廳收入總額
            //取出昨天00:00
            $date[$i]['starttime'] = $yesterday->copy();
            //取出昨天01:00
            $date[$i]['endtime'] = $yesterdayAddHour->copy();
            foreach ($everyHourOnlyGoodRestaruantOrder as $key => $value) {
                $totalOrder[] = [
                    'rid' => $value['rid'],
                    'money' => $value['total'],
                    'starttime' => $date[$i]['starttime'],
                    'endtime' => $date[$i]['endtime']
                ];
            }
            //對起始時間加一小
            $yesterday = $yesterday->addHour();
            //對終止時間加一小
            $yesterdayAddHour = $yesterdayAddHour->addHour();
        }
        //將相同時間與相同餐廳金額加總
        $sums = [];
        foreach ($totalOrder as $item) {
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
        //存入資料庫
        Restaruant_Total_Money::insert($result);
    }
}
