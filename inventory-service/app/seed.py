
from app.database import Base, SessionLocal, engine
from app.models import InventoryItem


def seed() -> None:
    Base.metadata.create_all(bind=engine)

    with SessionLocal() as session:
        with session.begin():
            products = {
                1: 5,
                2: 1,
                9_000_001: 1,
            }

            for product_id, quantity in products.items():
                item = session.get(InventoryItem, product_id)

                if item is None:
                    session.add(
                        InventoryItem(
                            product_id=product_id,
                            available_quantity=quantity,
                        )
                    )


if __name__ == "__main__":
    seed()
