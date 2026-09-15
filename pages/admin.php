<?php
$adminUrl = 'index.php?page=admin';
$noticeMessages = [
    'added' => 'Product added successfully.',
    'updated' => 'Product details saved.',
    'deleted' => 'Product deleted successfully.',
    'offer' => 'Special offer updated successfully.',
    'password' => 'Admin password changed successfully.',
    'mfa-setup' => 'Scan the QR code, then verify the code shown in Microsoft Authenticator.',
    'mfa-enabled' => 'Microsoft Authenticator has been enabled.',
    'mfa-disabled' => 'Microsoft Authenticator has been disabled.',
];
$noticeKey = (string) ($_GET['notice'] ?? '');
$products = $db->getAllProducts();
$enquiries = $db->getEnquiries();
$activeOfferCount = count(array_filter($products, static function (array $product): bool {
    return !empty($product['special_offer_active']) && !empty($product['special_offer_price_cents']);
}));
$categoryCount = count(array_unique(array_column($products, 'category')));
$mfaEnabled = !empty($adminMfaConfig['enabled']) && !empty($adminMfaConfig['secret']);
$mfaSetupSecret = (string) ($_SESSION['mfa_setup_secret'] ?? '');
$mfaSetupUri = $mfaSetupSecret === '' ? '' : 'otpauth://totp/'
    . rawurlencode('Adelaide Artisan Bakery:Admin')
    . '?secret=' . rawurlencode($mfaSetupSecret)
    . '&issuer=' . rawurlencode('Adelaide Artisan Bakery')
    . '&algorithm=SHA1&digits=6&period=30';
