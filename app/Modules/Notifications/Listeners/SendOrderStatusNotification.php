<?php

namespace App\Modules\Notifications\Listeners;

use App\Modules\Notifications\Notifications\OrderStatusNotification;
use App\Modules\Orders\Events\OrderStatusChanged;
use App\Modules\Users\Models\User;

class SendOrderStatusNotification
{
    public function handle(OrderStatusChanged $event): void
    {
        $user = User::find($event->userId);

        $user?->notify(
            new OrderStatusNotification($event->orderId, $event->status)
        );
    }
}
