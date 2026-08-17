<?php
require_once __DIR__ . '/../assets/php/product_common.php';

$products = [];
$result = $mysqli->query('SELECT * FROM products ORDER BY CAST(code AS UNSIGNED)');
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $products[] = $row;
    }
    $result->free();
}

$summary = getInventorySummary($mysqli);
$categoryOptions = buildCategoryOptionsHtml($mysqli);
?>
<input type="hidden" id="productCsrfToken" value="<?php echo csrf_token(); ?>">
<div class="card">
    <div class="page-actions">
        <a href="#" id="new-products" class="button">+ کالای جدید</a>
        <button type="button" class="button button-success" id="openPriceIncreaseModal">افزایش قیمت</button>
        <a href="assets/php/export_products.php" class="button-secondary">خروجی اکسل</a>
        <button type="button" class="button-secondary" id="openProductImportModal">ورود از اکسل</button>
        <div class="filter-panel">
            <span>تعداد:</span>
            <span id="productCount" class="value-badge"><?php echo $summary['total']; ?></span>
            <select id="productCategoryFilter">
                <?php echo buildCategoryFilterOptionsHtml($mysqli); ?>
            </select>
            <span>ارزش موجودی:</span>
            <span class="value-badge"><span id="inventoryValue"><?php echo number_format($summary['value'], 0); ?></span> ریال</span>
            <input type="search" id="productSearch" placeholder="جستجو...">
        </div>
    </div>
    <div class="table-wrapper">
        <table id="products-list" class="action-table">
            <thead>
                <tr>
                    <th>کد</th>
                    <th>نام</th>
                    <th>نوع</th>
                    <th>واحد</th>
                    <th>قیمت فروش</th>
                    <th>موجودی</th>
                    <th>عملیات</th>
                </tr>
            </thead>
            <tbody>
                <?php if (count($products) === 0): ?>
                    <tr class="empty-state-row">
                        <td colspan="7" class="empty-state">کالایی یافت نشد</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($products as $p): ?>
                        <?php echo buildProductRow($p); ?>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<div class="modal" id="productModal">
    <div class="modal-backdrop"></div>
    <div class="modal-content">
        <div class="modal-header">
            <h2 id="productModalTitle">کالای جدید</h2>
            <button class="modal-close" id="closeProductModal" type="button">×</button>
        </div>
        <form id="productForm" method="post" action="assets/php/product_action.php">
            <input type="hidden" name="product_id" id="productId" value="">
            <input type="hidden" name="code" id="productCodeHidden" value="">
            <div class="form-grid">
                <div class="form-row"><label>کد کالا</label><input type="text" id="productCode" readonly disabled placeholder="به صورت خودکار تولید می‌شود"></div>
                <div class="form-row"><label>نام کالا <span class="required">*</span></label><input type="text" name="name" id="productName" required></div>
                <div class="form-row"><label>دسته‌بندی</label>
                    <select name="category_id" id="productCategory">
                        <?php echo $categoryOptions; ?>
                    </select>
                </div>
                <div class="form-row"><label>نوع</label>
                    <select name="type" id="productType">
                        <option value="product">محصول</option>
                        <option value="service">خدمت</option>
                    </select>
                </div>
                <div class="form-row"><label>واحد</label>
                    <select name="unit" id="productUnit">
                        <option value="عدد">عدد</option>
                        <option value="بسته">بسته</option>
                        <option value="قرص">قرص</option>
                    </select>
                </div>
                <div class="form-row"><label>قیمت خرید</label><input type="number" name="purchase_price" id="productPurchasePrice" min="0" step="1"></div>
                <div class="form-row"><label>قیمت فروش</label><input type="number" name="sale_price" id="productSalePrice" min="0" step="1"></div>
                <div class="form-row"><label>موجودی</label><input type="number" name="stock" id="productStock" min="0" step="1"></div>
                <div class="form-row"><label>حداقل موجودی</label><input type="number" name="min_stock" id="productMinStock" min="0" step="1"></div>
                <div class="form-row"><label>توضیحات</label><textarea name="description" id="productDescription" rows="2"></textarea></div>
            </div>
            <div class="form-actions modal-actions">
                <button type="submit" class="button" id="saveProductButton">ذخیره</button>
                <button type="button" class="button-secondary" id="cancelProductModal">لغو</button>
            </div>
        </form>
    </div>
</div>

