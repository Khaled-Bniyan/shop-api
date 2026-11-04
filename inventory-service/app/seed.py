
from app.database import SessionLocal
from app.models import InventoryItem

with SessionLocal() as session:
    with session.begin():
        products = {
            1: 5,
            2: 1,
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

print("Test inventory created successfully")
