
from sqlalchemy import (
    Column,
    Integer,
    BigInteger,
    String,
    ForeignKey,
    CheckConstraint,
    UniqueConstraint,
    Index,
)

from app.database import Base


class InventoryItem(Base):
    __tablename__ = "inventory_items"

    __table_args__ = (
        CheckConstraint(
            "available_quantity >= 0",
            name="ck_inventory_items_quantity_nonnegative",
        ),
    )

    product_id = Column(BigInteger, primary_key=True)

    available_quantity = Column(
        Integer,
        nullable=False,
        default=0,
    )


class Reservation(Base):
    __tablename__ = "reservations"

    __table_args__ = (
        CheckConstraint(
            "status IN ('RESERVED', 'RELEASED')",
            name="ck_reservations_valid_status",
        ),
    )

    reservation_id = Column(String(100), primary_key=True)

    status = Column(String(30), nullable=False)


class ReservationLine(Base):
    __tablename__ = "reservation_lines"

    __table_args__ = (
        CheckConstraint(
            "quantity > 0",
            name="ck_reservation_lines_quantity_positive",
        ),
        UniqueConstraint(
            "reservation_id",
            "product_id",
            name="uq_reservation_lines_reservation_product",
        ),
        Index(
            "ix_reservation_lines_reservation_id",
            "reservation_id",
        ),
    )

    id = Column(Integer, primary_key=True, autoincrement=True)

    reservation_id = Column(
        String(100),
        ForeignKey("reservations.reservation_id"),
        nullable=False,
    )

    product_id = Column(
        BigInteger,
        ForeignKey("inventory_items.product_id"),
        nullable=False,
    )

    quantity = Column(Integer, nullable=False)
