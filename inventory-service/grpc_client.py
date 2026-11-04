
import uuid
import grpc

from inventory.v1 import inventory_pb2
from inventory.v1 import inventory_pb2_grpc


def main():
    channel = grpc.insecure_channel("localhost:50051")

    try:
        stub = inventory_pb2_grpc.InventoryServiceStub(channel)

        reservation_id = f"grpc-test-{uuid.uuid4()}"

        request = inventory_pb2.ReserveStockRequest(
            reservation_id=reservation_id,
            items=[
                inventory_pb2.ReservationItem(
                    product_id=1,
                    quantity=2,
                )
            ],
        )

        # Reserve stock with a 5-second timeout.
        try:
            response = stub.ReserveStock(request, timeout=5)

        except grpc.RpcError as error:
            print("Reservation request failed")
            print("gRPC Status:", error.code())
            print("Details:", error.details())
            return

        print("\n--- ReserveStock Response ---")
        print(f"Success: {response.success}")
        print(
            "Status:",
            inventory_pb2.ReservationStatus.Name(response.status),
        )
        print(f"Message: {response.message}")
        print(f"Reservation ID: {response.reservation_id}")

        if not response.success:
            return

        release_request = inventory_pb2.ReleaseReservationRequest(
            reservation_id=reservation_id
        )

        # Release stock with a 5-second timeout.
        try:
            release_response = stub.ReleaseReservation(
                release_request,
                timeout=5,
            )

        except grpc.RpcError as error:
            print("\nRelease request failed")
            print("gRPC Status:", error.code())
            print("Details:", error.details())
            return

        print("\n--- ReleaseReservation Response ---")
        print(f"Success: {release_response.success}")
        print(f"Message: {release_response.message}")

    finally:
        channel.close()


if __name__ == "__main__":
    main()
