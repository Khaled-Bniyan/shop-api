# Internet Applications Project 2026 - Shop API

Laravel modular monolith (Users, Products, Orders, Notifications) with JWT authentication.

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
