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
        $Start = Carbon::yesterday();
        //取出今天00:00
        $End = Carbon::today();
        //取出昨天至今天所有訂單資料
        $RestaruantFavorite = User_favorite::select('rid', 'created_at')
            ->whereBetween('created_at', [$Start, $End])->get();
        $Yesterday = Carbon::yesterday();
        $YesterdayAddHour = Carbon::yesterday()->addHour();
        $List = [];
        for ($I = 0; $I < 24; $I++) {
            // //取得每小時的ecpay支付方式
            $EveryHourFavoriteCount = $RestaruantFavorite
                ->whereBetween('created_at', [$Yesterday, $YesterdayAddHour]);
            foreach ($EveryHourFavoriteCount as $Item) {
                $List[] = [
                    'rid' => $Item['rid'],
                    'starttime' => $Yesterday->copy(),
                    'endtime' => $YesterdayAddHour->copy()
                ];
            }
            //對起始時間加一小
            $Yesterday = $Yesterday->addHour();
            //對終止時間加一小
            $YesterdayAddHour = $YesterdayAddHour->addHour();
        }
        //將相同時間與相同餐廳次數加總
        $Sums = [];
        foreach ($List as $Item) {
            $Key = $Item['starttime'] . $Item['rid'];
            if (array_key_exists($Key, $Sums)) {
                $Sums[$Key]['Count'] += 1;
            } else {
                $Sums[$Key] = [
                    'rid' => $Item['rid'],
                    'Count' => 1,
                    'starttime' => $Item['starttime'],
                    'endtime' => $Item['endtime'],
                ];
            }
        }
        //將結果整理後存進資料庫
        $Result = [];
        foreach ($Sums as $Item) {
            $Result[] = [
                'rid' => $Item['rid'],
                'count' => $Item['Count'],
                'starttime' => $Item['starttime'],
                'endtime' => $Item['endtime'],
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ];
        }
        //存入資料庫
        RestaruantFavoritCount::insert($Result);
    }
}
