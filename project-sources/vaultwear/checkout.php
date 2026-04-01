<?php
declare(strict_types=1);

require_once __DIR__ . "/includes/bootstrap.php";

$pdo = db();
$successOrder = trim((string) ($_GET["success"] ?? ""));

if ($successOrder !== "") {
    $pageTitle = "Order Confirmed | Vaultwear";
    $activePage = "cart";
    include __DIR__ . "/includes/header.php";
    ?>
    <section class="section-block">
        <div class="container">
            <div class="checkout-success">
                <p class="hero-kicker">Order Confirmed</p>
                <h1>Thank you for your purchase.</h1>
                <p>Your order number is <strong><?= e($successOrder); ?></strong>.</p>
                <div class="hero-actions">
                    <a class="btn btn-primary" href="<?= e(app_url("shop.php")); ?>">Continue Shopping</a>
                    <a class="btn btn-secondary" href="<?= e(app_url("index.php")); ?>">Back to Home</a>
                </div>
            </div>
        </div>
    </section>
    <?php
    include __DIR__ . "/includes/footer.php";
    exit;
}

$cartItems = cart_line_items($pdo);
if ($cartItems === []) {
    set_flash("error", "Your cart is empty.");
    redirect("shop.php");
}

$form = [
    "customer_name" => "",
    "email" => "",
    "phone" => "",
    "address_line" => "",
    "city" => "",
    "state" => "",
    "postal_code" => "",
    "country" => "United States",
];
$errors = [];
$subtotal = cart_subtotal($pdo);

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $form = [
        "customer_name" => trim((string) ($_POST["customer_name"] ?? "")),
        "email" => trim((string) ($_POST["email"] ?? "")),
        "phone" => trim((string) ($_POST["phone"] ?? "")),
        "address_line" => trim((string) ($_POST["address_line"] ?? "")),
        "city" => trim((string) ($_POST["city"] ?? "")),
        "state" => trim((string) ($_POST["state"] ?? "")),
        "postal_code" => trim((string) ($_POST["postal_code"] ?? "")),
        "country" => trim((string) ($_POST["country"] ?? "United States")),
    ];

    if (!verify_csrf($_POST["csrf_token"] ?? null)) {
        $errors[] = "Security validation failed. Refresh and try again.";
    }
    if (strlen($form["customer_name"]) < 2) {
        $errors[] = "Customer name is required.";
    }
    if (!filter_var($form["email"], FILTER_VALIDATE_EMAIL)) {
        $errors[] = "A valid email address is required.";
    }
    if ($form["address_line"] === "" || $form["city"] === "" || $form["state"] === "" || $form["postal_code"] === "" || $form["country"] === "") {
        $errors[] = "Complete shipping address is required.";
    }

    if ($errors === []) {
        $cart = get_cart();
        $productIds = array_keys($cart);

        try {
            $pdo->beginTransaction();

            $placeholder = implode(",", array_fill(0, count($productIds), "?"));
            $inventoryStatement = $pdo->prepare(
                "SELECT id, name, price, stock
                 FROM products
                 WHERE id IN ($placeholder)
                 FOR UPDATE"
            );
            $inventoryStatement->execute($productIds);

            $inventory = [];
            foreach ($inventoryStatement->fetchAll() as $row) {
                $inventory[(int) $row["id"]] = $row;
            }

            $orderItems = [];
            $runningTotal = 0.0;

            foreach ($cart as $productId => $quantity) {
                $productId = (int) $productId;
                $quantity = (int) $quantity;

                if (!isset($inventory[$productId])) {
                    $errors[] = "A cart item no longer exists.";
                    continue;
                }

                $available = (int) $inventory[$productId]["stock"];
                if ($quantity > $available) {
                    $errors[] = $inventory[$productId]["name"] . " has only " . $available . " units available.";
                    continue;
                }

                $unitPrice = (float) $inventory[$productId]["price"];
                $lineTotal = $unitPrice * $quantity;
                $runningTotal += $lineTotal;

                $orderItems[] = [
                    "product_id" => $productId,
                    "name" => $inventory[$productId]["name"],
                    "quantity" => $quantity,
                    "unit_price" => $unitPrice,
                    "line_total" => $lineTotal,
                ];
            }

            if ($errors !== []) {
                $pdo->rollBack();
            } else {
                $orderNumber = "VW-" . date("Ymd-His") . "-" . strtoupper(substr(bin2hex(random_bytes(3)), 0, 6));

                $orderStatement = $pdo->prepare(
                    "INSERT INTO orders
                    (order_number, customer_name, email, phone, address_line, city, state, postal_code, country, subtotal, total, status)
                    VALUES
                    (:order_number, :customer_name, :email, :phone, :address_line, :city, :state, :postal_code, :country, :subtotal, :total, :status)"
                );
                $orderStatement->execute([
                    ":order_number" => $orderNumber,
                    ":customer_name" => $form["customer_name"],
                    ":email" => $form["email"],
                    ":phone" => $form["phone"],
                    ":address_line" => $form["address_line"],
                    ":city" => $form["city"],
                    ":state" => $form["state"],
                    ":postal_code" => $form["postal_code"],
                    ":country" => $form["country"],
                    ":subtotal" => $runningTotal,
                    ":total" => $runningTotal,
                    ":status" => "pending",
                ]);

                $orderId = (int) $pdo->lastInsertId();

                $itemStatement = $pdo->prepare(
                    "INSERT INTO order_items (order_id, product_id, product_name, unit_price, quantity, line_total)
                     VALUES (:order_id, :product_id, :product_name, :unit_price, :quantity, :line_total)"
                );
                $stockStatement = $pdo->prepare("UPDATE products SET stock = stock - :quantity WHERE id = :id");

                foreach ($orderItems as $item) {
                    $itemStatement->execute([
                        ":order_id" => $orderId,
                        ":product_id" => $item["product_id"],
                        ":product_name" => $item["name"],
                        ":unit_price" => $item["unit_price"],
                        ":quantity" => $item["quantity"],
                        ":line_total" => $item["line_total"],
                    ]);

                    $stockStatement->execute([
                        ":quantity" => $item["quantity"],
                        ":id" => $item["product_id"],
                    ]);
                }

                $pdo->commit();
                clear_cart();
                redirect("checkout.php?success=" . urlencode($orderNumber));
            }
        } catch (Throwable $throwable) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            $errors[] = "Order processing failed. Please try again.";
        }
    }
}

