<?php
/**
 * invoice_products.php — جستجوی کالا برای مودال انتخاب (GET بدون CSRF)
 * بازگشت: محصولات شامل قیمت فروش/خرید و موجودی
 */

require_once __DIR__ . '/boot.php';
require_once __DIR__ . '/db.php';

$search = trim((string) ($_GET['search'] ?? ''));
$category = trim((string) ($_GET['category'] ?? ''));

$where = [];
$types = '';
$params = [];

if ($search !== '') {
    $like = '%' . $search . '%';
    $where[] = '(code LIKE ? OR name LIKE ?)';
    $types .= 'ss';
    $params[] = $like;
    $params[] = $like;
}
if ($category !== '' && ctype_digit($category)) {
    $where[] = 'category_id = ?';
    $types .= 'i';
    $params[] = (int) $category;
}

$sql = 'SELECT id, code, name, unit, type, sale_price, purchase_price, stock
        FROM products';
if ($where) {
    $sql .= ' WHERE ' . implode(' AND ', $where);
}
$sql .= ' ORDER BY CAST(code AS UNSIGNED) LIMIT 50';

if ($types !== '') {
    $stmt = $mysqli->prepare($sql);
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $res = $stmt->get_result();
    $stmt->close();
} else {
    $res = $mysqli->query($sql);
}

$products = [];
if ($res) {
    while ($row = $res->fetch_assoc()) {
        $products[] = $row;
    }
}

respond_json(true, '', $products);
