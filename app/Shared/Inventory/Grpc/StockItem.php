<?php

namespace App\Shared\Inventory\Grpc;

class StockItem extends WireMessage
{
    public function __construct(
        public int $productId = 0,
        public int $availableQuantity = 0,
    ) {}

    public function serializeToString(): string
    {
        return self::uint(1, $this->productId)
            .self::uint(2, $this->availableQuantity);
    }

    public function mergeFromString(string $payload): void
    {
        $fields = self::fields($payload);
        $this->productId = (int) ($fields[1][0] ?? 0);
        $this->availableQuantity = (int) ($fields[2][0] ?? 0);
    }

    public static function decode(string $payload): self
    {
        $message = new self;
        $message->mergeFromString($payload);

        return $message;
    }
}
