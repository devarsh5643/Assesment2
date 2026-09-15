<?php
declare(strict_types=1);

session_set_cookie_params([
    'httponly' => true,
    'secure' => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
    'samesite' => 'Lax',
]);
session_start();
require_once __DIR__ . '/db/config.php';

$adminPasswordHash = getenv('ADMIN_PASSWORD_HASH') ?: '';
$adminPasswordHashFile = '/var/www/.bakery-admin-password-hash';
if ($adminPasswordHash === '' && is_readable($adminPasswordHashFile)) {
    $adminPasswordHash = trim((string) file_get_contents($adminPasswordHashFile));
}
$adminMfaFile = '/var/www/.bakery-admin-mfa.json';
$adminMfaConfig = ['enabled' => false, 'secret' => '', 'recovery_codes' => [], 'last_counter' => -1];
if (is_readable($adminMfaFile)) {
    $savedMfaConfig = json_decode((string) file_get_contents($adminMfaFile), true);
    if (is_array($savedMfaConfig)) {
        $adminMfaConfig = array_merge($adminMfaConfig, $savedMfaConfig);
    }
}

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
$adminLoginError = '';

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

function isAdmin(): bool
{
    return isset($_SESSION['admin_authenticated']) && $_SESSION['admin_authenticated'] === true;
}

function base32Encode(string $value): string
{
    $alphabet = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';
    $buffer = 0;
    $bitsLeft = 0;
    $encoded = '';

    foreach (unpack('C*', $value) as $byte) {
        $buffer = ($buffer << 8) | $byte;
        $bitsLeft += 8;
        while ($bitsLeft >= 5) {
            $bitsLeft -= 5;
            $encoded .= $alphabet[($buffer >> $bitsLeft) & 31];
        }
        $buffer = $bitsLeft > 0 ? $buffer & ((1 << $bitsLeft) - 1) : 0;
    }
    if ($bitsLeft > 0) {
        $encoded .= $alphabet[($buffer << (5 - $bitsLeft)) & 31];
    }

    return $encoded;
}

function base32Decode(string $value): string
{
    $alphabet = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';
    $clean = strtoupper((string) preg_replace('/[^A-Z2-7]/i', '', $value));
    $buffer = 0;
    $bitsLeft = 0;
    $decoded = '';

    for ($i = 0, $length = strlen($clean); $i < $length; $i++) {
        $position = strpos($alphabet, $clean[$i]);
        if ($position === false) {
            return '';
        }
        $buffer = ($buffer << 5) | $position;
        $bitsLeft += 5;
        if ($bitsLeft >= 8) {
            $bitsLeft -= 8;
            $decoded .= chr(($buffer >> $bitsLeft) & 255);
        }
        $buffer = $bitsLeft > 0 ? $buffer & ((1 << $bitsLeft) - 1) : 0;
    }

    return $decoded;
}

function totpCode(string $secret, int $counter): string
{
    $key = base32Decode($secret);
    $high = intdiv($counter, 4294967296);
    $low = $counter % 4294967296;
    $digest = hash_hmac('sha1', pack('N2', $high, $low), $key, true);
    $offset = ord($digest[19]) & 15;
    $number = ((ord($digest[$offset]) & 127) << 24)
        | ((ord($digest[$offset + 1]) & 255) << 16)
        | ((ord($digest[$offset + 2]) & 255) << 8)
        | (ord($digest[$offset + 3]) & 255);

    return str_pad((string) ($number % 1000000), 6, '0', STR_PAD_LEFT);
}

function verifyTotp(string $secret, string $submittedCode, int $window = 1): ?int
{
    $code = preg_replace('/\s+/', '', $submittedCode);
    if (!is_string($code) || !preg_match('/^[0-9]{6}$/', $code) || $secret === '') {
        return null;
    }

    $currentCounter = intdiv(time(), 30);
    for ($offset = -$window; $offset <= $window; $offset++) {
        $counter = $currentCounter + $offset;
        if ($counter >= 0 && hash_equals(totpCode($secret, $counter), $code)) {
            return $counter;
        }
    }

    return null;
}

function normaliseRecoveryCode(string $code): string
{
    return strtoupper((string) preg_replace('/[^A-Z0-9]/i', '', $code));
}

function useRecoveryCode(array &$config, string $submittedCode): bool
{
    $code = normaliseRecoveryCode($submittedCode);
    if (strlen($code) < 8 || empty($config['recovery_codes']) || !is_array($config['recovery_codes'])) {
        return false;
    }

    foreach ($config['recovery_codes'] as $index => $hash) {
        if (is_string($hash) && password_verify($code, $hash)) {
            unset($config['recovery_codes'][$index]);
            $config['recovery_codes'] = array_values($config['recovery_codes']);
            return true;
        }
    }

    return false;
}

