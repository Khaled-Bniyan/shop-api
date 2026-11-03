<?php

namespace App\Modules\Orders\Services;

use App\Modules\Orders\Events\OrderStatusChanged;
use App\Modules\Orders\Models\Order;
use App\Modules\Products\Contracts\ProductCatalog;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class OrderService
{
    public function __construct(private ProductCatalog $catalog)
    {
    }

    public function place(int $userId, array $items): Order
    {
        $ids = collect($items)->pluck('product_id')->unique()->values()->all();
        $products = $this->catalog->findMany($ids);

        if (count($products) !== count($ids)) {
            throw ValidationException::withMessages([
                'items' => ['يوجد منتج غير موجود ضمن الطلب'],
            ]);
        }

        return DB::transaction(function () use ($userId, $items, $products) {
            $order = Order::create([
                'user_id' => $userId,
                'status' => Order::PENDING,
                'total' => 0,
            ]);

            $total = 0;
            foreach ($items as $item) {
                $product = $products[$item['product_id']];

                $order->items()->create([
                    'product_id' => $product['id'],
                    'product_name' => $product['name'],
                    'quantity' => $item['quantity'],
                    'price' => $product['price'],
                ]);

                $total += $product['price'] * $item['quantity'];
            }

            $order->update(['total' => round($total, 2)]);

            return $order;
        });
    }

    public function changeStatus(Order $order, string $status): Order
    {
        $order->update(['status' => $status]);

        OrderStatusChanged::dispatch($order->id, $order->user_id, $status);

        return $order;
    }
}
