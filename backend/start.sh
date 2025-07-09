#!/bin/bash

# Backend Startup Script for Laravel PHP Backend
# This script starts the Laravel backend server

echo "Starting Laravel PHP Backend..."

# Navigate to backend directory
cd /app/backend

# Check if .env file exists
if [ ! -f .env ]; then
    echo "Error: .env file not found!"
    echo "Please ensure .env file is properly configured."
    exit 1
fi

# Check if vendor directory exists (composer dependencies)
if [ ! -d vendor ]; then
    echo "Installing composer dependencies..."
    composer install --no-dev --optimize-autoloader
fi

# Generate application key if not set
if ! grep -q "APP_KEY=base64:" .env; then
    echo "Generating application key..."
    php artisan key:generate
fi

# Clear and cache configuration
echo "Optimizing application..."
php artisan config:cache
php artisan route:cache

# Start Laravel server
echo "Starting Laravel server on 0.0.0.0:8001..."
php artisan serve --host=0.0.0.0 --port=8001

# If server fails to start, show error
if [ $? -ne 0 ]; then
    echo "Error: Failed to start Laravel server!"
    echo "Please check the logs for more information."
    exit 1
fi