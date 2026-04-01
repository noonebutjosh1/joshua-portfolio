<?php
declare(strict_types=1);

require_once __DIR__ . "/includes/bootstrap.php";

$brandCards = [
    [
        "label" => "Ashluxe",
        "logo" => "Images/Ashluxe/logo.png",
        "page" => "Ashluxe/Ashluxe.php",
    ],
    [
        "label" => "HellStar",
        "logo" => "Images/HellStar/logo.png",
        "page" => "HellStar/HellStar.php",
    ],
    [
        "label" => "Nike",
        "logo" => "Images/Nike/logo.png",
        "page" => "Nike/Nike.php",
    ],
    [
        "label" => "Pith",
        "logo" => "Images/Pith/logo.png",
        "page" => "Pith/Pith.php",
    ],
];

$pageTitle = "Shop | Vaultwear";
$activePage = "shop";
include __DIR__ . "/includes/header.php";
?>

<section class="page-head">
    <div class="container">
        <h1>Shop By Brand</h1>
        <p>Pick a brand logo to open that brand's product page.</p>
    </div>
</section>

<section class="section-block">
    <div class="container">
        <div class="brand-logo-grid" aria-label="Brand links">
            <?php foreach ($brandCards as $brand): ?>
                <a class="brand-logo-card" href="<?= e(app_url((string) $brand["page"])); ?>" aria-label="<?= e((string) $brand["label"]); ?>">
                    <img src="<?= e(image_src((string) $brand["logo"])); ?>" alt="<?= e((string) $brand["label"]); ?> logo" loading="lazy">
                    <span class="brand-logo-card__label"><?= e((string) $brand["label"]); ?></span>
                    <span class="brand-logo-card__hint">Open Collection</span>
                </a>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<?php include __DIR__ . "/includes/footer.php"; ?>
