"""Demonstrate that exactly one competing stock reservation succeeds.

Run inside the inventory container after the Compose stack is running:
    python demo_concurrent_reservation.py
"""

from concurrent.futures import ThreadPoolExecutor
import uuid

import grpc

from inventory.v1 import inventory_pb2, inventory_pb2_grpc


def reserve(reservation_id: str):
    with grpc.insecure_channel("localhost:50051") as channel:
        stub = inventory_pb2_grpc.InventoryServiceStub(channel)
        return reservation_id, stub.ReserveStock(
            inventory_pb2.ReserveStockRequest(
                reservation_id=reservation_id,
                items=[
                    inventory_pb2.ReservationItem(
                        product_id=1,
                        quantity=4,
                    )
                ],
            ),
            timeout=5,
        )


def main() -> int:
    reservation_ids = [
        f"race-demo-{uuid.uuid4()}",
        f"race-demo-{uuid.uuid4()}",
    ]

    with ThreadPoolExecutor(max_workers=2) as executor:
        results = list(executor.map(reserve, reservation_ids))

    successful = [
        (reservation_id, response)
        for reservation_id, response in results
        if response.success
    ]
    rejected = [
        response
        for _, response in results
        if not response.success
    ]

    for reservation_id, response in results:
        status = inventory_pb2.ReservationStatus.Name(response.status)
        print(f"{reservation_id}: {status} - {response.message}")

    if len(successful) != 1 or len(rejected) != 1:
        print("Race demo failed: expected one success and one rejection.")
        return 1

    winner_id, _ = successful[0]
    with grpc.insecure_channel("localhost:50051") as channel:
        stub = inventory_pb2_grpc.InventoryServiceStub(channel)
        release = stub.ReleaseReservation(
            inventory_pb2.ReleaseReservationRequest(
                reservation_id=winner_id,
            ),
            timeout=5,
        )

    if not release.success:
        print("Race demo failed: could not release the winning reservation.")
        return 1

    print("Race demo passed: one reservation won; its stock was released.")
    return 0


if __name__ == "__main__":
    try:
        raise SystemExit(main())
    except grpc.RpcError as error:
        print(f"gRPC failure: {error.code().name} - {error.details()}")
        raise SystemExit(1) from error
