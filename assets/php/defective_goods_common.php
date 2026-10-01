<?php
/**
 * defective_goods_common.php — منطق مشترک ماژول کالاهای معیوب
 *
 * ترتیب بارگذاری: boot.php → db.php → invoice_common.php → defective_goods_common.php
 * متغیر سراسری $mysqli فرض می‌شود. تغییرات موجودی فقط از طریق applyStockChange (معماری متمرکز موجودی) انجام می‌شود.

 * تمام کوئری‌ها با prepared statement ساخته می‌شوند و هیچ ورودی کاربر مستقیماً
 * داخل SQL قرار نمی‌گیرد.
 */

require_once __DIR__ . '/invoice_common.php';

/**
 * اعتبارسنجی و نرمال‌سازی تاریخ میلادی به شکل Y-m-d
 */
function dg_parse_greg_date(string $raw): ?string
{
    $raw = trim($raw);
    if ($raw === '' || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $raw)) {
        return null;
    }
    if (!checkdate((int) substr($raw, 5, 2), (int) substr($raw, 8, 2), (int) substr($raw, 0, 4))) {
        return null;
    }
    return $raw;
}

/**
 * استخراج و اعتبارسنجی فیلترهای بازه تاریخ گزارش
 *
 * @return array ['from' => ?string, 'to' => ?string] تاریخ میلادی Y-m-d
 */
function dg_extract_date_filters(array $get): array
{
    $from = isset($get['from']) ? dg_parse_greg_date((string) $get['from']) : null;
    $to   = isset($get['to'])   ? dg_parse_greg_date((string) $get['to'])   : null;

    /* اگر از تاریخ بزرگ‌تر از تا تاریخ باشد، جابه‌جا می‌کنیم تا بازه همیشه معتبر باشد */
    if ($from !== null && $to !== null && $from > $to) {
        $tmp  = $from;
        $from = $to;
        $to   = $tmp;
    }

    return ['from' => $from, 'to' => $to];
}

/**
 * ساخت شرط WHERE فیلتر تاریخ (فقط پارامترهای آماده)

 * @return array ['sql' => string, 'types' => string, 'params' => array]
 */
function dg_where_sql(array $filters): array
{
    $cond = [];
    $types = '';
    $params = [];

    if (!empty($filters['from'])) {
        $cond[] = 'dg.defective_date >= ?';
        $types .= 's';
        $params[] = $filters['from'];
    }
    if (!empty($filters['to'])) {
        $cond[] = 'dg.defective_date <= ?';
        $types .= 's';
        $params[] = $filters['to'];
    }

    return [
        'sql'    => $cond ? 'WHERE ' . implode(' AND ', $cond) : '',
        'types'  => $types,
        'params' => $params,
    ];
}

/**
 * اجرای کوئری آماده با پارامترهای اختیاری و بازگرداندن همه سطرها
 */
function dg_run_query(mysqli $mysqli, string $sql, string $types = '', array $params = []): array
{
    $rows = [];
    if ($types !== '') {
        $stmt = $mysqli->prepare($sql);
        if ($stmt) {
            $stmt->bind_param($types, ...$params);
            $stmt->execute();
            $res = $stmt->get_result();
            $stmt->close();
            if ($res) {
                while ($row = $res->fetch_assoc()) {
                    $rows[] = $row;
                }
                $res->free();
            }
        }
    } else {
        $res = $mysqli->query($sql);
        if ($res) {
            while ($row = $res->fetch_assoc()) {
                $rows[] = $row;
            }
            $res->free();
        }
    }
    return $rows;
}

/**
 * اجرای کوئری COUNT با پارامترهای اختیاری و بازگرداندن شمار
 */
function dg_run_count(mysqli $mysqli, string $sql, string $types = '', array $params = []): int
{
    $count = 0;
    if ($types !== '') {
        $stmt = $mysqli->prepare($sql);
        if ($stmt) {
            $stmt->bind_param($types, ...$params);
            $stmt->execute();
            $res = $stmt->get_result();
            $stmt->close();
            $row = $res ? $res->fetch_assoc() : null;
            $count = $row ? (int) ($row['c'] ?? 0) : 0;
        }
    } else {
        $res = $mysqli->query($sql);
        $row = $res ? $res->fetch_assoc() : null;
        $count = $row ? (int) ($row['c'] ?? 0) : 0;
    }
    return $count;
}

