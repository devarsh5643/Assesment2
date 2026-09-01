<?php
// Database configuration and functions
class Database {
    private $db;
    
    public function __construct() {
        $dbPath = __DIR__ . '/../data/bakery.db';
        
        // Create data directory if it doesn't exist
        if (!is_dir(dirname($dbPath))) {
            mkdir(dirname($dbPath), 0755, true);
        }
        
        $this->db = new PDO('sqlite:' . $dbPath);
        $this->db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        $this->ensureSchema();
    }
    
    private function ensureSchema() {
        $this->db->exec("
            CREATE TABLE IF NOT EXISTS products (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                name TEXT NOT NULL,
                description TEXT NOT NULL,
                price_cents INTEGER NOT NULL CHECK (price_cents >= 0),
                category TEXT NOT NULL,
                image_url TEXT NOT NULL,
                created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
                updated_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
                special_offer_price_cents INTEGER,
                special_offer_active INTEGER DEFAULT 0
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
        ");
        
        // Seed initial products if empty
        $count = $this->db->query("SELECT COUNT(*) as count FROM products")->fetch(PDO::FETCH_ASSOC);
        if ($count['count'] == 0) {
            $this->seedProducts();
        }
    }
    
    private function seedProducts() {
        $products = [
            ['name' => 'Sourdough Country Loaf', 'description' => 'Slow-fermented with a crisp crust and a tender, open crumb.', 'priceCents' => 850, 'category' => 'Bread', 'imageUrl' => 'https://images.unsplash.com/photo-1509440159596-0249088772ff?auto=format&fit=crop&w=900&q=80'],
            ['name' => 'Almond Croissant', 'description' => 'Buttery laminated pastry filled with almond frangipane and toasted almonds.', 'priceCents' => 680, 'category' => 'Pastry', 'imageUrl' => 'https://images.unsplash.com/photo-1555507036-ab1f4038808a?auto=format&fit=crop&w=900&q=80'],
            ['name' => 'Seasonal Fruit Tart', 'description' => 'Vanilla bean custard, crisp pastry and the best fruit from the market.', 'priceCents' => 720, 'category' => 'Sweet', 'imageUrl' => 'https://images.unsplash.com/photo-1519915028121-7d3463d20b13?auto=format&fit=crop&w=900&q=80'],
            ['name' => 'Rosemary Focaccia', 'description' => 'Olive oil-rich focaccia finished with rosemary, sea salt and garlic.', 'priceCents' => 950, 'category' => 'Bread', 'imageUrl' => 'https://images.unsplash.com/photo-1598373182133-52452f7691ef?auto=format&fit=crop&w=900&q=80'],
            ['name' => 'Chocolate Éclair', 'description' => 'Light choux pastry filled with silky chocolate cream and topped with glossy dark chocolate.', 'priceCents' => 450, 'category' => 'Sweet', 'imageUrl' => 'https://images.unsplash.com/photo-1585080876138-1a6da1709e88?auto=format&fit=crop&w=900&q=80'],
            ['name' => 'Olive & Feta Soufflé', 'description' => 'Savoury puffed pastry with Kalamata olives, creamy feta and fresh herbs.', 'priceCents' => 550, 'category' => 'Savoury', 'imageUrl' => 'https://images.unsplash.com/photo-1586985289688-cacf913ecc0c?auto=format&fit=crop&w=900&q=80'],
            ['name' => 'Pistachio Macarons', 'description' => 'Delicate French almond meringue cookies with creamy pistachio filling. Pack of 6.', 'priceCents' => 890, 'category' => 'Sweet', 'imageUrl' => 'https://images.unsplash.com/photo-1569718212817-e52f991a10e7?auto=format&fit=crop&w=900&q=80'],
            ['name' => 'Spinach & Cheese Danish', 'description' => 'Flaky laminated pastry with seasoned spinach, ricotta and roasted garlic.', 'priceCents' => 620, 'category' => 'Savoury', 'imageUrl' => 'https://images.unsplash.com/photo-1574197201214-f6b766a13c7d?auto=format&fit=crop&w=900&q=80'],
            ['name' => 'Multigrain Artisan Loaf', 'description' => 'Hearty blend of grains and seeds with a nutty flavour and wholesome texture.', 'priceCents' => 920, 'category' => 'Bread', 'imageUrl' => 'https://images.unsplash.com/photo-1580822184713-fc5400e7fe10?auto=format&fit=crop&w=900&q=80'],
            ['name' => 'Lemon Meringue Tart', 'description' => 'Tangy lemon curd in a crisp pastry shell, topped with golden toasted meringue.', 'priceCents' => 780, 'category' => 'Sweet', 'imageUrl' => 'https://images.unsplash.com/photo-1578985545062-69928b1d9587?auto=format&fit=crop&w=900&q=80'],
            ['name' => 'Goat Cheese & Honey Pastry', 'description' => 'Creamy goat cheese with golden honey drizzle on flaky pastry crust.', 'priceCents' => 590, 'category' => 'Savoury', 'imageUrl' => 'https://images.unsplash.com/photo-1623428508639-7f821e5da61f?auto=format&fit=crop&w=900&q=80'],
            ['name' => 'Matcha Green Tea Cake', 'description' => 'Delicate matcha sponge cake with white chocolate mousse and fresh berries.', 'priceCents' => 650, 'category' => 'Sweet', 'imageUrl' => 'https://images.unsplash.com/photo-1578985545062-69928b1d9587?auto=format&fit=crop&w=900&q=80'],
            ['name' => 'Sesame Bagel', 'description' => 'Chewy bagel with a crispy crust, topped with toasted sesame seeds.', 'priceCents' => 380, 'category' => 'Bread', 'imageUrl' => 'https://images.unsplash.com/photo-1573520056683-d51a44f19fac?auto=format&fit=crop&w=900&q=80'],
            ['name' => 'Pesto & Sun-Dried Tomato Bread', 'description' => 'Aromatic basil pesto swirled with sun-dried tomatoes in soft Italian-style loaf.', 'priceCents' => 880, 'category' => 'Bread', 'imageUrl' => 'https://images.unsplash.com/photo-1598373182133-52452f7691ef?auto=format&fit=crop&w=900&q=80'],
            ['name' => 'Vanilla Bean Custard Slice', 'description' => 'Classic custard slice with smooth vanilla bean custard between crispy pastry layers.', 'priceCents' => 520, 'category' => 'Sweet', 'imageUrl' => 'https://images.unsplash.com/photo-1578985545062-69928b1d9587?auto=format&fit=crop&w=900&q=80'],
            ['name' => 'Mushroom & Thyme Quiche', 'description' => 'Savory quiche with sautéed mushrooms, fresh thyme and creamy egg custard.', 'priceCents' => 680, 'category' => 'Savoury', 'imageUrl' => 'https://images.unsplash.com/photo-1578985545062-69928b1d9587?auto=format&fit=crop&w=900&q=80'],
            ['name' => 'Strawberry Shortcake', 'description' => 'Light sponge cake with fresh whipped cream and juicy fresh strawberries.', 'priceCents' => 750, 'category' => 'Sweet', 'imageUrl' => 'https://images.unsplash.com/photo-1578985545062-69928b1d9587?auto=format&fit=crop&w=900&q=80'],
            ['name' => 'Chorizo & Paprika Soufflé', 'description' => 'Spanish-inspired savoury pastry with chorizo, roasted peppers and smoked paprika.', 'priceCents' => 610, 'category' => 'Savoury', 'imageUrl' => 'https://images.unsplash.com/photo-1578985545062-69928b1d9587?auto=format&fit=crop&w=900&q=80'],
        ];
        
        $stmt = $this->db->prepare("INSERT INTO products (name, description, price_cents, category, image_url) VALUES (?, ?, ?, ?, ?)");
        foreach ($products as $p) {
            $stmt->execute([$p['name'], $p['description'], $p['priceCents'], $p['category'], $p['imageUrl']]);
        }
    }
    
    public function getConnection() {
        return $this->db;
    }
    
    public function getAllProducts() {
        return $this->db->query("SELECT * FROM products ORDER BY category, name")->fetchAll(PDO::FETCH_ASSOC);
    }
    
    public function getFeaturedProducts($limit = 5) {
        return $this->db->query("SELECT * FROM products ORDER BY category, name LIMIT $limit")->fetchAll(PDO::FETCH_ASSOC);
    }
    
    public function getProductById($id) {
        $stmt = $this->db->prepare("SELECT * FROM products WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    public function addEnquiry($name, $email, $phone, $type, $message) {
        $stmt = $this->db->prepare("INSERT INTO enquiries (customer_name, email, phone, enquiry_type, message) VALUES (?, ?, ?, ?, ?)");
        return $stmt->execute([$name, $email, $phone, $type, $message]);
    }
    
    public function getEnquiries() {
        return $this->db->query("SELECT * FROM enquiries ORDER BY created_at DESC")->fetchAll(PDO::FETCH_ASSOC);
    }
    
    public function updateProduct($id, $name, $description, $price_cents, $category, $image_url) {
        $stmt = $this->db->prepare("UPDATE products SET name = ?, description = ?, price_cents = ?, category = ?, image_url = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?");
        return $stmt->execute([$name, $description, $price_cents, $category, $image_url, $id]);
    }
    
    public function deleteProduct($id) {
        $stmt = $this->db->prepare("DELETE FROM products WHERE id = ?");
        return $stmt->execute([$id]);
    }
    
    public function setSpecialOffer($id, $offer_price_cents, $active) {
        $stmt = $this->db->prepare("UPDATE products SET special_offer_price_cents = ?, special_offer_active = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?");
        return $stmt->execute([$offer_price_cents, $active, $id]);
    }
}
?>
