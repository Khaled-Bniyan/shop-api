
from sqlalchemy import (
    Column,
    Integer,
    BigInteger,
    String,
    ForeignKey,
)
from app.database import Base


class InventoryItem(Base):
    __tablename__ = "inventory_items"

    product_id = Column(BigInteger, primary_key=True)
    available_quantity = Column(Integer, nullable=False, default=0)


class Reservation(Base):
    __tablename__ = "reservations"

    reservation_id = Column(String(100), primary_key=True)
    status = Column(String(30), nullable=False)


class ReservationLine(Base):
    __tablename__ = "reservation_lines"

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
