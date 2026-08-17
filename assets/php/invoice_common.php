<?php

/**
 * invoice_common.php — منطق مشترک ماژول فاکتور فروش/خرید
 *
 * تمام محاسبات مالی با BCMath (Decimal-safe) انجام می‌شود.
 * تمام تغییرات موجودی فقط از طریق applyStockChange و
 * تمام ثبت گردش فقط از طریق createStockMovement انجام می‌شود.
 *
 * بسته به ترتیب بارگذاری endpoint:
 *   boot.php → db.php → invoice_common.php
 * متغیر سراسری $mysqli فرض می‌شود.
 */

require_once __DIR__ . '/jdf.php';

/* ============================================================
 * ۱) ابزار صحیح محاسبات پولی (BCMath)
 * ========================================================== */

/**
 * گرد کردن مقدار (رشته/عدد) به $scale رقم اعشار (half-up).
 */
function money_round($value, int $scale = 2): string
{
    $value = (string) $value;
    if ($value === '' || $value === null) {
        $value = '0';
    }
    $neg = '';
    if (str_starts_with($value, '-')) {
        $neg = '-';
        $value = substr($value, 1);
    }
    $pow = bcpow('10', (string) $scale);
    $scaled = bcmul($value, $pow, 10);
    $roundedInt = bcadd($scaled, '0.5', 0); // floor(x+0.5) → round half up
    return $neg . bcdiv($roundedInt, $pow, $scale);
}

function money_add($a, $b, int $scale = 2): string
{
    return bcadd((string) $a, (string) $b, $scale);
}
function money_sub($a, $b, int $scale = 2): string
{
    return bcsub((string) $a, (string) $b, $scale);
}
function money_mul($a, $b, int $scale = 2): string
{
    return bcmul((string) $a, (string) $b, $scale);
}
function money_div($a, $b, int $scale = 2): string
{
    if (bccomp((string) $b, '0', 6) === 0) {
        return '0';
    }
    return bcdiv((string) $a, (string) $b, $scale);
}
function money_cmp($a, $b, int $scale = 2): int
{
    return bccomp(money_round($a, $scale), money_round($b, $scale), $scale);
}
function money_abs($a): string
{
    $a = (string) $a;
    return str_starts_with($a, '-') ? substr($a, 1) : $a;
}

/* ============================================================
 * ۲) ثابت‌ها و نگاشت‌های نوع
 * ========================================================== */

define('INVOICE_TYPES', [
    'sales_invoice',
    'sales_proforma',
    'purchase_invoice',
    'purchase_proforma',
]);
define('PAYMENT_TYPES', ['cash', 'pos', 'bank_transfer']);
define('PAYMENT_STATUSES', ['paid', 'unpaid', 'partial']);
define('FINAL_INVOICE_TYPES', ['sales_invoice', 'purchase_invoice']);

function isSales(string $type): bool
{
    return str_starts_with($type, 'sales');
}

function isPurchase(string $type): bool
{
    return str_starts_with($type, 'purchase');
}

function isFinal(string $type): bool
{
    return in_array($type, FINAL_INVOICE_TYPES, true);
}

function partyFkField(string $type): string
{
    return isSales($type) ? 'customer_id' : 'supplier_id';
}

function invoiceNumberPrefix(string $type): string
{
    $map = [
        'sales_invoice'      => 'S-',
        'sales_proforma'     => 'SP-',
        'purchase_invoice'   => 'P-',
        'purchase_proforma'  => 'PP-',
    ];
    return $map[$type] ?? 'X-';
}

/**
 * تعیین نوع و جهت حرکت موجودی
 *
 * @param string $type  نوع فاکتور
 * @param string $op    create | delete | edit
 * @param string $delta تغییر تعداد (new-old) برای edit
 * @return array [type, direction]
 */
function opToMovement(string $type, string $op, string $delta = '0'): array
{
    $sales = isSales($type);
    switch ($op) {
        case 'create':
            return $sales ? ['sale', 'out'] : ['purchase', 'in'];
        case 'delete':
            return $sales ? ['sale_cancel', 'in'] : ['purchase_cancel', 'out'];
        case 'edit':
            if ($sales) {
                return ['sale_edit', money_cmp($delta, '0') >= 0 ? 'out' : 'in'];
            }
            return ['purchase_edit', money_cmp($delta, '0') >= 0 ? 'in' : 'out'];
    }
    return ['adjustment', 'in'];
}

/* ============================================================
 * ۳) محاسبات مالی
 * ========================================================== */

function calculateLineTotal($quantity, $unitPrice, $lineDiscount): string
{
    return money_round(money_sub(money_mul($quantity, $unitPrice), $lineDiscount));
}

function calculateTax($taxable, $taxRate, bool $taxEnabled): string
{
    if (!$taxEnabled || money_cmp($taxRate, '0') <= 0) {
        return money_round('0');
    }
    return money_round(money_div(money_mul($taxable, $taxRate), '100'));
}

/**
 * محاسبه همه مبالغ فاکتور در Backend
 */
function calculateInvoiceTotals(array $items, $invoiceDiscount, $taxRate, bool $taxEnabled): array
{
    $subtotal = '0';
    foreach ($items as $item) {
        $subtotal = money_add($subtotal, $item['line_total']);
    }
    $taxable = money_sub($subtotal, $invoiceDiscount);
    $taxAmount = calculateTax($taxable, $taxRate, $taxEnabled);
    $payable = money_add($taxable, $taxAmount);

    return [
        'subtotal'       => money_round($subtotal),
        'taxable'        => money_round($taxable),
        'tax_amount'     => money_round($taxAmount),
        'payable_amount' => money_round($payable),
    ];
}

