<?php
require_once __DIR__ . '/db.php';

/**
 * تولید خودکار کد بعدی کالا (بزرگ‌ترین کد موجود + ۱)
 */
function nextProductCode($mysqli)
{
    $result = $mysqli->query('SELECT code FROM products ORDER BY CAST(code AS UNSIGNED) DESC LIMIT 1');
    if ($result && $row = $result->fetch_assoc()) {
        return (string) ((int) $row['code'] + 1);
    }
    return '1';
}

/**
 * دریافت درخت دسته‌بندی‌ها
 * @return array [$tree, $children]
 */
function getCategoryTree($mysqli): array
{
    $items = [];
    $result = $mysqli->query('SELECT * FROM product_categories ORDER BY parent_id IS NULL DESC, parent_id, CAST(code AS UNSIGNED)');
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $items[] = $row;
        }
        $result->free();
    }

    $tree = [];
    $children = [];
    foreach ($items as $item) {
        if ($item['parent_id'] === null) {
            $tree[$item['id']] = $item;
        } else {
            $children[$item['parent_id']][] = $item;
        }
    }

    return [$tree, $children];
}

/**
 * ساخت آپشن‌های دسته‌بندی (درختی) برای select مودال کالا
 */
function buildCategoryOptionsHtml($mysqli, $selectedId = '')
{
    list($tree, $children) = getCategoryTree($mysqli);

    $html = '<option value="">بدون دسته‌بندی</option>';
    foreach ($tree as $node) {
        $html .= buildCategoryOption($node, $children, 0, $selectedId);
    }
    return $html;
}

/**
 * ساخت آپشن‌های فیلتر دسته‌بندی (همه + درخت دسته‌بندی‌ها)
 */
function buildCategoryFilterOptionsHtml($mysqli, $selectedId = '', $firstLabel = 'همه')
{
    list($tree, $children) = getCategoryTree($mysqli);

    $html = '<option value="">' . htmlspecialchars($firstLabel, ENT_QUOTES, 'UTF-8') . '</option>';
    foreach ($tree as $node) {
        $html .= buildCategoryOption($node, $children, 0, $selectedId);
    }
    return $html;
}

function buildCategoryOption(array $node, array $children, int $level, $selectedId): string
{
    $prefix = str_repeat('— ', $level);
    $sel = ((string) $node['id'] === (string) $selectedId) ? ' selected' : '';
    $html = '<option value="' . $node['id'] . '"' . $sel . '>' . $prefix . htmlspecialchars($node['name'], ENT_QUOTES, 'UTF-8') . '</option>';
    if (!empty($children[$node['id']])) {
        foreach ($children[$node['id']] as $child) {
            $html .= buildCategoryOption($child, $children, $level + 1, $selectedId);
        }
    }
    return $html;
}

/**
 * محاسبه تعداد کل کالاها و ارزش کل موجودی (فقط کالاهای نوع «محصول»)
 * ارزش هر کالا = موجودی × قیمت خرید
 */
function getInventorySummary($mysqli): array
{
    $total = 0;
    $value = 0.0;

    $countResult = $mysqli->query('SELECT COUNT(*) AS c FROM products');
    if ($countResult && $row = $countResult->fetch_assoc()) {
        $total = (int) $row['c'];
    }
    if ($countResult) {
        $countResult->free();
    }

    $valueResult = $mysqli->query("SELECT COALESCE(SUM(stock * purchase_price), 0) AS v FROM products WHERE type = 'product'");
    if ($valueResult && $row = $valueResult->fetch_assoc()) {
        $value = (float) $row['v'];
    }
    if ($valueResult) {
        $valueResult->free();
    }

    return ['total' => $total, 'value' => $value];
}

/**
 * ساخت ردیف جدول کالا (با data attributes برای ویرایش/حذف)
 */
function buildProductRow(array $p): string
{
    $esc = function ($v) {
        return htmlspecialchars((string) ($v ?? ''), ENT_QUOTES, 'UTF-8');
    };
    $typeLabel = ($p['type'] === 'service') ? 'خدمت' : 'محصول';
    $saleDisplay = number_format((float) ($p['sale_price'] ?? 0));

    return '<tr class="product-row" data-id="' . $esc($p['id']) . '"'
        . ' data-code="' . $esc($p['code']) . '"'
        . ' data-name="' . $esc($p['name']) . '"'
        . ' data-category-id="' . $esc($p['category_id']) . '"'
        . ' data-type="' . $esc($p['type']) . '"'
        . ' data-unit="' . $esc($p['unit']) . '"'
        . ' data-purchase-price="' . $esc($p['purchase_price']) . '"'
        . ' data-sale-price="' . $esc($p['sale_price']) . '"'
        . ' data-stock="' . $esc($p['stock']) . '"'
        . ' data-min-stock="' . $esc($p['min_stock']) . '"'
        . ' data-description="' . $esc($p['description']) . '">'
        . '<td>' . $esc($p['code']) . '</td>'
        . '<td>' . $esc($p['name']) . '</td>'
        . '<td>' . $esc($typeLabel) . '</td>'
        . '<td>' . $esc($p['unit']) . '</td>'
        . '<td>' . $esc($saleDisplay) . '</td>'
        . '<td>' . $esc($p['stock']) . '</td>'
        . '<td><button class="button-secondary small edit-product" type="button">ویرایش</button> <button class="button-secondary small delete-product" type="button">حذف</button></td>'
        . '</tr>';
}