$pageTitle = "Checkout | Vaultwear";
$activePage = "cart";
include __DIR__ . "/includes/header.php";
?>

<section class="page-head">
    <div class="container">
        <h1>Secure Checkout</h1>
        <p>Finalize your order with validated checkout processing.</p>
    </div>
</section>

<section class="section-block">
    <div class="container checkout-layout">
        <div>
            <?php if ($errors !== []): ?>
                <div class="form-errors">
                    <?php foreach ($errors as $error): ?>
                        <p><?= e($error); ?></p>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
            <form class="checkout-form" method="post" action="<?= e(app_url("checkout.php")); ?>">
                <input type="hidden" name="csrf_token" value="<?= e(csrf_token()); ?>">

                <div class="form-row">
                    <label for="customer_name">Full Name</label>
                    <input id="customer_name" name="customer_name" value="<?= e($form["customer_name"]); ?>" required>
                </div>

                <div class="form-row">
                    <label for="email">Email</label>
                    <input id="email" name="email" type="email" value="<?= e($form["email"]); ?>" required>
                </div>

                <div class="form-row">
                    <label for="phone">Phone (optional)</label>
                    <input id="phone" name="phone" value="<?= e($form["phone"]); ?>">
                </div>

                <div class="form-row">
                    <label for="address_line">Address</label>
                    <input id="address_line" name="address_line" value="<?= e($form["address_line"]); ?>" required>
                </div>

                <div class="form-row dual">
                    <div>
                        <label for="city">City</label>
                        <input id="city" name="city" value="<?= e($form["city"]); ?>" required>
                    </div>
                    <div>
                        <label for="state">State</label>
                        <input id="state" name="state" value="<?= e($form["state"]); ?>" required>
                    </div>
                </div>

                <div class="form-row dual">
                    <div>
                        <label for="postal_code">Postal Code</label>
                        <input id="postal_code" name="postal_code" value="<?= e($form["postal_code"]); ?>" required>
                    </div>
                    <div>
                        <label for="country">Country</label>
                        <input id="country" name="country" value="<?= e($form["country"]); ?>" required>
                    </div>
                </div>

                <button type="submit" class="btn btn-primary full-width">Place Order</button>
            </form>
        </div>

        <aside class="checkout-summary">
            <h3>Order Items</h3>
            <?php foreach ($cartItems as $item): ?>
                <p>
                    <span><?= e($item["product"]["name"]); ?> x <?= e((string) $item["quantity"]); ?></span>
                    <strong><?= e(money((float) $item["line_total"])); ?></strong>
                </p>
            <?php endforeach; ?>
            <p class="summary-total">
                <span>Total</span>
                <strong><?= e(money($subtotal)); ?></strong>
            </p>
        </aside>
    </div>
</section>

<?php include __DIR__ . "/includes/footer.php"; ?>
