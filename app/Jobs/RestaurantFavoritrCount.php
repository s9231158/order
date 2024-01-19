<?php

namespace App\Jobs;

use App\Models\RestaruantFavoritCount as RestaruantFavoritCountModel;
use App\Models\User_favorite as UserFavoriteModel;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

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
        $start = now()->subHour()->minute(0)->second(0);
        $end = now()->minute(0)->second(0);
        $favorite = UserFavoriteModel::select('rid')
            ->whereBetween('created_at', [$start, $end])
            ->get();
        $favoriteTotal = $favorite->groupBy('rid')->map(function ($group) {
            return count($group);
        });
        $result = [];
        foreach ($favoriteTotal as $key => $value) {
            $result[] = [
                'rid' => $key,
                'count' => $value,
                'starttime' => $start,
                'endtime' => $end
            ];
        }
        RestaruantFavoritCountModel::insert($result);
    }
}
