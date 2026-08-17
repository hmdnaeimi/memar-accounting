<?php
require_once __DIR__ . '/../assets/php/product_common.php';

$rows = [];
$result = $mysqli->query("
    SELECT p.id, p.code, p.name, p.category_id, p.type, p.unit, p.sale_price, p.stock, pc.name AS category_name
    FROM products p
    LEFT JOIN product_categories pc ON pc.id = p.category_id
    ORDER BY CAST(p.code AS UNSIGNED)
");
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $rows[] = $row;
    }
    $result->free();
}
?>
<div class="card">
    <div class="page-actions">
        <a href="assets/php/report_inventory_export.php" class="button-secondary" id="exportInventoryBtn">خروجی اکسل</a>
        <div class="filter-panel">
            <input type="search" id="inventorySearch" placeholder="جستجو بر اساس نام کالا، دسته‌بندی یا نوع...">
            <select id="inventoryCategoryFilter">
                <?php echo buildCategoryFilterOptionsHtml($mysqli); ?>
            </select>
            <select id="inventoryTypeFilter">
                <option value="">همه انواع</option>
                <option value="product">محصول</option>
                <option value="service">خدمت</option>
            </select>
        </div>
    </div>
    <div class="table-wrapper">
        <table class="action-table" id="inventoryReportTable">
            <thead>
                <tr>
                    <th>نام کالا</th>
                    <th>نام دسته‌بندی</th>
                    <th>نوع</th>
                    <th>واحد</th>
                    <th>قیمت فروش</th>
                    <th>موجودی</th>
                </tr>
            </thead>
            <tbody>
                <?php if (count($rows) === 0): ?>
                    <tr class="empty-state-row"><td colspan="6" class="empty-state">کالایی یافت نشد</td></tr>
                <?php else: ?>
                    <?php foreach ($rows as $r): ?>
                        <?php $cat = $r['category_name'] !== null ? $r['category_name'] : '-'; ?>
                        <tr class="inventory-report-row"
                            data-name="<?php echo htmlspecialchars($r['name'], ENT_QUOTES, 'UTF-8'); ?>"
                            data-category="<?php echo htmlspecialchars($cat, ENT_QUOTES, 'UTF-8'); ?>"
                            data-category-id="<?php echo $r['category_id'] !== null ? $r['category_id'] : ''; ?>"
                            data-type="<?php echo $r['type']; ?>">
                            <td><?php echo htmlspecialchars($r['name'], ENT_QUOTES, 'UTF-8'); ?></td>
                            <td><?php echo htmlspecialchars($cat, ENT_QUOTES, 'UTF-8'); ?></td>
                            <td><?php echo $r['type'] === 'service' ? 'خدمت' : 'محصول'; ?></td>
                            <td><?php echo htmlspecialchars($r['unit'], ENT_QUOTES, 'UTF-8'); ?></td>
                            <td><?php echo number_format((float) $r['sale_price']); ?></td>
                            <td><?php echo number_format((float) $r['stock']); ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>