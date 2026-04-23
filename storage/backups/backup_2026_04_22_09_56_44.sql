-- BAH Pharmacy SQL Dump
-- TARIH: 2026-04-22 09:56:44
SET FOREIGN_KEY_CHECKS=0;

DROP TABLE IF EXISTS `categories`;
CREATE TABLE `categories` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(150) NOT NULL,
  `description` text DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=10 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `categories` (`id`, `name`, `description`, `created_at`) VALUES ('1', 'Ağrı Kesici', 'Analjezik ve antipiretik ilaçlar', '2026-04-21 19:26:26');
INSERT INTO `categories` (`id`, `name`, `description`, `created_at`) VALUES ('2', 'Antibiyotik', 'Bakteri enfeksiyonlarına karşı ilaçlar', '2026-04-21 19:26:26');
INSERT INTO `categories` (`id`, `name`, `description`, `created_at`) VALUES ('3', 'Vitamin & Takviye', 'Vitamin ve mineral takviyeleri', '2026-04-21 19:26:26');
INSERT INTO `categories` (`id`, `name`, `description`, `created_at`) VALUES ('4', 'Cilt Ürünleri', 'Topikal kremler ve losyonlar', '2026-04-21 19:26:26');
INSERT INTO `categories` (`id`, `name`, `description`, `created_at`) VALUES ('5', 'Göz & Kulak', 'Damla ve pomadlar', '2026-04-21 19:26:26');
INSERT INTO `categories` (`id`, `name`, `description`, `created_at`) VALUES ('6', 'Diğer', 'Sınıflandırılmamış ürünler', '2026-04-21 19:26:26');
INSERT INTO `categories` (`id`, `name`, `description`, `created_at`) VALUES ('7', 'şurup', '', '2026-04-21 21:18:37');
INSERT INTO `categories` (`id`, `name`, `description`, `created_at`) VALUES ('8', 'hap', '', '2026-04-21 21:18:51');
INSERT INTO `categories` (`id`, `name`, `description`, `created_at`) VALUES ('9', 'enjeksiyon', '', '2026-04-21 21:19:04');

