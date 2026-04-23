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
INSERT INTO `currencies` VALUES('2', 'EUR', '€', 'Euro', 'before', ',', '.', '1', '2026-04-21 20:59:51', '1.000000', '2026-04-22', '0');
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
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `customers` VALUES('1', 'HAS5WCVKZ611', 'HASSAN', 'S. KROMAH', '0555 333 44 77', 'İSTANBUL BAHÇELİEVLER ADRES BİLGİSİ YAZDIK', '30', '-500.00', 'USD', '1', '2026-04-21 21:20:16', '2026-04-22 18:17:41');
INSERT INTO `customers` VALUES('2', 'İSMJCVAX14DV', 'İSMAİL', 'FALAN', '12456', '160 FRANKLIN STREET 1ST FLOR NEW YORK, NY 10013', '30', '0.00', 'LD', '1', '2026-04-22 11:01:35', '2026-04-22 16:41:37');
INSERT INTO `customers` VALUES('3', 'ALI42CICGXZT', 'Ali', 'Veli', '0535 555 11 22', 'ali veli adresi istanbul', '30', '100.00', 'LD', '1', '2026-04-22 14:58:33', '2026-04-22 18:17:04');
INSERT INTO `customers` VALUES('5', 'MUSFQL90DFHA', 'Musa', 'Kamara', '0333333333', 'Siyavuşpaşa', '30', '0.00', 'LD', '1', '2026-04-22 18:13:55', '2026-04-22 18:13:55');

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
) ENGINE=InnoDB AUTO_INCREMENT=84 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

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
INSERT INTO `logs` VALUES('38', 'Müşteri silindi', 'system', '192.168.1.137', 'ID:4 | Oumar Kamara', '2026-04-22 16:53:07');
INSERT INTO `logs` VALUES('39', 'Satış', 'system', '192.168.1.137', 'Satış #5 tamamlandı. Tutar: 18.36', '2026-04-22 17:29:53');
INSERT INTO `logs` VALUES('40', 'Borç Dekontu', 'system', '::1', 'Müşteri #3 için 10 borç eklendi: ', '2026-04-22 17:44:45');
INSERT INTO `logs` VALUES('41', 'Borç Dekontu', 'system', '::1', 'Müşteri #3 için 17 borç eklendi: 3 aylık vade farkı faiz', '2026-04-22 17:45:10');
INSERT INTO `logs` VALUES('42', 'Giriş Yapıldı', 'system', '::1', 'Kullanıcı admin sisteme giriş yaptı.', '2026-04-22 18:00:46');
INSERT INTO `logs` VALUES('43', 'Profil Güncellendi', 'system', '::1', 'Kullanıcı #1', '2026-04-22 18:01:29');
INSERT INTO `logs` VALUES('44', 'Kullanıcı Eklendi', 'system', '::1', 'Yeni kullanıcı oluşturuldu: ismail', '2026-04-22 18:01:46');
INSERT INTO `logs` VALUES('45', 'Giriş Yapıldı', 'system', '::1', 'Kullanıcı İSMAİL sisteme giriş yaptı.', '2026-04-22 18:03:50');
INSERT INTO `logs` VALUES('46', 'Kullanıcı Eklendi', 'system', '::1', 'Yeni kullanıcı oluşturuldu: mesut', '2026-04-22 18:04:09');
INSERT INTO `logs` VALUES('47', 'Ödeme Silindi', 'system', '::1', 'Müşteri #3 için Ödeme #4 silindi.', '2026-04-22 18:04:55');
INSERT INTO `logs` VALUES('48', 'Satış Silindi', 'system', '::1', 'Müşteri #3 için Satış #7 silindi.', '2026-04-22 18:05:25');
INSERT INTO `logs` VALUES('49', 'Satış Silindi', 'system', '::1', 'Müşteri #3 için Satış #6 silindi.', '2026-04-22 18:05:27');
INSERT INTO `logs` VALUES('50', 'Satış Silindi', 'system', '::1', 'Müşteri #3 için Satış #5 silindi.', '2026-04-22 18:05:29');
INSERT INTO `logs` VALUES('51', 'Ödeme Silindi', 'system', '::1', 'Müşteri #3 için Ödeme #6 silindi.', '2026-04-22 18:05:38');
INSERT INTO `logs` VALUES('52', 'Ödeme Silindi', 'system', '::1', 'Müşteri #3 için Ödeme #7 silindi.', '2026-04-22 18:05:41');
INSERT INTO `logs` VALUES('53', 'Çıkış Yapıldı', 'system', '192.168.1.137', 'Kullanıcı  sistemden çıkış yaptı.', '2026-04-22 18:09:04');
INSERT INTO `logs` VALUES('54', 'Hatalı Giriş', 'system', '192.168.1.137', 'Kullanıcı adı: hassan', '2026-04-22 18:09:16');
INSERT INTO `logs` VALUES('55', 'Hatalı Giriş', 'system', '192.168.1.137', 'Kullanıcı adı: hassan', '2026-04-22 18:09:27');
INSERT INTO `logs` VALUES('56', 'Hatalı Giriş', 'system', '192.168.1.137', 'Kullanıcı adı: hassan', '2026-04-22 18:10:44');
INSERT INTO `logs` VALUES('57', 'Hatalı Giriş', 'system', '192.168.1.137', 'Kullanıcı adı: Hassan', '2026-04-22 18:10:54');
INSERT INTO `logs` VALUES('58', 'Hatalı Giriş', 'system', '192.168.1.137', 'Kullanıcı adı: admin', '2026-04-22 18:11:10');
INSERT INTO `logs` VALUES('59', 'Giriş Yapıldı', 'system', '192.168.1.137', 'Kullanıcı ismail sisteme giriş yaptı.', '2026-04-22 18:11:19');
INSERT INTO `logs` VALUES('60', 'Çıkış Yapıldı', 'system', '192.168.1.137', 'Kullanıcı ismail sistemden çıkış yaptı.', '2026-04-22 18:11:36');
INSERT INTO `logs` VALUES('61', 'Giriş Yapıldı', 'system', '192.168.1.137', 'Kullanıcı admin sisteme giriş yaptı.', '2026-04-22 18:11:47');
INSERT INTO `logs` VALUES('62', 'Giriş Yapıldı', 'system', '::1', 'Kullanıcı ismail sisteme giriş yaptı.', '2026-04-22 18:16:29');
INSERT INTO `logs` VALUES('63', 'Borç Dekontu', 'system', '::1', 'Müşteri #3 için 100 borç eklendi: ', '2026-04-22 18:17:04');
INSERT INTO `logs` VALUES('64', 'Ödeme Silindi', 'system', '::1', 'Müşteri #1 için Ödeme #1 silindi.', '2026-04-22 18:17:23');
INSERT INTO `logs` VALUES('65', 'Ödeme Silindi', 'system', '::1', 'Müşteri #1 için Ödeme #2 silindi.', '2026-04-22 18:17:25');
INSERT INTO `logs` VALUES('66', 'Ödeme Silindi', 'system', '::1', 'Müşteri #1 için Ödeme #3 silindi.', '2026-04-22 18:17:28');
INSERT INTO `logs` VALUES('67', 'Ödeme Silindi', 'system', '::1', 'Müşteri #1 için Ödeme #8 silindi.', '2026-04-22 18:17:30');
INSERT INTO `logs` VALUES('68', 'Giriş Yapıldı', 'system', '::1', 'Kullanıcı ismail sisteme giriş yaptı.', '2026-04-22 18:34:08');
INSERT INTO `logs` VALUES('69', 'Giriş Yapıldı', 'system', '::1', 'Kullanıcı ismail sisteme giriş yaptı.', '2026-04-22 18:40:44');
INSERT INTO `logs` VALUES('70', 'Rapor Ayarları Güncellendi', 'system', '::1', 'Genel rapor tasarım tercihleri değiştirildi.', '2026-04-22 18:48:39');
INSERT INTO `logs` VALUES('71', 'Hatalı Giriş', 'system', '192.168.1.137', 'Kullanıcı adı: hassan', '2026-04-22 18:52:58');
INSERT INTO `logs` VALUES('72', 'Giriş Yapıldı', 'system', '192.168.1.137', 'Kullanıcı admin sisteme giriş yaptı.', '2026-04-22 18:53:07');
INSERT INTO `logs` VALUES('73', 'Giriş Yapıldı', 'system', '192.168.1.137', 'Kullanıcı admin sisteme giriş yaptı.', '2026-04-22 18:55:57');
INSERT INTO `logs` VALUES('74', 'Giriş Yapıldı', 'system', '::1', 'Kullanıcı ismail sisteme giriş yaptı.', '2026-04-22 18:58:58');
INSERT INTO `logs` VALUES('75', 'Hatalı Giriş', 'system', '::1', 'Kullanıcı adı: ismail', '2026-04-22 19:28:01');
INSERT INTO `logs` VALUES('76', 'Giriş Yapıldı', 'system', '192.168.1.137', 'Kullanıcı admin sisteme giriş yaptı.', '2026-04-22 21:29:24');
INSERT INTO `logs` VALUES('77', 'Giriş Yapıldı', 'system', '192.168.1.137', 'Kullanıcı admin sisteme giriş yaptı.', '2026-04-22 21:33:29');
INSERT INTO `logs` VALUES('78', 'Giriş Yapıldı', 'system', '192.168.1.137', 'Kullanıcı admin sisteme giriş yaptı.', '2026-04-22 21:35:50');
INSERT INTO `logs` VALUES('79', 'Profil Güncellendi', 'system', '192.168.1.137', 'Kullanıcı #1 (admin)', '2026-04-22 21:36:07');
INSERT INTO `logs` VALUES('80', 'Profil Güncellendi', 'system', '192.168.1.137', 'Kullanıcı #1 (admin)', '2026-04-22 21:36:15');
INSERT INTO `logs` VALUES('81', 'Profil Güncellendi', 'system', '192.168.1.137', 'Kullanıcı #1 (admin)', '2026-04-22 21:36:16');
INSERT INTO `logs` VALUES('82', 'Ürün Güncellendi', 'system', '192.168.1.137', 'Ürün ID: #53', '2026-04-22 21:37:31');
INSERT INTO `logs` VALUES('83', 'Giriş Yapıldı', 'system', '::1', 'Kullanıcı ismail sisteme giriş yaptı.', '2026-04-23 06:20:16');

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
) ENGINE=InnoDB AUTO_INCREMENT=10 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `payments` VALUES('5', '2', NULL, '50.00', 'cash', 'USD', 'ödeme', '2026-04-22 15:27:00');
INSERT INTO `payments` VALUES('9', '1', NULL, '500.00', 'cash', 'USD', '', '2026-04-22 18:17:41');

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
) ENGINE=InnoDB AUTO_INCREMENT=59 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `products` VALUES('2', '1212114444', 'HS01', 'PRESTİJ GÜMRÜK MÜŞAVİRLİĞİ', 'Tablet', '3', '10.00', '20.00', '5', '9', 'Kutu', 'LD', 'product_69e8738b5c6281.46331195.png', '1', '2026-04-22 10:01:13', '2026-04-22 16:41:39', 'product_69e8738b5c6281.46331195.png');
INSERT INTO `products` VALUES('3', '111', 'HS02', 'PRESTİJ GÜMRÜK AÇIK KUTU', 'Tablet', '3', '0.00', '2.00', '5', '20', 'Adet', 'LD', NULL, '1', '2026-04-22 10:02:59', '2026-04-22 16:41:41', NULL);
INSERT INTO `products` VALUES('4', '121212', 'İLAÇ-02', 'Parol', 'tablet', NULL, '15.00', '25.00', '5', '100', 'Kutu', 'LD', NULL, '1', '2026-04-22 14:57:51', '2026-04-22 16:41:36', NULL);
INSERT INTO `products` VALUES('9', '8699514010112', 'PHARM-001', 'Parol 500 mg', 'Tablet', NULL, '58.00', '82.50', '20', '270', 'Kutu', 'TRY', NULL, '1', '2026-04-22 16:47:37', '2026-04-22 16:47:37', NULL);
INSERT INTO `products` VALUES('10', '8699536090055', 'PHARM-002', 'Arveles 25 mg', 'Film Tablet', NULL, '52.00', '75.50', '15', '100', 'Kutu', 'TRY', NULL, '1', '2026-04-22 16:47:37', '2026-04-22 16:47:37', NULL);
INSERT INTO `products` VALUES('11', '8699522095538', 'PHARM-003', 'Augmentin BID 1000 mg', 'Film Tablet', NULL, '112.40', '158.00', '10', '80', 'Kutu', 'TRY', NULL, '1', '2026-04-22 16:47:37', '2026-04-22 16:47:37', NULL);
INSERT INTO `products` VALUES('12', '8699546011743', 'PHARM-004', 'Dolorex 50 mg', 'Draje', NULL, '28.50', '42.00', '30', '500', 'Kutu', 'TRY', NULL, '1', '2026-04-22 16:47:37', '2026-04-22 16:47:37', NULL);
INSERT INTO `products` VALUES('13', '8699293151608', 'PHARM-005', 'Nexium 40 mg', 'Enterik Tablet', '3', '145.00', '205.00', '12', '60', 'Kutu', 'TRY', NULL, '1', '2026-04-22 16:47:37', '2026-04-22 16:47:37', NULL);
INSERT INTO `products` VALUES('15', '8699508570024', 'PHARM-007', 'Pantenol Şurup', 'Şurup', NULL, '65.00', '92.00', '10', '45', 'Şişe', 'TRY', NULL, '1', '2026-04-22 16:47:37', '2026-04-22 16:47:37', NULL);
INSERT INTO `products` VALUES('16', '8699508380029', 'PHARM-008', 'Advantan Pomad', 'Krem', NULL, '88.00', '124.00', '5', '35', 'Tüp', 'TRY', NULL, '1', '2026-04-22 16:47:37', '2026-04-22 18:05:29', NULL);
INSERT INTO `products` VALUES('17', '8699508750013', 'PHARM-009', 'Bemiks Ampul', 'Ampul', '7', '72.50', '102.00', '8', '50', 'Kutu', 'TRY', NULL, '1', '2026-04-22 16:47:37', '2026-04-22 16:47:37', NULL);
INSERT INTO `products` VALUES('19', '8699514010129', 'PHARM-011', 'Apranax Fort 550 mg', 'Tablet', NULL, '64.00', '90.00', '15', '90', 'Kutu', 'TRY', NULL, '1', '2026-04-22 16:47:37', '2026-04-22 16:47:37', NULL);
INSERT INTO `products` VALUES('20', '8699569010051', 'PHARM-012', 'Buscopan Plus', 'Tablet', NULL, '74.20', '105.00', '10', '75', 'Kutu', 'TRY', NULL, '1', '2026-04-22 16:47:37', '2026-04-22 16:47:37', NULL);
INSERT INTO `products` VALUES('21', '8699514150115', 'PHARM-013', 'Lansor 30 mg', 'Kapsül', '3', '120.00', '168.00', '12', '55', 'Kutu', 'TRY', NULL, '1', '2026-04-22 16:47:37', '2026-04-22 16:47:37', NULL);
INSERT INTO `products` VALUES('22', '8699522657439', 'PHARM-014', 'Gaviscon Likit', 'Süspansiyon', '3', '95.00', '135.00', '10', '40', 'Şişe', 'TRY', NULL, '1', '2026-04-22 16:47:37', '2026-04-22 16:47:37', NULL);
INSERT INTO `products` VALUES('23', '8699546350125', 'PHARM-015', 'Bepanthen Plus', 'Krem', NULL, '110.00', '155.00', '15', '65', 'Tüp', 'TRY', NULL, '1', '2026-04-22 16:47:37', '2026-04-22 16:47:37', NULL);
INSERT INTO `products` VALUES('24', '8699522521150', 'PHARM-016', 'Ventolin Nebules', 'Nebül', NULL, '82.00', '116.00', '5', '30', 'Kutu', 'TRY', NULL, '1', '2026-04-22 16:47:37', '2026-04-22 16:47:37', NULL);
INSERT INTO `products` VALUES('25', '8699522015512', 'PHARM-017', 'Clamoxyl 500 mg', 'Kapsül', NULL, '68.50', '98.00', '15', '85', 'Kutu', 'TRY', NULL, '1', '2026-04-22 16:47:37', '2026-04-22 16:47:37', NULL);
INSERT INTO `products` VALUES('26', '8699548013141', 'PHARM-018', 'Euthyrox 100 mcg', 'Tablet', NULL, '55.00', '78.00', '25', '110', 'Kutu', 'TRY', NULL, '1', '2026-04-22 16:47:37', '2026-04-22 16:47:37', NULL);
INSERT INTO `products` VALUES('27', '8699525091155', 'PHARM-019', 'Glifor 1000 mg', 'Tablet', NULL, '88.00', '124.00', '20', '140', 'Kutu', 'TRY', NULL, '1', '2026-04-22 16:47:37', '2026-04-22 16:47:37', NULL);
INSERT INTO `products` VALUES('28', '8699543010114', 'PHARM-020', 'Lipanthyl 267 mg', 'Kapsül', NULL, '185.00', '260.00', '8', '40', 'Kutu', 'TRY', NULL, '1', '2026-04-22 16:47:37', '2026-04-22 16:47:37', NULL);
INSERT INTO `products` VALUES('29', '8699624080036', 'PHARM-021', 'Zyrtec 10 mg', 'Film Tablet', '10', '62.00', '88.00', '15', '95', 'Kutu', 'TRY', NULL, '1', '2026-04-22 16:47:37', '2026-04-22 16:47:37', NULL);
INSERT INTO `products` VALUES('30', '8699546090151', 'PHARM-022', 'Aerius 5 mg', 'Tablet', '10', '78.00', '110.00', '12', '80', 'Kutu', 'TRY', NULL, '1', '2026-04-22 16:47:37', '2026-04-22 16:47:37', NULL);
INSERT INTO `products` VALUES('31', '8690570010155', 'PHARM-023', 'Nurofen Cold & Flu', 'Tablet', NULL, '85.50', '122.00', '25', '130', 'Kutu', 'TRY', NULL, '1', '2026-04-22 16:47:37', '2026-04-22 16:47:37', NULL);
INSERT INTO `products` VALUES('32', '8699525010118', 'PHARM-024', 'A-Ferin Forte', 'Tablet', NULL, '42.00', '60.00', '30', '160', 'Kutu', 'TRY', NULL, '1', '2026-04-22 16:47:37', '2026-04-22 18:05:29', NULL);
INSERT INTO `products` VALUES('33', '8699522705512', 'PHARM-025', 'Calpol 120 mg/5 ml', 'Şurup', NULL, '58.00', '82.00', '20', '110', 'Şişe', 'TRY', NULL, '1', '2026-04-22 16:47:37', '2026-04-22 16:47:37', NULL);
INSERT INTO `products` VALUES('34', '8699508350015', 'PHARM-026', 'Tylol Hot', 'Saşe', NULL, '145.00', '205.00', '15', '50', 'Kutu', 'TRY', NULL, '1', '2026-04-22 16:47:37', '2026-04-22 16:47:37', NULL);
INSERT INTO `products` VALUES('35', '8699546011156', 'PHARM-027', 'Benexol B12', 'Tablet', '7', '92.00', '130.00', '20', '100', 'Kutu', 'TRY', NULL, '1', '2026-04-22 16:47:37', '2026-04-22 18:05:29', NULL);
INSERT INTO `products` VALUES('36', '8699546011163', 'PHARM-028', 'Ecopirin 100 mg', 'Tablet', NULL, '32.00', '46.00', '40', '250', 'Kutu', 'TRY', NULL, '1', '2026-04-22 16:47:37', '2026-04-22 16:47:37', NULL);
INSERT INTO `products` VALUES('37', '8699546750116', 'PHARM-029', 'Diprospan Ampul', 'Ampul', NULL, '125.00', '176.00', '5', '25', 'Kutu', 'TRY', NULL, '1', '2026-04-22 16:47:37', '2026-04-22 16:47:37', NULL);
INSERT INTO `products` VALUES('38', '8699525380112', 'PHARM-030', 'Momecon Pomad', 'Krem', NULL, '74.00', '105.00', '10', '45', 'Tüp', 'TRY', NULL, '1', '2026-04-22 16:47:37', '2026-04-22 16:47:37', NULL);
INSERT INTO `products` VALUES('39', '8699543650112', 'PHARM-031', 'Duphalac Şurup', 'Şurup', '3', '115.00', '162.00', '6', '30', 'Şişe', 'TRY', NULL, '1', '2026-04-22 16:47:37', '2026-04-22 16:47:37', NULL);
INSERT INTO `products` VALUES('40', '8699504010111', 'PHARM-032', 'Cataflam 50 mg', 'Draje', NULL, '56.00', '79.00', '15', '85', 'Kutu', 'TRY', NULL, '1', '2026-04-22 16:47:37', '2026-04-22 16:47:37', NULL);
INSERT INTO `products` VALUES('41', '8699519010018', 'PHARM-033', 'Xanax 0,5 mg', 'Tablet', NULL, '98.00', '138.00', '5', '20', 'Kutu', 'TRY', NULL, '1', '2026-04-22 16:47:37', '2026-04-22 16:47:37', NULL);
INSERT INTO `products` VALUES('42', '8699519010056', 'PHARM-034', 'Lustral 50 mg', 'Tablet', NULL, '142.00', '200.00', '10', '55', 'Kutu', 'TRY', NULL, '1', '2026-04-22 16:47:37', '2026-04-22 16:47:37', NULL);
INSERT INTO `products` VALUES('43', '8699624010019', 'PHARM-035', 'Atarax 25 mg', 'Tablet', NULL, '65.00', '92.00', '8', '40', 'Kutu', 'TRY', NULL, '1', '2026-04-22 16:47:37', '2026-04-22 16:47:37', NULL);
INSERT INTO `products` VALUES('44', '8699546011170', 'PHARM-036', 'Rennie Tablet', 'Çiğnem Tableti', '3', '48.00', '68.00', '25', '120', 'Kutu', 'TRY', NULL, '1', '2026-04-22 16:47:37', '2026-04-22 16:47:37', NULL);
INSERT INTO `products` VALUES('45', '8699546011187', 'PHARM-037', 'Talcid Tablet', 'Çiğnem Tableti', '3', '46.00', '65.00', '20', '115', 'Kutu', 'TRY', NULL, '1', '2026-04-22 16:47:37', '2026-04-22 16:47:37', NULL);
INSERT INTO `products` VALUES('46', '8699293521609', 'PHARM-038', 'Pulmicort 0,5 mg', 'Nebül', NULL, '210.00', '295.00', '5', '15', 'Kutu', 'TRY', NULL, '1', '2026-04-22 16:47:37', '2026-04-22 16:47:37', NULL);
INSERT INTO `products` VALUES('47', '8699293551606', 'PHARM-039', 'Symbicort Turbuhaler', 'Inhaler', NULL, '345.00', '485.00', '3', '12', 'Adet', 'TRY', NULL, '1', '2026-04-22 16:47:37', '2026-04-22 16:47:37', NULL);
INSERT INTO `products` VALUES('48', '8699522525530', 'PHARM-040', 'Flixotide 125 mcg', 'Inhaler', NULL, '285.00', '400.00', '3', '10', 'Adet', 'TRY', NULL, '1', '2026-04-22 16:47:37', '2026-04-22 16:47:37', NULL);
INSERT INTO `products` VALUES('49', '8699569010112', 'PHARM-041', 'Vasoxen 5 mg', 'Tablet', NULL, '115.00', '162.00', '15', '70', 'Kutu', 'TRY', NULL, '1', '2026-04-22 16:47:37', '2026-04-22 16:47:37', NULL);
INSERT INTO `products` VALUES('50', '8699548013158', 'PHARM-042', 'Concor 5 mg', 'Tablet', NULL, '94.00', '132.00', '15', '85', 'Kutu', 'TRY', NULL, '1', '2026-04-22 16:47:37', '2026-04-22 16:47:37', NULL);
INSERT INTO `products` VALUES('51', '8699293031603', 'PHARM-043', 'Beloc ZOK 50 mg', 'Tablet', NULL, '108.00', '152.00', '20', '90', 'Kutu', 'TRY', NULL, '1', '2026-04-22 16:47:37', '2026-04-22 16:47:37', NULL);
INSERT INTO `products` VALUES('52', '8699569010150', 'PHARM-044', 'Micardis 80 mg', 'Tablet', NULL, '195.00', '275.00', '7', '35', 'Kutu', 'TRY', NULL, '1', '2026-04-22 16:47:37', '2026-04-22 16:47:37', NULL);
INSERT INTO `products` VALUES('53', '8699809018441', 'PHARM-045', 'Lasix 40 mg', 'Tablet', NULL, '42.00', '60.00', '20', '1000', 'Kutu', 'EUR', NULL, '1', '2026-04-22 16:47:37', '2026-04-22 21:37:31', NULL);
INSERT INTO `products` VALUES('54', '8699552010112', 'PHARM-046', 'Daflon 500 mg', 'Tablet', NULL, '175.00', '248.00', '10', '50', 'Kutu', 'TRY', NULL, '1', '2026-04-22 16:47:37', '2026-04-22 16:47:37', NULL);
INSERT INTO `products` VALUES('55', '8699745010110', 'PHARM-047', 'Venoruton Fort', 'Tablet', NULL, '135.00', '190.00', '8', '45', 'Kutu', 'TRY', NULL, '1', '2026-04-22 16:47:37', '2026-04-22 16:47:37', NULL);
INSERT INTO `products` VALUES('56', '8699809190116', 'PHARM-048', 'Muscoril 4 mg', 'Kapsül', NULL, '78.00', '110.00', '15', '70', 'Kutu', 'TRY', NULL, '1', '2026-04-22 16:47:37', '2026-04-22 16:47:37', NULL);
INSERT INTO `products` VALUES('57', '8699514010150', 'PHARM-049', 'Cabral 400 mg', 'Tablet', NULL, '64.00', '90.00', '15', '80', 'Kutu', 'TRY', NULL, '1', '2026-04-22 16:47:37', '2026-04-22 16:47:37', NULL);
INSERT INTO `products` VALUES('58', '8699519010124', 'PHARM-050', 'Parafon Tablet', 'Tablet', NULL, '52.00', '74.00', '20', '110', 'Kutu', 'TRY', NULL, '1', '2026-04-22 16:47:37', '2026-04-22 16:47:37', NULL);

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
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;


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
  `due_date` date DEFAULT NULL,
  `note` text DEFAULT NULL,
  `invoice_path` varchar(255) DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `customer_id` (`customer_id`),
  CONSTRAINT `sales_ibfk_1` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `sales` VALUES('8', '3', '100.00', 'none', '0.00', '100.00', '0.00', '100.00', 'USD', NULL, '(Borç Dekontu) ', NULL, '2026-04-22 18:17:04');

