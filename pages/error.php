<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($errorTitle ?? 'Error') ?> - Adelaide Artisan Bakery</title>
    <link rel="stylesheet" href="public/styles.css">
</head>
<body>
<header class="site-header">
    <div class="container nav-wrap">
        <a class="brand" href="index.php" aria-label="Adelaide Artisan Bakery home"><span class="brand-mark">AAB</span><span>Adelaide<br><em>Artisan Bakery</em></span></a>
    </div>
</header>

<main>
    <section class="container error-page">
        <h1><?= e($errorTitle ?? 'Something went wrong') ?></h1>
        <p><?= e($errorMessage ?? 'Please return to the homepage and try again.') ?></p>
        <a href="index.php" class="button">Back to home</a>
    </section>
</main>

<footer class="site-footer">
    <div class="container">
        <span>© <?= date('Y') ?> Adelaide Artisan Bakery</span>
    </div>
</footer>
</body>
</html>
