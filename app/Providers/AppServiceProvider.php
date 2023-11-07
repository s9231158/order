<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Contracts\GetmenuInterface;
use App\OCmenu;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        // $this->app->bind(GetmenuInterface::class, OCmenu::class);
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        //
    }
}
