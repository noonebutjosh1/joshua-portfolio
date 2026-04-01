<?php
declare(strict_types=1);

function e(mixed $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, "UTF-8");
}

function app_url(string $path = ""): string
{
    $base = APP_URL;
    $path = ltrim($path, "/");

    if ($path === "") {
        return $base === "" ? "/" : $base . "/";
    }

    return ($base === "" ? "" : $base . "/") . $path;
}

function redirect(string $path): never
{
    if (preg_match("#^https?://#i", $path) === 1 || str_starts_with($path, "/")) {
        $location = $path;
    } else {
        $location = app_url($path);
    }

    header("Location: " . $location);
    exit;
}

function set_flash(string $key, string $message): void
{
    $_SESSION["flash"][$key] = $message;
}

function get_flash(string $key): ?string
{
    if (!isset($_SESSION["flash"][$key])) {
        return null;
    }

    $message = (string) $_SESSION["flash"][$key];
    unset($_SESSION["flash"][$key]);
    return $message;
}

function csrf_token(): string
{
    if (empty($_SESSION["csrf_token"])) {
        $_SESSION["csrf_token"] = bin2hex(random_bytes(32));
    }

    return (string) $_SESSION["csrf_token"];
}

function verify_csrf(?string $token): bool
{
    $sessionToken = (string) ($_SESSION["csrf_token"] ?? "");
    if ($sessionToken === "" || !is_string($token)) {
        return false;
    }

    return hash_equals($sessionToken, $token);
}

function money(float $amount): string
{
    return "$" . number_format($amount, 2);
}

function slugify(string $value): string
{
    $value = strtolower(trim($value));
    $value = preg_replace("/[^a-z0-9]+/", "-", $value) ?? "";
    $value = trim($value, "-");

    if ($value !== "") {
        return $value;
    }

    return "item-" . substr(bin2hex(random_bytes(6)), 0, 6);
}

function image_src(?string $path): string
{
    if ($path === null || trim($path) === "") {
        return "https://images.unsplash.com/photo-1515886657613-9f3515b0c78f?auto=format&fit=crop&w=1000&q=80";
    }

    if (preg_match("#^https?://#i", $path) === 1) {
        return $path;
    }

    return app_url(ltrim($path, "/"));
}

function brand_image_products(string $brandFolder, array $excludedFiles = []): array
{
    $brandFolder = trim($brandFolder, "/\\");
    if ($brandFolder === "") {
        return [];
    }

    $directory = dirname(__DIR__) . "/Images/" . $brandFolder;
    if (!is_dir($directory)) {
        return [];
    }

    $allowedExtensions = ["jpg", "jpeg", "png", "webp", "gif"];
    $excludedLookup = array_fill_keys(
        array_map(static fn (mixed $file): string => strtolower(trim((string) $file)), $excludedFiles),
        true
    );

    $products = [];
    $iterator = new DirectoryIterator($directory);

    foreach ($iterator as $fileInfo) {
        if (!$fileInfo->isFile()) {
            continue;
        }

        $filename = (string) $fileInfo->getFilename();
        $lowerFilename = strtolower($filename);
        if (isset($excludedLookup[$lowerFilename]) || str_contains($lowerFilename, "logo")) {
            continue;
        }

        $extension = strtolower((string) pathinfo($filename, PATHINFO_EXTENSION));
        if (!in_array($extension, $allowedExtensions, true)) {
            continue;
        }

        $baseName = (string) pathinfo($filename, PATHINFO_FILENAME);
        $normalizedName = trim((string) preg_replace("/[_-]+/", " ", $baseName));
        $displayName = $normalizedName === "" ? "Product" : (ctype_digit($normalizedName) ? "Product " . $normalizedName : ucwords($normalizedName));

        $products[] = [
            "file" => $filename,
            "name" => $displayName,
            "image_url" => "Images/" . $brandFolder . "/" . $filename,
        ];
    }

    usort(
        $products,
        static fn (array $left, array $right): int => strnatcasecmp((string) $left["file"], (string) $right["file"])
    );

    return $products;
}

