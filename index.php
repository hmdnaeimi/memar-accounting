<?php
require_once __DIR__ . '/assets/php/boot.php'; // session_start + توابع CSRF قبل از هر خروجی
$pages = [
    'dashboard' => 'داشبورد',
    'factors' => 'فاکتورها',
    'purchase-invoice' => 'فاکتور خرید',
    'sales-return' => 'برگشت فروش',
    'product-categories' => 'دسته‌بندی کالاها',
    'goods-and-services' => 'کالاها و خدمات',
    'customers' => 'مشتریان',
    'suppliers' => 'تامین‌کنندگان',
    'report-inventory' => 'گزارش موجودی کالاها',
    'report-customer-debt' => 'گزارش بدهی مشتریان',
    'report-supplier-debt' => 'گزارش بدهی به تامین‌کنندگان',
    'settings' => 'تنظیمات',
    'notes' => 'یادداشت‌ها',
    'about-us' => 'درباره ما',
];
$page = $_GET['page'] ?? 'dashboard';
if (!array_key_exists($page, $pages)) {
    $page = 'dashboard';
}
?>
<!DOCTYPE html>
<html lang="fa" dir="rtl">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>کوشا حساب | <?php echo $pages[$page]; ?></title>
    <link rel="stylesheet" href="assets/css/fontawesome.css">
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="assets/css/kamadatepicker.css">
    <script src="assets/js/jquery.min.js"></script>
    <script src="assets/js/kamadatepicker.js"></script>
    <script src="assets/js/main.js" defer></script>
    <script src="assets/js/invoice.js" defer></script>
</head>

<body id="<?php echo $page; ?>">
    <?php
    date_default_timezone_set('Asia/Tehran');
    require_once __DIR__ . '/assets/php/jdf.php';
    $shamsiDate = jdate('l، j F Y');
    $navIcons = [
        'dashboard' => 'fa-chart-pie',
        'factors' => 'fa-file-invoice-dollar',
        'purchase-invoice' => 'fa-cart-plus',
        'sales-return' => 'fa-undo',
        'product-categories' => 'fa-sitemap',
        'goods-and-services' => 'fa-box-open',
        'customers' => 'fa-users',
        'suppliers' => 'fa-truck',
        'report-inventory' => 'fa-boxes-stacked',
        'report-customer-debt' => 'fa-user-clock',
        'report-supplier-debt' => 'fa-truck-field',
        'settings' => 'fa-cog',
        'notes' => 'fa-sticky-note',
        'about-us' => 'fa-info-circle',
    ];
    ?>
    <div class="app-shell">
        <aside class="sidebar">
            <div class="brand-panel">
                <div class="brand-title">کوشا حساب</div>
                <div class="brand-subtitle">حسابداری ساده</div>
            </div>
            <nav class="main-nav">
                <?php
                /* فاکتور خرید و برگشت فروش فقط زیرمنوی «فاکتورها» هستند */
                /* زیرمنوهای گزارشات (به‌جز اولین که منوی «گزارشات» را باز می‌کند) در نوار اصلی نمایش داده نمی‌شوند */
                $factorsChildren = ['factors', 'purchase-invoice', 'sales-return'];
                $reportsChildren = ['report-inventory', 'report-customer-debt', 'report-supplier-debt'];
                foreach ($pages as $key => $label):
                    if (in_array($key, ['purchase-invoice', 'sales-return', 'report-customer-debt', 'report-supplier-debt'], true)) {
                        continue;
                    }
                    $iconClass = $navIcons[$key] ?? 'fa-circle';
                ?>
                    <?php if ($key === 'factors'): ?>
                        <?php $factorsActive = in_array($page, $factorsChildren, true); ?>
                        <div class="nav-dropdown<?php echo $factorsActive ? ' open' : ''; ?>">
                            <a href="?page=factors" class="nav-link nav-dropdown-toggle<?php echo $factorsActive ? ' active' : ''; ?>">
                                <span class="nav-icon"><i class="fas fa-file-invoice-dollar"></i></span>
                                <span class="nav-text">فاکتورها</span>
                                <span class="nav-caret"><i class="fas fa-chevron-down"></i></span>
                            </a>
                            <div class="nav-dropdown-menu">
                                <a href="?page=factors" class="nav-sub-link<?php echo $page === 'factors' ? ' active' : ''; ?>">فاکتور فروش</a>
                                <a href="?page=purchase-invoice" class="nav-sub-link<?php echo $page === 'purchase-invoice' ? ' active' : ''; ?>">فاکتور خرید</a>
                                <a href="?page=sales-return" class="nav-sub-link<?php echo $page === 'sales-return' ? ' active' : ''; ?>">برگشت فروش</a>
                            </div>
                        </div>
                    <?php elseif ($key === 'report-inventory'): ?>
                        <?php $reportsActive = in_array($page, $reportsChildren, true); ?>
                        <div class="nav-dropdown<?php echo $reportsActive ? ' open' : ''; ?>">
                            <a href="?page=report-inventory" class="nav-link nav-dropdown-toggle<?php echo $reportsActive ? ' active' : ''; ?>">
                                <span class="nav-icon"><i class="fas fa-chart-bar"></i></span>
                                <span class="nav-text">گزارشات</span>
                                <span class="nav-caret"><i class="fas fa-chevron-down"></i></span>
                            </a>
                            <div class="nav-dropdown-menu">
                                <a href="?page=report-inventory" class="nav-sub-link<?php echo $page === 'report-inventory' ? ' active' : ''; ?>">گزارش موجودی کالاها</a>
                                <a href="?page=report-customer-debt" class="nav-sub-link<?php echo $page === 'report-customer-debt' ? ' active' : ''; ?>">گزارش بدهی مشتریان</a>
                                <a href="?page=report-supplier-debt" class="nav-sub-link<?php echo $page === 'report-supplier-debt' ? ' active' : ''; ?>">گزارش بدهی به تامین‌کنندگان</a>
                            </div>
                        </div>
                    <?php else: ?>
                        <a href="?page=<?php echo $key; ?>" class="nav-link<?php echo $page === $key ? ' active' : ''; ?>">
                            <span class="nav-icon"><i class="fas <?php echo $iconClass; ?>"></i></span>
                            <span class="nav-text"><?php echo $label; ?></span>
                        </a>
                    <?php endif; ?>
                <?php endforeach; ?>
            </nav>

        </aside>
        <main class="main-content">
            <header class="page-header">
                <div>
                    <h1><?php echo $pages[$page]; ?></h1>
                </div>
                <div class="page-meta"><?php echo $shamsiDate; ?></div>
            </header>
            <section class="page-body">
                <?php include __DIR__ . '/pages/' . $page . '.php'; ?>
            </section>
        </main>
    </div>
</body>

</html>