<?php
declare(strict_types=1);

require_once __DIR__ . "/../includes/bootstrap.php";

if (is_admin_logged_in()) {
    redirect("admin/dashboard.php");
}

$error = null;
$flashSuccess = get_flash("success");
$flashError = get_flash("error");

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $username = trim((string) ($_POST["username"] ?? ""));
    $password = (string) ($_POST["password"] ?? "");

    if (!verify_csrf($_POST["csrf_token"] ?? null)) {
        $error = "Security validation failed.";
    } elseif ($username === "" || $password === "") {
        $error = "Username and password are required.";
    } else {
        $pdo = db();
        $statement = $pdo->prepare(
            "SELECT id, username, password_hash
             FROM admins
             WHERE username = :username
             LIMIT 1"
        );
        $statement->execute([":username" => $username]);
        $admin = $statement->fetch();

        if ($admin !== false && password_verify($password, (string) $admin["password_hash"])) {
            session_regenerate_id(true);
            $_SESSION["admin_id"] = (int) $admin["id"];
            $_SESSION["admin_username"] = (string) $admin["username"];
            set_flash("success", "Signed in successfully.");
            redirect("admin/dashboard.php");
        }

        $error = "Invalid credentials.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login | Vaultwear</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;600;700;800&family=Orbitron:wght@600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= e(app_url("admin/admin.css")); ?>">
</head>
<body class="admin-login-body">
    <div class="admin-login-wrap">
        <a class="admin-back-link" href="<?= e(app_url("index.php")); ?>">Back to Store</a>

        <section class="admin-login-card">
            <h1>Vaultwear Admin</h1>
            <p>Protected access for product and order control.</p>

            <?php if ($flashSuccess !== null): ?>
                <div class="admin-flash admin-flash--success"><?= e($flashSuccess); ?></div>
            <?php endif; ?>
            <?php if ($flashError !== null): ?>
                <div class="admin-flash admin-flash--error"><?= e($flashError); ?></div>
            <?php endif; ?>
            <?php if ($error !== null): ?>
                <div class="admin-flash admin-flash--error"><?= e($error); ?></div>
            <?php endif; ?>

            <form method="post" action="<?= e(app_url("admin/login.php")); ?>" class="admin-login-form">
                <input type="hidden" name="csrf_token" value="<?= e(csrf_token()); ?>">

                <label for="username">Username</label>
                <input id="username" name="username" required>

                <label for="password">Password</label>
                <input id="password" type="password" name="password" required>

                <button type="submit">Sign In</button>
            </form>
            <p class="login-hint">Seed credential: <strong>admin / Admin@123</strong></p>
        </section>
    </div>
</body>
</html>
