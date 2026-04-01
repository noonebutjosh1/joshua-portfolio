<?php
declare(strict_types=1);

require_once __DIR__ . "/../includes/bootstrap.php";
require_admin();

$pdo = db();
$errors = [];

$form = [
    "id" => 0,
    "name" => "",
    "slug" => "",
    "description" => "",
    "price" => "",
    "image_url" => "",
    "category" => "",
    "stock" => "0",
    "is_featured" => 0,
];
$isEditing = false;

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    if (!verify_csrf($_POST["csrf_token"] ?? null)) {
        set_flash("error", "Invalid request token.");
        redirect("admin/products.php");
    }

    $action = (string) ($_POST["action"] ?? "");

    if ($action === "delete") {
        $productId = (int) ($_POST["id"] ?? 0);
        if ($productId > 0) {
            $deleteStatement = $pdo->prepare("DELETE FROM products WHERE id = :id");
            $deleteStatement->execute([":id" => $productId]);
            set_flash("success", "Product deleted.");
        }
        redirect("admin/products.php");
    }

    if ($action === "create" || $action === "update") {
        $form = [
            "id" => (int) ($_POST["id"] ?? 0),
            "name" => trim((string) ($_POST["name"] ?? "")),
            "slug" => trim((string) ($_POST["slug"] ?? "")),
            "description" => trim((string) ($_POST["description"] ?? "")),
            "price" => trim((string) ($_POST["price"] ?? "")),
            "image_url" => trim((string) ($_POST["image_url"] ?? "")),
            "category" => trim((string) ($_POST["category"] ?? "")),
            "stock" => trim((string) ($_POST["stock"] ?? "0")),
            "is_featured" => isset($_POST["is_featured"]) ? 1 : 0,
        ];
        $isEditing = $action === "update";

        if ($form["name"] === "") {
            $errors[] = "Product name is required.";
        }

        if ($form["slug"] === "") {
            $form["slug"] = slugify($form["name"]);
        }

        if (!is_numeric($form["price"]) || (float) $form["price"] <= 0) {
            $errors[] = "Price must be a positive number.";
        }

        if (!ctype_digit((string) $form["stock"])) {
            $errors[] = "Stock must be a non-negative integer.";
        }

        if (strlen($form["slug"]) > 150) {
            $errors[] = "Slug is too long.";
        }

        if ($action === "update" && $form["id"] <= 0) {
            $errors[] = "Invalid product ID.";
        }

        $slugCheckSql = "SELECT id FROM products WHERE slug = :slug";
        $slugParameters = [":slug" => $form["slug"]];
        if ($action === "update") {
            $slugCheckSql .= " AND id <> :id";
            $slugParameters[":id"] = $form["id"];
        }

        $slugStatement = $pdo->prepare($slugCheckSql . " LIMIT 1");
        $slugStatement->execute($slugParameters);
        if ($slugStatement->fetch() !== false) {
            $errors[] = "Slug already exists. Use a unique slug.";
        }

        if ($errors === []) {
            if ($action === "create") {
                $insertStatement = $pdo->prepare(
                    "INSERT INTO products
                    (name, slug, description, price, image_url, category, stock, is_featured)
                    VALUES
                    (:name, :slug, :description, :price, :image_url, :category, :stock, :is_featured)"
                );
                $insertStatement->execute([
                    ":name" => $form["name"],
                    ":slug" => $form["slug"],
                    ":description" => $form["description"],
                    ":price" => (float) $form["price"],
                    ":image_url" => $form["image_url"],
                    ":category" => $form["category"],
                    ":stock" => (int) $form["stock"],
                    ":is_featured" => $form["is_featured"],
                ]);
                set_flash("success", "Product created.");
            } else {
                $updateStatement = $pdo->prepare(
                    "UPDATE products
                     SET name = :name,
                         slug = :slug,
                         description = :description,
                         price = :price,
                         image_url = :image_url,
                         category = :category,
                         stock = :stock,
                         is_featured = :is_featured
                     WHERE id = :id"
                );
                $updateStatement->execute([
                    ":name" => $form["name"],
                    ":slug" => $form["slug"],
                    ":description" => $form["description"],
                    ":price" => (float) $form["price"],
                    ":image_url" => $form["image_url"],
                    ":category" => $form["category"],
                    ":stock" => (int) $form["stock"],
                    ":is_featured" => $form["is_featured"],
                    ":id" => $form["id"],
                ]);
                set_flash("success", "Product updated.");
            }

            redirect("admin/products.php");
        }
    }
}

