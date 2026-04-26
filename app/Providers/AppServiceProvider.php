<?php

namespace App\Providers;

use App\Modules\Catalog\PriceListService;
use App\Modules\DataManagement\OpeningBalanceService;
use App\Modules\Inventory\InventoryService;
use App\Modules\Purchases\PurchaseService;
use App\Modules\Sales\InvoiceService;
use App\Modules\Sales\QuickSaleService;
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
        $this->app->singleton(PurchaseService::class);
        $this->app->singleton(InventoryService::class);
        $this->app->singleton(QuickSaleService::class);
        $this->app->singleton(InvoiceService::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
