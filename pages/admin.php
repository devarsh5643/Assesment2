<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Admin - Adelaide Artisan Bakery</title>
    <link rel="stylesheet" href="public/styles.php">
</head>
<body>
<header class="site-header">
    <div class="container nav-wrap">
        <a class="brand" href="index.php">Adelaide <em>Artisan Bakery</em></a>
        <a href="index.php">View public site</a>
    </div>
</header>

<main class="admin-page container">
    <div class="section-heading">
        <div>
            <p class="eyebrow">Private workspace</p>
            <h1>Bakery admin</h1>
        </div>
        <p class="section-intro">Manage products, set special offers, and review incoming enquiries.</p>
    </div>

    <section class="admin-section">
        <h2>Add a product</h2>
        <form class="admin-form" action="index.php" method="post">
            <input type="hidden" name="action" value="add-product">
            <input type="hidden" name="adminToken" value="<?= htmlspecialchars($adminToken) ?>">
            <label for="new-name">Name</label>
            <input id="new-name" name="name" required>
            <label for="new-description">Description</label>
            <textarea id="new-description" name="description" rows="3" required></textarea>
            <div class="field-row">
                <div>
                    <label for="new-price">Price (AUD)</label>
                    <input id="new-price" name="price" type="number" min="0" step="0.01" required>
                </div>
                <div>
                    <label for="new-category">Category</label>
                    <select id="new-category" name="category" required>
                        <?php foreach ($categories as $cat): ?>
                        <option><?= htmlspecialchars($cat) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            <label for="new-image">Image URL</label>
            <input id="new-image" name="imageUrl" type="url" required>
            <button class="button" type="submit">Add product</button>
        </form>
    </section>

    <section class="admin-section">
        <h2>Products & Special Offers <span class="count"><?= count($db->getAllProducts()) ?></span></h2>
        <div class="admin-list">
            <?php 
            $products = $db->getAllProducts();
            foreach ($products as $product):
                $product = formatProduct($product);
            ?>
            <div class="admin-product-card">
                <form class="admin-product" action="index.php" method="post">
                    <input type="hidden" name="action" value="update-product">
                    <input type="hidden" name="adminToken" value="<?= htmlspecialchars($adminToken) ?>">
                    <input type="hidden" name="id" value="<?= $product['id'] ?>">
                    
                    <div class="admin-product-head">
                        <strong>#<?= $product['id'] ?> · <?= htmlspecialchars($product['name']) ?></strong>
                        <button class="text-button danger" formaction="index.php" onclick="return confirm('Delete this product?');">Delete</button>
                    </div>

                    <div class="field-row">
                        <div>
                            <label>Name</label>
                            <input name="name" value="<?= htmlspecialchars($product['name']) ?>" required>
                        </div>
                        <div>
                            <label>Category</label>
                            <select name="category" required>
                                <?php foreach ($categories as $cat): ?>
                                <option <?= $product['category'] === $cat ? 'selected' : '' ?>><?= htmlspecialchars($cat) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>

                    <div class="field-row">
                        <div>
                            <label>Price (AUD)</label>
                            <input name="price" type="number" min="0" step="0.01" value="<?= $product['price'] ?>" required>
                        </div>
                    </div>

                    <label>Description</label>
                    <textarea name="description" rows="2" required><?= htmlspecialchars($product['description']) ?></textarea>

                    <label>Image URL</label>
                    <input name="imageUrl" type="url" value="<?= htmlspecialchars($product['image_url']) ?>" required>

                    <button class="button button-small" type="submit">Update product</button>
                </form>

                <form class="admin-special-offer" action="index.php" method="post">
                    <input type="hidden" name="action" value="set-offer">
                    <input type="hidden" name="adminToken" value="<?= htmlspecialchars($adminToken) ?>">
                    <input type="hidden" name="id" value="<?= $product['id'] ?>">
                    
                    <div class="offer-section">
                        <h4>Special Offer</h4>
                        <div class="field-row">
                            <div>
                                <label>
                                    <input type="checkbox" name="offerActive" <?= $product['special_offer_active'] ? 'checked' : '' ?>>
                                    Activate special offer
                                </label>
                            </div>
                            <div>
                                <label>Offer Price (AUD)</label>
                                <input name="offerPrice" type="number" min="0" step="0.01" value="<?= $product['special_offer_price'] ?? '' ?>" placeholder="Leave blank to remove">
                            </div>
                        </div>
                        <button class="button button-small" type="submit">Set Special Offer</button>
                    </div>
                </form>
            </div>
            <?php endforeach; ?>
        </div>
    </section>

    <section class="admin-section">
        <h2>Enquiries <span class="count"><?= count($db->getEnquiries()) ?></span></h2>
        <div class="enquiries-list">
            <?php 
            $enquiries = $db->getEnquiries();
            if (empty($enquiries)):
            ?>
            <p>No enquiries yet.</p>
            <?php 
            else:
                foreach ($enquiries as $enquiry):
            ?>
            <div class="enquiry-item">
                <div class="enquiry-head">
                    <strong><?= htmlspecialchars($enquiry['customer_name']) ?></strong>
                    <span class="enquiry-type"><?= htmlspecialchars($enquiry['enquiry_type']) ?></span>
                </div>
                <div class="enquiry-details">
                    <p><strong>Email:</strong> <a href="mailto:<?= htmlspecialchars($enquiry['email']) ?>"><?= htmlspecialchars($enquiry['email']) ?></a></p>
                    <p><strong>Phone:</strong> <?= htmlspecialchars($enquiry['phone']) ?></p>
                    <p><strong>Message:</strong> <?= htmlspecialchars($enquiry['message']) ?></p>
                    <p class="enquiry-date"><?= date('M d, Y', strtotime($enquiry['created_at'])) ?></p>
                </div>
            </div>
            <?php 
                endforeach;
            endif;
            ?>
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
