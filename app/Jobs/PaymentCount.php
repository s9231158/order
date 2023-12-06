<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
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
        $Start = Carbon::yesterday();
        //取出今天00:00
        $End = Carbon::today();
        //取出昨天至今天所有訂單資料
        $WalletRecord = Wallet_Record::select('pid', 'created_at', 'out')->whereBetween('created_at', [$Start, $End])->get();
        $Yesterday = Carbon::yesterday();
        $YesterdayAddHour = Carbon::yesterday()->addHour();
        $Paymentlist = [];
        for ($I = 0; $I < 24; $I++) {
            //取得每小時的ecpay支付方式
            $EveryHourEcpayCount = $WalletRecord->whereBetween('created_at', [$Yesterday, $YesterdayAddHour])->wherenotnull('out')->where('pid', '=', 1)->count();
            //取得每小時的本地支付方式
            $EveryHourLocalPayCount = $WalletRecord->whereBetween('created_at', [$Yesterday, $YesterdayAddHour])->wherenotnull('out')->where('pid', '=', 2)->count();
            //將開始時間放入Paymentlist
            $Paymentlist[$I]['starttime'] = $Yesterday->copy();
            //將失敗時間放入Paymentlist
            $Paymentlist[$I]['endtime'] = $YesterdayAddHour->copy();
            //將各個交易次數放入Paymentlist
            $Paymentlist[$I]['ecpay'] = $EveryHourEcpayCount;
            $Paymentlist[$I]['local'] = $EveryHourLocalPayCount;
            $Paymentlist[$I]['created_at'] = Carbon::now();
            $Paymentlist[$I]['updated_at'] = Carbon::now();
            //對起始時間加一小
            $Yesterday = $Yesterday->addHour();
            //對終止時間加一小
            $YesterdayAddHour = $YesterdayAddHour->addHour();
        }
        //存入資料庫
        ModelPaymentCount::insert($Paymentlist);
    }
}
