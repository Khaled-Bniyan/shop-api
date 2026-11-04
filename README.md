# Internet Applications Project 2026 - Shop API

Laravel modular monolith (Users, Products, Orders, Notifications) with JWT authentication,
plus an isolated Python inventory service connected over gRPC.

## Run with Docker

```bash
cp .env.example .env
docker compose up -d --build
docker compose logs -f app        # wait until migrations finish, then Ctrl+C
docker compose exec app php artisan key:generate
docker compose exec app php artisan jwt:secret
docker compose exec app php artisan db:seed
```

On Windows PowerShell, use `copy .env.example .env` instead of `cp`.

API base URL: `http://localhost:8000/api/v1`

## Test accounts (created by the seeder)

| Role     | Email            | Password    |
|----------|------------------|-------------|
| Admin    | admin@shop.test  | password123 |
| Customer | a@shop.test      | password123 |
| Customer | b@shop.test      | password123 |

## Run the tests

```bash
docker compose exec app php artisan test
```

## API documentation

Import `project_api_collection.json` into Postman and run it in order.

## Modules

- `app/Modules/Users`: register, login, logout, JWT
- `app/Modules/Products`: categories and products (image upload, filter, pagination)
- `app/Modules/Orders`: create orders, view them, change status (admin only)
- `app/Modules/Notifications`: in-app notifications
- `app/Shared`: shared middleware and events

## Requirement 2: inventory integration

The system runs two independent services and two isolated databases:

| Service | Technology | Database | Responsibility |
|---|---|---|---|
| Shop API | Laravel / PHP | MySQL | JWT API, products, orders, notifications |
| Inventory | Python / gRPC | PostgreSQL | available quantities and reservations |

The Laravel application never reads or writes the inventory database. Its only
inventory integration is the versioned gRPC contract at
[`proto/inventory/V1/inventory.proto`](proto/inventory/V1/inventory.proto).
The inventory port is internal to Docker Compose and is not published to the host.

When an order is created, the API first sends all requested quantities in one
`ReserveStock` call. The order is created only after a successful reservation.
The inventory service locks all relevant product rows in a consistent order,
checks every requested quantity before changing any row, and records the
reservation in the same PostgreSQL transaction. A cancellation releases the
same reservation; a failed Laravel database transaction also attempts to release it.

An insufficient quantity returns HTTP `422` and no order is created. A gRPC
deadline, unavailable service, or internal inventory failure returns HTTP `503`.
Set `INVENTORY_GRPC_TIMEOUT_US` in `.env` to adjust the deadline (default:
3 seconds). The inventory seed initializes product `1` with `5` units and
product `2` with `1` unit for the demonstration.

### Race-condition demonstration

After a teammate starts the stack, run this inside the inventory container:

```bash
docker compose exec inventory-service python demo_concurrent_reservation.py
```

It sends two simultaneous reservations of four units for product `1`. Exactly
one succeeds, the other receives `INSUFFICIENT_STOCK`, and the winner is released
at the end so the command is repeatable.
