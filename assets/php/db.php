<?php
$dbHost = '127.0.0.1';
$dbUser = 'root';
$dbPass = '';
$dbName = 'memar_accounting';

$mysqli = new mysqli($dbHost, $dbUser, $dbPass);
if ($mysqli->connect_errno) {
    die('خطا در اتصال به دیتابیس: ' . $mysqli->connect_error);
}

$mysqli->query("CREATE DATABASE IF NOT EXISTS `{$dbName}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
$mysqli->select_db($dbName);
$mysqli->set_charset('utf8mb4');

$createCustomers = "CREATE TABLE IF NOT EXISTS customers (
    id INT AUTO_INCREMENT PRIMARY KEY,
    first_name VARCHAR(120) NOT NULL,
    last_name VARCHAR(120) NOT NULL,
    national_code VARCHAR(20) DEFAULT NULL,
    phone VARCHAR(40) NOT NULL,
    economic_code VARCHAR(80) DEFAULT NULL,
    registration_number VARCHAR(80) DEFAULT NULL,
    address TEXT DEFAULT NULL,
    postal_code VARCHAR(40) DEFAULT NULL,
    note TEXT DEFAULT NULL,
    total_spent DECIMAL(14,2) NOT NULL DEFAULT 0,
    debt DECIMAL(14,2) NOT NULL DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";

$mysqli->query($createCustomers);

$createSuppliers = "CREATE TABLE IF NOT EXISTS suppliers (
    id INT AUTO_INCREMENT PRIMARY KEY,
    company_name VARCHAR(150) DEFAULT NULL,
    first_name VARCHAR(120) DEFAULT NULL,
    last_name VARCHAR(120) DEFAULT NULL,
    national_code VARCHAR(20) DEFAULT NULL,
    phone VARCHAR(40) NOT NULL,
    economic_code VARCHAR(80) DEFAULT NULL,
    registration_number VARCHAR(80) DEFAULT NULL,
    address TEXT DEFAULT NULL,
    postal_code VARCHAR(40) DEFAULT NULL,
    note TEXT DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";

$mysqli->query($createSuppliers);

$createCategories = "CREATE TABLE IF NOT EXISTS product_categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    code VARCHAR(50) NOT NULL UNIQUE,
    name VARCHAR(150) NOT NULL,
    parent_id INT DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (parent_id) REFERENCES product_categories(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";

$mysqli->query($createCategories);

$createProducts = "CREATE TABLE IF NOT EXISTS products (
    id INT AUTO_INCREMENT PRIMARY KEY,
    code VARCHAR(50) NOT NULL UNIQUE,
    name VARCHAR(150) NOT NULL,
    category_id INT DEFAULT NULL,
    type ENUM('product','service') NOT NULL DEFAULT 'product',
    unit VARCHAR(50) NOT NULL DEFAULT 'عدد',
    purchase_price DECIMAL(14,2) NOT NULL DEFAULT 0,
    sale_price DECIMAL(14,2) NOT NULL DEFAULT 0,
    stock DECIMAL(14,2) NOT NULL DEFAULT 0,
    min_stock DECIMAL(14,2) NOT NULL DEFAULT 0,
    description TEXT DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (category_id) REFERENCES product_categories(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";

$mysqli->query($createProducts);

$createStoreSettings = "CREATE TABLE IF NOT EXISTS store_settings (
    id INT PRIMARY KEY,
    store_name VARCHAR(255) NOT NULL,
    economic_code VARCHAR(80) DEFAULT NULL,
    national_code VARCHAR(80) DEFAULT NULL,
    registration_number VARCHAR(80) DEFAULT NULL,
    province_id INT DEFAULT NULL,
    city_id INT DEFAULT NULL,
    postal_code VARCHAR(40) DEFAULT NULL,
    phone VARCHAR(60) DEFAULT NULL,
    address TEXT DEFAULT NULL,
    logo_path VARCHAR(255) DEFAULT NULL,
    signature_path VARCHAR(255) DEFAULT NULL,
    stamp_path VARCHAR(255) DEFAULT NULL,
    default_size_percentage INT DEFAULT NULL,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";

$mysqli->query($createStoreSettings);

$createInvoiceSettings = "CREATE TABLE IF NOT EXISTS invoice_settings (
    id INT PRIMARY KEY,
    unofficial_invoice_desc TEXT DEFAULT NULL,
    official_invoice_desc TEXT DEFAULT NULL,
    proforma_desc TEXT DEFAULT NULL,
    proforma_title VARCHAR(255) DEFAULT NULL,
    invoice_template_color VARCHAR(20) DEFAULT NULL,
    official_invoice_direction VARCHAR(20) DEFAULT 'vertical',
    unofficial_invoice_direction VARCHAR(20) DEFAULT 'vertical',
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";

$mysqli->query($createInvoiceSettings);

$createTaxSettings = "CREATE TABLE IF NOT EXISTS tax_settings (
    id INT PRIMARY KEY,
    tax_enabled TINYINT(1) NOT NULL DEFAULT 0,
    tax_rate DECIMAL(5,2) NOT NULL DEFAULT 0,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";

$mysqli->query($createTaxSettings);

$createDbBackupSettings = "CREATE TABLE IF NOT EXISTS db_backup_settings (
    id INT PRIMARY KEY,
    backup_dir VARCHAR(255) DEFAULT NULL,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";

$mysqli->query($createDbBackupSettings);

/* ============================================================
 * ماژول فاکتور فروش/خرید — جداول جدید (Invoice Module)
 * ========================================================== */

/* --- شمارنده اتمیک شماره فاکتور --- */
$createInvoiceSequences = "CREATE TABLE IF NOT EXISTS invoice_sequences (
    seq_key VARCHAR(40) PRIMARY KEY,
    current_value BIGINT UNSIGNED NOT NULL DEFAULT 0,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";
$mysqli->query($createInvoiceSequences);

/* اطمینان از وجود ۴ سری شماره */
$mysqli->query("INSERT IGNORE INTO invoice_sequences (seq_key, current_value) VALUES
    ('sales_invoice', 0),
    ('sales_proforma', 0),
    ('purchase_invoice', 0),
    ('purchase_proforma', 0)");

/* --- فاکتورها (فروش/خرید/پیش‌فاکتور) --- */
$createInvoices = "CREATE TABLE IF NOT EXISTS invoices (
    id INT AUTO_INCREMENT PRIMARY KEY,
    invoice_number VARCHAR(40) NOT NULL UNIQUE,
    type ENUM('sales_invoice','sales_proforma','purchase_invoice','purchase_proforma') NOT NULL,
    customer_id INT NULL,
    supplier_id INT NULL,
    payment_type ENUM('cash','pos','bank_transfer') NOT NULL DEFAULT 'pos',
    payment_status ENUM('paid','unpaid','partial') NOT NULL DEFAULT 'unpaid',
    invoice_date DATE NOT NULL,
    subtotal DECIMAL(14,2) NOT NULL DEFAULT 0,
    discount DECIMAL(14,2) NOT NULL DEFAULT 0,
    tax_rate DECIMAL(5,2) NOT NULL DEFAULT 0,
    tax_amount DECIMAL(14,2) NOT NULL DEFAULT 0,
    payable_amount DECIMAL(14,2) NOT NULL DEFAULT 0,
    note TEXT NULL,
    client_token VARCHAR(64) NULL UNIQUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    KEY idx_inv_type (type),
    KEY idx_inv_customer (customer_id),
    KEY idx_inv_supplier (supplier_id),
    KEY idx_inv_date (invoice_date),
    CONSTRAINT fk_inv_customer FOREIGN KEY (customer_id) REFERENCES customers(id) ON DELETE RESTRICT,
    CONSTRAINT fk_inv_supplier FOREIGN KEY (supplier_id) REFERENCES suppliers(id) ON DELETE RESTRICT,
    CONSTRAINT chk_inv_party CHECK (
        (type LIKE 'sales%' AND customer_id IS NOT NULL AND supplier_id IS NULL)
        OR
        (type LIKE 'purchase%' AND customer_id IS NULL AND supplier_id IS NOT NULL)
    )
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";
$mysqli->query($createInvoices);

/* --- اقلام فاکتور --- */
$createInvoiceItems = "CREATE TABLE IF NOT EXISTS invoice_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    invoice_id INT NOT NULL,
    product_id INT NOT NULL,
    quantity DECIMAL(14,2) NOT NULL,
    unit_price DECIMAL(14,2) NOT NULL,
    discount DECIMAL(14,2) NOT NULL DEFAULT 0,
    line_total DECIMAL(14,2) NOT NULL DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    KEY idx_ii_invoice (invoice_id),
    KEY idx_ii_product (product_id),
    CONSTRAINT fk_ii_invoice FOREIGN KEY (invoice_id) REFERENCES invoices(id) ON DELETE CASCADE,
    CONSTRAINT fk_ii_product FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE RESTRICT,
    CONSTRAINT chk_ii_quantity CHECK (quantity > 0),
    CONSTRAINT chk_ii_unit_price CHECK (unit_price >= 0),
    CONSTRAINT chk_ii_discount CHECK (discount >= 0)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";
$mysqli->query($createInvoiceItems);

/* --- گردش موجودی (Audit Trail دائمی) --- */
$createStockMovements = "CREATE TABLE IF NOT EXISTS stock_movements (
    id INT AUTO_INCREMENT PRIMARY KEY,
    product_id INT NOT NULL,
    invoice_id INT NULL,
    type VARCHAR(30) NOT NULL,
    direction ENUM('in','out') NOT NULL,
    quantity DECIMAL(14,2) NOT NULL,
    stock_before DECIMAL(14,2) NOT NULL,
    stock_after DECIMAL(14,2) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    KEY idx_sm_product (product_id),
    KEY idx_sm_invoice (invoice_id),
    KEY idx_sm_type (type),
    KEY idx_sm_created (created_at),
    CONSTRAINT fk_sm_product FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE RESTRICT,
    CONSTRAINT fk_sm_invoice FOREIGN KEY (invoice_id) REFERENCES invoices(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";
$mysqli->query($createStockMovements);
/* ============================================================
 * Sticky Notes
 * ========================================================== */

$createNotes = "CREATE TABLE IF NOT EXISTS notes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(150) NOT NULL DEFAULT '',
    content TEXT NOT NULL,
    color VARCHAR(30) NOT NULL DEFAULT '#fff3a3',
    pos_x INT NOT NULL DEFAULT 30,
    pos_y INT NOT NULL DEFAULT 30,
    z_index INT NOT NULL DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    KEY idx_notes_z_index (z_index),
    KEY idx_notes_created (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";

$mysqli->query($createNotes);
