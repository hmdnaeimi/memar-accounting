<?php
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/report_excel_lib.php';

$search = reportParam('search');
$category = reportCategoryParam();
$type = reportTypeParam();

$conditions = [];
$params = [];
$types = '';

if ($search !== '') {
    $conditions[] = '(p.name LIKE ? OR pc.name LIKE ?)';
    $params[] = '%' . $search . '%';
    $params[] = '%' . $search . '%';
    $types .= 'ss';

    $searchType = null;
    $low = mb_strtolower($search);
    if (in_array($low, ['محصول', 'product'], true)) {
        $searchType = 'product';
    } elseif (in_array($low, ['خدمت', 'service'], true)) {
        $searchType = 'service';
    }
    if ($searchType !== null) {
        $conditions[] = 'p.type = ?';
        $params[] = $searchType;
        $types .= 's';
    }
}
if ($category !== '') {
    $conditions[] = 'p.category_id = ?';
    $params[] = (int) $category;
    $types .= 'i';
}
if ($type !== '') {
    $conditions[] = 'p.type = ?';
    $params[] = $type;
    $types .= 's';
}

$sql = "
    SELECT p.code, p.name, pc.name AS category_name, p.type, p.unit, p.sale_price, p.stock
    FROM products p
    LEFT JOIN product_categories pc ON pc.id = p.category_id
";
if ($conditions) {
    $sql .= ' WHERE ' . implode(' AND ', $conditions);
}
$sql .= ' ORDER BY CAST(p.code AS UNSIGNED)';

$rows = [];
if ($conditions) {
    $stmt = $mysqli->prepare($sql);
    if ($stmt) {
        $bindParams = [$types];
        foreach ($params as $k => $v) {
            $bindParams[] = &$params[$k];
        }
        call_user_func_array([$stmt, 'bind_param'], $bindParams);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result) {
            while ($row = $result->fetch_assoc()) {
                $rows[] = $row;
            }
        }
        $stmt->close();
    }
} else {
    $result = $mysqli->query($sql);
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $rows[] = $row;
        }
        $result->free();
    }
}

$columns = [
    'name'          => 'نام کالا',
    'category_name' => 'نام دسته‌بندی',
    'type'          => 'نوع',
    'unit'          => 'واحد',
    'sale_price'    => 'قیمت فروش',
    'stock'         => 'موجودی',
];

$exportRows = [];
foreach ($rows as $r) {
    $exportRows[] = [
        'name'          => $r['name'],
        'category_name' => $r['category_name'] !== null ? $r['category_name'] : '-',
        'type'          => $r['type'],
        'unit'          => $r['unit'],
        'sale_price'    => number_format((float) $r['sale_price']),
        'stock'         => number_format((float) $r['stock']),
    ];
}

outputReportExcel('گزارش موجودی کالاها', $columns, $exportRows, 'Report_Inventory_' . date('Y-m-d_His') . '.xls');