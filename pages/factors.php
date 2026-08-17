<?php require_once __DIR__ . '/../assets/php/boot.php'; ?>
<input type="hidden" id="invoiceMode" value="sales">
<input type="hidden" id="invoiceCsrfToken" value="<?php echo csrf_token(); ?>">

<div class="card" id="invoiceListView">
    <div class="page-actions">
        <a href="#" class="button invoice-new" data-type="sales_invoice">+ فاکتور فروش جدید</a>
        <a href="#" class="button-secondary invoice-new" data-type="sales_proforma">+ پیش فاکتور فروش</a>
        <div class="filter-panel">
            <input type="search" id="invoiceSearch" placeholder="جستجو (شماره/مشتری)...">
            <select id="invoiceStatusFilter">
                <option value="">همه وضعیت‌ها</option>
                <option value="paid">پرداخت شده</option>
                <option value="unpaid">پرداخت نشده</option>
                <option value="partial">پرداخت جزئی</option>
            </select>
            <button type="button" class="button-secondary" id="invoiceRefresh">تازه‌سازی</button>
        </div>
    </div>
    <div class="table-wrapper">
        <table class="action-table" id="invoicesTable">
            <thead>
                <tr>
                    <th>شماره</th><th>مشتری</th><th>تاریخ (شمسی)</th><th>نوع</th><th>وضعیت پرداخت</th><th>مبلغ کل</th><th>عملیات</th>
                </tr>
            </thead>
            <tbody>
                <tr><td colspan="7" class="empty-state">در حال بارگذاری...</td></tr>
            </tbody>
        </table>
    </div>
</div>

<div class="card" id="invoiceFormView" style="display:none;"></div>

<!-- مودال انتخاب کالا -->
<div class="modal" id="invoiceProductModal">
    <div class="modal-backdrop" data-close="invoiceProductModal"></div>
    <div class="modal-content">
        <div class="modal-header">
            <h2>انتخاب کالا</h2>
            <button class="modal-close" data-close="invoiceProductModal" type="button">×</button>
        </div>
        <div class="form-grid">
            <div class="form-row" style="grid-column: span 2;">
                <input type="search" id="invoiceProductSearch" placeholder="جستجوی کالا...">
            </div>
        </div>
        <div class="table-wrapper">
            <table class="action-table" id="invoiceProductTable">
                <thead>
                    <tr><th>کد</th><th>نام</th><th>واحد</th><th>قیمت</th><th>موجودی</th><th></th></tr>
                </thead>
                <tbody><tr><td colspan="6" class="empty-state">جستجو کنید...</td></tr></tbody>
            </table>
        </div>
    </div>
</div>

<!-- مودال انتخاب مشتری -->
<div class="modal" id="invoicePartyModal">
    <div class="modal-backdrop" data-close="invoicePartyModal"></div>
    <div class="modal-content">
        <div class="modal-header">
            <h2>انتخاب مشتری</h2>
            <button class="modal-close" data-close="invoicePartyModal" type="button">×</button>
        </div>
        <div class="form-grid">
            <div class="form-row" style="grid-column: span 2;">
                <input type="search" id="invoicePartySearch" placeholder="جستجوی مشتری...">
            </div>
        </div>
        <div class="table-wrapper">
            <table class="action-table" id="invoicePartyTable">
                <thead>
                    <tr><th>نام</th><th>تلفن</th><th>کد ملی</th><th></th></tr>
                </thead>
                <tbody><tr><td colspan="4" class="empty-state">جستجو کنید...</td></tr></tbody>
            </table>
        </div>
    </div>
</div>
