<?php
declare(strict_types=1);

$pageTitle = $pageTitle ?? "Admin | " . APP_NAME;
$activeAdminPage = $activeAdminPage ?? "";
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
    <link href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;600;700;800&family=Orbitron:wght@600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= e(app_url("admin/admin.css")); ?>">
</head>
<body class="admin-body">
    <header class="admin-header">
        <div class="admin-header__inner">
            <a class="admin-brand" href="<?= e(app_url("admin/dashboard.php")); ?>">Vaultwear Control</a>
            <nav class="admin-nav">
                <a class="<?= $activeAdminPage === "dashboard" ? "is-active" : ""; ?>" href="<?= e(app_url("admin/dashboard.php")); ?>">Dashboard</a>
                <a class="<?= $activeAdminPage === "products" ? "is-active" : ""; ?>" href="<?= e(app_url("admin/products.php")); ?>">Products</a>
                <a class="<?= $activeAdminPage === "orders" ? "is-active" : ""; ?>" href="<?= e(app_url("admin/orders.php")); ?>">Orders</a>
                <a href="<?= e(app_url("index.php")); ?>">View Store</a>
            </nav>
            <div class="admin-session">
                <span><?= e(admin_display_name()); ?></span>
                <a href="<?= e(app_url("admin/logout.php")); ?>">Logout</a>
            </div>
        </div>
    </header>

    <?php if ($flashSuccess !== null): ?>
        <div class="admin-flash admin-flash--success"><?= e($flashSuccess); ?></div>
    <?php endif; ?>
    <?php if ($flashError !== null): ?>
        <div class="admin-flash admin-flash--error"><?= e($flashError); ?></div>
    <?php endif; ?>

    <main class="admin-main">
