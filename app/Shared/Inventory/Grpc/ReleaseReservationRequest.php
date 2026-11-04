<?php

namespace App\Shared\Inventory\Grpc;

class ReleaseReservationRequest extends WireMessage
{
    public function __construct(public string $reservationId = '')
    {
    }

    public function serializeToString(): string
    {
        return self::string(1, $this->reservationId);
    }
}
