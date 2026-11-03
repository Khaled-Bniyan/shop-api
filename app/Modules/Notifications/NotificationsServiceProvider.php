<?php

namespace App\Modules\Notifications;

use App\Modules\Notifications\Listeners\SendOrderStatusNotification;
use App\Modules\Orders\Events\OrderStatusChanged;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

class NotificationsServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/Database/Migrations');

        Event::listen(OrderStatusChanged::class, SendOrderStatusNotification::class);

        Route::middleware('api')
            ->prefix('api/v1')
            ->group(__DIR__.'/Routes/api.php');
    }
}