/* ============================================================
 * ۴) شماره فاکتور (اتمیک) — داخل Transaction
 * ========================================================== */

function generateInvoiceNumber(mysqli $mysqli, string $type): string
{
    $seqKey = $type;
    $stmt = $mysqli->prepare('UPDATE invoice_sequences SET current_value = current_value + 1 WHERE seq_key = ?');
    $stmt->bind_param('s', $seqKey);
    $stmt->execute();
    $stmt->close();

    $stmt = $mysqli->prepare('SELECT current_value FROM invoice_sequences WHERE seq_key = ?');
    $stmt->bind_param('s', $seqKey);
    $stmt->execute();
    $res = $stmt->get_result();
    $stmt->close();
    $val = 0;
    if ($res && $row = $res->fetch_assoc()) {
        $val = (int) $row['current_value'];
    }
    return invoiceNumberPrefix($type) . sprintf('%06d', $val);
}

/* ============================================================
 * ۵) Lock محصولات (deterministic) — داخل Transaction
 * ========================================================== */

/**
 * Dedup + sort ASC سپس FOR UPDATE روی products.
 *
 * @return array<int,string> map pid => stock
 * @throws Exception اگر محصولی یافت نشود
 */
function lockProductsById(mysqli $mysqli, array $ids): array
{
    $ids = array_values(array_unique(array_map('intval', $ids)));
    sort($ids, SORT_NUMERIC);
    $stocks = [];
    foreach ($ids as $id) {
        $stmt = $mysqli->prepare('SELECT stock FROM products WHERE id = ? FOR UPDATE');
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $res = $stmt->get_result();
        $stmt->close();
        if (!$res || !($row = $res->fetch_assoc())) {
            throw new Exception('product_not_found');
        }
        $stocks[$id] = $row['stock'];
    }
    return $stocks;
}

/* ============================================================
 * ۶) ثبت گردش موجودی
 * ========================================================== */

function createStockMovement(
    mysqli $mysqli,
    int $productId,
    ?int $invoiceId,
    string $type,
    string $direction,
    $quantity,
    $stockBefore,
    $stockAfter
): void {
    $quantity = money_round($quantity);
    if (money_cmp($quantity, '0') === 0) {
        return;
    }
    $sb = money_round($stockBefore);
    $sa = money_round($stockAfter);
    $stmt = $mysqli->prepare(
        'INSERT INTO stock_movements
            (product_id, invoice_id, type, direction, quantity, stock_before, stock_after)
         VALUES (?, ?, ?, ?, ?, ?, ?)'
    );
    $stmt->bind_param(
        'iisssdd',
        $productId,
        $invoiceId,
        $type,
        $direction,
        $quantity,
        $sb,
        $sa
    );
    $stmt->execute();
    $stmt->close();
}

/* ============================================================
 * ۷) اعمال تغییر موجودی (stable + check منفی + ثبت movement)
 * ========================================================== */

/**
 * اعمال یک تغییر موجودی برای یک محصول قفل‌شده.
 *
 * @param int    $productId
 * @param string $currentStock موجودی فعلی (مقدار قفل‌شده)
 * @param string $stockChange  تغییر (مثبت/منفی)
 * @param int    $invoiceId
 * @param string $type         نوع فاکتور
 * @param string $op           create|delete|edit
 * @return string موجودی جدید
 * @throws Exception اگر نتیجه منفی شود
 */
function applyStockChange(
    mysqli $mysqli,
    int $productId,
    $currentStock,
    $stockChange,
    int $invoiceId,
    string $type,
    string $op
): string {
    if (money_cmp($stockChange, '0') === 0) {
        return money_round($currentStock);
    }
    $stockBefore = money_round($currentStock);
    $stockAfter = money_round(money_add($stockBefore, $stockChange));

    if (money_cmp($stockAfter, '0') < 0) {
        throw new Exception('negative_stock');
    }

    $stmt = $mysqli->prepare('UPDATE products SET stock = ? WHERE id = ?');
    $stmt->bind_param('di', $stockAfter, $productId);
    $stmt->execute();
    $stmt->close();

    list($movType, $direction) = opToMovement($type, $op, $stockChange);
    createStockMovement(
        $mysqli,
        $productId,
        $invoiceId,
        $movType,
        $direction,
        money_abs($stockChange),
        $stockBefore,
        $stockAfter
    );

    return $stockAfter;
}

/* ============================================================
 * ۸) نرمال‌سازی/اعتبارسنجی اقلام
 * ========================================================== */

