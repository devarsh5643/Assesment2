<?php
declare(strict_types=1);

session_start();
require_once __DIR__ . '/db/config.php';

$adminToken = getenv('ADMIN_TOKEN') ?: 'change-this-development-token';

try {
    $db = new Database();
} catch (Throwable $exception) {
    http_response_code(500);
    $errorTitle = 'PHP setup required';
    $errorMessage = extension_loaded('pdo_sqlite')
        ? 'The website could not create its database. Make sure the data folder is writable, then reload the page.'
        : 'The PDO SQLite extension is not enabled. Start the website with START_WEBSITE.bat or enable pdo_sqlite in your PHP installation.';
    include __DIR__ . '/pages/error.php';
    exit;
}

$categories = ['Bread', 'Pastry', 'Sweet', 'Savoury'];
$enquiryTypes = ['Custom order', 'Catering', 'Wholesale', 'General'];

if (!isset($_SESSION['cart']) || !is_array($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$page = trim((string) ($_GET['page'] ?? $_POST['page'] ?? 'home'), '/');
$action = (string) ($_POST['action'] ?? '');
$enquiryErrors = [];
$enquiryFormData = [];
$checkoutErrors = [];
$checkoutFormData = [];
$adminErrors = [];
$adminFormData = [];

function e($value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function redirectTo(string $location): void
{
    header('Location: ' . $location);
    exit;
}

function csrfIsValid(): bool
{
    $submitted = (string) ($_POST['csrf_token'] ?? '');
    return $submitted !== '' && hash_equals((string) $_SESSION['csrf_token'], $submitted);
}

function isAdmin(string $adminToken): bool
{
    $submittedToken = (string) ($_GET['token'] ?? $_POST['adminToken'] ?? '');
    return $submittedToken !== '' && hash_equals($adminToken, $submittedToken);
}

function formatProduct(array $product): array
{
    $regularCents = (int) $product['price_cents'];
    $offerCents = isset($product['special_offer_price_cents'])
        ? (int) $product['special_offer_price_cents']
        : 0;
    $offerActive = !empty($product['special_offer_active']) && $offerCents > 0;
    $effectiveCents = $offerActive ? $offerCents : $regularCents;

    return array_merge($product, [
        'price' => number_format($regularCents / 100, 2),
        'special_offer_price' => $offerActive ? number_format($offerCents / 100, 2) : null,
        'is_on_offer' => $offerActive,
        'effective_price_cents' => $effectiveCents,
        'effective_price' => number_format($effectiveCents / 100, 2),
    ]);
}

function cartUnitPriceCents(array $item): int
{
    if (isset($item['unit_price_cents'])) {
        return (int) $item['unit_price_cents'];
    }
    if (isset($item['effective_price_cents'])) {
        return (int) $item['effective_price_cents'];
    }
    if (isset($item['price_cents'])) {
        return (int) $item['price_cents'];
    }
    return (int) round(((float) ($item['price'] ?? 0)) * 100);
}

function validateContact(array $data, array $allowedTypes): array
{
    $errors = [];
    $name = trim((string) ($data['customerName'] ?? ''));
    $email = trim((string) ($data['email'] ?? ''));
    $phone = trim((string) ($data['phone'] ?? ''));
    $type = trim((string) ($data['enquiryType'] ?? ''));
    $message = trim((string) ($data['message'] ?? ''));

    if (strlen($name) < 2 || strlen($name) > 80) {
        $errors[] = 'Please enter your name (2–80 characters).';
    }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Please enter a valid email address.';
    }
    if (!preg_match('/^[0-9+() .-]{8,20}$/', $phone)) {
        $errors[] = 'Please enter a valid phone number.';
    }
    if (!in_array($type, $allowedTypes, true)) {
        $errors[] = 'Please choose an enquiry type.';
    }
    if (strlen($message) < 10 || strlen($message) > 1000) {
        $errors[] = 'Please enter a message between 10 and 1000 characters.';
    }

    return $errors;
}

function validateCheckout(array $data, array $cart): array
{
    $errors = [];
    $name = trim((string) ($data['customerName'] ?? ''));
    $email = trim((string) ($data['email'] ?? ''));
    $phone = trim((string) ($data['phone'] ?? ''));
    $address = trim((string) ($data['address'] ?? ''));
    $notes = trim((string) ($data['notes'] ?? ''));

    if (empty($cart)) {
        $errors[] = 'Your cart is empty.';
    }
    if (strlen($name) < 2 || strlen($name) > 80) {
        $errors[] = 'Please enter your name (2–80 characters).';
    }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Please enter a valid email address.';
    }
    if (!preg_match('/^[0-9+() .-]{8,20}$/', $phone)) {
        $errors[] = 'Please enter a valid phone number.';
    }
    if (strlen($address) < 8 || strlen($address) > 250) {
        $errors[] = 'Please enter a complete delivery address.';
    }
    if (strlen($notes) > 500) {
        $errors[] = 'Special requests must be 500 characters or fewer.';
    }

    return $errors;
}

function validateProduct(array $data, array $categories): array
{
    $errors = [];
    $name = trim((string) ($data['name'] ?? ''));
    $description = trim((string) ($data['description'] ?? ''));
    $price = trim((string) ($data['price'] ?? ''));
    $category = trim((string) ($data['category'] ?? ''));
    $imageUrl = trim((string) ($data['imageUrl'] ?? ''));

    if (strlen($name) < 2 || strlen($name) > 100) {
        $errors[] = 'Product name must be between 2 and 100 characters.';
    }
    if (strlen($description) < 10 || strlen($description) > 500) {
        $errors[] = 'Description must be between 10 and 500 characters.';
    }
    if (!is_numeric($price) || (float) $price <= 0 || (float) $price > 10000) {
        $errors[] = 'Price must be a positive amount no greater than $10,000.';
    }
    if (!in_array($category, $categories, true)) {
        $errors[] = 'Please choose a valid category.';
    }
    if (!filter_var($imageUrl, FILTER_VALIDATE_URL)) {
        $errors[] = 'Please enter a valid image URL.';
    }

    return $errors;
}

if ($page === 'home') {
    include __DIR__ . '/pages/home.php';
    exit;
}

if ($page === 'menu') {
    include __DIR__ . '/pages/menu.php';
    exit;
}

if ($page === 'cart-add' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrfIsValid()) {
        http_response_code(403);
        $errorTitle = 'Request expired';
        $errorMessage = 'Please return to the menu and try again.';
        include __DIR__ . '/pages/error.php';
        exit;
    }

    $productId = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
    $quantity = filter_input(INPUT_POST, 'quantity', FILTER_VALIDATE_INT) ?: 1;
    $quantity = max(1, min(99, $quantity));
    $product = $productId ? $db->getProductById($productId) : false;

    if ($product) {
        $product = formatProduct($product);
        $found = false;
        foreach ($_SESSION['cart'] as &$item) {
            if ((int) $item['id'] === (int) $productId) {
                $item['quantity'] = min(99, (int) $item['quantity'] + $quantity);
                $found = true;
                break;
            }
        }
        unset($item);

        if (!$found) {
            $_SESSION['cart'][] = [
                'id' => (int) $product['id'],
                'name' => $product['name'],
                'category' => $product['category'],
                'unit_price_cents' => (int) $product['effective_price_cents'],
                'quantity' => $quantity,
            ];
        }
    }

    redirectTo('index.php?page=checkout');
}

