<?php

namespace App\Jobs;

use App\Models\RestaruantFavoritCount;
use App\Models\RestaruantTotalMoney;
use App\Models\User_favorite;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;

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
    private $time = [
        '0' => [],
        '1' => [],
        '2' => [],
        '3' => [],
        '4' => [],
        '5' => [],
        '6' => [],
        '7' => [],
        '8' => [],
        '9' => [],
        '10' => [],
        '11' => [],
        '12' => [],
        '13' => [],
        '14' => [],
        '15' => [],
        '16' => [],
        '17' => [],
        '18' => [],
        '19' => [],
        '20' => [],
        '21' => [],
        '22' => [],
        '23' => [],
        '24' => [],
    ];
    public function handle()
    {
        $restaurantFavorite = Cache::get('restaurant_favorite');
        Cache::set('restaurant_favorite', $this->time);
        $go = Carbon::today();
        $to = Carbon::today()->addHour();
        $list = [];
        for ($i = 0; $i < 25; $i++) {
            if (!$restaurantFavorite[$i]) {
                $go = $go->addHour();
                $to = $to->addHour();
                continue;
            }
            foreach ($restaurantFavorite[$i] as $key => $value) {
                $list[] = [
                    'rid' => $key,
                    'count' => $value,
                    'starttime' => $go->copy(),
                    'endtime' => $to->copy(),
                    'created_at' => Carbon::now(),
                    'updated_at' => Carbon::now()
                ];
            }
            $go = $go->addHour();
            $to = $to->addHour();
        }
        RestaruantFavoritCount::insert($list);
    }
}
