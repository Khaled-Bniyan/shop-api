<?php

namespace App\Providers;

use App\Shared\Inventory\Contracts\InventoryGateway;
use App\Shared\Inventory\Grpc\GrpcInventoryGateway;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(InventoryGateway::class, GrpcInventoryGateway::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