function normalizeItems(mysqli $mysqli, $rawItems): array
{
    if (!is_array($rawItems) || count($rawItems) === 0) {
        return ['ok' => false, 'message' => 'حداقل یک ردیف کالا باید ارسال شود.', 'items' => []];
    }

    $merged = [];
    foreach ($rawItems as $raw) {
        if (!is_array($raw)) {
            return ['ok' => false, 'message' => 'ساختار اقلام نامعتبر است.', 'items' => []];
        }
        $pid = trim((string) ($raw['product_id'] ?? ''));
        if ($pid === '' || !ctype_digit($pid)) {
            return ['ok' => false, 'message' => 'شناسه کالا نامعتبر است.', 'items' => []];
        }
        $pid = (int) $pid;

        $qty = trim((string) ($raw['quantity'] ?? ''));
        if ($qty === '' || !is_numeric($qty) || money_cmp($qty, '0') <= 0) {
            return ['ok' => false, 'message' => "تعداد کالای {$pid} نامعتبر است.", 'items' => []];
        }
        $unit = trim((string) ($raw['unit_price'] ?? ''));
        if ($unit === '' || !is_numeric($unit) || money_cmp($unit, '0') < 0) {
            return ['ok' => false, 'message' => "قیمت واحد کالای {$pid} نامعتبر است.", 'items' => []];
        }
        $disc = trim((string) ($raw['discount'] ?? '0'));
        if ($disc === '' || !is_numeric($disc) || money_cmp($disc, '0') < 0) {
            return ['ok' => false, 'message' => "تخفیف کالای {$pid} نامعتبر است.", 'items' => []];
        }

        $qtyR = money_round($qty);
        $unitR = money_round($unit);
        $discR = money_round($disc);

        if (isset($merged[$pid])) {
            $merged[$pid]['quantity'] = money_add($merged[$pid]['quantity'], $qtyR);
            $merged[$pid]['discount'] = money_add($merged[$pid]['discount'], $discR);
        } else {
            $merged[$pid] = [
                'product_id' => $pid,
                'quantity'   => $qtyR,
                'unit_price' => $unitR,
                'discount'   => $discR,
            ];
        }
        if (money_cmp($merged[$pid]['discount'], money_mul($merged[$pid]['quantity'], $merged[$pid]['unit_price'])) > 0) {
            return ['ok' => false, 'message' => "تخفیف کالای {$pid} از مبلغ ردیف بیشتر است.", 'items' => []];
        }
    }

    $items = [];
    foreach ($merged as $row) {
        $row['line_total'] = calculateLineTotal($row['quantity'], $row['unit_price'], $row['discount']);
        $items[] = $row;
    }
    return ['ok' => true, 'message' => '', 'items' => $items];
}

/* ============================================================
 * ۹) اعتبارسنجی کلی درخواست فاکتور
 * ========================================================== */

function validateInvoice(mysqli $mysqli, array $in): array
{
    $type = $in['type'] ?? '';
    if (!in_array($type, INVOICE_TYPES, true)) {
        return ['ok' => false, 'message' => 'نوع فاکتور نامعتبر است.'];
    }

    $partyId = (int) ($in['party_id'] ?? 0);
    if ($partyId <= 0) {
        return ['ok' => false, 'message' => isSales($type) ? 'مشتری الزامی است.' : 'تامین‌کننده الزامی است.'];
    }
    $table = isSales($type) ? 'customers' : 'suppliers';
    $stmt = $mysqli->prepare("SELECT id FROM {$table} WHERE id = ?");
    $stmt->bind_param('i', $partyId);
    $stmt->execute();
    $stmt->store_result();
    $exists = $stmt->num_rows > 0;
    $stmt->close();
    if (!$exists) {
        return ['ok' => false, 'message' => isSales($type) ? 'مشتری انتخاب‌شده معتبر نیست.' : 'تامین‌کننده انتخاب‌شده معتبر نیست.'];
    }

    if (!in_array($in['payment_type'] ?? '', PAYMENT_TYPES, true)) {
        return ['ok' => false, 'message' => 'نوع پرداخت نامعتبر است.'];
    }
    if (!in_array($in['payment_status'] ?? '', PAYMENT_STATUSES, true)) {
        return ['ok' => false, 'message' => 'وضعیت پرداخت نامعتبر است.'];
    }
    if (empty($in['invoice_date']) || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $in['invoice_date'])) {
        return ['ok' => false, 'message' => 'تاریخ فاکتور نامعتبر است.'];
    }
    if (!checkdate(
        (int) substr($in['invoice_date'], 5, 2),
        (int) substr($in['invoice_date'], 8, 2),
        (int) substr($in['invoice_date'], 0, 4)
    )) {
        return ['ok' => false, 'message' => 'تاریخ فاکتور معتبر نیست.'];
    }

    $discount = trim((string) ($in['discount'] ?? '0'));
    if ($discount === '' || !is_numeric($discount) || money_cmp($discount, '0') < 0) {
        return ['ok' => false, 'message' => 'تخفیف فاکتور نامعتبر است.'];
    }
    $in['discount'] = money_round($discount);

    return ['ok' => true, 'message' => ''];
}

/* ============================================================
 * ۱۰) تنظیمات مالیات (منبع حقیقت: tax_settings)
 * ========================================================== */

function loadTaxSettings(mysqli $mysqli): array
{
    $enabled = false;
    $rate = '0';
    $res = $mysqli->query('SELECT tax_enabled, tax_rate FROM tax_settings WHERE id = 1 LIMIT 1');
    if ($res && $row = $res->fetch_assoc()) {
        $enabled = (bool) $row['tax_enabled'];
        $rate = money_round($row['tax_rate']);
    }
    return ['tax_enabled' => $enabled, 'tax_rate' => $rate];
}

/* ============================================================
 * ۱۱) خواندن فاکتور + اقلام
 * ========================================================== */

