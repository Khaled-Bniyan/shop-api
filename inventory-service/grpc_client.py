
import uuid
import grpc

from inventory.v1 import inventory_pb2
from inventory.v1 import inventory_pb2_grpc


def main():
    channel = grpc.insecure_channel("localhost:50051")

    try:
        stub = inventory_pb2_grpc.InventoryServiceStub(channel)

        # إنشاء رقم فريد للحجز
        reservation_id = f"grpc-test-{uuid.uuid4()}"

        # إرسال طلب حجز المنتج رقم 1
        request = inventory_pb2.ReserveStockRequest(
            reservation_id=reservation_id,
            items=[
                inventory_pb2.ReservationItem(
                    product_id=1,
                    quantity=2,
                )
            ],
        )

        response = stub.ReserveStock(request, timeout=5)

        print("\n--- ReserveStock Response ---")
        print(f"Success: {response.success}")
        print(f"Status: {inventory_pb2.ReservationStatus.Name(response.status)}")
        print(f"Message: {response.message}")
        print(f"Reservation ID: {response.reservation_id}")

        if not response.success:
            return

        # إرسال طلب إلغاء الحجز
        release_request = inventory_pb2.ReleaseReservationRequest(
            reservation_id=reservation_id
        )

        release_response = stub.ReleaseReservation(
            release_request,
            timeout=5,
        )

        print("\n--- ReleaseReservation Response ---")
        print(f"Success: {release_response.success}")
        print(f"Message: {release_response.message}")

    finally:
        channel.close()


if __name__ == "__main__":
    main()
