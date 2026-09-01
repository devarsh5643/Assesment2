<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Error - Adelaide Artisan Bakery</title>
    <link rel="stylesheet" href="public/styles.php">
</head>
<body>
<header class="site-header">
    <div class="container nav-wrap">
        <a class="brand" href="index.php">Adelaide <em>Artisan Bakery</em></a>
    </div>
</header>

<main>
    <section class="container" style="padding: 80px 0; text-align: center;">
        <h1>Admin access required</h1>
        <p style="font-size: 18px; color: var(--muted); margin: 20px 0;">Use the configured admin token to access this page.</p>
        <a href="index.php" class="button" style="margin-top: 20px;">Back to home</a>
    </section>
</main>

<footer class="site-footer">
    <div class="container">
        <span>© <?= date('Y') ?> Adelaide Artisan Bakery</span>
    </div>
</footer>
</body>
</html>
