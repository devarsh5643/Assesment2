<?php
$cart = $_SESSION['cart'] ?? [];
$subtotalCents = 0;
foreach ($cart as $cartItem) {
    $subtotalCents += cartUnitPriceCents($cartItem) * (int) $cartItem['quantity'];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Checkout - Adelaide Artisan Bakery</title>
    <link rel="stylesheet" href="public/styles.css">
</head>
<body>
<header class="site-header">
    <div class="container nav-wrap">
        <a class="brand" href="index.php" aria-label="Adelaide Artisan Bakery home">
            <span class="brand-mark">AAB</span>
            <span>Adelaide<br><em>Artisan Bakery</em></span>
        </a>
        <nav aria-label="Primary navigation">
            <a href="index.php?page=menu">Menu</a>
            <a href="index.php?page=checkout" class="button button-small" aria-current="page">Cart</a>
        </nav>
    </div>
</header>

<main>
    <section class="checkout-section container" aria-labelledby="cart-title">
        <?php if (!empty($checkoutErrors)): ?>
        <div class="notice error checkout-notice" role="alert" aria-live="polite">
            <strong>Please check the following:</strong>
            <ul>
                <?php foreach ($checkoutErrors as $error): ?>
                <li><?= e($error) ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
        <?php endif; ?>

        <div class="checkout-grid">
            <section class="checkout-cart">
                <h1 id="cart-title">Cart items</h1>
                <?php if (!empty($cart)): ?>
                <div class="cart-items">
                    <?php foreach ($cart as $item): ?>
                    <?php
                    $unitPriceCents = cartUnitPriceCents($item);
                    $itemTotalCents = $unitPriceCents * (int) $item['quantity'];
                    ?>
                    <div class="cart-item">
                        <div class="cart-item-details">
                            <h2><?= e($item['name']) ?></h2>
                            <p class="cart-item-meta"><?= e($item['category']) ?> · Qty: <?= (int) $item['quantity'] ?></p>
                        </div>
                        <div class="cart-item-price">
                            <strong>$<?= number_format($itemTotalCents / 100, 2) ?></strong>
                            <form action="index.php?page=cart-remove" method="post">
                                <input type="hidden" name="csrf_token" value="<?= e($_SESSION['csrf_token']) ?>">
                                <input type="hidden" name="id" value="<?= (int) $item['id'] ?>">
                                <button class="text-button" type="submit">Remove</button>
                            </form>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>

                <div class="cart-summary" role="group" aria-label="Order total">
                    <div class="summary-row">
                        <span>Subtotal</span>
                        <strong>$<?= number_format($subtotalCents / 100, 2) ?></strong>
                    </div>
                    <div class="summary-row">
                        <span>Delivery</span>
                        <strong>Free</strong>
                    </div>
                    <div class="summary-row total">
                        <span>Total</span>
                        <strong>$<?= number_format($subtotalCents / 100, 2) ?></strong>
                    </div>
                </div>
                <?php else: ?>
                <p class="empty-state">Your cart is empty. <a href="index.php?page=menu">Browse the menu</a> to add items.</p>
                <?php endif; ?>
            </section>

            <section class="checkout-form" aria-labelledby="delivery-title">
                <h2 id="delivery-title">Delivery details</h2>
                <form action="index.php?page=checkout" method="post" novalidate>
                    <input type="hidden" name="action" value="place-order">
                    <input type="hidden" name="csrf_token" value="<?= e($_SESSION['csrf_token']) ?>">

                    <div class="field-row">
                        <div class="field">
                            <label for="customerName">Your name</label>
                            <input id="customerName" name="customerName" type="text" value="<?= e($checkoutFormData['customerName'] ?? '') ?>" autocomplete="name" required>
                        </div>
                        <div class="field">
                            <label for="email">Email address</label>
                            <input id="email" name="email" type="email" value="<?= e($checkoutFormData['email'] ?? '') ?>" autocomplete="email" required>
                        </div>
                    </div>

                    <div class="field">
                        <label for="phone">Phone</label>
                        <input id="phone" name="phone" type="tel" value="<?= e($checkoutFormData['phone'] ?? '') ?>" autocomplete="tel" required>
                    </div>

                    <div class="field">
                        <label for="address">Delivery address</label>
                        <textarea id="address" name="address" rows="3" autocomplete="street-address" required><?= e($checkoutFormData['address'] ?? '') ?></textarea>
                    </div>

                    <div class="field">
                        <label for="notes">Special requests (optional)</label>
                        <textarea id="notes" name="notes" rows="3"><?= e($checkoutFormData['notes'] ?? '') ?></textarea>
                    </div>

                    <button class="button" type="submit" <?= empty($cart) ? 'disabled' : '' ?>>Place order <span aria-hidden="true">→</span></button>
                </form>
            </section>
        </div>
    </section>
</main>

<footer class="site-footer">
    <div class="container">
        <span>© <?= date('Y') ?> Adelaide Artisan Bakery</span>
        <div class="footer-contact">
            <a href="mailto:pateldevarsh1010@gmail.com">Email: pateldevarsh1010@gmail.com</a>
            <span>Call: 0493875729</span>
        </div>
        <a href="index.php?page=admin&amp;token=<?= rawurlencode($adminToken) ?>">Admin</a>
    </div>
</footer>
</body>
</html>
