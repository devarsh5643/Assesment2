<?php
$adminUrl = 'index.php?page=admin&token=' . rawurlencode($adminToken);
$noticeMessages = [
    'added' => 'Product added successfully.',
    'updated' => 'Product updated successfully.',
    'deleted' => 'Product deleted successfully.',
    'offer' => 'Special offer updated successfully.',
];
$noticeKey = (string) ($_GET['notice'] ?? '');
$products = $db->getAllProducts();
$enquiries = $db->getEnquiries();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Admin - Adelaide Artisan Bakery</title>
    <link rel="stylesheet" href="public/styles.css">
</head>
<body>
<header class="site-header">
    <div class="container nav-wrap">
        <a class="brand" href="index.php" aria-label="Adelaide Artisan Bakery home">
            <span class="brand-mark">AAB</span>
            <span>Adelaide<br><em>Artisan Bakery</em></span>
        </a>
        <a href="index.php">View public site</a>
    </div>
</header>

<main class="admin-page container">
    <div class="section-heading">
        <div>
            <p class="eyebrow">Private workspace</p>
            <h1>Bakery admin</h1>
        </div>
        <p class="section-intro">Create, view, update and delete products, manage special offers, and review customer enquiries.</p>
    </div>

    <?php if (isset($noticeMessages[$noticeKey])): ?>
    <div class="notice success" role="status" aria-live="polite"><?= e($noticeMessages[$noticeKey]) ?></div>
    <?php endif; ?>

    <?php if (!empty($adminErrors)): ?>
    <div class="notice error" role="alert" aria-live="polite">
        <strong>The change was not saved:</strong>
        <ul>
            <?php foreach ($adminErrors as $error): ?>
            <li><?= e($error) ?></li>
            <?php endforeach; ?>
        </ul>
    </div>
    <?php endif; ?>

    <section class="admin-section" aria-labelledby="add-product-title">
        <h2 id="add-product-title">Add a product</h2>
        <form class="admin-form" action="<?= e($adminUrl) ?>" method="post" novalidate>
            <input type="hidden" name="action" value="add-product">
            <input type="hidden" name="adminToken" value="<?= e($adminToken) ?>">
            <input type="hidden" name="csrf_token" value="<?= e($_SESSION['csrf_token']) ?>">

            <label for="new-name">Name</label>
            <input id="new-name" name="name" type="text" value="<?= ($action === 'add-product') ? e($adminFormData['name'] ?? '') : '' ?>" required>

            <label for="new-description">Description</label>
            <textarea id="new-description" name="description" rows="3" required><?= ($action === 'add-product') ? e($adminFormData['description'] ?? '') : '' ?></textarea>

            <div class="field-row">
                <div class="field">
                    <label for="new-price">Price (AUD)</label>
                    <input id="new-price" name="price" type="number" min="0.01" max="10000" step="0.01" value="<?= ($action === 'add-product') ? e($adminFormData['price'] ?? '') : '' ?>" required>
                </div>
                <div class="field">
                    <label for="new-category">Category</label>
                    <select id="new-category" name="category" required>
                        <?php foreach ($categories as $category): ?>
                        <option <?= ($action === 'add-product' && ($adminFormData['category'] ?? '') === $category) ? 'selected' : '' ?>><?= e($category) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <label for="new-image">Image URL</label>
            <input id="new-image" name="imageUrl" type="url" value="<?= ($action === 'add-product') ? e($adminFormData['imageUrl'] ?? '') : '' ?>" required>
            <button class="button" type="submit">Add product</button>
        </form>
    </section>

    <section class="admin-section" aria-labelledby="products-title">
        <h2 id="products-title">Products and offers <span class="count"><?= count($products) ?></span></h2>
        <div class="admin-list">
            <?php foreach ($products as $rawProduct): ?>
            <?php $product = formatProduct($rawProduct); ?>
            <article class="admin-product-card">
                <div class="admin-product-head">
                    <strong>#<?= (int) $product['id'] ?> · <?= e($product['name']) ?></strong>
                </div>

                <form class="admin-product" action="<?= e($adminUrl) ?>" method="post" novalidate>
                    <input type="hidden" name="action" value="update-product">
                    <input type="hidden" name="adminToken" value="<?= e($adminToken) ?>">
                    <input type="hidden" name="csrf_token" value="<?= e($_SESSION['csrf_token']) ?>">
                    <input type="hidden" name="id" value="<?= (int) $product['id'] ?>">

                    <div class="field-row">
                        <div class="field">
                            <label for="name-<?= (int) $product['id'] ?>">Name</label>
                            <input id="name-<?= (int) $product['id'] ?>" name="name" type="text" value="<?= e($product['name']) ?>" required>
                        </div>
                        <div class="field">
                            <label for="category-<?= (int) $product['id'] ?>">Category</label>
                            <select id="category-<?= (int) $product['id'] ?>" name="category" required>
                                <?php foreach ($categories as $category): ?>
                                <option <?= $product['category'] === $category ? 'selected' : '' ?>><?= e($category) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>

                    <div class="field">
                        <label for="price-<?= (int) $product['id'] ?>">Price (AUD)</label>
                        <input id="price-<?= (int) $product['id'] ?>" name="price" type="number" min="0.01" max="10000" step="0.01" value="<?= e($product['price']) ?>" required>
                    </div>

                    <div class="field">
                        <label for="description-<?= (int) $product['id'] ?>">Description</label>
                        <textarea id="description-<?= (int) $product['id'] ?>" name="description" rows="2" required><?= e($product['description']) ?></textarea>
                    </div>

                    <div class="field">
                        <label for="image-<?= (int) $product['id'] ?>">Image URL</label>
                        <input id="image-<?= (int) $product['id'] ?>" name="imageUrl" type="url" value="<?= e($product['image_url']) ?>" required>
                    </div>

                    <button class="button button-small" type="submit">Save changes</button>
                </form>

                <form class="admin-special-offer" action="<?= e($adminUrl) ?>" method="post" novalidate>
                    <input type="hidden" name="action" value="set-offer">
                    <input type="hidden" name="adminToken" value="<?= e($adminToken) ?>">
                    <input type="hidden" name="csrf_token" value="<?= e($_SESSION['csrf_token']) ?>">
                    <input type="hidden" name="id" value="<?= (int) $product['id'] ?>">

                    <h3>Special offer</h3>
                    <div class="field-row">
                        <label class="checkbox-label">
                            <input type="checkbox" name="offerActive" <?= $product['special_offer_active'] ? 'checked' : '' ?>>
                            Activate special offer
                        </label>
                        <div class="field">
                            <label for="offer-<?= (int) $product['id'] ?>">Offer price (AUD)</label>
                            <input id="offer-<?= (int) $product['id'] ?>" name="offerPrice" type="number" min="0.01" step="0.01" value="<?= e($product['special_offer_price'] ?? '') ?>">
                        </div>
                    </div>
                    <button class="button button-small" type="submit">Update offer</button>
                </form>

                <form class="delete-form" action="<?= e($adminUrl) ?>" method="post" onsubmit="return confirm('Delete this product?');">
                    <input type="hidden" name="action" value="delete-product">
                    <input type="hidden" name="adminToken" value="<?= e($adminToken) ?>">
                    <input type="hidden" name="csrf_token" value="<?= e($_SESSION['csrf_token']) ?>">
                    <input type="hidden" name="id" value="<?= (int) $product['id'] ?>">
                    <button class="text-button danger" type="submit">Delete product</button>
                </form>
            </article>
            <?php endforeach; ?>
        </div>
    </section>

    <section class="admin-section" aria-labelledby="enquiries-title">
        <h2 id="enquiries-title">Enquiries <span class="count"><?= count($enquiries) ?></span></h2>
        <div class="enquiries">
            <?php if (empty($enquiries)): ?>
            <p>No enquiries yet.</p>
            <?php else: ?>
                <?php foreach ($enquiries as $enquiry): ?>
                <article class="enquiry-item">
                    <strong><?= e($enquiry['customer_name']) ?></strong>
                    <p class="meta"><?= e($enquiry['enquiry_type']) ?> · <a href="mailto:<?= e($enquiry['email']) ?>"><?= e($enquiry['email']) ?></a> · <?= e($enquiry['phone']) ?></p>
                    <p><?= e($enquiry['message']) ?></p>
                    <p class="meta"><?= e(date('j M Y, g:i a', strtotime($enquiry['created_at']))) ?></p>
                </article>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </section>
</main>

<footer class="site-footer">
    <div class="container">
        <span>© <?= date('Y') ?> Adelaide Artisan Bakery</span>
        <a href="index.php">Back to site</a>
    </div>
</footer>
</body>
</html>
