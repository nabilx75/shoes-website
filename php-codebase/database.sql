-- StrideHub - MySQL/MariaDB Database Schema
-- Tailored specifically for XAMPP / MariaDB installation

CREATE DATABASE IF NOT EXISTS `stridehub` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `stridehub`;

-- Drop existing tables to allow safe fresh setup re-imports
DROP TABLE IF EXISTS `admin_logs`;
DROP TABLE IF EXISTS `reviews`;
DROP TABLE IF EXISTS `payments`;
DROP TABLE IF EXISTS `order_items`;
DROP TABLE IF EXISTS `orders`;
DROP TABLE IF EXISTS `coupons`;
DROP TABLE IF EXISTS `wishlist`;
DROP TABLE IF EXISTS `cart`;
DROP TABLE IF EXISTS `addresses`;
DROP TABLE IF EXISTS `shoe_images`;
DROP TABLE IF EXISTS `shoe_sizes`;
DROP TABLE IF EXISTS `shoes`;
DROP TABLE IF EXISTS `categories`;
DROP TABLE IF EXISTS `brands`;
DROP TABLE IF EXISTS `users`;

-- 1. USERS TABLE
CREATE TABLE `users` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `full_name` VARCHAR(100) NOT NULL,
    `email` VARCHAR(150) UNIQUE NOT NULL,
    `password` VARCHAR(255) NOT NULL,
    `phone` VARCHAR(20) DEFAULT NULL,
    `role` VARCHAR(20) DEFAULT 'customer',
    `status` VARCHAR(20) DEFAULT 'active',
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE INDEX `idx_users_email` ON `users`(`email`);

-- 2. BRANDS TABLE
CREATE TABLE `brands` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `name` VARCHAR(100) NOT NULL UNIQUE,
    `logo` VARCHAR(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 3. CATEGORIES TABLE
CREATE TABLE `categories` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `name` VARCHAR(100) NOT NULL UNIQUE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 4. SHOES TABLE
CREATE TABLE `shoes` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `name` VARCHAR(150) NOT NULL,
    `brand_id` INT DEFAULT NULL,
    `category_id` INT DEFAULT NULL,
    `description` TEXT,
    `price` DECIMAL(10, 2) NOT NULL,
    `discount_price` DECIMAL(10, 2) DEFAULT NULL,
    `gender` VARCHAR(20) DEFAULT 'unisex',
    `color` VARCHAR(50) NOT NULL,
    `material` VARCHAR(100) DEFAULT NULL,
    `stock` INT DEFAULT 0,
    `rating_average` DECIMAL(3, 2) DEFAULT 5.00,
    `featured` BOOLEAN DEFAULT FALSE,
    `is_active` BOOLEAN DEFAULT TRUE,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`brand_id`) REFERENCES `brands`(`id`) ON DELETE SET NULL,
    FOREIGN KEY (`category_id`) REFERENCES `categories`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE INDEX `idx_shoes_brand` ON `shoes`(`brand_id`);
CREATE INDEX `idx_shoes_category` ON `shoes`(`category_id`);
CREATE INDEX `idx_shoes_featured` ON `shoes`(`featured`);

-- 5. SHOE SIZES
CREATE TABLE `shoe_sizes` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `shoe_id` INT NOT NULL,
    `size` VARCHAR(10) NOT NULL,
    `stock_quantity` INT DEFAULT 0,
    UNIQUE KEY `uq_shoe_size` (`shoe_id`, `size`),
    FOREIGN KEY (`shoe_id`) REFERENCES `shoes`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE INDEX `idx_shoe_sizes_shoe` ON `shoe_sizes`(`shoe_id`);

-- 6. SHOE IMAGES
CREATE TABLE `shoe_images` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `shoe_id` INT NOT NULL,
    `image_url` VARCHAR(255) NOT NULL,
    `is_primary` BOOLEAN DEFAULT FALSE,
    FOREIGN KEY (`shoe_id`) REFERENCES `shoes`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE INDEX `idx_shoe_images_shoe` ON `shoe_images`(`shoe_id`);

-- 7. ADDRESSES TABLE
CREATE TABLE `addresses` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT DEFAULT NULL,
    `country` VARCHAR(100) NOT NULL,
    `city` VARCHAR(100) NOT NULL,
    `address_line` TEXT NOT NULL,
    `postal_code` VARCHAR(20) NOT NULL,
    `is_default` BOOLEAN DEFAULT FALSE,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE INDEX `idx_addresses_user` ON `addresses`(`user_id`);

