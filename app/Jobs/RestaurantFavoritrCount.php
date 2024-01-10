<?php

namespace App\Jobs;

use App\Models\RestaruantFavoritCount;
use App\Models\User_favorite;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Carbon;

class RestaurantFavoritrCount implements ShouldQueue
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
        $restaruantFavorite = User_favorite::select('rid', 'created_at')
            ->whereBetween('created_at', [$start, $end])->get();
        $yesterday = Carbon::yesterday();
        $yesterdayAddHour = Carbon::yesterday()->addHour();
        $list = [];
        for ($i = 0; $i < 24; $i++) {
            // //取得每小時的ecpay支付方式
            $everyHourFavoriteCount = $restaruantFavorite
                ->whereBetween('created_at', [$yesterday, $yesterdayAddHour]);
            foreach ($everyHourFavoriteCount as $item) {
                $list[] = [
                    'rid' => $item['rid'],
                    'starttime' => $yesterday->copy(),
                    'endtime' => $yesterdayAddHour->copy()
                ];
            }
            //對起始時間加一小
            $yesterday = $yesterday->addHour();
            //對終止時間加一小
            $yesterdayAddHour = $yesterdayAddHour->addHour();
        }
        //將相同時間與相同餐廳次數加總
        $sums = [];
        foreach ($list as $item) {
            $key = $item['starttime'] . $item['rid'];
            if (array_key_exists($key, $sums)) {
                $sums[$key]['Count'] += 1;
            } else {
                $sums[$key] = [
                    'rid' => $item['rid'],
                    'Count' => 1,
                    'starttime' => $item['starttime'],
                    'endtime' => $item['endtime'],
                ];
            }
        }
        //將結果整理後存進資料庫
        $result = [];
        foreach ($sums as $item) {
            $result[] = [
                'rid' => $item['rid'],
                'count' => $item['Count'],
                'starttime' => $item['starttime'],
                'endtime' => $item['endtime'],
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ];
        }
        //存入資料庫
        RestaruantFavoritCount::insert($result);
    }
}
