
from concurrent import futures

import grpc

from inventory.v1 import inventory_pb2
from inventory.v1 import inventory_pb2_grpc

from app.inventory import (
    get_available_quantities,
    release_reservation,
    reserve_stock,
)


class InventoryService(
    inventory_pb2_grpc.InventoryServiceServicer
):

    def GetStock(self, request, context):
        try:
            quantities = get_available_quantities(
                list(request.product_ids)
            )
        except ValueError as error:
            context.abort(
                grpc.StatusCode.INVALID_ARGUMENT,
                str(error),
            )
        except Exception:
            context.abort(
                grpc.StatusCode.UNAVAILABLE,
                "Inventory storage is temporarily unavailable",
            )

        return inventory_pb2.GetStockResponse(
            items=[
                inventory_pb2.StockItem(
                    product_id=product_id,
                    available_quantity=quantity,
                )
                for product_id, quantity in quantities.items()
            ]
        )

    def ReserveStock(self, request, context):

        # تحويل عناصر طلب gRPC إلى بيانات Python
        items = [
            {
                "product_id": item.product_id,
                "quantity": item.quantity,
            }
            for item in request.items
        ]

        result = reserve_stock(
            request.reservation_id,
            items,
        )

        if result["status"] == "INTERNAL_ERROR":
            context.abort(
                grpc.StatusCode.UNAVAILABLE,
                "Inventory storage is temporarily unavailable",
            )

        status_map = {
            "RESERVED": inventory_pb2.RESERVED,
            "INSUFFICIENT_STOCK": inventory_pb2.INSUFFICIENT_STOCK,
            "INVALID_REQUEST": inventory_pb2.INVALID_REQUEST,
            "INTERNAL_ERROR": inventory_pb2.INTERNAL_ERROR,
        }

        return inventory_pb2.ReserveStockResponse(
            success=result["success"],
            status=status_map[result["status"]],
            message=result["message"],
            reservation_id=result["reservation_id"],
        )

    def ReleaseReservation(self, request, context):

        result = release_reservation(request.reservation_id)

        if not result["success"]:
            context.abort(
                grpc.StatusCode.UNAVAILABLE,
                "Inventory storage is temporarily unavailable",
            )

        return inventory_pb2.ReleaseReservationResponse(
            success=result["success"],
            message=result["message"],
        )


def serve():

    server = grpc.server(
        futures.ThreadPoolExecutor(max_workers=10)
    )

    inventory_pb2_grpc.add_InventoryServiceServicer_to_server(
        InventoryService(),
        server,
    )

    port = server.add_insecure_port("[::]:50051")

    if port == 0:
        raise RuntimeError("Could not bind gRPC port")

    server.start()

    print("Inventory gRPC Server is running on port 50051")

    server.wait_for_termination()


if __name__ == "__main__":
    serve()