-- 8. CART TABLE
CREATE TABLE `cart` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT NOT NULL,
    `shoe_id` INT NOT NULL,
    `size_id` INT NOT NULL,
    `quantity` INT NOT NULL,
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`shoe_id`) REFERENCES `shoes`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`size_id`) REFERENCES `shoe_sizes`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE INDEX `idx_cart_user` ON `cart`(`user_id`);

-- 9. WISHLIST TABLE
CREATE TABLE `wishlist` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT NOT NULL,
    `shoe_id` INT NOT NULL,
    UNIQUE KEY `uq_user_wishlist_shoe` (`user_id`, `shoe_id`),
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`shoe_id`) REFERENCES `shoes`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 10. COUPONS TABLE
CREATE TABLE `coupons` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `code` VARCHAR(50) UNIQUE NOT NULL,
    `discount_percent` INT NOT NULL,
    `expiration_date` DATE NOT NULL,
    `active` BOOLEAN DEFAULT TRUE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 11. ORDERS TABLE
CREATE TABLE `orders` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT DEFAULT NULL,
    `total_price` DECIMAL(10, 2) NOT NULL,
    `shipping_price` DECIMAL(10, 2) DEFAULT 0.00,
    `payment_method` VARCHAR(50) NOT NULL,
    `status` VARCHAR(20) DEFAULT 'pending',
    `shipping_address_id` INT DEFAULT NULL,
    `coupon_id` INT DEFAULT NULL,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE SET NULL,
    FOREIGN KEY (`shipping_address_id`) REFERENCES `addresses`(`id`) ON DELETE RESTRICT,
    FOREIGN KEY (`coupon_id`) REFERENCES `coupons`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE INDEX `idx_orders_user` ON `orders`(`user_id`);
CREATE INDEX `idx_orders_status` ON `orders`(`status`);

-- 12. ORDER ITEMS TABLE
CREATE TABLE `order_items` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `order_id` INT NOT NULL,
    `shoe_id` INT DEFAULT NULL,
    `size_id` INT DEFAULT NULL,
    `quantity` INT NOT NULL,
    `price` DECIMAL(10, 2) NOT NULL,
    FOREIGN KEY (`order_id`) REFERENCES `orders`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`shoe_id`) REFERENCES `shoes`(`id`) ON DELETE SET NULL,
    FOREIGN KEY (`size_id`) REFERENCES `shoe_sizes`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE INDEX `idx_order_items_order` ON `order_items`(`order_id`);

-- 13. PAYMENTS TABLE
CREATE TABLE `payments` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `order_id` INT NOT NULL,
    `amount` DECIMAL(10, 2) NOT NULL,
    `payment_method` VARCHAR(50) NOT NULL,
    `payment_status` VARCHAR(20) DEFAULT 'pending',
    `transaction_reference` VARCHAR(100) UNIQUE,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`order_id`) REFERENCES `orders`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE INDEX `idx_payments_order` ON `payments`(`order_id`);

-- 14. REVIEWS TABLE
CREATE TABLE `reviews` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT NOT NULL,
    `shoe_id` INT NOT NULL,
    `rating` INT NOT NULL,
    `comment` TEXT,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY `uq_user_shoe_review` (`user_id`, `shoe_id`),
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`shoe_id`) REFERENCES `shoes`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE INDEX `idx_reviews_shoe` ON `reviews`(`shoe_id`);

-- 15. ADMIN LOGS
CREATE TABLE `admin_logs` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `admin_id` INT DEFAULT NULL,
    `action` TEXT NOT NULL,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`admin_id`) REFERENCES `users`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ==================================================
-- SEED DATA (Bootstrap StrideHub Storefront instantly!)
-- ==================================================

-- Brands
INSERT INTO `brands` (`id`, `name`, `logo`) VALUES 
(1, 'Nike', 'nike_logo.png'),
(2, 'Adidas', 'adidas_logo.png'),
(3, 'Puma', 'puma_logo.png'),
(4, 'New Balance', 'nb_logo.png'),
(5, 'Jordan', 'jordan_logo.png');

-- Categories
INSERT INTO `categories` (`id`, `name`) VALUES 
(1, 'Running'),
(2, 'Basketball'),
(3, 'Sneakers'),
(4, 'Casual'),
(5, 'Boots');

