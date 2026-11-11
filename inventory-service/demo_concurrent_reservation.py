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
                        product_id=9_000_001,
                        quantity=1,
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
        (reservation_id, response)
        for reservation_id, response in results
        if not response.success
    ]

    for reservation_id, response in results:
        status = inventory_pb2.ReservationStatus.Name(response.status)
        print(f"{reservation_id}: {status} - {response.message}")

    with grpc.insecure_channel("localhost:50051") as channel:
        stub = inventory_pb2_grpc.InventoryServiceStub(channel)
        for reservation_id, _ in successful:
            release = stub.ReleaseReservation(
                inventory_pb2.ReleaseReservationRequest(
                    reservation_id=reservation_id,
                ),
                timeout=5,
            )
            if not release.success:
                print("Race demo failed: could not release a reservation.")
                return 1

    insufficient = [
        response
        for _, response in rejected
        if response.status == inventory_pb2.INSUFFICIENT_STOCK
    ]

    if len(successful) != 1 or len(insufficient) != 1:
        print(
            "Race demo failed: expected one success and one "
            "INSUFFICIENT_STOCK rejection."
        )
        return 1

    print("Race demo passed: one reservation won; its stock was released.")
    return 0


if __name__ == "__main__":
    try:
        raise SystemExit(main())
    except grpc.RpcError as error:
        print(f"gRPC failure: {error.code().name} - {error.details()}")
        raise SystemExit(1) from error
