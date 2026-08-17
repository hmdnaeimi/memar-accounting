<?php
/**
 * product_excel_lib.php
 *
 * منطق نرمال‌سازی، نگاشت هدر و اعتبارسنجی رکوردهای Excel برای «ورود از اکسل»
 * کالاها و خدمات. این فایل هیچ فراخوانی boot/db ندارد؛ اتصال DB به‌صورت پارامتر
 * به توابع داده می‌شود تا هم در HTTP و هم در CLI قابل تست باشد.
 *
 * سازگاری: PHP >= 7.0 (بدون توابع مخصوص PHP 8)
 */

if (!defined('PRODUCT_EXCEL_LIB_LOADED')) {
    define('PRODUCT_EXCEL_LIB_LOADED', true);
}

/**
 * تبدیل ارقام ۰-۹ فارسی/عربی به 0-9 انگلیسی
 */
function product_excel_to_latin_digits($s)
{
    static $map = null;
    if ($map === null) {
        $fa = array('۰', '۱', '۲', '۳', '۴', '۵', '۶', '۷', '۸', '۹');
        $ar = array('٠', '١', '٢', '٣', '٤', '٥', '٦', '٧', '٨', '٩');
        $en = array('0', '1', '2', '3', '4', '5', '6', '7', '8', '9');
        $map = array();
        foreach ($en as $k => $v) {
            $map[$fa[$k]] = $v;
            $map[$ar[$k]] = $v;
        }
    }
    return strtr((string) $s, $map);
}

/**
 * نرمال‌سازی متن برای مقایسه (مطابقت هدرها، نام دسته‌بندی، کد)
 */
function product_excel_canonical($s)
{
    $s = product_excel_safe_utf8($s);
    $s = trim((string) $s);
    $s = product_excel_to_latin_digits($s);
    $s = strtr($s, array('ي' => 'ی', 'ك' => 'ک', 'ة' => 'ه', 'ۀ' => 'ه',
                         'ؤ' => 'و', 'أ' => 'ا', 'إ' => 'ا', 'آ' => 'ا'));
    $s = preg_replace('/[\x{200B}\x{200C}\x{200D}\x{200E}\x{200F}\x{202A}\x{202B}\x{202C}\x{202D}\x{202E}\x{2060}\x{FEFF}]/u', '', $s);
    $s = preg_replace('/\s+/u', ' ', $s);
    return mb_strtolower(trim($s), 'UTF-8');
}

/**
 * نام‌های مجاز هدر برای هر فیلد (پس از canonical)
 */
function product_excel_header_aliases()
{
    static $aliases = null;
    if ($aliases !== null) {
        return $aliases;
    }
    $raw = array(
        'code'           => array('کد کالا', 'کد', 'کدکالا', 'code'),
        'name'           => array('نام کالا', 'نام', 'کالا', 'name', 'نام کالا و خدمات'),
        'category'       => array('دسته‌بندی', 'دسته بندی', 'دسته', 'category', 'گروه کالا', 'گروه'),
        'type'           => array('نوع', 'type'),
        'unit'           => array('واحد', 'واحد اندازه‌گیری', 'unit'),
        'purchase_price' => array('قیمت خرید', 'قیمت خرید کالا', 'قیمت خريد', 'purchase price', 'purchaseprice'),
        'sale_price'     => array('قیمت فروش', 'قیمت فروش کالا', 'sale price', 'saleprice'),
        'stock'          => array('موجودی', 'stock', 'مقدار'),
        'min_stock'      => array('حداقل موجودی', 'حداقل', 'min stock', 'minstock', 'موجودی حداقل'),
        'description'    => array('توضیحات', 'توضیح', 'شرح', 'description'),
    );
    $aliases = array();
    foreach ($raw as $field => $list) {
        $aliases[$field] = array();
        foreach ($list as $a) {
            $aliases[$field][product_excel_canonical($a)] = true;
        }
    }
    return $aliases;
}

