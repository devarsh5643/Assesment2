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
        <h1 id="admin-login-title">Admin login</h1>
        <p>Enter the administrator password to manage products, offers and enquiries.</p>

        <?php if ($adminLoginError !== ''): ?>
        <div class="notice error" role="alert"><?= e($adminLoginError) ?></div>
        <?php endif; ?>

        <form action="index.php?page=admin" method="post">
            <input type="hidden" name="action" value="admin-login">
            <input type="hidden" name="csrf_token" value="<?= e($_SESSION['csrf_token']) ?>">

            <label for="admin-password">Password</label>
            <input id="admin-password" name="password" type="password" autocomplete="current-password" required autofocus>
            <button class="button" type="submit">Log in</button>
        </form>
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
