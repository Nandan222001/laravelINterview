# Laravel 11 Application - Installation Guide

Complete step-by-step installation guide for the Laravel 11 API application.

## Prerequisites

Before you begin, ensure you have the following installed:

- **PHP**: Version 8.2 or higher
- **Composer**: Latest version
- **MySQL**: Version 8.0 or higher (or MariaDB 10.3+)
- **Git**: For version control
- **Web Server**: Apache/Nginx (optional for development)

### Checking Prerequisites

```bash
# Check PHP version
php -v

# Check Composer
composer --version

# Check MySQL
mysql --version

# Check required PHP extensions
php -m | grep -E "pdo|mbstring|openssl|tokenizer|xml|ctype|json|bcmath"
```

### Required PHP Extensions

- PDO
- Mbstring
- OpenSSL
- Tokenizer
- XML
- Ctype
- JSON
- BCMath
- Fileinfo

## Installation Steps

### 1. Clone or Download the Repository

```bash
git clone <repository-url>
cd <project-directory>
```

### 2. Install PHP Dependencies

```bash
composer install
```

This will install all required Laravel and PHP packages defined in `composer.json`.

### 3. Environment Configuration

Copy the example environment file:

```bash
cp .env.example .env
```

### 4. Generate Application Key

```bash
php artisan key:generate
```

This generates a unique application key required for encryption.

### 5. Configure Database

Edit the `.env` file and update the database credentials:

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=your_database_name
DB_USERNAME=your_database_user
DB_PASSWORD=your_database_password
```

#### Create Database

```bash
# Login to MySQL
mysql -u root -p

# Create database
CREATE DATABASE your_database_name CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

# Create user (optional)
CREATE USER 'your_database_user'@'localhost' IDENTIFIED BY 'your_database_password';

# Grant privileges
GRANT ALL PRIVILEGES ON your_database_name.* TO 'your_database_user'@'localhost';

# Flush privileges
FLUSH PRIVILEGES;

# Exit
EXIT;
```

### 6. Run Database Migrations

```bash
php artisan migrate
```

This creates all necessary database tables.

### 7. Seed Database (Optional)

```bash
php artisan db:seed
```

This creates a test user:
- Email: `test@example.com`
- Password: `password`

### 8. Configure Storage Permissions

```bash
# Linux/Mac
chmod -R 775 storage bootstrap/cache
chown -R www-data:www-data storage bootstrap/cache

# Or if running as current user
chmod -R 775 storage bootstrap/cache
```

```powershell
# Windows (PowerShell as Administrator)
icacls storage /grant Users:F /T
icacls bootstrap\cache /grant Users:F /T
```

### 9. Create Storage Link (If needed for file uploads)

```bash
php artisan storage:link
```

### 10. Configure Queue and Cache (Optional)

For production, configure Redis or another cache/queue driver:

```env
CACHE_DRIVER=redis
QUEUE_CONNECTION=redis
SESSION_DRIVER=redis

REDIS_HOST=127.0.0.1
REDIS_PASSWORD=null
REDIS_PORT=6379
```

### 11. Start Development Server

```bash
php artisan serve
```

The application will be available at `http://localhost:8000`

## Post-Installation Configuration

### Configure CORS (If needed)

Edit `config/cors.php` to configure allowed origins:

```php
'allowed_origins' => [
    'http://localhost:3000',
    'https://yourdomain.com',
],

'supports_credentials' => true,
```

### Configure Sanctum Stateful Domains

Edit `.env` to add your frontend domain:

```env
SANCTUM_STATEFUL_DOMAINS=localhost,localhost:3000,127.0.0.1,127.0.0.1:8000
```

### Configure Mail Settings

For email verification and password resets:

```env
MAIL_MAILER=smtp
MAIL_HOST=smtp.gmail.com
MAIL_PORT=587
MAIL_USERNAME=your-email@gmail.com
MAIL_PASSWORD=your-app-password
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=your-email@gmail.com
MAIL_FROM_NAME="${APP_NAME}"
```

## Verification

### Test the Installation

1. **Access the Application**
   ```bash
   curl http://localhost:8000
   ```
   
   Should return Laravel version information.

2. **Test Database Connection**
   ```bash
   php artisan migrate:status
   ```

3. **Test Authentication**
   ```bash
   # Register a user
   curl -X POST http://localhost:8000/register \
     -H "Content-Type: application/json" \
     -d '{"name":"Test","email":"test2@example.com","password":"password","password_confirmation":"password"}'
   ```

### Run Tests

```bash
php artisan test
```

## Production Deployment

### 1. Optimize Application

