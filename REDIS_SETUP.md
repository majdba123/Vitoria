# Redis Configuration for MSZ Project

## Quick Setup

1. **Install Predis** (already done):
   ```bash
   composer require predis/predis
   ```

2. **Update your `.env` file** with these variables:

```env
# Cache Configuration
CACHE_STORE=redis

# Redis Client (using Predis - pure PHP, no extension needed)
REDIS_CLIENT=predis

# Redis Configuration
REDIS_HOST=127.0.0.1
REDIS_PORT=6379
REDIS_PASSWORD=null
REDIS_DB=0

# Redis Cache Database (separate from default to avoid conflicts with other projects)
REDIS_CACHE_DB=2

# Optional: Redis URL (if using a different connection)
# REDIS_URL=redis://127.0.0.1:6379
```

## Important Notes

1. **Database Separation**: This project uses `REDIS_CACHE_DB=2` to avoid conflicts with other projects
   - Default Redis DB: `0` (for general use)
   - Cache Redis DB: `2` (for this project's cache)

2. **If you need a different database number**, change `REDIS_CACHE_DB` to any number from 0-15

3. **After updating .env**, clear config cache:
   ```bash
   php artisan config:clear
   php artisan cache:clear
   ```

## Testing Redis Connection

Run this command to test:
```bash
php artisan tinker
```

Then in tinker:
```php
Cache::store('redis')->put('test', 'works', 60);
Cache::store('redis')->get('test');
```

If it returns 'works', Redis is configured correctly!