/**
 * تبدیل مقدار «نوع» به مقدار داخلی دیتابیس (محصول/خدمت)
 * @return string|null 'product' | 'service' | null (نامعتبر)
 */
function product_excel_parse_type($value)
{
    $c = product_excel_canonical($value);
    switch ($c) {
        case 'product':
        case 'محصول':
        case 'کالا':
            return 'product';
        case 'service':
        case 'خدمت':
        case 'خدمات':
            return 'service';
    }
    return null;
}

/**
 * واحدهای مجاز (بر اساس فرم فعلی کالا)
 */
function product_excel_allowed_units()
{
    return array('عدد', 'بسته', 'قرص');
}

/**
 * نرمال‌سازی رشته عددی: تبدیل ارقام فارسی، حذف جداکننده هزارگان
 *
 * @return array ['ok'=>bool, 'value'=>string|null, 'negative'=>bool, 'too_large'=>bool]
 */
function product_excel_normalize_number($value)
{
    $s = trim((string) ($value === null ? '' : $value));
    if ($s === '') {
        return array('ok' => true, 'value' => '0', 'negative' => false, 'too_large' => false);
    }
    $s = product_excel_to_latin_digits($s);
    $s = str_replace(array(',', '٬', '،'), '', $s);   // جداکننده هزارگان
    $s = preg_replace('/\s+/u', '', $s);
    if (!is_numeric($s)) {
        return array('ok' => false, 'value' => null, 'negative' => false, 'too_large' => false);
    }
    $f = (float) $s;
    if ($f < 0) {
        return array('ok' => false, 'value' => null, 'negative' => true, 'too_large' => false);
    }
    if ($f > 999999999999.99) {
        return array('ok' => false, 'value' => null, 'negative' => false, 'too_large' => true);
    }
    $out = number_format($f, 2, '.', '');
    $out = rtrim(rtrim($out, '0'), '.');
    if ($out === '' || $out === '-') {
        $out = '0';
    }
    return array('ok' => true, 'value' => $out, 'negative' => false, 'too_large' => false);
}
/**
 * تبدیل رشته به UTF-8 معتبر (برای جلوگیری از خطای json_encode روی داده‌های خراب)
 */
function product_excel_safe_utf8($s)
{
    $s = (string) $s;
    if (function_exists('iconv')) {
        $c = @iconv('UTF-8', 'UTF-8//IGNORE', $s);
        if ($c !== false) {
            return $c;
        }
    }
    return $s;
}

/**
 * پیدا کردن سطر هدر در بین ردیف‌های خوانده‌شده
 *
 * @param array $rows خروجی ProductExcelReader::readFile
 * @return array ['found'=>bool, 'index'=>int, 'row_number'=>int]
 */
function product_excel_find_header_row(array $rows)
{
    $aliases = product_excel_header_aliases();
    foreach ($rows as $idx => $row) {
        $cells = isset($row['cells']) ? $row['cells'] : array();
        $hasCode = false;
        $hasName = false;
        foreach ($cells as $v) {
            $c = product_excel_canonical($v);
            if (isset($aliases['code'][$c])) {
                $hasCode = true;
            }
            if (isset($aliases['name'][$c])) {
                $hasName = true;
            }
        }
        if ($hasCode && $hasName) {
            return array('found' => true, 'index' => $idx, 'row_number' => (int) $row['row']);
        }
    }
    return array('found' => false, 'index' => -1, 'row_number' => 0);
}

/**
 * ساخت نگاشت ستون ← فیلد و تشخیص هدرهای ضروری
 *
 * @return array{map: array, missing: string[], found: array}
 */
function product_excel_build_mapping(array $headerCells)
{
    $aliases = product_excel_header_aliases();
    $map = array();
    $found = array();
    foreach ($aliases as $field => $aliasSet) {
        $found[$field] = false;
    }
    foreach ($headerCells as $col => $v) {
        $c = product_excel_canonical($v);
        foreach ($aliases as $field => $aliasSet) {
            if (isset($aliasSet[$c]) && !$found[$field]) {
                $map[$field] = (int) $col;
                $found[$field] = true;
                break;
            }
        }
    }
    $required = array('code', 'name');
    $missing = array();
    foreach ($required as $f) {
        if (!$found[$f]) {
            $missing[] = $f;
        }
    }
    return array('map' => $map, 'missing' => $missing, 'found' => $found);
}

