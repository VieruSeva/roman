# Docker Deployment Guide for ANIPM Website

## 🐳 Docker Architecture Overview

The ANIPM website uses a multi-stage Docker build process that creates a production-ready container with:
- **Stage 1**: Frontend build (React.js compilation)
- **Stage 2**: Backend build (PHP Laravel setup)
- **Stage 3**: Final production image (Nginx + PHP-FPM)

## 📋 Container Specifications

### Base Images
- **Frontend Build**: `node:20` (Alpine Linux)
- **Backend Build**: `php:8.2-fpm-alpine`
- **Production**: `nginx:stable-alpine`

### Service Ports
- **Container Port**: 80 (HTTP)
- **Frontend Internal**: 3000 (React dev server - build only)
- **Backend Internal**: 8001 (PHP Laravel API)

### Volume Mounts
- `/var/www/html/storage` - Laravel storage directory
- `/var/log/nginx` - Nginx access/error logs
- `/var/log/php` - PHP-FPM logs

## 🚀 Quick Docker Deployment

### Method 1: Single Container (Simplest)
```bash
# Build the image
docker build -t anipm-website .

# Run with basic configuration
docker run -d \
  --name anipm-website \
  -p 80:80 \
  -p 443:443 \
  -e FRONTEND_ENV="WDS_SOCKET_PORT=443,REACT_APP_BACKEND_URL=https://yourdomain.com" \
  anipm-website

# Check status
docker ps
docker logs anipm-website
```

### Method 2: Docker Compose (Recommended)
Create `docker-compose.yml`:
```yaml
version: '3.8'

services:
  app:
    build: .
    container_name: anipm-website
    ports:
      - "80:80"
      - "443:443"
    environment:
      - FRONTEND_ENV=WDS_SOCKET_PORT=443,REACT_APP_BACKEND_URL=https://yourdomain.com
    volumes:
      - anipm_storage:/var/www/html/storage
      - anipm_logs:/var/log/nginx
    restart: unless-stopped
    depends_on:
      - mysql
    networks:
      - anipm-network

  mysql:
    image: mysql:8.0
    container_name: anipm-mysql
    environment:
      MYSQL_ROOT_PASSWORD: your_secure_root_password
      MYSQL_DATABASE: anipm_db
      MYSQL_USER: anipm_user
      MYSQL_PASSWORD: your_secure_password
    volumes:
      - mysql_data:/var/lib/mysql
      - ./database/init.sql:/docker-entrypoint-initdb.d/init.sql
    ports:
      - "3306:3306"
    restart: unless-stopped
    command: --default-authentication-plugin=mysql_native_password
    networks:
      - anipm-network

  phpmyadmin:
    image: phpmyadmin/phpmyadmin
    container_name: anipm-phpmyadmin
    environment:
      PMA_HOST: mysql
      PMA_PORT: 3306
      PMA_USER: anipm_user
      PMA_PASSWORD: your_secure_password
    ports:
      - "8080:80"
    depends_on:
      - mysql
    networks:
      - anipm-network

volumes:
  mysql_data:
  anipm_storage:
  anipm_logs:

networks:
  anipm-network:
    driver: bridge
```

Deploy with:
```bash
docker-compose up -d
```

### Method 3: Production with SSL (Advanced)
Create `docker-compose.prod.yml`:
```yaml
version: '3.8'

services:
  app:
    build: .
    container_name: anipm-website
    environment:
      - FRONTEND_ENV=WDS_SOCKET_PORT=443,REACT_APP_BACKEND_URL=https://yourdomain.com
    volumes:
      - anipm_storage:/var/www/html/storage
      - anipm_logs:/var/log/nginx
      - ./ssl:/etc/nginx/ssl
    restart: unless-stopped
    depends_on:
      - mysql
    networks:
      - anipm-network

  nginx-proxy:
    image: nginx:alpine
    container_name: anipm-proxy
    ports:
      - "80:80"
      - "443:443"
    volumes:
      - ./nginx/proxy.conf:/etc/nginx/conf.d/default.conf
      - ./ssl:/etc/nginx/ssl
      - /var/run/docker.sock:/tmp/docker.sock:ro
    depends_on:
      - app
    restart: unless-stopped
    networks:
      - anipm-network

  mysql:
    image: mysql:8.0
    container_name: anipm-mysql
    environment:
      MYSQL_ROOT_PASSWORD: your_secure_root_password
      MYSQL_DATABASE: anipm_db
      MYSQL_USER: anipm_user
      MYSQL_PASSWORD: your_secure_password
    volumes:
      - mysql_data:/var/lib/mysql
    restart: unless-stopped
    command: --default-authentication-plugin=mysql_native_password
    networks:
      - anipm-network

volumes:
  mysql_data:
  anipm_storage:
  anipm_logs:

networks:
  anipm-network:
    driver: bridge
```

