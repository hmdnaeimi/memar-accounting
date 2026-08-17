-- نسخه پشتیبان پایگاه داده
-- تاریخ و ساعت تهیه: 2026-08-17 22:37:05

SET FOREIGN_KEY_CHECKS=0;

DROP TABLE IF EXISTS `customers`;
CREATE TABLE `customers` (
  `id` int NOT NULL AUTO_INCREMENT,
  `first_name` varchar(120) NOT NULL,
  `last_name` varchar(120) NOT NULL,
  `national_code` varchar(20) DEFAULT NULL,
  `phone` varchar(40) NOT NULL,
  `economic_code` varchar(80) DEFAULT NULL,
  `registration_number` varchar(80) DEFAULT NULL,
  `address` text,
  `postal_code` varchar(40) DEFAULT NULL,
  `note` text,
  `total_spent` decimal(14,2) NOT NULL DEFAULT '0.00',
  `debt` decimal(14,2) NOT NULL DEFAULT '0.00',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

INSERT INTO `customers` (`id`, `first_name`, `last_name`, `national_code`, `phone`, `economic_code`, `registration_number`, `address`, `postal_code`, `note`, `total_spent`, `debt`, `created_at`) VALUES ('1', 'حمید', 'نعیمی', '2500034992', '09179079543', '', '', 'لامرد چاه قایدی کوچه عرفان', '7434178891', '', '0.00', '0.00', '2026-08-07 18:47:27');
INSERT INTO `customers` (`id`, `first_name`, `last_name`, `national_code`, `phone`, `economic_code`, `registration_number`, `address`, `postal_code`, `note`, `total_spent`, `debt`, `created_at`) VALUES ('7', 'تست', 'سس', '', '444', '', '', '', '', '', '0.00', '0.00', '2026-08-17 14:22:11');

DROP TABLE IF EXISTS `db_backup_settings`;
CREATE TABLE `db_backup_settings` (
  `id` int NOT NULL,
  `backup_dir` varchar(255) DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

INSERT INTO `db_backup_settings` (`id`, `backup_dir`, `updated_at`) VALUES ('1', 'C:\\backups', '2026-08-12 12:05:42');

DROP TABLE IF EXISTS `invoice_items`;
CREATE TABLE `invoice_items` (
  `id` int NOT NULL AUTO_INCREMENT,
  `invoice_id` int NOT NULL,
  `product_id` int NOT NULL,
  `quantity` decimal(14,2) NOT NULL,
  `unit_price` decimal(14,2) NOT NULL,
  `discount` decimal(14,2) NOT NULL DEFAULT '0.00',
  `line_total` decimal(14,2) NOT NULL DEFAULT '0.00',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_ii_invoice` (`invoice_id`),
  KEY `idx_ii_product` (`product_id`),
  CONSTRAINT `fk_ii_invoice` FOREIGN KEY (`invoice_id`) REFERENCES `invoices` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_ii_product` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `chk_ii_discount` CHECK ((`discount` >= 0)),
  CONSTRAINT `chk_ii_quantity` CHECK ((`quantity` > 0)),
  CONSTRAINT `chk_ii_unit_price` CHECK ((`unit_price` >= 0))
) ENGINE=InnoDB AUTO_INCREMENT=149 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `invoice_items` (`id`, `invoice_id`, `product_id`, `quantity`, `unit_price`, `discount`, `line_total`, `created_at`) VALUES ('104', '80', '1', '6.00', '2200.00', '0.00', '13200.00', '2026-08-14 14:16:31');
INSERT INTO `invoice_items` (`id`, `invoice_id`, `product_id`, `quantity`, `unit_price`, `discount`, `line_total`, `created_at`) VALUES ('105', '80', '2', '1.00', '2640.00', '0.00', '2640.00', '2026-08-14 14:16:31');
INSERT INTO `invoice_items` (`id`, `invoice_id`, `product_id`, `quantity`, `unit_price`, `discount`, `line_total`, `created_at`) VALUES ('110', '83', '1', '1.00', '144.00', '0.00', '144.00', '2026-08-17 14:28:51');
INSERT INTO `invoice_items` (`id`, `invoice_id`, `product_id`, `quantity`, `unit_price`, `discount`, `line_total`, `created_at`) VALUES ('111', '83', '2', '1.00', '1100.00', '0.00', '1100.00', '2026-08-17 14:28:51');
INSERT INTO `invoice_items` (`id`, `invoice_id`, `product_id`, `quantity`, `unit_price`, `discount`, `line_total`, `created_at`) VALUES ('120', '79', '1', '3.00', '2200.00', '0.00', '6600.00', '2026-08-17 14:46:19');
INSERT INTO `invoice_items` (`id`, `invoice_id`, `product_id`, `quantity`, `unit_price`, `discount`, `line_total`, `created_at`) VALUES ('121', '84', '2', '19.00', '1100.00', '0.00', '20900.00', '2026-08-17 15:08:06');
INSERT INTO `invoice_items` (`id`, `invoice_id`, `product_id`, `quantity`, `unit_price`, `discount`, `line_total`, `created_at`) VALUES ('122', '85', '1', '35.00', '144.00', '0.00', '5040.00', '2026-08-17 15:08:33');
INSERT INTO `invoice_items` (`id`, `invoice_id`, `product_id`, `quantity`, `unit_price`, `discount`, `line_total`, `created_at`) VALUES ('123', '85', '2', '35.00', '1100.00', '0.00', '38500.00', '2026-08-17 15:08:33');
INSERT INTO `invoice_items` (`id`, `invoice_id`, `product_id`, `quantity`, `unit_price`, `discount`, `line_total`, `created_at`) VALUES ('124', '85', '56', '35.00', '2999.00', '0.00', '104965.00', '2026-08-17 15:08:33');
INSERT INTO `invoice_items` (`id`, `invoice_id`, `product_id`, `quantity`, `unit_price`, `discount`, `line_total`, `created_at`) VALUES ('125', '85', '57', '35.00', '10000.00', '0.00', '350000.00', '2026-08-17 15:08:33');
INSERT INTO `invoice_items` (`id`, `invoice_id`, `product_id`, `quantity`, `unit_price`, `discount`, `line_total`, `created_at`) VALUES ('126', '85', '58', '35.00', '10000.00', '0.00', '350000.00', '2026-08-17 15:08:33');
INSERT INTO `invoice_items` (`id`, `invoice_id`, `product_id`, `quantity`, `unit_price`, `discount`, `line_total`, `created_at`) VALUES ('127', '85', '59', '35.00', '3000.00', '0.00', '105000.00', '2026-08-17 15:08:33');
INSERT INTO `invoice_items` (`id`, `invoice_id`, `product_id`, `quantity`, `unit_price`, `discount`, `line_total`, `created_at`) VALUES ('128', '85', '60', '35.00', '10000.00', '0.00', '350000.00', '2026-08-17 15:08:33');
INSERT INTO `invoice_items` (`id`, `invoice_id`, `product_id`, `quantity`, `unit_price`, `discount`, `line_total`, `created_at`) VALUES ('143', '91', '1', '1.00', '144.00', '0.00', '144.00', '2026-08-17 15:32:15');
INSERT INTO `invoice_items` (`id`, `invoice_id`, `product_id`, `quantity`, `unit_price`, `discount`, `line_total`, `created_at`) VALUES ('144', '87', '57', '1.00', '10000.00', '0.00', '10000.00', '2026-08-17 15:32:56');
INSERT INTO `invoice_items` (`id`, `invoice_id`, `product_id`, `quantity`, `unit_price`, `discount`, `line_total`, `created_at`) VALUES ('145', '87', '59', '1.00', '3000.00', '0.00', '3000.00', '2026-08-17 15:32:56');
INSERT INTO `invoice_items` (`id`, `invoice_id`, `product_id`, `quantity`, `unit_price`, `discount`, `line_total`, `created_at`) VALUES ('146', '92', '1', '10.00', '144.00', '0.00', '1440.00', '2026-08-17 15:33:26');
INSERT INTO `invoice_items` (`id`, `invoice_id`, `product_id`, `quantity`, `unit_price`, `discount`, `line_total`, `created_at`) VALUES ('148', '93', '2', '15.00', '100000.00', '0.00', '1500000.00', '2026-08-17 15:50:38');

DROP TABLE IF EXISTS `invoice_sequences`;
CREATE TABLE `invoice_sequences` (
  `seq_key` varchar(40) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `current_value` bigint unsigned NOT NULL DEFAULT '0',
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`seq_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `invoice_sequences` (`seq_key`, `current_value`, `updated_at`) VALUES ('purchase_invoice', '17', '2026-08-14 14:16:19');
INSERT INTO `invoice_sequences` (`seq_key`, `current_value`, `updated_at`) VALUES ('purchase_proforma', '6', '2026-08-14 13:10:59');
INSERT INTO `invoice_sequences` (`seq_key`, `current_value`, `updated_at`) VALUES ('sales_invoice', '55', '2026-08-17 15:50:18');
INSERT INTO `invoice_sequences` (`seq_key`, `current_value`, `updated_at`) VALUES ('sales_proforma', '7', '2026-08-14 14:13:23');

DROP TABLE IF EXISTS `invoice_settings`;
CREATE TABLE `invoice_settings` (
  `id` int NOT NULL,
  `unofficial_invoice_desc` text,
  `official_invoice_desc` text,
  `proforma_desc` text,
  `proforma_title` varchar(255) DEFAULT NULL,
  `invoice_template_color` varchar(20) DEFAULT NULL,
  `official_invoice_direction` varchar(20) DEFAULT 'vertical',
  `unofficial_invoice_direction` varchar(20) DEFAULT 'vertical',
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

INSERT INTO `invoice_settings` (`id`, `unofficial_invoice_desc`, `official_invoice_desc`, `proforma_desc`, `proforma_title`, `invoice_template_color`, `official_invoice_direction`, `unofficial_invoice_direction`, `updated_at`) VALUES ('1', 'توضیحات فاکتور غیر رسمی', 'توضیحات فاکتور رسمی', 'توضیحات پیش‌فاکتور', 'پیش فاکتور', '#2068ff', 'horizontal', 'horizontal', '2026-08-12 09:47:32');

DROP TABLE IF EXISTS `invoices`;
CREATE TABLE `invoices` (
  `id` int NOT NULL AUTO_INCREMENT,
  `invoice_number` varchar(40) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `type` enum('sales_invoice','sales_proforma','purchase_invoice','purchase_proforma') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `customer_id` int DEFAULT NULL,
  `supplier_id` int DEFAULT NULL,
  `payment_type` enum('cash','pos','bank_transfer') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pos',
  `payment_status` enum('paid','unpaid','partial') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'unpaid',
  `invoice_date` date NOT NULL,
  `subtotal` decimal(14,2) NOT NULL DEFAULT '0.00',
  `discount` decimal(14,2) NOT NULL DEFAULT '0.00',
  `tax_rate` decimal(5,2) NOT NULL DEFAULT '0.00',
  `tax_amount` decimal(14,2) NOT NULL DEFAULT '0.00',
  `payable_amount` decimal(14,2) NOT NULL DEFAULT '0.00',
  `note` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `client_token` varchar(64) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `invoice_number` (`invoice_number`),
  UNIQUE KEY `client_token` (`client_token`),
  KEY `idx_inv_type` (`type`),
  KEY `idx_inv_customer` (`customer_id`),
  KEY `idx_inv_supplier` (`supplier_id`),
  KEY `idx_inv_date` (`invoice_date`),
  CONSTRAINT `fk_inv_customer` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `fk_inv_supplier` FOREIGN KEY (`supplier_id`) REFERENCES `suppliers` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `chk_inv_party` CHECK ((((`type` like _utf8mb4'sales%') and (`customer_id` is not null) and (`supplier_id` is null)) or ((`type` like _utf8mb4'purchase%') and (`customer_id` is null) and (`supplier_id` is not null))))
) ENGINE=InnoDB AUTO_INCREMENT=94 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `invoices` (`id`, `invoice_number`, `type`, `customer_id`, `supplier_id`, `payment_type`, `payment_status`, `invoice_date`, `subtotal`, `discount`, `tax_rate`, `tax_amount`, `payable_amount`, `note`, `client_token`, `created_at`, `updated_at`) VALUES ('79', 'P-000016', 'purchase_invoice', NULL, '6', 'pos', 'unpaid', '2026-08-14', '6600.00', '0.00', '0.00', '0.00', '6600.00', NULL, '9f4d77f3-8955-45d2-9609-e308bf66ca0', '2026-08-14 14:15:10', '2026-08-17 14:46:19');
INSERT INTO `invoices` (`id`, `invoice_number`, `type`, `customer_id`, `supplier_id`, `payment_type`, `payment_status`, `invoice_date`, `subtotal`, `discount`, `tax_rate`, `tax_amount`, `payable_amount`, `note`, `client_token`, `created_at`, `updated_at`) VALUES ('80', 'P-000017', 'purchase_invoice', NULL, '1', 'pos', 'paid', '2026-08-14', '15840.00', '0.00', '0.00', '0.00', '15840.00', NULL, '657a6355-903a-42d5-bb00-b1ef4f84a088', '2026-08-14 14:16:19', '2026-08-14 14:16:31');
INSERT INTO `invoices` (`id`, `invoice_number`, `type`, `customer_id`, `supplier_id`, `payment_type`, `payment_status`, `invoice_date`, `subtotal`, `discount`, `tax_rate`, `tax_amount`, `payable_amount`, `note`, `client_token`, `created_at`, `updated_at`) VALUES ('83', 'S-000047', 'sales_invoice', '7', NULL, 'pos', 'paid', '2026-08-17', '1244.00', '0.00', '0.00', '0.00', '1244.00', NULL, 'd09348e5-4737-46cb-b7ae-e39573bd755', '2026-08-17 14:28:51', '2026-08-17 14:28:51');
INSERT INTO `invoices` (`id`, `invoice_number`, `type`, `customer_id`, `supplier_id`, `payment_type`, `payment_status`, `invoice_date`, `subtotal`, `discount`, `tax_rate`, `tax_amount`, `payable_amount`, `note`, `client_token`, `created_at`, `updated_at`) VALUES ('84', 'S-000048', 'sales_invoice', '1', NULL, 'pos', 'paid', '2026-08-17', '20900.00', '0.00', '0.00', '0.00', '20900.00', NULL, '8a77edd0-7662-4b81-8af0-fd5e26129e53', '2026-08-17 14:31:46', '2026-08-17 15:08:06');
INSERT INTO `invoices` (`id`, `invoice_number`, `type`, `customer_id`, `supplier_id`, `payment_type`, `payment_status`, `invoice_date`, `subtotal`, `discount`, `tax_rate`, `tax_amount`, `payable_amount`, `note`, `client_token`, `created_at`, `updated_at`) VALUES ('85', 'S-000049', 'sales_invoice', '1', NULL, 'pos', 'paid', '2026-08-17', '1303505.00', '0.00', '0.00', '0.00', '1303505.00', NULL, 'b506ac08-b8b5-4265-b359-07ad274545f2', '2026-08-17 14:36:38', '2026-08-17 15:08:33');
INSERT INTO `invoices` (`id`, `invoice_number`, `type`, `customer_id`, `supplier_id`, `payment_type`, `payment_status`, `invoice_date`, `subtotal`, `discount`, `tax_rate`, `tax_amount`, `payable_amount`, `note`, `client_token`, `created_at`, `updated_at`) VALUES ('87', 'S-000051', 'sales_invoice', '7', NULL, 'pos', 'unpaid', '2026-06-24', '13000.00', '0.00', '0.00', '0.00', '13000.00', NULL, '13061fbb-d84c-4bac-bb45-e71bd954194', '2026-08-17 15:13:37', '2026-08-17 15:32:56');
INSERT INTO `invoices` (`id`, `invoice_number`, `type`, `customer_id`, `supplier_id`, `payment_type`, `payment_status`, `invoice_date`, `subtotal`, `discount`, `tax_rate`, `tax_amount`, `payable_amount`, `note`, `client_token`, `created_at`, `updated_at`) VALUES ('91', 'S-000053', 'sales_invoice', '7', NULL, 'pos', 'unpaid', '2026-06-24', '144.00', '0.00', '0.00', '0.00', '144.00', NULL, 'ac1736f2-c9b4-fa3a-169e-4c18b80dcc', '2026-08-17 15:14:54', '2026-08-17 15:32:15');
INSERT INTO `invoices` (`id`, `invoice_number`, `type`, `customer_id`, `supplier_id`, `payment_type`, `payment_status`, `invoice_date`, `subtotal`, `discount`, `tax_rate`, `tax_amount`, `payable_amount`, `note`, `client_token`, `created_at`, `updated_at`) VALUES ('92', 'S-000054', 'sales_invoice', '7', NULL, 'pos', 'paid', '2026-08-17', '1440.00', '0.00', '0.00', '0.00', '1440.00', NULL, 'bda92062-8683-433a-9d9f-16a9bd22052c', '2026-08-17 15:33:26', '2026-08-17 15:33:26');
INSERT INTO `invoices` (`id`, `invoice_number`, `type`, `customer_id`, `supplier_id`, `payment_type`, `payment_status`, `invoice_date`, `subtotal`, `discount`, `tax_rate`, `tax_amount`, `payable_amount`, `note`, `client_token`, `created_at`, `updated_at`) VALUES ('93', 'S-000055', 'sales_invoice', '7', NULL, 'pos', 'paid', '2026-08-10', '1500000.00', '0.00', '0.00', '0.00', '1500000.00', NULL, '42a2e4a3-7c22-41c2-a75e-c03a163b926e', '2026-08-17 15:50:18', '2026-08-17 15:50:38');

DROP TABLE IF EXISTS `notes`;
CREATE TABLE `notes` (
  `id` int NOT NULL AUTO_INCREMENT,
  `title` varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `content` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `color` varchar(30) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '#fff3a3',
  `pos_x` int NOT NULL DEFAULT '30',
  `pos_y` int NOT NULL DEFAULT '30',
  `z_index` int NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_notes_z_index` (`z_index`),
  KEY `idx_notes_created` (`created_at`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `notes` (`id`, `title`, `content`, `color`, `pos_x`, `pos_y`, `z_index`, `created_at`, `updated_at`) VALUES ('2', 'عنوان', '', '#ffadad', '1296', '52', '106', '2026-08-17 22:31:52', '2026-08-17 22:36:29');

DROP TABLE IF EXISTS `product_categories`;
CREATE TABLE `product_categories` (
  `id` int NOT NULL AUTO_INCREMENT,
  `code` varchar(50) NOT NULL,
  `name` varchar(150) NOT NULL,
  `parent_id` int DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `code` (`code`),
  KEY `parent_id` (`parent_id`),
  CONSTRAINT `product_categories_ibfk_1` FOREIGN KEY (`parent_id`) REFERENCES `product_categories` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=14 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

INSERT INTO `product_categories` (`id`, `code`, `name`, `parent_id`, `created_at`) VALUES ('1', '1', 'لوازم طراحی', NULL, '2026-08-07 20:04:28');
INSERT INTO `product_categories` (`id`, `code`, `name`, `parent_id`, `created_at`) VALUES ('2', '2', 'دفتر', NULL, '2026-08-07 20:04:49');
INSERT INTO `product_categories` (`id`, `code`, `name`, `parent_id`, `created_at`) VALUES ('11', '11', 'ممم', '1', '2026-08-07 20:49:18');

DROP TABLE IF EXISTS `products`;
CREATE TABLE `products` (
  `id` int NOT NULL AUTO_INCREMENT,
  `code` varchar(50) NOT NULL,
  `name` varchar(150) NOT NULL,
  `category_id` int DEFAULT NULL,
  `type` enum('product','service') NOT NULL DEFAULT 'product',
  `unit` varchar(50) NOT NULL DEFAULT 'عدد',
  `purchase_price` decimal(14,2) NOT NULL DEFAULT '0.00',
  `sale_price` decimal(14,2) NOT NULL DEFAULT '0.00',
  `stock` decimal(14,2) NOT NULL DEFAULT '0.00',
  `min_stock` decimal(14,2) NOT NULL DEFAULT '0.00',
  `description` text,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `code` (`code`),
  KEY `category_id` (`category_id`),
  CONSTRAINT `products_ibfk_1` FOREIGN KEY (`category_id`) REFERENCES `product_categories` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=61 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

INSERT INTO `products` (`id`, `code`, `name`, `category_id`, `type`, `unit`, `purchase_price`, `sale_price`, `stock`, `min_stock`, `description`, `created_at`) VALUES ('1', '1', 'خودکار', '1', 'product', 'بسته', '2200.00', '144.00', '4.00', '20.00', '', '2026-08-13 14:01:43');
INSERT INTO `products` (`id`, `code`, `name`, `category_id`, `type`, `unit`, `purchase_price`, `sale_price`, `stock`, `min_stock`, `description`, `created_at`) VALUES ('2', '2', 'ماژیک', '2', 'product', 'عدد', '2640.00', '1100.00', '0.00', '20.00', '', '2026-08-13 14:21:14');
INSERT INTO `products` (`id`, `code`, `name`, `category_id`, `type`, `unit`, `purchase_price`, `sale_price`, `stock`, `min_stock`, `description`, `created_at`) VALUES ('56', '3', 'مداد', '1', 'product', 'عدد', '1000.00', '2999.00', '15.00', '20.00', '', '2026-08-17 14:33:03');
INSERT INTO `products` (`id`, `code`, `name`, `category_id`, `type`, `unit`, `purchase_price`, `sale_price`, `stock`, `min_stock`, `description`, `created_at`) VALUES ('57', '4', 'پاک کن', '1', 'product', 'عدد', '9000.00', '10000.00', '14.00', '20.00', '', '2026-08-17 14:33:22');
INSERT INTO `products` (`id`, `code`, `name`, `category_id`, `type`, `unit`, `purchase_price`, `sale_price`, `stock`, `min_stock`, `description`, `created_at`) VALUES ('58', '5', 'تراش', '1', 'product', 'عدد', '9000.00', '10000.00', '15.00', '20.00', '', '2026-08-17 14:33:49');
INSERT INTO `products` (`id`, `code`, `name`, `category_id`, `type`, `unit`, `purchase_price`, `sale_price`, `stock`, `min_stock`, `description`, `created_at`) VALUES ('59', '6', 'خودکار پنتر', '1', 'product', 'عدد', '2000.00', '3000.00', '14.00', '20.00', '', '2026-08-17 14:34:13');
INSERT INTO `products` (`id`, `code`, `name`, `category_id`, `type`, `unit`, `purchase_price`, `sale_price`, `stock`, `min_stock`, `description`, `created_at`) VALUES ('60', '7', 'سخا', '1', 'product', 'عدد', '1000.00', '10000.00', '15.00', '30.00', '', '2026-08-17 14:35:44');

DROP TABLE IF EXISTS `stock_movements`;
CREATE TABLE `stock_movements` (
  `id` int NOT NULL AUTO_INCREMENT,
  `product_id` int NOT NULL,
  `invoice_id` int DEFAULT NULL,
  `type` varchar(30) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `direction` enum('in','out') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `quantity` decimal(14,2) NOT NULL,
  `stock_before` decimal(14,2) NOT NULL,
  `stock_after` decimal(14,2) NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_sm_product` (`product_id`),
  KEY `idx_sm_invoice` (`invoice_id`),
  KEY `idx_sm_type` (`type`),
  KEY `idx_sm_created` (`created_at`),
  CONSTRAINT `fk_sm_invoice` FOREIGN KEY (`invoice_id`) REFERENCES `invoices` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_sm_product` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB AUTO_INCREMENT=126 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `stock_movements` (`id`, `product_id`, `invoice_id`, `type`, `direction`, `quantity`, `stock_before`, `stock_after`, `created_at`) VALUES ('78', '2', NULL, 'sale', 'out', '5.00', '20.00', '15.00', '2026-08-14 13:48:37');
INSERT INTO `stock_movements` (`id`, `product_id`, `invoice_id`, `type`, `direction`, `quantity`, `stock_before`, `stock_after`, `created_at`) VALUES ('79', '1', NULL, 'sale', 'out', '2.00', '100.00', '98.00', '2026-08-14 13:48:37');
INSERT INTO `stock_movements` (`id`, `product_id`, `invoice_id`, `type`, `direction`, `quantity`, `stock_before`, `stock_after`, `created_at`) VALUES ('80', '2', NULL, 'sale_cancel', 'in', '5.00', '15.00', '20.00', '2026-08-14 13:49:05');
INSERT INTO `stock_movements` (`id`, `product_id`, `invoice_id`, `type`, `direction`, `quantity`, `stock_before`, `stock_after`, `created_at`) VALUES ('81', '1', NULL, 'sale_cancel', 'in', '2.00', '98.00', '100.00', '2026-08-14 13:49:05');
INSERT INTO `stock_movements` (`id`, `product_id`, `invoice_id`, `type`, `direction`, `quantity`, `stock_before`, `stock_after`, `created_at`) VALUES ('82', '1', NULL, 'sale', 'out', '1.00', '100.00', '99.00', '2026-08-14 13:49:48');
INSERT INTO `stock_movements` (`id`, `product_id`, `invoice_id`, `type`, `direction`, `quantity`, `stock_before`, `stock_after`, `created_at`) VALUES ('83', '2', NULL, 'sale', 'out', '1.00', '20.00', '19.00', '2026-08-14 13:49:48');
INSERT INTO `stock_movements` (`id`, `product_id`, `invoice_id`, `type`, `direction`, `quantity`, `stock_before`, `stock_after`, `created_at`) VALUES ('84', '1', NULL, 'sale', 'out', '1.00', '99.00', '98.00', '2026-08-14 13:50:25');
INSERT INTO `stock_movements` (`id`, `product_id`, `invoice_id`, `type`, `direction`, `quantity`, `stock_before`, `stock_after`, `created_at`) VALUES ('85', '2', NULL, 'sale', 'out', '1.00', '19.00', '18.00', '2026-08-14 13:50:25');
INSERT INTO `stock_movements` (`id`, `product_id`, `invoice_id`, `type`, `direction`, `quantity`, `stock_before`, `stock_after`, `created_at`) VALUES ('86', '1', NULL, 'sale', 'out', '1.00', '98.00', '97.00', '2026-08-14 14:14:13');
INSERT INTO `stock_movements` (`id`, `product_id`, `invoice_id`, `type`, `direction`, `quantity`, `stock_before`, `stock_after`, `created_at`) VALUES ('87', '1', '79', 'purchase', 'in', '3.00', '97.00', '100.00', '2026-08-14 14:15:10');
INSERT INTO `stock_movements` (`id`, `product_id`, `invoice_id`, `type`, `direction`, `quantity`, `stock_before`, `stock_after`, `created_at`) VALUES ('88', '1', '80', 'purchase', 'in', '6.00', '100.00', '106.00', '2026-08-14 14:16:19');
INSERT INTO `stock_movements` (`id`, `product_id`, `invoice_id`, `type`, `direction`, `quantity`, `stock_before`, `stock_after`, `created_at`) VALUES ('89', '2', '80', 'purchase_edit', 'in', '1.00', '18.00', '19.00', '2026-08-14 14:16:31');
INSERT INTO `stock_movements` (`id`, `product_id`, `invoice_id`, `type`, `direction`, `quantity`, `stock_before`, `stock_after`, `created_at`) VALUES ('90', '1', NULL, 'sale', 'out', '1.00', '106.00', '105.00', '2026-08-15 13:56:33');
INSERT INTO `stock_movements` (`id`, `product_id`, `invoice_id`, `type`, `direction`, `quantity`, `stock_before`, `stock_after`, `created_at`) VALUES ('91', '1', NULL, 'sale', 'out', '1.00', '105.00', '104.00', '2026-08-16 13:41:08');
INSERT INTO `stock_movements` (`id`, `product_id`, `invoice_id`, `type`, `direction`, `quantity`, `stock_before`, `stock_after`, `created_at`) VALUES ('92', '2', NULL, 'sale', 'out', '1.00', '19.00', '18.00', '2026-08-16 13:41:08');
INSERT INTO `stock_movements` (`id`, `product_id`, `invoice_id`, `type`, `direction`, `quantity`, `stock_before`, `stock_after`, `created_at`) VALUES ('93', '1', NULL, 'sale_cancel', 'in', '1.00', '104.00', '105.00', '2026-08-17 14:20:43');
INSERT INTO `stock_movements` (`id`, `product_id`, `invoice_id`, `type`, `direction`, `quantity`, `stock_before`, `stock_after`, `created_at`) VALUES ('94', '2', NULL, 'sale_cancel', 'in', '1.00', '18.00', '19.00', '2026-08-17 14:20:43');
INSERT INTO `stock_movements` (`id`, `product_id`, `invoice_id`, `type`, `direction`, `quantity`, `stock_before`, `stock_after`, `created_at`) VALUES ('95', '1', NULL, 'sale_cancel', 'in', '1.00', '105.00', '106.00', '2026-08-17 14:20:46');
INSERT INTO `stock_movements` (`id`, `product_id`, `invoice_id`, `type`, `direction`, `quantity`, `stock_before`, `stock_after`, `created_at`) VALUES ('96', '1', NULL, 'sale_cancel', 'in', '1.00', '106.00', '107.00', '2026-08-17 14:20:50');
INSERT INTO `stock_movements` (`id`, `product_id`, `invoice_id`, `type`, `direction`, `quantity`, `stock_before`, `stock_after`, `created_at`) VALUES ('97', '1', NULL, 'sale_cancel', 'in', '1.00', '107.00', '108.00', '2026-08-17 14:20:53');
INSERT INTO `stock_movements` (`id`, `product_id`, `invoice_id`, `type`, `direction`, `quantity`, `stock_before`, `stock_after`, `created_at`) VALUES ('98', '2', NULL, 'sale_cancel', 'in', '1.00', '19.00', '20.00', '2026-08-17 14:20:53');
INSERT INTO `stock_movements` (`id`, `product_id`, `invoice_id`, `type`, `direction`, `quantity`, `stock_before`, `stock_after`, `created_at`) VALUES ('99', '1', NULL, 'sale_cancel', 'in', '1.00', '108.00', '109.00', '2026-08-17 14:20:57');
INSERT INTO `stock_movements` (`id`, `product_id`, `invoice_id`, `type`, `direction`, `quantity`, `stock_before`, `stock_after`, `created_at`) VALUES ('100', '2', NULL, 'sale_cancel', 'in', '1.00', '20.00', '21.00', '2026-08-17 14:20:57');
INSERT INTO `stock_movements` (`id`, `product_id`, `invoice_id`, `type`, `direction`, `quantity`, `stock_before`, `stock_after`, `created_at`) VALUES ('101', '1', '83', 'sale', 'out', '1.00', '109.00', '108.00', '2026-08-17 14:28:51');
INSERT INTO `stock_movements` (`id`, `product_id`, `invoice_id`, `type`, `direction`, `quantity`, `stock_before`, `stock_after`, `created_at`) VALUES ('102', '2', '83', 'sale', 'out', '1.00', '21.00', '20.00', '2026-08-17 14:28:51');
INSERT INTO `stock_movements` (`id`, `product_id`, `invoice_id`, `type`, `direction`, `quantity`, `stock_before`, `stock_after`, `created_at`) VALUES ('103', '2', '84', 'sale', 'out', '19.00', '20.00', '1.00', '2026-08-17 14:31:46');
INSERT INTO `stock_movements` (`id`, `product_id`, `invoice_id`, `type`, `direction`, `quantity`, `stock_before`, `stock_after`, `created_at`) VALUES ('104', '1', '85', 'sale', 'out', '35.00', '50.00', '15.00', '2026-08-17 14:36:38');
INSERT INTO `stock_movements` (`id`, `product_id`, `invoice_id`, `type`, `direction`, `quantity`, `stock_before`, `stock_after`, `created_at`) VALUES ('105', '2', '85', 'sale', 'out', '35.00', '50.00', '15.00', '2026-08-17 14:36:38');
INSERT INTO `stock_movements` (`id`, `product_id`, `invoice_id`, `type`, `direction`, `quantity`, `stock_before`, `stock_after`, `created_at`) VALUES ('106', '56', '85', 'sale', 'out', '35.00', '50.00', '15.00', '2026-08-17 14:36:38');
INSERT INTO `stock_movements` (`id`, `product_id`, `invoice_id`, `type`, `direction`, `quantity`, `stock_before`, `stock_after`, `created_at`) VALUES ('107', '57', '85', 'sale', 'out', '35.00', '50.00', '15.00', '2026-08-17 14:36:38');
INSERT INTO `stock_movements` (`id`, `product_id`, `invoice_id`, `type`, `direction`, `quantity`, `stock_before`, `stock_after`, `created_at`) VALUES ('108', '58', '85', 'sale', 'out', '35.00', '50.00', '15.00', '2026-08-17 14:36:38');
INSERT INTO `stock_movements` (`id`, `product_id`, `invoice_id`, `type`, `direction`, `quantity`, `stock_before`, `stock_after`, `created_at`) VALUES ('109', '59', '85', 'sale', 'out', '35.00', '50.00', '15.00', '2026-08-17 14:36:38');
INSERT INTO `stock_movements` (`id`, `product_id`, `invoice_id`, `type`, `direction`, `quantity`, `stock_before`, `stock_after`, `created_at`) VALUES ('110', '60', '85', 'sale', 'out', '35.00', '50.00', '15.00', '2026-08-17 14:36:38');
INSERT INTO `stock_movements` (`id`, `product_id`, `invoice_id`, `type`, `direction`, `quantity`, `stock_before`, `stock_after`, `created_at`) VALUES ('111', '1', NULL, 'sale', 'out', '1.00', '15.00', '14.00', '2026-08-17 15:13:27');
INSERT INTO `stock_movements` (`id`, `product_id`, `invoice_id`, `type`, `direction`, `quantity`, `stock_before`, `stock_after`, `created_at`) VALUES ('112', '57', '87', 'sale', 'out', '1.00', '15.00', '14.00', '2026-08-17 15:13:37');
INSERT INTO `stock_movements` (`id`, `product_id`, `invoice_id`, `type`, `direction`, `quantity`, `stock_before`, `stock_after`, `created_at`) VALUES ('113', '59', '87', 'sale', 'out', '1.00', '15.00', '14.00', '2026-08-17 15:13:37');
INSERT INTO `stock_movements` (`id`, `product_id`, `invoice_id`, `type`, `direction`, `quantity`, `stock_before`, `stock_after`, `created_at`) VALUES ('114', '60', NULL, 'sale', 'out', '11.00', '15.00', '4.00', '2026-08-17 15:14:22');
INSERT INTO `stock_movements` (`id`, `product_id`, `invoice_id`, `type`, `direction`, `quantity`, `stock_before`, `stock_after`, `created_at`) VALUES ('115', '59', NULL, 'sale', 'out', '11.00', '14.00', '3.00', '2026-08-17 15:14:22');
INSERT INTO `stock_movements` (`id`, `product_id`, `invoice_id`, `type`, `direction`, `quantity`, `stock_before`, `stock_after`, `created_at`) VALUES ('116', '58', NULL, 'sale', 'out', '11.00', '15.00', '4.00', '2026-08-17 15:14:22');
INSERT INTO `stock_movements` (`id`, `product_id`, `invoice_id`, `type`, `direction`, `quantity`, `stock_before`, `stock_after`, `created_at`) VALUES ('117', '56', NULL, 'sale', 'out', '1.00', '15.00', '14.00', '2026-08-17 15:14:22');
INSERT INTO `stock_movements` (`id`, `product_id`, `invoice_id`, `type`, `direction`, `quantity`, `stock_before`, `stock_after`, `created_at`) VALUES ('118', '1', '91', 'sale', 'out', '1.00', '14.00', '13.00', '2026-08-17 15:14:54');
INSERT INTO `stock_movements` (`id`, `product_id`, `invoice_id`, `type`, `direction`, `quantity`, `stock_before`, `stock_after`, `created_at`) VALUES ('119', '1', NULL, 'sale_cancel', 'in', '1.00', '13.00', '14.00', '2026-08-17 15:30:30');
INSERT INTO `stock_movements` (`id`, `product_id`, `invoice_id`, `type`, `direction`, `quantity`, `stock_before`, `stock_after`, `created_at`) VALUES ('120', '60', NULL, 'sale_cancel', 'in', '11.00', '4.00', '15.00', '2026-08-17 15:30:34');
INSERT INTO `stock_movements` (`id`, `product_id`, `invoice_id`, `type`, `direction`, `quantity`, `stock_before`, `stock_after`, `created_at`) VALUES ('121', '59', NULL, 'sale_cancel', 'in', '11.00', '3.00', '14.00', '2026-08-17 15:30:34');
INSERT INTO `stock_movements` (`id`, `product_id`, `invoice_id`, `type`, `direction`, `quantity`, `stock_before`, `stock_after`, `created_at`) VALUES ('122', '58', NULL, 'sale_cancel', 'in', '11.00', '4.00', '15.00', '2026-08-17 15:30:34');
INSERT INTO `stock_movements` (`id`, `product_id`, `invoice_id`, `type`, `direction`, `quantity`, `stock_before`, `stock_after`, `created_at`) VALUES ('123', '56', NULL, 'sale_cancel', 'in', '1.00', '14.00', '15.00', '2026-08-17 15:30:34');
INSERT INTO `stock_movements` (`id`, `product_id`, `invoice_id`, `type`, `direction`, `quantity`, `stock_before`, `stock_after`, `created_at`) VALUES ('124', '1', '92', 'sale', 'out', '10.00', '14.00', '4.00', '2026-08-17 15:33:26');
INSERT INTO `stock_movements` (`id`, `product_id`, `invoice_id`, `type`, `direction`, `quantity`, `stock_before`, `stock_after`, `created_at`) VALUES ('125', '2', '93', 'sale', 'out', '15.00', '15.00', '0.00', '2026-08-17 15:50:18');

DROP TABLE IF EXISTS `store_settings`;
CREATE TABLE `store_settings` (
  `id` int NOT NULL,
  `store_name` varchar(255) NOT NULL,
  `economic_code` varchar(80) DEFAULT NULL,
  `national_code` varchar(80) DEFAULT NULL,
  `registration_number` varchar(80) DEFAULT NULL,
  `province_id` int DEFAULT NULL,
  `city_id` int DEFAULT NULL,
  `postal_code` varchar(40) DEFAULT NULL,
  `phone` varchar(60) DEFAULT NULL,
  `address` text,
  `logo_path` varchar(255) DEFAULT NULL,
  `signature_path` varchar(255) DEFAULT NULL,
  `stamp_path` varchar(255) DEFAULT NULL,
  `default_size_percentage` int DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

INSERT INTO `store_settings` (`id`, `store_name`, `economic_code`, `national_code`, `registration_number`, `province_id`, `city_id`, `postal_code`, `phone`, `address`, `logo_path`, `signature_path`, `stamp_path`, `default_size_percentage`, `updated_at`) VALUES ('1', 'کتابفروشی معمار', '', '2500034992', '', '17', '669', '7434178891', '09179079543', 'لامرد', 'assets/uploads/logo_image_1786188618.jpg', 'assets/uploads/signature_image_1786188618.jpg', 'assets/uploads/stamp_image_1786188618.jpg', '80', '2026-08-08 15:00:18');

DROP TABLE IF EXISTS `suppliers`;
CREATE TABLE `suppliers` (
  `id` int NOT NULL AUTO_INCREMENT,
  `company_name` varchar(150) DEFAULT NULL,
  `first_name` varchar(120) DEFAULT NULL,
  `last_name` varchar(120) DEFAULT NULL,
  `national_code` varchar(20) DEFAULT NULL,
  `phone` varchar(40) NOT NULL,
  `economic_code` varchar(80) DEFAULT NULL,
  `registration_number` varchar(80) DEFAULT NULL,
  `address` text,
  `postal_code` varchar(40) DEFAULT NULL,
  `note` text,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

INSERT INTO `suppliers` (`id`, `company_name`, `first_name`, `last_name`, `national_code`, `phone`, `economic_code`, `registration_number`, `address`, `postal_code`, `note`, `created_at`) VALUES ('1', 'دنیای تحریر کرمی', 'حاج علی', 'کرمی', '', '0999999999', '', '', 'لامرد', '', '', '2026-08-12 09:35:25');
INSERT INTO `suppliers` (`id`, `company_name`, `first_name`, `last_name`, `national_code`, `phone`, `economic_code`, `registration_number`, `address`, `postal_code`, `note`, `created_at`) VALUES ('6', 'خواجه', 'خواجه', 'خواجه', '', '000', '', '', '', '', '', '2026-08-17 14:46:07');

DROP TABLE IF EXISTS `tax_settings`;
CREATE TABLE `tax_settings` (
  `id` int NOT NULL,
  `tax_enabled` tinyint(1) NOT NULL DEFAULT '0',
  `tax_rate` decimal(5,2) NOT NULL DEFAULT '0.00',
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

INSERT INTO `tax_settings` (`id`, `tax_enabled`, `tax_rate`, `updated_at`) VALUES ('1', '0', '10.00', '2026-08-14 14:15:46');

SET FOREIGN_KEY_CHECKS=1;
