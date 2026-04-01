<?php
declare(strict_types=1);

require_once __DIR__ . "/includes/bootstrap.php";

$pdo = db();
$productId = (int) ($_GET["id"] ?? 0);
$productSlug = trim((string) ($_GET["slug"] ?? ""));

$product = null;

if ($productId > 0) {
    $statement = $pdo->prepare(
        "SELECT id, name, slug, description, price, image_url, category, stock
         FROM products
         WHERE id = :id
         LIMIT 1"
    );
    $statement->execute([":id" => $productId]);
    $product = $statement->fetch();
} elseif ($productSlug !== "") {
    $statement = $pdo->prepare(
        "SELECT id, name, slug, description, price, image_url, category, stock
         FROM products
         WHERE slug = :slug
         LIMIT 1"
    );
    $statement->execute([":slug" => $productSlug]);
    $product = $statement->fetch();
}

if ($product === false || $product === null) {
    http_response_code(404);
    $pageTitle = "Product Not Found | Vaultwear";
    $activePage = "shop";
    include __DIR__ . "/includes/header.php";
    ?>
    <section class="section-block">
        <div class="container">
            <div class="empty-state">
                <h3>Product not found</h3>
                <p>The requested item no longer exists or has moved.</p>
                <a class="btn btn-primary" href="<?= e(app_url("shop.php")); ?>">Back to Shop</a>
            </div>
        </div>
    </section>
    <?php
    include __DIR__ . "/includes/footer.php";
    exit;
}

$pageTitle = (string) $product["name"] . " | Vaultwear";
$activePage = "shop";
include __DIR__ . "/includes/header.php";
?>

<section class="section-block">
    <div class="container">
        <article class="product-view">
            <div class="product-view__media">
                <img src="<?= e(image_src($product["image_url"])); ?>" alt="<?= e($product["name"]); ?>">
            </div>
            <div class="product-view__content">
                <p class="product-category"><?= e($product["category"]); ?></p>
                <h1><?= e($product["name"]); ?></h1>
                <p class="product-price"><?= e(money((float) $product["price"])); ?></p>
                <p class="product-description">
                    <?= e((string) ($product["description"] ?: "Premium streetwear piece from the Vaultwear catalog.")); ?>
                </p>
                <p class="<?= (int) $product["stock"] > 0 ? "in-stock" : "out-stock"; ?>">
                    <?= (int) $product["stock"] > 0 ? ((int) $product["stock"]) . " pieces left in stock" : "Currently sold out"; ?>
                </p>

                <form class="product-view__form" method="post" action="<?= e(app_url("cart.php")); ?>">
                    <input type="hidden" name="csrf_token" value="<?= e(csrf_token()); ?>">
                    <input type="hidden" name="action" value="add">
                    <input type="hidden" name="product_id" value="<?= e((string) $product["id"]); ?>">
                    <input type="hidden" name="redirect_to" value="product.php?slug=<?= e(urlencode((string) $product["slug"])); ?>">
                    <label for="quantity">Quantity</label>
                    <input
                        type="number"
                        id="quantity"
                        name="quantity"
                        min="1"
                        max="<?= e((string) max(1, (int) $product["stock"])); ?>"
                        value="1"
                        <?= (int) $product["stock"] <= 0 ? "disabled" : ""; ?>
                    >
                    <button type="submit" <?= (int) $product["stock"] <= 0 ? "disabled" : ""; ?>>
                        <?= (int) $product["stock"] > 0 ? "Add to Cart" : "Unavailable"; ?>
                    </button>
                </form>
            </div>
        </article>
    </div>
</section>

<?php include __DIR__ . "/includes/footer.php"; ?>
