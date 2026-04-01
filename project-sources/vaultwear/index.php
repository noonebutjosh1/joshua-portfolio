<?php
declare(strict_types=1);

require_once __DIR__ . "/includes/bootstrap.php";

$pdo = db();

$featuredStatement = $pdo->query(
    "SELECT id, name, slug, price, image_url, category, stock
     FROM products
     WHERE is_featured = 1
     ORDER BY created_at DESC
     LIMIT 8"
);
$featuredProducts = $featuredStatement->fetchAll();

if ($featuredProducts === []) {
    $fallbackStatement = $pdo->query(
        "SELECT id, name, slug, price, image_url, category, stock
         FROM products
         ORDER BY created_at DESC
         LIMIT 8"
    );
    $featuredProducts = $fallbackStatement->fetchAll();
}

$pageTitle = "Vaultwear | Dark Streetwear Ecommerce";
$activePage = "home";
include __DIR__ . "/includes/header.php";
?>

<section class="hero">
    <div class="container hero-grid">
        <div class="hero-copy">
            <p class="hero-kicker">Welcome to Vaultwear</p>
            <h1>Own The Night. Wear The Drop.</h1>
            <p>
                Vaultwear is your dark-mode streetwear marketplace, built for fast product discovery,
                smooth checkout, and a clean admin workflow behind the scenes.
            </p>
            <div class="hero-actions">
                <a class="btn btn-primary" href="<?= e(app_url("shop.php")); ?>">Start Shopping</a>
                <a class="btn btn-secondary" href="<?= e(app_url("admin/login.php")); ?>">Admin Access</a>
            </div>
        </div>
        <div class="hero-cards">
            <article class="hero-card">
                <h3>For Shoppers</h3>
                <p>Browse drops, view full product details, and manage your cart with session persistence.</p>
                <a href="<?= e(app_url("shop.php")); ?>">Go to shop</a>
            </article>
            <article class="hero-card hero-card--admin">
                <h3>For Admin</h3>
                <p>Use the protected control panel to login, upload products, update inventory, and manage orders.</p>
                <a href="<?= e(app_url("admin/login.php")); ?>">Open admin panel</a>
            </article>
        </div>
    </div>
</section>

<section class="section-block">
    <div class="container">
        <div class="section-head">
            <h2>Featured Drops</h2>
            <a href="<?= e(app_url("shop.php")); ?>">View all products</a>
        </div>

        <?php if ($featuredProducts === []): ?>
            <div class="empty-state">
                <h3>No products found yet</h3>
                <p>Import the SQL seed file and your featured products will appear here.</p>
            </div>
        <?php else: ?>
            <div class="product-grid">
                <?php foreach ($featuredProducts as $product): ?>
                    <?php $productId = (int) $product["id"]; ?>
                    <article class="product-card">
                        <a class="product-media" href="<?= e(app_url("product.php?slug=" . urlencode((string) $product["slug"]))); ?>">
                            <img src="<?= e(image_src($product["image_url"])); ?>" alt="<?= e($product["name"]); ?>" loading="lazy">
                        </a>
                        <div class="product-body">
                            <p class="product-category"><?= e($product["category"]); ?></p>
                            <h3>
                                <a href="<?= e(app_url("product.php?slug=" . urlencode((string) $product["slug"]))); ?>">
                                    <?= e($product["name"]); ?>
                                </a>
                            </h3>
                            <div class="product-meta">
                                <span><?= e(money((float) $product["price"])); ?></span>
                                <span class="<?= (int) $product["stock"] > 0 ? "in-stock" : "out-stock"; ?>">
                                    <?= (int) $product["stock"] > 0 ? "In Stock" : "Sold Out"; ?>
                                </span>
                            </div>
                            <form method="post" action="<?= e(app_url("cart.php")); ?>" class="product-actions">
                                <input type="hidden" name="csrf_token" value="<?= e(csrf_token()); ?>">
                                <input type="hidden" name="action" value="add">
                                <input type="hidden" name="product_id" value="<?= e((string) $productId); ?>">
                                <input type="hidden" name="quantity" value="1">
                                <input type="hidden" name="redirect_to" value="index.php">
                                <button type="submit" <?= (int) $product["stock"] <= 0 ? "disabled" : ""; ?>>
                                    <?= (int) $product["stock"] > 0 ? "Add to Cart" : "Unavailable"; ?>
                                </button>
                            </form>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</section>

<?php include __DIR__ . "/includes/footer.php"; ?>
