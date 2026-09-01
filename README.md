# Adelaide Artisan Bakery - PHP Edition

A production-ready, mobile-first bakery catalogue and enquiry website for a local Adelaide bakery. Built with native PHP and SQLite, featuring a public site showcasing baked goods, a shopping cart system, and a token-protected admin panel for product and enquiry management.

## Architecture

```text
index.php              Central router and application controller
db/config.php          PDO-based SQLite database abstraction layer
pages/                 PHP template files (home, menu, checkout, admin, error)
public/styles.php      CSS delivery layer with responsive design
public/images/         Product images (placeholder references)
data/bakery.db         Local SQLite database (auto-created on first load)
.htaccess              Apache URL rewriting for clean URLs
```

## Prerequisites

- **PHP 7.4 or newer** with PDO SQLite support
- **Apache with mod_rewrite enabled** (or Nginx with PHP-FPM)
- **SQLite3 support** in PHP (usually included by default)

## Quick Start

### Option 1: Local Testing with Built-in PHP Server

```bash
# Navigate to project directory
cd path/to/assessment2-1

# Start PHP built-in server (no Apache needed)
php -S localhost:8000
```

Then open `http://localhost:8000` in your browser.

### Option 2: Apache Installation

1. **Install Apache + PHP** (recommended: use XAMPP, WAMP, or LEMP stack)
2. **Enable mod_rewrite**: Uncomment `LoadModule rewrite_module` in Apache config
3. **Copy project files** to Apache web root (typically `htdocs/`)
4. **Restart Apache** and visit `http://localhost/assessment2-1`

### Option 3: Hosting Provider

Upload all files to a PHP-enabled web host. Most providers support:
- PHP 7.4+
- SQLite (check if enabled; request enable if not)
- Apache with mod_rewrite

## Configuration

**Admin Token**: Edit `index.php` line 22:
```php
const ADMIN_TOKEN = 'change-this-development-token';
```

Change to a secure token before deployment.

**Database Location**: SQLite database automatically created at `/data/bakery.db`. Ensure the `/data` directory exists and is writable.

**Session Storage**: PHP uses filesystem-based sessions. Ensure `/tmp` is writable or configure `session.save_path` in `php.ini`.

## Features

- **Homepage**: Hero section with 5 featured products
- **Menu Page**: All 18 bakery items with category filters (All, Bread, Pastry, Sweet, Savoury)
- **Shopping Cart**: Add/remove items, persistent across session
- **Checkout**: Order form with delivery details
- **Special Offers**: Admin can mark products with discounted prices
- **Admin Panel**: Token-protected access to:
  - Add/edit/delete products
  - Activate/configure special offers
  - View and manage customer enquiries

## Access Points

- **Homepage**: `http://localhost:8000/`
- **Menu**: `http://localhost:8000/menu`
- **Checkout**: `http://localhost:8000/checkout`
- **Admin Panel**: `http://localhost:8000/admin?token=change-this-development-token`

## Database Structure

**Products Table** (18 items pre-seeded):
- id, name, description, category, price (in cents), imageUrl, is_featured, is_on_offer, offer_price

**Enquiries Table**:
- id, name, email, phone, address, items, total, message, created_at

## Troubleshooting

### "PHP not found" error
Install PHP from [php.net](https://www.php.net/downloads.php) or use XAMPP/WAMP.

### ".htaccess not working" / "Page not found"
- Ensure Apache has mod_rewrite enabled
- Check `.htaccess` file exists in project root
- Access without URL rewriting: `http://localhost:8000/index.php?page=menu`

### "Database file not writable"
- Ensure `/data` directory exists
- Set folder permissions: `chmod 755 data/` (Linux/Mac) or right-click Properties (Windows)

### "Call to undefined function" errors
Verify PHP version ≥ 7.4 and PDO SQLite is enabled:
```bash
php -m | grep -i pdo
php -m | grep -i sqlite
```

### Session not persisting
Check PHP's session configuration in `php.ini` - ensure `session.save_path` points to a writable directory.

## Accessibility and security notes

Pages use semantic landmarks, explicit labels, keyboard-visible focus styles, live validation/status regions, and a high-contrast palette. Server-side validation is performed with `express-validator`; prices are stored as integer cents to avoid floating-point persistence errors. The admin token is intentionally lightweight for a local assessment application and should be replaced with session-based authentication, CSRF protection, rate limiting, and secret management for a public production deployment.

## Known limitations

- Product images use remote Unsplash URLs and require network access; replace them with owned or locally hosted assets for a production launch.
- The admin authentication is a shared token rather than user accounts and roles.
- There is no email notification provider; enquiries are persisted in SQLite for staff review.
- SQLite is appropriate for this zero-config local deployment, but a managed database is recommended for high traffic or multiple application instances.

## Public site: http://localhost:3000
## Admin panel: http://localhost:3000/admin?token=change-this-development-token