$editId = (int) ($_GET["edit"] ?? 0);
if ($editId > 0 && $_SERVER["REQUEST_METHOD"] !== "POST") {
    $editStatement = $pdo->prepare(
        "SELECT id, name, slug, description, price, image_url, category, stock, is_featured
         FROM products
         WHERE id = :id
         LIMIT 1"
    );
    $editStatement->execute([":id" => $editId]);
    $editProduct = $editStatement->fetch();

    if ($editProduct === false) {
        set_flash("error", "Product not found.");
        redirect("admin/products.php");
    }

    $form = [
        "id" => (int) $editProduct["id"],
        "name" => (string) $editProduct["name"],
        "slug" => (string) $editProduct["slug"],
        "description" => (string) $editProduct["description"],
        "price" => (string) $editProduct["price"],
        "image_url" => (string) $editProduct["image_url"],
        "category" => (string) $editProduct["category"],
        "stock" => (string) $editProduct["stock"],
        "is_featured" => (int) $editProduct["is_featured"],
    ];
    $isEditing = true;
}

$productsStatement = $pdo->query(
    "SELECT id, name, slug, category, price, stock, is_featured, created_at
     FROM products
     ORDER BY created_at DESC"
);
$products = $productsStatement->fetchAll();

$pageTitle = "Products | Vaultwear Admin";
$activeAdminPage = "products";
include __DIR__ . "/includes/header.php";
?>

<section class="admin-panel">
    <div class="admin-panel-head">
        <h1><?= $isEditing ? "Edit Product" : "Create Product"; ?></h1>
        <?php if ($isEditing): ?>
            <a href="<?= e(app_url("admin/products.php")); ?>">Cancel edit</a>
        <?php endif; ?>
    </div>

    <?php if ($errors !== []): ?>
        <div class="form-errors">
            <?php foreach ($errors as $error): ?>
                <p><?= e($error); ?></p>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <form method="post" action="<?= e(app_url("admin/products.php")); ?>" class="admin-form">
        <input type="hidden" name="csrf_token" value="<?= e(csrf_token()); ?>">
        <input type="hidden" name="action" value="<?= $isEditing ? "update" : "create"; ?>">
        <input type="hidden" name="id" value="<?= e((string) $form["id"]); ?>">

        <div class="form-grid">
            <div>
                <label for="name">Name</label>
                <input id="name" name="name" value="<?= e($form["name"]); ?>" required>
            </div>
            <div>
                <label for="slug">Slug</label>
                <input id="slug" name="slug" value="<?= e($form["slug"]); ?>" placeholder="auto-generated-if-empty">
            </div>
            <div>
                <label for="price">Price</label>
                <input id="price" name="price" type="number" min="0.01" step="0.01" value="<?= e($form["price"]); ?>" required>
            </div>
            <div>
                <label for="stock">Stock</label>
                <input id="stock" name="stock" type="number" min="0" step="1" value="<?= e($form["stock"]); ?>" required>
            </div>
            <div>
                <label for="category">Category</label>
                <input id="category" name="category" value="<?= e($form["category"]); ?>">
            </div>
            <div>
                <label for="image_url">Image URL / Path</label>
                <input id="image_url" name="image_url" value="<?= e($form["image_url"]); ?>" placeholder="https://... or Images/...">
            </div>
        </div>

        <label for="description">Description</label>
        <textarea id="description" name="description" rows="5"><?= e($form["description"]); ?></textarea>

        <label class="checkbox-row">
            <input type="checkbox" name="is_featured" value="1" <?= (int) $form["is_featured"] === 1 ? "checked" : ""; ?>>
            Featured on homepage
        </label>

        <button type="submit"><?= $isEditing ? "Update Product" : "Create Product"; ?></button>
    </form>
</section>

<section class="admin-panel">
    <div class="admin-panel-head">
        <h2>Product Inventory</h2>
    </div>

    <?php if ($products === []): ?>
        <p class="admin-empty">No products available.</p>
    <?php else: ?>
        <div class="table-wrap">
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Name</th>
                        <th>Slug</th>
                        <th>Category</th>
                        <th>Price</th>
                        <th>Stock</th>
                        <th>Featured</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($products as $product): ?>
                        <tr>
                            <td><?= e((string) $product["id"]); ?></td>
                            <td><?= e($product["name"]); ?></td>
                            <td><?= e($product["slug"]); ?></td>
                            <td><?= e($product["category"]); ?></td>
                            <td><?= e(money((float) $product["price"])); ?></td>
                            <td><?= e((string) $product["stock"]); ?></td>
                            <td><?= (int) $product["is_featured"] === 1 ? "Yes" : "No"; ?></td>
                            <td class="table-actions">
                                <a href="<?= e(app_url("admin/products.php?edit=" . (int) $product["id"])); ?>">Edit</a>
                                <form method="post" action="<?= e(app_url("admin/products.php")); ?>" onsubmit="return confirm('Delete this product?');">
                                    <input type="hidden" name="csrf_token" value="<?= e(csrf_token()); ?>">
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="id" value="<?= e((string) $product["id"]); ?>">
                                    <button type="submit" class="danger-btn">Delete</button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</section>

<?php include __DIR__ . "/includes/footer.php"; ?>
