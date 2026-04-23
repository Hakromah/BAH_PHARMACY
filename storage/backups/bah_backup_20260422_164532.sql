SET FOREIGN_KEY_CHECKS=0;

DROP TABLE IF EXISTS `categories`;
CREATE TABLE `categories` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(150) NOT NULL,
  `description` text DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=11 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `categories` VALUES('3', 'Vitamin & Takviye', 'Vitamin ve mineral takviyeleri', '2026-04-21 19:26:26');
INSERT INTO `categories` VALUES('7', 'şurup', '', '2026-04-21 21:18:37');
INSERT INTO `categories` VALUES('10', 'Test Kategori', '', '2026-04-22 14:56:38');

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
  `current_rate` decimal(16,6) DEFAULT NULL,
  `rate_date` date DEFAULT NULL,
  `is_default` tinyint(1) DEFAULT 0,
  PRIMARY KEY (`id`),
  UNIQUE KEY `code` (`code`)
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `currencies` VALUES('1', 'USD', '$', 'US Dollar', 'before', '.', ',', '1', '2026-04-21 20:59:51', '1.000000', '2026-04-22', '0');
INSERT INTO `currencies` VALUES('2', 'EUR', '€', 'Euro', 'before', ',', '.', '0', '2026-04-21 20:59:51', '1.000000', '2026-04-22', '0');
INSERT INTO `currencies` VALUES('8', 'LD', 'L$', 'LIBERIAN DOLLARS', 'before', '.', ',', '1', '2026-04-22 09:58:41', '1.000000', '2026-04-22', '1');

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
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `customers` VALUES('1', 'HAS5WCVKZ611', 'HASSAN', 'S. KROMAH', '0555 333 44 77', 'İSTANBUL BAHÇELİEVLER ADRES BİLGİSİ YAZDIK', '30', '0.00', 'USD', '1', '2026-04-21 21:20:16', '2026-04-22 16:41:39');
INSERT INTO `customers` VALUES('2', 'İSMJCVAX14DV', 'İSMAİL', 'FALAN', '12456', '160 FRANKLIN STREET 1ST FLOR NEW YORK, NY 10013', '30', '0.00', 'LD', '1', '2026-04-22 11:01:35', '2026-04-22 16:41:37');
INSERT INTO `customers` VALUES('3', 'ALI42CICGXZT', 'Ali', 'Veli', '0535 555 11 22', 'ali veli adresi istanbul', '30', '0.00', 'LD', '1', '2026-04-22 14:58:33', '2026-04-22 16:41:36');

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

INSERT INTO `exchange_rates` VALUES('1', 'EUR', '0.920000', '2026-04-21', '2026-04-21 20:59:51');
INSERT INTO `exchange_rates` VALUES('2', 'TRY', '38.500000', '2026-04-21', '2026-04-21 20:59:51');
INSERT INTO `exchange_rates` VALUES('3', 'GBP', '0.790000', '2026-04-21', '2026-04-21 20:59:51');

DROP TABLE IF EXISTS `logs`;
CREATE TABLE `logs` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `action` varchar(255) NOT NULL,
  `user` varchar(150) DEFAULT 'system',
  `ip` varchar(45) DEFAULT NULL,
  `detail` text DEFAULT NULL,
  `timestamp` datetime DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=38 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `logs` VALUES('1', 'Ürün eklendi', 'system', '::1', 'ID:1 | DENEME İLAÇ', '2026-04-21 20:15:03');
