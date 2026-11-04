<?php

namespace App\Shared\Inventory\Grpc;

use App\Shared\Inventory\Contracts\InventoryGateway;
use App\Shared\Inventory\Exceptions\InventoryReservationRejectedException;
use App\Shared\Inventory\Exceptions\InventoryServiceUnavailableException;
use Grpc\ChannelCredentials;
use Grpc\UnaryCall;
use Throwable;

class GrpcInventoryGateway implements InventoryGateway
{
    private ?InventoryServiceClient $client = null;

    public function reserve(string $reservationId, array $items): void
    {
        $request = new ReserveStockRequest(
            $reservationId,
            array_map(
                fn (array $item) => new ReservationItem(
                    (int) $item['product_id'],
                    (int) $item['quantity'],
                ),
                $items,
            ),
        );

        try {
            $call = $this->client()->reserveStock(
                $request,
                options: $this->callOptions(),
            );
        } catch (Throwable $exception) {
            throw new InventoryServiceUnavailableException(
                'Inventory service is temporarily unavailable. Please try again shortly.',
                previous: $exception,
            );
        }

        /** @var ReserveStockResponse $response */
        $response = $this->wait($call);

        if ($response->status === ReserveStockResponse::INTERNAL_ERROR) {
            throw new InventoryServiceUnavailableException(
                'Inventory service is temporarily unavailable. Please try again shortly.',
            );
        }

        if (! $response->success) {
            throw new InventoryReservationRejectedException(
                $response->message ?: 'Inventory cannot reserve the requested quantities.',
            );
        }
    }

    public function release(string $reservationId): void
    {
        try {
            $call = $this->client()->releaseReservation(
                new ReleaseReservationRequest($reservationId),
                options: $this->callOptions(),
            );
        } catch (Throwable $exception) {
            throw new InventoryServiceUnavailableException(
                'Inventory service is temporarily unavailable. Please try again shortly.',
                previous: $exception,
            );
        }

        /** @var ReleaseReservationResponse $response */
        $response = $this->wait($call);

        if (! $response->success) {
            throw new InventoryServiceUnavailableException(
                $response->message ?: 'Inventory could not release the reservation.',
            );
        }
    }

    private function callOptions(): array
    {
        return ['timeout' => (int) config('inventory.timeout_us')];
    }

    private function client(): InventoryServiceClient
    {
        return $this->client ??= new InventoryServiceClient(
            (string) config('inventory.target'),
            ['credentials' => ChannelCredentials::createInsecure()],
        );
    }

    private function wait(UnaryCall $call): object
    {
        try {
            [$response, $status] = $call->wait();
        } catch (Throwable $exception) {
            throw new InventoryServiceUnavailableException(
                'Inventory service is temporarily unavailable. Please try again shortly.',
                previous: $exception,
            );
        }

        if (($status->code ?? 2) !== 0 || $response === null) {
            throw new InventoryServiceUnavailableException(
                'Inventory service is temporarily unavailable. Please try again shortly.',
            );
        }

        return $response;
    }
}