```bash
# Optimize configuration
php artisan config:cache

# Optimize routes
php artisan route:cache

# Optimize views
php artisan view:cache

# Optimize event listeners
php artisan event:cache
```

### 2. Update Environment

```env
APP_ENV=production
APP_DEBUG=false
```

### 3. Set Proper Permissions

```bash
# Linux/Mac
chmod -R 755 storage bootstrap/cache
chown -R www-data:www-data storage bootstrap/cache
```

### 4. Configure Web Server

#### Apache

Create `.htaccess` in public directory (usually included):

```apache
<IfModule mod_rewrite.c>
    <IfModule mod_negotiation.c>
        Options -MultiViews -Indexes
    </IfModule>

    RewriteEngine On

    # Handle Authorization Header
    RewriteCond %{HTTP:Authorization} .
    RewriteRule .* - [E=HTTP_AUTHORIZATION:%{HTTP:Authorization}]

    # Redirect Trailing Slashes If Not A Folder...
    RewriteCond %{REQUEST_FILENAME} !-d
    RewriteCond %{REQUEST_URI} (.+)/$
    RewriteRule ^ %1 [L,R=301]

    # Send Requests To Front Controller...
    RewriteCond %{REQUEST_FILENAME} !-d
    RewriteCond %{REQUEST_FILENAME} !-f
    RewriteRule ^ index.php [L]
</IfModule>
```

#### Nginx

```nginx
server {
    listen 80;
    server_name yourdomain.com;
    root /path/to/your/project/public;

    add_header X-Frame-Options "SAMEORIGIN";
    add_header X-Content-Type-Options "nosniff";

    index index.php;

    charset utf-8;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location = /favicon.ico { access_log off; log_not_found off; }
    location = /robots.txt  { access_log off; log_not_found off; }

    error_page 404 /index.php;

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.2-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
    }

    location ~ /\.(?!well-known).* {
        deny all;
    }
}
```

### 5. Set Up Queue Worker

```bash
# Install supervisor
sudo apt-get install supervisor

# Create supervisor config
sudo nano /etc/supervisor/conf.d/laravel-worker.conf
```

```ini
[program:laravel-worker]
process_name=%(program_name)s_%(process_num)02d
command=php /path/to/your/project/artisan queue:work --sleep=3 --tries=3 --max-time=3600
autostart=true
autorestart=true
stopasgroup=true
killasgroup=true
user=www-data
numprocs=8
redirect_stderr=true
stdout_logfile=/path/to/your/project/storage/logs/worker.log
stopwaitsecs=3600
```

```bash
# Update supervisor
sudo supervisorctl reread
sudo supervisorctl update
sudo supervisorctl start laravel-worker:*
```

### 6. Set Up Scheduler

Add to crontab:

```bash
crontab -e
```

Add this line:

```
* * * * * cd /path/to/your/project && php artisan schedule:run >> /dev/null 2>&1
```

## Troubleshooting

### Database Connection Issues

```bash
# Test database connection
php artisan tinker
>>> DB::connection()->getPdo();
```

### Permission Issues

```bash
# Reset permissions
sudo chown -R $USER:www-data .
sudo find . -type f -exec chmod 664 {} \;
sudo find . -type d -exec chmod 775 {} \;
sudo chgrp -R www-data storage bootstrap/cache
sudo chmod -R ug+rwx storage bootstrap/cache
```

### Clear All Caches

```bash
php artisan config:clear
php artisan cache:clear
php artisan route:clear
php artisan view:clear
php artisan event:clear
composer dump-autoload
```

### Debug Mode

Enable debug mode temporarily in `.env`:

```env
APP_DEBUG=true
LOG_LEVEL=debug
```

Check logs:

```bash
tail -f storage/logs/laravel.log
```

## Common Issues

### Issue: "No application encryption key has been specified"

**Solution:**
```bash
php artisan key:generate
```

### Issue: "Access denied for user"

**Solution:** Check database credentials in `.env`

### Issue: "Class not found"

**Solution:**
```bash
composer dump-autoload
```

### Issue: "The stream or file could not be opened"

**Solution:** Fix storage permissions
```bash
chmod -R 775 storage
```

## Additional Resources

- [Laravel Documentation](https://laravel.com/docs/11.x)
- [Laravel Sanctum](https://laravel.com/docs/11.x/sanctum)
- [Laravel Deployment](https://laravel.com/docs/11.x/deployment)

## Support

For issues or questions:
1. Check the Laravel documentation
2. Review the logs in `storage/logs/laravel.log`
3. Enable debug mode and check error details