DROP TABLE IF EXISTS `currencies`;
CREATE TABLE `currencies` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `code` varchar(10) NOT NULL,
  `symbol` varchar(10) NOT NULL,
  `name` varchar(100) NOT NULL,
  `position` enum('before','after') DEFAULT 'before',
  `decimal_sep` varchar(5) DEFAULT '.',
  `thousand_sep` varchar(5) DEFAULT ',',
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` datetime DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `code` (`code`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `currencies` (`id`, `code`, `symbol`, `name`, `position`, `decimal_sep`, `thousand_sep`, `is_active`, `created_at`) VALUES ('1', 'USD', '$', 'US Dollar', 'before', '.', ',', '1', '2026-04-21 20:59:51');
INSERT INTO `currencies` (`id`, `code`, `symbol`, `name`, `position`, `decimal_sep`, `thousand_sep`, `is_active`, `created_at`) VALUES ('2', 'EUR', '€', 'Euro', 'before', ',', '.', '1', '2026-04-21 20:59:51');
INSERT INTO `currencies` (`id`, `code`, `symbol`, `name`, `position`, `decimal_sep`, `thousand_sep`, `is_active`, `created_at`) VALUES ('3', 'TRY', '₺', 'Türk Lirası', 'after', ',', '.', '1', '2026-04-21 20:59:51');
INSERT INTO `currencies` (`id`, `code`, `symbol`, `name`, `position`, `decimal_sep`, `thousand_sep`, `is_active`, `created_at`) VALUES ('4', 'GBP', '£', 'British Pound', 'before', '.', ',', '1', '2026-04-21 20:59:51');
INSERT INTO `currencies` (`id`, `code`, `symbol`, `name`, `position`, `decimal_sep`, `thousand_sep`, `is_active`, `created_at`) VALUES ('5', 'XOF', 'CFA', 'Franc CFA (BCEAO)', 'after', ',', '.', '1', '2026-04-21 20:59:51');
INSERT INTO `currencies` (`id`, `code`, `symbol`, `name`, `position`, `decimal_sep`, `thousand_sep`, `is_active`, `created_at`) VALUES ('6', 'SX', '1', 'sx', 'after', '.', ',', '1', '2026-04-21 21:21:42');

DROP TABLE IF EXISTS `customers`;
CREATE TABLE `customers` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `unique_id` varchar(36) NOT NULL COMMENT 'UUID',
  `first_name` varchar(100) NOT NULL,
  `last_name` varchar(100) NOT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `payment_due_days` int(10) unsigned DEFAULT 30 COMMENT 'Vade günü',
  `total_debt` decimal(12,2) NOT NULL DEFAULT 0.00,
  `currency` varchar(10) DEFAULT 'USD',
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_id` (`unique_id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `customers` (`id`, `unique_id`, `first_name`, `last_name`, `phone`, `address`, `payment_due_days`, `total_debt`, `currency`, `is_active`, `created_at`, `updated_at`) VALUES ('1', '600277ee-d479-4565-9d25-922a1f9d51be', 'haso', 'filan', NULL, NULL, '30', '0.00', 'USD', '1', '2026-04-21 21:20:16', '2026-04-21 21:20:16');

DROP TABLE IF EXISTS `exchange_rates`;
CREATE TABLE `exchange_rates` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `currency_code` varchar(10) NOT NULL,
  `rate_to_usd` decimal(16,6) NOT NULL,
  `effective_date` date NOT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_code_date` (`currency_code`,`effective_date`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `exchange_rates` (`id`, `currency_code`, `rate_to_usd`, `effective_date`, `created_at`) VALUES ('1', 'EUR', '0.920000', '2026-04-21', '2026-04-21 20:59:51');
INSERT INTO `exchange_rates` (`id`, `currency_code`, `rate_to_usd`, `effective_date`, `created_at`) VALUES ('2', 'TRY', '38.500000', '2026-04-21', '2026-04-21 20:59:51');
INSERT INTO `exchange_rates` (`id`, `currency_code`, `rate_to_usd`, `effective_date`, `created_at`) VALUES ('3', 'GBP', '0.790000', '2026-04-21', '2026-04-21 20:59:51');
INSERT INTO `exchange_rates` (`id`, `currency_code`, `rate_to_usd`, `effective_date`, `created_at`) VALUES ('4', 'XOF', '603.500000', '2026-04-21', '2026-04-21 20:59:51');
INSERT INTO `exchange_rates` (`id`, `currency_code`, `rate_to_usd`, `effective_date`, `created_at`) VALUES ('5', 'SX', '10.000000', '2026-04-21', '2026-04-21 21:21:42');

DROP TABLE IF EXISTS `logs`;
CREATE TABLE `logs` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `action` varchar(255) NOT NULL,
  `user` varchar(150) DEFAULT 'system',
  `ip` varchar(45) DEFAULT NULL,
  `detail` text DEFAULT NULL,
  `timestamp` datetime DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `logs` (`id`, `action`, `user`, `ip`, `detail`, `timestamp`) VALUES ('1', 'Ürün eklendi', 'system', '::1', 'ID:1 | DENEME İLAÇ', '2026-04-21 20:15:03');
INSERT INTO `logs` (`id`, `action`, `user`, `ip`, `detail`, `timestamp`) VALUES ('2', 'Kategori eklendi', 'system', '::1', 'şurup', '2026-04-21 21:18:37');
INSERT INTO `logs` (`id`, `action`, `user`, `ip`, `detail`, `timestamp`) VALUES ('3', 'Kategori eklendi', 'system', '::1', 'hap', '2026-04-21 21:18:51');
INSERT INTO `logs` (`id`, `action`, `user`, `ip`, `detail`, `timestamp`) VALUES ('4', 'Kategori eklendi', 'system', '::1', 'enjeksiyon', '2026-04-21 21:19:04');
INSERT INTO `logs` (`id`, `action`, `user`, `ip`, `detail`, `timestamp`) VALUES ('5', 'Müşteri eklendi', 'system', '::1', 'ID:1 | haso filan', '2026-04-21 21:20:16');
INSERT INTO `logs` (`id`, `action`, `user`, `ip`, `detail`, `timestamp`) VALUES ('6', 'Currency added', 'system', '::1', 'Code:SX | sx', '2026-04-21 21:21:42');

DROP TABLE IF EXISTS `payments`;
CREATE TABLE `payments` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `customer_id` int(10) unsigned NOT NULL,
  `sale_id` int(10) unsigned DEFAULT NULL,
  `amount` decimal(12,2) NOT NULL DEFAULT 0.00,
  `method` enum('cash','card','transfer','other') NOT NULL DEFAULT 'cash',
  `currency` varchar(10) DEFAULT 'USD',
  `note` text DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `customer_id` (`customer_id`),
  KEY `sale_id` (`sale_id`),
  CONSTRAINT `payments_ibfk_1` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`) ON DELETE CASCADE,
  CONSTRAINT `payments_ibfk_2` FOREIGN KEY (`sale_id`) REFERENCES `sales` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

DROP TABLE IF EXISTS `products`;
CREATE TABLE `products` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `barcode` varchar(100) DEFAULT NULL,
  `sku` varchar(100) DEFAULT NULL,
  `name` varchar(255) NOT NULL,
  `dosage_form` varchar(100) DEFAULT NULL COMMENT 'Tablet, Şurup, Ampul vb.',
  `category_id` int(10) unsigned DEFAULT NULL,
  `purchase_price` decimal(10,2) NOT NULL DEFAULT 0.00,
  `sale_price` decimal(10,2) NOT NULL DEFAULT 0.00,
  `critical_stock` int(10) unsigned NOT NULL DEFAULT 5,
  `stock_quantity` int(11) NOT NULL DEFAULT 0,
  `unit` varchar(50) DEFAULT 'Kutu' COMMENT 'Kutu, Adet vb.',
  `currency` varchar(10) DEFAULT 'USD',
  `image` varchar(255) DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `barcode` (`barcode`),
  KEY `category_id` (`category_id`),
  CONSTRAINT `products_ibfk_1` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `products` (`id`, `barcode`, `sku`, `name`, `dosage_form`, `category_id`, `purchase_price`, `sale_price`, `critical_stock`, `stock_quantity`, `unit`, `currency`, `image`, `is_active`, `created_at`, `updated_at`) VALUES ('1', '1111111111', 'İLAÇ-01', 'DENEME İLAÇ', 'ŞURUP', '2', '10.00', '15.00', '5', '10', 'Şişe', 'USD', NULL, '1', '2026-04-21 20:15:03', '2026-04-21 20:15:03');

DROP TABLE IF EXISTS `sale_items`;
CREATE TABLE `sale_items` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `sale_id` int(10) unsigned NOT NULL,
  `product_id` int(10) unsigned NOT NULL,
  `quantity` int(11) NOT NULL DEFAULT 1,
  `unit_price` decimal(10,2) NOT NULL DEFAULT 0.00,
  `total_price` decimal(12,2) NOT NULL DEFAULT 0.00,
  PRIMARY KEY (`id`),
  KEY `sale_id` (`sale_id`),
  KEY `product_id` (`product_id`),
  CONSTRAINT `sale_items_ibfk_1` FOREIGN KEY (`sale_id`) REFERENCES `sales` (`id`) ON DELETE CASCADE,
  CONSTRAINT `sale_items_ibfk_2` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

DROP TABLE IF EXISTS `sales`;
CREATE TABLE `sales` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `customer_id` int(10) unsigned DEFAULT NULL,
  `total_amount` decimal(12,2) NOT NULL DEFAULT 0.00,
  `discount_type` enum('none','percent','fixed') NOT NULL DEFAULT 'none',
  `discount_value` decimal(10,2) NOT NULL DEFAULT 0.00,
  `final_amount` decimal(12,2) NOT NULL DEFAULT 0.00,
  `paid_amount` decimal(12,2) NOT NULL DEFAULT 0.00,
  `remaining_amount` decimal(12,2) NOT NULL DEFAULT 0.00,
  `currency` varchar(10) DEFAULT 'USD',
  `note` text DEFAULT NULL,
  `invoice_path` varchar(255) DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `customer_id` (`customer_id`),
  CONSTRAINT `sales_ibfk_1` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

DROP TABLE IF EXISTS `stock_movements`;
CREATE TABLE `stock_movements` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `product_id` int(10) unsigned NOT NULL,
  `type` enum('in','out','convert','adjust') NOT NULL COMMENT 'Giriş/Çıkış/Dönüşüm/Düzeltme',
  `quantity` int(11) NOT NULL COMMENT 'Pozitif veya negatif olabilir',
  `reference` varchar(100) DEFAULT NULL COMMENT 'Satış ID veya sebep',
  `note` text DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `product_id` (`product_id`),
  CONSTRAINT `stock_movements_ibfk_1` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

SET FOREIGN_KEY_CHECKS=1;
