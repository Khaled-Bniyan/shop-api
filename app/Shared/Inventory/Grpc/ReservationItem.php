<?php

namespace App\Shared\Inventory\Grpc;

class ReservationItem extends WireMessage
{
    public function __construct(
        public int $productId = 0,
        public int $quantity = 0,
    ) {
    }

    public function serializeToString(): string
    {
        return self::uint(1, $this->productId)
            .self::uint(2, $this->quantity);
    }
}
