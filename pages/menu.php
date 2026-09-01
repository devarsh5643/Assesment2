<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Full Menu - Adelaide Artisan Bakery</title>
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
            <a href="index.php">Home</a>
            <a href="index.php?page=checkout" class="button button-small">View Cart</a>
        </nav>
    </div>
</header>

<main>
    <section class="container menu-section">
        <div class="section-heading">
            <div>
                <p class="eyebrow">Browse all items</p>
                <h1>Full Menu</h1>
            </div>
            <p class="section-intro">Discover our complete selection of freshly baked goods, from hearty breads to delicate pastries and sweet treats.</p>
        </div>

        <div class="menu-filters">
            <button class="filter-btn active" data-filter="all">All Items</button>
            <button class="filter-btn" data-filter="Bread">Bread</button>
            <button class="filter-btn" data-filter="Pastry">Pastry</button>
            <button class="filter-btn" data-filter="Sweet">Sweet</button>
            <button class="filter-btn" data-filter="Savoury">Savoury</button>
        </div>

        <div class="product-grid">
            <?php 
            $products = $db->getAllProducts();
            foreach ($products as $product):
                $product = formatProduct($product);
            ?>
            <article class="product-card" data-category="<?= htmlspecialchars($product['category']) ?>">
                <img src="<?= htmlspecialchars($product['image_url']) ?>" alt="<?= htmlspecialchars($product['name']) ?>" loading="lazy">
                <div class="product-info">
                    <div class="product-meta">
                        <span><?= htmlspecialchars($product['category']) ?></span>
                        <strong>
                            <?php if ($product['is_on_offer']): ?>
                                $<?= $product['special_offer_price'] ?> <span style="text-decoration: line-through; color: var(--muted); font-size: 0.9em;">$<?= $product['price'] ?></span>
                            <?php else: ?>
                                $<?= $product['price'] ?>
                            <?php endif; ?>
                        </strong>
                    </div>
                    <h3><?= htmlspecialchars($product['name']) ?></h3>
                    <p><?= htmlspecialchars($product['description']) ?></p>
                    <form class="add-to-cart-form" action="index.php" method="get" style="margin-top: 12px;">
                        <input type="hidden" name="page" value="cart-add">
                        <input type="hidden" name="id" value="<?= $product['id'] ?>">
                        <button class="button button-small" type="submit">Add to cart <span aria-hidden="true">→</span></button>
                    </form>
                </div>
            </article>
            <?php endforeach; ?>
        </div>
    </section>
</main>

<footer class="site-footer">
    <div class="container">
        <span>© <?= date('Y') ?> Adelaide Artisan Bakery</span>
        <div class="footer-contact">
            <a href="mailto:pateldevarsh1010@gmail.com">Email: pateldevarsh1010@gmail.com</a> · <span>Call: 0493875729</span>
        </div>
        <a href="index.php">Back to site</a>
    </div>
</footer>

<script>
document.querySelectorAll('.filter-btn').forEach(btn => {
  btn.addEventListener('click', () => {
    const filter = btn.dataset.filter;
    document.querySelectorAll('.product-card').forEach(card => {
      if (filter === 'all' || card.dataset.category === filter) {
        card.style.display = '';
      } else {
        card.style.display = 'none';
      }
    });
    document.querySelectorAll('.filter-btn').forEach(b => b.classList.remove('active'));
    btn.classList.add('active');
  });
});
</script>
</body>
</html>