function getInvoice(mysqli $mysqli, int $invoiceId): ?array
{
    $stmt = $mysqli->prepare('SELECT * FROM invoices WHERE id = ?');
    $stmt->bind_param('i', $invoiceId);
    $stmt->execute();
    $res = $stmt->get_result();
    $stmt->close();
    $inv = $res ? $res->fetch_assoc() : null;
    if (!$inv) {
        return null;
    }

    if (isSales($inv['type'])) {
        $stmt = $mysqli->prepare('SELECT CONCAT(first_name," ",last_name) AS name, phone FROM customers WHERE id = ?');
        $stmt->bind_param('i', $inv['customer_id']);
        $stmt->execute();
        $r = $stmt->get_result();
        $stmt->close();
        $party = $r ? $r->fetch_assoc() : null;
        $inv['party_name'] = $party['name'] ?? '';
        $inv['party_phone'] = $party['phone'] ?? '';
    } else {
        $stmt = $mysqli->prepare(
            'SELECT IF(company_name IS NOT NULL AND company_name <> "", company_name,
                CONCAT(COALESCE(first_name,"")," ",COALESCE(last_name,""))) AS name, phone
             FROM suppliers WHERE id = ?'
        );
        $stmt->bind_param('i', $inv['supplier_id']);
        $stmt->execute();
        $r = $stmt->get_result();
        $stmt->close();
        $party = $r ? $r->fetch_assoc() : null;
        $inv['party_name'] = $party['name'] ?? '';
        $inv['party_phone'] = $party['phone'] ?? '';
    }

    $inv['invoice_date_shamsi'] = $inv['invoice_date']
        ? jdate('j F Y', strtotime($inv['invoice_date']))
        : '';

    $items = [];
    $stmt = $mysqli->prepare(
        'SELECT ii.*, p.code AS product_code, p.name AS product_name, p.unit AS product_unit
         FROM invoice_items ii
         LEFT JOIN products p ON p.id = ii.product_id
         WHERE ii.invoice_id = ?
         ORDER BY ii.id'
    );
    $stmt->bind_param('i', $invoiceId);
    $stmt->execute();
    $res = $stmt->get_result();
    $stmt->close();
    if ($res) {
        while ($row = $res->fetch_assoc()) {
            $items[] = $row;
        }
    }

    $inv['items'] = $items;
    return $inv;
}

/* ============================================================
 * ۱۲) مقایسه اقلام قدیم/جدی (Delta)
 * ========================================================== */

function compareInvoiceItems(array $oldItems, array $newItems): array
{
    $old = [];
    foreach ($oldItems as $it) {
        $pid = (int) $it['product_id'];
        $old[$pid] = money_add($old[$pid] ?? '0', $it['quantity']);
    }
    $new = [];
    foreach ($newItems as $it) {
        $pid = (int) $it['product_id'];
        $new[$pid] = money_add($new[$pid] ?? '0', $it['quantity']);
    }
    $all = array_values(array_unique(array_merge(array_keys($old), array_keys($new))));
    $delta = [];
    foreach ($all as $pid) {
        $delta[$pid] = money_sub($new[$pid] ?? '0', $old[$pid] ?? '0');
    }
    return $delta;
}

/* ============================================================
 * ۱۳) درج اقلام در دیتابیس
 * ========================================================== */

function insertInvoiceItems(mysqli $mysqli, int $invoiceId, array $items): void
{
    $stmt = $mysqli->prepare(
        'INSERT INTO invoice_items (invoice_id, product_id, quantity, unit_price, discount, line_total)
         VALUES (?, ?, ?, ?, ?, ?)'
    );
    foreach ($items as $it) {
        $iid = $invoiceId;
        $pid = (int) $it['product_id'];
        $qty = money_round($it['quantity']);
        $unit = money_round($it['unit_price']);
        $disc = money_round($it['discount']);
        $lt = money_round($it['line_total']);
        $stmt->bind_param('iidddd', $iid, $pid, $qty, $unit, $disc, $lt);
        $stmt->execute();
    }
    $stmt->close();
}

/* ============================================================
 * ۱۴) سرویس ساخت فاکتور جدید
 * ========================================================== */

