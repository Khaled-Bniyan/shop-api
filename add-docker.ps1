# Creates the Docker files for the project (3 separate containers: app, nginx, db).
# Run from the project folder (where the file 'artisan' is):
#   powershell -ExecutionPolicy Bypass -File .\add-docker.ps1

$utf8 = New-Object System.Text.UTF8Encoding($false)

function Write-Lf($path, $content) {
    $dir = Split-Path $path -Parent
    if ($dir -and -not (Test-Path $dir)) { New-Item -ItemType Directory -Path $dir -Force | Out-Null }
    $lf = $content -replace "`r`n", "`n"
    [System.IO.File]::WriteAllText($path, $lf, $utf8)
    Write-Host "created: $path" -ForegroundColor Green
}

if (-not (Test-Path "$PWD\artisan")) {
    Write-Host "ERROR: run this script from the project folder (where 'artisan' is)." -ForegroundColor Red
    exit 1
}

$dockerfile = @'
FROM php:8.4-fpm

RUN apt-get update && apt-get install -y \
        git unzip libzip-dev libpng-dev libonig-dev libxml2-dev libsqlite3-dev \
    && docker-php-ext-install pdo_mysql pdo_sqlite mbstring zip gd bcmath \
    && rm -rf /var/lib/apt/lists/*

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html
'@

$compose = @'
services:
  app:
    build: .
    container_name: shop-app
    working_dir: /var/www/html
    volumes:
      - ./:/var/www/html
    environment:
      DB_CONNECTION: mysql
      DB_HOST: db
      DB_PORT: 3306
      DB_DATABASE: shop
      DB_USERNAME: root
      DB_PASSWORD: root
    depends_on:
      db:
        condition: service_healthy
    command: >
      sh -c "composer install --no-interaction &&
             (php artisan storage:link || true) &&
             php artisan migrate --force &&
             php-fpm"

  nginx:
    image: nginx:1.27-alpine
    container_name: shop-nginx
    ports:
      - "8000:80"
    volumes:
      - ./:/var/www/html
      - ./docker/nginx/default.conf:/etc/nginx/conf.d/default.conf
    depends_on:
      - app

  db:
    image: mysql:8.0
    container_name: shop-db
    environment:
      MYSQL_DATABASE: shop
      MYSQL_ROOT_PASSWORD: root
    volumes:
      - dbdata:/var/lib/mysql
    healthcheck:
      test: ["CMD", "mysqladmin", "ping", "-h", "localhost", "-proot"]
      interval: 5s
      timeout: 5s
      retries: 40

volumes:
  dbdata:
'@

$nginx = @'
server {
    listen 80;
    index index.php;
    root /var/www/html/public;
    client_max_body_size 10M;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        fastcgi_pass app:9000;
        fastcgi_index index.php;
        include fastcgi_params;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        fastcgi_read_timeout 120;
    }
}
'@

$dockerignore = @'
.git
node_modules
storage/logs/*
'@

Write-Lf "$PWD\Dockerfile" $dockerfile
Write-Lf "$PWD\docker-compose.yml" $compose
Write-Lf "$PWD\docker\nginx\default.conf" $nginx
Write-Lf "$PWD\.dockerignore" $dockerignore

Write-Host "`nDone. Next:" -ForegroundColor Cyan
Write-Host "  1) Stop 'php artisan serve' (Ctrl+C) - port 8000 will be used by Docker."
Write-Host "  2) docker compose up -d --build"
Write-Host "  3) docker compose exec app php artisan db:seed"
Write-Host "  4) docker compose ps"