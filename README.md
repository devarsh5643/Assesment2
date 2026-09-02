# Adelaide Artisan Bakery — Assessment 2

A responsive, dynamic and data-driven bakery website built with native PHP and SQLite.

## Assessment requirements covered

- Product CRUD: administrators can create, read, update and delete products.
- Public enquiry form with server-side validation, clear errors and SQLite storage.
- Shopping cart and checkout with accurate prices stored and calculated in cents.
- Responsive layouts for mobile, tablet and desktop.
- Semantic HTML, associated form labels, keyboard focus indicators and accessible status messages.
- Parameterised database queries, output escaping and CSRF protection for data-changing forms.

## Requirements

- PHP 7.4 or newer
- PDO SQLite extension enabled

Check the installation:

```bash
php --version
php -m
```

The PHP module list should contain `PDO` and `pdo_sqlite`.

## Run locally

### Windows — easiest method

1. Delete any older extracted copy of the project first. Do not merge this download into the old folder.
2. Download the repository again and extract it to a new folder.
3. Double-click `START_WEBSITE.bat`.
4. The website will open at `http://localhost:8000`.

> **Important:** This is a PHP website. Do not use `npm start`, `node server.js` or an old `START_SERVER` file. If an error mentions Express, EJS, `server.js`, `node_modules` or “Failed to lookup view”, you are running the deleted Node.js version from an old folder.

The starter uses PHP from the system PATH or from a standard XAMPP installation and checks that PDO SQLite is available before opening the site.

### macOS or Linux

From the project folder, run:

```bash
php -S 127.0.0.1:8000 router.php
```

Open these addresses:

- Public website: `http://localhost:8000`
- Menu: `http://localhost:8000/index.php?page=menu`
- Checkout: `http://localhost:8000/index.php?page=checkout`
- Admin: `http://localhost:8000/index.php?page=admin&token=change-this-development-token`

The SQLite database and tables are created automatically on the first request. Eighteen sample products are added when the product table is empty.

### Windows with XAMPP

1. Copy the project folder into the XAMPP `htdocs` folder.
2. Start Apache from the XAMPP Control Panel.
3. Open `http://localhost/Assesment2/`.

Alternatively, double-click `START_WEBSITE.bat`; it can use `C:\xampp\php\php.exe` directly without starting Apache.

## Admin token

For this local assessment project, the default token is:

```text
change-this-development-token
```

To use a different token without editing the code, set the `ADMIN_TOKEN` environment variable before starting PHP.

## Project structure

```text
Assesment2/
├── .gitignore
├── .htaccess
├── README.md
├── START_WEBSITE.bat      # Windows launcher for the PHP website
├── index.php              # Router, validation and application actions
├── router.php             # Router for PHP's built-in server
├── data/
│   └── .gitkeep           # Runtime database directory
├── db/
│   └── config.php         # SQLite schema, seed data and queries
├── pages/
│   ├── admin.php          # Product CRUD and enquiry list
│   ├── checkout.php       # Cart totals and order form
│   ├── error.php          # Error response page
│   ├── home.php           # Homepage and public enquiry form
│   └── menu.php           # Complete filterable catalogue
└── public/
    └── styles.css         # Responsive and accessible presentation
```

## Functional test checklist

1. Open the homepage and confirm five featured products appear.
2. Open the menu, filter the categories and add a product to the cart.
3. Confirm the cart subtotal and total match the displayed item price.
4. Submit invalid checkout details and confirm clear error messages appear.
5. Submit valid checkout details and confirm the order appears in the admin enquiry list.
6. Submit the public enquiry form and confirm it appears in the admin enquiry list.
7. In the admin page, add a product, edit it and then delete it.
8. Resize the browser to confirm the pages work on mobile, tablet and desktop widths.

## Database notes

The runtime database is `data/bakery.db`. It is intentionally excluded from Git because it is generated automatically. SQLite journal files are also excluded. This keeps the repository clean and gives every assessor a fresh database with predictable sample data.

## Known local-development limitation

The admin token is suitable for demonstrating protected assessment functionality locally. A public production deployment would require user accounts, stronger authentication, HTTPS and environment-managed secrets.
