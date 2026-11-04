<?php

namespace App\Shared\Inventory\Grpc;

class ReserveStockRequest extends WireMessage
{
    /** @param array<int, ReservationItem> $items */
    public function __construct(
        public string $reservationId = '',
        public array $items = [],
    ) {
    }

    public function serializeToString(): string
    {
        $encoded = self::string(1, $this->reservationId);

        foreach ($this->items as $item) {
            $encoded .= self::message(2, $item);
        }

        return $encoded;
    }
}
