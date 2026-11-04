<?php

namespace App\Shared\Inventory\Grpc;

class ReserveStockResponse extends WireMessage
{
    public const INTERNAL_ERROR = 4;

    public bool $success = false;
    public int $status = 0;
    public string $message = '';
    public string $reservationId = '';

    public function serializeToString(): string
    {
        return '';
    }

    public function mergeFromString(string $payload): void
    {
        $fields = self::fields($payload);
        $this->success = (bool) ($fields[1][0] ?? 0);
        $this->status = (int) ($fields[2][0] ?? 0);
        $this->message = (string) ($fields[3][0] ?? '');
        $this->reservationId = (string) ($fields[4][0] ?? '');
    }

    public static function decode(string $payload): self
    {
        $message = new self;
        $message->mergeFromString($payload);

        return $message;
    }
}