## 🔧 Configuration Files

### Environment Variables
Create `.env.docker`:
```env
# Application Configuration
APP_NAME=ANIPM
APP_ENV=production
APP_DEBUG=false
APP_URL=https://yourdomain.com

# Database Configuration
DB_CONNECTION=mysql
DB_HOST=mysql
DB_PORT=3306
DB_DATABASE=anipm_db
DB_USERNAME=anipm_user
DB_PASSWORD=your_secure_password

# Laravel Configuration
LOG_CHANNEL=single
LOG_LEVEL=error
SESSION_DRIVER=file
CACHE_DRIVER=file
QUEUE_CONNECTION=sync

# Frontend Configuration
REACT_APP_BACKEND_URL=https://yourdomain.com
WDS_SOCKET_PORT=443
```

### Nginx Proxy Configuration
Create `nginx/proxy.conf`:
```nginx
upstream anipm-backend {
    server app:80;
}

server {
    listen 80;
    server_name yourdomain.com www.yourdomain.com;
    
    # Redirect HTTP to HTTPS
    return 301 https://$server_name$request_uri;
}

server {
    listen 443 ssl http2;
    server_name yourdomain.com www.yourdomain.com;
    
    # SSL Configuration
    ssl_certificate /etc/nginx/ssl/fullchain.pem;
    ssl_certificate_key /etc/nginx/ssl/privkey.pem;
    ssl_protocols TLSv1.2 TLSv1.3;
    ssl_ciphers ECDHE-RSA-AES128-GCM-SHA256:ECDHE-RSA-AES256-GCM-SHA384;
    ssl_prefer_server_ciphers off;
    
    # Security Headers
    add_header X-Frame-Options "SAMEORIGIN" always;
    add_header X-Content-Type-Options "nosniff" always;
    add_header X-XSS-Protection "1; mode=block" always;
    add_header Strict-Transport-Security "max-age=31536000; includeSubDomains" always;
    
    # Client upload size
    client_max_body_size 100M;
    
    # Proxy Configuration
    location / {
        proxy_pass http://anipm-backend;
        proxy_set_header Host $host;
        proxy_set_header X-Real-IP $remote_addr;
        proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;
        proxy_set_header X-Forwarded-Proto $scheme;
        proxy_buffering off;
        proxy_request_buffering off;
    }
    
    # Health check
    location /health {
        access_log off;
        return 200 "healthy\n";
        add_header Content-Type text/plain;
    }
}
```

## 🗃️ Database Initialization
Create `database/init.sql`:
```sql
-- Create database (if not exists)
CREATE DATABASE IF NOT EXISTS anipm_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

-- Use the database
USE anipm_db;

-- Create status_checks table
CREATE TABLE IF NOT EXISTS status_checks (
    id VARCHAR(255) PRIMARY KEY,
    client_name VARCHAR(255) NOT NULL,
    timestamp TIMESTAMP NOT NULL,
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Create indexes for better performance
CREATE INDEX IF NOT EXISTS idx_status_checks_timestamp ON status_checks(timestamp);
CREATE INDEX IF NOT EXISTS idx_status_checks_client ON status_checks(client_name);
CREATE INDEX IF NOT EXISTS idx_status_checks_created ON status_checks(created_at);

-- Insert sample data (optional)
INSERT IGNORE INTO status_checks (id, client_name, timestamp) VALUES 
('550e8400-e29b-41d4-a716-446655440000', 'system_check', NOW()),
('550e8400-e29b-41d4-a716-446655440001', 'health_check', NOW());
```

## 🔄 Container Management

### Building and Updating
```bash
# Build from scratch
docker build --no-cache -t anipm-website .

# Update existing container
docker-compose down
docker-compose build --no-cache
docker-compose up -d

# View logs
docker-compose logs -f app
docker-compose logs -f mysql
```

### Backup and Restore
```bash
# Database backup
docker exec anipm-mysql mysqldump -u anipm_user -p anipm_db > backup.sql

# Database restore
docker exec -i anipm-mysql mysql -u anipm_user -p anipm_db < backup.sql

# Volume backup
docker run --rm -v anipm_storage:/data -v $(pwd):/backup alpine tar czf /backup/storage-backup.tar.gz /data

# Volume restore
docker run --rm -v anipm_storage:/data -v $(pwd):/backup alpine tar xzf /backup/storage-backup.tar.gz -C /
```