DROP TABLE IF EXISTS `settings`;
CREATE TABLE `settings` (
  `key` varchar(100) NOT NULL,
  `value` text DEFAULT NULL,
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `settings` VALUES('report_settings', '{\"show_logo\":true,\"logo_size\":60,\"hide_header_nav\":false,\"hide_sidebar\":false,\"show_summary\":true}', '2026-04-22 18:48:39');

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

DROP TABLE IF EXISTS `translations`;
CREATE TABLE `translations` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `lang_code` varchar(5) NOT NULL,
  `string_key` varchar(255) NOT NULL,
  `string_value` text DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_lang_key` (`lang_code`,`string_key`)
) ENGINE=InnoDB AUTO_INCREMENT=1223 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `translations` VALUES('1', 'tr', 'app_name', 'BAH Eczane');
INSERT INTO `translations` VALUES('2', 'tr', 'dashboard', 'Kontrol Paneli');
INSERT INTO `translations` VALUES('3', 'tr', 'settings', 'Ayarlar');
INSERT INTO `translations` VALUES('4', 'tr', 'save', 'Kaydet');
INSERT INTO `translations` VALUES('5', 'tr', 'cancel', 'İptal');
INSERT INTO `translations` VALUES('6', 'tr', 'delete', 'Sil');
INSERT INTO `translations` VALUES('7', 'tr', 'edit', 'Düzenle');
INSERT INTO `translations` VALUES('8', 'tr', 'add', 'Ekle');
INSERT INTO `translations` VALUES('9', 'tr', 'search', 'Ara');
INSERT INTO `translations` VALUES('10', 'tr', 'filter', 'Filtrele');
INSERT INTO `translations` VALUES('11', 'tr', 'back', 'Geri');
INSERT INTO `translations` VALUES('12', 'tr', 'close', 'Kapat');
INSERT INTO `translations` VALUES('13', 'tr', 'yes', 'Evet');
INSERT INTO `translations` VALUES('14', 'tr', 'no', 'Hayır');
INSERT INTO `translations` VALUES('15', 'tr', 'actions', 'İşlemler');
INSERT INTO `translations` VALUES('16', 'tr', 'detail', 'Detay');
INSERT INTO `translations` VALUES('17', 'tr', 'all', 'Tümü');
INSERT INTO `translations` VALUES('18', 'tr', 'date', 'Tarih');
INSERT INTO `translations` VALUES('19', 'tr', 'note', 'Not');
INSERT INTO `translations` VALUES('20', 'tr', 'status', 'Durum');
INSERT INTO `translations` VALUES('21', 'tr', 'active', 'Aktif');
INSERT INTO `translations` VALUES('22', 'tr', 'passive', 'Pasif');
INSERT INTO `translations` VALUES('23', 'tr', 'print', 'Yazdır');
INSERT INTO `translations` VALUES('24', 'tr', 'export_csv', 'CSV İndir');
INSERT INTO `translations` VALUES('25', 'tr', 'error', 'Hata');
INSERT INTO `translations` VALUES('26', 'tr', 'success', 'Başarılı');
INSERT INTO `translations` VALUES('27', 'tr', 'confirm_delete', 'Silmek istediğinize emin misiniz?');
INSERT INTO `translations` VALUES('28', 'tr', 'no_data', 'Veri bulunamadı.');
INSERT INTO `translations` VALUES('29', 'tr', 'total', 'Toplam');
INSERT INTO `translations` VALUES('30', 'tr', 'menu_main', 'ANA MENÜ');
INSERT INTO `translations` VALUES('31', 'tr', 'menu_stock', 'STOK');
INSERT INTO `translations` VALUES('32', 'tr', 'menu_customer_sales', 'MÜŞTERİ & SATIŞ');
INSERT INTO `translations` VALUES('33', 'tr', 'menu_reports', 'RAPORLAR');
INSERT INTO `translations` VALUES('34', 'tr', 'products', 'Ürünler');
INSERT INTO `translations` VALUES('35', 'tr', 'categories', 'Kategoriler');
INSERT INTO `translations` VALUES('36', 'tr', 'stock_entry', 'Stok Girişi');
INSERT INTO `translations` VALUES('37', 'tr', 'stock_movements', 'Stok Hareketleri');
INSERT INTO `translations` VALUES('38', 'tr', 'stock_convert', 'Stok Dönüşümü');
INSERT INTO `translations` VALUES('39', 'tr', 'customers', 'Müşteriler');
INSERT INTO `translations` VALUES('40', 'tr', 'sales', 'Satışlar');
INSERT INTO `translations` VALUES('41', 'tr', 'new_sale', 'Yeni Satış');
INSERT INTO `translations` VALUES('42', 'tr', 'reports', 'Raporlar');
INSERT INTO `translations` VALUES('43', 'tr', 'action_logs', 'İşlem Logları');
INSERT INTO `translations` VALUES('44', 'tr', 'stock_report', 'Stok Raporu');
INSERT INTO `translations` VALUES('45', 'tr', 'today_sales', 'Bugünkü Satışlar');
INSERT INTO `translations` VALUES('46', 'tr', 'monthly_revenue', 'Aylık Ciro');
INSERT INTO `translations` VALUES('47', 'tr', 'total_products', 'Toplam Ürün');
INSERT INTO `translations` VALUES('48', 'tr', 'critical_stock', 'Kritik Stok');
INSERT INTO `translations` VALUES('49', 'tr', 'total_customers', 'Toplam Müşteri');
INSERT INTO `translations` VALUES('50', 'tr', 'total_debt', 'Toplam Borç');
INSERT INTO `translations` VALUES('51', 'tr', 'today_profit', 'Bugünkü Kâr');
INSERT INTO `translations` VALUES('52', 'tr', 'monthly_profit', 'Aylık Kâr');
INSERT INTO `translations` VALUES('53', 'tr', 'recent_sales', 'Son Satışlar');
INSERT INTO `translations` VALUES('54', 'tr', 'low_stock_alert', 'Düşük Stok Uyarıları');
INSERT INTO `translations` VALUES('55', 'tr', 'sales_chart', 'Satış Grafiği (Son 7 Gün)');
INSERT INTO `translations` VALUES('56', 'tr', 'top_products', 'En Çok Satan Ürünler');
INSERT INTO `translations` VALUES('57', 'tr', 'revenue', 'Ciro');
INSERT INTO `translations` VALUES('58', 'tr', 'profit', 'Kâr');
INSERT INTO `translations` VALUES('59', 'tr', 'sale_count', 'Satış Adedi');
INSERT INTO `translations` VALUES('60', 'tr', 'quick_actions', 'Hızlı İşlemler');
INSERT INTO `translations` VALUES('61', 'tr', 'product_name', 'İlaç Adı');
INSERT INTO `translations` VALUES('62', 'tr', 'barcode', 'Barkod');
INSERT INTO `translations` VALUES('63', 'tr', 'sku', 'SKU Kodu');
INSERT INTO `translations` VALUES('64', 'tr', 'dosage_form', 'Farmasötik Form');
INSERT INTO `translations` VALUES('65', 'tr', 'category', 'Kategori');
INSERT INTO `translations` VALUES('66', 'tr', 'purchase_price', 'Alış Fiyatı');
INSERT INTO `translations` VALUES('67', 'tr', 'sale_price', 'Satış Fiyatı');
INSERT INTO `translations` VALUES('68', 'tr', 'unit', 'Birim');
INSERT INTO `translations` VALUES('69', 'tr', 'stock_qty', 'Stok Miktarı');
INSERT INTO `translations` VALUES('70', 'tr', 'critical_level', 'Kritik Seviye');
INSERT INTO `translations` VALUES('71', 'tr', 'product_image', 'Ürün Görseli');
INSERT INTO `translations` VALUES('72', 'tr', 'product_active', 'Ürün Aktif');
INSERT INTO `translations` VALUES('73', 'tr', 'new_product', 'Yeni Ürün');
INSERT INTO `translations` VALUES('74', 'tr', 'edit_product', 'Ürün Düzenle');
INSERT INTO `translations` VALUES('75', 'tr', 'product_added', 'Ürün başarıyla eklendi.');
INSERT INTO `translations` VALUES('76', 'tr', 'product_updated', 'Ürün başarıyla güncellendi.');
INSERT INTO `translations` VALUES('77', 'tr', 'product_deleted', 'Ürün silindi.');
INSERT INTO `translations` VALUES('78', 'tr', 'barcode_exists', 'Bu barkod zaten kayıtlı.');
INSERT INTO `translations` VALUES('79', 'tr', 'name_required', 'İlaç adı zorunludur.');
INSERT INTO `translations` VALUES('80', 'tr', 'price_required', 'Satış fiyatı 0\'dan büyük olmalıdır.');
INSERT INTO `translations` VALUES('81', 'tr', 'stock', 'Stok');
INSERT INTO `translations` VALUES('82', 'tr', 'sufficient', 'Yeterli');
INSERT INTO `translations` VALUES('83', 'tr', 'critical', 'Kritik');
INSERT INTO `translations` VALUES('84', 'tr', 'out_of_stock', 'Tükendi');
INSERT INTO `translations` VALUES('85', 'tr', 'box', 'Kutu');
INSERT INTO `translations` VALUES('86', 'tr', 'piece', 'Adet');
INSERT INTO `translations` VALUES('87', 'tr', 'bottle', 'Şişe');
INSERT INTO `translations` VALUES('88', 'tr', 'tube', 'Tüp');
INSERT INTO `translations` VALUES('89', 'tr', 'pack', 'Paket');
INSERT INTO `translations` VALUES('90', 'tr', 'blister', 'Blister');
INSERT INTO `translations` VALUES('91', 'tr', 'customer', 'Müşteri');
INSERT INTO `translations` VALUES('92', 'tr', 'first_name', 'Ad');
INSERT INTO `translations` VALUES('93', 'tr', 'last_name', 'Soyad');
INSERT INTO `translations` VALUES('94', 'tr', 'phone', 'Telefon');
INSERT INTO `translations` VALUES('95', 'tr', 'address', 'Adres');
INSERT INTO `translations` VALUES('96', 'tr', 'due_days', 'Vade (Gün)');
INSERT INTO `translations` VALUES('97', 'tr', 'debt', 'Borç');
INSERT INTO `translations` VALUES('98', 'tr', 'new_customer', 'Yeni Müşteri');
INSERT INTO `translations` VALUES('99', 'tr', 'edit_customer', 'Müşteri Düzenle');
INSERT INTO `translations` VALUES('100', 'tr', 'customer_detail', 'Müşteri Detayı');
INSERT INTO `translations` VALUES('101', 'tr', 'take_payment', 'Ödeme Al');
INSERT INTO `translations` VALUES('102', 'tr', 'payment_amount', 'Ödeme Tutarı');
INSERT INTO `translations` VALUES('103', 'tr', 'payment_method', 'Ödeme Yöntemi');
INSERT INTO `translations` VALUES('104', 'tr', 'cash', 'Nakit');
INSERT INTO `translations` VALUES('105', 'tr', 'card', 'Kart');
INSERT INTO `translations` VALUES('106', 'tr', 'transfer', 'Havale');
INSERT INTO `translations` VALUES('107', 'tr', 'other', 'Diğer');
INSERT INTO `translations` VALUES('108', 'tr', 'payment_history', 'Ödeme Geçmişi');
INSERT INTO `translations` VALUES('109', 'tr', 'sales_history', 'Satış Geçmişi');
INSERT INTO `translations` VALUES('110', 'tr', 'has_debt', 'Borçlu');
INSERT INTO `translations` VALUES('111', 'tr', 'no_debt', 'Borçsuz');
INSERT INTO `translations` VALUES('112', 'tr', 'sale', 'Satış');
INSERT INTO `translations` VALUES('113', 'tr', 'new_sale_title', 'Yeni Satış');
INSERT INTO `translations` VALUES('114', 'tr', 'cart', 'Sepet');
INSERT INTO `translations` VALUES('115', 'tr', 'add_to_cart', 'Sepete Ekle');
INSERT INTO `translations` VALUES('116', 'tr', 'quantity', 'Miktar');
INSERT INTO `translations` VALUES('117', 'tr', 'unit_price', 'Birim Fiyat');
INSERT INTO `translations` VALUES('118', 'tr', 'subtotal', 'Ara Toplam');
INSERT INTO `translations` VALUES('119', 'tr', 'discount', 'İskonto');
INSERT INTO `translations` VALUES('120', 'tr', 'discount_type', 'İskonto Türü');
INSERT INTO `translations` VALUES('121', 'tr', 'discount_pct', 'Yüzde (%)');
INSERT INTO `translations` VALUES('122', 'tr', 'discount_fixed', 'Sabit Tutar');
INSERT INTO `translations` VALUES('123', 'tr', 'none', 'Yok');
INSERT INTO `translations` VALUES('124', 'tr', 'net_total', 'Net Toplam');
INSERT INTO `translations` VALUES('125', 'tr', 'paid_amount', 'Ödenen');
INSERT INTO `translations` VALUES('126', 'tr', 'remaining', 'Kalan');
INSERT INTO `translations` VALUES('127', 'tr', 'complete_sale', 'Satışı Tamamla');
INSERT INTO `translations` VALUES('128', 'tr', 'invoice', 'Fatura');
INSERT INTO `translations` VALUES('129', 'tr', 'cancel_sale', 'Satışı İptal Et');
INSERT INTO `translations` VALUES('130', 'tr', 'sale_completed', 'Satış başarıyla tamamlandı.');
INSERT INTO `translations` VALUES('131', 'tr', 'sale_cancelled', 'Satış iptal edildi. Stok geri yüklendi.');
INSERT INTO `translations` VALUES('132', 'tr', 'customer_optional', 'Müşteri (Opsiyonel)');
INSERT INTO `translations` VALUES('133', 'tr', 'search_product', 'İlaç adı veya barkod ile ara...');
INSERT INTO `translations` VALUES('134', 'tr', 'search_customer', 'Müşteri ara...');
INSERT INTO `translations` VALUES('135', 'tr', 'quick_add_customer', 'Hızlı Müşteri Ekle');
INSERT INTO `translations` VALUES('136', 'tr', 'select_customer', 'Müşteri Seç');
INSERT INTO `translations` VALUES('137', 'tr', 'walk_in', 'Peşin');
INSERT INTO `translations` VALUES('138', 'tr', 'stock_entry_title', 'Stok Girişi');
INSERT INTO `translations` VALUES('139', 'tr', 'new_stock_entry', 'Yeni Stok Girişi');
INSERT INTO `translations` VALUES('140', 'tr', 'entry_qty', 'Giriş Miktarı');
INSERT INTO `translations` VALUES('141', 'tr', 'invoice_ref', 'Fatura / İrsaliye No');
INSERT INTO `translations` VALUES('142', 'tr', 'update_purchase_price', 'Alış fiyatını güncelle');
INSERT INTO `translations` VALUES('143', 'tr', 'recent_entries', 'Son Girişler');
INSERT INTO `translations` VALUES('144', 'tr', 'stock_entry_done', 'Stok girişi yapıldı.');
INSERT INTO `translations` VALUES('145', 'tr', 'movement_in', 'Giriş');
INSERT INTO `translations` VALUES('146', 'tr', 'movement_out', 'Çıkış');
INSERT INTO `translations` VALUES('147', 'tr', 'movement_adjust', 'Düzeltme');
INSERT INTO `translations` VALUES('148', 'tr', 'movement_convert', 'Dönüşüm');
INSERT INTO `translations` VALUES('149', 'tr', 'reference', 'Referans');
INSERT INTO `translations` VALUES('150', 'tr', 'source_product', 'Kaynak Ürün');
INSERT INTO `translations` VALUES('151', 'tr', 'target_product', 'Hedef Ürün');
INSERT INTO `translations` VALUES('152', 'tr', 'convert_ratio', 'Dönüşüm Oranı');
INSERT INTO `translations` VALUES('153', 'tr', 'period', 'Dönem');
INSERT INTO `translations` VALUES('154', 'tr', 'today', 'Bugün');
INSERT INTO `translations` VALUES('155', 'tr', 'this_week', 'Bu Hafta');
INSERT INTO `translations` VALUES('156', 'tr', 'this_month', 'Bu Ay');
INSERT INTO `translations` VALUES('157', 'tr', 'this_year', 'Bu Yıl');
INSERT INTO `translations` VALUES('158', 'tr', 'custom', 'Özel');
INSERT INTO `translations` VALUES('159', 'tr', 'start_date', 'Başlangıç');
INSERT INTO `translations` VALUES('160', 'tr', 'end_date', 'Bitiş');
INSERT INTO `translations` VALUES('161', 'tr', 'apply', 'Uygula');
INSERT INTO `translations` VALUES('162', 'tr', 'gross_revenue', 'Brüt Ciro');
INSERT INTO `translations` VALUES('163', 'tr', 'net_revenue', 'Net Ciro');
INSERT INTO `translations` VALUES('164', 'tr', 'collected', 'Tahsil Edilen');
INSERT INTO `translations` VALUES('165', 'tr', 'uncollected', 'Tahsilsiz');
INSERT INTO `translations` VALUES('166', 'tr', 'cost', 'Maliyet');
INSERT INTO `translations` VALUES('167', 'tr', 'margin', 'Marj');
INSERT INTO `translations` VALUES('168', 'tr', 'category_dist', 'Kategori Dağılımı');
INSERT INTO `translations` VALUES('169', 'tr', 'top_customers', 'En İyi Müşteriler');
INSERT INTO `translations` VALUES('170', 'tr', 'daily_chart', 'Günlük Satış Grafiği');
INSERT INTO `translations` VALUES('171', 'tr', 'spent', 'Harcama');
INSERT INTO `translations` VALUES('172', 'tr', 'language', 'Dil');
INSERT INTO `translations` VALUES('173', 'tr', 'currency', 'Para Birimi');
INSERT INTO `translations` VALUES('174', 'tr', 'settings_saved', 'Ayarlar kaydedildi.');
INSERT INTO `translations` VALUES('175', 'tr', 'appearance', 'Görünüm');
INSERT INTO `translations` VALUES('176', 'tr', 'preferences', 'Tercihler');
INSERT INTO `translations` VALUES('177', 'tr', 'default_currency', 'Varsayılan Para Birimi');
INSERT INTO `translations` VALUES('178', 'tr', 'select_language', 'Dil Seçin');
INSERT INTO `translations` VALUES('179', 'tr', 'select_currency', 'Para Birimi Seçin');
INSERT INTO `translations` VALUES('180', 'tr', 'log_action', 'İşlem');
INSERT INTO `translations` VALUES('181', 'tr', 'log_user', 'Kullanıcı');
INSERT INTO `translations` VALUES('182', 'tr', 'log_ip', 'IP');
INSERT INTO `translations` VALUES('183', 'tr', 'log_detail', 'Detay');
INSERT INTO `translations` VALUES('184', 'tr', 'log_time', 'Tarih / Saat');
INSERT INTO `translations` VALUES('185', 'tr', 'added', 'Eklendi');
INSERT INTO `translations` VALUES('186', 'tr', 'updated', 'Güncellendi');
INSERT INTO `translations` VALUES('187', 'tr', 'deleted', 'Silindi');
INSERT INTO `translations` VALUES('188', 'tr', 'payment', 'Ödeme');
INSERT INTO `translations` VALUES('189', 'tr', 'txt__value', '\"\n                    value=\"');
INSERT INTO `translations` VALUES('190', 'tr', 'txt__onclickreturn_confirm', '\"\n                                        onclick=\"return confirm(\'');
INSERT INTO `translations` VALUES('191', 'tr', 'txt__onclickreturn_confirmbu_yedek', '\"\n                                        onclick=\"return confirm(\'Bu yedek silinecek onaylıyor musunuz?\');\">');
INSERT INTO `translations` VALUES('192', 'tr', 'txt__stylecolorvarwarningbackgroun', '\"\n                                                style=\"color:var(--warning);background:none;border:none;cursor:pointer;\">');
INSERT INTO `translations` VALUES('193', 'tr', 'txt__stylefontsize18pxpadding14px', '\" style=\"font-size:18px;padding:14px;\">');
INSERT INTO `translations` VALUES('194', 'tr', 'txt__maxlength10_styletexttransfor', '\"\n                                maxlength=\"10\" style=\"text-transform:uppercase;\">');
INSERT INTO `translations` VALUES('195', 'tr', 'txt__maxlength10', '\"\n                                maxlength=\"10\">');
INSERT INTO `translations` VALUES('196', 'tr', 'txt__required', '\" required>');
INSERT INTO `translations` VALUES('197', 'tr', 'txt__autocompleteoff', '\" autocomplete=\"off\">');
INSERT INTO `translations` VALUES('198', 'tr', 'txt_hizli_tahsilat', 'Hizli Tahsilat');
INSERT INTO `translations` VALUES('199', 'tr', 'txt_musteri_secin', 'Select Customer');
INSERT INTO `translations` VALUES('200', 'tr', 'txt__musteri_ara_sec', ' Search Customer');
INSERT INTO `translations` VALUES('201', 'tr', 'txt_guncel_cari_durum', 'Guncel Cari Durum');
INSERT INTO `translations` VALUES('202', 'tr', 'txt_tahsilat_avans_tutari', 'Tahsilat / Avans Tutarı');
INSERT INTO `translations` VALUES('203', 'tr', 'txt_orn_50000', 'Orn 50000');
INSERT INTO `translations` VALUES('204', 'tr', 'txt_odeme_yontemi', 'Odeme Yontemi');
INSERT INTO `translations` VALUES('205', 'tr', 'txt_nakit', 'Nakit');
INSERT INTO `translations` VALUES('206', 'tr', 'txt_kredibanka_karti', 'Kredibanka Karti');
INSERT INTO `translations` VALUES('207', 'tr', 'txt_havaleeft', 'Havaleeft');
INSERT INTO `translations` VALUES('208', 'tr', 'txt_diger', 'Diger');
INSERT INTO `translations` VALUES('209', 'tr', 'txt_aciklama_not', 'Aciklama Not');
INSERT INTO `translations` VALUES('210', 'tr', 'txt_orn_ekim_ayi_avansi_eski_borc', 'Orn Ekim Ayi Avansi Eski Borc');
INSERT INTO `translations` VALUES('211', 'tr', 'txt_tahsilati_tamamla', 'Tahsilati Tamamla');
INSERT INTO `translations` VALUES('212', 'tr', 'txt_cari_hesap_ekstresi', 'Cari Hesap Ekstresi');
INSERT INTO `translations` VALUES('213', 'tr', 'txt_cikti_al_pdf', 'Cikti Al Pdf');
INSERT INTO `translations` VALUES('214', 'tr', 'txt_id', 'Id');
INSERT INTO `translations` VALUES('215', 'tr', 'txt_telefon', 'Telefon');
INSERT INTO `translations` VALUES('216', 'tr', 'txt_adres', 'Adres');
INSERT INTO `translations` VALUES('217', 'tr', 'txt_tarih', 'Tarih');
INSERT INTO `translations` VALUES('218', 'tr', 'txt_islem_turu_aciklama', 'Islem Turu Aciklama');
INSERT INTO `translations` VALUES('219', 'tr', 'txt_borc_satis', 'Borc Satış');
INSERT INTO `translations` VALUES('220', 'tr', 'txt_alacak_tahsilat', 'Alacak Tahsilat');
INSERT INTO `translations` VALUES('221', 'tr', 'txt_yuruyen_bakiye', 'Yuruyen Bakiye');
INSERT INTO `translations` VALUES('222', 'tr', 'txt_cari_hareket_bulunamadi', 'Cari Hareket Bulunamadi');
INSERT INTO `translations` VALUES('223', 'tr', 'txt_satis_fatura', 'Satis Fatura');
INSERT INTO `translations` VALUES('224', 'tr', 'txt_tahsilat_avans_makb', 'Tahsilat / Avans Makbuzu');
INSERT INTO `translations` VALUES('225', 'tr', 'txt_not', 'Not');
INSERT INTO `translations` VALUES('226', 'tr', 'txt_kategori_adi', 'Kategori Adı');
INSERT INTO `translations` VALUES('227', 'tr', 'txt_aciklama', 'Aciklama');
INSERT INTO `translations` VALUES('228', 'tr', 'txt_kisa_aciklama', 'Kisa Aciklama');
INSERT INTO `translations` VALUES('229', 'tr', 'txt_iptal', 'Iptal');
INSERT INTO `translations` VALUES('230', 'tr', 'txt_kategoriler', 'Kategoriler');
INSERT INTO `translations` VALUES('231', 'tr', 'txt_urun', 'Urun');
INSERT INTO `translations` VALUES('232', 'tr', 'txt_islem', 'Islem');
INSERT INTO `translations` VALUES('233', 'tr', 'txt_kategori_yok', 'Kategori Yok');
INSERT INTO `translations` VALUES('234', 'tr', 'txt_kategori', 'Kategori');
INSERT INTO `translations` VALUES('235', 'tr', 'txt__sec', ' Sec');
INSERT INTO `translations` VALUES('236', 'tr', 'txt_para_birimi', 'Para Birimi');
INSERT INTO `translations` VALUES('237', 'tr', 'txt_alis_fiyati', 'Alis Fiyati');
INSERT INTO `translations` VALUES('238', 'tr', 'txt_satis_fiyati', 'Satis Fiyati');
INSERT INTO `translations` VALUES('239', 'tr', 'txt_birim', 'Birim');
INSERT INTO `translations` VALUES('240', 'tr', 'txt_mevcut_stok', 'Mevcut Stok');
INSERT INTO `translations` VALUES('241', 'tr', 'txt_kritik_stok_seviyesi', 'Kritik Stok Seviyesi');
INSERT INTO `translations` VALUES('242', 'tr', 'txt_urun_aktif', 'Urun Aktif');
INSERT INTO `translations` VALUES('243', 'tr', 'txt_urun_gorseli', 'Urun Gorseli');
INSERT INTO `translations` VALUES('244', 'tr', 'txt_gorsel_sec_veya_surukle_birak', 'Gorsel Sec Veya Surukle Birak');
INSERT INTO `translations` VALUES('245', 'tr', 'txt_jpg_png_webp_max_3mb', 'Jpg Png Webp Max 3mb');
INSERT INTO `translations` VALUES('246', 'tr', 'txt_stok_degeri', 'Stock Value');
INSERT INTO `translations` VALUES('247', 'tr', 'txt_hizli_donem', 'Hizli Donem');
INSERT INTO `translations` VALUES('248', 'tr', 'txt_baslangic', 'Baslangic');
INSERT INTO `translations` VALUES('249', 'tr', 'txt_bitis', 'Bitis');
INSERT INTO `translations` VALUES('250', 'tr', 'txt_uygula', 'Uygula');
INSERT INTO `translations` VALUES('251', 'tr', 'txt_disa_aktar', 'Disa Aktar');
INSERT INTO `translations` VALUES('252', 'tr', 'txt_csv', 'Csv');
INSERT INTO `translations` VALUES('253', 'tr', 'txt_yazdir', 'Yazdir');
INSERT INTO `translations` VALUES('254', 'tr', 'txt_donem', 'Donem');
INSERT INTO `translations` VALUES('255', 'tr', 'txt_satis_adedi', 'Satis Adedi');
INSERT INTO `translations` VALUES('256', 'tr', 'txt_net_ciro', 'Net Ciro');
INSERT INTO `translations` VALUES('257', 'tr', 'txt_kr', 'Kr');
INSERT INTO `translations` VALUES('258', 'tr', 'txt_satilan_maliyet', 'Satilan Maliyet');
INSERT INTO `translations` VALUES('259', 'tr', 'txt_tahsil_edilen', 'Tahsil Edilen');
INSERT INTO `translations` VALUES('260', 'tr', 'txt_tahsilsiz', 'Tahsilsiz');
INSERT INTO `translations` VALUES('261', 'tr', 'txt_gunluk_satis_grafigi', 'Gunluk Satış Grafigi');
INSERT INTO `translations` VALUES('262', 'tr', 'txt_en_cok_satan_urunler', 'En Cok Satan Ürünler');
INSERT INTO `translations` VALUES('263', 'tr', 'txt_satilan', 'Satilan');
INSERT INTO `translations` VALUES('264', 'tr', 'txt_ciro', 'Ciro');
INSERT INTO `translations` VALUES('265', 'tr', 'txt_veri_yok', 'Veri Yok');
INSERT INTO `translations` VALUES('266', 'tr', 'txt_kategori_dagilimi', 'Kategori Dagilimi');
INSERT INTO `translations` VALUES('267', 'tr', 'txt_en_iyi_musteriler', 'En Iyi Müşteriler');
INSERT INTO `translations` VALUES('268', 'tr', 'txt_musteri', 'Musteri');
INSERT INTO `translations` VALUES('269', 'tr', 'txt_satis', 'Satis');
INSERT INTO `translations` VALUES('270', 'tr', 'txt_harcama', 'Harcama');
INSERT INTO `translations` VALUES('271', 'tr', 'txt_borc', 'Borc');
INSERT INTO `translations` VALUES('272', 'tr', 'txt_ara', 'Ara');
INSERT INTO `translations` VALUES('273', 'tr', 'txt_islem_detay_veya_kullanici', 'Islem Detay Veya Kullanici');
INSERT INTO `translations` VALUES('274', 'tr', 'txt_filtrele', 'Filtrele');
INSERT INTO `translations` VALUES('275', 'tr', 'txt_9899bf', '9899bf');
INSERT INTO `translations` VALUES('276', 'tr', 'txt_islem_loglari', 'Islem Loglari');
INSERT INTO `translations` VALUES('277', 'tr', 'txt_toplam', 'Toplam');
INSERT INTO `translations` VALUES('278', 'tr', 'txt_kayit', 'Kayit');
INSERT INTO `translations` VALUES('279', 'tr', 'txt_tarih_saat', 'Tarih Saat');
INSERT INTO `translations` VALUES('280', 'tr', 'txt_detay', 'Detay');
INSERT INTO `translations` VALUES('281', 'tr', 'txt_kullanici', 'Kullanici');
INSERT INTO `translations` VALUES('282', 'tr', 'txt_ip', 'Ip');
INSERT INTO `translations` VALUES('283', 'tr', 'txt_log_bulunamadi', 'Log Bulunamadi');
INSERT INTO `translations` VALUES('284', 'tr', 'txt_urun_ekle', 'Urun Ekle');
INSERT INTO `translations` VALUES('285', 'tr', 'txt_ilac_adi_veya_barkod_ara', 'Ilac Adi Veya Barkod Ara');
INSERT INTO `translations` VALUES('286', 'tr', 'txt_tum_kategoriler', 'Tum Kategoriler');
INSERT INTO `translations` VALUES('287', 'tr', 'txt_stok_durumu', 'Stok Durumu');
INSERT INTO `translations` VALUES('288', 'tr', 'txt_stokta_var', 'Stokta Var');
INSERT INTO `translations` VALUES('289', 'tr', 'txt_stokta_yok', 'Stokta Yok');
INSERT INTO `translations` VALUES('290', 'tr', 'txt_sepet', 'Sepet');
INSERT INTO `translations` VALUES('291', 'tr', 'txt_temizle', 'Temizle');
INSERT INTO `translations` VALUES('292', 'tr', 'txt_ilac', 'Ilac');
INSERT INTO `translations` VALUES('293', 'tr', 'txt_birim_fiyat', 'Birim Fiyat');
INSERT INTO `translations` VALUES('294', 'tr', 'txt_adet', 'Adet');
INSERT INTO `translations` VALUES('295', 'tr', 'txt_sepet_bos_urun_ekleyin', 'Sepet Bos Ürün Ekleyin');
INSERT INTO `translations` VALUES('296', 'tr', 'txt_musteri_ara', 'Musteri Ara');
INSERT INTO `translations` VALUES('297', 'tr', 'txt_ad_soyad_veya_telefon', 'Ad Soyad Veya Telefon');
INSERT INTO `translations` VALUES('298', 'tr', 'txt_kaldir', 'Kaldir');
INSERT INTO `translations` VALUES('299', 'tr', 'txt_hizli_musteri_ekle', 'Hizli Müşteri Ekle');
INSERT INTO `translations` VALUES('300', 'tr', 'txt_ad', 'Ad');
INSERT INTO `translations` VALUES('301', 'tr', 'txt_soyad', 'Soyad');
INSERT INTO `translations` VALUES('302', 'tr', 'txt_ekle_ve_sec', 'Ekle Ve Sec');
INSERT INTO `translations` VALUES('303', 'tr', 'txt_odeme_ozeti', 'Odeme Ozeti');
INSERT INTO `translations` VALUES('304', 'tr', 'txt_iskonto', 'Iskonto');
INSERT INTO `translations` VALUES('305', 'tr', 'txt_yok', 'Yok');
INSERT INTO `translations` VALUES('306', 'tr', 'txt_yuzde', 'Yuzde');
INSERT INTO `translations` VALUES('307', 'tr', 'txt_sabit', 'Sabit');
INSERT INTO `translations` VALUES('308', 'tr', 'txt_ara_toplam', 'Ara Toplam');
INSERT INTO `translations` VALUES('309', 'tr', 'txt_net_tutar', 'Net Tutar');
INSERT INTO `translations` VALUES('310', 'tr', 'txt_alinan_odeme', 'Alinan Odeme');
INSERT INTO `translations` VALUES('311', 'tr', 'txt_tamamini_ode', 'Tamamini Ode');
INSERT INTO `translations` VALUES('312', 'tr', 'txt_veresiye', 'Veresiye');
INSERT INTO `translations` VALUES('313', 'tr', 'txt_kalan_borc', 'Kalan Borc');
INSERT INTO `translations` VALUES('314', 'tr', 'txt_musteri_hesabina_borc_olarak_y', 'Musteri Hesabina Borc Olarak Y');
INSERT INTO `translations` VALUES('315', 'tr', 'txt_satis_notu', 'Satis Notu');
INSERT INTO `translations` VALUES('316', 'tr', 'txt_satisi_tamamla', 'Satisi Tamamla');
INSERT INTO `translations` VALUES('317', 'tr', 'txt_sistem_yedekleri', 'Sistem Yedekleri');
INSERT INTO `translations` VALUES('318', 'tr', 'txt_veritabaninin_tam_yedegini_ali', 'Veritabaninin Tam Yedegini Ali');
INSERT INTO `translations` VALUES('319', 'tr', 'txt_yeni_yedek_al', 'Yeni Yedek Al');
INSERT INTO `translations` VALUES('320', 'tr', 'txt_mevcut_yedekler', 'Mevcut Yedekler');
INSERT INTO `translations` VALUES('321', 'tr', 'txt_dosya_adi', 'Dosya Adi');
INSERT INTO `translations` VALUES('322', 'tr', 'txt_boyut', 'Boyut');
INSERT INTO `translations` VALUES('323', 'tr', 'txt_olusturulma_tarihi', 'Olusturulma Tarihi');
INSERT INTO `translations` VALUES('324', 'tr', 'txt_islemler', 'Islemler');
INSERT INTO `translations` VALUES('325', 'tr', 'txt_hic_yedek_bulunamadi', 'Hic Yedek Bulunamadi');
INSERT INTO `translations` VALUES('326', 'tr', 'txt_geri_yukle', 'Geri Yukle');
INSERT INTO `translations` VALUES('327', 'tr', 'txt_veritabanina_yazilacaktir_mevc', 'Veritabanina Yazilacaktir Mevc');
INSERT INTO `translations` VALUES('328', 'tr', 'txt__classbtnsmicon_btnedit_me1_ti', ' Classbtnsmicon Btnedit Me1 Ti');
INSERT INTO `translations` VALUES('329', 'tr', 'txt_sil', 'Sil');
INSERT INTO `translations` VALUES('330', 'tr', 'txt_uyari', 'Uyari');
INSERT INTO `translations` VALUES('331', 'tr', 'txt_geri_yukleme_islemi_mevcut_tum', 'Geri Yukleme Islemi Mevcut Tum');
INSERT INTO `translations` VALUES('332', 'tr', 'txt_geri_alinamaz', 'Geri Alinamaz');
INSERT INTO `translations` VALUES('333', 'tr', 'txt_code', 'Code');
INSERT INTO `translations` VALUES('334', 'tr', 'txt_symbol', 'Symbol');
INSERT INTO `translations` VALUES('335', 'tr', 'txt_1_usd', '1 Usd');
INSERT INTO `translations` VALUES('336', 'tr', 'txt_rate_date', 'Rate Date');
INSERT INTO `translations` VALUES('337', 'tr', 'txt_1000000_base', '1000000 Base');
INSERT INTO `translations` VALUES('338', 'tr', 'txt__no_rate', ' No Rate');
INSERT INTO `translations` VALUES('339', 'tr', 'txt_base', 'Base');
INSERT INTO `translations` VALUES('340', 'tr', 'txt_rate_history', 'Rate History');
INSERT INTO `translations` VALUES('341', 'tr', 'txt_date', 'Date');
INSERT INTO `translations` VALUES('342', 'tr', 'txt_entry_time', 'Entry Time');
INSERT INTO `translations` VALUES('343', 'tr', 'txt_set_exchange_rate', 'Set Exchange Rate');
INSERT INTO `translations` VALUES('344', 'tr', 'txt__select', ' Select');
INSERT INTO `translations` VALUES('345', 'tr', 'txt_enter_how_many_units_of_this_c', 'Enter How Many Units Of This C');
INSERT INTO `translations` VALUES('346', 'tr', 'txt_effective_date', 'Effective Date');
INSERT INTO `translations` VALUES('347', 'tr', 'txt_rate_applies_from_this_date_fo', 'Rate Applies From This Date Fo');
INSERT INTO `translations` VALUES('348', 'tr', 'txt_set_rate', 'Set Rate');
INSERT INTO `translations` VALUES('349', 'tr', 'txt_add_currency', 'Add Currency');
INSERT INTO `translations` VALUES('350', 'tr', 'txt_position', 'Position');
INSERT INTO `translations` VALUES('351', 'tr', 'txt_currency_name', 'Currency Name');
INSERT INTO `translations` VALUES('352', 'tr', 'txt_saudi_riyal', 'Saudi Riyal');
INSERT INTO `translations` VALUES('353', 'tr', 'txt_decimal_sep', 'Decimal Sep');
INSERT INTO `translations` VALUES('354', 'tr', 'txt_thousand_sep', 'Thousand Sep');
INSERT INTO `translations` VALUES('355', 'tr', 'txt_initial_rate_1_usd', 'Initial Rate 1 Usd');
INSERT INTO `translations` VALUES('356', 'tr', 'txt_optional', 'Optional');
INSERT INTO `translations` VALUES('357', 'tr', 'txt_leave_empty_if_youll_set_the_r', 'Leave Empty If Youll Set The R');
INSERT INTO `translations` VALUES('358', 'tr', 'txt_yeni_donusum', 'Yeni Donusum');
INSERT INTO `translations` VALUES('359', 'tr', 'txt_ornek', 'Ornek');
INSERT INTO `translations` VALUES('360', 'tr', 'txt_1_kutuluk_aspirin_kutu_urununu', '1 Kutuluk Aspirin Kutu Ürünunu');
INSERT INTO `translations` VALUES('361', 'tr', 'txt_kaynak_urun_parcalanacak', 'Kaynak Ürün Parcalanacak');
INSERT INTO `translations` VALUES('362', 'tr', 'txt__urun_sec', ' Ürün Sec');
INSERT INTO `translations` VALUES('363', 'tr', 'txt_alinacak_miktar_kaynak', 'Alinacak Miktar Kaynak');
INSERT INTO `translations` VALUES('364', 'tr', 'txt_kac_kutu', 'Kac Kutu');
INSERT INTO `translations` VALUES('365', 'tr', 'txt_hedef_urun_eklenecek', 'Hedef Ürün Eklenecek');
INSERT INTO `translations` VALUES('366', 'tr', 'txt_mevcut', 'Mevcut');
INSERT INTO `translations` VALUES('367', 'tr', 'txt_eklenecek_miktar_hedef', 'Eklenecek Miktar Hedef');
INSERT INTO `translations` VALUES('368', 'tr', 'txt_kac_adet_eklenecek', 'Kac Adet Eklenecek');
INSERT INTO `translations` VALUES('369', 'tr', 'txt_donusum_aciklamasi', 'Donusum Aciklamasi');
INSERT INTO `translations` VALUES('370', 'tr', 'txt_donusumu_gerceklestir', 'Donusumu Gerceklestir');
INSERT INTO `translations` VALUES('371', 'tr', 'txt_son_donusumler', 'Son Donusumler');
INSERT INTO `translations` VALUES('372', 'tr', 'txt_miktar', 'Miktar');
INSERT INTO `translations` VALUES('373', 'tr', 'txt_henuz_donusum_yok', 'Henuz Donusum Yok');
INSERT INTO `translations` VALUES('374', 'tr', 'txt_yeni_stok_girisi', 'Yeni Stok Girisi');
INSERT INTO `translations` VALUES('375', 'tr', 'txt_fatura_irsaliye_no', 'Fatura Irsaliye No');
INSERT INTO `translations` VALUES('376', 'tr', 'txt_orn_ftr20260042', 'Orn Ftr20260042');
INSERT INTO `translations` VALUES('377', 'tr', 'txt_alis_fiyatini_guncelle', 'Alis Fiyatini Guncelle');
INSERT INTO `translations` VALUES('378', 'tr', 'txt_yeni_alis_fiyati', 'Yeni Alis Fiyati');
INSERT INTO `translations` VALUES('379', 'tr', 'txt_istege_bagli_mevcut_resmi_gunc', 'Istege Bagli Mevcut Resmi Gunc');
INSERT INTO `translations` VALUES('380', 'tr', 'txt_aciklama_tedarikci_bilgisi_vb', 'Aciklama Tedarikci Bilgisi Vb');
INSERT INTO `translations` VALUES('381', 'tr', 'txt_stok_girisi_yap', 'Stok Girisi Yap');
INSERT INTO `translations` VALUES('382', 'tr', 'txt_son_stok_girisleri', 'Son Stok Girisleri');
INSERT INTO `translations` VALUES('383', 'tr', 'txt_tumunu_gor', 'Tumunu Gor');
INSERT INTO `translations` VALUES('384', 'tr', 'txt_referans', 'Referans');
INSERT INTO `translations` VALUES('385', 'tr', 'txt_henuz_stok_girisi_yapilmamis', 'Henuz Stok Girisi Yapilmamis');
INSERT INTO `translations` VALUES('386', 'tr', 'txt_tumu', 'Tumu');
INSERT INTO `translations` VALUES('387', 'tr', 'txt_tur', 'Tur');
INSERT INTO `translations` VALUES('388', 'tr', 'txt_giris', 'Giris');
INSERT INTO `translations` VALUES('389', 'tr', 'txt_cikis', 'Cikis');
INSERT INTO `translations` VALUES('390', 'tr', 'txt_duzeltme', 'Duzeltme');
INSERT INTO `translations` VALUES('391', 'tr', 'txt_donusum', 'Donusum');
INSERT INTO `translations` VALUES('392', 'tr', 'txt_hareket_listesi', 'Hareket Listesi');
INSERT INTO `translations` VALUES('393', 'tr', 'txt_kayit_yok', 'Kayit Yok');
INSERT INTO `translations` VALUES('394', 'tr', 'txt_uygulama_temasi_ui_theme', 'Uygulama Teması (UI Theme)');
INSERT INTO `translations` VALUES('395', 'tr', 'txt_ozel_renkler_custom', 'Özel Renkler (Custom)');
INSERT INTO `translations` VALUES('396', 'tr', 'txt_arkaplan_rengi_body', 'Arkaplan Rengi (Body)');
INSERT INTO `translations` VALUES('397', 'tr', 'txt_sol_menu_sidebar', 'Sol Menü (Sidebar)');
INSERT INTO `translations` VALUES('398', 'tr', 'txt_kart_arkaplani_panel', 'Kart Arkaplanı (Panel)');
INSERT INTO `translations` VALUES('399', 'tr', 'txt_ana_vurgu_accent', 'Ana Vurgu (Accent)');
INSERT INTO `translations` VALUES('400', 'tr', 'txt_yazi_rengi_text', 'Yazı Rengi (Text)');
INSERT INTO `translations` VALUES('401', 'tr', 'txt_site_logosu', 'Site Logosu');
INSERT INTO `translations` VALUES('402', 'tr', 'txt_varsayilan_urun_gorseli', 'Varsayılan Ürün Görseli');
INSERT INTO `translations` VALUES('403', 'tr', 'txt_preview_onizleme', 'Önizleme');
INSERT INTO `translations` VALUES('404', 'tr', 'txt_management', ' Yönetimi');
INSERT INTO `translations` VALUES('405', 'tr', 'txt_addremove_currencies_set_daily', 'Para birimi ekleyin/çıkarın, günlük kurları girin.');
INSERT INTO `translations` VALUES('406', 'tr', 'menu_users', 'Kullanıcı Yönetimi');
INSERT INTO `translations` VALUES('407', 'tr', 'txt_login', 'Giriş Yap');
INSERT INTO `translations` VALUES('408', 'tr', 'txt_logout', 'Çıkış Yap');
INSERT INTO `translations` VALUES('409', 'tr', 'txt_username', 'Kullanıcı Adı');
INSERT INTO `translations` VALUES('410', 'tr', 'txt_password', 'Şifre');
INSERT INTO `translations` VALUES('411', 'tr', 'txt_profile', 'Profilim');
INSERT INTO `translations` VALUES('412', 'tr', 'txt_session_timeout', 'Oturum Süresi');
INSERT INTO `translations` VALUES('413', 'en', 'app_name', 'BAH Pharmacy');
INSERT INTO `translations` VALUES('414', 'en', 'dashboard', 'Dashboard');
INSERT INTO `translations` VALUES('415', 'en', 'settings', 'Settings');
INSERT INTO `translations` VALUES('416', 'en', 'save', 'Save');
INSERT INTO `translations` VALUES('417', 'en', 'cancel', 'Cancel');
INSERT INTO `translations` VALUES('418', 'en', 'delete', 'Delete');
INSERT INTO `translations` VALUES('419', 'en', 'edit', 'Edit');
INSERT INTO `translations` VALUES('420', 'en', 'add', 'Add');
INSERT INTO `translations` VALUES('421', 'en', 'search', 'Search');
INSERT INTO `translations` VALUES('422', 'en', 'filter', 'Filter');
INSERT INTO `translations` VALUES('423', 'en', 'back', 'Back');
INSERT INTO `translations` VALUES('424', 'en', 'close', 'Close');
INSERT INTO `translations` VALUES('425', 'en', 'yes', 'Yes');
INSERT INTO `translations` VALUES('426', 'en', 'no', 'No');
INSERT INTO `translations` VALUES('427', 'en', 'actions', 'Actions');
INSERT INTO `translations` VALUES('428', 'en', 'detail', 'Detail');
INSERT INTO `translations` VALUES('429', 'en', 'all', 'All');
INSERT INTO `translations` VALUES('430', 'en', 'date', 'Date');
INSERT INTO `translations` VALUES('431', 'en', 'note', 'Note');
INSERT INTO `translations` VALUES('432', 'en', 'status', 'Status');
INSERT INTO `translations` VALUES('433', 'en', 'active', 'Active');
INSERT INTO `translations` VALUES('434', 'en', 'passive', 'Passive');
INSERT INTO `translations` VALUES('435', 'en', 'print', 'Print');
INSERT INTO `translations` VALUES('436', 'en', 'export_csv', 'Export CSV');
INSERT INTO `translations` VALUES('437', 'en', 'error', 'Error');
INSERT INTO `translations` VALUES('438', 'en', 'success', 'Success');
INSERT INTO `translations` VALUES('439', 'en', 'confirm_delete', 'Are you sure you want to delete?');
INSERT INTO `translations` VALUES('440', 'en', 'no_data', 'No data found.');
INSERT INTO `translations` VALUES('441', 'en', 'total', 'Total');
INSERT INTO `translations` VALUES('442', 'en', 'menu_main', 'MAIN MENU');
INSERT INTO `translations` VALUES('443', 'en', 'menu_stock', 'STOCK');
INSERT INTO `translations` VALUES('444', 'en', 'menu_customer_sales', 'CUSTOMER & SALES');
INSERT INTO `translations` VALUES('445', 'en', 'menu_reports', 'REPORTS');
INSERT INTO `translations` VALUES('446', 'en', 'products', 'Products');
INSERT INTO `translations` VALUES('447', 'en', 'categories', 'Categories');
INSERT INTO `translations` VALUES('448', 'en', 'stock_entry', 'Stock Entry');
INSERT INTO `translations` VALUES('449', 'en', 'stock_movements', 'Stock Movements');
INSERT INTO `translations` VALUES('450', 'en', 'stock_convert', 'Stock Conversion');
INSERT INTO `translations` VALUES('451', 'en', 'customers', 'Customers');
INSERT INTO `translations` VALUES('452', 'en', 'sales', 'Sales');
INSERT INTO `translations` VALUES('453', 'en', 'new_sale', 'New Sale');
INSERT INTO `translations` VALUES('454', 'en', 'reports', 'Reports');
INSERT INTO `translations` VALUES('455', 'en', 'action_logs', 'Action Logs');
INSERT INTO `translations` VALUES('456', 'en', 'stock_report', 'Stock Report');
INSERT INTO `translations` VALUES('457', 'en', 'today_sales', 'Today\'s Sales');
INSERT INTO `translations` VALUES('458', 'en', 'monthly_revenue', 'Monthly Revenue');
INSERT INTO `translations` VALUES('459', 'en', 'total_products', 'Total Products');
INSERT INTO `translations` VALUES('460', 'en', 'critical_stock', 'Critical Stock');
INSERT INTO `translations` VALUES('461', 'en', 'total_customers', 'Total Customers');
INSERT INTO `translations` VALUES('462', 'en', 'total_debt', 'Total Debt');
INSERT INTO `translations` VALUES('463', 'en', 'today_profit', 'Today\'s Profit');
INSERT INTO `translations` VALUES('464', 'en', 'monthly_profit', 'Monthly Profit');
INSERT INTO `translations` VALUES('465', 'en', 'recent_sales', 'Recent Sales');
INSERT INTO `translations` VALUES('466', 'en', 'low_stock_alert', 'Low Stock Alerts');
INSERT INTO `translations` VALUES('467', 'en', 'sales_chart', 'Sales Chart (Last 7 Days)');
INSERT INTO `translations` VALUES('468', 'en', 'top_products', 'Top Selling Products');
INSERT INTO `translations` VALUES('469', 'en', 'revenue', 'Revenue');
INSERT INTO `translations` VALUES('470', 'en', 'profit', 'Profit');
INSERT INTO `translations` VALUES('471', 'en', 'sale_count', 'Sale Count');
INSERT INTO `translations` VALUES('472', 'en', 'quick_actions', 'Quick Actions');
INSERT INTO `translations` VALUES('473', 'en', 'product_name', 'Product Name');
INSERT INTO `translations` VALUES('474', 'en', 'barcode', 'Barcode');
INSERT INTO `translations` VALUES('475', 'en', 'sku', 'SKU Code');
INSERT INTO `translations` VALUES('476', 'en', 'dosage_form', 'Dosage Form');
INSERT INTO `translations` VALUES('477', 'en', 'category', 'Category');
INSERT INTO `translations` VALUES('478', 'en', 'purchase_price', 'Purchase Price');
INSERT INTO `translations` VALUES('479', 'en', 'sale_price', 'Sale Price');
INSERT INTO `translations` VALUES('480', 'en', 'unit', 'Unit');
INSERT INTO `translations` VALUES('481', 'en', 'stock_qty', 'Stock Qty');
INSERT INTO `translations` VALUES('482', 'en', 'critical_level', 'Critical Level');
INSERT INTO `translations` VALUES('483', 'en', 'product_image', 'Product Image');
INSERT INTO `translations` VALUES('484', 'en', 'product_active', 'Product Active');
INSERT INTO `translations` VALUES('485', 'en', 'new_product', 'New Product');
INSERT INTO `translations` VALUES('486', 'en', 'edit_product', 'Edit Product');
INSERT INTO `translations` VALUES('487', 'en', 'product_added', 'Product added successfully.');
INSERT INTO `translations` VALUES('488', 'en', 'product_updated', 'Product updated successfully.');
INSERT INTO `translations` VALUES('489', 'en', 'product_deleted', 'Product deleted successfully.');
INSERT INTO `translations` VALUES('490', 'en', 'barcode_exists', 'This barcode is already registered.');
INSERT INTO `translations` VALUES('491', 'en', 'name_required', 'Product name is required.');
INSERT INTO `translations` VALUES('492', 'en', 'price_required', 'Sale price must be greater than 0.');
INSERT INTO `translations` VALUES('493', 'en', 'stock', 'Stock');
INSERT INTO `translations` VALUES('494', 'en', 'sufficient', 'Sufficient');
INSERT INTO `translations` VALUES('495', 'en', 'critical', 'Critical');
INSERT INTO `translations` VALUES('496', 'en', 'out_of_stock', 'Out of Stock');
INSERT INTO `translations` VALUES('497', 'en', 'box', 'Box');
INSERT INTO `translations` VALUES('498', 'en', 'piece', 'Piece');
INSERT INTO `translations` VALUES('499', 'en', 'bottle', 'Bottle');
INSERT INTO `translations` VALUES('500', 'en', 'tube', 'Tube');
INSERT INTO `translations` VALUES('501', 'en', 'pack', 'Pack');
INSERT INTO `translations` VALUES('502', 'en', 'blister', 'Blister');
INSERT INTO `translations` VALUES('503', 'en', 'customer', 'Customer');
INSERT INTO `translations` VALUES('504', 'en', 'first_name', 'First Name');
INSERT INTO `translations` VALUES('505', 'en', 'last_name', 'Last Name');
INSERT INTO `translations` VALUES('506', 'en', 'phone', 'Phone');
INSERT INTO `translations` VALUES('507', 'en', 'address', 'Address');
INSERT INTO `translations` VALUES('508', 'en', 'due_days', 'Payment Due (Days)');
INSERT INTO `translations` VALUES('509', 'en', 'debt', 'Debt');
INSERT INTO `translations` VALUES('510', 'en', 'new_customer', 'New Customer');
INSERT INTO `translations` VALUES('511', 'en', 'edit_customer', 'Edit Customer');
INSERT INTO `translations` VALUES('512', 'en', 'customer_detail', 'Customer Detail');
INSERT INTO `translations` VALUES('513', 'en', 'take_payment', 'Take Payment');
INSERT INTO `translations` VALUES('514', 'en', 'payment_amount', 'Payment Amount');
INSERT INTO `translations` VALUES('515', 'en', 'payment_method', 'Payment Method');
INSERT INTO `translations` VALUES('516', 'en', 'cash', 'Cash');
INSERT INTO `translations` VALUES('517', 'en', 'card', 'Card');
INSERT INTO `translations` VALUES('518', 'en', 'transfer', 'Transfer');
INSERT INTO `translations` VALUES('519', 'en', 'other', 'Other');
INSERT INTO `translations` VALUES('520', 'en', 'payment_history', 'Payment History');
INSERT INTO `translations` VALUES('521', 'en', 'sales_history', 'Sales History');
INSERT INTO `translations` VALUES('522', 'en', 'has_debt', 'Has Debt');
INSERT INTO `translations` VALUES('523', 'en', 'no_debt', 'No Debt');
INSERT INTO `translations` VALUES('524', 'en', 'sale', 'Sale');
INSERT INTO `translations` VALUES('525', 'en', 'new_sale_title', 'New Sale');
INSERT INTO `translations` VALUES('526', 'en', 'cart', 'Cart');
INSERT INTO `translations` VALUES('527', 'en', 'add_to_cart', 'Add to Cart');
INSERT INTO `translations` VALUES('528', 'en', 'quantity', 'Quantity');
INSERT INTO `translations` VALUES('529', 'en', 'unit_price', 'Unit Price');
INSERT INTO `translations` VALUES('530', 'en', 'subtotal', 'Subtotal');
INSERT INTO `translations` VALUES('531', 'en', 'discount', 'Discount');
INSERT INTO `translations` VALUES('532', 'en', 'discount_type', 'Discount Type');
INSERT INTO `translations` VALUES('533', 'en', 'discount_pct', 'Percent (%)');
INSERT INTO `translations` VALUES('534', 'en', 'discount_fixed', 'Fixed Amount');
INSERT INTO `translations` VALUES('535', 'en', 'none', 'None');
INSERT INTO `translations` VALUES('536', 'en', 'net_total', 'Net Total');
INSERT INTO `translations` VALUES('537', 'en', 'paid_amount', 'Paid Amount');
INSERT INTO `translations` VALUES('538', 'en', 'remaining', 'Remaining');
INSERT INTO `translations` VALUES('539', 'en', 'complete_sale', 'Complete Sale');
INSERT INTO `translations` VALUES('540', 'en', 'invoice', 'Invoice');
INSERT INTO `translations` VALUES('541', 'en', 'cancel_sale', 'Cancel Sale');
INSERT INTO `translations` VALUES('542', 'en', 'sale_completed', 'Sale completed successfully.');
INSERT INTO `translations` VALUES('543', 'en', 'sale_cancelled', 'Sale cancelled. Stock restored.');
INSERT INTO `translations` VALUES('544', 'en', 'customer_optional', 'Customer (Optional)');
INSERT INTO `translations` VALUES('545', 'en', 'search_product', 'Search by name or barcode...');
INSERT INTO `translations` VALUES('546', 'en', 'search_customer', 'Search customer...');
INSERT INTO `translations` VALUES('547', 'en', 'quick_add_customer', 'Quick Add Customer');
INSERT INTO `translations` VALUES('548', 'en', 'select_customer', 'Select Customer');
INSERT INTO `translations` VALUES('549', 'en', 'walk_in', 'Walk-in');
INSERT INTO `translations` VALUES('550', 'en', 'stock_entry_title', 'Stock Entry');
INSERT INTO `translations` VALUES('551', 'en', 'new_stock_entry', 'New Stock Entry');
INSERT INTO `translations` VALUES('552', 'en', 'entry_qty', 'Entry Quantity');
INSERT INTO `translations` VALUES('553', 'en', 'invoice_ref', 'Invoice / Ref No');
INSERT INTO `translations` VALUES('554', 'en', 'update_purchase_price', 'Update purchase price');
INSERT INTO `translations` VALUES('555', 'en', 'recent_entries', 'Recent Entries');
INSERT INTO `translations` VALUES('556', 'en', 'stock_entry_done', 'Stock entry completed.');
INSERT INTO `translations` VALUES('557', 'en', 'movement_in', 'In');
INSERT INTO `translations` VALUES('558', 'en', 'movement_out', 'Out');
INSERT INTO `translations` VALUES('559', 'en', 'movement_adjust', 'Adjustment');
INSERT INTO `translations` VALUES('560', 'en', 'movement_convert', 'Conversion');
INSERT INTO `translations` VALUES('561', 'en', 'reference', 'Reference');
INSERT INTO `translations` VALUES('562', 'en', 'source_product', 'Source Product');
INSERT INTO `translations` VALUES('563', 'en', 'target_product', 'Target Product');
INSERT INTO `translations` VALUES('564', 'en', 'convert_ratio', 'Conversion Ratio');
INSERT INTO `translations` VALUES('565', 'en', 'period', 'Period');
INSERT INTO `translations` VALUES('566', 'en', 'today', 'Today');
INSERT INTO `translations` VALUES('567', 'en', 'this_week', 'This Week');
INSERT INTO `translations` VALUES('568', 'en', 'this_month', 'This Month');
INSERT INTO `translations` VALUES('569', 'en', 'this_year', 'This Year');
INSERT INTO `translations` VALUES('570', 'en', 'custom', 'Custom');
INSERT INTO `translations` VALUES('571', 'en', 'start_date', 'Start Date');
INSERT INTO `translations` VALUES('572', 'en', 'end_date', 'End Date');
INSERT INTO `translations` VALUES('573', 'en', 'apply', 'Apply');
INSERT INTO `translations` VALUES('574', 'en', 'gross_revenue', 'Gross Revenue');
INSERT INTO `translations` VALUES('575', 'en', 'net_revenue', 'Net Revenue');
INSERT INTO `translations` VALUES('576', 'en', 'collected', 'Collected');
INSERT INTO `translations` VALUES('577', 'en', 'uncollected', 'Uncollected');
INSERT INTO `translations` VALUES('578', 'en', 'cost', 'Cost');
INSERT INTO `translations` VALUES('579', 'en', 'margin', 'Margin');
INSERT INTO `translations` VALUES('580', 'en', 'category_dist', 'Category Distribution');
INSERT INTO `translations` VALUES('581', 'en', 'top_customers', 'Top Customers');
INSERT INTO `translations` VALUES('582', 'en', 'daily_chart', 'Daily Sales Chart');
INSERT INTO `translations` VALUES('583', 'en', 'spent', 'Spent');
INSERT INTO `translations` VALUES('584', 'en', 'language', 'Language');
INSERT INTO `translations` VALUES('585', 'en', 'currency', 'Currency');
INSERT INTO `translations` VALUES('586', 'en', 'settings_saved', 'Settings saved successfully.');
INSERT INTO `translations` VALUES('587', 'en', 'appearance', 'Appearance');
INSERT INTO `translations` VALUES('588', 'en', 'preferences', 'Preferences');
INSERT INTO `translations` VALUES('589', 'en', 'default_currency', 'Default Currency');
INSERT INTO `translations` VALUES('590', 'en', 'select_language', 'Select Language');
INSERT INTO `translations` VALUES('591', 'en', 'select_currency', 'Select Currency');
INSERT INTO `translations` VALUES('592', 'en', 'log_action', 'Action');
INSERT INTO `translations` VALUES('593', 'en', 'log_user', 'User');
INSERT INTO `translations` VALUES('594', 'en', 'log_ip', 'IP');
INSERT INTO `translations` VALUES('595', 'en', 'log_detail', 'Detail');
INSERT INTO `translations` VALUES('596', 'en', 'log_time', 'Date / Time');
INSERT INTO `translations` VALUES('597', 'en', 'added', 'Added');
INSERT INTO `translations` VALUES('598', 'en', 'updated', 'Updated');
INSERT INTO `translations` VALUES('599', 'en', 'deleted', 'Deleted');
INSERT INTO `translations` VALUES('600', 'en', 'payment', 'Payment');
INSERT INTO `translations` VALUES('601', 'en', 'txt__value', '\"\r\n                    value=\"');
INSERT INTO `translations` VALUES('602', 'en', 'txt__onclickreturn_confirm', '\"\r\n                                        onclick=\"return confirm(\'');
INSERT INTO `translations` VALUES('603', 'en', 'txt__onclickreturn_confirmbu_yedek', '\"\r\n                                        onclick=\"return confirm(\'Bu yedek silinecek onaylıyor musunuz?\');\">');
INSERT INTO `translations` VALUES('604', 'en', 'txt__stylecolorvarwarningbackgroun', '\"\r\n                                                style=\"color:var(--warning);background:none;border:none;cursor:pointer;\">');
INSERT INTO `translations` VALUES('605', 'en', 'txt__stylefontsize18pxpadding14px', '\" style=\"font-size:18px;padding:14px;\">');
INSERT INTO `translations` VALUES('606', 'en', 'txt__maxlength10_styletexttransfor', '\"\r\n                                maxlength=\"10\" style=\"text-transform:uppercase;\">');
INSERT INTO `translations` VALUES('607', 'en', 'txt__maxlength10', '\"\r\n                                maxlength=\"10\">');
INSERT INTO `translations` VALUES('608', 'en', 'txt__required', '\" required>');
INSERT INTO `translations` VALUES('609', 'en', 'txt__autocompleteoff', '\" autocomplete=\"off\">');
INSERT INTO `translations` VALUES('610', 'en', 'txt_hizli_tahsilat', 'Hizli Tahsilat');
INSERT INTO `translations` VALUES('611', 'en', 'txt_musteri_secin', 'Select Customer');
INSERT INTO `translations` VALUES('612', 'en', 'txt__musteri_ara_sec', ' Search Customer');
INSERT INTO `translations` VALUES('613', 'en', 'txt_guncel_cari_durum', 'Guncel Cari Durum');
INSERT INTO `translations` VALUES('614', 'en', 'txt_tahsilat_avans_tutari', 'Tahsilat Avans Tutari');
INSERT INTO `translations` VALUES('615', 'en', 'txt_orn_50000', 'Orn 50000');
INSERT INTO `translations` VALUES('616', 'en', 'txt_odeme_yontemi', 'Odeme Yontemi');
INSERT INTO `translations` VALUES('617', 'en', 'txt_nakit', 'Nakit');
INSERT INTO `translations` VALUES('618', 'en', 'txt_kredibanka_karti', 'Kredibanka Karti');
INSERT INTO `translations` VALUES('619', 'en', 'txt_havaleeft', 'Havaleeft');
INSERT INTO `translations` VALUES('620', 'en', 'txt_diger', 'Diger');
INSERT INTO `translations` VALUES('621', 'en', 'txt_aciklama_not', 'Aciklama Not');
INSERT INTO `translations` VALUES('622', 'en', 'txt_orn_ekim_ayi_avansi_eski_borc', 'Orn Ekim Ayi Avansi Eski Borc');
INSERT INTO `translations` VALUES('623', 'en', 'txt_tahsilati_tamamla', 'Tahsilati Tamamla');
INSERT INTO `translations` VALUES('624', 'en', 'txt_cari_hesap_ekstresi', 'Cari Hesap Ekstresi');
INSERT INTO `translations` VALUES('625', 'en', 'txt_cikti_al_pdf', 'Cikti Al Pdf');
INSERT INTO `translations` VALUES('626', 'en', 'txt_id', 'Id');
INSERT INTO `translations` VALUES('627', 'en', 'txt_telefon', 'Telefon');
INSERT INTO `translations` VALUES('628', 'en', 'txt_adres', 'Adres');
INSERT INTO `translations` VALUES('629', 'en', 'txt_tarih', 'Tarih');
INSERT INTO `translations` VALUES('630', 'en', 'txt_islem_turu_aciklama', 'Islem Turu Aciklama');
INSERT INTO `translations` VALUES('631', 'en', 'txt_borc_satis', 'Borc Satis');
INSERT INTO `translations` VALUES('632', 'en', 'txt_alacak_tahsilat', 'Alacak Tahsilat');
INSERT INTO `translations` VALUES('633', 'en', 'txt_yuruyen_bakiye', 'Yuruyen Bakiye');
INSERT INTO `translations` VALUES('634', 'en', 'txt_cari_hareket_bulunamadi', 'Cari Hareket Bulunamadi');
INSERT INTO `translations` VALUES('635', 'en', 'txt_satis_fatura', 'Satis Fatura');
INSERT INTO `translations` VALUES('636', 'en', 'txt_tahsilat_avans_makb', 'Tahsilat Avans Makb');
INSERT INTO `translations` VALUES('637', 'en', 'txt_not', 'Not');
INSERT INTO `translations` VALUES('638', 'en', 'txt_kategori_adi', 'Kategori Adi');
INSERT INTO `translations` VALUES('639', 'en', 'txt_aciklama', 'Aciklama');
INSERT INTO `translations` VALUES('640', 'en', 'txt_kisa_aciklama', 'Kisa Aciklama');
INSERT INTO `translations` VALUES('641', 'en', 'txt_iptal', 'Iptal');
INSERT INTO `translations` VALUES('642', 'en', 'txt_kategoriler', 'Kategoriler');
INSERT INTO `translations` VALUES('643', 'en', 'txt_urun', 'Urun');
INSERT INTO `translations` VALUES('644', 'en', 'txt_islem', 'Islem');
INSERT INTO `translations` VALUES('645', 'en', 'txt_kategori_yok', 'Kategori Yok');
INSERT INTO `translations` VALUES('646', 'en', 'txt_kategori', 'Kategori');
INSERT INTO `translations` VALUES('647', 'en', 'txt__sec', ' Sec');
INSERT INTO `translations` VALUES('648', 'en', 'txt_para_birimi', 'Para Birimi');
INSERT INTO `translations` VALUES('649', 'en', 'txt_alis_fiyati', 'Alis Fiyati');
INSERT INTO `translations` VALUES('650', 'en', 'txt_satis_fiyati', 'Satis Fiyati');
INSERT INTO `translations` VALUES('651', 'en', 'txt_birim', 'Birim');
INSERT INTO `translations` VALUES('652', 'en', 'txt_mevcut_stok', 'Mevcut Stok');
INSERT INTO `translations` VALUES('653', 'en', 'txt_kritik_stok_seviyesi', 'Kritik Stok Seviyesi');
INSERT INTO `translations` VALUES('654', 'en', 'txt_urun_aktif', 'Urun Aktif');
INSERT INTO `translations` VALUES('655', 'en', 'txt_urun_gorseli', 'Urun Gorseli');
INSERT INTO `translations` VALUES('656', 'en', 'txt_gorsel_sec_veya_surukle_birak', 'Gorsel Sec Veya Surukle Birak');
INSERT INTO `translations` VALUES('657', 'en', 'txt_jpg_png_webp_max_3mb', 'Jpg Png Webp Max 3mb');
INSERT INTO `translations` VALUES('658', 'en', 'txt_stok_degeri', 'Stock Value');
INSERT INTO `translations` VALUES('659', 'en', 'txt_hizli_donem', 'Hizli Donem');
INSERT INTO `translations` VALUES('660', 'en', 'txt_baslangic', 'Baslangic');
INSERT INTO `translations` VALUES('661', 'en', 'txt_bitis', 'Bitis');
INSERT INTO `translations` VALUES('662', 'en', 'txt_uygula', 'Uygula');
INSERT INTO `translations` VALUES('663', 'en', 'txt_disa_aktar', 'Disa Aktar');
INSERT INTO `translations` VALUES('664', 'en', 'txt_csv', 'Csv');
INSERT INTO `translations` VALUES('665', 'en', 'txt_yazdir', 'Yazdir');
INSERT INTO `translations` VALUES('666', 'en', 'txt_donem', 'Donem');
INSERT INTO `translations` VALUES('667', 'en', 'txt_satis_adedi', 'Satis Adedi');
INSERT INTO `translations` VALUES('668', 'en', 'txt_net_ciro', 'Net Ciro');
INSERT INTO `translations` VALUES('669', 'en', 'txt_kr', 'Kr');
INSERT INTO `translations` VALUES('670', 'en', 'txt_satilan_maliyet', 'Satilan Maliyet');
INSERT INTO `translations` VALUES('671', 'en', 'txt_tahsil_edilen', 'Tahsil Edilen');
INSERT INTO `translations` VALUES('672', 'en', 'txt_tahsilsiz', 'Tahsilsiz');
INSERT INTO `translations` VALUES('673', 'en', 'txt_gunluk_satis_grafigi', 'Gunluk Satis Grafigi');
INSERT INTO `translations` VALUES('674', 'en', 'txt_en_cok_satan_urunler', 'En Cok Satan Urunler');
INSERT INTO `translations` VALUES('675', 'en', 'txt_satilan', 'Satilan');
INSERT INTO `translations` VALUES('676', 'en', 'txt_ciro', 'Ciro');
INSERT INTO `translations` VALUES('677', 'en', 'txt_veri_yok', 'Veri Yok');
INSERT INTO `translations` VALUES('678', 'en', 'txt_kategori_dagilimi', 'Kategori Dagilimi');
INSERT INTO `translations` VALUES('679', 'en', 'txt_en_iyi_musteriler', 'En Iyi Musteriler');
INSERT INTO `translations` VALUES('680', 'en', 'txt_musteri', 'Musteri');
INSERT INTO `translations` VALUES('681', 'en', 'txt_satis', 'Satis');
INSERT INTO `translations` VALUES('682', 'en', 'txt_harcama', 'Harcama');
INSERT INTO `translations` VALUES('683', 'en', 'txt_borc', 'Borc');
INSERT INTO `translations` VALUES('684', 'en', 'txt_ara', 'Ara');
INSERT INTO `translations` VALUES('685', 'en', 'txt_islem_detay_veya_kullanici', 'Islem Detay Veya Kullanici');
INSERT INTO `translations` VALUES('686', 'en', 'txt_filtrele', 'Filtrele');
INSERT INTO `translations` VALUES('687', 'en', 'txt_9899bf', '9899bf');
INSERT INTO `translations` VALUES('688', 'en', 'txt_islem_loglari', 'Islem Loglari');
INSERT INTO `translations` VALUES('689', 'en', 'txt_toplam', 'Toplam');
INSERT INTO `translations` VALUES('690', 'en', 'txt_kayit', 'Kayit');
INSERT INTO `translations` VALUES('691', 'en', 'txt_tarih_saat', 'Tarih Saat');
INSERT INTO `translations` VALUES('692', 'en', 'txt_detay', 'Detay');
INSERT INTO `translations` VALUES('693', 'en', 'txt_kullanici', 'Kullanici');
INSERT INTO `translations` VALUES('694', 'en', 'txt_ip', 'Ip');
INSERT INTO `translations` VALUES('695', 'en', 'txt_log_bulunamadi', 'Log Bulunamadi');
INSERT INTO `translations` VALUES('696', 'en', 'txt_urun_ekle', 'Urun Ekle');
INSERT INTO `translations` VALUES('697', 'en', 'txt_ilac_adi_veya_barkod_ara', 'Ilac Adi Veya Barkod Ara');
INSERT INTO `translations` VALUES('698', 'en', 'txt_tum_kategoriler', 'Tum Kategoriler');
INSERT INTO `translations` VALUES('699', 'en', 'txt_stok_durumu', 'Stok Durumu');
INSERT INTO `translations` VALUES('700', 'en', 'txt_stokta_var', 'Stokta Var');
INSERT INTO `translations` VALUES('701', 'en', 'txt_stokta_yok', 'Stokta Yok');
INSERT INTO `translations` VALUES('702', 'en', 'txt_sepet', 'Sepet');
INSERT INTO `translations` VALUES('703', 'en', 'txt_temizle', 'Temizle');
INSERT INTO `translations` VALUES('704', 'en', 'txt_ilac', 'Ilac');
INSERT INTO `translations` VALUES('705', 'en', 'txt_birim_fiyat', 'Birim Fiyat');
INSERT INTO `translations` VALUES('706', 'en', 'txt_adet', 'Adet');
INSERT INTO `translations` VALUES('707', 'en', 'txt_sepet_bos_urun_ekleyin', 'Sepet Bos Urun Ekleyin');
INSERT INTO `translations` VALUES('708', 'en', 'txt_musteri_ara', 'Musteri Ara');
INSERT INTO `translations` VALUES('709', 'en', 'txt_ad_soyad_veya_telefon', 'Ad Soyad Veya Telefon');
INSERT INTO `translations` VALUES('710', 'en', 'txt_kaldir', 'Kaldir');
INSERT INTO `translations` VALUES('711', 'en', 'txt_hizli_musteri_ekle', 'Hizli Musteri Ekle');
INSERT INTO `translations` VALUES('712', 'en', 'txt_ad', 'Ad');
INSERT INTO `translations` VALUES('713', 'en', 'txt_soyad', 'Soyad');
INSERT INTO `translations` VALUES('714', 'en', 'txt_ekle_ve_sec', 'Ekle Ve Sec');
INSERT INTO `translations` VALUES('715', 'en', 'txt_odeme_ozeti', 'Odeme Ozeti');
INSERT INTO `translations` VALUES('716', 'en', 'txt_iskonto', 'Iskonto');
INSERT INTO `translations` VALUES('717', 'en', 'txt_yok', 'Yok');
INSERT INTO `translations` VALUES('718', 'en', 'txt_yuzde', 'Yuzde');
INSERT INTO `translations` VALUES('719', 'en', 'txt_sabit', 'Sabit');
INSERT INTO `translations` VALUES('720', 'en', 'txt_ara_toplam', 'Ara Toplam');
INSERT INTO `translations` VALUES('721', 'en', 'txt_net_tutar', 'Net Tutar');
INSERT INTO `translations` VALUES('722', 'en', 'txt_alinan_odeme', 'Alinan Odeme');
INSERT INTO `translations` VALUES('723', 'en', 'txt_tamamini_ode', 'Tamamini Ode');
INSERT INTO `translations` VALUES('724', 'en', 'txt_veresiye', 'Veresiye');
INSERT INTO `translations` VALUES('725', 'en', 'txt_kalan_borc', 'Kalan Borc');
INSERT INTO `translations` VALUES('726', 'en', 'txt_musteri_hesabina_borc_olarak_y', 'Musteri Hesabina Borc Olarak Y');
INSERT INTO `translations` VALUES('727', 'en', 'txt_satis_notu', 'Satis Notu');
INSERT INTO `translations` VALUES('728', 'en', 'txt_satisi_tamamla', 'Satisi Tamamla');
INSERT INTO `translations` VALUES('729', 'en', 'txt_sistem_yedekleri', 'Sistem Yedekleri');
INSERT INTO `translations` VALUES('730', 'en', 'txt_veritabaninin_tam_yedegini_ali', 'Veritabaninin Tam Yedegini Ali');
INSERT INTO `translations` VALUES('731', 'en', 'txt_yeni_yedek_al', 'Yeni Yedek Al');
INSERT INTO `translations` VALUES('732', 'en', 'txt_mevcut_yedekler', 'Mevcut Yedekler');
INSERT INTO `translations` VALUES('733', 'en', 'txt_dosya_adi', 'Dosya Adi');
INSERT INTO `translations` VALUES('734', 'en', 'txt_boyut', 'Boyut');
INSERT INTO `translations` VALUES('735', 'en', 'txt_olusturulma_tarihi', 'Olusturulma Tarihi');
INSERT INTO `translations` VALUES('736', 'en', 'txt_islemler', 'Islemler');
INSERT INTO `translations` VALUES('737', 'en', 'txt_hic_yedek_bulunamadi', 'Hic Yedek Bulunamadi');
INSERT INTO `translations` VALUES('738', 'en', 'txt_geri_yukle', 'Geri Yukle');
INSERT INTO `translations` VALUES('739', 'en', 'txt_veritabanina_yazilacaktir_mevc', 'Veritabanina Yazilacaktir Mevc');
INSERT INTO `translations` VALUES('740', 'en', 'txt__classbtnsmicon_btnedit_me1_ti', ' Classbtnsmicon Btnedit Me1 Ti');
INSERT INTO `translations` VALUES('741', 'en', 'txt_sil', 'Sil');
INSERT INTO `translations` VALUES('742', 'en', 'txt_uyari', 'Uyari');
INSERT INTO `translations` VALUES('743', 'en', 'txt_geri_yukleme_islemi_mevcut_tum', 'Geri Yukleme Islemi Mevcut Tum');
INSERT INTO `translations` VALUES('744', 'en', 'txt_geri_alinamaz', 'Geri Alinamaz');
INSERT INTO `translations` VALUES('745', 'en', 'txt_code', 'Code');
INSERT INTO `translations` VALUES('746', 'en', 'txt_symbol', 'Symbol');
INSERT INTO `translations` VALUES('747', 'en', 'txt_1_usd', '1 Usd');
INSERT INTO `translations` VALUES('748', 'en', 'txt_rate_date', 'Rate Date');
INSERT INTO `translations` VALUES('749', 'en', 'txt_1000000_base', '1000000 Base');
INSERT INTO `translations` VALUES('750', 'en', 'txt__no_rate', ' No Rate');
INSERT INTO `translations` VALUES('751', 'en', 'txt_base', 'Base');
INSERT INTO `translations` VALUES('752', 'en', 'txt_rate_history', 'Rate History');
INSERT INTO `translations` VALUES('753', 'en', 'txt_date', 'Date');
INSERT INTO `translations` VALUES('754', 'en', 'txt_entry_time', 'Entry Time');
INSERT INTO `translations` VALUES('755', 'en', 'txt_set_exchange_rate', 'Set Exchange Rate');
INSERT INTO `translations` VALUES('756', 'en', 'txt__select', ' Select');
INSERT INTO `translations` VALUES('757', 'en', 'txt_enter_how_many_units_of_this_c', 'Enter How Many Units Of This C');
INSERT INTO `translations` VALUES('758', 'en', 'txt_effective_date', 'Effective Date');
INSERT INTO `translations` VALUES('759', 'en', 'txt_rate_applies_from_this_date_fo', 'Rate Applies From This Date Fo');
INSERT INTO `translations` VALUES('760', 'en', 'txt_set_rate', 'Set Rate');
INSERT INTO `translations` VALUES('761', 'en', 'txt_add_currency', 'Add Currency');
INSERT INTO `translations` VALUES('762', 'en', 'txt_position', 'Position');
INSERT INTO `translations` VALUES('763', 'en', 'txt_currency_name', 'Currency Name');
INSERT INTO `translations` VALUES('764', 'en', 'txt_saudi_riyal', 'Saudi Riyal');
INSERT INTO `translations` VALUES('765', 'en', 'txt_decimal_sep', 'Decimal Sep');
INSERT INTO `translations` VALUES('766', 'en', 'txt_thousand_sep', 'Thousand Sep');
INSERT INTO `translations` VALUES('767', 'en', 'txt_initial_rate_1_usd', 'Initial Rate 1 Usd');
INSERT INTO `translations` VALUES('768', 'en', 'txt_optional', 'Optional');
INSERT INTO `translations` VALUES('769', 'en', 'txt_leave_empty_if_youll_set_the_r', 'Leave Empty If Youll Set The R');
INSERT INTO `translations` VALUES('770', 'en', 'txt_uygulama_temasi_ui_theme', 'Uygulama Temasi Ui Theme');
INSERT INTO `translations` VALUES('771', 'en', 'txt_ozel_renkler_custom', 'Ozel Renkler Custom');
INSERT INTO `translations` VALUES('772', 'en', 'txt_arkaplan_rengi_body', 'Arkaplan Rengi Body');
INSERT INTO `translations` VALUES('773', 'en', 'txt_sol_menu_sidebar', 'Sol Menu Sidebar');
INSERT INTO `translations` VALUES('774', 'en', 'txt_kart_arkaplani_panel', 'Kart Arkaplani Panel');
INSERT INTO `translations` VALUES('775', 'en', 'txt_ana_vurgu_accent', 'Ana Vurgu Accent');
INSERT INTO `translations` VALUES('776', 'en', 'txt_yazi_rengi_text', 'Yazi Rengi Text');
INSERT INTO `translations` VALUES('777', 'en', 'txt_site_logosu', 'Site Logosu');
INSERT INTO `translations` VALUES('778', 'en', 'txt_varsayilan_urun_gorseli', 'Varsayilan Urun Gorseli');
INSERT INTO `translations` VALUES('779', 'en', 'txt_preview_onizleme', 'Preview Onizleme');
INSERT INTO `translations` VALUES('780', 'en', 'txt_management', 'Management');
INSERT INTO `translations` VALUES('781', 'en', 'txt_addremove_currencies_set_daily', 'Addremove Currencies Set Daily');
INSERT INTO `translations` VALUES('782', 'en', 'txt_yeni_donusum', 'Yeni Donusum');
INSERT INTO `translations` VALUES('783', 'en', 'txt_ornek', 'Ornek');
INSERT INTO `translations` VALUES('784', 'en', 'txt_1_kutuluk_aspirin_kutu_urununu', '1 Kutuluk Aspirin Kutu Urununu');
INSERT INTO `translations` VALUES('785', 'en', 'txt_kaynak_urun_parcalanacak', 'Kaynak Urun Parcalanacak');
INSERT INTO `translations` VALUES('786', 'en', 'txt__urun_sec', ' Urun Sec');
INSERT INTO `translations` VALUES('787', 'en', 'txt_alinacak_miktar_kaynak', 'Alinacak Miktar Kaynak');
INSERT INTO `translations` VALUES('788', 'en', 'txt_kac_kutu', 'Kac Kutu');
INSERT INTO `translations` VALUES('789', 'en', 'txt_hedef_urun_eklenecek', 'Hedef Urun Eklenecek');
INSERT INTO `translations` VALUES('790', 'en', 'txt_mevcut', 'Mevcut');
INSERT INTO `translations` VALUES('791', 'en', 'txt_eklenecek_miktar_hedef', 'Eklenecek Miktar Hedef');
INSERT INTO `translations` VALUES('792', 'en', 'txt_kac_adet_eklenecek', 'Kac Adet Eklenecek');
INSERT INTO `translations` VALUES('793', 'en', 'txt_donusum_aciklamasi', 'Donusum Aciklamasi');
INSERT INTO `translations` VALUES('794', 'en', 'txt_donusumu_gerceklestir', 'Donusumu Gerceklestir');
INSERT INTO `translations` VALUES('795', 'en', 'txt_son_donusumler', 'Son Donusumler');
INSERT INTO `translations` VALUES('796', 'en', 'txt_miktar', 'Miktar');
INSERT INTO `translations` VALUES('797', 'en', 'txt_henuz_donusum_yok', 'Henuz Donusum Yok');
INSERT INTO `translations` VALUES('798', 'en', 'txt_yeni_stok_girisi', 'Yeni Stok Girisi');
INSERT INTO `translations` VALUES('799', 'en', 'txt_fatura_irsaliye_no', 'Fatura Irsaliye No');
INSERT INTO `translations` VALUES('800', 'en', 'txt_orn_ftr20260042', 'Orn Ftr20260042');
INSERT INTO `translations` VALUES('801', 'en', 'txt_alis_fiyatini_guncelle', 'Alis Fiyatini Guncelle');
INSERT INTO `translations` VALUES('802', 'en', 'txt_yeni_alis_fiyati', 'Yeni Alis Fiyati');
INSERT INTO `translations` VALUES('803', 'en', 'txt_istege_bagli_mevcut_resmi_gunc', 'Istege Bagli Mevcut Resmi Gunc');
INSERT INTO `translations` VALUES('804', 'en', 'txt_aciklama_tedarikci_bilgisi_vb', 'Aciklama Tedarikci Bilgisi Vb');
INSERT INTO `translations` VALUES('805', 'en', 'txt_stok_girisi_yap', 'Stok Girisi Yap');
INSERT INTO `translations` VALUES('806', 'en', 'txt_son_stok_girisleri', 'Son Stok Girisleri');
INSERT INTO `translations` VALUES('807', 'en', 'txt_tumunu_gor', 'Tumunu Gor');
INSERT INTO `translations` VALUES('808', 'en', 'txt_referans', 'Referans');
INSERT INTO `translations` VALUES('809', 'en', 'txt_henuz_stok_girisi_yapilmamis', 'Henuz Stok Girisi Yapilmamis');
INSERT INTO `translations` VALUES('810', 'en', 'txt_tumu', 'Tumu');
INSERT INTO `translations` VALUES('811', 'en', 'txt_tur', 'Tur');
INSERT INTO `translations` VALUES('812', 'en', 'txt_giris', 'Giris');
INSERT INTO `translations` VALUES('813', 'en', 'txt_cikis', 'Cikis');
INSERT INTO `translations` VALUES('814', 'en', 'txt_duzeltme', 'Duzeltme');
INSERT INTO `translations` VALUES('815', 'en', 'txt_donusum', 'Donusum');
INSERT INTO `translations` VALUES('816', 'en', 'txt_hareket_listesi', 'Hareket Listesi');
INSERT INTO `translations` VALUES('817', 'en', 'txt_kayit_yok', 'Kayit Yok');
INSERT INTO `translations` VALUES('818', 'fr', 'app_name', 'BAH Pharmacie');
INSERT INTO `translations` VALUES('819', 'fr', 'dashboard', 'Tableau de Bord');
INSERT INTO `translations` VALUES('820', 'fr', 'settings', 'Paramètres');
INSERT INTO `translations` VALUES('821', 'fr', 'save', 'Enregistrer');
INSERT INTO `translations` VALUES('822', 'fr', 'cancel', 'Annuler');
INSERT INTO `translations` VALUES('823', 'fr', 'delete', 'Supprimer');
INSERT INTO `translations` VALUES('824', 'fr', 'edit', 'Modifier');
INSERT INTO `translations` VALUES('825', 'fr', 'add', 'Ajouter');
INSERT INTO `translations` VALUES('826', 'fr', 'search', 'Rechercher');
INSERT INTO `translations` VALUES('827', 'fr', 'filter', 'Filtrer');
INSERT INTO `translations` VALUES('828', 'fr', 'back', 'Retour');
INSERT INTO `translations` VALUES('829', 'fr', 'close', 'Fermer');
INSERT INTO `translations` VALUES('830', 'fr', 'yes', 'Oui');
INSERT INTO `translations` VALUES('831', 'fr', 'no', 'Non');
INSERT INTO `translations` VALUES('832', 'fr', 'actions', 'Actions');
INSERT INTO `translations` VALUES('833', 'fr', 'detail', 'Détail');
INSERT INTO `translations` VALUES('834', 'fr', 'all', 'Tous');
INSERT INTO `translations` VALUES('835', 'fr', 'date', 'Date');
INSERT INTO `translations` VALUES('836', 'fr', 'note', 'Note');
INSERT INTO `translations` VALUES('837', 'fr', 'status', 'Statut');
INSERT INTO `translations` VALUES('838', 'fr', 'active', 'Actif');
INSERT INTO `translations` VALUES('839', 'fr', 'passive', 'Inactif');
INSERT INTO `translations` VALUES('840', 'fr', 'print', 'Imprimer');
INSERT INTO `translations` VALUES('841', 'fr', 'export_csv', 'Exporter CSV');
INSERT INTO `translations` VALUES('842', 'fr', 'error', 'Erreur');
INSERT INTO `translations` VALUES('843', 'fr', 'success', 'Succès');
INSERT INTO `translations` VALUES('844', 'fr', 'confirm_delete', 'Êtes-vous sûr de vouloir supprimer ?');
INSERT INTO `translations` VALUES('845', 'fr', 'no_data', 'Aucune donnée trouvée.');
INSERT INTO `translations` VALUES('846', 'fr', 'total', 'Total');
INSERT INTO `translations` VALUES('847', 'fr', 'menu_main', 'MENU PRINCIPAL');
INSERT INTO `translations` VALUES('848', 'fr', 'menu_stock', 'STOCK');
INSERT INTO `translations` VALUES('849', 'fr', 'menu_customer_sales', 'CLIENTS & VENTES');
INSERT INTO `translations` VALUES('850', 'fr', 'menu_reports', 'RAPPORTS');
INSERT INTO `translations` VALUES('851', 'fr', 'products', 'Produits');
INSERT INTO `translations` VALUES('852', 'fr', 'categories', 'Catégories');
INSERT INTO `translations` VALUES('853', 'fr', 'stock_entry', 'Entrée de Stock');
INSERT INTO `translations` VALUES('854', 'fr', 'stock_movements', 'Mouvements de Stock');
INSERT INTO `translations` VALUES('855', 'fr', 'stock_convert', 'Conversion de Stock');
INSERT INTO `translations` VALUES('856', 'fr', 'customers', 'Clients');
INSERT INTO `translations` VALUES('857', 'fr', 'sales', 'Ventes');
INSERT INTO `translations` VALUES('858', 'fr', 'new_sale', 'Nouvelle Vente');
INSERT INTO `translations` VALUES('859', 'fr', 'reports', 'Rapports');
INSERT INTO `translations` VALUES('860', 'fr', 'action_logs', 'Journal d\'Actions');
INSERT INTO `translations` VALUES('861', 'fr', 'stock_report', 'Rapport de Stock');
INSERT INTO `translations` VALUES('862', 'fr', 'today_sales', 'Ventes du Jour');
INSERT INTO `translations` VALUES('863', 'fr', 'monthly_revenue', 'Chiffre d\'Affaires Mensuel');
INSERT INTO `translations` VALUES('864', 'fr', 'total_products', 'Total Produits');
INSERT INTO `translations` VALUES('865', 'fr', 'critical_stock', 'Stock Critique');
INSERT INTO `translations` VALUES('866', 'fr', 'total_customers', 'Total Clients');
INSERT INTO `translations` VALUES('867', 'fr', 'total_debt', 'Dette Totale');
INSERT INTO `translations` VALUES('868', 'fr', 'today_profit', 'Bénéfice du Jour');
INSERT INTO `translations` VALUES('869', 'fr', 'monthly_profit', 'Bénéfice Mensuel');
INSERT INTO `translations` VALUES('870', 'fr', 'recent_sales', 'Ventes Récentes');
INSERT INTO `translations` VALUES('871', 'fr', 'low_stock_alert', 'Alertes Stock Bas');
INSERT INTO `translations` VALUES('872', 'fr', 'sales_chart', 'Graphique des Ventes (7 Derniers Jours)');
INSERT INTO `translations` VALUES('873', 'fr', 'top_products', 'Produits Les Plus Vendus');
INSERT INTO `translations` VALUES('874', 'fr', 'revenue', 'Chiffre d\'Affaires');
INSERT INTO `translations` VALUES('875', 'fr', 'profit', 'Bénéfice');
INSERT INTO `translations` VALUES('876', 'fr', 'sale_count', 'Nombre de Ventes');
INSERT INTO `translations` VALUES('877', 'fr', 'quick_actions', 'Actions Rapides');
INSERT INTO `translations` VALUES('878', 'fr', 'product_name', 'Nom du Produit');
INSERT INTO `translations` VALUES('879', 'fr', 'barcode', 'Code-barres');
INSERT INTO `translations` VALUES('880', 'fr', 'sku', 'Code SKU');
INSERT INTO `translations` VALUES('881', 'fr', 'dosage_form', 'Forme Galénique');
INSERT INTO `translations` VALUES('882', 'fr', 'category', 'Catégorie');
INSERT INTO `translations` VALUES('883', 'fr', 'purchase_price', 'Prix d\'Achat');
INSERT INTO `translations` VALUES('884', 'fr', 'sale_price', 'Prix de Vente');
INSERT INTO `translations` VALUES('885', 'fr', 'unit', 'Unité');
INSERT INTO `translations` VALUES('886', 'fr', 'stock_qty', 'Quantité en Stock');
INSERT INTO `translations` VALUES('887', 'fr', 'critical_level', 'Niveau Critique');
INSERT INTO `translations` VALUES('888', 'fr', 'product_image', 'Image du Produit');
INSERT INTO `translations` VALUES('889', 'fr', 'product_active', 'Produit Actif');
INSERT INTO `translations` VALUES('890', 'fr', 'new_product', 'Nouveau Produit');
INSERT INTO `translations` VALUES('891', 'fr', 'edit_product', 'Modifier Produit');
INSERT INTO `translations` VALUES('892', 'fr', 'product_added', 'Produit ajouté avec succès.');
INSERT INTO `translations` VALUES('893', 'fr', 'product_updated', 'Produit mis à jour.');
INSERT INTO `translations` VALUES('894', 'fr', 'product_deleted', 'Produit supprimé.');
INSERT INTO `translations` VALUES('895', 'fr', 'barcode_exists', 'Ce code-barres est déjà enregistré.');
INSERT INTO `translations` VALUES('896', 'fr', 'name_required', 'Le nom du produit est obligatoire.');
INSERT INTO `translations` VALUES('897', 'fr', 'price_required', 'Le prix de vente doit être supérieur à 0.');
INSERT INTO `translations` VALUES('898', 'fr', 'stock', 'Stock');
INSERT INTO `translations` VALUES('899', 'fr', 'sufficient', 'Suffisant');
INSERT INTO `translations` VALUES('900', 'fr', 'critical', 'Critique');
INSERT INTO `translations` VALUES('901', 'fr', 'out_of_stock', 'Rupture');
INSERT INTO `translations` VALUES('902', 'fr', 'box', 'Boîte');
INSERT INTO `translations` VALUES('903', 'fr', 'piece', 'Pièce');
INSERT INTO `translations` VALUES('904', 'fr', 'bottle', 'Flacon');
INSERT INTO `translations` VALUES('905', 'fr', 'tube', 'Tube');
INSERT INTO `translations` VALUES('906', 'fr', 'pack', 'Paquet');
INSERT INTO `translations` VALUES('907', 'fr', 'blister', 'Blister');
INSERT INTO `translations` VALUES('908', 'fr', 'customer', 'Client');
INSERT INTO `translations` VALUES('909', 'fr', 'first_name', 'Prénom');
INSERT INTO `translations` VALUES('910', 'fr', 'last_name', 'Nom');
INSERT INTO `translations` VALUES('911', 'fr', 'phone', 'Téléphone');
INSERT INTO `translations` VALUES('912', 'fr', 'address', 'Adresse');
INSERT INTO `translations` VALUES('913', 'fr', 'due_days', 'Échéance (Jours)');
INSERT INTO `translations` VALUES('914', 'fr', 'debt', 'Dette');
INSERT INTO `translations` VALUES('915', 'fr', 'new_customer', 'Nouveau Client');
INSERT INTO `translations` VALUES('916', 'fr', 'edit_customer', 'Modifier Client');
INSERT INTO `translations` VALUES('917', 'fr', 'customer_detail', 'Détail Client');
INSERT INTO `translations` VALUES('918', 'fr', 'take_payment', 'Encaisser');
INSERT INTO `translations` VALUES('919', 'fr', 'payment_amount', 'Montant du Paiement');
INSERT INTO `translations` VALUES('920', 'fr', 'payment_method', 'Mode de Paiement');
INSERT INTO `translations` VALUES('921', 'fr', 'cash', 'Espèces');
INSERT INTO `translations` VALUES('922', 'fr', 'card', 'Carte');
INSERT INTO `translations` VALUES('923', 'fr', 'transfer', 'Virement');
INSERT INTO `translations` VALUES('924', 'fr', 'other', 'Autre');
INSERT INTO `translations` VALUES('925', 'fr', 'payment_history', 'Historique des Paiements');
INSERT INTO `translations` VALUES('926', 'fr', 'sales_history', 'Historique des Ventes');
INSERT INTO `translations` VALUES('927', 'fr', 'has_debt', 'Endetté');
INSERT INTO `translations` VALUES('928', 'fr', 'no_debt', 'Sans Dette');
INSERT INTO `translations` VALUES('929', 'fr', 'sale', 'Vente');
INSERT INTO `translations` VALUES('930', 'fr', 'new_sale_title', 'Nouvelle Vente');
INSERT INTO `translations` VALUES('931', 'fr', 'cart', 'Panier');
INSERT INTO `translations` VALUES('932', 'fr', 'add_to_cart', 'Ajouter au Panier');
INSERT INTO `translations` VALUES('933', 'fr', 'quantity', 'Quantité');
INSERT INTO `translations` VALUES('934', 'fr', 'unit_price', 'Prix Unitaire');
INSERT INTO `translations` VALUES('935', 'fr', 'subtotal', 'Sous-total');
INSERT INTO `translations` VALUES('936', 'fr', 'discount', 'Remise');
INSERT INTO `translations` VALUES('937', 'fr', 'discount_type', 'Type de Remise');
INSERT INTO `translations` VALUES('938', 'fr', 'discount_pct', 'Pourcentage (%)');
INSERT INTO `translations` VALUES('939', 'fr', 'discount_fixed', 'Montant Fixe');
INSERT INTO `translations` VALUES('940', 'fr', 'none', 'Aucun');
INSERT INTO `translations` VALUES('941', 'fr', 'net_total', 'Total Net');
INSERT INTO `translations` VALUES('942', 'fr', 'paid_amount', 'Montant Payé');
INSERT INTO `translations` VALUES('943', 'fr', 'remaining', 'Restant');
INSERT INTO `translations` VALUES('944', 'fr', 'complete_sale', 'Finaliser la Vente');
INSERT INTO `translations` VALUES('945', 'fr', 'invoice', 'Facture');
INSERT INTO `translations` VALUES('946', 'fr', 'cancel_sale', 'Annuler la Vente');
INSERT INTO `translations` VALUES('947', 'fr', 'sale_completed', 'Vente finalisée avec succès.');
INSERT INTO `translations` VALUES('948', 'fr', 'sale_cancelled', 'Vente annulée. Stock restauré.');
INSERT INTO `translations` VALUES('949', 'fr', 'customer_optional', 'Client (Optionnel)');
INSERT INTO `translations` VALUES('950', 'fr', 'search_product', 'Rechercher par nom ou code-barres...');
INSERT INTO `translations` VALUES('951', 'fr', 'search_customer', 'Rechercher un client...');
INSERT INTO `translations` VALUES('952', 'fr', 'quick_add_customer', 'Ajout Rapide Client');
INSERT INTO `translations` VALUES('953', 'fr', 'select_customer', 'Sélectionner Client');
INSERT INTO `translations` VALUES('954', 'fr', 'walk_in', 'Comptant');
INSERT INTO `translations` VALUES('955', 'fr', 'stock_entry_title', 'Entrée de Stock');
INSERT INTO `translations` VALUES('956', 'fr', 'new_stock_entry', 'Nouvelle Entrée');
INSERT INTO `translations` VALUES('957', 'fr', 'entry_qty', 'Quantité d\'Entrée');
INSERT INTO `translations` VALUES('958', 'fr', 'invoice_ref', 'N° Facture / Bon');
INSERT INTO `translations` VALUES('959', 'fr', 'update_purchase_price', 'Mettre à jour le prix d\'achat');
INSERT INTO `translations` VALUES('960', 'fr', 'recent_entries', 'Entrées Récentes');
INSERT INTO `translations` VALUES('961', 'fr', 'stock_entry_done', 'Entrée de stock effectuée.');
INSERT INTO `translations` VALUES('962', 'fr', 'movement_in', 'Entrée');
INSERT INTO `translations` VALUES('963', 'fr', 'movement_out', 'Sortie');
INSERT INTO `translations` VALUES('964', 'fr', 'movement_adjust', 'Ajustement');
INSERT INTO `translations` VALUES('965', 'fr', 'movement_convert', 'Conversion');
INSERT INTO `translations` VALUES('966', 'fr', 'reference', 'Référence');
INSERT INTO `translations` VALUES('967', 'fr', 'source_product', 'Produit Source');
INSERT INTO `translations` VALUES('968', 'fr', 'target_product', 'Produit Cible');
INSERT INTO `translations` VALUES('969', 'fr', 'convert_ratio', 'Ratio de Conversion');
INSERT INTO `translations` VALUES('970', 'fr', 'period', 'Période');
INSERT INTO `translations` VALUES('971', 'fr', 'today', 'Aujourd\'hui');
INSERT INTO `translations` VALUES('972', 'fr', 'this_week', 'Cette Semaine');
INSERT INTO `translations` VALUES('973', 'fr', 'this_month', 'Ce Mois');
INSERT INTO `translations` VALUES('974', 'fr', 'this_year', 'Cette Année');
INSERT INTO `translations` VALUES('975', 'fr', 'custom', 'Personnalisé');
INSERT INTO `translations` VALUES('976', 'fr', 'start_date', 'Date de Début');
INSERT INTO `translations` VALUES('977', 'fr', 'end_date', 'Date de Fin');
INSERT INTO `translations` VALUES('978', 'fr', 'apply', 'Appliquer');
INSERT INTO `translations` VALUES('979', 'fr', 'gross_revenue', 'CA Brut');
INSERT INTO `translations` VALUES('980', 'fr', 'net_revenue', 'CA Net');
INSERT INTO `translations` VALUES('981', 'fr', 'collected', 'Encaissé');
INSERT INTO `translations` VALUES('982', 'fr', 'uncollected', 'Non Encaissé');
INSERT INTO `translations` VALUES('983', 'fr', 'cost', 'Coût');
INSERT INTO `translations` VALUES('984', 'fr', 'margin', 'Marge');
INSERT INTO `translations` VALUES('985', 'fr', 'category_dist', 'Répartition par Catégorie');
INSERT INTO `translations` VALUES('986', 'fr', 'top_customers', 'Meilleurs Clients');
INSERT INTO `translations` VALUES('987', 'fr', 'daily_chart', 'Graphique Journalier');
INSERT INTO `translations` VALUES('988', 'fr', 'spent', 'Dépensé');
INSERT INTO `translations` VALUES('989', 'fr', 'language', 'Langue');
INSERT INTO `translations` VALUES('990', 'fr', 'currency', 'Devise');
INSERT INTO `translations` VALUES('991', 'fr', 'settings_saved', 'Paramètres enregistrés.');
INSERT INTO `translations` VALUES('992', 'fr', 'appearance', 'Apparence');
INSERT INTO `translations` VALUES('993', 'fr', 'preferences', 'Préférences');
INSERT INTO `translations` VALUES('994', 'fr', 'default_currency', 'Devise par Défaut');
INSERT INTO `translations` VALUES('995', 'fr', 'select_language', 'Choisir la Langue');
INSERT INTO `translations` VALUES('996', 'fr', 'select_currency', 'Choisir la Devise');
INSERT INTO `translations` VALUES('997', 'fr', 'log_action', 'Action');
INSERT INTO `translations` VALUES('998', 'fr', 'log_user', 'Utilisateur');
INSERT INTO `translations` VALUES('999', 'fr', 'log_ip', 'IP');
INSERT INTO `translations` VALUES('1000', 'fr', 'log_detail', 'Détail');
INSERT INTO `translations` VALUES('1001', 'fr', 'log_time', 'Date / Heure');
INSERT INTO `translations` VALUES('1002', 'fr', 'added', 'Ajouté');
INSERT INTO `translations` VALUES('1003', 'fr', 'updated', 'Mis à Jour');
INSERT INTO `translations` VALUES('1004', 'fr', 'deleted', 'Supprimé');
INSERT INTO `translations` VALUES('1005', 'fr', 'payment', 'Paiement');
INSERT INTO `translations` VALUES('1006', 'fr', 'txt__value', '\"\r\n                    value=\"');
INSERT INTO `translations` VALUES('1007', 'fr', 'txt__onclickreturn_confirm', '\"\r\n                                        onclick=\"return confirm(\'');
INSERT INTO `translations` VALUES('1008', 'fr', 'txt__onclickreturn_confirmbu_yedek', '\"\r\n                                        onclick=\"return confirm(\'Bu yedek silinecek onaylıyor musunuz?\');\">');
INSERT INTO `translations` VALUES('1009', 'fr', 'txt__stylecolorvarwarningbackgroun', '\"\r\n                                                style=\"color:var(--warning);background:none;border:none;cursor:pointer;\">');
INSERT INTO `translations` VALUES('1010', 'fr', 'txt__stylefontsize18pxpadding14px', '\" style=\"font-size:18px;padding:14px;\">');
INSERT INTO `translations` VALUES('1011', 'fr', 'txt__maxlength10_styletexttransfor', '\"\r\n                                maxlength=\"10\" style=\"text-transform:uppercase;\">');
INSERT INTO `translations` VALUES('1012', 'fr', 'txt__maxlength10', '\"\r\n                                maxlength=\"10\">');
INSERT INTO `translations` VALUES('1013', 'fr', 'txt__required', '\" required>');
INSERT INTO `translations` VALUES('1014', 'fr', 'txt__autocompleteoff', '\" autocomplete=\"off\">');
INSERT INTO `translations` VALUES('1015', 'fr', 'txt_hizli_tahsilat', 'Hizli Tahsilat');
INSERT INTO `translations` VALUES('1016', 'fr', 'txt_musteri_secin', 'Musteri Secin');
INSERT INTO `translations` VALUES('1017', 'fr', 'txt__musteri_ara_sec', ' Musteri Ara Sec');
INSERT INTO `translations` VALUES('1018', 'fr', 'txt_guncel_cari_durum', 'Guncel Cari Durum');
INSERT INTO `translations` VALUES('1019', 'fr', 'txt_tahsilat_avans_tutari', 'Tahsilat Avans Tutari');
INSERT INTO `translations` VALUES('1020', 'fr', 'txt_orn_50000', 'Orn 50000');
INSERT INTO `translations` VALUES('1021', 'fr', 'txt_odeme_yontemi', 'Odeme Yontemi');
INSERT INTO `translations` VALUES('1022', 'fr', 'txt_nakit', 'Nakit');
INSERT INTO `translations` VALUES('1023', 'fr', 'txt_kredibanka_karti', 'Kredibanka Karti');
INSERT INTO `translations` VALUES('1024', 'fr', 'txt_havaleeft', 'Havaleeft');
INSERT INTO `translations` VALUES('1025', 'fr', 'txt_diger', 'Diger');
INSERT INTO `translations` VALUES('1026', 'fr', 'txt_aciklama_not', 'Aciklama Not');
INSERT INTO `translations` VALUES('1027', 'fr', 'txt_orn_ekim_ayi_avansi_eski_borc', 'Orn Ekim Ayi Avansi Eski Borc');
INSERT INTO `translations` VALUES('1028', 'fr', 'txt_tahsilati_tamamla', 'Tahsilati Tamamla');
INSERT INTO `translations` VALUES('1029', 'fr', 'txt_cari_hesap_ekstresi', 'Cari Hesap Ekstresi');
INSERT INTO `translations` VALUES('1030', 'fr', 'txt_cikti_al_pdf', 'Cikti Al Pdf');
INSERT INTO `translations` VALUES('1031', 'fr', 'txt_id', 'Id');
INSERT INTO `translations` VALUES('1032', 'fr', 'txt_telefon', 'Telefon');
INSERT INTO `translations` VALUES('1033', 'fr', 'txt_adres', 'Adres');
INSERT INTO `translations` VALUES('1034', 'fr', 'txt_tarih', 'Tarih');
INSERT INTO `translations` VALUES('1035', 'fr', 'txt_islem_turu_aciklama', 'Islem Turu Aciklama');
INSERT INTO `translations` VALUES('1036', 'fr', 'txt_borc_satis', 'Borc Satis');
INSERT INTO `translations` VALUES('1037', 'fr', 'txt_alacak_tahsilat', 'Alacak Tahsilat');
INSERT INTO `translations` VALUES('1038', 'fr', 'txt_yuruyen_bakiye', 'Yuruyen Bakiye');
INSERT INTO `translations` VALUES('1039', 'fr', 'txt_cari_hareket_bulunamadi', 'Cari Hareket Bulunamadi');
INSERT INTO `translations` VALUES('1040', 'fr', 'txt_satis_fatura', 'Satis Fatura');
INSERT INTO `translations` VALUES('1041', 'fr', 'txt_tahsilat_avans_makb', 'Tahsilat Avans Makb');
INSERT INTO `translations` VALUES('1042', 'fr', 'txt_not', 'Not');
INSERT INTO `translations` VALUES('1043', 'fr', 'txt_kategori_adi', 'Kategori Adi');
INSERT INTO `translations` VALUES('1044', 'fr', 'txt_aciklama', 'Aciklama');
INSERT INTO `translations` VALUES('1045', 'fr', 'txt_kisa_aciklama', 'Kisa Aciklama');
INSERT INTO `translations` VALUES('1046', 'fr', 'txt_iptal', 'Iptal');
INSERT INTO `translations` VALUES('1047', 'fr', 'txt_kategoriler', 'Kategoriler');
INSERT INTO `translations` VALUES('1048', 'fr', 'txt_urun', 'Urun');
INSERT INTO `translations` VALUES('1049', 'fr', 'txt_islem', 'Islem');
INSERT INTO `translations` VALUES('1050', 'fr', 'txt_kategori_yok', 'Kategori Yok');
INSERT INTO `translations` VALUES('1051', 'fr', 'txt_kategori', 'Kategori');
INSERT INTO `translations` VALUES('1052', 'fr', 'txt__sec', ' Sec');
INSERT INTO `translations` VALUES('1053', 'fr', 'txt_para_birimi', 'Para Birimi');
INSERT INTO `translations` VALUES('1054', 'fr', 'txt_alis_fiyati', 'Alis Fiyati');
INSERT INTO `translations` VALUES('1055', 'fr', 'txt_satis_fiyati', 'Satis Fiyati');
INSERT INTO `translations` VALUES('1056', 'fr', 'txt_birim', 'Birim');
INSERT INTO `translations` VALUES('1057', 'fr', 'txt_mevcut_stok', 'Mevcut Stok');
INSERT INTO `translations` VALUES('1058', 'fr', 'txt_kritik_stok_seviyesi', 'Kritik Stok Seviyesi');
INSERT INTO `translations` VALUES('1059', 'fr', 'txt_urun_aktif', 'Urun Aktif');
INSERT INTO `translations` VALUES('1060', 'fr', 'txt_urun_gorseli', 'Urun Gorseli');
INSERT INTO `translations` VALUES('1061', 'fr', 'txt_gorsel_sec_veya_surukle_birak', 'Gorsel Sec Veya Surukle Birak');
INSERT INTO `translations` VALUES('1062', 'fr', 'txt_jpg_png_webp_max_3mb', 'Jpg Png Webp Max 3mb');
INSERT INTO `translations` VALUES('1063', 'fr', 'txt_stok_degeri', 'Stock Value');
INSERT INTO `translations` VALUES('1064', 'fr', 'txt_hizli_donem', 'Hizli Donem');
INSERT INTO `translations` VALUES('1065', 'fr', 'txt_baslangic', 'Baslangic');
INSERT INTO `translations` VALUES('1066', 'fr', 'txt_bitis', 'Bitis');
INSERT INTO `translations` VALUES('1067', 'fr', 'txt_uygula', 'Uygula');
INSERT INTO `translations` VALUES('1068', 'fr', 'txt_disa_aktar', 'Disa Aktar');
INSERT INTO `translations` VALUES('1069', 'fr', 'txt_csv', 'Csv');
INSERT INTO `translations` VALUES('1070', 'fr', 'txt_yazdir', 'Yazdir');
INSERT INTO `translations` VALUES('1071', 'fr', 'txt_donem', 'Donem');
INSERT INTO `translations` VALUES('1072', 'fr', 'txt_satis_adedi', 'Satis Adedi');
INSERT INTO `translations` VALUES('1073', 'fr', 'txt_net_ciro', 'Net Ciro');
INSERT INTO `translations` VALUES('1074', 'fr', 'txt_kr', 'Kr');
INSERT INTO `translations` VALUES('1075', 'fr', 'txt_satilan_maliyet', 'Satilan Maliyet');
INSERT INTO `translations` VALUES('1076', 'fr', 'txt_tahsil_edilen', 'Tahsil Edilen');
INSERT INTO `translations` VALUES('1077', 'fr', 'txt_tahsilsiz', 'Tahsilsiz');
INSERT INTO `translations` VALUES('1078', 'fr', 'txt_gunluk_satis_grafigi', 'Gunluk Satis Grafigi');
INSERT INTO `translations` VALUES('1079', 'fr', 'txt_en_cok_satan_urunler', 'En Cok Satan Urunler');
INSERT INTO `translations` VALUES('1080', 'fr', 'txt_satilan', 'Satilan');
INSERT INTO `translations` VALUES('1081', 'fr', 'txt_ciro', 'Ciro');
INSERT INTO `translations` VALUES('1082', 'fr', 'txt_veri_yok', 'Veri Yok');
INSERT INTO `translations` VALUES('1083', 'fr', 'txt_kategori_dagilimi', 'Kategori Dagilimi');
INSERT INTO `translations` VALUES('1084', 'fr', 'txt_en_iyi_musteriler', 'En Iyi Musteriler');
INSERT INTO `translations` VALUES('1085', 'fr', 'txt_musteri', 'Musteri');
INSERT INTO `translations` VALUES('1086', 'fr', 'txt_satis', 'Satis');
INSERT INTO `translations` VALUES('1087', 'fr', 'txt_harcama', 'Harcama');
INSERT INTO `translations` VALUES('1088', 'fr', 'txt_borc', 'Borc');
INSERT INTO `translations` VALUES('1089', 'fr', 'txt_ara', 'Ara');
INSERT INTO `translations` VALUES('1090', 'fr', 'txt_islem_detay_veya_kullanici', 'Islem Detay Veya Kullanici');
INSERT INTO `translations` VALUES('1091', 'fr', 'txt_filtrele', 'Filtrele');
INSERT INTO `translations` VALUES('1092', 'fr', 'txt_9899bf', '9899bf');
INSERT INTO `translations` VALUES('1093', 'fr', 'txt_islem_loglari', 'Islem Loglari');
INSERT INTO `translations` VALUES('1094', 'fr', 'txt_toplam', 'Toplam');
INSERT INTO `translations` VALUES('1095', 'fr', 'txt_kayit', 'Kayit');
INSERT INTO `translations` VALUES('1096', 'fr', 'txt_tarih_saat', 'Tarih Saat');
INSERT INTO `translations` VALUES('1097', 'fr', 'txt_detay', 'Detay');
INSERT INTO `translations` VALUES('1098', 'fr', 'txt_kullanici', 'Kullanici');
INSERT INTO `translations` VALUES('1099', 'fr', 'txt_ip', 'Ip');
INSERT INTO `translations` VALUES('1100', 'fr', 'txt_log_bulunamadi', 'Log Bulunamadi');
INSERT INTO `translations` VALUES('1101', 'fr', 'txt_urun_ekle', 'Urun Ekle');
INSERT INTO `translations` VALUES('1102', 'fr', 'txt_ilac_adi_veya_barkod_ara', 'Ilac Adi Veya Barkod Ara');
INSERT INTO `translations` VALUES('1103', 'fr', 'txt_tum_kategoriler', 'Tum Kategoriler');
INSERT INTO `translations` VALUES('1104', 'fr', 'txt_stok_durumu', 'Stok Durumu');
INSERT INTO `translations` VALUES('1105', 'fr', 'txt_stokta_var', 'Stokta Var');
INSERT INTO `translations` VALUES('1106', 'fr', 'txt_stokta_yok', 'Stokta Yok');
INSERT INTO `translations` VALUES('1107', 'fr', 'txt_sepet', 'Sepet');
INSERT INTO `translations` VALUES('1108', 'fr', 'txt_temizle', 'Temizle');
INSERT INTO `translations` VALUES('1109', 'fr', 'txt_ilac', 'Ilac');
INSERT INTO `translations` VALUES('1110', 'fr', 'txt_birim_fiyat', 'Birim Fiyat');
INSERT INTO `translations` VALUES('1111', 'fr', 'txt_adet', 'Adet');
INSERT INTO `translations` VALUES('1112', 'fr', 'txt_sepet_bos_urun_ekleyin', 'Sepet Bos Urun Ekleyin');
INSERT INTO `translations` VALUES('1113', 'fr', 'txt_musteri_ara', 'Musteri Ara');
INSERT INTO `translations` VALUES('1114', 'fr', 'txt_ad_soyad_veya_telefon', 'Ad Soyad Veya Telefon');
INSERT INTO `translations` VALUES('1115', 'fr', 'txt_kaldir', 'Kaldir');
INSERT INTO `translations` VALUES('1116', 'fr', 'txt_hizli_musteri_ekle', 'Hizli Musteri Ekle');
INSERT INTO `translations` VALUES('1117', 'fr', 'txt_ad', 'Ad');
INSERT INTO `translations` VALUES('1118', 'fr', 'txt_soyad', 'Soyad');
INSERT INTO `translations` VALUES('1119', 'fr', 'txt_ekle_ve_sec', 'Ekle Ve Sec');
INSERT INTO `translations` VALUES('1120', 'fr', 'txt_odeme_ozeti', 'Odeme Ozeti');
INSERT INTO `translations` VALUES('1121', 'fr', 'txt_iskonto', 'Iskonto');
INSERT INTO `translations` VALUES('1122', 'fr', 'txt_yok', 'Yok');
INSERT INTO `translations` VALUES('1123', 'fr', 'txt_yuzde', 'Yuzde');
INSERT INTO `translations` VALUES('1124', 'fr', 'txt_sabit', 'Sabit');
INSERT INTO `translations` VALUES('1125', 'fr', 'txt_ara_toplam', 'Ara Toplam');
INSERT INTO `translations` VALUES('1126', 'fr', 'txt_net_tutar', 'Net Tutar');
INSERT INTO `translations` VALUES('1127', 'fr', 'txt_alinan_odeme', 'Alinan Odeme');
INSERT INTO `translations` VALUES('1128', 'fr', 'txt_tamamini_ode', 'Tamamini Ode');
INSERT INTO `translations` VALUES('1129', 'fr', 'txt_veresiye', 'Veresiye');
INSERT INTO `translations` VALUES('1130', 'fr', 'txt_kalan_borc', 'Kalan Borc');
INSERT INTO `translations` VALUES('1131', 'fr', 'txt_musteri_hesabina_borc_olarak_y', 'Musteri Hesabina Borc Olarak Y');
INSERT INTO `translations` VALUES('1132', 'fr', 'txt_satis_notu', 'Satis Notu');
INSERT INTO `translations` VALUES('1133', 'fr', 'txt_satisi_tamamla', 'Satisi Tamamla');
INSERT INTO `translations` VALUES('1134', 'fr', 'txt_sistem_yedekleri', 'Sistem Yedekleri');
INSERT INTO `translations` VALUES('1135', 'fr', 'txt_veritabaninin_tam_yedegini_ali', 'Veritabaninin Tam Yedegini Ali');
INSERT INTO `translations` VALUES('1136', 'fr', 'txt_yeni_yedek_al', 'Yeni Yedek Al');
INSERT INTO `translations` VALUES('1137', 'fr', 'txt_mevcut_yedekler', 'Mevcut Yedekler');
INSERT INTO `translations` VALUES('1138', 'fr', 'txt_dosya_adi', 'Dosya Adi');
INSERT INTO `translations` VALUES('1139', 'fr', 'txt_boyut', 'Boyut');
INSERT INTO `translations` VALUES('1140', 'fr', 'txt_olusturulma_tarihi', 'Olusturulma Tarihi');
INSERT INTO `translations` VALUES('1141', 'fr', 'txt_islemler', 'Islemler');
INSERT INTO `translations` VALUES('1142', 'fr', 'txt_hic_yedek_bulunamadi', 'Hic Yedek Bulunamadi');
INSERT INTO `translations` VALUES('1143', 'fr', 'txt_geri_yukle', 'Geri Yukle');
INSERT INTO `translations` VALUES('1144', 'fr', 'txt_veritabanina_yazilacaktir_mevc', 'Veritabanina Yazilacaktir Mevc');
INSERT INTO `translations` VALUES('1145', 'fr', 'txt__classbtnsmicon_btnedit_me1_ti', ' Classbtnsmicon Btnedit Me1 Ti');
INSERT INTO `translations` VALUES('1146', 'fr', 'txt_sil', 'Sil');
INSERT INTO `translations` VALUES('1147', 'fr', 'txt_uyari', 'Uyari');
INSERT INTO `translations` VALUES('1148', 'fr', 'txt_geri_yukleme_islemi_mevcut_tum', 'Geri Yukleme Islemi Mevcut Tum');
INSERT INTO `translations` VALUES('1149', 'fr', 'txt_geri_alinamaz', 'Geri Alinamaz');
INSERT INTO `translations` VALUES('1150', 'fr', 'txt_code', 'Code');
INSERT INTO `translations` VALUES('1151', 'fr', 'txt_symbol', 'Symbol');
INSERT INTO `translations` VALUES('1152', 'fr', 'txt_1_usd', '1 Usd');
INSERT INTO `translations` VALUES('1153', 'fr', 'txt_rate_date', 'Rate Date');
INSERT INTO `translations` VALUES('1154', 'fr', 'txt_1000000_base', '1000000 Base');
INSERT INTO `translations` VALUES('1155', 'fr', 'txt__no_rate', ' No Rate');
INSERT INTO `translations` VALUES('1156', 'fr', 'txt_base', 'Base');
INSERT INTO `translations` VALUES('1157', 'fr', 'txt_rate_history', 'Rate History');
INSERT INTO `translations` VALUES('1158', 'fr', 'txt_date', 'Date');
INSERT INTO `translations` VALUES('1159', 'fr', 'txt_entry_time', 'Entry Time');
INSERT INTO `translations` VALUES('1160', 'fr', 'txt_set_exchange_rate', 'Set Exchange Rate');
INSERT INTO `translations` VALUES('1161', 'fr', 'txt__select', ' Select');
INSERT INTO `translations` VALUES('1162', 'fr', 'txt_enter_how_many_units_of_this_c', 'Enter How Many Units Of This C');
INSERT INTO `translations` VALUES('1163', 'fr', 'txt_effective_date', 'Effective Date');
INSERT INTO `translations` VALUES('1164', 'fr', 'txt_rate_applies_from_this_date_fo', 'Rate Applies From This Date Fo');
INSERT INTO `translations` VALUES('1165', 'fr', 'txt_set_rate', 'Set Rate');
INSERT INTO `translations` VALUES('1166', 'fr', 'txt_add_currency', 'Add Currency');
INSERT INTO `translations` VALUES('1167', 'fr', 'txt_position', 'Position');
INSERT INTO `translations` VALUES('1168', 'fr', 'txt_currency_name', 'Currency Name');
INSERT INTO `translations` VALUES('1169', 'fr', 'txt_saudi_riyal', 'Saudi Riyal');
INSERT INTO `translations` VALUES('1170', 'fr', 'txt_decimal_sep', 'Decimal Sep');
INSERT INTO `translations` VALUES('1171', 'fr', 'txt_thousand_sep', 'Thousand Sep');
INSERT INTO `translations` VALUES('1172', 'fr', 'txt_initial_rate_1_usd', 'Initial Rate 1 Usd');
INSERT INTO `translations` VALUES('1173', 'fr', 'txt_optional', 'Optional');
INSERT INTO `translations` VALUES('1174', 'fr', 'txt_leave_empty_if_youll_set_the_r', 'Leave Empty If Youll Set The R');
INSERT INTO `translations` VALUES('1175', 'fr', 'txt_uygulama_temasi_ui_theme', 'Uygulama Temasi Ui Theme');
INSERT INTO `translations` VALUES('1176', 'fr', 'txt_ozel_renkler_custom', 'Ozel Renkler Custom');
INSERT INTO `translations` VALUES('1177', 'fr', 'txt_arkaplan_rengi_body', 'Arkaplan Rengi Body');
INSERT INTO `translations` VALUES('1178', 'fr', 'txt_sol_menu_sidebar', 'Sol Menu Sidebar');
INSERT INTO `translations` VALUES('1179', 'fr', 'txt_kart_arkaplani_panel', 'Kart Arkaplani Panel');
INSERT INTO `translations` VALUES('1180', 'fr', 'txt_ana_vurgu_accent', 'Ana Vurgu Accent');
INSERT INTO `translations` VALUES('1181', 'fr', 'txt_yazi_rengi_text', 'Yazi Rengi Text');
INSERT INTO `translations` VALUES('1182', 'fr', 'txt_site_logosu', 'Site Logosu');
INSERT INTO `translations` VALUES('1183', 'fr', 'txt_varsayilan_urun_gorseli', 'Varsayilan Urun Gorseli');
INSERT INTO `translations` VALUES('1184', 'fr', 'txt_preview_onizleme', 'Preview Onizleme');
INSERT INTO `translations` VALUES('1185', 'fr', 'txt_management', 'Management');
INSERT INTO `translations` VALUES('1186', 'fr', 'txt_addremove_currencies_set_daily', 'Addremove Currencies Set Daily');
INSERT INTO `translations` VALUES('1187', 'fr', 'txt_yeni_donusum', 'Yeni Donusum');
INSERT INTO `translations` VALUES('1188', 'fr', 'txt_ornek', 'Ornek');
INSERT INTO `translations` VALUES('1189', 'fr', 'txt_1_kutuluk_aspirin_kutu_urununu', '1 Kutuluk Aspirin Kutu Urununu');
INSERT INTO `translations` VALUES('1190', 'fr', 'txt_kaynak_urun_parcalanacak', 'Kaynak Urun Parcalanacak');
INSERT INTO `translations` VALUES('1191', 'fr', 'txt__urun_sec', ' Urun Sec');
INSERT INTO `translations` VALUES('1192', 'fr', 'txt_alinacak_miktar_kaynak', 'Alinacak Miktar Kaynak');
INSERT INTO `translations` VALUES('1193', 'fr', 'txt_kac_kutu', 'Kac Kutu');
INSERT INTO `translations` VALUES('1194', 'fr', 'txt_hedef_urun_eklenecek', 'Hedef Urun Eklenecek');
INSERT INTO `translations` VALUES('1195', 'fr', 'txt_mevcut', 'Mevcut');
INSERT INTO `translations` VALUES('1196', 'fr', 'txt_eklenecek_miktar_hedef', 'Eklenecek Miktar Hedef');
INSERT INTO `translations` VALUES('1197', 'fr', 'txt_kac_adet_eklenecek', 'Kac Adet Eklenecek');
INSERT INTO `translations` VALUES('1198', 'fr', 'txt_donusum_aciklamasi', 'Donusum Aciklamasi');
INSERT INTO `translations` VALUES('1199', 'fr', 'txt_donusumu_gerceklestir', 'Donusumu Gerceklestir');
INSERT INTO `translations` VALUES('1200', 'fr', 'txt_son_donusumler', 'Son Donusumler');
INSERT INTO `translations` VALUES('1201', 'fr', 'txt_miktar', 'Miktar');
INSERT INTO `translations` VALUES('1202', 'fr', 'txt_henuz_donusum_yok', 'Henuz Donusum Yok');
INSERT INTO `translations` VALUES('1203', 'fr', 'txt_yeni_stok_girisi', 'Yeni Stok Girisi');
INSERT INTO `translations` VALUES('1204', 'fr', 'txt_fatura_irsaliye_no', 'Fatura Irsaliye No');
INSERT INTO `translations` VALUES('1205', 'fr', 'txt_orn_ftr20260042', 'Orn Ftr20260042');
INSERT INTO `translations` VALUES('1206', 'fr', 'txt_alis_fiyatini_guncelle', 'Alis Fiyatini Guncelle');
INSERT INTO `translations` VALUES('1207', 'fr', 'txt_yeni_alis_fiyati', 'Yeni Alis Fiyati');
INSERT INTO `translations` VALUES('1208', 'fr', 'txt_istege_bagli_mevcut_resmi_gunc', 'Istege Bagli Mevcut Resmi Gunc');
INSERT INTO `translations` VALUES('1209', 'fr', 'txt_aciklama_tedarikci_bilgisi_vb', 'Aciklama Tedarikci Bilgisi Vb');
INSERT INTO `translations` VALUES('1210', 'fr', 'txt_stok_girisi_yap', 'Stok Girisi Yap');
INSERT INTO `translations` VALUES('1211', 'fr', 'txt_son_stok_girisleri', 'Son Stok Girisleri');
INSERT INTO `translations` VALUES('1212', 'fr', 'txt_tumunu_gor', 'Tumunu Gor');
INSERT INTO `translations` VALUES('1213', 'fr', 'txt_referans', 'Referans');
INSERT INTO `translations` VALUES('1214', 'fr', 'txt_henuz_stok_girisi_yapilmamis', 'Henuz Stok Girisi Yapilmamis');
INSERT INTO `translations` VALUES('1215', 'fr', 'txt_tumu', 'Tumu');
INSERT INTO `translations` VALUES('1216', 'fr', 'txt_tur', 'Tur');
INSERT INTO `translations` VALUES('1217', 'fr', 'txt_giris', 'Giris');
INSERT INTO `translations` VALUES('1218', 'fr', 'txt_cikis', 'Cikis');
INSERT INTO `translations` VALUES('1219', 'fr', 'txt_duzeltme', 'Duzeltme');
INSERT INTO `translations` VALUES('1220', 'fr', 'txt_donusum', 'Donusum');
INSERT INTO `translations` VALUES('1221', 'fr', 'txt_hareket_listesi', 'Hareket Listesi');
INSERT INTO `translations` VALUES('1222', 'fr', 'txt_kayit_yok', 'Kayit Yok');

DROP TABLE IF EXISTS `users`;
CREATE TABLE `users` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `first_name` varchar(100) DEFAULT NULL,
  `last_name` varchar(100) DEFAULT NULL,
  `session_timeout` int(10) unsigned DEFAULT 30,
  `created_at` datetime DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `username` (`username`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `users` VALUES('1', 'admin', '$2y$10$77gZHZdc7Uu3fal1zycsn.iqzqnnb.O2qgOWTxUSZHbzshk.wANP6', 'Hassan', 'S. KROMAH', '5', '2026-04-22 17:59:56');
INSERT INTO `users` VALUES('2', 'ismail', '$2y$10$OFCuVweRsZn/iquKaoiUkO7lI3a.gD8sMOP88HUetUxIsGIEw0qYm', 'İSMAİL', 'AKBIYIK', '5', '2026-04-22 18:01:46');
INSERT INTO `users` VALUES('3', 'mesut', '$2y$10$GFPTGea2NMsIFpUAsYU2y.M5MXVWMv7vgOlUwqX/hd9Ek2gjNVUie', 'MESUT', 'UÇAMAZ', '30', '2026-04-22 18:04:09');

SET FOREIGN_KEY_CHECKS=1;
