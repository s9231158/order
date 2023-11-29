<?php

namespace App\Console;

use App\Jobs\FailOrderCount;
use App\Jobs\PaymentCount;
use App\Jobs\RestaruantMoneyTotal;
use App\Jobs\UserRecordCount;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * Define the application's command schedule.
     *
     * @param  \Illuminate\Console\Scheduling\Schedule  $schedule
     * @return void
     */
    protected function schedule(Schedule $schedule)
    {

        $schedule->call(function () {
            UserRecordCount::dispatch();
        })->everyMinute();
        $schedule->call(function () {
            RestaruantMoneyTotal::dispatch();
        })->everyMinute();
        $schedule->call(function () {
            FailOrderCount::dispatch();
        })->everyMinute();
        $schedule->call(function () {
            PaymentCount::dispatch();
        })->everyMinute();
    }

    /**
     * Register the commands for the application.
     *
     * @return void
     */
    protected function commands()
    {
        $this->load(__DIR__ . '/Commands');

        require base_path('routes/console.php');
    }
}