<div class="modal" id="priceIncreaseModal">
    <div class="modal-backdrop"></div>
    <div class="modal-content">
        <div class="modal-header">
            <h2>افزایش قیمت کالاها</h2>
            <button class="modal-close" id="closePriceIncreaseModal" type="button">×</button>
        </div>
        <form id="priceIncreaseForm" method="post" action="assets/php/price_increase.php">
            <div class="form-grid">
                <div class="form-row" style="grid-column: span 2;">
                    <label>محدوده</label>
                    <select name="scope" id="priceScope">
                        <?php echo buildCategoryFilterOptionsHtml($mysqli, '', 'همه کالاها'); ?>
                    </select>
                </div>
                <div class="form-row" style="grid-column: span 2;">
                    <label>کدام قیمت</label>
                    <div class="radio-group">
                        <label class="radio-option"><input type="radio" name="price_type" value="sale" checked> قیمت فروش</label>
                        <label class="radio-option"><input type="radio" name="price_type" value="purchase"> قیمت خرید</label>
                        <label class="radio-option"><input type="radio" name="price_type" value="both"> هر دو</label>
                    </div>
                </div>
                <div class="form-row" style="grid-column: span 2;">
                    <label>نوع افزایش</label>
                    <div class="radio-group">
                        <label class="radio-option"><input type="radio" name="increase_type" value="percent" checked> درصدی</label>
                        <label class="radio-option"><input type="radio" name="increase_type" value="amount"> مبلغ</label>
                    </div>
                </div>
                <div class="form-row" style="grid-column: span 2;">
                    <label>میزان افزایش</label>
                    <input type="number" name="increase_value" id="priceIncreaseValue" min="0" step="any" inputmode="numeric" placeholder="مثال: ۱۰ یعنی ۱۰٪ افزایش">
                </div>
            </div>
            <div class="form-actions modal-actions">
                <button type="submit" class="button button-success" id="applyPriceIncrease">اعمال</button>
                <button type="button" class="button-secondary" id="cancelPriceIncreaseModal">لغو</button>
            </div>
        </form>
        <div class="price-result" id="priceIncreaseResult" style="display:none;"></div>
    </div>
</div>

<div class="modal" id="productImportModal">
    <div class="modal-backdrop"></div>
    <div class="modal-content modal-content-wide">
        <div class="modal-header">
            <h2>ورود کالاها و خدمات از اکسل</h2>
            <button class="modal-close" id="closeProductImportModal" type="button">×</button>
        </div>
        <div class="form-grid">
            <div class="form-row" style="grid-column: span 2;">
                <label>فایل اکسل (.xlsx یا .xls)</label>
                <input type="file" id="productImportFile" accept=".xlsx,.xls">
                <span class="import-help">ردیف اول باید عنوان ستون‌ها باشد (کد کالا، نام کالا، دسته‌بندی، نوع، واحد، قیمت خرید، قیمت فروش، موجودی، حداقل موجودی، توضیحات). ترتیب ستون‌ها آزاد است.</span>
            </div>
        </div>
        <div class="import-actions">
            <button type="button" class="button" id="productImportPreview" disabled>خواندن و پیش‌نمایش</button>
            <button type="button" class="button button-success" id="productImportSubmit" disabled>ثبت نهایی</button>
            <span class="import-spinner" id="productImportSpinner" style="display:none;">در حال پردازش...</span>
        </div>
        <div class="import-result" id="productImportResult" style="display:none;"></div>
        <div class="import-summary" id="productImportSummary" style="display:none;"></div>
        <div class="import-errors-wrap" id="productImportErrorsWrap" style="display:none;">
            <div class="import-sub-title">لیست خطاها:</div>
            <ul class="import-errors" id="productImportErrors"></ul>
        </div>
        <div class="import-preview-wrap" id="productImportPreviewWrap" style="display:none;">
            <div class="import-sub-title">پیش‌نمایش رکوردها:</div>
            <div class="import-preview-scroll">
                <table class="action-table import-preview-table" id="productImportPreviewTable">
                    <thead>
                        <tr>
                            <th>ردیف</th><th>کد</th><th>نام</th><th>دسته‌بندی</th><th>نوع</th><th>واحد</th>
                            <th>قیمت خرید</th><th>قیمت فروش</th><th>موجودی</th><th>حداقل موجودی</th><th>وضعیت</th>
                        </tr>
                    </thead>
                    <tbody></tbody>
                </table>
            </div>
        </div>
        <div class="form-actions modal-actions">
            <button type="button" class="button-secondary" id="cancelProductImportModal">بستن</button>
        </div>
    </div>
</div>