$mfaRecoveryCodes = $_SESSION['mfa_recovery_codes'] ?? [];
unset($_SESSION['mfa_recovery_codes']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Admin Dashboard - Adelaide Artisan Bakery</title>
    <link rel="stylesheet" href="public/styles.css">
</head>
<body class="admin-shell-body">
<header class="admin-topbar">
    <a class="admin-brand" href="<?= e($adminUrl) ?>" aria-label="Adelaide Artisan Bakery dashboard">
        <span class="admin-brand-mark">AAB</span>
        <span><strong>Adelaide Artisan Bakery</strong><small>Administration</small></span>
    </a>
    <div class="admin-topbar-actions">
        <a class="admin-site-link" href="index.php" target="_blank" rel="noopener">View live website <span aria-hidden="true">↗</span></a>
        <form action="<?= e($adminUrl) ?>" method="post">
            <input type="hidden" name="action" value="admin-logout">
            <input type="hidden" name="csrf_token" value="<?= e($_SESSION['csrf_token']) ?>">
            <button class="admin-logout" type="submit">Log out</button>
        </form>
    </div>
</header>

<div class="admin-shell">
    <aside class="admin-sidebar" aria-label="Dashboard navigation">
        <p class="admin-nav-label">Workspace</p>
        <nav>
            <a class="active" href="#overview"><span aria-hidden="true">⌂</span> Overview</a>
            <a href="#add-product"><span aria-hidden="true">＋</span> Add product</a>
            <a href="#inventory"><span aria-hidden="true">▦</span> Inventory</a>
            <a href="#enquiries"><span aria-hidden="true">✉</span> Enquiries</a>
            <a href="#security"><span aria-hidden="true">◇</span> Security</a>
        </nav>
        <div class="admin-sidebar-note">
            <span class="status-dot" aria-hidden="true"></span>
            <div><strong>Website online</strong><small>Secure HTTPS connection</small></div>
        </div>
    </aside>

    <main class="admin-workspace">
        <section id="overview" class="admin-welcome" aria-labelledby="dashboard-title">
            <div>
                <p class="eyebrow">Bakery management</p>
                <h1 id="dashboard-title">Dashboard</h1>
                <p>Manage the catalogue, offers and customer messages from one place.</p>
            </div>
            <a class="button admin-primary-action" href="#add-product">Add new product</a>
        </section>

        <?php if (isset($noticeMessages[$noticeKey])): ?>
        <div class="notice success admin-notice" role="status" aria-live="polite"><?= e($noticeMessages[$noticeKey]) ?></div>
        <?php endif; ?>

        <?php if (!empty($adminErrors)): ?>
        <div class="notice error admin-notice" role="alert" aria-live="polite">
            <strong>The change was not saved:</strong>
            <ul>
                <?php foreach ($adminErrors as $error): ?>
                <li><?= e($error) ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
        <?php endif; ?>

        <section class="admin-stats" aria-label="Bakery summary">
            <article class="admin-stat-card">
                <span class="admin-stat-icon products" aria-hidden="true">▦</span>
                <div><strong><?= count($products) ?></strong><span>Total products</span></div>
            </article>
            <article class="admin-stat-card">
                <span class="admin-stat-icon offers" aria-hidden="true">%</span>
                <div><strong><?= $activeOfferCount ?></strong><span>Active offers</span></div>
            </article>
            <article class="admin-stat-card">
                <span class="admin-stat-icon messages" aria-hidden="true">✉</span>
                <div><strong><?= count($enquiries) ?></strong><span>Customer enquiries</span></div>
            </article>
            <article class="admin-stat-card">
                <span class="admin-stat-icon categories" aria-hidden="true">◆</span>
                <div><strong><?= $categoryCount ?></strong><span>Categories</span></div>
            </article>
        </section>

        <section id="add-product" class="admin-panel" aria-labelledby="add-product-title">
            <div class="admin-panel-heading">
                <div>
                    <p class="admin-kicker">Catalogue</p>
                    <h2 id="add-product-title">Add a new product</h2>
                    <p>Create a product and publish it to the bakery menu.</p>
                </div>
                <span class="admin-panel-badge">New listing</span>
            </div>

            <form class="admin-form admin-create-form" action="<?= e($adminUrl) ?>#add-product" method="post" novalidate>
                <input type="hidden" name="action" value="add-product">
                <input type="hidden" name="csrf_token" value="<?= e($_SESSION['csrf_token']) ?>">
                <div class="field admin-field-wide">
                    <label for="new-name">Product name</label>
                    <input id="new-name" name="name" type="text" value="<?= ($action === 'add-product') ? e($adminFormData['name'] ?? '') : '' ?>" placeholder="e.g. Almond Croissant" required>
                </div>
                <div class="field">
                    <label for="new-category">Category</label>
                    <select id="new-category" name="category" required>
                        <?php foreach ($categories as $category): ?>
                        <option <?= ($action === 'add-product' && ($adminFormData['category'] ?? '') === $category) ? 'selected' : '' ?>><?= e($category) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="field">
                    <label for="new-price">Price (AUD)</label>
                    <div class="currency-field"><span>$</span><input id="new-price" name="price" type="number" min="0.01" max="10000" step="0.01" value="<?= ($action === 'add-product') ? e($adminFormData['price'] ?? '') : '' ?>" placeholder="0.00" required></div>
                </div>
                <div class="field admin-field-wide">
                    <label for="new-description">Description</label>
                    <textarea id="new-description" name="description" rows="3" placeholder="Describe the ingredients, flavour and finish." required><?= ($action === 'add-product') ? e($adminFormData['description'] ?? '') : '' ?></textarea>
                </div>
                <div class="field admin-field-wide">
                    <label for="new-image">Product image URL</label>
                    <input id="new-image" name="imageUrl" type="url" value="<?= ($action === 'add-product') ? e($adminFormData['imageUrl'] ?? '') : '' ?>" placeholder="https://images.example.com/product.jpg" required>
                </div>
                <div class="admin-form-actions admin-field-wide">
                    <p>The product will appear on the public menu immediately.</p>
                    <button class="button" type="submit">Publish product</button>
                </div>
            </form>
        </section>

        <section id="inventory" class="admin-panel" aria-labelledby="products-title">
            <div class="admin-panel-heading">
                <div><p class="admin-kicker">Inventory</p><h2 id="products-title">Products</h2><p>Edit product information, control offers or remove a listing.</p></div>
                <span class="admin-panel-count"><?= count($products) ?> items</span>
            </div>
            <div class="admin-product-table">
                <?php foreach ($products as $rawProduct): ?>
                <?php $product = formatProduct($rawProduct); ?>
                <article class="admin-product-card">
                    <details class="admin-product-editor">
                        <summary class="admin-product-summary">
                            <img src="<?= e($product['image_url']) ?>" alt="" onerror="this.onerror=null;this.src='public/image-fallback.svg';">
                            <span class="admin-product-identity">
                                <span class="admin-product-tags"><span><?= e($product['category']) ?></span><?php if ($product['is_on_offer']): ?><span class="offer-tag">On offer</span><?php endif; ?></span>
                                <strong><?= e($product['name']) ?></strong><small>Product #<?= (int) $product['id'] ?></small>
                            </span>
                            <span class="admin-product-price"><?php if ($product['is_on_offer']): ?><small>$<?= e($product['price']) ?></small><?php endif; ?><strong>$<?= e($product['effective_price']) ?></strong></span>
                            <span class="admin-edit-trigger">Edit product</span>
                        </summary>
                            <div class="admin-editor-content">
                                <form class="admin-product" action="<?= e($adminUrl) ?>#inventory" method="post" novalidate>
                                    <input type="hidden" name="action" value="update-product"><input type="hidden" name="csrf_token" value="<?= e($_SESSION['csrf_token']) ?>"><input type="hidden" name="id" value="<?= (int) $product['id'] ?>">
                                    <div class="field-row"><div class="field"><label for="name-<?= (int) $product['id'] ?>">Product name</label><input id="name-<?= (int) $product['id'] ?>" name="name" type="text" value="<?= e($product['name']) ?>" required></div><div class="field"><label for="category-<?= (int) $product['id'] ?>">Category</label><select id="category-<?= (int) $product['id'] ?>" name="category" required><?php foreach ($categories as $category): ?><option <?= $product['category'] === $category ? 'selected' : '' ?>><?= e($category) ?></option><?php endforeach; ?></select></div></div>
                                    <div class="field-row"><div class="field"><label for="price-<?= (int) $product['id'] ?>">Regular price (AUD)</label><input id="price-<?= (int) $product['id'] ?>" name="price" type="number" min="0.01" max="10000" step="0.01" value="<?= e($product['price']) ?>" required></div><div class="field"><label for="image-<?= (int) $product['id'] ?>">Image URL</label><input id="image-<?= (int) $product['id'] ?>" name="imageUrl" type="url" value="<?= e($product['image_url']) ?>" required></div></div>
                                    <div class="field"><label for="description-<?= (int) $product['id'] ?>">Description</label><textarea id="description-<?= (int) $product['id'] ?>" name="description" rows="3" required><?= e($product['description']) ?></textarea></div>
                                    <button class="button button-small" type="submit">Save product</button>
                                </form>
                                <div class="admin-editor-divider"></div>
                                <form class="admin-special-offer" action="<?= e($adminUrl) ?>#inventory" method="post" novalidate>
                                    <input type="hidden" name="action" value="set-offer"><input type="hidden" name="csrf_token" value="<?= e($_SESSION['csrf_token']) ?>"><input type="hidden" name="id" value="<?= (int) $product['id'] ?>">
                                    <div><h4>Special offer</h4><p>Show a reduced price on the public menu.</p></div>
                                    <label class="checkbox-label"><input type="checkbox" name="offerActive" <?= $product['special_offer_active'] ? 'checked' : '' ?>> Offer active</label>
                                    <div class="field"><label for="offer-<?= (int) $product['id'] ?>">Offer price (AUD)</label><input id="offer-<?= (int) $product['id'] ?>" name="offerPrice" type="number" min="0.01" step="0.01" value="<?= e($product['special_offer_price'] ?? '') ?>" placeholder="0.00"></div>
                                    <button class="button button-small button-secondary" type="submit">Update offer</button>
                                </form>
                                <form class="delete-form" action="<?= e($adminUrl) ?>#inventory" method="post" onsubmit="return confirm('Delete this product? This cannot be undone.');">
                                    <input type="hidden" name="action" value="delete-product"><input type="hidden" name="csrf_token" value="<?= e($_SESSION['csrf_token']) ?>"><input type="hidden" name="id" value="<?= (int) $product['id'] ?>">
                                    <button class="admin-delete-button" type="submit">Delete this product</button>
                                </form>
                            </div>
                    </details>
                </article>
                <?php endforeach; ?>
            </div>
        </section>

        <section id="enquiries" class="admin-panel" aria-labelledby="enquiries-title">
            <div class="admin-panel-heading">
                <div><p class="admin-kicker">Inbox</p><h2 id="enquiries-title">Customer enquiries</h2><p>Review new order requests and customer messages.</p></div>
                <span class="admin-panel-count"><?= count($enquiries) ?> messages</span>
            </div>
            <div class="admin-enquiries">
                <?php if (empty($enquiries)): ?>
                <div class="admin-empty-state"><span aria-hidden="true">✉</span><h3>Inbox is clear</h3><p>New customer messages will appear here.</p></div>
                <?php else: foreach ($enquiries as $enquiry): ?>
                <article class="admin-enquiry-item">
                    <div class="admin-enquiry-avatar" aria-hidden="true"><?= e(strtoupper(substr($enquiry['customer_name'], 0, 1))) ?></div>
                    <div class="admin-enquiry-content"><div class="admin-enquiry-heading"><div><strong><?= e($enquiry['customer_name']) ?></strong><span><?= e($enquiry['enquiry_type']) ?></span></div><time datetime="<?= e($enquiry['created_at']) ?>"><?= e(date('j M Y, g:i a', strtotime($enquiry['created_at']))) ?></time></div><p><?= e($enquiry['message']) ?></p><div class="admin-enquiry-actions"><a href="mailto:<?= e($enquiry['email']) ?>">Reply by email</a><a href="tel:<?= e($enquiry['phone']) ?>"><?= e($enquiry['phone']) ?></a></div></div>
                </article>
                <?php endforeach; endif; ?>
            </div>
        </section>

        <section id="security" class="admin-panel admin-security-panel" aria-labelledby="security-title">
            <div class="admin-panel-heading">
                <div><p class="admin-kicker">Account security</p><h2 id="security-title">Security settings</h2><p>Protect access with a strong password and Microsoft Authenticator.</p></div>
                <span class="admin-security-icon" aria-hidden="true">◇</span>
            </div>

            <div class="admin-authenticator">
                <div class="admin-authenticator-heading">
                    <div><span class="admin-authenticator-logo" aria-hidden="true">M</span><div><h3>Microsoft Authenticator</h3><p>Require a six-digit verification code after the admin password.</p></div></div>
                    <span class="mfa-status <?= $mfaEnabled ? 'enabled' : '' ?>"><?= $mfaEnabled ? 'Enabled' : 'Not enabled' ?></span>
                </div>

                <?php if (!empty($mfaRecoveryCodes) && is_array($mfaRecoveryCodes)): ?>
                <div class="mfa-recovery-box" role="status">
                    <h4>Save your recovery codes now</h4>
                    <p>Each code works once. Keep them somewhere safe because they will not be shown again.</p>
                    <div class="mfa-recovery-grid">
                        <?php foreach ($mfaRecoveryCodes as $recoveryCode): ?><code><?= e($recoveryCode) ?></code><?php endforeach; ?>
                    </div>
                    <button class="button button-small button-secondary" type="button" onclick="window.print()">Print recovery codes</button>
                </div>
                <?php elseif ($mfaEnabled): ?>
                <div class="mfa-enabled-box">
                    <p><strong>Two-step verification is active.</strong> Every new admin login now requires the changing code from your Authenticator app.</p>
                    <details>
                        <summary>Disable Microsoft Authenticator</summary>
                        <form class="mfa-disable-form" action="<?= e($adminUrl) ?>#security" method="post">
                            <input type="hidden" name="action" value="mfa-disable">
                            <input type="hidden" name="csrf_token" value="<?= e($_SESSION['csrf_token']) ?>">
                            <div class="field"><label for="mfa-disable-password">Current password</label><input id="mfa-disable-password" name="currentPassword" type="password" autocomplete="current-password" required></div>
                            <div class="field"><label for="mfa-disable-code">Authenticator or recovery code</label><input id="mfa-disable-code" name="verificationCode" type="text" autocomplete="one-time-code" required></div>
                            <button class="admin-delete-button" type="submit">Disable two-step verification</button>
                        </form>
                    </details>
                </div>
                <?php elseif ($mfaSetupSecret !== ''): ?>
                <div class="mfa-setup-grid">
                    <div class="mfa-qr-panel">
                        <div id="mfa-qrcode" data-uri="<?= e($mfaSetupUri) ?>" aria-label="Microsoft Authenticator setup QR code"></div>
                        <p>Microsoft Authenticator → <strong>+</strong> → <strong>Other account</strong> → Scan QR code</p>
                    </div>
                    <div class="mfa-setup-steps">
                        <p class="mfa-step"><span>1</span> Scan the QR code using Microsoft Authenticator.</p>
                        <p class="mfa-step"><span>2</span> If scanning does not work, enter this setup key manually:</p>
                        <code class="mfa-secret"><?= e($mfaSetupSecret) ?></code>
                        <p class="mfa-step"><span>3</span> Enter the six-digit code shown in the app.</p>
                        <form action="<?= e($adminUrl) ?>#security" method="post">
                            <input type="hidden" name="action" value="mfa-enable">
                            <input type="hidden" name="csrf_token" value="<?= e($_SESSION['csrf_token']) ?>">
                            <div class="field"><label for="mfa-setup-code">Verification code</label><input id="mfa-setup-code" name="verificationCode" type="text" inputmode="numeric" autocomplete="one-time-code" pattern="[0-9]{6}" maxlength="6" placeholder="000000" required></div>
                            <button class="button button-small" type="submit">Verify and enable</button>
                        </form>
                        <form class="mfa-cancel-form" action="<?= e($adminUrl) ?>#security" method="post">
                            <input type="hidden" name="action" value="mfa-cancel"><input type="hidden" name="csrf_token" value="<?= e($_SESSION['csrf_token']) ?>">
                            <button class="text-button" type="submit">Cancel setup</button>
                        </form>
                    </div>
                </div>
                <?php else: ?>
                <form class="mfa-start-form" action="<?= e($adminUrl) ?>#security" method="post">
                    <input type="hidden" name="action" value="mfa-start"><input type="hidden" name="csrf_token" value="<?= e($_SESSION['csrf_token']) ?>">
                    <div><strong>Add an extra layer of security</strong><p>Confirm your password to generate a private setup QR code.</p></div>
                    <div class="field"><label for="mfa-start-password">Current password</label><input id="mfa-start-password" name="currentPassword" type="password" autocomplete="current-password" required></div>
                    <button class="button button-small" type="submit">Configure Authenticator</button>
                </form>
                <?php endif; ?>
            </div>

            <div class="admin-security-divider"></div>
            <div class="admin-password-heading"><h3>Change admin password</h3><p>Use a strong password that you do not use for another account.</p></div>
            <form class="admin-password-form" action="<?= e($adminUrl) ?>#security" method="post">
                <input type="hidden" name="action" value="change-password"><input type="hidden" name="csrf_token" value="<?= e($_SESSION['csrf_token']) ?>">
                <div class="field admin-field-wide"><label for="current-password">Current password</label><input id="current-password" name="currentPassword" type="password" autocomplete="current-password" required></div>
                <div class="field"><label for="new-password">New password</label><input id="new-password" name="newPassword" type="password" minlength="10" autocomplete="new-password" required></div>
                <div class="field"><label for="confirm-password">Confirm new password</label><input id="confirm-password" name="confirmPassword" type="password" minlength="10" autocomplete="new-password" required></div>
                <p class="admin-password-help admin-field-wide">At least 10 characters, including uppercase, lowercase, a number and a symbol.</p>
                <div class="admin-form-actions admin-field-wide"><p>You will use the new password the next time you log in.</p><button class="button" type="submit">Update password</button></div>
            </form>
        </section>

        <footer class="admin-footer"><span>© <?= date('Y') ?> Adelaide Artisan Bakery</span><span>Secure administration portal</span></footer>
    </main>
</div>
<?php if ($mfaSetupUri !== ''): ?>
<script src="public/qrcode.min.js"></script>
<script>
    const qrTarget = document.getElementById('mfa-qrcode');
    if (qrTarget && window.QRCode) {
        new QRCode(qrTarget, {
            text: qrTarget.dataset.uri,
            width: 190,
            height: 190,
            colorDark: '#17212b',
            colorLight: '#ffffff',
            correctLevel: QRCode.CorrectLevel.M
        });
    }
</script>
<?php endif; ?>
</body>
</html>
