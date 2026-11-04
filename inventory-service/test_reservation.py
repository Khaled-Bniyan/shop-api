
from app.inventory import reserve_stock

# اختبار طلب يحتاج كمية أكبر من المتوفر
result = reserve_stock(
    "test-reservation-001",
    [
        {"product_id": 1, "quantity": 3},
        {"product_id": 2, "quantity": 2},
    ],
)

print("Test result:", result)