function createInvoice(mysqli $mysqli, array $in): array
{
    $valid = validateInvoice($mysqli, $in);
    if (!$valid['ok']) {
        return ['ok' => false, 'message' => $valid['message']];
    }
    $norm = normalizeItems($mysqli, $in['items'] ?? []);
    if (!$norm['ok']) {
        return ['ok' => false, 'message' => $norm['message']];
    }
    $items = $norm['items'];

    $type = $in['type'];
    $partyId = (int) $in['party_id'];
    $tax = loadTaxSettings($mysqli);
    if (!$tax['tax_enabled']) {
        $taxRate = '0';
    } else {
        $providedTax = $in['tax_rate'] ?? null;
        if ($providedTax !== null && $providedTax !== '') {
            $taxRate = money_round($providedTax);
            if (money_cmp($taxRate, '0') < 0 || money_cmp($taxRate, '100') > 0) {
                return ['ok' => false, 'message' => 'نرخ مالیات باید بین ۰ تا ۱۰۰ درصد باشد.'];
            }
        } else {
            $taxRate = money_round($tax['tax_rate']);
        }
    }
    $invoiceDiscount = money_round($in['discount']);
    $clientToken = trim((string) ($in['client_token'] ?? ''));
    $clientToken = $clientToken === '' ? null : substr($clientToken, 0, 64);
    $note = ($in['note'] ?? null);
    $note = ($note === '' || $note === null) ? null : (string) $note;

    $allPids = array_map('intval', array_column($items, 'product_id'));

    $mysqli->begin_transaction();
    try {
        // ایدمپوتنسی: اگر client_token قبلاً استفاده شده باشد همان فاکتور برگردد
        if ($clientToken !== null) {
            $stmt = $mysqli->prepare('SELECT id FROM invoices WHERE client_token = ? LIMIT 1');
            $stmt->bind_param('s', $clientToken);
            $stmt->execute();
            $res = $stmt->get_result();
            $stmt->close();
            if ($res && $row = $res->fetch_assoc()) {
                $mysqli->commit();
                return ['ok' => true, 'message' => 'فاکتور قبلاً با همین توکن ثبت شده است.', 'invoice_id' => (int) $row['id']];
            }
        }

        $invoiceNumber = generateInvoiceNumber($mysqli, $type);
        $totals = calculateInvoiceTotals($items, $invoiceDiscount, $taxRate, $tax['tax_enabled']);
        if (money_cmp($invoiceDiscount, $totals['subtotal']) > 0) {
            throw new Exception('discount_exceeds_subtotal');
        }

        $customerId = isSales($type) ? $partyId : null;
        $supplierId = isPurchase($type) ? $partyId : null;

        $stmt = $mysqli->prepare(
            "INSERT INTO invoices
                (invoice_number, type, customer_id, supplier_id, payment_type, payment_status,
                 invoice_date, subtotal, discount, tax_rate, tax_amount, payable_amount, note, client_token)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)"
        );
        $stmt->bind_param(
            'ssiisssdddddss',
            $invoiceNumber,
            $type,
            $customerId,
            $supplierId,
            $in['payment_type'],
            $in['payment_status'],
            $in['invoice_date'],
            $totals['subtotal'],
            $invoiceDiscount,
            $taxRate,
            $totals['tax_amount'],
            $totals['payable_amount'],
            $note,
            $clientToken
        );
        $stmt->execute();
        $invoiceId = (int) $stmt->insert_id;
        $stmt->close();

        insertInvoiceItems($mysqli, $invoiceId, $items);

        // موجودی — فقط فاکتور قطعی
        if (isFinal($type)) {
            $stocks = lockProductsById($mysqli, $allPids);
            foreach ($items as $it) {
                $pid = (int) $it['product_id'];
                $change = isSales($type) ? money_mul('-1', $it['quantity']) : $it['quantity'];
                applyStockChange($mysqli, $pid, $stocks[$pid], $change, $invoiceId, $type, 'create');
            }
        }

        $mysqli->commit();
        return ['ok' => true, 'message' => 'فاکتور با موفقیت ثبت شد.', 'invoice_id' => $invoiceId];
    } catch (Exception $e) {
        $mysqli->rollback();
        $msg = invoiceErrorMessage($e, $mysqli, $clientToken);
        if ($msg === 'DOUBLE_SUBMIT_REUSE') {
            return [
                'ok' => true,
                'message' => 'فاکتور قبلاً با همین توکن ثبت شده است.',
                'invoice_id' => $GLOBALS['__invoice_idempotent_id'] ?? 0,
            ];
        }
        return ['ok' => false, 'message' => $msg];
    }
}

/* --- نگاشت خطای داخلی به پیام کاربر و استثنای ایدمپوتنسی --- */
function invoiceErrorMessage(Exception $e, mysqli $mysqli, ?string $clientToken): string
{
    $msg = $e->getMessage();
    if ($msg === 'negative_stock') {
        return 'موجودی کافی برای این عملیات وجود ندارد.';
    }
    if ($msg === 'discount_exceeds_subtotal') {
        return 'تخفیف فاکتور نمی‌تواند از جمع ردیف‌ها بیشتر باشد.';
    }
    if ($msg === 'product_not_found') {
        return 'یکی از کالاهای انتخاب‌شده معتبر نیست.';
    }
    if ($mysqli->errno === 1062 && $clientToken !== null) {
        // تداخل UNIQUE روی client_token: فاکتور موازی ثبت‌شده را برگردان
        $stmt = $mysqli->prepare('SELECT id FROM invoices WHERE client_token = ? LIMIT 1');
        $stmt->bind_param('s', $clientToken);
        $stmt->execute();
        $res = $stmt->get_result();
        $stmt->close();
        if ($res && $row = $res->fetch_assoc()) {
            $GLOBALS['__invoice_idempotent_id'] = (int) $row['id'];
            return 'DOUBLE_SUBMIT_REUSE';
        }
    }
    return 'خطا در ذخیره‌سازی: ' . $msg;
}

/* ============================================================
 * ۱۵) سرویس ویرایش فاکتور (Delta-based)
 * ========================================================== */

