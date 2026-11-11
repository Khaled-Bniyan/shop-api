<?php

namespace App\Notifications;

use Illuminate\Notifications\Notification;

class ProductCreatedNotification extends Notification
{
    public function __construct(
        public readonly int $productId,
        public readonly string $productName,
    ) {}

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'product_id' => $this->productId,
            'product_name' => $this->productName,
            'message' => "تمت إضافة منتج جديد: {$this->productName}",
        ];
    }
}