INSERT INTO `logs` VALUES('2', 'Kategori eklendi', 'system', '::1', 'şurup', '2026-04-21 21:18:37');
INSERT INTO `logs` VALUES('3', 'Kategori eklendi', 'system', '::1', 'hap', '2026-04-21 21:18:51');
INSERT INTO `logs` VALUES('4', 'Kategori eklendi', 'system', '::1', 'enjeksiyon', '2026-04-21 21:19:04');
INSERT INTO `logs` VALUES('5', 'Müşteri eklendi', 'system', '::1', 'ID:1 | haso filan', '2026-04-21 21:20:16');
INSERT INTO `logs` VALUES('6', 'Currency added', 'system', '::1', 'Code:SX | sx', '2026-04-21 21:21:42');
INSERT INTO `logs` VALUES('7', 'Manuel Yedek Alındı', 'system', '::1', 'Dosya: backup_2026_04_22_09_56_44.sql', '2026-04-22 09:56:44');
INSERT INTO `logs` VALUES('8', 'Currency deleted', 'system', '::1', 'Code:XOF', '2026-04-22 09:57:01');
INSERT INTO `logs` VALUES('9', 'Currency deleted', 'system', '::1', 'Code:SX', '2026-04-22 09:57:06');
INSERT INTO `logs` VALUES('10', 'Currency added', 'system', '::1', 'Code:LD | LIBERIAN DOLLARS', '2026-04-22 09:57:51');
INSERT INTO `logs` VALUES('11', 'Currency deleted', 'system', '::1', 'Code:LD', '2026-04-22 09:58:30');
INSERT INTO `logs` VALUES('12', 'Currency added', 'system', '::1', 'Code:LD | LIBERIAN DOLLARS', '2026-04-22 09:58:41');
INSERT INTO `logs` VALUES('13', 'Ürün eklendi', 'system', '::1', 'ID:2 | PRESTİJ GÜMRÜK MÜŞAVİRLİĞİ | Stok:0', '2026-04-22 10:01:13');
INSERT INTO `logs` VALUES('14', 'Stok girişi yapıldı', 'system', '::1', 'Ürün:PRESTİJ GÜMRÜK MÜŞAVİRLİĞİ | +10 Kutu | 0→10', '2026-04-22 10:01:39');
INSERT INTO `logs` VALUES('15', 'Ürün eklendi', 'system', '::1', 'ID:3 | PRESTİJ GÜMRÜK AÇIK KUTU | Stok:0', '2026-04-22 10:02:59');
INSERT INTO `logs` VALUES('16', 'Stok dönüşümü', 'system', '::1', 'Kaynak ID:2 x1 → Hedef ID:3 x20', '2026-04-22 10:03:22');
INSERT INTO `logs` VALUES('17', 'Satış yapıldı', 'system', '::1', 'Satış #1 | Tutar:9 LD | Müşteri ID:1', '2026-04-22 10:04:45');
INSERT INTO `logs` VALUES('18', 'Ürün güncellendi', 'system', '::1', 'ID:2 | PRESTİJ GÜMRÜK MÜŞAVİRLİĞİ', '2026-04-22 10:06:51');
INSERT INTO `logs` VALUES('19', 'Hızlı Tahsilat', 'system', '::1', 'Müşteri ID:1 | Tutar:4', '2026-04-22 10:52:06');
INSERT INTO `logs` VALUES('20', 'Hızlı Tahsilat', 'system', '192.168.1.137', 'Müşteri ID:1 | Tutar:5', '2026-04-22 10:54:49');
INSERT INTO `logs` VALUES('21', 'Satış yapıldı', 'system', '::1', 'Satış #2 | Tutar:20 LD | Müşteri ID:1', '2026-04-22 11:00:12');
INSERT INTO `logs` VALUES('22', 'Müşteri eklendi', 'system', '::1', 'ID:2 | İSMAİL FALAN', '2026-04-22 11:01:35');
INSERT INTO `logs` VALUES('23', 'Müşteri güncellendi', 'system', '::1', 'ID:2 | İSMAİL FALAN', '2026-04-22 11:01:42');
INSERT INTO `logs` VALUES('24', 'Satış yapıldı', 'system', '::1', 'Satış #3 | Tutar:60 LD | Müşteri ID:2', '2026-04-22 11:02:34');
INSERT INTO `logs` VALUES('25', 'Müşteri güncellendi', 'system', '::1', 'ID:1 | HASSAN S. KROMAH', '2026-04-22 11:07:20');
INSERT INTO `logs` VALUES('26', 'Ürün Eklendi', 'system', '::1', 'Ürün ID: #4 — Parol', '2026-04-22 14:57:51');
INSERT INTO `logs` VALUES('27', 'Stok Girişi', 'system', '::1', 'Ürün ID:4 | Mik:100', '2026-04-22 15:03:11');
INSERT INTO `logs` VALUES('28', 'Ürün Güncellendi', 'system', '::1', 'Ürün ID: #4', '2026-04-22 15:07:32');
INSERT INTO `logs` VALUES('29', 'Ürün Güncellendi', 'system', '::1', 'Ürün ID: #1', '2026-04-22 15:07:45');
INSERT INTO `logs` VALUES('30', 'Satış', 'system', '::1', 'Satış #4 tamamlandı. Tutar: 250', '2026-04-22 15:16:20');
INSERT INTO `logs` VALUES('31', 'Ürün Eklendi', 'system', '::1', 'Ürün ID: #5 — silinecek ilaç', '2026-04-22 15:33:27');
INSERT INTO `logs` VALUES('32', 'Ürün silindi', 'system', '::1', 'ID:1 | DENEME İLAÇ', '2026-04-22 16:41:25');
INSERT INTO `logs` VALUES('33', 'Satış iptal edildi', 'system', '::1', 'Satış #4 | Müşteri ID:3', '2026-04-22 16:41:36');
INSERT INTO `logs` VALUES('34', 'Satış iptal edildi', 'system', '::1', 'Satış #3 | Müşteri ID:2', '2026-04-22 16:41:37');
INSERT INTO `logs` VALUES('35', 'Satış iptal edildi', 'system', '::1', 'Satış #2 | Müşteri ID:1', '2026-04-22 16:41:39');
INSERT INTO `logs` VALUES('36', 'Satış iptal edildi', 'system', '::1', 'Satış #1 | Müşteri ID:1', '2026-04-22 16:41:41');
INSERT INTO `logs` VALUES('37', 'Ürün silindi', 'system', '::1', 'ID:5 | silinecek ilaç', '2026-04-22 16:42:24');

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
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `payments` VALUES('1', '1', NULL, '2.00', 'cash', 'LD', 'Satış anı ödemesi', '2026-04-22 10:04:45');
INSERT INTO `payments` VALUES('2', '1', NULL, '4.00', 'cash', 'USD', 'BORÇ ÖDEMESİ', '2026-04-22 10:52:06');
INSERT INTO `payments` VALUES('3', '1', NULL, '5.00', 'cash', 'USD', 'KALANI YARIN', '2026-04-22 10:54:49');
INSERT INTO `payments` VALUES('4', '3', NULL, '100.00', 'cash', 'USD', 'Satış esnasında alınan peşin ödeme.', '2026-04-22 15:16:20');
INSERT INTO `payments` VALUES('5', '2', NULL, '50.00', 'cash', 'USD', 'ödeme', '2026-04-22 15:27:00');

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
  `image_url` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `barcode` (`barcode`),
  KEY `category_id` (`category_id`),
  CONSTRAINT `products_ibfk_1` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `products` VALUES('2', '1212114444', 'HS01', 'PRESTİJ GÜMRÜK MÜŞAVİRLİĞİ', 'Tablet', '3', '10.00', '20.00', '5', '9', 'Kutu', 'LD', 'product_69e8738b5c6281.46331195.png', '1', '2026-04-22 10:01:13', '2026-04-22 16:41:39', 'product_69e8738b5c6281.46331195.png');