function saveMfaConfig(string $path, array $config): bool
{
    $json = json_encode($config, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    return is_string($json) && file_put_contents($path, $json . PHP_EOL, LOCK_EX) !== false;
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
    header('Cache-Control: no-store, no-cache, must-revalidate');

    if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'admin-login') {
        if (!csrfIsValid()) {
            $adminLoginError = 'Your session expired. Please reload the page and try again.';
        } elseif ($adminPasswordHash !== '' && password_verify((string) ($_POST['password'] ?? ''), $adminPasswordHash)) {
            session_regenerate_id(true);
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
            if (!empty($adminMfaConfig['enabled']) && !empty($adminMfaConfig['secret'])) {
                $_SESSION['admin_mfa_pending'] = true;
                unset($_SESSION['admin_authenticated']);
            } else {
                $_SESSION['admin_authenticated'] = true;
                unset($_SESSION['admin_mfa_pending']);
            }
            redirectTo('index.php?page=admin');
        } else {
            usleep(500000);
            $adminLoginError = 'Incorrect password. Please try again.';
        }

        http_response_code(401);
        include __DIR__ . '/pages/admin-login.php';
        exit;
    }

    if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'admin-mfa-login') {
        $submittedCode = (string) ($_POST['verificationCode'] ?? '');
        $verified = false;

        if (!csrfIsValid()) {
            $adminLoginError = 'Your session expired. Please reload the page and try again.';
        } elseif (empty($_SESSION['admin_mfa_pending']) || empty($adminMfaConfig['enabled'])) {
            $adminLoginError = 'Please enter your password again.';
        } else {
            $counter = verifyTotp((string) $adminMfaConfig['secret'], $submittedCode);
            $lastCounter = (int) ($adminMfaConfig['last_counter'] ?? -1);

            if ($counter !== null && $counter > $lastCounter) {
                $adminMfaConfig['last_counter'] = $counter;
                $verified = saveMfaConfig($adminMfaFile, $adminMfaConfig);
            } elseif (useRecoveryCode($adminMfaConfig, $submittedCode)) {
                $verified = saveMfaConfig($adminMfaFile, $adminMfaConfig);
            }

            if (!$verified) {
                usleep(500000);
                $adminLoginError = 'The verification code is incorrect or has already been used.';
            }
        }

        if ($verified) {
            session_regenerate_id(true);
            $_SESSION['admin_authenticated'] = true;
            unset($_SESSION['admin_mfa_pending']);
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
            redirectTo('index.php?page=admin');
        }

        http_response_code(401);
        include __DIR__ . '/pages/admin-login.php';
        exit;
    }

    if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'admin-mfa-back') {
        if (csrfIsValid()) {
            unset($_SESSION['admin_mfa_pending']);
            session_regenerate_id(true);
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        redirectTo('index.php?page=admin');
    }

    if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'admin-logout') {
        if (csrfIsValid()) {
            unset($_SESSION['admin_authenticated'], $_SESSION['admin_mfa_pending'], $_SESSION['mfa_setup_secret']);
            session_regenerate_id(true);
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        redirectTo('index.php?page=admin');
    }

    if (!isAdmin()) {
        include __DIR__ . '/pages/admin-login.php';
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
                    redirectTo('index.php?page=admin&notice=added');
                }

                $db->updateProduct(
                    (int) $productId,
                    trim((string) $_POST['name']),
                    trim((string) $_POST['description']),
                    $priceCents,
                    trim((string) $_POST['category']),
                    trim((string) $_POST['imageUrl'])
                );
                redirectTo('index.php?page=admin&notice=updated');
            }
        } elseif ($action === 'delete-product') {
            $productId = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
            if (!$productId) {
                $adminErrors[] = 'The selected product could not be found.';
            } else {
                $db->deleteProduct((int) $productId);
                redirectTo('index.php?page=admin&notice=deleted');
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
                redirectTo('index.php?page=admin&notice=offer');
            }
        } elseif ($action === 'mfa-start') {
            $currentPassword = (string) ($_POST['currentPassword'] ?? '');
            if ($adminPasswordHash === '' || !password_verify($currentPassword, $adminPasswordHash)) {
                $adminErrors[] = 'The current password is incorrect.';
            } elseif (!empty($adminMfaConfig['enabled'])) {
                $adminErrors[] = 'Microsoft Authenticator is already enabled.';
            } else {
                $_SESSION['mfa_setup_secret'] = base32Encode(random_bytes(20));
                redirectTo('index.php?page=admin&notice=mfa-setup#security');
            }
        } elseif ($action === 'mfa-enable') {
            $setupSecret = (string) ($_SESSION['mfa_setup_secret'] ?? '');
            $counter = verifyTotp($setupSecret, (string) ($_POST['verificationCode'] ?? ''));

            if ($setupSecret === '') {
                $adminErrors[] = 'The setup session expired. Please start again.';
            } elseif ($counter === null) {
                $adminErrors[] = 'Enter the current six-digit code from Microsoft Authenticator.';
            } else {
                $recoveryCodes = [];
                $recoveryHashes = [];
                for ($i = 0; $i < 8; $i++) {
                    $rawCode = strtoupper(bin2hex(random_bytes(5)));
                    $displayCode = substr($rawCode, 0, 5) . '-' . substr($rawCode, 5);
                    $recoveryCodes[] = $displayCode;
                    $recoveryHashes[] = password_hash($rawCode, PASSWORD_DEFAULT);
                }

                $newMfaConfig = [
                    'enabled' => true,
                    'secret' => $setupSecret,
                    'recovery_codes' => $recoveryHashes,
                    'last_counter' => $counter,
                ];

                if (!saveMfaConfig($adminMfaFile, $newMfaConfig)) {
                    $adminErrors[] = 'Authenticator settings could not be saved. Please try again.';
                } else {
                    unset($_SESSION['mfa_setup_secret']);
                    $_SESSION['mfa_recovery_codes'] = $recoveryCodes;
                    redirectTo('index.php?page=admin&notice=mfa-enabled#security');
                }
            }
        } elseif ($action === 'mfa-cancel') {
            unset($_SESSION['mfa_setup_secret']);
            redirectTo('index.php?page=admin#security');
        } elseif ($action === 'mfa-disable') {
            $currentPassword = (string) ($_POST['currentPassword'] ?? '');
            $submittedCode = (string) ($_POST['verificationCode'] ?? '');
            $counter = verifyTotp((string) ($adminMfaConfig['secret'] ?? ''), $submittedCode);
            $validSecondFactor = $counter !== null || useRecoveryCode($adminMfaConfig, $submittedCode);

            if ($adminPasswordHash === '' || !password_verify($currentPassword, $adminPasswordHash)) {
                $adminErrors[] = 'The current password is incorrect.';
            }
            if (!$validSecondFactor) {
                $adminErrors[] = 'Enter a valid Authenticator or recovery code.';
            }

            if (empty($adminErrors)) {
                $disabledMfaConfig = ['enabled' => false, 'secret' => '', 'recovery_codes' => [], 'last_counter' => -1];
                if (!saveMfaConfig($adminMfaFile, $disabledMfaConfig)) {
                    $adminErrors[] = 'Authenticator settings could not be updated. Please try again.';
                } else {
                    unset($_SESSION['mfa_setup_secret'], $_SESSION['mfa_recovery_codes']);
                    redirectTo('index.php?page=admin&notice=mfa-disabled#security');
                }
            }
        } elseif ($action === 'change-password') {
            $currentPassword = (string) ($_POST['currentPassword'] ?? '');
            $newPassword = (string) ($_POST['newPassword'] ?? '');
            $confirmPassword = (string) ($_POST['confirmPassword'] ?? '');

            if ($adminPasswordHash === '' || !password_verify($currentPassword, $adminPasswordHash)) {
                $adminErrors[] = 'The current password is incorrect.';
            }
            if (strlen($newPassword) < 10) {
                $adminErrors[] = 'The new password must contain at least 10 characters.';
            }
            if (!preg_match('/[A-Z]/', $newPassword)
                || !preg_match('/[a-z]/', $newPassword)
                || !preg_match('/[0-9]/', $newPassword)
                || !preg_match('/[^A-Za-z0-9]/', $newPassword)) {
                $adminErrors[] = 'Use an uppercase letter, lowercase letter, number and symbol in the new password.';
            }
            if (!hash_equals($newPassword, $confirmPassword)) {
                $adminErrors[] = 'The new passwords do not match.';
            }
            if ($currentPassword !== '' && hash_equals($currentPassword, $newPassword)) {
                $adminErrors[] = 'Choose a new password that is different from the current password.';
            }

            if (empty($adminErrors)) {
                $newPasswordHash = password_hash($newPassword, PASSWORD_DEFAULT);
                $written = file_put_contents($adminPasswordHashFile, $newPasswordHash . PHP_EOL, LOCK_EX);

                if ($written === false) {
                    $adminErrors[] = 'The password file could not be updated. Please try again.';
                } else {
                    clearstatcache(true, $adminPasswordHashFile);
                    redirectTo('index.php?page=admin&notice=password#security');
                }
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
