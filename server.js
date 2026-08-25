const path = require('node:path');
const express = require('express');
const { body, validationResult } = require('express-validator');
require('dotenv').config();
const db = require('./db/database');

const app = express();
const port = Number(process.env.PORT) || 3000;
const adminToken = process.env.ADMIN_TOKEN || 'change-this-development-token';
const categories = ['Bread', 'Pastry', 'Sweet', 'Savoury'];

app.set('view engine', 'ejs');
app.set('views', path.join(__dirname, 'views'));
app.use(express.urlencoded({ extended: true }));
app.use(express.json());
app.use(express.static(path.join(__dirname, 'public')));
app.use((req, res, next) => { res.locals.path = req.path; next(); });

// Browser forms and API submissions share the same validation contract.
function productInputRules() {
  return [
    body('name').trim().isLength({ min: 2, max: 100 }).withMessage('Name must be between 2 and 100 characters.'),
    body('description').trim().isLength({ min: 10, max: 500 }).withMessage('Description must be between 10 and 500 characters.'),
    body('price').trim().isFloat({ min: 0, max: 10000 }).withMessage('Price must be a valid positive amount.'),
    body('category').trim().isIn(categories).withMessage('Choose a valid category.'),
    body('imageUrl').trim().isURL({ protocols: ['http', 'https'] }).withMessage('Image URL must be a valid HTTP or HTTPS URL.')
  ];
}

const enquiryRules = [
  body('customerName').trim().isLength({ min: 2, max: 100 }).withMessage('Please enter your name.'),
  body('email').trim().isEmail().withMessage('Please enter a valid email address.'),
  body('phone').trim().isLength({ min: 6, max: 30 }).withMessage('Please enter a valid phone number.'),
  body('enquiryType').trim().isIn(['Custom order', 'Catering', 'Wholesale', 'General']).withMessage('Please choose an enquiry type.'),
  body('message').trim().isLength({ min: 10, max: 2000 }).withMessage('Message must be between 10 and 2,000 characters.')
];

function formatProduct(product) { return { ...product, price: (product.price_cents / 100).toFixed(2) }; }
function renderErrors(errors) { return errors.array().map((error) => error.msg); }
function requireAdmin(req, res, next) {
  if (req.get('x-admin-token') === adminToken || req.body.adminToken === adminToken || req.query.token === adminToken) return next();
  return res.status(401).render('error', { title: 'Admin access required', message: 'Use the configured admin token to access this page.' });
}

// Server rendering keeps the public catalogue useful without client-side JavaScript.
app.get('/', (req, res) => {
  const products = db.prepare('SELECT * FROM products ORDER BY category, name').all().map(formatProduct);
  res.render('home', { title: 'Adelaide Artisan Bakery', products, sent: req.query.sent === '1' });
});

app.post('/enquiries', enquiryRules, (req, res) => {
  const errors = validationResult(req);
  if (!errors.isEmpty()) {
    const products = db.prepare('SELECT * FROM products ORDER BY category, name').all().map(formatProduct);
    return res.status(422).render('home', { title: 'Adelaide Artisan Bakery', products, sent: false, enquiryErrors: renderErrors(errors), formData: req.body });
  }
  db.prepare(`INSERT INTO enquiries (customer_name, email, phone, enquiry_type, message) VALUES (?, ?, ?, ?, ?)`)
    .run(req.body.customerName.trim(), req.body.email.trim(), req.body.phone.trim(), req.body.enquiryType, req.body.message.trim());
  return res.redirect('/?sent=1#enquire');
});

app.get('/admin', requireAdmin, (req, res) => {
  const products = db.prepare('SELECT * FROM products ORDER BY updated_at DESC').all().map(formatProduct);
  const enquiries = db.prepare('SELECT * FROM enquiries ORDER BY created_at DESC').all();
  res.render('admin', { title: 'Bakery admin', products, enquiries, categories, token: req.query.token || '' });
});

app.post('/admin/products', requireAdmin, productInputRules(), (req, res) => {
  const errors = validationResult(req);
  if (!errors.isEmpty()) return res.status(422).render('error', { title: 'Product not saved', message: renderErrors(errors).join(' ') });
  db.prepare(`INSERT INTO products (name, description, price_cents, category, image_url) VALUES (?, ?, ?, ?, ?)`)
    .run(req.body.name.trim(), req.body.description.trim(), Math.round(Number(req.body.price) * 100), req.body.category, req.body.imageUrl.trim());
  res.redirect(`/admin?token=${encodeURIComponent(req.body.adminToken)}`);
});

app.post('/admin/products/:id/update', requireAdmin, productInputRules(), (req, res) => {
  const errors = validationResult(req);
  if (!errors.isEmpty()) return res.status(422).render('error', { title: 'Product not updated', message: renderErrors(errors).join(' ') });
  db.prepare(`UPDATE products SET name = ?, description = ?, price_cents = ?, category = ?, image_url = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?`)
    .run(req.body.name.trim(), req.body.description.trim(), Math.round(Number(req.body.price) * 100), req.body.category, req.body.imageUrl.trim(), Number(req.params.id));
  res.redirect(`/admin?token=${encodeURIComponent(req.body.adminToken)}`);
});

app.post('/admin/products/:id/delete', requireAdmin, (req, res) => {
  db.prepare('DELETE FROM products WHERE id = ?').run(Number(req.params.id));
  res.redirect(`/admin?token=${encodeURIComponent(req.body.adminToken)}`);
});

app.get('/api/products', (req, res) => res.json(db.prepare('SELECT * FROM products ORDER BY category, name').all().map(formatProduct)));
app.get('/api/admin/products', requireAdmin, (req, res) => res.json(db.prepare('SELECT * FROM products ORDER BY updated_at DESC').all().map(formatProduct)));
app.use((req, res) => res.status(404).render('error', { title: 'Page not found', message: 'That page does not exist.' }));

if (require.main === module) app.listen(port, () => console.log(`Adelaide Artisan Bakery running at http://localhost:${port}`));
module.exports = app;
