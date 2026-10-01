<?php
/**
 * invoice_products.php — جستجوی کالا برای مودال انتخاب (GET بدون CSRF)
 * بازگشت: محصولات شامل قیمت فروش/خرید و موجودی
 */

require_once __DIR__ . '/boot.php';
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/product_common.php';

$search = trim((string) ($_GET['search'] ?? ''));
$category = trim((string) ($_GET['category'] ?? ''));

$where = [];
$types = '';
$params = [];

if ($search !== '') {
    $like = '%' . $search . '%';
    $where[] = '(code LIKE ? OR name LIKE ? OR barcode LIKE ? OR EXISTS (SELECT 1 FROM product_units pu2 WHERE pu2.product_id = products.id AND pu2.barcode = ?))';
    $types .= 'ssss';
    $params[] = $like;
    $params[] = $like;
    $params[] = $like;
    $params[] = $search;
}
if ($category !== '' && ctype_digit($category)) {
    $where[] = 'category_id = ?';
    $types .= 'i';
    $params[] = (int) $category;
}
/* فیلتر اختیاری نوع (برای نمونه فقط «کالا/محصول» در انتخاب کالای معیوب) — بدون پارامتر، رفتار قبلی کاملاً حفظ می‌شود */
$typeFilter = trim((string) ($_GET['type'] ?? ''));
if ($typeFilter !== '' && in_array($typeFilter, ['product', 'service'], true)) {
    $where[] = 'type = ?';
    $types .= 's';
    $params[] = $typeFilter;
}

$sql = 'SELECT id, code, barcode, name, unit, type, sale_price, purchase_price, stock
        FROM products';
if ($where) {
    $sql .= ' WHERE ' . implode(' AND ', $where);
}
if ($search !== '') {
    /* مطابقت دقیق بارکد در صدر نتایج، سپس کد دقیق و سپس جستجوی معمولی */
    $sql .= ' ORDER BY CASE WHEN products.barcode = ? THEN 0 WHEN products.code = ? THEN 1 ELSE 2 END, CAST(code AS UNSIGNED) LIMIT 50';
    $types .= 'ss';
    $params[] = $search;
    $params[] = $search;
} else {
    $sql .= ' ORDER BY CAST(code AS UNSIGNED) LIMIT 50';
}

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

/* بارگذاری واحدهای اندازه‌گیری برای هر کالای بازگشتی */
if (count($products) > 0) {
    $unitsMap = getUnitsMapForProducts($mysqli, array_column($products, 'id'));
    foreach ($products as &$p) {
        $p['units'] = $unitsMap[(int) $p['id']] ?? [];
    }
    unset($p);
}

respond_json(true, '', $products);
