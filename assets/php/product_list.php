<?php
require_once __DIR__ . '/product_common.php';
header('Content-Type: application/json; charset=UTF-8');

$search = trim($_GET['search'] ?? '');
$category = trim($_GET['category'] ?? '');
if ($category !== '' && !ctype_digit($category)) {
    $category = '';
}

$conditions = [];
$types = '';
$likeVal = '';
$catVal = '';

if ($search !== '') {
    $likeVal = '%' . $search . '%';
    $conditions[] = '(code LIKE ? OR name LIKE ?)';
    $types .= 'ss';
}
if ($category !== '') {
    $catVal = (int) $category;
    $conditions[] = 'category_id = ?';
    $types .= 'i';
}

$where = $conditions ? 'WHERE ' . implode(' AND ', $conditions) : '';
$sql = "SELECT * FROM products $where ORDER BY CAST(code AS UNSIGNED)";

$tableBody = '';
if ($conditions) {
    $stmt = $mysqli->prepare($sql);
    if ($types === 'ss') {
        $stmt->bind_param('ss', $likeVal, $likeVal);
    } elseif ($types === 'i') {
        $stmt->bind_param('i', $catVal);
    } else {
        $stmt->bind_param('ssi', $likeVal, $likeVal, $catVal);
    }
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result && $result->num_rows === 0) {
        $tableBody = '<tr class="empty-state-row"><td colspan="7" class="empty-state">کالایی یافت نشد</td></tr>';
    } elseif ($result) {
        while ($row = $result->fetch_assoc()) {
            $tableBody .= buildProductRow($row);
        }
    }
    $stmt->close();
} else {
    $result = $mysqli->query($sql);
    if ($result) {
        if ($result->num_rows === 0) {
            $tableBody = '<tr class="empty-state-row"><td colspan="7" class="empty-state">کالایی یافت نشد</td></tr>';
        } else {
            while ($row = $result->fetch_assoc()) {
                $tableBody .= buildProductRow($row);
            }
        }
        $result->free();
    } else {
        $tableBody = '<tr class="empty-state-row"><td colspan="7" class="empty-state">کالایی یافت نشد</td></tr>';
    }
}

$summary = getInventorySummary($mysqli);

echo json_encode([
    'success' => true,
    'tableBody' => $tableBody,
    'totalProducts' => $summary['total'],
    'inventoryValue' => number_format($summary['value'], 0),
]);
