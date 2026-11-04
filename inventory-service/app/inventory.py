
import logging

from sqlalchemy import text

from app.database import SessionLocal
from app.models import InventoryItem, Reservation, ReservationLine


logger = logging.getLogger(__name__)

MAX_BIGINT = 9_223_372_036_854_775_807
MAX_INTEGER = 2_147_483_647


def _positive_integer(value, field_name):
    if isinstance(value, bool):
        raise ValueError(f"{field_name} must be an integer")

    if isinstance(value, int):
        number = value
    elif isinstance(value, str) and value.strip():
        number = int(value.strip())
    else:
        raise ValueError(f"{field_name} must be an integer")

    if number <= 0:
        raise ValueError(f"{field_name} must be positive")

    return number


def _validate_request(reservation_id, items):
    if not isinstance(reservation_id, str):
        raise ValueError("Reservation ID must be a string")

    reservation_id = reservation_id.strip()

    if not reservation_id or len(reservation_id) > 100:
        raise ValueError("Reservation ID must contain 1 to 100 characters")

    if not isinstance(items, list) or not items:
        raise ValueError("At least one item is required")

    quantities = {}

    for item in items:
        if not isinstance(item, dict):
            raise ValueError("Each item must be an object")

        product_id = _positive_integer(
            item.get("product_id"), "Product ID"
        )
        quantity = _positive_integer(
            item.get("quantity"), "Quantity"
        )

        if product_id > MAX_BIGINT:
            raise ValueError("Product ID is too large")

        total_quantity = quantities.get(product_id, 0) + quantity

        if total_quantity > MAX_INTEGER:
            raise ValueError("Total product quantity is too large")

        quantities[product_id] = total_quantity

    return reservation_id, quantities


def _lock_reservation_id(session, reservation_id):
    session.execute(
        text(
            "SELECT pg_advisory_xact_lock(hashtext(:reservation_id))"
        ),
        {"reservation_id": reservation_id},
    )


def _existing_reservation_result(
    session,
    reservation,
    reservation_id,
    requested_quantities,
):
    existing_lines = (
        session.query(ReservationLine)
        .filter_by(reservation_id=reservation_id)
        .all()
    )

    existing_quantities = {
        line.product_id: line.quantity
        for line in existing_lines
    }

    if reservation.status == "RESERVED":
        if existing_quantities == requested_quantities:
            return {
                "success": True,
                "status": "RESERVED",
                "message": "Reservation already exists",
                "reservation_id": reservation_id,
            }

        return {
            "success": False,
            "status": "INVALID_REQUEST",
            "message": "Reservation ID was used with different items",
            "reservation_id": reservation_id,
        }

    return {
        "success": False,
        "status": "INVALID_REQUEST",
        "message": "Reservation ID was already used",
        "reservation_id": reservation_id,
    }


def reserve_stock(reservation_id: str, items: list):
    try:
        reservation_id, quantities = _validate_request(
            reservation_id, items
        )
    except (ValueError, TypeError) as error:
        return {
            "success": False,
            "status": "INVALID_REQUEST",
            "message": str(error),
            "reservation_id": reservation_id,
        }

    try:
        with SessionLocal() as session:
            with session.begin():

                # Serialize requests using the same reservation ID.
                _lock_reservation_id(session, reservation_id)

                existing = session.get(Reservation, reservation_id)

                if existing:
                    return _existing_reservation_result(
                        session,
                        existing,
                        reservation_id,
                        quantities,
                    )

                product_ids = sorted(quantities.keys())

                # Lock inventory rows in a consistent order.
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
                    return {
                        "success": False,
                        "status": "INVALID_REQUEST",
                        "message": "One or more products do not exist",
                        "reservation_id": reservation_id,
                    }

                # Check every product before changing any quantity.
                for product_id, quantity in quantities.items():
                    product = products_by_id[product_id]

                    if product.available_quantity < quantity:
                        return {
                            "success": False,
                            "status": "INSUFFICIENT_STOCK",
                            "message": (
                                f"Insufficient stock for product {product_id}"
                            ),
                            "reservation_id": reservation_id,
                        }

                # Decrease stock only after all checks pass.
                for product_id, quantity in quantities.items():
                    products_by_id[
                        product_id
                    ].available_quantity -= quantity

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

    except Exception:
        logger.exception(
            "Failed to reserve stock. Reservation ID: %s",
            reservation_id,
        )

        return {
            "success": False,
            "status": "INTERNAL_ERROR",
            "message": "An internal inventory error occurred",
            "reservation_id": reservation_id,
        }


def release_reservation(reservation_id: str):
    if not isinstance(reservation_id, str) or not reservation_id.strip():
        return {
            "success": False,
            "message": "Reservation ID is required",
        }

    reservation_id = reservation_id.strip()

    if len(reservation_id) > 100:
        return {
            "success": False,
            "message": "Reservation ID is too long",
        }

    try:
        with SessionLocal() as session:
            with session.begin():

                # Coordinate release with reserve requests using the same ID.
                _lock_reservation_id(session, reservation_id)

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

                if not lines:
                    raise ValueError("Reservation has no inventory lines")

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
                    products_by_id[
                        line.product_id
                    ].available_quantity += line.quantity

                reservation.status = "RELEASED"

        return {
            "success": True,
            "message": "Reservation released successfully",
        }

    except Exception:
        logger.exception(
            "Failed to release reservation. Reservation ID: %s",
            reservation_id,
        )

        return {
            "success": False,
            "message": "An internal inventory error occurred",
        }
