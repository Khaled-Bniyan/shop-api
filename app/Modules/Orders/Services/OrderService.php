<?php

namespace App\Modules\Orders\Services;

use App\Modules\Orders\Events\OrderStatusChanged;
use App\Modules\Orders\Models\Order;
use App\Modules\Products\Contracts\ProductCatalog;
use App\Shared\Inventory\Contracts\InventoryGateway;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Throwable;

class OrderService
{
    public function __construct(
        private ProductCatalog $catalog,
        private InventoryGateway $inventory,
    )
    {
    }

    public function place(int $userId, array $items): Order
    {
        $quantities = collect($items)
            ->groupBy('product_id')
            ->map(fn ($lines) => (int) $lines->sum('quantity'));

        $ids = $quantities->keys()->map(fn ($id) => (int) $id)->all();
        $products = $this->catalog->findMany($ids);

        if (count($products) !== count($ids)) {
            throw ValidationException::withMessages([
                'items' => ['يوجد منتج غير موجود ضمن الطلب'],
            ]);
        }

        $reservationId = (string) Str::uuid();
        $reservationItems = $quantities
            ->map(fn (int $quantity, int|string $productId) => [
                'product_id' => (int) $productId,
                'quantity' => $quantity,
            ])
            ->values()
            ->all();

        $this->inventory->reserve($reservationId, $reservationItems);

        try {
            return DB::transaction(function () use ($userId, $reservationId, $reservationItems, $products) {
                $order = Order::create([
                    'user_id' => $userId,
                    'status' => Order::PENDING,
                    'inventory_reservation_id' => $reservationId,
                    'total' => 0,
                ]);

                $total = 0;
                foreach ($reservationItems as $item) {
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
        } catch (Throwable $exception) {
            try {
                $this->inventory->release($reservationId);
            } catch (Throwable $releaseException) {
                report($releaseException);
            }

            throw $exception;
        }
    }

    public function changeStatus(Order $order, string $status): Order
    {
        if (
            $status === Order::CANCELLED
            && $order->status !== Order::CANCELLED
            && $order->inventory_reservation_id !== null
        ) {
            $this->inventory->release($order->inventory_reservation_id);
        }

        $order->update(['status' => $status]);

        OrderStatusChanged::dispatch($order->id, $order->user_id, $status);

        return $order;
    }
}