/**
 * خواندن مقدار یک فیلد از ردیف بر اساس نقشه ستون‌ها
 */
function product_excel_cell_value(array $cells, array $map, $field)
{
    if (!isset($map[$field])) {
        return '';
    }
    $col = $map[$field];
    return isset($cells[$col]) ? $cells[$col] : '';
}

/**
 * پردازش کامل فایل: یافتن هدر، ساخت نگاشت، اعتبارسنجی رکوردها
 *
 * @param array $readerRows      خروجی ProductExcelReader::readFile
 * @param array $categoryByName  map: canonical(name) => id
 * @param array $existingCodes   map: canonical(code) => true
 * @param int   $maxRows         حداکثر تعداد ردیف داده
 *
 * @return array
 */
function product_excel_process_file(array $readerRows, array $categoryByName, array $existingCodes, $maxRows = 2000)
{
    $header = product_excel_find_header_row($readerRows);
    if (!$header['found']) {
        throw new ProductExcelReaderException(
            'ردیف هدر (شامل «کد کالا» و «نام کالا») در فایل پیدا نشد. ردیف اول فایل باید عنوان ستون‌ها باشد.'
        );
    }

    $mapping = product_excel_build_mapping($readerRows[$header['index']]['cells']);
    if (!empty($mapping['missing'])) {
        $labels = array('code' => 'کد کالا', 'name' => 'نام کالا');
        $parts = array();
        foreach ($mapping['missing'] as $f) {
            $parts[] = isset($labels[$f]) ? $labels[$f] : $f;
        }
        throw new ProductExcelReaderException(
            'عنوان ستون ضروری در فایل وجود ندارد: ' . implode('، ', $parts)
        );
    }

    $dataRows = array_slice($readerRows, $header['index'] + 1);
    // حذف ردیف‌های کاملاً خالی
    $dataRows = array_values(array_filter($dataRows, function ($row) {
        foreach ($row['cells'] as $v) {
            if (trim((string) $v) !== '') {
                return true;
            }
        }
        return false;
    }));

    if (count($dataRows) === 0) {
        throw new ProductExcelReaderException('فایل پس از هدر، هیچ ردیف داده‌ای ندارد.');
    }
    if (count($dataRows) > $maxRows) {
        throw new ProductExcelReaderException(
            'تعداد ردیف‌های داده (' . count($dataRows) . ') از حد مجاز (' . $maxRows . ') بیشتر است.'
        );
    }

    return product_excel_validate_rows($dataRows, $mapping['map'], $categoryByName, $existingCodes);
}
/**
 * اعتبارسنجی ردیف‌های داده و ساخت پیش‌نمایش.
 *
 * @param array $dataRows        ردیف‌های بعد از هدر (با شماره سطر اکسل)
 * @param array $map             نگاشت ستون ← فیلد
 * @param array $categoryByName  map: canonical(name) => id
 * @param array $existingCodes   map: canonical(code) => true
 *
 * @return array{
 *   total: int, valid: int, invalid: int,
 *   records: array, errors: string[], truncated: bool
 * }
 */
