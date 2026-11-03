<?php

namespace App\Modules\Notifications\Notifications;

use Illuminate\Notifications\Notification;

class OrderStatusNotification extends Notification
{
    public function __construct(
        public int $orderId,
        public string $status,
    ) {
    }

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'order_id' => $this->orderId,
            'status' => $this->status,
            'message' => "تم تغيير حالة طلبك رقم {$this->orderId} إلى {$this->status}",
        ];
    }
}