function safe_local_path(?string $path, string $fallback = "cart.php"): string
{
    $path = trim((string) $path);
    if ($path === "") {
        return $fallback;
    }

    if (preg_match("#^(https?:)?//#i", $path) === 1) {
        return $fallback;
    }

    if (preg_match("/^[a-zA-Z0-9_\/\-.?=&%]+$/", $path) !== 1) {
        return $fallback;
    }

    return ltrim($path, "/");
}

function get_cart(): array
{
    if (!isset($_SESSION["cart"]) || !is_array($_SESSION["cart"])) {
        $_SESSION["cart"] = [];
    }

    return $_SESSION["cart"];
}

function cart_count(): int
{
    return array_sum(array_map("intval", get_cart()));
}

function add_to_cart(int $productId, int $quantity = 1): void
{
    if ($productId <= 0) {
        return;
    }

    $quantity = max(1, min(99, $quantity));
    $cart = get_cart();
    $current = (int) ($cart[$productId] ?? 0);
    $cart[$productId] = min(99, $current + $quantity);
    $_SESSION["cart"] = $cart;
}

function update_cart_quantity(int $productId, int $quantity): void
{
    $cart = get_cart();
    if (!isset($cart[$productId])) {
        return;
    }

    if ($quantity <= 0) {
        unset($cart[$productId]);
    } else {
        $cart[$productId] = min(99, $quantity);
    }

    $_SESSION["cart"] = $cart;
}

function remove_from_cart(int $productId): void
{
    $cart = get_cart();
    unset($cart[$productId]);
    $_SESSION["cart"] = $cart;
}

function clear_cart(): void
{
    $_SESSION["cart"] = [];
}

function fetch_products_by_ids(PDO $pdo, array $ids): array
{
    $ids = array_values(array_filter(array_map("intval", $ids), static fn (int $id): bool => $id > 0));
    if ($ids === []) {
        return [];
    }

    $placeholder = implode(",", array_fill(0, count($ids), "?"));
    $statement = $pdo->prepare(
        "SELECT id, name, slug, description, price, image_url, category, stock
         FROM products
         WHERE id IN ($placeholder)"
    );
    $statement->execute($ids);

    $indexed = [];
    foreach ($statement->fetchAll() as $row) {
        $indexed[(int) $row["id"]] = $row;
    }

    return $indexed;
}

function cart_line_items(PDO $pdo): array
{
    $cart = get_cart();
    if ($cart === []) {
        return [];
    }

    $products = fetch_products_by_ids($pdo, array_keys($cart));
    $items = [];

    foreach ($cart as $productId => $quantity) {
        $productId = (int) $productId;
        if (!isset($products[$productId])) {
            continue;
        }

        $qty = max(1, (int) $quantity);
        $price = (float) $products[$productId]["price"];
        $items[] = [
            "product" => $products[$productId],
            "quantity" => $qty,
            "line_total" => $price * $qty,
        ];
    }

    return $items;
}

function cart_subtotal(PDO $pdo): float
{
    $total = 0.0;
    foreach (cart_line_items($pdo) as $item) {
        $total += (float) $item["line_total"];
    }

    return $total;
}

function is_admin_logged_in(): bool
{
    return isset($_SESSION["admin_id"]) && (int) $_SESSION["admin_id"] > 0;
}

function admin_display_name(): string
{
    return (string) ($_SESSION["admin_username"] ?? "Administrator");
}

function require_admin(): void
{
    if (!is_admin_logged_in()) {
        set_flash("error", "Please sign in to access the admin panel.");
        redirect("admin/login.php");
    }
}

function status_class(string $status): string
{
    return match (strtolower($status)) {
        "completed", "shipped" => "badge-success",
        "processing", "paid" => "badge-info",
        "cancelled" => "badge-danger",
        default => "badge-muted",
    };
}
