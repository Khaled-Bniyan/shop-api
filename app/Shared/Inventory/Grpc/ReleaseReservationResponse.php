<?php

namespace App\Shared\Inventory\Grpc;

class ReleaseReservationResponse extends WireMessage
{
    public bool $success = false;
    public string $message = '';

    public function serializeToString(): string
    {
        return '';
    }

    public function mergeFromString(string $payload): void
    {
        $fields = self::fields($payload);
        $this->success = (bool) ($fields[1][0] ?? 0);
        $this->message = (string) ($fields[2][0] ?? '');
    }

    public static function decode(string $payload): self
    {
        $message = new self;
        $message->mergeFromString($payload);

        return $message;
    }
}
