<?php

namespace App\Jobs;

use App\Models\RestaruantFavoritCount;
use App\Models\User_favorite;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
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
        $Start = Carbon::yesterday();
        //取出今天00:00
        $End = Carbon::today();
        //取出昨天至今天所有訂單資料
        $RestaruantFavorite = User_favorite::select('rid', 'created_at')->whereBetween('created_at', [$Start, $End])->get();
        $Yesterday = Carbon::yesterday();
        $YesterdayAddHour = Carbon::yesterday()->addHour();
        $RestaruantFavoriteList = [];
        $list = [];
        for ($I = 0; $I < 24; $I++) {
            // //取得每小時的ecpay支付方式
            $EveryHourFavoriteCount = $RestaruantFavorite->whereBetween('created_at', [$Yesterday, $YesterdayAddHour]);
            //將開始時間放入Paymentlist
            $RestaruantFavoriteList[$I]['starttime'] = $Yesterday->copy();
            //將失敗時間放入Paymentlist
            $RestaruantFavoriteList[$I]['endtime'] = $YesterdayAddHour->copy();
            $RestaruantFavoriteList[$I]['list'] = [];
            $Timelist[] = [
                'starttime' => $RestaruantFavoriteList[$I]['starttime'],
                'endtime' => $RestaruantFavoriteList[$I]['endtime'],
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ];
            foreach ($EveryHourFavoriteCount as $i) {
                $list[] = ['rid' => $i['rid'], 'starttime' => $RestaruantFavoriteList[$I]['starttime'], 'endtime' => $RestaruantFavoriteList[$I]['endtime']];
                $RestaruantFavoriteList[$I]['list'] = ['rid' => $i['rid']];
            }
            //對起始時間加一小
            $Yesterday = $Yesterday->addHour();
            //對終止時間加一小
            $YesterdayAddHour = $YesterdayAddHour->addHour();
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
        RestaruantFavoritCount::insert($result);
        RestaruantFavoritCount::insert($ResultTimelist);
    }
}