-- Users Password is 'password' (hashed)
INSERT INTO `users` (`id`, `full_name`, `email`, `password`, `phone`, `role`, `status`) VALUES
(1, 'System Administrator', 'admin@stridehub.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '1-800-ADMIN', 'admin', 'active'),
(2, 'John Doe', 'customer@stridehub.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '555-0199', 'customer', 'active');

-- Shoes
INSERT INTO `shoes` (`id`, `name`, `brand_id`, `category_id`, `description`, `price`, `discount_price`, `gender`, `color`, `material`, `stock`, `rating_average`, `featured`, `is_active`) VALUES
(1, 'Velocity Aether Max', 1, 1, 'A clean, high-performance running shoe built for everyday movement, maximum comfort, and effortless style.', 189.00, 159.00, 'unisex', 'Crimson Red / Slate Gray', 'Woven Suede Mesh', 50, 4.80, TRUE, TRUE),
(2, 'Sonic Rush G-2', 2, 1, 'Ultralight everyday runners designed with breathable fabrics and an elegant silhouette for all-day comfort.', 145.00, NULL, 'men', 'Carbon Cyan / Core Purple', 'Prime-Knit Mesh', 40, 4.50, FALSE, TRUE),
(3, 'Shadow Pulse 1.0', 5, 2, 'Premium ankle-support basketball sneakers featuring enhanced traction, supportive wrap, and premium materials.', 210.00, 185.00, 'men', 'Vantablack / Stealth Orange', 'Nylon Grid Mesh', 30, 4.90, TRUE, TRUE),
(4, 'Lunar Drift High-Top', 5, 2, 'A timeless high-top silhouette crafted from premium suede and leather for an elegant, elevated everyday look.', 165.00, NULL, 'women', 'Desert Sand / Cosmic Tan', 'Micro-Fiber Carbonate Suede', 45, 4.70, FALSE, TRUE),
(5, 'Carbon Elite X', 4, 1, 'Exquisite marathon shoes pairing a featherlight frame with a comfortable carbon plate for effortless movement.', 299.00, 260.00, 'unisex', 'Titanium Tint / Neon Glow', 'Aeron Mesh', 25, 5.00, TRUE, TRUE),
(6, 'Urban Street Glide', 3, 3, 'Ultra-minimal skate-inspired sneakers with a low-padded collar, lightweight structure, and a sleek modern finish.', 95.00, NULL, 'unisex', 'Chalk White / Charcoal Suede', 'Premium Suede Wrap', 60, 4.30, FALSE, TRUE);

-- Shoe Sizes
INSERT INTO `shoe_sizes` (`shoe_id`, `size`, `stock_quantity`) VALUES
(1, '40', 15), (1, '41', 20), (1, '42', 24), (1, '43', 8), (1, '44', 0),
(2, '40', 10), (2, '41', 12), (2, '42', 15), (2, '43', 3), (2, '44', 0),
(3, '40', 8),  (3, '41', 5),  (3, '42', 12), (3, '43', 5), (3, '44', 0),
(4, '40', 14), (4, '41', 10), (4, '42', 8),  (4, '43', 13), (4, '44', 0),
(5, '40', 5),  (5, '41', 8),  (5, '42', 10), (5, '43', 2), (5, '44', 0),
(6, '40', 20), (6, '41', 15), (6, '42', 25), (6, '43', 0), (6, '44', 0);

-- Shoe Images (Primary displays)
INSERT INTO `shoe_images` (`shoe_id`, `image_url`, `is_primary`) VALUES
(1, 'https://images.unsplash.com/photo-1542291026-7eec264c27ff?auto=format&fit=crop&w=600&q=80', TRUE),
(2, 'https://images.unsplash.com/photo-1608231387042-66d1773070a5?auto=format&fit=crop&w=600&q=80', TRUE),
(3, 'https://images.unsplash.com/photo-1552346154-21d32810aba3?auto=format&fit=crop&w=600&q=80', TRUE),
(4, 'https://images.unsplash.com/photo-1595950653106-6c9ebd614d3a?auto=format&fit=crop&w=600&q=80', TRUE),
(5, 'https://images.unsplash.com/photo-1514989940723-e8e51635b782?auto=format&fit=crop&w=600&q=80', TRUE),
(6, 'https://images.unsplash.com/photo-1525966222134-fcfa99b8ae77?auto=format&fit=crop&w=600&q=80', TRUE);

-- Coupons
INSERT INTO `coupons` (`code`, `discount_percent`, `expiration_date`, `active`) VALUES
('OFF10', 10, '2028-12-31', TRUE),
('SUMMER20', 20, '2028-12-31', TRUE),
('SNEAKERNEW', 15, '2028-12-31', TRUE);
