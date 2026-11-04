
from sqlalchemy import text

from app.database import engine


statements = [
    """
    DO $$
    BEGIN
        IF NOT EXISTS (
            SELECT 1 FROM pg_constraint
            WHERE conname = 'ck_inventory_items_quantity_nonnegative'
            AND conrelid = 'inventory_items'::regclass
        ) THEN
            ALTER TABLE inventory_items
            ADD CONSTRAINT ck_inventory_items_quantity_nonnegative
            CHECK (available_quantity >= 0);
        END IF;
    END $$;
    """,
    """
    DO $$
    BEGIN
        IF NOT EXISTS (
            SELECT 1 FROM pg_constraint
            WHERE conname = 'ck_reservations_valid_status'
            AND conrelid = 'reservations'::regclass
        ) THEN
            ALTER TABLE reservations
            ADD CONSTRAINT ck_reservations_valid_status
            CHECK (status IN ('RESERVED', 'RELEASED'));
        END IF;
    END $$;
    """,
    """
    DO $$
    BEGIN
        IF NOT EXISTS (
            SELECT 1 FROM pg_constraint
            WHERE conname = 'ck_reservation_lines_quantity_positive'
            AND conrelid = 'reservation_lines'::regclass
        ) THEN
            ALTER TABLE reservation_lines
            ADD CONSTRAINT ck_reservation_lines_quantity_positive
            CHECK (quantity > 0);
        END IF;
    END $$;
    """,
    """
    DO $$
    BEGIN
        IF NOT EXISTS (
            SELECT 1 FROM pg_constraint
            WHERE conname = 'uq_reservation_lines_reservation_product'
            AND conrelid = 'reservation_lines'::regclass
        ) THEN
            ALTER TABLE reservation_lines
            ADD CONSTRAINT uq_reservation_lines_reservation_product
            UNIQUE (reservation_id, product_id);
        END IF;
    END $$;
    """,
    """
    CREATE INDEX IF NOT EXISTS ix_reservation_lines_reservation_id
    ON reservation_lines (reservation_id);
    """,
]


def main():
    with engine.begin() as connection:
        for statement in statements:
            connection.execute(text(statement))

    print("Database constraints applied successfully")


if __name__ == "__main__":
    main()