if ($page === 'cart-remove' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrfIsValid()) {
        http_response_code(403);
        $errorTitle = 'Request expired';
        $errorMessage = 'Please return to your cart and try again.';
        include __DIR__ . '/pages/error.php';
        exit;
    }

    $productId = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
    $_SESSION['cart'] = array_values(array_filter(
        $_SESSION['cart'],
        static function (array $item) use ($productId): bool {
            return (int) $item['id'] !== (int) $productId;
        }
    ));
    redirectTo('index.php?page=checkout');
}

if ($page === 'checkout') {
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'place-order') {
        $checkoutFormData = $_POST;
        if (!csrfIsValid()) {
            $checkoutErrors[] = 'Your session expired. Please try again.';
        } else {
            $checkoutErrors = validateCheckout($_POST, $_SESSION['cart']);
        }

        if (empty($checkoutErrors)) {
            $items = [];
            $totalCents = 0;
            foreach ($_SESSION['cart'] as $item) {
                $unitPriceCents = cartUnitPriceCents($item);
                $quantity = (int) $item['quantity'];
                $totalCents += $unitPriceCents * $quantity;
                $items[] = $item['name'] . ' (qty: ' . $quantity . ')';
            }

            $address = trim((string) $_POST['address']);
            $notes = trim((string) ($_POST['notes'] ?? ''));
            $orderMessage = 'Delivery address: ' . $address
                . '. Items: ' . implode(', ', $items)
                . '. Special requests: ' . ($notes !== '' ? $notes : 'None')
                . '. Total: $' . number_format($totalCents / 100, 2) . '.';

            $db->addEnquiry(
                trim((string) $_POST['customerName']),
                trim((string) $_POST['email']),
                trim((string) $_POST['phone']),
                'Online Order',
                $orderMessage
            );
            $_SESSION['cart'] = [];
            redirectTo('index.php?ordered=1');
        }

        http_response_code(422);
    }

    include __DIR__ . '/pages/checkout.php';
    exit;
}