### Scaling and Load Balancing
```bash
# Scale application containers
docker-compose up -d --scale app=3

# Check running containers
docker-compose ps

# Load balancer configuration (nginx upstream)
upstream anipm-backend {
    server app_1:80;
    server app_2:80;
    server app_3:80;
}
```

## 📊 Monitoring and Health Checks

### Container Health Checks
Add to `docker-compose.yml`:
```yaml
services:
  app:
    # ... other configuration
    healthcheck:
      test: ["CMD", "curl", "-f", "http://localhost/api/"]
      interval: 30s
      timeout: 10s
      retries: 3
      start_period: 60s
    
  mysql:
    # ... other configuration
    healthcheck:
      test: ["CMD", "mysqladmin", "ping", "-h", "localhost"]
      interval: 30s
      timeout: 10s
      retries: 3
      start_period: 60s
```

### Monitoring Commands
```bash
# Check container health
docker-compose ps

# View resource usage
docker stats

# Check logs
docker-compose logs -f --tail=100 app
docker-compose logs -f --tail=100 mysql

# Execute commands in container
docker-compose exec app bash
docker-compose exec mysql mysql -u anipm_user -p anipm_db
```

## 🔐 Security Best Practices

### Container Security
```bash
# Run containers as non-root user
RUN addgroup -g 1000 app && adduser -D -s /bin/sh -u 1000 -G app app
USER app
```

### Network Security
```yaml
# Restrict network access
networks:
  anipm-network:
    driver: bridge
    ipam:
      config:
        - subnet: 172.20.0.0/16
```

### Secret Management
```bash
# Use Docker secrets for sensitive data
echo "your_secure_password" | docker secret create mysql_password -
```

## 🚨 Troubleshooting

### Common Issues
1. **Container won't start**
   ```bash
   docker-compose logs app
   docker-compose exec app bash
   ```

2. **Database connection failed**
   ```bash
   docker-compose exec mysql mysql -u root -p
   docker-compose exec app php artisan config:clear
   ```

3. **Frontend not loading**
   ```bash
   # Check build logs
   docker-compose logs app | grep -i "frontend"
   
   # Rebuild frontend
   docker-compose exec app npm run build
   ```

4. **SSL certificate issues**
   ```bash
   # Check SSL files
   docker-compose exec nginx-proxy ls -la /etc/nginx/ssl/
   
   # Test SSL
   openssl s_client -connect yourdomain.com:443 -servername yourdomain.com
   ```

## 📋 Production Deployment Checklist

### Pre-deployment
- [ ] Domain name configured
- [ ] SSL certificates obtained
- [ ] Database passwords generated
- [ ] Environment variables configured
- [ ] Firewall rules configured
- [ ] Backup strategy planned

### Deployment
- [ ] Docker and Docker Compose installed
- [ ] Application files uploaded
- [ ] `docker-compose.yml` configured
- [ ] Environment files created
- [ ] SSL certificates in place
- [ ] Database initialized

### Post-deployment
- [ ] All services running
- [ ] Health checks passing
- [ ] SSL certificate valid
- [ ] Database accessible
- [ ] Application responding
- [ ] Monitoring configured
- [ ] Backups scheduled

## 🎯 Performance Optimization

### Docker Optimizations
```dockerfile
# Multi-stage build optimization
FROM node:20-alpine AS frontend-build
WORKDIR /app
COPY frontend/package*.json ./
RUN npm ci --only=production
COPY frontend/ .
RUN npm run build

FROM php:8.2-fpm-alpine AS backend-build
WORKDIR /var/www/html
COPY backend/composer.json backend/composer.lock ./
RUN composer install --no-dev --optimize-autoloader --no-scripts
COPY backend/ .
RUN composer dump-autoload --optimize

FROM nginx:alpine
# Copy optimized builds
COPY --from=frontend-build /app/build /usr/share/nginx/html
COPY --from=backend-build /var/www/html /var/www/html
```

### Resource Limits
```yaml
services:
  app:
    deploy:
      resources:
        limits:
          cpus: '2.0'
          memory: 1G
        reservations:
          cpus: '1.0'
          memory: 512M
```

This Docker deployment guide provides comprehensive instructions for deploying the ANIPM website using Docker containers with proper security, monitoring, and optimization configurations.