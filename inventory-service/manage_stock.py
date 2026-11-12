"""Small maintenance CLI for manually inspecting and changing inventory.

Examples (inside the inventory container):
    python manage_stock.py list
    python manage_stock.py set 101 12
    python manage_stock.py add 101 5
"""

import argparse

from app.database import SessionLocal
from app.models import InventoryItem
from app.seed import DEMO_STOCK


MAX_BIGINT = 9_223_372_036_854_775_807
MAX_INTEGER = 2_147_483_647


def product_id_value(raw_value: str) -> int:
    value = int(raw_value)

    if value <= 0 or value > MAX_BIGINT:
        raise argparse.ArgumentTypeError("product_id must be a positive bigint")

    return value


def quantity_value(raw_value: str) -> int:
    value = int(raw_value)

    if value < 0 or value > MAX_INTEGER:
        raise argparse.ArgumentTypeError(
            "quantity must be between 0 and 2147483647"
        )

    return value


def positive_quantity_value(raw_value: str) -> int:
    value = quantity_value(raw_value)

    if value == 0:
        raise argparse.ArgumentTypeError("quantity must be greater than zero")

    return value


def list_stock() -> None:
    with SessionLocal() as session:
        items = (
            session.query(InventoryItem)
            .order_by(InventoryItem.product_id)
            .all()
        )

        print("PRODUCT_ID\tAVAILABLE_QUANTITY")
        for item in items:
            print(f"{item.product_id}\t\t{item.available_quantity}")


def set_stock(product_id: int, quantity: int) -> None:
    with SessionLocal() as session:
        with session.begin():
            item = (
                session.query(InventoryItem)
                .filter_by(product_id=product_id)
                .with_for_update()
                .one_or_none()
            )

            if item is None:
                item = InventoryItem(
                    product_id=product_id,
                    available_quantity=quantity,
                )
                session.add(item)
            else:
                item.available_quantity = quantity

        print(f"Product {product_id} quantity set to {quantity}.")


def add_stock(product_id: int, quantity: int) -> None:
    with SessionLocal() as session:
        with session.begin():
            item = (
                session.query(InventoryItem)
                .filter_by(product_id=product_id)
                .with_for_update()
                .one_or_none()
            )

            if item is None:
                item = InventoryItem(
                    product_id=product_id,
                    available_quantity=quantity,
                )
                session.add(item)
            else:
                new_quantity = item.available_quantity + quantity

                if new_quantity > MAX_INTEGER:
                    raise ValueError("resulting quantity is too large")

                item.available_quantity = new_quantity

        print(f"Added {quantity} units to product {product_id}.")


def reset_demo_stock() -> None:
    with SessionLocal() as session:
        with session.begin():
            for product_id, quantity in DEMO_STOCK.items():
                item = (
                    session.query(InventoryItem)
                    .filter_by(product_id=product_id)
                    .with_for_update()
                    .one_or_none()
                )

                if item is None:
                    session.add(
                        InventoryItem(
                            product_id=product_id,
                            available_quantity=quantity,
                        )
                    )
                else:
                    item.available_quantity = quantity

    print("Demo inventory quantities were restored.")


def build_parser() -> argparse.ArgumentParser:
    parser = argparse.ArgumentParser(
        description="Inspect or manually change inventory quantities."
    )
    commands = parser.add_subparsers(dest="command", required=True)

    commands.add_parser("list", help="List all inventory quantities")
    commands.add_parser(
        "reset-demo",
        help="Restore seeded quantities before a demonstration",
    )

    set_parser = commands.add_parser("set")
    set_parser.add_argument("product_id", type=product_id_value)
    set_parser.add_argument("quantity", type=quantity_value)

    add_parser = commands.add_parser("add")
    add_parser.add_argument("product_id", type=product_id_value)
    add_parser.add_argument("quantity", type=positive_quantity_value)

    return parser


def main() -> None:
    arguments = build_parser().parse_args()

    if arguments.command == "list":
        list_stock()
    elif arguments.command == "reset-demo":
        reset_demo_stock()
    elif arguments.command == "set":
        set_stock(arguments.product_id, arguments.quantity)
    else:
        add_stock(arguments.product_id, arguments.quantity)


if __name__ == "__main__":
    main()
