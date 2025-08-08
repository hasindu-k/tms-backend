# CORS Configuration Guide

## Overview

This document explains the CORS (Cross-Origin Resource Sharing) configuration for the TMS backend API.

## Environment Variables

Add these variables to your `.env` file:

```env
# CORS Configuration
APP_FRONTEND_URL=http://localhost:5173
APP_FRONTEND_PRODUCTION_URL=https://yourdomain.com
APP_FRONTEND_STAGING_URL=https://staging.yourdomain.com
```

## Configuration Details

### Allowed Origins

The CORS configuration allows requests from:

**Development:**

-   `http://localhost:3000`
-   `http://localhost:5173`
-   `http://127.0.0.1:3000`
-   `http://127.0.0.1:5173`

**Production:**

-   Set via `APP_FRONTEND_PRODUCTION_URL` environment variable
-   Default: `https://yourdomain.com` (replace with your actual domain)

**Staging:**

-   Set via `APP_FRONTEND_STAGING_URL` environment variable
-   Default: `https://staging.yourdomain.com` (replace with your actual domain)

### Allowed Methods

-   GET, POST, PUT, PATCH, DELETE, OPTIONS

### Allowed Headers

-   Content-Type
-   Authorization
-   X-Requested-With
-   Accept
-   Origin
-   X-CSRF-TOKEN

### Security Features

-   `supports_credentials: true` - Allows cookies and authentication headers
-   `max_age: 86400` - Caches preflight requests for 24 hours
-   Pattern matching for subdomains

## Production Setup

1. Update your `.env` file with your actual domain:

```env
APP_FRONTEND_PRODUCTION_URL=https://your-actual-domain.com
```

2. Update the pattern in `config/cors.php`:

```php
'allowed_origins_patterns' => [
    '/^https:\/\/.*\.your-actual-domain\.com$/',
],
```

## Security Best Practices

1. **Never use `*` for allowed origins in production**
2. **Always specify exact domains**
3. **Use HTTPS in production**
4. **Regularly review and update allowed origins**
5. **Monitor CORS errors in logs**

## Troubleshooting

If you encounter CORS errors:

1. Check that your frontend URL is in the `allowed_origins` array
2. Verify the environment variables are set correctly
3. Clear Laravel cache: `php artisan config:clear`
4. Check browser console for specific CORS error messages
