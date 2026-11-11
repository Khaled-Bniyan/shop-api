<?php

namespace App\Shared\Inventory\Contracts;

interface InventoryGateway
{
    /**
     * @param  int[]  $productIds
     * @return array<int, int> indexed by product ID
     */
    public function availableQuantities(array $productIds): array;

    /**
     * @param  array<int, array{product_id: int, quantity: int}>  $items
     */
    public function reserve(string $reservationId, array $items): void;

    public function release(string $reservationId): void;
}
