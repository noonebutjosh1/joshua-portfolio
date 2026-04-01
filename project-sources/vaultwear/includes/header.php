<?php
declare(strict_types=1);

$pageTitle = $pageTitle ?? APP_NAME;
$activePage = $activePage ?? "";
$flashSuccess = get_flash("success");
$flashError = get_flash("error");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($pageTitle); ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Bebas+Neue&family=Chakra+Petch:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= e(app_url("style.css")); ?>">
</head>
<body>
    <header class="site-header">
        <div class="container header-row">
            <a class="brand" href="<?= e(app_url("index.php")); ?>">Vaultwear</a>
            <nav class="site-nav">
                <a class="<?= $activePage === "home" ? "is-active" : ""; ?>" href="<?= e(app_url("index.php")); ?>">Home</a>
                <a class="<?= $activePage === "shop" ? "is-active" : ""; ?>" href="<?= e(app_url("shop.php")); ?>">Shop</a>
                <a class="<?= $activePage === "cart" ? "is-active" : ""; ?>" href="<?= e(app_url("cart.php")); ?>">Cart</a>
            </nav>
            <div class="header-actions">
                <a class="admin-link" href="<?= e(app_url("admin/login.php")); ?>">Admin</a>
                <a class="cart-pill" href="<?= e(app_url("cart.php")); ?>">
                    Cart
                    <span><?= e((string) cart_count()); ?></span>
                </a>
            </div>
        </div>
    </header>

    <?php if ($flashSuccess !== null): ?>
        <div class="flash flash-success">
            <div class="container"><?= e($flashSuccess); ?></div>
        </div>
    <?php endif; ?>

    <?php if ($flashError !== null): ?>
        <div class="flash flash-error">
            <div class="container"><?= e($flashError); ?></div>
        </div>
    <?php endif; ?>

    <main class="site-main">
