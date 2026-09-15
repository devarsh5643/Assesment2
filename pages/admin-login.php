<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Admin login - Adelaide Artisan Bakery</title>
    <link rel="stylesheet" href="public/styles.css">
</head>
<body>
<header class="site-header">
    <div class="container nav-wrap">
        <a class="brand" href="index.php" aria-label="Adelaide Artisan Bakery home">
            <span class="brand-mark">AAB</span>
            <span>Adelaide<br><em>Artisan Bakery</em></span>
        </a>
        <a href="index.php">Back to public site</a>
    </div>
</header>

<main class="admin-login-page container">
    <section class="admin-login-card" aria-labelledby="admin-login-title">
        <p class="eyebrow">Protected area</p>
        <?php if (!empty($_SESSION['admin_mfa_pending'])): ?>
        <h1 id="admin-login-title">Verification code</h1>
        <p>Enter the current code from Microsoft Authenticator to finish signing in.</p>
        <?php else: ?>
        <h1 id="admin-login-title">Admin login</h1>
        <p>Enter the administrator password to manage products, offers and enquiries.</p>
        <?php endif; ?>

        <?php if ($adminLoginError !== ''): ?>
        <div class="notice error" role="alert"><?= e($adminLoginError) ?></div>
        <?php endif; ?>

        <?php if (!empty($_SESSION['admin_mfa_pending'])): ?>
        <form action="index.php?page=admin" method="post">
            <input type="hidden" name="action" value="admin-mfa-login">
            <input type="hidden" name="csrf_token" value="<?= e($_SESSION['csrf_token']) ?>">

            <label for="admin-verification-code">Authenticator or recovery code</label>
            <input id="admin-verification-code" name="verificationCode" type="text" autocomplete="one-time-code" placeholder="000000 or recovery code" required autofocus>
            <button class="button" type="submit">Verify and sign in</button>
        </form>
        <form class="admin-login-back" action="index.php?page=admin" method="post">
            <input type="hidden" name="action" value="admin-mfa-back">
            <input type="hidden" name="csrf_token" value="<?= e($_SESSION['csrf_token']) ?>">
            <button class="text-button" type="submit">Use password instead</button>
        </form>
        <?php else: ?>
        <form action="index.php?page=admin" method="post">
            <input type="hidden" name="action" value="admin-login">
            <input type="hidden" name="csrf_token" value="<?= e($_SESSION['csrf_token']) ?>">

            <label for="admin-password">Password</label>
            <input id="admin-password" name="password" type="password" autocomplete="current-password" required autofocus>
            <button class="button" type="submit">Log in</button>
        </form>
        <?php endif; ?>
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