function product_excel_validate_rows(array $dataRows, array $map, array $categoryByName, array $existingCodes)
{
    $allowedUnits = product_excel_allowed_units();
    $unitCanon = array();
    foreach ($allowedUnits as $u) {
        $unitCanon[product_excel_canonical($u)] = $u;
    }

    $records = array();
    $allErrors = array();
    $seenCodes = array();
    $previewLimit = 500;

    foreach ($dataRows as $row) {
        $cells = isset($row['cells']) ? $row['cells'] : array();
        $rowNo = (int) $row['row'];
        $errors = array();

        // ---- کد کالا ----
        $code = trim(product_excel_cell_value($cells, $map, 'code'));
        $code = product_excel_to_latin_digits($code);
        $codeKey = product_excel_canonical($code);
        if ($code === '') {
            $errors[] = 'کد کالا اجباری است.';
        } elseif (mb_strlen($code) > 50) {
            $errors[] = 'کد کالا حداکثر ۵۰ کاراکتر است.';
        } else {
            if (isset($seenCodes[$codeKey])) {
                $errors[] = 'کد کالا «' . $code . '» در فایل تکراری است (ردیف قبلی: ' . $seenCodes[$codeKey] . ').';
            } elseif (isset($existingCodes[$codeKey])) {
                $errors[] = 'کد کالا «' . $code . '» قبلاً در دیتابیس ثبت شده است.';
            } else {
                $seenCodes[$codeKey] = $rowNo;
            }
        }

        // ---- نام کالا ----
        $name = trim(product_excel_cell_value($cells, $map, 'name'));
        if ($name === '') {
            $errors[] = 'نام کالا اجباری است.';
        } elseif (mb_strlen($name) > 150) {
            $errors[] = 'نام کالا حداکثر ۱۵۰ کاراکتر است.';
        }

        // ---- دسته‌بندی ----
        $categoryRaw = trim(product_excel_cell_value($cells, $map, 'category'));
        $categoryId = null;
        $categoryDisplay = '';
        if ($categoryRaw !== '' && $categoryRaw !== '-' && $categoryRaw !== '—') {
            $catKey = product_excel_canonical($categoryRaw);
            if (isset($categoryByName[$catKey])) {
                $categoryId = (int) $categoryByName[$catKey];
                $categoryDisplay = $categoryRaw;
            } else {
                $errors[] = 'دسته‌بندی «' . $categoryRaw . '» در سیستم وجود ندارد.';
            }
        }

        // ---- نوع ----
        $typeRaw = trim(product_excel_cell_value($cells, $map, 'type'));
        $type = 'product';
        if ($typeRaw !== '') {
            $parsedType = product_excel_parse_type($typeRaw);
            if ($parsedType === null) {
                $errors[] = 'نوع «' . $typeRaw . '» مجاز نیست (فقط «محصول» یا «خدمت»).';
            } else {
                $type = $parsedType;
            }
        }

        // ---- واحد ----
        $unitRaw = trim(product_excel_cell_value($cells, $map, 'unit'));
        $unit = 'عدد';
        if ($unitRaw !== '') {
            $uKey = product_excel_canonical($unitRaw);
            if (isset($unitCanon[$uKey])) {
                $unit = $unitCanon[$uKey];
            } else {
                $errors[] = 'واحد «' . $unitRaw . '» مجاز نیست (مقادیر مجاز: عدد، بسته، قرص).';
            }
        }

        // ---- اعداد ----
        $numbers = array(
            'purchase_price' => 'قیمت خرید',
            'sale_price'     => 'قیمت فروش',
            'stock'          => 'موجودی',
            'min_stock'      => 'حداقل موجودی',
        );
        $data = array(
            'code'          => $code,
            'name'          => $name,
            'category_id'   => $categoryId,
            'category_name' => $categoryDisplay,
            'type'          => $type,
            'unit'          => $unit,
            'purchase_price' => '0',
            'sale_price'     => '0',
            'stock'          => '0',
            'min_stock'      => '0',
            'description'    => '',
        );
        foreach ($numbers as $field => $label) {
            $num = product_excel_normalize_number(product_excel_cell_value($cells, $map, $field));
            if (!$num['ok']) {
                if ($num['negative']) {
                    $errors[] = $label . ' باید عدد غیرمنفی باشد.';
                } elseif ($num['too_large']) {
                    $errors[] = $label . ' از حد مجاز (۱۲ رقم) بیشتر است.';
                } else {
                    $errors[] = $label . ' باید عدد معتبر باشد.';
                }
            } else {
                $data[$field] = $num['value'];
            }
        }

        // ---- توضیحات ----
        $data['description'] = trim(product_excel_cell_value($cells, $map, 'description'));

        $status = empty($errors) ? 'ok' : 'error';
        $record = array(
            'row'    => $rowNo,
            'status' => $status,
            'errors' => $errors,
            'data'   => $data,
        );
        $records[] = $record;

        foreach ($errors as $e) {
            $allErrors[] = 'ردیف ' . $rowNo . ': ' . $e;
        }
    }

    $valid = 0;
    $invalid = 0;
    foreach ($records as $r) {
        if ($r['status'] === 'ok') {
            $valid++;
        } else {
            $invalid++;
        }
    }

    $truncated = count($records) > $previewLimit;
    return array(
        'total'     => count($records),
        'valid'     => $valid,
        'invalid'   => $invalid,
        'records'   => $truncated ? array_slice($records, 0, $previewLimit) : $records,
        'errors'    => $allErrors,
        'truncated' => $truncated,
    );
}
/**
 * دریافت نگاشت نام دسته‌بندی ← id از دیتابیس
 */