/**
 * خلاصه گزارش (تعداد رکوردها و جمع تعداد معیوب) — با احترام کامل به فیلتر تاریخ
 *
 * @return array ['records' => int, 'total_quantity' => string]
 */
function dg_summary(mysqli $mysqli, array $filters): array
{
    $w = dg_where_sql($filters);
    $sql = "SELECT COUNT(*) AS c, COALESCE(SUM(dg.quantity), 0) AS tq
            FROM defective_goods dg {$w['sql']}";
    $rows = dg_run_query($mysqli, $sql, $w['types'], $w['params']);
    $row = $rows[0] ?? [];

    return [
        'records'         => (int) ($row['c'] ?? 0),
        'total_quantity' => money_round((string) ($row['tq'] ?? '0')),
    ];
}

/**
 * لیست صفحه‌بندی‌شده گزارش (سمت سرور؛ پیش‌فرض ۲۵ رکورد در هر صفحه)

 * @return array rows/total/totalPages/page/perPage/rangeStart/rangeEnd
 */
function dg_list(mysqli $mysqli, array $filters, int $page, int $perPage = 25): array
{
    $w = dg_where_sql($filters);

    $total = dg_run_count(
        $mysqli,
        "SELECT COUNT(*) AS c FROM defective_goods dg {$w['sql']}",
        $w['types'],
        $w['params']
    );

    $totalPages = max(1, (int) ceil($total / $perPage));
    if ($page > $totalPages) {
        $page = $totalPages;

    }
    if ($page < 1) {
        $page = 1;
    }
    $offset = ($page - 1) * $perPage;


    $sql = "SELECT dg.id,dg.product_id,dg.quantity,dg.defective_date,dg.reason,dg.description,
                   p.name AS product_name,p.code AS product_code,p.barcode AS product_barcode,p.unit AS product_unit
            FROM defective_goods dg
            JOIN products p ON p.id = dg.product_id
            {$w['sql']}
            ORDER BY dg.defective_date DESC,dg.id DESC
            LIMIT ? OFFSET ?";

    $allTypes = $w['types'] . 'ii';
    $allParams = array_merge($w['params'], [$perPage, $offset]);
    $rows = dg_run_query($mysqli, $sql, $allTypes, $allParams);

    return [
        'rows'        => $rows,
        'total'       => $total,
        'totalPages' => $totalPages,
        'page'        => $page,
        'perPage'    => $perPage,
        'rangeStart' => $total === 0 ? 0 : $offset + 1,
        'rangeEnd'   => min($page * $perPage, $total),
    ];
}

/**
 * دریافت همه رکوردهای منطبق با فیلتر (برای خروجی اکسل — مستقل از صفحه‌بندی)
 */
function dg_all_for_export(mysqli $mysqli, array $filters): array
{
    $w = dg_where_sql($filters);
    $sql = "SELECT dg.id,dg.product_id,dg.quantity,dg.defective_date,dg.reason,dg.description,
                   p.name AS product_name,p.code AS product_code,p.barcode AS product_barcode,p.unit AS product_unit
            FROM defective_goods dg
            JOIN products p ON p.id = dg.product_id
            {$w['sql']}
            ORDER BY dg.defective_date DESC,dg.id DESC";

    return dg_run_query($mysqli, $sql, $w['types'], $w['params']);
}

/**
 * ثبت کالای معیوب — اعتبارسنجی کامل سمت سرور + تراکنش اتمیک.
 *
 * کاهش موجودی فقط از طریق applyStockChange (معماری متمرکز موجودی) اعمال می‌شود:
 * قفل ردیف (FOR UPDATE) داخل تراکنش از race condition جلوگیری می‌کند و موجودی
 * هرگز منفی نمی‌شود. رکورد تاریخی مستقل از فاکتور خرید ذخیره می‌شود و هیچ اثری
 * روی بدهی تامین‌کننده یا فاکتورهای موجود ندارد.
 *
 * @return array ['ok' => bool, 'message' => string, 'data' => array|null]
 */