function editInvoice(mysqli $mysqli, int $invoiceId, array $in): array
{
    $stmt = $mysqli->prepare('SELECT * FROM invoices WHERE id = ? FOR UPDATE');
    $stmt->bind_param('i', $invoiceId);
    $stmt->execute();
    $res = $stmt->get_result();
    $stmt->close();
    $old = $res ? $res->fetch_assoc() : null;
    if (!$old) {
        return ['ok' => false, 'message' => 'فاکتور موردنظر یافت نشد.'];
    }
    $oldType = $old['type'];

    $valid = validateInvoice($mysqli, $in);
    if (!$valid['ok']) {
        return ['ok' => false, 'message' => $valid['message']];
    }
    $newType = $in['type'];

    if (isFinal($oldType) !== isFinal($newType)) {
        return ['ok' => false, 'message' => 'تبدیل بین فاکتور قطعی و پیش‌فاکتور باید از عملیات «تبدیل» انجام شود.'];
    }
    if (isSales($oldType) !== isSales($newType)) {
        return ['ok' => false, 'message' => 'تغییر جهت فروش/خرید فاکتور مجاز نیست.'];
    }

    $norm = normalizeItems($mysqli, $in['items'] ?? []);
    if (!$norm['ok']) {
        return ['ok' => false, 'message' => $norm['message']];
    }
    $newItems = $norm['items'];

    $oldItems = [];
    $stmt = $mysqli->prepare('SELECT product_id, quantity FROM invoice_items WHERE invoice_id = ? FOR UPDATE');
    $stmt->bind_param('i', $invoiceId);
    $stmt->execute();
    $res = $stmt->get_result();
    $stmt->close();
    while ($res && ($row = $res->fetch_assoc())) {
        $oldItems[] = $row;
    }

    $tax = loadTaxSettings($mysqli);
    if (!$tax['tax_enabled']) {
        $taxRate = '0';
    } else {
        $providedTax = $in['tax_rate'] ?? null;
        if ($providedTax !== null && $providedTax !== '') {
            $taxRate = money_round($providedTax);
            if (money_cmp($taxRate, '0') < 0 || money_cmp($taxRate, '100') > 0) {
                return ['ok' => false, 'message' => 'نرخ مالیات باید بین ۰ تا ۱۰۰ درصد باشد.'];
            }
        } else {
            $taxRate = money_round($tax['tax_rate']);
        }
    }
    $invoiceDiscount = money_round($in['discount']);
    $totals = calculateInvoiceTotals($newItems, $invoiceDiscount, $taxRate, $tax['tax_enabled']);
    if (money_cmp($invoiceDiscount, $totals['subtotal']) > 0) {
        return ['ok' => false, 'message' => 'تخفیف فاکتور نمی‌تواند از جمع ردیف‌ها بیشتر باشد.'];
    }

    $allPids = [];
    foreach ($oldItems as $it) {
        $allPids[] = (int) $it['product_id'];
    }
    foreach ($newItems as $it) {
        $allPids[] = (int) $it['product_id'];
    }

    $mysqli->begin_transaction();
    try {
        $stocks = $allPids ? lockProductsById($mysqli, $allPids) : [];

        if (isFinal($newType)) {
            $deltas = compareInvoiceItems($oldItems, $newItems);
            foreach ($deltas as $pid => $delta) {
                $pid = (int) $pid;
                if (money_cmp($delta, '0') === 0) {
                    continue; // فقط قیمت/تخفیف/شماره تغییر کرده → موجودی تغییر نکند
                }
                $change = isSales($newType) ? money_mul('-1', $delta) : $delta;
                applyStockChange($mysqli, $pid, $stocks[$pid], $change, $invoiceId, $newType, 'edit');
            }
        }

        // بازنویسی اقلام
        $stmt = $mysqli->prepare('DELETE FROM invoice_items WHERE invoice_id = ?');
        $stmt->bind_param('i', $invoiceId);
        $stmt->execute();
        $stmt->close();
        insertInvoiceItems($mysqli, $invoiceId, $newItems);

        $partyId = (int) $in['party_id'];
        $customerId = isSales($newType) ? $partyId : null;
        $supplierId = isPurchase($newType) ? $partyId : null;
        $note = ($in['note'] ?? null);
        $note = ($note === '' || $note === null) ? null : (string) $note;

        $stmt = $mysqli->prepare(
            'UPDATE invoices SET
                customer_id = ?, supplier_id = ?, payment_type = ?, payment_status = ?,
                invoice_date = ?, subtotal = ?, discount = ?, tax_rate = ?, tax_amount = ?,
                payable_amount = ?, note = ?
             WHERE id = ?'
        );
        $stmt->bind_param(
            'iisssdddddsi',
            $customerId,
            $supplierId,
            $in['payment_type'],
            $in['payment_status'],
            $in['invoice_date'],
            $totals['subtotal'],
            $invoiceDiscount,
            $taxRate,
            $totals['tax_amount'],
            $totals['payable_amount'],
            $note,
            $invoiceId
        );
        $stmt->execute();
        $stmt->close();

        $mysqli->commit();
        return ['ok' => true, 'message' => 'فاکتور با موفقیت ویرایش شد.', 'invoice_id' => $invoiceId];
    } catch (Exception $e) {
        $mysqli->rollback();
        $msg = $e->getMessage();
        if ($msg === 'negative_stock') {
            $msg = 'موجودی کافی برای اعمال این ویرایش وجود ندارد.';
        } elseif ($msg === 'product_not_found') {
            $msg = 'یکی از کالاهای انتخاب‌شده معتبر نیست.';
        }
        return ['ok' => false, 'message' => $msg];
    }
}

/* ============================================================
 * ۱۶) سرویس حذف فاکتور
 * ========================================================== */

