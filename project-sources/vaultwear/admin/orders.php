<?php
declare(strict_types=1);

require_once __DIR__ . "/../includes/bootstrap.php";
require_admin();

$pdo = db();
$statusOptions = ["pending", "processing", "paid", "shipped", "completed", "cancelled"];

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    if (!verify_csrf($_POST["csrf_token"] ?? null)) {
        set_flash("error", "Invalid request token.");
        redirect("admin/orders.php");
    }

    $action = (string) ($_POST["action"] ?? "");
    if ($action === "update_status") {
        $orderId = (int) ($_POST["order_id"] ?? 0);
        $status = strtolower(trim((string) ($_POST["status"] ?? "pending")));
        $returnView = (int) ($_POST["return_view"] ?? 0);

        if ($orderId <= 0 || !in_array($status, $statusOptions, true)) {
            set_flash("error", "Invalid status update.");
        } else {
            $updateStatement = $pdo->prepare("UPDATE orders SET status = :status WHERE id = :id");
            $updateStatement->execute([
                ":status" => $status,
                ":id" => $orderId,
            ]);
            set_flash("success", "Order status updated.");
        }

        $redirectPath = "admin/orders.php";
        if ($returnView > 0) {
            $redirectPath .= "?view=" . $returnView;
        }
        redirect($redirectPath);
    }
}

$ordersStatement = $pdo->query(
    "SELECT o.id, o.order_number, o.customer_name, o.email, o.total, o.status, o.created_at, COUNT(oi.id) AS items_count
     FROM orders o
     LEFT JOIN order_items oi ON oi.order_id = o.id
     GROUP BY o.id
     ORDER BY o.created_at DESC"
);
$orders = $ordersStatement->fetchAll();

$selectedOrder = null;
$selectedItems = [];
$selectedId = (int) ($_GET["view"] ?? 0);

if ($selectedId > 0) {
    $orderDetailStatement = $pdo->prepare(
        "SELECT id, order_number, customer_name, email, phone, address_line, city, state, postal_code, country, subtotal, total, status, created_at
         FROM orders
         WHERE id = :id
         LIMIT 1"
    );
    $orderDetailStatement->execute([":id" => $selectedId]);
    $selectedOrder = $orderDetailStatement->fetch();

    if ($selectedOrder !== false) {
        $itemStatement = $pdo->prepare(
            "SELECT product_name, quantity, unit_price, line_total
             FROM order_items
             WHERE order_id = :order_id
             ORDER BY id ASC"
        );
        $itemStatement->execute([":order_id" => $selectedId]);
        $selectedItems = $itemStatement->fetchAll();
    } else {
        $selectedOrder = null;
    }
}

$pageTitle = "Orders | Vaultwear Admin";
$activeAdminPage = "orders";
include __DIR__ . "/includes/header.php";
?>

<section class="admin-panel">
    <div class="admin-panel-head">
        <h1>Order Management</h1>
    </div>

    <?php if ($orders === []): ?>
        <p class="admin-empty">No orders placed yet.</p>
    <?php else: ?>
        <div class="table-wrap">
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>Order #</th>
                        <th>Customer</th>
                        <th>Email</th>
                        <th>Items</th>
                        <th>Total</th>
                        <th>Status</th>
                        <th>Date</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($orders as $order): ?>
                        <tr>
                            <td><?= e($order["order_number"]); ?></td>
                            <td><?= e($order["customer_name"]); ?></td>
                            <td><?= e($order["email"]); ?></td>
                            <td><?= e((string) $order["items_count"]); ?></td>
                            <td><?= e(money((float) $order["total"])); ?></td>
                            <td><span class="badge <?= e(status_class((string) $order["status"])); ?>"><?= e(ucfirst((string) $order["status"])); ?></span></td>
                            <td><?= e(date("M d, Y", strtotime((string) $order["created_at"]))); ?></td>
                            <td class="table-actions">
                                <a href="<?= e(app_url("admin/orders.php?view=" . (int) $order["id"])); ?>">View</a>
                                <form method="post" action="<?= e(app_url("admin/orders.php")); ?>">
                                    <input type="hidden" name="csrf_token" value="<?= e(csrf_token()); ?>">
                                    <input type="hidden" name="action" value="update_status">
                                    <input type="hidden" name="order_id" value="<?= e((string) $order["id"]); ?>">
                                    <input type="hidden" name="return_view" value="<?= e((string) $selectedId); ?>">
                                    <select name="status">
                                        <?php foreach ($statusOptions as $status): ?>
                                            <option value="<?= e($status); ?>" <?= $status === (string) $order["status"] ? "selected" : ""; ?>>
                                                <?= e(ucfirst($status)); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                    <button type="submit">Update</button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</section>

<?php if ($selectedOrder !== null): ?>
    <section class="admin-panel">
        <div class="admin-panel-head">
            <h2>Order Detail: <?= e($selectedOrder["order_number"]); ?></h2>
        </div>

        <div class="order-detail-grid">
            <div>
                <h3>Customer</h3>
                <p><?= e($selectedOrder["customer_name"]); ?></p>
                <p><?= e($selectedOrder["email"]); ?></p>
                <p><?= e((string) $selectedOrder["phone"]); ?></p>
            </div>
            <div>
                <h3>Shipping</h3>
                <p><?= e($selectedOrder["address_line"]); ?></p>
                <p><?= e($selectedOrder["city"] . ", " . $selectedOrder["state"] . " " . $selectedOrder["postal_code"]); ?></p>
                <p><?= e($selectedOrder["country"]); ?></p>
            </div>
            <div>
                <h3>Totals</h3>
                <p>Subtotal: <?= e(money((float) $selectedOrder["subtotal"])); ?></p>
                <p>Total: <?= e(money((float) $selectedOrder["total"])); ?></p>
                <p>Status: <span class="badge <?= e(status_class((string) $selectedOrder["status"])); ?>"><?= e(ucfirst((string) $selectedOrder["status"])); ?></span></p>
            </div>
        </div>

        <div class="table-wrap">
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>Product</th>
                        <th>Qty</th>
                        <th>Unit</th>
                        <th>Line Total</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($selectedItems as $item): ?>
                        <tr>
                            <td><?= e($item["product_name"]); ?></td>
                            <td><?= e((string) $item["quantity"]); ?></td>
                            <td><?= e(money((float) $item["unit_price"])); ?></td>
                            <td><?= e(money((float) $item["line_total"])); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </section>
<?php endif; ?>

<?php include __DIR__ . "/includes/footer.php"; ?>
