<?php

namespace App\Modules\Users;

use App\Modules\Users\Services\EloquentCustomerRecipientProvider;
use App\Shared\Notifications\Contracts\CustomerRecipientProvider;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

class UsersServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(
            CustomerRecipientProvider::class,
            EloquentCustomerRecipientProvider::class,
        );
    }

    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/Database/Migrations');

        Route::middleware('api')
            ->prefix('api/v1')
            ->group(__DIR__.'/Routes/api.php');
    }
}
