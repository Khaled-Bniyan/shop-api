<?php

namespace App\Shared\Inventory\Contracts;

interface InventoryGateway
{
    /**
     * @param array<int, array{product_id: int, quantity: int}> $items
     */
    public function reserve(string $reservationId, array $items): void;

    public function release(string $reservationId): void;
}