INSERT INTO `products` VALUES('3', '111', 'HS02', 'PRESTİJ GÜMRÜK AÇIK KUTU', 'Tablet', '3', '0.00', '2.00', '5', '20', 'Adet', 'LD', NULL, '1', '2026-04-22 10:02:59', '2026-04-22 16:41:41', NULL);
INSERT INTO `products` VALUES('4', '121212', 'İLAÇ-02', 'Parol', 'tablet', NULL, '15.00', '25.00', '5', '100', 'Kutu', 'LD', NULL, '1', '2026-04-22 14:57:51', '2026-04-22 16:41:36', NULL);

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
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;


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
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;


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
) ENGINE=InnoDB AUTO_INCREMENT=12 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `stock_movements` VALUES('1', '2', 'in', '10', '123456', '10 Kutu giriş | 0 → 10', '2026-04-22 10:01:39');
INSERT INTO `stock_movements` VALUES('2', '2', 'convert', '-1', 'Dönüşüm (çıkış)', '', '2026-04-22 10:03:22');
INSERT INTO `stock_movements` VALUES('3', '3', 'convert', '20', 'Dönüşüm (giriş)', '', '2026-04-22 10:03:22');
INSERT INTO `stock_movements` VALUES('4', '3', 'out', '-5', 'Satış #1', 'Satış', '2026-04-22 10:04:45');
INSERT INTO `stock_movements` VALUES('5', '2', 'out', '-1', 'Satış #2', 'Satış', '2026-04-22 11:00:12');
INSERT INTO `stock_movements` VALUES('6', '2', 'out', '-3', 'Satış #3', 'Satış', '2026-04-22 11:02:34');
INSERT INTO `stock_movements` VALUES('7', '4', 'in', '100', '', '', '2026-04-22 15:03:11');
INSERT INTO `stock_movements` VALUES('8', '4', 'in', '10', 'Satış #4 iptali', 'Satış iptali', '2026-04-22 16:41:36');
INSERT INTO `stock_movements` VALUES('9', '2', 'in', '3', 'Satış #3 iptali', 'Satış iptali', '2026-04-22 16:41:37');
INSERT INTO `stock_movements` VALUES('10', '2', 'in', '1', 'Satış #2 iptali', 'Satış iptali', '2026-04-22 16:41:39');
INSERT INTO `stock_movements` VALUES('11', '3', 'in', '5', 'Satış #1 iptali', 'Satış iptali', '2026-04-22 16:41:41');

SET FOREIGN_KEY_CHECKS=1;
