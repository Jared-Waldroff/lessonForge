#!/bin/bash
set -e

echo "Deploying LessonForge..."

# 1. Pull latest changes (if using git)
# git pull origin main

# 2. Stop existing containers
docker compose -f docker-compose.prod.yml down

# 3. Build and Start containers
docker compose -f docker-compose.prod.yml up -d --build

# 4. Install dependencies inside container
docker compose -f docker-compose.prod.yml exec -T app composer install --no-dev --optimize-autoloader

# 5. Run Database Migrations (if needed)
# docker compose -f docker-compose.prod.yml exec -T db ...

echo "Deployment finished! Application is running on Port 80."
