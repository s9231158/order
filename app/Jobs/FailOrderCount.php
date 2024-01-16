<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Carbon;
use App\Models\FailOrderCount as FailOrderCountModel;
use Illuminate\Support\Facades\Cache;

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
    private $time = [
        '0' => ['order' => 0, 'fail' => 0, 'success' => 0],
        '1' => ['order' => 0, 'fail' => 0, 'success' => 0],
        '2' => ['order' => 0, 'fail' => 0, 'success' => 0],
        '3' => ['order' => 0, 'fail' => 0, 'success' => 0],
        '4' => ['order' => 0, 'fail' => 0, 'success' => 0],
        '5' => ['order' => 0, 'fail' => 0, 'success' => 0],
        '6' => ['order' => 0, 'fail' => 0, 'success' => 0],
        '7' => ['order' => 0, 'fail' => 0, 'success' => 0],
        '8' => ['order' => 0, 'fail' => 0, 'success' => 0],
        '9' => ['order' => 0, 'fail' => 0, 'success' => 0],
        '10' => ['order' => 0, 'fail' => 0, 'success' => 0],
        '11' => ['order' => 0, 'fail' => 0, 'success' => 0],
        '12' => ['order' => 0, 'fail' => 0, 'success' => 0],
        '13' => ['order' => 0, 'fail' => 0, 'success' => 0],
        '14' => ['order' => 0, 'fail' => 0, 'success' => 0],
        '15' => ['order' => 0, 'fail' => 0, 'success' => 0],
        '16' => ['order' => 0, 'fail' => 0, 'success' => 0],
        '17' => ['order' => 0, 'fail' => 0, 'success' => 0],
        '18' => ['order' => 0, 'fail' => 0, 'success' => 0],
        '19' => ['order' => 0, 'fail' => 0, 'success' => 0],
        '20' => ['order' => 0, 'fail' => 0, 'success' => 0],
        '21' => ['order' => 0, 'fail' => 0, 'success' => 0],
        '22' => ['order' => 0, 'fail' => 0, 'success' => 0],
        '23' => ['order' => 0, 'fail' => 0, 'success' => 0],
        '24' => ['order' => 0, 'fail' => 0, 'success' => 0],
    ];
    public function handle()
    {
        $orderTotal = Cache::get('order_total');
        Cache::set('order_total', $this->time);
        $go = Carbon::today();
        $to = Carbon::today()->addHour();
        $list = [];
        for ($i = 0; $i < 25; $i++) {
            if (!$orderTotal[$i]['order']) {
                $go = $go->addHour();
                $to = $to->addHour();
                continue;
            }
            $list[] = [
                'totalcount' => $orderTotal[$i]['order'],
                'failcount' => $orderTotal[$i]['fail'],
                'success' => $orderTotal[$i]['success'],
                'starttime' => $go->copy(),
                'endtime' => $to->copy(),
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now()
            ];
            $go = $go->addHour();
            $to = $to->addHour();
        }
        FailOrderCountModel::insert($list);
    }
}
