<?php

namespace App\Providers;

use App\Modules\Catalog\PriceListService;
use App\Modules\DataManagement\OpeningBalanceService;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(PriceListService::class);
        $this->app->singleton(OpeningBalanceService::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
