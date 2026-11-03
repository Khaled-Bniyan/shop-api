<?php

namespace App\Modules\Orders\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OrderResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'user_id' => $this->user_id,
            'status' => $this->status,
            'total' => $this->total,
            'created_at' => $this->created_at,
            'items' => $this->items->map(fn ($item) => [
                'product_id' => $item->product_id,
                'name' => $item->product_name,
                'quantity' => $item->quantity,
                'price' => $item->price,
            ]),
        ];
    }
}
