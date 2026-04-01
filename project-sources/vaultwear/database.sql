CREATE DATABASE IF NOT EXISTS `vaultwear` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `vaultwear`;

CREATE TABLE IF NOT EXISTS `admins` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `username` VARCHAR(60) NOT NULL UNIQUE,
    `password_hash` VARCHAR(255) NOT NULL,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS `products` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `name` VARCHAR(150) NOT NULL,
    `slug` VARCHAR(170) NOT NULL UNIQUE,
    `description` TEXT NULL,
    `price` DECIMAL(10,2) NOT NULL,
    `image_url` VARCHAR(255) NULL,
    `category` VARCHAR(80) NULL,
    `stock` INT UNSIGNED NOT NULL DEFAULT 0,
    `is_featured` TINYINT(1) NOT NULL DEFAULT 0,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    INDEX `idx_products_featured` (`is_featured`),
    INDEX `idx_products_category` (`category`)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS `orders` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `order_number` VARCHAR(40) NOT NULL UNIQUE,
    `customer_name` VARCHAR(120) NOT NULL,
    `email` VARCHAR(150) NOT NULL,
    `phone` VARCHAR(40) NULL,
    `address_line` VARCHAR(255) NOT NULL,
    `city` VARCHAR(120) NOT NULL,
    `state` VARCHAR(120) NOT NULL,
    `postal_code` VARCHAR(30) NOT NULL,
    `country` VARCHAR(120) NOT NULL,
    `subtotal` DECIMAL(10,2) NOT NULL,
    `total` DECIMAL(10,2) NOT NULL,
    `status` ENUM('pending', 'processing', 'paid', 'shipped', 'completed', 'cancelled') NOT NULL DEFAULT 'pending',
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    INDEX `idx_orders_status` (`status`),
    INDEX `idx_orders_created_at` (`created_at`)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS `order_items` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `order_id` INT UNSIGNED NOT NULL,
    `product_id` INT UNSIGNED NULL,
    `product_name` VARCHAR(150) NOT NULL,
    `unit_price` DECIMAL(10,2) NOT NULL,
    `quantity` INT UNSIGNED NOT NULL,
    `line_total` DECIMAL(10,2) NOT NULL,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    CONSTRAINT `fk_order_items_order`
        FOREIGN KEY (`order_id`) REFERENCES `orders`(`id`)
        ON DELETE CASCADE,
    CONSTRAINT `fk_order_items_product`
        FOREIGN KEY (`product_id`) REFERENCES `products`(`id`)
        ON DELETE SET NULL
) ENGINE=InnoDB;

INSERT INTO `admins` (`username`, `password_hash`)
VALUES
    ('admin', '$2y$10$GTw.7V8AL8On2Q77BOv2A.Pk0id12O12Jc4Csfn3Sk8bQCgfLQu3i')
ON DUPLICATE KEY UPDATE
    `password_hash` = VALUES(`password_hash`);

INSERT INTO `products` (`name`, `slug`, `description`, `price`, `image_url`, `category`, `stock`, `is_featured`)
VALUES
    ('Hellstar Eclipse Hoodie', 'hellstar-eclipse-hoodie', 'Heavyweight black hoodie with reflective eclipse print.', 92.00, 'Images/HellStar/1.jpg', 'Hoodies', 20, 1),
    ('Ashluxe Core Tee', 'ashluxe-core-tee', 'Premium relaxed-fit tee with minimalist chest stamp.', 46.00, 'Images/Ashluxe/ash1.jpg', 'T-Shirts', 34, 1),
    ('Nike Nightshift Cargo', 'nike-nightshift-cargo', 'Street utility cargos with tapered ankle and side pockets.', 78.00, 'Images/Nike/3.jpg', 'Bottoms', 16, 1),
    ('Pith Obsidian Jacket', 'pith-obsidian-jacket', 'Layer-friendly technical jacket designed for urban weather.', 120.00, 'Images/Pith/3.jpg', 'Outerwear', 9, 1),
    ('Hellstar Flame Longsleeve', 'hellstar-flame-longsleeve', 'Longsleeve graphic piece with oversized silhouette.', 58.00, 'Images/HellStar/6.jpg', 'Longsleeves', 18, 0),
    ('Ashluxe Side Logo Shorts', 'ashluxe-side-logo-shorts', 'Soft fleece shorts for daily street rotation.', 52.00, 'Images/Ashluxe/ash8.jpg', 'Shorts', 28, 0),
    ('Nike Recoil Runner', 'nike-recoil-runner', 'Monochrome runner sneaker with lightweight cushioning.', 105.00, 'Images/Nike/10.jpg', 'Footwear', 13, 1),
    ('Pith Shadow Cap', 'pith-shadow-cap', 'Curved brim cap with tonal stitch logo.', 35.00, 'Images/Pith/1.png', 'Accessories', 40, 0)
ON DUPLICATE KEY UPDATE
    `name` = VALUES(`name`),
    `description` = VALUES(`description`),
    `price` = VALUES(`price`),
    `image_url` = VALUES(`image_url`),
    `category` = VALUES(`category`),
    `stock` = VALUES(`stock`),
    `is_featured` = VALUES(`is_featured`);
