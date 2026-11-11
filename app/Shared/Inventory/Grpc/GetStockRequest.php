<?php

namespace App\Shared\Inventory\Grpc;

class GetStockRequest extends WireMessage
{
    /** @param int[] $productIds */
    public function __construct(public array $productIds = []) {}

    public function serializeToString(): string
    {
        $encoded = '';

        foreach ($this->productIds as $productId) {
            $encoded .= self::uint(1, (int) $productId);
        }

        return $encoded;
    }
}
