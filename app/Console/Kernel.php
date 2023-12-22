<?php

namespace App\Console;

use App\Jobs\FailOrderCount;
use App\Jobs\PaymentCount;
use App\Jobs\RestaruantMoneyTotal;
use App\Jobs\RestaurantFavoritrCount;
use App\Jobs\UserRecordCount;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;
use App\Service\OrderService;

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
        $orderServiceInstance = new OrderService();
        $schedule->call(function () {
            UserRecordCount::dispatch();
        })->dailyAt('6:32');
        $schedule->call(function () {
            RestaruantMoneyTotal::dispatch();
        })->dailyAt('6:32');
        $schedule->call(function () use ($orderServiceInstance) {
            FailOrderCount::dispatch($orderServiceInstance);
        })->dailyAt('6:32');
        $schedule->call(function () {
            PaymentCount::dispatch();
        })->dailyAt('6:32');
        $schedule->call(function () {
            RestaurantFavoritrCount::dispatch();
        })->dailyAt('6:32');
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
