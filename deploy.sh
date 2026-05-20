#!/bin/bash
set -e

DEPLOY_DIR="/root/serverflow"

echo "==> Pulling latest code..."
cd "$DEPLOY_DIR"
git pull origin main

echo "==> Installing PHP dependencies..."
docker compose -f docker-compose.yml -f docker-compose.prod.yml run --rm app composer install --no-dev --optimize-autoloader --no-interaction

echo "==> Building frontend assets..."
npm ci --prefer-offline
npm run build

echo "==> Building & starting containers..."
docker compose -f docker-compose.yml -f docker-compose.prod.yml up --build -d

echo "==> Waiting for DB to be ready..."
sleep 5

echo "==> Running migrations..."
docker compose -f docker-compose.yml -f docker-compose.prod.yml exec -T app php artisan migrate --force

echo "==> Caching config / routes / views..."
docker compose -f docker-compose.yml -f docker-compose.prod.yml exec -T app php artisan config:cache
docker compose -f docker-compose.yml -f docker-compose.prod.yml exec -T app php artisan route:cache
docker compose -f docker-compose.yml -f docker-compose.prod.yml exec -T app php artisan view:clear
docker compose -f docker-compose.yml -f docker-compose.prod.yml exec -T app php artisan storage:link

echo "==> Done. ServerFlow is up at http://serverflow.careflow-pflege.de"
