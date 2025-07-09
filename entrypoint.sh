#!/bin/sh
set -e

# Start PHP-FPM
echo "Starting PHP-FPM"
php-fpm &
PHP_FPM_PID=$!

# Start Nginx
echo "Starting Nginx"
nginx -g "daemon off;" &
NGINX_PID=$!

# Handle termination signals
trap "kill $PHP_FPM_PID $NGINX_PID; exit 0" SIGTERM SIGINT

# Check if processes are still running
while kill -0 $PHP_FPM_PID 2>/dev/null && kill -0 $NGINX_PID 2>/dev/null; do
    sleep 1
done

# If we get here, one of the processes died
if kill -0 $PHP_FPM_PID 2>/dev/null; then
    echo "Nginx died, shutting down PHP-FPM..."
    kill $PHP_FPM_PID
else
    echo "PHP-FPM died, shutting down Nginx..."
    kill $NGINX_PID
fi

exit 1

