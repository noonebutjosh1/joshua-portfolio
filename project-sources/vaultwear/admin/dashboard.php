<?php
declare(strict_types=1);

require_once __DIR__ . "/../includes/bootstrap.php";
require_admin();

$pdo = db();

$totalProducts = (int) $pdo->query("SELECT COUNT(*) FROM products")->fetchColumn();
$totalOrders = (int) $pdo->query("SELECT COUNT(*) FROM orders")->fetchColumn();
$pendingOrders = (int) $pdo->query("SELECT COUNT(*) FROM orders WHERE status = 'pending'")->fetchColumn();
$revenue = (float) $pdo->query(
    "SELECT COALESCE(SUM(total), 0)
     FROM orders
     WHERE status IN ('processing', 'paid', 'shipped', 'completed')"
)->fetchColumn();

$latestOrdersStatement = $pdo->query(
    "SELECT id, order_number, customer_name, total, status, created_at
     FROM orders
     ORDER BY created_at DESC
     LIMIT 8"
);
$latestOrders = $latestOrdersStatement->fetchAll();

$pageTitle = "Admin Dashboard | Vaultwear";
$activeAdminPage = "dashboard";
include __DIR__ . "/includes/header.php";
?>

<section class="admin-section">
    <h1>Dashboard Overview</h1>
    <p>Monitor storefront inventory and order activity in one place.</p>

    <div class="stats-grid">
        <article class="stat-card">
            <h3>Total Products</h3>
            <p><?= e((string) $totalProducts); ?></p>
        </article>
        <article class="stat-card">
            <h3>Total Orders</h3>
            <p><?= e((string) $totalOrders); ?></p>
        </article>
        <article class="stat-card">
            <h3>Pending Orders</h3>
            <p><?= e((string) $pendingOrders); ?></p>
        </article>
        <article class="stat-card">
            <h3>Tracked Revenue</h3>
            <p><?= e(money($revenue)); ?></p>
        </article>
    </div>
</section>

<section class="admin-panel">
    <div class="admin-panel-head">
        <h2>Recent Orders</h2>
        <a href="<?= e(app_url("admin/orders.php")); ?>">View all</a>
    </div>

    <?php if ($latestOrders === []): ?>
        <p class="admin-empty">No orders yet.</p>
    <?php else: ?>
        <div class="table-wrap">
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>Order</th>
                        <th>Customer</th>
                        <th>Total</th>
                        <th>Status</th>
                        <th>Date</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($latestOrders as $order): ?>
                        <tr>
                            <td><?= e($order["order_number"]); ?></td>
                            <td><?= e($order["customer_name"]); ?></td>
                            <td><?= e(money((float) $order["total"])); ?></td>
                            <td><span class="badge <?= e(status_class((string) $order["status"])); ?>"><?= e(ucfirst((string) $order["status"])); ?></span></td>
                            <td><?= e(date("M d, Y", strtotime((string) $order["created_at"]))); ?></td>
                            <td><a href="<?= e(app_url("admin/orders.php?view=" . (int) $order["id"])); ?>">Open</a></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</section>

<?php include __DIR__ . "/includes/footer.php"; ?>
