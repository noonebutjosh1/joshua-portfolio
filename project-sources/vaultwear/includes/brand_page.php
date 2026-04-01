<?php
declare(strict_types=1);

require_once __DIR__ . "/bootstrap.php";

$brandName = trim((string) ($brandName ?? ""));
$brandFolder = trim((string) ($brandFolder ?? ""));
$excludedImageFiles = is_array($excludedImageFiles ?? null) ? $excludedImageFiles : ["logo.png"];

if ($brandName === "" || $brandFolder === "") {
    http_response_code(500);
    echo "Brand page configuration is missing.";
    exit;
}

$pdo = db();
$brandPagePath = $brandFolder . "/" . $brandFolder . ".php";
$imageProducts = brand_image_products($brandFolder, $excludedImageFiles);
$imageUrls = array_values(
    array_map(
        static fn (array $product): string => (string) $product["image_url"],
        $imageProducts
    )
);

$productsByImage = [];
if ($imageUrls !== []) {
    $placeholders = implode(",", array_fill(0, count($imageUrls), "?"));
    $statement = $pdo->prepare(
        "SELECT id, name, slug, price, image_url, category, stock
         FROM products
         WHERE image_url IN ($placeholders)
         ORDER BY created_at DESC"
    );
    $statement->execute($imageUrls);

    foreach ($statement->fetchAll() as $row) {
        $imageKey = strtolower((string) $row["image_url"]);
        if (!isset($productsByImage[$imageKey])) {
            $productsByImage[$imageKey] = $row;
        }
    }
}

$slugExistsStatement = $pdo->prepare("SELECT id FROM products WHERE slug = :slug LIMIT 1");
$insertStatement = $pdo->prepare(
    "INSERT INTO products
    (name, slug, description, price, image_url, category, stock, is_featured)
    VALUES
    (:name, :slug, :description, :price, :image_url, :category, :stock, :is_featured)"
);

$products = [];
foreach ($imageProducts as $imageProduct) {
    $imageUrl = (string) $imageProduct["image_url"];
    $imageKey = strtolower($imageUrl);

    if (!isset($productsByImage[$imageKey])) {
        $baseSlug = slugify($brandName . " " . (string) $imageProduct["name"]);
        $slug = $baseSlug;
        $suffix = 2;

        while (true) {
            $slugExistsStatement->execute([":slug" => $slug]);
            if ($slugExistsStatement->fetch() === false) {
                break;
            }

            $slug = $baseSlug . "-" . $suffix;
            $suffix++;
        }

        $insertStatement->execute([
            ":name" => (string) $imageProduct["name"],
            ":slug" => $slug,
            ":description" => "Auto-generated from " . $brandName . " gallery image. Update this product in admin.",
            ":price" => 59.00,
            ":image_url" => $imageUrl,
            ":category" => $brandName,
            ":stock" => 20,
            ":is_featured" => 0,
        ]);

        $productsByImage[$imageKey] = [
            "id" => (int) $pdo->lastInsertId(),
            "name" => (string) $imageProduct["name"],
            "slug" => $slug,
            "price" => 59.00,
            "image_url" => $imageUrl,
            "category" => $brandName,
            "stock" => 20,
        ];
    }

    $products[] = $productsByImage[$imageKey];
}

$pageTitle = $brandName . " | Vaultwear";
$activePage = "shop";
include __DIR__ . "/header.php";
?>

<section class="page-head">
    <div class="container">
        <h1><?= e($brandName); ?> Collection</h1>
        <p>Browse <?= e($brandName); ?> products and continue straight into checkout.</p>
        <p>
            <a class="brand-filter brand-filter--all" href="<?= e(app_url("shop.php")); ?>">Back To Brand Grid</a>
        </p>
    </div>
</section>

<section class="section-block">
    <div class="container">
        <?php if ($products === []): ?>
            <div class="empty-state">
                <h3>No products found</h3>
                <p>Add images to <?= e("Images/" . $brandFolder); ?> to render products on this page.</p>
            </div>
        <?php else: ?>
            <div class="product-grid">
                <?php foreach ($products as $product): ?>
                    <?php $productId = (int) $product["id"]; ?>
                    <article class="product-card product-card--brand">
                        <a class="product-media product-media--brand" href="<?= e(app_url("product.php?slug=" . urlencode((string) $product["slug"]))); ?>">
                            <img src="<?= e(image_src((string) $product["image_url"])); ?>" alt="<?= e((string) $product["name"]); ?>" loading="lazy">
                        </a>
                        <div class="product-body">
                            <p class="product-category"><?= e((string) ($product["category"] ?: $brandName)); ?></p>
                            <h3>
                                <a href="<?= e(app_url("product.php?slug=" . urlencode((string) $product["slug"]))); ?>">
                                    <?= e((string) $product["name"]); ?>
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
                                <input type="hidden" name="redirect_to" value="<?= e($brandPagePath); ?>">
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

<?php include __DIR__ . "/footer.php"; ?>