function deleteInvoice(mysqli $mysqli, int $invoiceId): array
{
    $stmt = $mysqli->prepare('SELECT * FROM invoices WHERE id = ? FOR UPDATE');
    $stmt->bind_param('i', $invoiceId);
    $stmt->execute();
    $res = $stmt->get_result();
    $stmt->close();
    $inv = $res ? $res->fetch_assoc() : null;
    if (!$inv) {
        return ['ok' => false, 'message' => 'فاکتور موردنظر یافت نشد.'];
    }
    $type = $inv['type'];

    $oldItems = [];
    $stmt = $mysqli->prepare('SELECT product_id, quantity FROM invoice_items WHERE invoice_id = ? FOR UPDATE');
    $stmt->bind_param('i', $invoiceId);
    $stmt->execute();
    $res = $stmt->get_result();
    $stmt->close();
    while ($res && ($row = $res->fetch_assoc())) {
        $oldItems[] = $row;
    }
    $allPids = array_map('intval', array_column($oldItems, 'product_id'));

    $mysqli->begin_transaction();
    try {
        $stocks = $allPids ? lockProductsById($mysqli, $allPids) : [];

        // فقط فاکتور قطعی روی موجودی اثر داشته؛ حذف آن برعکس اعمال می‌شود
        if (isFinal($type)) {
            foreach ($oldItems as $it) {
                $pid = (int) $it['product_id'];
                // Sales: stock += qty | Purchase: stock -= qty (با چک منفی)
                $change = isSales($type) ? $it['quantity'] : money_mul('-1', $it['quantity']);
                applyStockChange($mysqli, $pid, $stocks[$pid], $change, $invoiceId, $type, 'delete');
            }
        } elseif ($inv['type'] === 'sales_proforma' || $inv['type'] === 'purchase_proforma') {
            // پیش‌فاکتور: بدون تغییر موجودی
        }

        // اقلام با CASCADE حذف می‌شوند؛ stock_movements با ON DELETE SET NULL باقی می‌ماند
        $stmt = $mysqli->prepare('DELETE FROM invoices WHERE id = ?');
        $stmt->bind_param('i', $invoiceId);
        $stmt->execute();
        $stmt->close();

        $mysqli->commit();
        return ['ok' => true, 'message' => 'فاکتور با موفقیت حذف شد.', 'invoice_id' => $invoiceId];
    } catch (Exception $e) {
        $mysqli->rollback();
        $msg = $e->getMessage();
        if ($msg === 'negative_stock') {
            $msg = 'موجودی فعلی کافی برای حذف این فاکتور نیست؛ عملیات لغو شد.';
        } elseif ($msg === 'product_not_found') {
            $msg = 'یکی از کالاهای فاکتور دیگر معتبر نیست.';
        }
        return ['ok' => false, 'message' => $msg];
    }
}

/* ============================================================
 * ۱۷) سرویس تبدیل پیش‌فاکتور → فاکتور قطعی (یک‌بار)
 * ========================================================== */

function convertInvoice(mysqli $mysqli, int $invoiceId): array
{
    $stmt = $mysqli->prepare('SELECT * FROM invoices WHERE id = ? FOR UPDATE');
    $stmt->bind_param('i', $invoiceId);
    $stmt->execute();
    $res = $stmt->get_result();
    $stmt->close();
    $inv = $res ? $res->fetch_assoc() : null;
    if (!$inv) {
        return ['ok' => false, 'message' => 'فاکتور موردنظر یافت نشد.'];
    }
    if (isFinal($inv['type'])) {
        return ['ok' => false, 'message' => 'این فاکتور قبلاً به فاکتور قطعی تبدیل شده است.'];
    }

    $type = $inv['type'];
    $finalType = isSales($type) ? 'sales_invoice' : 'purchase_invoice';
    $isSales = isSales($type);

    $items = [];
    $stmt = $mysqli->prepare('SELECT product_id, quantity FROM invoice_items WHERE invoice_id = ?');
    $stmt->bind_param('i', $invoiceId);
    $stmt->execute();
    $res = $stmt->get_result();
    $stmt->close();
    while ($res && ($row = $res->fetch_assoc())) {
        $items[] = $row;
    }
    $allPids = array_map('intval', array_column($items, 'product_id'));

    $mysqli->begin_transaction();
    try {
        $stocks = $allPids ? lockProductsById($mysqli, $allPids) : [];
        $newNumber = generateInvoiceNumber($mysqli, $finalType);

        foreach ($items as $it) {
            $pid = (int) $it['product_id'];
            $change = $isSales ? money_mul('-1', $it['quantity']) : $it['quantity'];
            applyStockChange($mysqli, $pid, $stocks[$pid], $change, $invoiceId, $finalType, 'create');
        }

        $stmt = $mysqli->prepare('UPDATE invoices SET type = ?, invoice_number = ? WHERE id = ?');
        $stmt->bind_param('ssi', $finalType, $newNumber, $invoiceId);
        $stmt->execute();
        $stmt->close();

        $mysqli->commit();
        return ['ok' => true, 'message' => 'پیش‌فاکتور با موفقیت به فاکتور قطعی تبدیل شد.', 'invoice_id' => $invoiceId];
    } catch (Exception $e) {
        $mysqli->rollback();
        $msg = $e->getMessage();
        if ($msg === 'negative_stock') {
            $msg = 'موجودی کافی برای تبدیل به فاکتور قطعی وجود ندارد.';
        } elseif ($msg === 'product_not_found') {
            $msg = 'یکی از کالاهای فاکتور معتبر نیست.';
        }
        return ['ok' => false, 'message' => $msg];
    }
}

