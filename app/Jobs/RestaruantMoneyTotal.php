<?php

namespace App\Jobs;

use App\Models\Order as OrderModel;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Models\RestaruantTotalMoney as RestaruantTotalMoneyModel;

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
        $start = now()->subHour()->minute(0)->second(0);
        $end = now()->minute(0)->second(0);
        $orders = OrderModel::select('total', 'rid')
            ->whereBetween('created_at', [$start, $end])
            ->whereBetween('status', [0, 9])
            ->get();
        $restaurantTotal = $orders->groupBy('rid')->map(function ($group) {
            return $group->sum('total');
        });
        $result = [];
        foreach ($restaurantTotal as $key => $value) {
            $result[] = [
                'rid' => $key,
                'money' => $value,
                'starttime' => $start,
                'endtime' => $end
            ];
        }
        //存入資料庫
        RestaruantTotalMoneyModel::insert($result);
    }
}