if ($page === 'enquiries' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $enquiryFormData = $_POST;
    if (!csrfIsValid()) {
        $enquiryErrors[] = 'Your session expired. Please try again.';
    } else {
        $enquiryErrors = validateContact($_POST, $enquiryTypes);
    }

    if (empty($enquiryErrors)) {
        $db->addEnquiry(
            trim((string) $_POST['customerName']),
            trim((string) $_POST['email']),
            trim((string) $_POST['phone']),
            trim((string) $_POST['enquiryType']),
            trim((string) $_POST['message'])
        );
        redirectTo('index.php?sent=1#enquire');
    }

    http_response_code(422);
    include __DIR__ . '/pages/home.php';
    exit;
}

if ($page === 'admin') {
    if (!isAdmin($adminToken)) {
        http_response_code(401);
        $errorTitle = 'Admin access required';
        $errorMessage = 'Use the configured admin token to access this page.';
        include __DIR__ . '/pages/error.php';
        exit;
    }

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $adminFormData = $_POST;
        if (!csrfIsValid()) {
            $adminErrors[] = 'Your session expired. Please try again.';
        } elseif ($action === 'add-product' || $action === 'update-product') {
            $adminErrors = validateProduct($_POST, $categories);
            $productId = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);

            if ($action === 'update-product' && !$productId) {
                $adminErrors[] = 'The selected product could not be found.';
            }

            if (empty($adminErrors)) {
                $priceCents = (int) round(((float) $_POST['price']) * 100);
                if ($action === 'add-product') {
                    $db->addProduct(
                        trim((string) $_POST['name']),
                        trim((string) $_POST['description']),
                        $priceCents,
                        trim((string) $_POST['category']),
                        trim((string) $_POST['imageUrl'])
                    );
                    redirectTo('index.php?page=admin&token=' . rawurlencode($adminToken) . '&notice=added');
                }

                $db->updateProduct(
                    (int) $productId,
                    trim((string) $_POST['name']),
                    trim((string) $_POST['description']),
                    $priceCents,
                    trim((string) $_POST['category']),
                    trim((string) $_POST['imageUrl'])
                );
                redirectTo('index.php?page=admin&token=' . rawurlencode($adminToken) . '&notice=updated');
            }
        } elseif ($action === 'delete-product') {
            $productId = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
            if (!$productId) {
                $adminErrors[] = 'The selected product could not be found.';
            } else {
                $db->deleteProduct((int) $productId);
                redirectTo('index.php?page=admin&token=' . rawurlencode($adminToken) . '&notice=deleted');
            }
        } elseif ($action === 'set-offer') {
            $productId = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
            $product = $productId ? $db->getProductById((int) $productId) : false;
            $active = isset($_POST['offerActive']);
            $offerPriceRaw = trim((string) ($_POST['offerPrice'] ?? ''));
            $offerPriceCents = null;

            if (!$product) {
                $adminErrors[] = 'The selected product could not be found.';
            } elseif ($active) {
                if (!is_numeric($offerPriceRaw) || (float) $offerPriceRaw <= 0) {
                    $adminErrors[] = 'Enter a positive offer price before activating the offer.';
                } else {
                    $offerPriceCents = (int) round(((float) $offerPriceRaw) * 100);
                    if ($offerPriceCents >= (int) $product['price_cents']) {
                        $adminErrors[] = 'The offer price must be lower than the regular price.';
                    }
                }
            }

            if (empty($adminErrors)) {
                $db->setSpecialOffer((int) $productId, $offerPriceCents, $active ? 1 : 0);
                redirectTo('index.php?page=admin&token=' . rawurlencode($adminToken) . '&notice=offer');
            }
        } else {
            $adminErrors[] = 'The requested admin action is not supported.';
        }

        if (!empty($adminErrors)) {
            http_response_code(422);
        }
    }

    include __DIR__ . '/pages/admin.php';
    exit;
}

http_response_code(404);
$errorTitle = 'Page not found';
$errorMessage = 'The requested page does not exist.';
include __DIR__ . '/pages/error.php';
