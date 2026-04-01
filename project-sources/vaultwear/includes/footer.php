<?php
declare(strict_types=1);
?>
    </main>

    <footer class="site-footer">
        <div class="container footer-row">
            <div>
                <h3>Vaultwear</h3>
                <p>Dark-styled streetwear ecommerce for modern drops and sharp admin control.</p>
            </div>
            <div>
                <h4>Explore</h4>
                <a href="<?= e(app_url("shop.php")); ?>">Shop All</a>
                <a href="<?= e(app_url("cart.php")); ?>">Your Cart</a>
            </div>
            <div>
                <h4>Admin</h4>
                <a href="<?= e(app_url("admin/login.php")); ?>">Admin Login</a>
                <a href="<?= e(app_url("admin/dashboard.php")); ?>">Dashboard</a>
            </div>
        </div>
        <p class="copyright">&copy; <?= e((string) date("Y")); ?> Vaultwear. All rights reserved.</p>
    </footer>
</body>
</html>
