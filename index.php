<?php
session_start();
require_once 'db/config.php';

// Initialize database
$db = new Database();
$pdo = $db->getConnection();

// Initialize session cart
if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

// Admin token
$adminToken = 'change-this-development-token';
$categories = ['Bread', 'Pastry', 'Sweet', 'Savoury'];

// Get the page/action from URL
$page = isset($_GET['page']) ? trim($_GET['page'], '/') : 'home';
$action = isset($_POST['action']) ? $_POST['action'] : null;

// Helper function to format product with price
function formatProduct($product) {
    $price = number_format($product['price_cents'] / 100, 2);
    $specialOfferPrice = $product['special_offer_price_cents'] ? number_format($product['special_offer_price_cents'] / 100, 2) : null;
    return array_merge($product, [
        'price' => $price,
        'special_offer_price' => $specialOfferPrice,
        'is_on_offer' => $product['special_offer_active'] && $specialOfferPrice
    ]);
}

// Check admin token
function isAdmin() {
    global $adminToken;
    return (isset($_GET['token']) && $_GET['token'] === $adminToken) || 
           (isset($_POST['adminToken']) && $_POST['adminToken'] === $adminToken);
}

// Route handling
if ($page === 'menu') {
    include 'pages/menu.php';
} elseif ($page === 'checkout') {
    include 'pages/checkout.php';
} elseif ($page === 'admin') {
    if (!isAdmin()) {
        include 'pages/error.php';
        exit;
    }
    
    // Handle admin actions
    if ($action === 'add-product' && isAdmin()) {
        $name = $_POST['name'] ?? '';
        $description = $_POST['description'] ?? '';
        $price = floatval($_POST['price'] ?? 0) * 100;
        $category = $_POST['category'] ?? '';
        $imageUrl = $_POST['imageUrl'] ?? '';
        
        if ($name && $description && $price && $category && $imageUrl) {
            $stmt = $pdo->prepare("INSERT INTO products (name, description, price_cents, category, image_url) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([$name, $description, intval($price), $category, $imageUrl]);
        }
        header('Location: index.php?page=admin&token=' . urlencode($_POST['adminToken']));
        exit;
    }
    
    if ($action === 'update-product' && isAdmin()) {
        $id = intval($_POST['id'] ?? 0);
        $name = $_POST['name'] ?? '';
        $description = $_POST['description'] ?? '';
        $price = floatval($_POST['price'] ?? 0) * 100;
        $category = $_POST['category'] ?? '';
        $imageUrl = $_POST['imageUrl'] ?? '';
        
        if ($id && $name && $description && $price && $category && $imageUrl) {
            $db->updateProduct($id, $name, $description, intval($price), $category, $imageUrl);
        }
        header('Location: index.php?page=admin&token=' . urlencode($_POST['adminToken']));
        exit;
    }
    
    if ($action === 'set-offer' && isAdmin()) {
        $id = intval($_POST['id'] ?? 0);
        $offerPrice = isset($_POST['offerPrice']) && $_POST['offerPrice'] !== '' ? intval(floatval($_POST['offerPrice']) * 100) : null;
        $active = isset($_POST['offerActive']) ? 1 : 0;
        
        if ($id) {
            $db->setSpecialOffer($id, $offerPrice, $active);
        }
        header('Location: index.php?page=admin&token=' . urlencode($_POST['adminToken']));
        exit;
    }
    
    include 'pages/admin.php';
} elseif ($page === 'cart-add' && isset($_GET['id'])) {
    $productId = intval($_GET['id']);
    $product = $db->getProductById($productId);
    if ($product) {
        $product = formatProduct($product);
        $existing = null;
        foreach ($_SESSION['cart'] as &$item) {
            if ($item['id'] == $productId) {
                $item['quantity']++;
                $existing = true;
                break;
            }
        }
        if (!$existing) {
            $_SESSION['cart'][] = array_merge($product, ['quantity' => 1]);
        }
    }
    header('Location: index.php?page=checkout');
    exit;
} elseif ($page === 'cart-remove' && isset($_GET['id'])) {
    $productId = intval($_GET['id']);
    $_SESSION['cart'] = array_filter($_SESSION['cart'], function($item) use ($productId) {
        return $item['id'] != $productId;
    });
    header('Location: index.php?page=checkout');
    exit;
} elseif ($page === 'enquiries' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = $_POST['name'] ?? $_POST['customerName'] ?? '';
    $email = $_POST['email'] ?? '';
    $phone = $_POST['phone'] ?? '';
    $type = $_POST['enquiryType'] ?? 'Online Order';
    $message = $_POST['message'] ?? '';
    
    // For order checkout
    if (isset($_POST['address'])) {
        $address = $_POST['address'] ?? '';
        $notes = $_POST['notes'] ?? '';
        $cart = $_SESSION['cart'] ?? [];
        $items = implode(', ', array_map(function($item) {
            return $item['name'] . " (qty: " . $item['quantity'] . ")";
        }, $cart));
        $total = array_reduce($cart, function($sum, $item) {
            return $sum + ($item['price'] * $item['quantity']);
        }, 0);
        $message = "Order for delivery to: $address. Items: $items. Special requests: $notes. Total: $" . number_format($total, 2);
        $_SESSION['cart'] = [];
    }
    
    if ($name && $email && $phone && $type && $message) {
        $db->addEnquiry($name, $email, $phone, $type, $message);
        header('Location: index.php?sent=1');
        exit;
    }
} else {
    include 'pages/home.php';
}
?>
