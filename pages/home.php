<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Adelaide Artisan Bakery</title>
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
            <a href="index.php?page=checkout" class="button button-small">Cart</a>
            <a class="button button-small" href="#enquire">Enquire</a>
        </nav>
    </div>
</header>

<main>
    <section class="hero">
        <div class="container hero-grid">
            <div>
                <p class="eyebrow">Hand-shaped in Adelaide, South Australia</p>
                <h1>Good bread,<br><span>made slowly.</span></h1>
                <p class="hero-copy">Naturally leavened loaves, laminated pastries and seasonal sweets made by hand every morning.</p>
                <a class="button" href="#bakes">Explore the counter <span aria-hidden="true">↓</span></a>
            </div>
            <div class="hero-note">
                <span class="stamp">BAKED<br>FRESH</span>
                <p>Open Tuesday–Saturday<br><strong>7:00 am until sold out</strong></p>
            </div>
        </div>
    </section>

    <section id="bakes" class="catalogue container" aria-labelledby="catalogue-title">
        <div class="section-heading">
            <div>
                <p class="eyebrow">From our ovens</p>
                <h2 id="catalogue-title">Featured items today</h2>
            </div>
            <p class="section-intro">A curated selection of our most popular items. <a class="emphasis-link" href="index.php?page=menu">View the full menu →</a></p>
        </div>
        <div class="product-grid">
            <?php 
            $products = $db->getFeaturedProducts(5);
            foreach ($products as $product):
                $product = formatProduct($product);
            ?>
            <article class="product-card">
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

    <section id="enquire" class="enquiry-band" aria-labelledby="enquiry-title">
        <div class="container enquiry-grid">
            <div>
                <p class="eyebrow">Planning something special?</p>
                <h2 id="enquiry-title">Let's talk<br>about your order.</h2>
                <p>Tell us what you have in mind. We'll reply within two business days.</p>
                <p class="contact-detail">
                    <strong>Visit the bakery</strong><br>
                    14 Market Lane, Adelaide SA 5000<br>
                    (08) 8123 4567
                </p>
            </div>
            <form class="enquiry-form" action="index.php?page=enquiries" method="post" novalidate>
                <?php if (isset($_GET['sent']) && $_GET['sent'] === '1'): ?>
                <div class="notice success" role="status" aria-live="polite">Thanks. Your enquiry is on its way to the bakery.</div>
                <?php endif; ?>
                <?php if (isset($_GET['ordered']) && $_GET['ordered'] === '1'): ?>
                <div class="notice success" role="status" aria-live="polite">Thanks. Your order has been saved for the bakery.</div>
                <?php endif; ?>
                <?php if (!empty($enquiryErrors)): ?>
                <div class="notice error" role="alert" aria-live="polite">
                    <strong>Please check the following:</strong>
                    <ul>
                        <?php foreach ($enquiryErrors as $error): ?>
                        <li><?= e($error) ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
                <?php endif; ?>
                <div class="field-row">
                    <div class="field">
                        <label for="customerName">Your name</label>
                        <input id="customerName" name="customerName" type="text" value="<?= e($enquiryFormData['customerName'] ?? '') ?>" autocomplete="name" required>
                    </div>
                    <div class="field">
                        <label for="email">Email address</label>
                        <input id="email" name="email" type="email" value="<?= e($enquiryFormData['email'] ?? '') ?>" autocomplete="email" required>
                    </div>
                </div>
                <div class="field-row">
                    <div class="field">
                        <label for="phone">Phone</label>
                        <input id="phone" name="phone" type="tel" value="<?= e($enquiryFormData['phone'] ?? '') ?>" autocomplete="tel" required>
                    </div>
                    <div class="field">
                        <label for="enquiryType">I'm enquiring about</label>
                        <select id="enquiryType" name="enquiryType" required>
                            <option value="">Choose one</option>
                            <?php foreach ($enquiryTypes as $type): ?>
                            <option <?= ($enquiryFormData['enquiryType'] ?? '') === $type ? 'selected' : '' ?>><?= e($type) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div class="field">
                    <label for="message">Your message</label>
                    <textarea id="message" name="message" rows="5" required><?= e($enquiryFormData['message'] ?? '') ?></textarea>
                </div>
                <input type="hidden" name="csrf_token" value="<?= e($_SESSION['csrf_token']) ?>">
                <button class="button" type="submit">Send enquiry <span aria-hidden="true">→</span></button>
            </form>
        </div>
    </section>
</main>

<footer class="site-footer">
    <div class="container">
        <span>© <?= date('Y') ?> Adelaide Artisan Bakery</span>
        <div class="footer-contact">
            <a href="mailto:pateldevarsh1010@gmail.com">Email: pateldevarsh1010@gmail.com</a> · <span>Call: 0493875729</span>
        </div>
        <a href="index.php?page=admin&amp;token=<?= rawurlencode($adminToken) ?>">Admin</a>
    </div>
</footer>
</body>
</html>
