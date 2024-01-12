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
use Illuminate\Support\Facades\Cache;

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
    private $time = [
        '0' => ['local' => 0, 'ecpay' => 0],
        '1' => ['local' => 0, 'ecpay' => 0],
        '2' => ['local' => 0, 'ecpay' => 0],
        '3' => ['local' => 0, 'ecpay' => 0],
        '4' => ['local' => 0, 'ecpay' => 0],
        '5' => ['local' => 0, 'ecpay' => 0],
        '6' => ['local' => 0, 'ecpay' => 0],
        '7' => ['local' => 0, 'ecpay' => 0],
        '8' => ['local' => 0, 'ecpay' => 0],
        '9' => ['local' => 0, 'ecpay' => 0],
        '10' => ['local' => 0, 'ecpay' => 0],
        '11' => ['local' => 0, 'ecpay' => 0],
        '12' => ['local' => 0, 'ecpay' => 0],
        '13' => ['local' => 0, 'ecpay' => 0],
        '14' => ['local' => 0, 'ecpay' => 0],
        '15' => ['local' => 0, 'ecpay' => 0],
        '16' => ['local' => 0, 'ecpay' => 0],
        '17' => ['local' => 0, 'ecpay' => 0],
        '18' => ['local' => 0, 'ecpay' => 0],
        '19' => ['local' => 0, 'ecpay' => 0],
        '20' => ['local' => 0, 'ecpay' => 0],
        '21' => ['local' => 0, 'ecpay' => 0],
        '22' => ['local' => 0, 'ecpay' => 0],
        '23' => ['local' => 0, 'ecpay' => 0],
        '24' => ['local' => 0, 'ecpay' => 0],
    ];
    public function handle()
    {
        $paymentCount = Cache::get('payment_count');
        Cache::set('payment_count', $this->time);
        $go = Carbon::today();
        $to = Carbon::today()->addHour();
        $list = [];
        for ($i = 0; $i < 25; $i++) {
            if (!$paymentCount[$i]['local'] && !$paymentCount[$i]['ecpay']) {
                $go = $go->addHour();
                $to = $to->addHour();
                continue;
            }

            $list[] = [
                'ecpay' => $paymentCount[$i]['ecpay'],
                'local' => $paymentCount[$i]['local'],
                'starttime' => $go->copy(),
                'endtime' => $to->copy(),
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now()
            ];

            $go = $go->addHour();
            $to = $to->addHour();
        }
        ModelPaymentCount::insert($list);
    }
}
