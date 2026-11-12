
from app.database import Base, SessionLocal, engine
from app.models import InventoryItem


DEMO_STOCK = {
    1: 5,
    2: 1,
    101: 12,
    102: 8,
    103: 15,
    104: 20,
    105: 25,
    106: 9,
    107: 6,
    108: 4,
    9_000_001: 1,
}


def seed() -> None:
    Base.metadata.create_all(bind=engine)

    with SessionLocal() as session:
        with session.begin():
            for product_id, quantity in DEMO_STOCK.items():
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
