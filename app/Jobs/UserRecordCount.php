<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Carbon;
use App\Models\LoginTotal as Login_Total;
use Illuminate\Support\Facades\Cache;

class UserRecordCount implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

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
        '0' => 0,
        '1' => 0,
        '2' => 0,
        '3' => 0,
        '4' => 0,
        '5' => 0,
        '6' => 0,
        '7' => 0,
        '8' => 0,
        '9' => 0,
        '10' => 0,
        '11' => 0,
        '12' => 0,
        '13' => 0,
        '14' => 0,
        '15' => 0,
        '16' => 0,
        '17' => 0,
        '18' => 0,
        '19' => 0,
        '20' => 0,
        '21' => 0,
        '22' => 0,
        '23' => 0,
        '24' => 0,
    ];
    public function handle()
    {
        $count = Cache::get('login_record');
        Cache::set('login_record', $this->time);
        $go = Carbon::today();
        $to = Carbon::today()->addHour();
        $list = [];
        for ($i = 0; $i < 25; $i++) {
            if (!$count[$i]) {
                $go = $go->addHour();
                $to = $to->addHour();
                continue;
            }
            $list[] = [
                'count' => $count[$i],
                'starttime' => $go->copy(),
                'endtime' => $to->copy(),
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now()
            ];
            $go = $go->addHour();
            $to = $to->addHour();
        }
        Login_Total::insert($list);
    }
}
