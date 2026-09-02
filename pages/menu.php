<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Full Menu - Adelaide Artisan Bakery</title>
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
            <button class="filter-btn active" data-filter="all" aria-pressed="true">All Items</button>
            <button class="filter-btn" data-filter="Bread" aria-pressed="false">Bread</button>
            <button class="filter-btn" data-filter="Pastry" aria-pressed="false">Pastry</button>
            <button class="filter-btn" data-filter="Sweet" aria-pressed="false">Sweet</button>
            <button class="filter-btn" data-filter="Savoury" aria-pressed="false">Savoury</button>
        </div>

        <div class="product-grid">
            <?php 
            $products = $db->getAllProducts();
            foreach ($products as $product):
                $product = formatProduct($product);
            ?>
            <article class="product-card" data-category="<?= e($product['category']) ?>">
                <img src="<?= e($product['image_url']) ?>" alt="<?= e($product['name']) ?>" loading="lazy">
                <div class="product-info">
                    <div class="product-meta">
                        <span><?= e($product['category']) ?></span>
                        <strong>
                            <?php if ($product['is_on_offer']): ?>
                                $<?= $product['special_offer_price'] ?> <span class="regular-price">$<?= $product['price'] ?></span>
                            <?php else: ?>
                                $<?= $product['price'] ?>
                            <?php endif; ?>
                        </strong>
                    </div>
                    <h3><?= e($product['name']) ?></h3>
                    <p><?= e($product['description']) ?></p>
                    <form class="add-to-cart-form" action="index.php?page=cart-add" method="post">
                        <input type="hidden" name="csrf_token" value="<?= e($_SESSION['csrf_token']) ?>">
                        <input type="hidden" name="id" value="<?= $product['id'] ?>">
                        <input type="hidden" name="quantity" value="1">
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
    document.querySelectorAll('.filter-btn').forEach(b => {
      b.classList.remove('active');
      b.setAttribute('aria-pressed', 'false');
    });
    btn.classList.add('active');
    btn.setAttribute('aria-pressed', 'true');
  });
});
</script>
</body>
</html>