function product_excel_category_map($mysqli)
{
    $map = array();
    $result = $mysqli->query('SELECT id, name FROM product_categories');
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $key = product_excel_canonical($row['name']);
            if (!isset($map[$key])) {
                $map[$key] = (int) $row['id'];
            }
        }
        $result->free();
    }
    return $map;
}

/**
 * دریافت مجموعه کدهای موجود در دیتابیس (کلید: canonical code)
 */
function product_excel_existing_codes($mysqli)
{
    $codes = array();
    $result = $mysqli->query('SELECT code FROM products');
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $codes[product_excel_canonical($row['code'])] = true;
        }
        $result->free();
    }
    return $codes;
}

/**
 * ثبت نهایی رکوردهای معتبر داخل یک transaction.
 *
 * @param mysqli $mysqli  اتصال دیتابیس
 * @param array  $records خروجی product_excel_validate_rows
 *
 * @return array ['inserted'=>int, 'rejected'=>int]
 * @throws Exception در خطای غیرمنتظره (روی transaction rollback می‌شود)
 */
function product_excel_insert_rows($mysqli, array $records)
{
    $inserted = 0;
    $rejected = 0;

    if (count($records) === 0) {
        return array('inserted' => 0, 'rejected' => 0);
    }

    if (!$mysqli->begin_transaction()) {
        throw new Exception('شروع transaction انجام نشد. هیچ رکوردی ثبت نشد.');
    }

    try {
        foreach ($records as $r) {
            if ($r['status'] !== 'ok') {
                $rejected++;
                continue;
            }
            $d = $r['data'];

            $pp = (float) $d['purchase_price'];
            $sp = (float) $d['sale_price'];
            $st = (float) $d['stock'];
            $ms = (float) $d['min_stock'];

            if ($d['category_id'] === null) {
                $sql = 'INSERT INTO products (code, name, type, unit, purchase_price, sale_price, stock, min_stock, description)
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)';
                $stmt = $mysqli->prepare($sql);
                $stmt->bind_param('ssssdddds', $d['code'], $d['name'], $d['type'],
                    $d['unit'], $pp, $sp, $st, $ms, $d['description']);
            } else {
                $sql = 'INSERT INTO products (code, name, category_id, type, unit, purchase_price, sale_price, stock, min_stock, description)
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)';
                $stmt = $mysqli->prepare($sql);
                $stmt->bind_param('ssissdddds', $d['code'], $d['name'], $d['category_id'], $d['type'],
                    $d['unit'], $pp, $sp, $st, $ms, $d['description']);
            }
            if ($stmt->execute()) {
                $inserted++;
            } else {
                throw new Exception('خطا در ثبت کالا (ردیف ' . $r['row'] . '): ' . $stmt->error);
            }
            $stmt->close();
        }
        $mysqli->commit();
    } catch (Throwable $e) {
        if (method_exists($mysqli, 'rollback')) {
            @$mysqli->rollback();
        }
        throw $e;
    }

    return array('inserted' => $inserted, 'rejected' => $rejected);
}