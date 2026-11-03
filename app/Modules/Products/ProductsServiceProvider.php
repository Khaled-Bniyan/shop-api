<?php

namespace App\Modules\Products;

use App\Modules\Products\Contracts\ProductCatalog;
use App\Modules\Products\Services\EloquentProductCatalog;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

class ProductsServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(ProductCatalog::class, EloquentProductCatalog::class);
    }

    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/Database/Migrations');

        Route::middleware('api')
            ->prefix('api/v1')
            ->group(__DIR__.'/Routes/api.php');
    }
}
