<?php

namespace App\Jobs;

use App\Models\User_recode as UserRecodeModel;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Models\LoginTotal as LoginTotalModel;

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
    public function handle()
    {
        $start = now()->subHour()->minute(0)->second(0);
        $end = now()->minute(0)->second(0);
        $recordCount = UserRecodeModel::whereBetween('created_at', [$start, $end])
            ->count();
        $result = [
            'count' => $recordCount,
            'starttime' => $start,
            'endtime' => $end
        ];
        LoginTotalModel::insert($result);
    }
}
