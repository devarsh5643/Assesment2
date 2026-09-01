# Setup Guide - Adelaide Artisan Bakery (PHP Edition)

## What Changed?

This project has been converted from **Node.js/Express** to **Native PHP**. This means:

- ✅ No Node.js required
- ✅ No npm dependencies 
- ✅ Simpler deployment
- ✅ Standard PHP hosting support
- ✅ Same features and functionality preserved

## Prerequisites

You need **PHP 7.4 or newer** with SQLite support.

### Check if PHP is installed:

**Windows (PowerShell or Command Prompt):**
```powershell
php --version
```

**Mac/Linux:**
```bash
which php
php --version
```

### If PHP is NOT installed:

**Windows:**
1. Download XAMPP from [apachefriends.org](https://www.apachefriends.org/)
2. Run the installer and select PHP
3. PHP will be automatically added to your PATH
4. Restart Command Prompt and verify: `php --version`

**Mac:**
```bash
# Using Homebrew (simplest)
brew install php
```

**Linux (Ubuntu/Debian):**
```bash
sudo apt-get install php php-cli php-sqlite3
```

## Running Locally

### Quick Start (Windows):

1. Double-click `START_SERVER.bat` in the project folder
2. Wait for the message: `Listening on http://localhost:8000`
3. Open your browser to `http://localhost:8000`

### Manual Start (All Platforms):

Open terminal/command prompt in the project folder:

```bash
php -S localhost:8000
```

Then visit: `http://localhost:8000`

### Stopping the Server:

Press `Ctrl+C` in the terminal

## Project Structure

```
├── index.php              # Main router (entry point)
├── .htaccess              # URL rewriting rules for Apache
├── db/
│   └── config.php         # Database connection & queries
├── pages/
│   ├── home.php           # Homepage (5 featured products)
│   ├── menu.php           # Full menu with filters
│   ├── checkout.php       # Shopping cart & order form
│   ├── admin.php          # Admin panel (token protected)
│   └── error.php          # Error page
├── public/
│   ├── styles.php         # CSS delivered as PHP
│   └── images/            # Product images (add here)
└── data/
    └── bakery.db          # SQLite database (auto-created)
```

## Features Overview

### Public Site
- **Homepage** (`/`): Hero banner + 5 featured products + enquiry form
- **Menu** (`/menu`): All 18 items with category filters (Bread, Pastry, Sweet, Savoury)
- **Shopping Cart** (persistent): Add/remove items during session
- **Checkout** (`/checkout`): Review cart + order form

### Admin Panel
- **Access URL**: `http://localhost:8000/admin?token=change-this-development-token`
- **Features**:
  - Add new products
  - Edit product details
  - Delete products
  - Set special offer prices
  - View all customer enquiries

### Contact Information
- **Email**: pateldevarsh1010@gmail.com
- **Phone**: 0493875729
- (Displayed in website footer)

## Configuration

### Change Admin Token (IMPORTANT!)

Edit `index.php` line 15:

```php
$adminToken = 'change-this-development-token';
```

Change to a strong, unique token:

```php
$adminToken = 'my-secret-bakery-token-12345';
```

Then access admin at: `http://localhost:8000/admin?token=my-secret-bakery-token-12345`

### Database

- **Location**: `/data/bakery.db` (SQLite)
- **Auto-created**: First time you load the app
- **Pre-seeded**: 18 bakery items included
- **Permissions**: Ensure `/data` folder is writable

## Troubleshooting

### "PHP is not installed"
→ See **Prerequisites** section above

### "localhost:8000 refused to connect"
→ Did you start the server with `php -S localhost:8000`?
→ Check terminal shows: `Listening on http://localhost:8000`

### Menu shows only some items
→ Database not created yet. Reload page - it auto-creates on first access

### Admin page shows "Unauthorized"
→ Check your admin token in URL matches the one in `index.php`

### Images not loading
→ Product images need to be in `/public/images/` folder
→ Update image URLs in admin panel

### Shopping cart empties when browser closes
→ This is expected! Sessions are temporary. Persistent carts require login system.

### Special offers aren't showing
→ Check you saved the offer in admin panel
→ Verify "Activate offer" checkbox is checked
→ Offer price must be set and less than regular price

### Permission denied errors
→ Windows: Run Command Prompt as Administrator
→ Linux/Mac: `chmod 755 data/` to make folder writable

## Deployment to Web Host

1. **Check host supports PHP 7.4+** (most do)
2. **Verify SQLite/PDO support** (request if not enabled)
3. **Upload all files** to web host (use FTP/SFTP)
4. **Ensure `/data` folder exists** and is writable
5. **Change admin token** in `index.php` before going live
6. **Test all features** on live server

**Common hosts known to work:**
- Bluehost
- SiteGround  
- DreamHost
- Any shared hosting with cPanel

## Technical Details

### Session Management
- PHP native `$_SESSION` (no login required)
- Stored on server filesystem
- Timeout: PHP default (usually 24 hours)

### Database
- SQLite 3 (file-based, no server needed)
- PDO abstraction layer
- 18 pre-seeded products
- Supports unlimited enquiries

### URL Rewriting
- `.htaccess` file enables clean URLs
- `/menu` routes to `index.php?page=menu`
- Requires Apache with mod_rewrite enabled
- Falls back to `?page=` syntax on other servers

## Next Steps

1. ✅ Install PHP (if not already installed)
2. ✅ Run `START_SERVER.bat` (Windows) or `php -S localhost:8000` (all platforms)
3. ✅ Visit `http://localhost:8000`
4. ✅ Test features (menu, add to cart, checkout, admin panel)
5. ✅ Change admin token to something secure
6. ✅ Deploy to web host when ready

## Support

For questions about PHP, visit: [php.net/docs](https://www.php.net/docs.php)

For questions about this project, check the code comments in:
- `index.php` - main logic
- `db/config.php` - database operations
- `pages/*.php` - page templates