function dg_register_defective(mysqli $mysqli, int $productId, string $qtyRaw, string $dateRaw, string $reason, string $description): array
{
    if ($productId <= 0) {
        return ['ok' => false, 'message' => 'کالای انتخاب‌شده نامعتبر است.'];
    }

    if ($qtyRaw === '' || !is_numeric($qtyRaw) || money_cmp($qtyRaw, '0') <= 0) {
        return ['ok' => false, 'message' => 'تعداد معیوب باید عددی بزرگ‌تر از صفر باشد.'];
    }
    if (!preg_match('/^\d+(\.\d{1,2})?$/', $qtyRaw)) {
        return ['ok' => false, 'message' => 'تعداد معیوب حداکثر می‌تواند دو رقم اعشار داشته باشد.'];
    }
    $qty = money_round($qtyRaw);

    $date = dg_parse_greg_date($dateRaw);
    if ($date === null) {
        return ['ok' => false, 'message' => 'تاریخ ثبت کالای معیوب نامعتبر است.'];
    }

    $reason = trim($reason);
    if ($reason === '') {
        return ['ok' => false, 'message' => 'دلیل ثبت کالای معیوب الزامی است.'];
    }
    if (mb_strlen($reason) > 500) {
        return ['ok' => false, 'message' => 'دلیل ثبت کالای معیوب حداکثر ۵۰۰ کاراکتر است.'];
    }

    $description = trim($description);
    if (mb_strlen($description) > 2000) {
        return ['ok' => false, 'message' => 'توضیحات حداکثر ۲۰۰۰ کاراکتر است.'];
    }

    /* کالا باید وجود داشته باشد و از نوع «محصول» (موجودی‌پذیر) باشد */
    $stmt = $mysqli->prepare('SELECT id, type FROM products WHERE id = ? LIMIT 1');
    $stmt->bind_param('i', $productId);
    $stmt->execute();
    $res = $stmt->get_result();
    $stmt->close();
    $productRow = $res ? $res->fetch_assoc() : null;
    if (!$productRow) {
        return ['ok' => false, 'message' => 'کالای انتخاب‌شده یافت نشد.'];
    }
    if ($productRow['type'] !== 'product') {
        return ['ok' => false, 'message' => 'ثبت کالای معیوب فقط برای کالاها (محصولات) با موجودی فیزیکی امکان‌پذیر است.'];
    }

    $newStock = null;
    $defectiveId = 0;
    $mysqli->begin_transaction();
    try {
        /* قفل ردیف کالا (FOR UPDATE) و خواندن موجودی به‌عنوان منبع حقیقت */
        $stocks = lockProductsById($mysqli, [$productId]);
        $stock = $stocks[$productId] ?? '0';

        if (money_cmp($qty, $stock) > 0) {
            throw new Exception('defective_exceeds_stock');
        }

        $stmt = $mysqli->prepare(
            'INSERT INTO defective_goods (product_id, quantity, defective_date, reason, description) VALUES (?, ?, ?, ?, ?)'
        );
        $stmt->bind_param('idsss', $productId, $qty, $date, $reason, $description);
        $stmt->execute();
        $defectiveId = (int) $stmt->insert_id;
        $stmt->close();

        /* کاهش موجودی + ثبت گردش موجودی (stock_movements) — همان معماری موجود پروژه */
        $newStock = applyStockChange(
            $mysqli,
            $productId,
            $stock,
            money_mul('-1', $qty),
            null,
            'defective_goods',
            'create'
        );

        $mysqli->commit();
    } catch (Exception $e) {
        $mysqli->rollback();
        $msg = $e->getMessage();
        if ($msg === 'product_not_found') {
            return ['ok' => false, 'message' => 'کالای انتخاب‌شده یافت نشد.'];
        }
        if ($msg === 'defective_exceeds_stock' || $msg === 'negative_stock') {
            return ['ok' => false, 'message' => 'تعداد معیوب بیشتر از موجودی فعلی کالا است.'];
        }
        return ['ok' => false, 'message' => $msg];
    }

    return [
        'ok' => true,
        'message' => 'کالای معیوب با موفقیت ثبت شد و موجودی کالا کاهش یافت.',
        'data' => [
            'defective_id' => $defectiveId,
            'quantity'      => $qty,
            'stock_before' => $stock,
            'stock_after'  => $newStock,
        ],
    ];
}