<?php

namespace App\Providers;

use App\UserInterface\FavoriteInterface;
use App\UserInterface\LoginInterface;
use App\UserInterface\LogoutInterface;
use App\UserInterface\RecordInerface;
use App\UserService\FavoriteService;
use App\UserService\LoginService;
use App\UserService\LogoutService;
use App\UserService\RecordService;
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
    $this->app->when(UserController::class)
      ->needs(LogoutInterface::class)
      ->give(LogoutService::class);
    $this->app->when(UserController::class)
      ->needs(RecordInerface::class)
      ->give(RecordService::class);
    $this->app->when(UserController::class)
      ->needs(FavoriteInterface::class)
      ->give(FavoriteService::class);
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
