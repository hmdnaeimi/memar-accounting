<?php
require_once __DIR__ . '/product_common.php';
header('Content-Type: application/json; charset=UTF-8');

$action = $_GET['action'] ?? ($_POST['action'] ?? '');

if ($action === 'next_code') {
    echo json_encode(['success' => true, 'code' => nextProductCode($mysqli)]);
    exit;
}

if ($action === 'delete') {
    $product_id = trim($_POST['product_id'] ?? '');
    if ($product_id === '' || !ctype_digit($product_id)) {
        echo json_encode(['success' => false, 'message' => 'شناسه کالا نامعتبر است.']);
        exit;
    }
    $stmt = $mysqli->prepare('DELETE FROM products WHERE id = ?');
    $stmt->bind_param('i', $product_id);
    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'action' => 'deleted', 'product_id' => (int) $product_id]);
    } else {
        echo json_encode(['success' => false, 'message' => 'خطا در حذف کالا.']);
    }
    $stmt->close();
    exit;
}

/* ---------- افزودن / ویرایش ---------- */
$product_id = trim($_POST['product_id'] ?? '');
$code = trim($_POST['code'] ?? '');
$name = trim($_POST['name'] ?? '');
$category_id = trim($_POST['category_id'] ?? '');
$type = trim($_POST['type'] ?? 'product');
$unit = trim($_POST['unit'] ?? 'عدد');
$purchase_price = trim($_POST['purchase_price'] ?? '');
$sale_price = trim($_POST['sale_price'] ?? '');
$stock = trim($_POST['stock'] ?? '');
$min_stock = trim($_POST['min_stock'] ?? '');
$description = trim($_POST['description'] ?? '');

if ($name === '') {
    echo json_encode(['success' => false, 'message' => 'نام کالا اجباری است.']);
    exit;
}

if ($code === '') {
    $code = nextProductCode($mysqli);
}

if ($category_id === '') {
    $category_id = null;
}
if ($type !== 'product' && $type !== 'service') {
    $type = 'product';
}
$allowedUnits = ['عدد', 'بسته', 'قرص'];
if (!in_array($unit, $allowedUnits, true)) {
    $unit = 'عدد';
}
if ($purchase_price === '') {
    $purchase_price = 0;
}
if ($sale_price === '') {
    $sale_price = 0;
}
if ($stock === '') {
    $stock = 0;
}
if ($min_stock === '') {
    $min_stock = 0;
}

if ($product_id !== '') {
    /* ---- ویرایش ---- */
    if (!ctype_digit($product_id)) {
        echo json_encode(['success' => false, 'message' => 'شناسه کالا نامعتبر است.']);
        exit;
    }
    if ($category_id === null) {
        $stmt = $mysqli->prepare('UPDATE products SET code = ?, name = ?, category_id = NULL, type = ?, unit = ?, purchase_price = ?, sale_price = ?, stock = ?, min_stock = ?, description = ? WHERE id = ?');
        $stmt->bind_param('ssssddddsi', $code, $name, $type, $unit, $purchase_price, $sale_price, $stock, $min_stock, $description, $product_id);
    } else {
        $stmt = $mysqli->prepare('UPDATE products SET code = ?, name = ?, category_id = ?, type = ?, unit = ?, purchase_price = ?, sale_price = ?, stock = ?, min_stock = ?, description = ? WHERE id = ?');
        $stmt->bind_param('ssissddddsi', $code, $name, $category_id, $type, $unit, $purchase_price, $sale_price, $stock, $min_stock, $description, $product_id);
    }
    if ($stmt->execute()) {
        echo json_encode([
            'success' => true,
            'action' => 'edited',
            'product' => [
                'id' => (int) $product_id,
                'code' => $code,
                'name' => $name,
                'category_id' => $category_id,
                'type' => $type,
                'unit' => $unit,
                'purchase_price' => (string) $purchase_price,
                'sale_price' => (string) $sale_price,
                'stock' => (string) $stock,
                'min_stock' => (string) $min_stock,
                'description' => $description,
            ],
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => 'خطا در به‌روزرسانی اطلاعات کالا.']);
    }
    $stmt->close();
    exit;
}

/* ---- افزودن ---- */
if ($category_id === null) {
    $stmt = $mysqli->prepare('INSERT INTO products (code, name, type, unit, purchase_price, sale_price, stock, min_stock, description) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)');
    $stmt->bind_param('ssssdddds', $code, $name, $type, $unit, $purchase_price, $sale_price, $stock, $min_stock, $description);
} else {
    $stmt = $mysqli->prepare('INSERT INTO products (code, name, category_id, type, unit, purchase_price, sale_price, stock, min_stock, description) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)');
    $stmt->bind_param('ssissdddds', $code, $name, $category_id, $type, $unit, $purchase_price, $sale_price, $stock, $min_stock, $description);
}

if ($stmt->execute()) {
    $id = $stmt->insert_id;
    echo json_encode([
        'success' => true,
        'action' => 'added',
        'product' => [
            'id' => $id,
            'code' => $code,
            'name' => $name,
            'category_id' => $category_id,
            'type' => $type,
            'unit' => $unit,
            'purchase_price' => (string) $purchase_price,
            'sale_price' => (string) $sale_price,
            'stock' => (string) $stock,
            'min_stock' => (string) $min_stock,
            'description' => $description,
        ],
    ]);
} else {
    echo json_encode(['success' => false, 'message' => 'خطا در ذخیره‌سازی کالا.']);
}
$stmt->close();
