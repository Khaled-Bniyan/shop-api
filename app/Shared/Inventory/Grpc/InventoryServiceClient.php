<?php

namespace App\Shared\Inventory\Grpc;

use Grpc\BaseStub;
use Grpc\UnaryCall;

class InventoryServiceClient extends BaseStub
{
    public function reserveStock(
        ReserveStockRequest $request,
        array $metadata = [],
        array $options = [],
    ): UnaryCall {
        return $this->_simpleRequest(
            '/inventory.v1.InventoryService/ReserveStock',
            $request,
            [ReserveStockResponse::class, 'decode'],
            $metadata,
            $options,
        );
    }

    public function releaseReservation(
        ReleaseReservationRequest $request,
        array $metadata = [],
        array $options = [],
    ): UnaryCall {
        return $this->_simpleRequest(
            '/inventory.v1.InventoryService/ReleaseReservation',
            $request,
            [ReleaseReservationResponse::class, 'decode'],
            $metadata,
            $options,
        );
    }
}
