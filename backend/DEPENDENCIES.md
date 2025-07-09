# Backend Dependencies List (PHP Laravel)

## Overview
This backend is built using Laravel 10.x with PHP 8.1+ and uses MongoDB as the database.

## Main Dependencies (Production)

### Core Framework
- **laravel/framework** ^10.10 - The main Laravel framework
- **php** ^8.1 - Minimum PHP version requirement

### HTTP Client & Web Scraping
- **guzzlehttp/guzzle** ^7.9 - HTTP client for making requests to external URLs
- **symfony/dom-crawler** ^6.4 - HTML/XML parser for extracting og:image tags from web pages

### Database
- **mongodb/laravel-mongodb** ^4.0 - MongoDB integration for Laravel (replaces traditional SQL database)

### Authentication & Security
- **laravel/sanctum** ^3.3 - Laravel Sanctum for API authentication

### Development Tools
- **laravel/tinker** ^2.8 - Interactive shell for Laravel

## Development Dependencies (require-dev)

### Testing
- **phpunit/phpunit** ^10.1 - PHP testing framework
- **mockery/mockery** ^1.4.4 - Mocking framework for testing

### Code Quality
- **laravel/pint** ^1.0 - Laravel Pint for code style fixing
- **nunomaduro/collision** ^7.0 - Better error handling for CLI
- **spatie/laravel-ignition** ^2.0 - Error page for Laravel

### Development Environment
- **laravel/sail** ^1.18 - Laravel Sail for Docker development
- **fakerphp/faker** ^1.9.1 - Fake data generator for testing

### Frontend Build Tools (Laravel Mix/Vite)
- **laravel-vite-plugin** ^1.0.0 - Laravel Vite integration
- **vite** ^5.0.0 - Build tool for frontend assets
- **axios** ^1.6.4 - HTTP client for frontend

## Installation Instructions

### 1. Install PHP Dependencies
```bash
composer install
```

### 2. Install Node.js Dependencies (for frontend build tools)
```bash
npm install
# or
yarn install
```

### 3. Generate Application Key
```bash
php artisan key:generate
```

### 4. Set up Environment
Copy the `.env` file and configure your database settings.

### 5. Run Migrations (if using traditional database)
```bash
php artisan migrate
```

### 6. Start the Laravel Server
```bash
php artisan serve --host=0.0.0.0 --port=8001
```

## API Endpoints Available

### Core Endpoints
- `GET /api/` - Main API information page
- `GET /api/demo` - Interactive demo page
- `GET /api/preview` - Preview redirect page
- `GET /api/links` - All preview links

### News Image Extraction
- `POST /api/fetch-news-image` - Extract image from single news URL
- `POST /api/fetch-multiple-news-images` - Extract images from multiple URLs
- `GET /api/test-image-extraction` - Test page for image extraction

### Document Downloads
- `GET /api/download/industria-bauturilor.pdf`
- `GET /api/download/oferta-lactate-ro.pdf`
- `GET /api/download/oferta-carne-si-oua-ro.pdf`
- `GET /api/download/minist1.pdf`
- `GET /api/download/minist2.pdf`
- `GET /api/download/minist3.docx`
- `GET /api/download/minist4.pdf`

### Status Checking
- `POST /api/status` - Create status check
- `GET /api/status` - Get all status checks
- `GET /api/status/{id}` - Get specific status check

### News Ticker
- `GET /api/news-ticker` - Get news ticker data

## Key Features

### 1. News Image Extraction
- Uses Guzzle HTTP client to fetch web pages
- Parses HTML using Symfony DomCrawler
- Extracts og:image meta tags
- Handles relative/absolute URL conversion
- Extracts page titles and descriptions

### 2. Document Management
- Serves PDF and DOCX files
- Handles file downloads with proper headers
- Manages document paths and filename mappings

### 3. MongoDB Integration
- Uses Laravel MongoDB package
- Stores status checks and other data
- Provides Eloquent-like syntax for MongoDB operations

### 4. CORS Support
- Configured for frontend integration
- Allows cross-origin requests

## Configuration Files

### Important Configuration
- `config/app.php` - Main application configuration
- `config/cors.php` - CORS configuration
- `config/database.php` - Database configuration
- `routes/api.php` - API routes definition
- `app/Http/Controllers/Api/ApiController.php` - Main API controller

### Environment Variables
- `APP_URL` - Application URL
- `MONGO_URL` - MongoDB connection string
- `PREVIEW_ENDPOINT` - Frontend preview URL
- `DOCUMENTS_PATH` - Path to document files

## System Requirements

### PHP Requirements
- PHP 8.1 or higher
- Extensions: mbstring, openssl, PDO, tokenizer, XML, ctype, JSON, BCMath, fileinfo

### Server Requirements
- Web server (Apache/Nginx)
- MongoDB server
- Node.js (for frontend build tools)

## Security Notes
- All API endpoints use Laravel's built-in security features
- Input validation on all endpoints
- File downloads are restricted to specific paths
- Error handling prevents information disclosure

## Performance Considerations
- Guzzle HTTP client with timeout settings
- Efficient MongoDB queries
- Proper error handling and logging
- Caching can be implemented for frequently accessed data