/* ============================================================
 * ۱۸) سرویس لیست فاکتورها
 * ========================================================== */

function listInvoices(mysqli $mysqli, array $filter): array
{
    $where = [];
    $types = '';
    $params = [];

    $typeGroup = $filter['type_group'] ?? ''; // sales | purchase | ''
    if ($typeGroup === 'sales') {
        $where[] = "type IN ('sales_invoice','sales_proforma')";
    } elseif ($typeGroup === 'purchase') {
        $where[] = "type IN ('purchase_invoice','purchase_proforma')";
    }

    if (!empty($filter['search'])) {
        $search = '%' . $filter['search'] . '%';

        if ($typeGroup === 'sales') {
            // جستجوی فاکتور فروش بر اساس:
            // شماره فاکتور، یادداشت، نام/نام خانوادگی مشتری
            $where[] = '(
            invoices.invoice_number LIKE ?
            OR invoices.note LIKE ?
            OR EXISTS (
                SELECT 1
                FROM customers c
                WHERE c.id = invoices.customer_id
                  AND (
                      c.first_name LIKE ?
                      OR c.last_name LIKE ?
                      OR CONCAT(c.first_name, " ", c.last_name) LIKE ?
                  )
            )
        )';

            $types .= 'sssss';
            $params[] = $search;
            $params[] = $search;
            $params[] = $search;
            $params[] = $search;
            $params[] = $search;
        } elseif ($typeGroup === 'purchase') {
            // جستجوی فاکتور خرید بر اساس:
            // شماره فاکتور، یادداشت، نام شرکت، نام و نام خانوادگی تامین‌کننده
            $where[] = '(
            invoices.invoice_number LIKE ?
            OR invoices.note LIKE ?
            OR EXISTS (
                SELECT 1
                FROM suppliers s
                WHERE s.id = invoices.supplier_id
                  AND (
                      s.company_name LIKE ?
                      OR s.first_name LIKE ?
                      OR s.last_name LIKE ?
                      OR CONCAT(s.first_name, " ", s.last_name) LIKE ?
                  )
            )
        )';

            $types .= 'ssssss';
            $params[] = $search;
            $params[] = $search;
            $params[] = $search;
            $params[] = $search;
            $params[] = $search;
            $params[] = $search;
        } else {
            // حالت عمومی؛ برای جلوگیری از تغییر رفتار سایر استفاده‌های تابع
            $where[] = '(invoices.invoice_number LIKE ? OR invoices.note LIKE ?)';
            $types .= 'ss';
            $params[] = $search;
            $params[] = $search;
        }
    }
    if (!empty($filter['payment_status']) && in_array($filter['payment_status'], PAYMENT_STATUSES, true)) {
        $where[] = 'payment_status = ?';
        $types .= 's';
        $params[] = $filter['payment_status'];
    }
    if (!empty($filter['type']) && in_array($filter['type'], INVOICE_TYPES, true)) {
        $where[] = 'type = ?';
        $types .= 's';
        $params[] = $filter['type'];
    }

    $sql = 'SELECT * FROM invoices';
    if ($where) {
        $sql .= ' WHERE ' . implode(' AND ', $where);
    }
    $sql .= ' ORDER BY id DESC LIMIT 500';

    if ($types !== '') {
        $stmt = $mysqli->prepare($sql);
        $stmt->bind_param($types, ...$params);
        $stmt->execute();
        $res = $stmt->get_result();
        $stmt->close();
    } else {
        $res = $mysqli->query($sql);
    }

    $rows = [];
    if ($res) {
        while ($row = $res->fetch_assoc()) {
            $rows[] = $row;
        }
    }

    $partyCache = [];
    foreach ($rows as &$r) {
        if (isSales($r['type'])) {
            $cid = (int) $r['customer_id'];
            if (!isset($partyCache['c'][$cid])) {
                $stmt = $mysqli->prepare('SELECT CONCAT(first_name," ",last_name) AS name FROM customers WHERE id = ?');
                $stmt->bind_param('i', $cid);
                $stmt->execute();
                $q = $stmt->get_result();
                $stmt->close();
                $partyCache['c'][$cid] = ($q && ($p = $q->fetch_assoc())) ? $p['name'] : '';
            }
            $r['party_name'] = $partyCache['c'][$cid];
        } else {
            $sid = (int) $r['supplier_id'];
            if (!isset($partyCache['s'][$sid])) {
                $stmt = $mysqli->prepare(
                    'SELECT IF(company_name IS NOT NULL AND company_name <> "", company_name,
                        CONCAT(COALESCE(first_name,"")," ",COALESCE(last_name,""))) AS name
                     FROM suppliers WHERE id = ?'
                );
                $stmt->bind_param('i', $sid);
                $stmt->execute();
                $q = $stmt->get_result();
                $stmt->close();
                $partyCache['s'][$sid] = ($q && ($p = $q->fetch_assoc())) ? $p['name'] : '';
            }
            $r['party_name'] = $partyCache['s'][$sid];
        }
        $r['invoice_date_shamsi'] = $r['invoice_date'] ? jdate('j F Y', strtotime($r['invoice_date'])) : '';
        unset($r);
    }

    return $rows;
}
