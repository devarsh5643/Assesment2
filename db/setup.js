const fs = require('node:fs');
const path = require('node:path');
const Database = require('better-sqlite3');
require('dotenv').config();

const databasePath = process.env.DATABASE_URL || './data/bakery.db';
const absolutePath = path.resolve(databasePath);
fs.mkdirSync(path.dirname(absolutePath), { recursive: true });

const db = new Database(absolutePath);
db.pragma('journal_mode = WAL');
// Setup is idempotent and safe to rerun after a fresh clone.
db.exec(`
  CREATE TABLE IF NOT EXISTS products (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    name TEXT NOT NULL,
    description TEXT NOT NULL,
    price_cents INTEGER NOT NULL CHECK (price_cents >= 0),
    category TEXT NOT NULL,
    image_url TEXT NOT NULL,
    created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP
  );
  CREATE TABLE IF NOT EXISTS enquiries (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    customer_name TEXT NOT NULL,
    email TEXT NOT NULL,
    phone TEXT NOT NULL,
    enquiry_type TEXT NOT NULL,
    message TEXT NOT NULL,
    created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP
  );
`);

const count = db.prepare('SELECT COUNT(*) AS count FROM products').get().count;
if (count === 0) {
  const insert = db.prepare(`INSERT INTO products (name, description, price_cents, category, image_url)
    VALUES (@name, @description, @priceCents, @category, @imageUrl)`);
  const seed = db.transaction(() => {
    [
      { name: 'Sourdough Country Loaf', description: 'Slow-fermented with a crisp crust and a tender, open crumb.', priceCents: 850, category: 'Bread', imageUrl: 'https://images.unsplash.com/photo-1509440159596-0249088772ff?auto=format&fit=crop&w=900&q=80' },
      { name: 'Almond Croissant', description: 'Buttery laminated pastry filled with almond frangipane and toasted almonds.', priceCents: 680, category: 'Pastry', imageUrl: 'https://images.unsplash.com/photo-1555507036-ab1f4038808a?auto=format&fit=crop&w=900&q=80' },
      { name: 'Seasonal Fruit Tart', description: 'Vanilla bean custard, crisp pastry and the best fruit from the market.', priceCents: 720, category: 'Sweet', imageUrl: 'https://images.unsplash.com/photo-1519915028121-7d3463d20b13?auto=format&fit=crop&w=900&q=80' },
      { name: 'Rosemary Focaccia', description: 'Olive oil-rich focaccia finished with rosemary, sea salt and garlic.', priceCents: 950, category: 'Bread', imageUrl: 'https://images.unsplash.com/photo-1598373182133-52452f7691ef?auto=format&fit=crop&w=900&q=80' }
    ].forEach((product) => insert.run(product));
  });
  seed();
}

db.close();
console.log(`Database ready at ${absolutePath}`);
