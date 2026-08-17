<?php
/**
 * product_import.php
 *
 * Endpoint «ورود از اکسل» برای کالاها و خدمات.
 *
 * اقدام‌ها:
 *   - preview : خواندن فایل، سردار هدر، نرمال‌سازی و اعتبارسنجی کامل
 *               (هیچ Insert انجام نمی‌شود)
 *   - import  : خواندن مجدد همان فایل، اعتبارسنجی نهایی سمت سرور و ثبت
 *               رکوردهای معتبر داخل transaction
 *
 * امنیت:
 *   - boot.php + require_csrf_or_fail()
 *   - محدودیت نوع (xlsx/xls) و حجم
 *   - کپی به فایل موقت با نام تصادفی و حذف پس از پردازش
 *   - خروجی JSON با respond_json()/respond_error()
 */

require_once __DIR__ . '/boot.php';
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/product_excel_reader.php';
require_once __DIR__ . '/product_excel_lib.php';

require_csrf_or_fail();

header('Content-Type: application/json; charset=UTF-8');

/* ---------- مقادیر و محدودیت‌ها ---------- */
const PRODUCT_IMPORT_MAX_BYTES = 5 * 1024 * 1024; // ۵ مگابایت
const PRODUCT_IMPORT_MAX_ROWS  = 2000;            // حداکثر سطر داده
$allowedExts = array('xlsx', 'xls');

$action = trim($_POST['action'] ?? '');
if ($action !== 'preview' && $action !== 'import') {
    respond_error('عملیات نامعتبر است. (فقط preview یا import مجاز است.)');
}

/* ---------- اعتبارسنجی فایل آپلودی ---------- */
$uploads = isset($_FILES['file']) && is_array($_FILES['file']) ? $_FILES['file'] : null;
if (!$uploads || (isset($uploads['error']) && (int) $uploads['error'] === UPLOAD_ERR_NO_FILE)) {
    respond_error('فایلی انتخاب نشده است.');
}
$uploadError = (int) ($uploads['error'] ?? UPLOAD_ERR_OK);
if ($uploadError !== UPLOAD_ERR_OK) {
    if ($uploadError === UPLOAD_ERR_INI_SIZE || $uploadError === UPLOAD_ERR_FORM_SIZE) {
        respond_error('حجم فایل بیش از حد مجاز است (حداکثر ' . (int) (PRODUCT_IMPORT_MAX_BYTES / 1024 / 1024) . ' مگابایت).');
    }
    respond_error('بارگذاری فایل با خطا مواجه شد (کد ' . $uploadError . ').');
}

$origName = (string) ($uploads['name'] ?? '');
$ext = strtolower(pathinfo($origName, PATHINFO_EXTENSION));
if (!in_array($ext, $allowedExts, true)) {
    respond_error('فقط فایل‌های .xlsx و .xls پذیرفته می‌شوند.');
}

$size = (int) ($uploads['size'] ?? 0);
if ($size <= 0) {
    respond_error('فایل انتخاب‌شده خالی است.');
}
if ($size > PRODUCT_IMPORT_MAX_BYTES) {
    respond_error('حجم فایل بیشتر از حد مجاز (' . (int) (PRODUCT_IMPORT_MAX_BYTES / 1024 / 1024) . ' مگابایت) است.');
}

$src = $uploads['tmp_name'];
if (!is_string($src) || !is_file($src)) {
    respond_error('فایل موقت آپلود در دسترس نیست.');
}

/* ---------- کپی به فایل موقت با نام تصادفی ---------- */
$tmpFile = @tempnam(sys_get_temp_dir(), 'pimx_');
if ($tmpFile === false) {
    respond_error('امکان ساخت فایل موقت وجود ندارد.');
}
// اطمینان از حذف فایل موقت حتی در صورت خروج با exit/exception
register_shutdown_function(function () use ($tmpFile) {
    if (is_string($tmpFile) && is_file($tmpFile)) {
        @unlink($tmpFile);
    }
});
$copied = @copy($src, $tmpFile);
if (!$copied) {
    @unlink($tmpFile);
    respond_error('امکان ذخیره فایل موقت وجود نداشت.');
}

/* ---------- پردازش با پاکسازی قطعی فایل موقت ---------- */
try {
    $readerRows = ProductExcelReader::readFile($tmpFile);   // ممکن است استثنا پرتاب کند

    if ($action === 'preview') {
        $categoryByName = product_excel_category_map($mysqli);
        $existingCodes  = product_excel_existing_codes($mysqli);
        $result = product_excel_process_file($readerRows, $categoryByName, $existingCodes, PRODUCT_IMPORT_MAX_ROWS);

        respond_json(true, 'فایل با موفقیت خوانده و اعتبارسنجی شد.', array(
            'file_name'  => $origName,
            'total'      => $result['total'],
            'valid'      => $result['valid'],
            'invalid'    => $result['invalid'],
            'records'    => $result['records'],
            'errors'     => $result['errors'],
            'truncated'  => $result['truncated'],
            'header_cols'=> array_keys($result['records'][0]['data'] ?? array('code')),
        ));
    }

    // ---- import ----
    $categoryByName = product_excel_category_map($mysqli);
    $existingCodes  = product_excel_existing_codes($mysqli);
    $result = product_excel_process_file($readerRows, $categoryByName, $existingCodes, PRODUCT_IMPORT_MAX_ROWS);

    if ($result['valid'] === 0) {
        respond_error('هیچ رکورد معتبری در فایل وجود ندارد؛ ثبت انجام نشد.', 422);
    }

    $done = product_excel_insert_rows($mysqli, $result['records']);

    respond_json(true, 'ثبت کالاها و خدمات با موفقیت انجام شد.', array(
        'file_name' => $origName,
        'total'     => $result['total'],
        'inserted'  => $done['inserted'],
        'rejected'  => $result['invalid'] + $done['rejected'],
    ));
} catch (ProductExcelReaderException $e) {
    respond_error($e->getMessage());
} catch (Exception $e) {
    respond_error('خطا در ثبت کالاها: ' . $e->getMessage(), 500);
} catch (Throwable $e) {
    respond_error('خطای پیش‌بینی‌نشده در پردازش: ' . $e->getMessage(), 500);
} finally {
    if (is_file($tmpFile)) {
        @unlink($tmpFile);
    }
}

/* این بخش هرگز اجرا نمی‌شود (respond_json خروج می‌کند) */
exit;