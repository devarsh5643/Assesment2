# Adelaide Artisan Bakery

A production-minded, mobile-first bakery catalogue and enquiry website for a local Adelaide bakery. The public site presents baked goods and accepts validated enquiries; a token-protected admin workspace provides product CRUD and enquiry review.

## Architecture

```text
server.js              Express app, validation, routes and server startup
db/database.js         SQLite connection and schema safety check
db/setup.js            Database initialization and sample catalogue seed
views/                 EJS server-rendered public and admin pages
public/styles.css      Responsive accessible visual system
data/bakery.db         Local SQLite database (created by setup, ignored by Git)
```

## Prerequisites

- Node.js 18 or newer
- npm

## Installation and running

```bash
npm install
copy .env.example .env
npm run setup
npm start
```

Open `http://localhost:3000`. For development, `npm run dev` uses Node's built-in watch mode.

## Environment configuration

Copy `.env.example` to `.env` and adjust values as needed:

- `PORT`: HTTP port, default `3000`.
- `DATABASE_URL`: SQLite file path, default `./data/bakery.db`.
- `ADMIN_TOKEN`: token required for `/admin` and admin API access. Change it before deploying.

Open the admin page with the token as a query parameter, for example `http://localhost:3000/admin?token=change-this-development-token`. The token is also sent through hidden form fields for the form-based admin actions.

## API and routes

- `GET /api/products`: public product JSON catalogue.
- `GET /api/admin/products`: all products, requires `x-admin-token` or `?token=`.
- `GET /admin?token=...`: product CRUD and enquiry review workspace.
- `POST /enquiries`: server-validated enquiry persistence.
- `POST /admin/products`: create a product.
- `POST /admin/products/:id/update`: update a product.
- `POST /admin/products/:id/delete`: delete a product.

## Accessibility and security notes

Pages use semantic landmarks, explicit labels, keyboard-visible focus styles, live validation/status regions, and a high-contrast palette. Server-side validation is performed with `express-validator`; prices are stored as integer cents to avoid floating-point persistence errors. The admin token is intentionally lightweight for a local assessment application and should be replaced with session-based authentication, CSRF protection, rate limiting, and secret management for a public production deployment.

## Known limitations

- Product images use remote Unsplash URLs and require network access; replace them with owned or locally hosted assets for a production launch.
- The admin authentication is a shared token rather than user accounts and roles.
- There is no email notification provider; enquiries are persisted in SQLite for staff review.
- SQLite is appropriate for this zero-config local deployment, but a managed database is recommended for high traffic or multiple application instances.

