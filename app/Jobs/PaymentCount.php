<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Carbon;
use App\Models\Wallet_Record;
use App\Models\PaymentCount as ModelPaymentCount;

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
        //取出昨天00:00
        $start = Carbon::yesterday();
        //取出今天00:00
        $end = Carbon::today();
        //取出昨天至今天所有訂單資料
        $walletRecord = Wallet_Record::select('pid', 'created_at', 'out')
            ->whereBetween('created_at', [$start, $end])->get();
        $yesterday = Carbon::yesterday();
        $yesterdayAddHour = Carbon::yesterday()->addHour();
        $paymentList = [];
        for ($i = 0; $i < 24; $i++) {
            //取得每小時的ecpay支付方式
            $everyHourEcpayCount = $walletRecord->whereBetween('created_at', [$yesterday, $yesterdayAddHour])
                ->wherenotnull('out')->where('pid', '=', 1)->count();
            //取得每小時的本地支付方式
            $everyHourLocalPayCount = $walletRecord->whereBetween('created_at', [$yesterday, $yesterdayAddHour])
                ->wherenotnull('out')->where('pid', '=', 2)->count();
            if ($everyHourEcpayCount !== 0 or $everyHourLocalPayCount !== 0) {
                $paymentList[] = ['starttime' => $yesterday->copy(),
                    'endtime' => $yesterdayAddHour->copy(),
                    'ecpay' => $everyHourEcpayCount,
                    'local' => $everyHourLocalPayCount,
                    'created_at' => Carbon::now(),
                    'updated_at' => Carbon::now(),
                ];
            }
            //對起始時間加一小
            $yesterday = $yesterday->addHour();
            //對終止時間加一小
            $yesterdayAddHour = $yesterdayAddHour->addHour();
        }
        //存入資料庫
        ModelPaymentCount::insert($paymentList);
    }
}
