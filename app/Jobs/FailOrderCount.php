<?php

namespace App\Jobs;

use App\Models\Order as OrderModel;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Models\FailOrderCount as FailOrderCountModel;

class FailOrderCount implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;
    private $orderService;
    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct()
    {
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $start = now()->minute(0)->second(0);
        $end = now()->addHour()->minute(0)->second(0);
        //取出訂單
        $orders = OrderModel::whereBetween('created_at', [$start, $end])->get();
        if ($orders->isEmpty()) {
            return;
        }
        $ordersCount = $orders->count();
        $failOrders = $orders->whereBetween('status', [10, 20]);
        $failOrdersCount = $failOrders->count();
        $result = [
            'failcount' => $failOrdersCount,
            'totalcount' => $ordersCount,
            'starttime' => $start,
            'endtime' => $end
        ];
        FailOrderCountModel::insert($result);
    }
}
