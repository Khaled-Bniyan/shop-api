
from sqlalchemy import func

from app.database import SessionLocal
from app.models import InventoryItem, Reservation, ReservationLine


def reserve_stock(reservation_id: str, items: list):
    # التحقق من صحة الطلب
    if not reservation_id or not items:
        return {
            "success": False,
            "status": "INVALID_REQUEST",
            "message": "Reservation ID and items are required",
            "reservation_id": reservation_id,
        }

    # تجميع المنتجات المكررة والتحقق من الكميات
    quantities = {}

    for item in items:
        product_id = int(item["product_id"])
        quantity = int(item["quantity"])

        if product_id <= 0 or quantity <= 0:
            return {
                "success": False,
                "status": "INVALID_REQUEST",
                "message": "Product IDs and quantities must be positive",
                "reservation_id": reservation_id,
            }

        quantities[product_id] = quantities.get(product_id, 0) + quantity

    try:
        with SessionLocal() as session:
            with session.begin():

                # منع تكرار تنفيذ الحجز عند إعادة إرسال الطلب
                existing = session.get(Reservation, reservation_id)

                if existing:
                    if existing.status == "RESERVED":
                        return {
                            "success": True,
                            "status": "RESERVED",
                            "message": "Reservation already exists",
                            "reservation_id": reservation_id,
                        }

                    return {
                        "success": False,
                        "status": "INVALID_REQUEST",
                        "message": "Reservation ID was already used",
                        "reservation_id": reservation_id,
                    }

                product_ids = sorted(quantities.keys())

                # قفل المنتجات حتى لا يحجزها طلب آخر بالتزامن
                products = (
                    session.query(InventoryItem)
                    .filter(InventoryItem.product_id.in_(product_ids))
                    .order_by(InventoryItem.product_id)
                    .with_for_update()
                    .all()
                )

                products_by_id = {
                    product.product_id: product
                    for product in products
                }

                # التأكد من وجود كل المنتجات
                if len(products_by_id) != len(product_ids):
                    return {
                        "success": False,
                        "status": "INVALID_REQUEST",
                        "message": "One or more products do not exist",
                        "reservation_id": reservation_id,
                    }

                # التحقق من توفر جميع الكميات قبل خصم أي كمية
                for product_id, quantity in quantities.items():
                    product = products_by_id[product_id]

                    if product.available_quantity < quantity:
                        return {
                            "success": False,
                            "status": "INSUFFICIENT_STOCK",
                            "message": f"Insufficient stock for product {product_id}",
                            "reservation_id": reservation_id,
                        }

                # خصم الكميات بعد نجاح جميع عمليات التحقق
                for product_id, quantity in quantities.items():
                    products_by_id[product_id].available_quantity -= quantity

                # تسجيل الحجز
                reservation = Reservation(
                    reservation_id=reservation_id,
                    status="RESERVED",
                )
                session.add(reservation)

                for product_id, quantity in quantities.items():
                    session.add(
                        ReservationLine(
                            reservation_id=reservation_id,
                            product_id=product_id,
                            quantity=quantity,
                        )
                    )

        return {
            "success": True,
            "status": "RESERVED",
            "message": "Stock reserved successfully",
            "reservation_id": reservation_id,
        }

    except Exception as error:
        return {
            "success": False,
            "status": "INVALID_REQUEST",
            "message": "Reservation failed",
            "reservation_id": reservation_id,
        }


#----------------------------------------------


def release_reservation(reservation_id: str):
    if not reservation_id:
        return {
            "success": False,
            "message": "Reservation ID is required",
        }

    try:
        with SessionLocal() as session:
            with session.begin():

                reservation = (
                    session.query(Reservation)
                    .filter_by(reservation_id=reservation_id)
                    .with_for_update()
                    .one_or_none()
                )

                if reservation is None:
                    return {
                        "success": False,
                        "message": "Reservation not found",
                    }

                # إعادة المحاولة لا تعيد الكميات مرتين
                if reservation.status == "RELEASED":
                    return {
                        "success": True,
                        "message": "Reservation already released",
                    }

                if reservation.status != "RESERVED":
                    return {
                        "success": False,
                        "message": "Reservation cannot be released",
                    }

                lines = (
                    session.query(ReservationLine)
                    .filter_by(reservation_id=reservation_id)
                    .all()
                )

                product_ids = sorted({
                    line.product_id for line in lines
                })

                products = (
                    session.query(InventoryItem)
                    .filter(InventoryItem.product_id.in_(product_ids))
                    .order_by(InventoryItem.product_id)
                    .with_for_update()
                    .all()
                )

                products_by_id = {
                    product.product_id: product
                    for product in products
                }

                if len(products_by_id) != len(product_ids):
                    raise ValueError("Inventory product is missing")

                for line in lines:
                    products_by_id[line.product_id].available_quantity += (
                        line.quantity
                    )

                reservation.status = "RELEASED"

        return {
            "success": True,
            "message": "Reservation released successfully",
        }

    except Exception:
        return {
            "success": False,
            "message": "Failed to release reservation",
        }
