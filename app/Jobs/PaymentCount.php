<?php

namespace App\Jobs;

use App\Models\Wallet_Record as WalletRecordModel;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Models\PaymentCount as PaymentCountModel;

class PaymentCount implements ShouldQueue
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
        $records = WalletRecordModel::select('pid')->whereBetween('created_at', [$start, $end])->get();
        $ecpayCount = $records->where('pid', '=', '1')->count();
        $localCount = $records->where('pid', '=', '2')->count();
        $result = [
            'local' => $localCount,
            'ecpay' => $ecpayCount,
            'starttime' => $start,
            'endtime' => $end
        ];
        PaymentCountModel::insert($result);
    }
}
