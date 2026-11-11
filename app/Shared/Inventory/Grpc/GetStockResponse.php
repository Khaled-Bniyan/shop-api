<?php

namespace App\Shared\Inventory\Grpc;

class GetStockResponse extends WireMessage
{
    /** @var StockItem[] */
    public array $items = [];

    public function serializeToString(): string
    {
        $encoded = '';

        foreach ($this->items as $item) {
            $encoded .= self::message(1, $item);
        }

        return $encoded;
    }

    public function mergeFromString(string $payload): void
    {
        $fields = self::fields($payload);
        $this->items = array_map(
            fn (string $item) => StockItem::decode($item),
            $fields[1] ?? [],
        );
    }

    public static function decode(string $payload): self
    {
        $message = new self;
        $message->mergeFromString($payload);

        return $message;
    }
}
