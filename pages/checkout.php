<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Checkout - Adelaide Artisan Bakery</title>
    <link rel="stylesheet" href="public/styles.php">
</head>
<body>
<header class="site-header">
    <div class="container nav-wrap">
        <a class="brand" href="index.php" aria-label="Adelaide Artisan Bakery home">
            <span class="brand-mark">AAB</span>
            <span>Adelaide<br><em>Artisan Bakery</em></span>
        </a>
        <nav aria-label="Primary navigation">
            <a href="index.php?page=menu">Back to menu</a>
        </nav>
    </div>
</header>

<main>
    <section class="container checkout-section">
        <div class="section-heading">
            <div>
                <p class="eyebrow">Your order</p>
                <h1>Checkout</h1>
            </div>
        </div>

        <div class="checkout-container">
            <div class="checkout-items">
                <?php 
                $cart = $_SESSION['cart'] ?? [];
                $total = 0;
                
                if (!empty($cart)): 
                    foreach ($cart as $item) {
                        $total += $item['price'] * $item['quantity'];
                    }
                ?>
                <h2>Order summary</h2>
                <table class="cart-table">
                    <thead>
                        <tr>
                            <th>Product</th>
                            <th>Quantity</th>
                            <th>Price</th>
                            <th>Total</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($cart as $item): ?>
                        <tr>
                            <td><strong><?= htmlspecialchars($item['name']) ?></strong><br><small><?= htmlspecialchars($item['category']) ?></small></td>
                            <td><?= intval($item['quantity']) ?></td>
                            <td>$<?= $item['price'] ?></td>
                            <td>$<?= number_format($item['price'] * $item['quantity'], 2) ?></td>
                            <td><a href="index.php?page=cart-remove&id=<?= $item['id'] ?>" class="text-button danger">Remove</a></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                <div class="cart-summary">
                    <h3>Total: <strong>$<?= number_format($total, 2) ?></strong></h3>
                </div>
                <?php else: ?>
                <p class="empty-cart">Your cart is empty. <a href="index.php?page=menu">Continue shopping</a></p>
                <?php endif; ?>
            </div>

            <?php if (!empty($cart)): ?>
            <div class="checkout-form">
                <h2>Delivery details</h2>
                <form action="index.php" method="post" novalidate>
                    <div class="field-row">
                        <div class="field">
                            <label for="name">Full name</label>
                            <input id="name" name="name" required>
                        </div>
                        <div class="field">
                            <label for="email">Email</label>
                            <input id="email" name="email" type="email" required>
                        </div>
                    </div>
                    <div class="field-row">
                        <div class="field">
                            <label for="phone">Phone</label>
                            <input id="phone" name="phone" type="tel" required>
                        </div>
                        <div class="field">
                            <label for="address">Delivery address</label>
                            <input id="address" name="address" required>
                        </div>
                    </div>
                    <div class="field">
                        <label for="notes">Special requests or dietary notes</label>
                        <textarea id="notes" name="notes" rows="3"></textarea>
                    </div>
                    <div class="field-row">
                        <input type="hidden" name="page" value="enquiries">
                        <button class="button" type="submit">Place order <span aria-hidden="true">→</span></button>
                        <a href="index.php?page=menu" class="button button-secondary">Continue shopping</a>
                    </div>
                </form>
            </div>
            <?php endif; ?>
        </div>
    </section>
</main>

<footer class="site-footer">
    <div class="container">
        <span>© <?= date('Y') ?> Adelaide Artisan Bakery</span>
        <div class="footer-contact">
            <a href="mailto:pateldevarsh1010@gmail.com">Email: pateldevarsh1010@gmail.com</a> · <span>Call: 0493875729</span>
        </div>
    </div>
</footer>
</body>
</html>
