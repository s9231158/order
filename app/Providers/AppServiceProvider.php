<?php

namespace App\Providers;

use App\UserInterface\LoginInterface;
use App\UserService\LoginService;
use Illuminate\Support\ServiceProvider;
use App\Http\Controllers\UserController;
use App\UserInterface\CreateInrerface;
use App\UserService\CreateService;
class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        $this->app->when(UserController::class)
          ->needs(CreateInrerface::class)
          ->give(CreateService::class);
        $this->app->when(UserController::class)
          ->needs(LoginInterface::class)
          ->give(LoginService::class);
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
