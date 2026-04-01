<?php
declare(strict_types=1);

require_once __DIR__ . "/includes/bootstrap.php";

$pdo = db();

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    if (!verify_csrf($_POST["csrf_token"] ?? null)) {
        set_flash("error", "Invalid request token. Please try again.");
        redirect("cart.php");
    }

    $action = (string) ($_POST["action"] ?? "");
    $redirectTarget = safe_local_path($_POST["redirect_to"] ?? "cart.php", "cart.php");

    if ($action === "add") {
        $productId = (int) ($_POST["product_id"] ?? 0);
        $quantity = max(1, (int) ($_POST["quantity"] ?? 1));

        $statement = $pdo->prepare("SELECT id, name, stock FROM products WHERE id = :id LIMIT 1");
        $statement->execute([":id" => $productId]);
        $product = $statement->fetch();

        if ($product === false) {
            set_flash("error", "Product not found.");
        } elseif ((int) $product["stock"] <= 0) {
            set_flash("error", "This product is currently out of stock.");
        } else {
            $cart = get_cart();
            $alreadyInCart = (int) ($cart[$productId] ?? 0);
            $maxAddable = max(0, (int) $product["stock"] - $alreadyInCart);

            if ($maxAddable <= 0) {
                set_flash("error", "Maximum available stock is already in your cart.");
            } else {
                $finalQuantity = min($quantity, $maxAddable);
                add_to_cart($productId, $finalQuantity);
                set_flash("success", "Added " . $finalQuantity . " item(s) to your cart.");
            }
        }
    } elseif ($action === "update") {
        $productId = (int) ($_POST["product_id"] ?? 0);
        $quantity = (int) ($_POST["quantity"] ?? 1);

        if ($quantity <= 0) {
            remove_from_cart($productId);
            set_flash("success", "Item removed from your cart.");
        } else {
            $statement = $pdo->prepare("SELECT stock FROM products WHERE id = :id LIMIT 1");
            $statement->execute([":id" => $productId]);
            $product = $statement->fetch();

            if ($product === false) {
                remove_from_cart($productId);
                set_flash("error", "Product no longer exists and was removed from your cart.");
            } elseif ((int) $product["stock"] <= 0) {
                remove_from_cart($productId);
                set_flash("error", "Product is out of stock and was removed from your cart.");
            } else {
                $finalQuantity = min($quantity, (int) $product["stock"]);
                update_cart_quantity($productId, $finalQuantity);
                set_flash("success", "Cart updated.");
            }
        }
    } elseif ($action === "remove") {
        $productId = (int) ($_POST["product_id"] ?? 0);
        remove_from_cart($productId);
        set_flash("success", "Item removed from your cart.");
    } elseif ($action === "clear") {
        clear_cart();
        set_flash("success", "Cart cleared.");
    } else {
        set_flash("error", "Unsupported cart action.");
    }

    redirect($redirectTarget);
}

$items = cart_line_items($pdo);
$subtotal = cart_subtotal($pdo);

$pageTitle = "Cart | Vaultwear";
$activePage = "cart";
include __DIR__ . "/includes/header.php";
?>

<section class="page-head">
    <div class="container">
        <h1>Your Cart</h1>
        <p>Session-based cart ready for secure checkout.</p>
    </div>
</section>

<section class="section-block">
    <div class="container">
        <?php if ($items === []): ?>
            <div class="empty-state">
                <h3>Your cart is empty</h3>
                <p>Pick your next drop and come back for checkout.</p>
                <a class="btn btn-primary" href="<?= e(app_url("shop.php")); ?>">Shop Now</a>
            </div>
        <?php else: ?>
            <div class="cart-layout">
                <div class="table-wrap">
                    <table class="cart-table">
                        <thead>
                            <tr>
                                <th>Product</th>
                                <th>Price</th>
                                <th>Quantity</th>
                                <th>Total</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($items as $item): ?>
                                <?php
                                    $product = $item["product"];
                                    $productId = (int) $product["id"];
                                ?>
                                <tr>
                                    <td>
                                        <div class="cart-product">
                                            <img src="<?= e(image_src($product["image_url"])); ?>" alt="<?= e($product["name"]); ?>">
                                            <div>
                                                <a href="<?= e(app_url("product.php?slug=" . urlencode((string) $product["slug"]))); ?>">
                                                    <?= e($product["name"]); ?>
                                                </a>
                                                <p><?= e($product["category"]); ?></p>
                                            </div>
                                        </div>
                                    </td>
                                    <td><?= e(money((float) $product["price"])); ?></td>
                                    <td>
                                        <form method="post" action="<?= e(app_url("cart.php")); ?>" class="qty-form">
                                            <input type="hidden" name="csrf_token" value="<?= e(csrf_token()); ?>">
                                            <input type="hidden" name="action" value="update">
                                            <input type="hidden" name="product_id" value="<?= e((string) $productId); ?>">
                                            <input type="hidden" name="redirect_to" value="cart.php">
                                            <input
                                                type="number"
                                                name="quantity"
                                                min="0"
                                                max="<?= e((string) max(1, (int) $product["stock"])); ?>"
                                                value="<?= e((string) $item["quantity"]); ?>"
                                            >
                                            <button type="submit">Update</button>
                                        </form>
                                    </td>
                                    <td><?= e(money((float) $item["line_total"])); ?></td>
                                    <td>
                                        <form method="post" action="<?= e(app_url("cart.php")); ?>">
                                            <input type="hidden" name="csrf_token" value="<?= e(csrf_token()); ?>">
                                            <input type="hidden" name="action" value="remove">
                                            <input type="hidden" name="product_id" value="<?= e((string) $productId); ?>">
                                            <input type="hidden" name="redirect_to" value="cart.php">
                                            <button class="danger-btn" type="submit">Remove</button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <aside class="cart-summary">
                    <h3>Order Summary</h3>
                    <p>
                        <span>Subtotal</span>
                        <strong><?= e(money($subtotal)); ?></strong>
                    </p>
                    <p>
                        <span>Shipping</span>
                        <strong>Free</strong>
                    </p>
                    <p class="summary-total">
                        <span>Total</span>
                        <strong><?= e(money($subtotal)); ?></strong>
                    </p>
                    <a class="btn btn-primary full-width" href="<?= e(app_url("checkout.php")); ?>">Proceed to Checkout</a>
                    <form method="post" action="<?= e(app_url("cart.php")); ?>">
                        <input type="hidden" name="csrf_token" value="<?= e(csrf_token()); ?>">
                        <input type="hidden" name="action" value="clear">
                        <input type="hidden" name="redirect_to" value="cart.php">
                        <button type="submit" class="btn btn-secondary full-width">Clear Cart</button>
                    </form>
                </aside>
            </div>
        <?php endif; ?>
    </div>
</section>

<?php include __DIR__ . "/includes/footer.php"; ?>
