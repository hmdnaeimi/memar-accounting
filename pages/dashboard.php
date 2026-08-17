<?php
require_once __DIR__ . '/../assets/php/db.php';
require_once __DIR__ . '/../assets/php/jdf.php';

// ۱. شمارش تعداد مشتریان
$customersCount = 0;
$result = $mysqli->query('SELECT COUNT(*) AS total FROM customers');
if ($result) {
    $row = $result->fetch_assoc();
    $customersCount = (int) $row['total'];
    $result->free();
}

// ۲. شمارش تعداد کالاها
$productsCount = 0;
$result = $mysqli->query('SELECT COUNT(*) AS total FROM products');
if ($result) {
    $row = $result->fetch_assoc();
    $productsCount = (int) $row['total'];
    $result->free();
}
/*
 * آخرین ۵ فاکتور فروش قطعی
 *
 * sales_invoice = فاکتور فروش قطعی
 * sales_proforma = پیش فاکتور و عمداً در این لیست قرار نمی‌گیرد
 */
$latestSalesInvoices = [];

$sql = "
    SELECT
        i.id,
        i.invoice_number,
        i.invoice_date,
        i.payment_status,
        i.payable_amount,
        CONCAT(c.first_name, ' ', c.last_name) AS customer_name
    FROM invoices AS i
    INNER JOIN customers AS c ON c.id = i.customer_id
    WHERE i.type = 'sales_invoice'
    ORDER BY i.id DESC
    LIMIT 5
";

$result = $mysqli->query($sql);

if ($result) {
    while ($row = $result->fetch_assoc()) {
        $latestSalesInvoices[] = $row;
    }

    $result->free();
}

// ۳. محاسبه فروش امروز (فقط فاکتورهای فروش قطعی)
$todaySales = 0;
$queryToday = "SELECT COALESCE(SUM(payable_amount), 0) AS total FROM invoices WHERE type = 'sales_invoice' AND invoice_date = CURDATE()";
$result = $mysqli->query($queryToday);
if ($result) {
    $row = $result->fetch_assoc();
    $todaySales = (float) $row['total'];
    $result->free();
}

// ۴. محاسبه فروش ماهانه (فقط فاکتورهای فروش قطعی در ماه جاری)
$monthlySales = 0;
$queryMonthly = "SELECT COALESCE(SUM(payable_amount), 0) AS total FROM invoices WHERE type = 'sales_invoice' AND YEAR(invoice_date) = YEAR(CURDATE()) AND MONTH(invoice_date) = MONTH(CURDATE())";
$result = $mysqli->query($queryMonthly);
if ($result) {
    $row = $result->fetch_assoc();
    $monthlySales = (float) $row['total'];
    $result->free();
}


// ═══════════════════════════════════════════════════════════════════
//  بخش جدید: محاسبه داده‌های نمودار فروش ماهانه (۳۰ روز گذشته)
//  فقط فاکتورهای فروش قطعی (sales_invoice)
// ═══════════════════════════════════════════════════════════════════
$salesByDay   = [];
$chartLabels  = [];
$chartData    = [];
$hasSalesData = false;

// ساخت آرایه ۳۰ روز گذشته (شامل امروز) با مقدار اولیه صفر
for ($i = 29; $i >= 0; $i--) {
    $date = date('Y-m-d', strtotime("-{$i} days"));
    $salesByDay[$date] = '0.00';
}

// کوئری: فقط sales_invoice و فقط ۳۰ روز اخیر
$sql = "SELECT invoice_date, SUM(payable_amount) AS total 
        FROM invoices 
        WHERE type = 'sales_invoice' 
          AND invoice_date >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
        GROUP BY invoice_date 
        ORDER BY invoice_date ASC";
$result = $mysqli->query($sql);
if ($result) {
    while ($row = $result->fetch_assoc()) {
        if (isset($salesByDay[$row['invoice_date']])) {
            $salesByDay[$row['invoice_date']] = $row['total'];
        }
    }
    $result->free();
}

// تبدیل تاریخ‌های میلادی به شمسی برای نمایش روی محور X
foreach ($salesByDay as $date => $amount) {
    $floatAmount = (float) $amount;
    if ($floatAmount > 0) {
        $hasSalesData = true;
    }
    list($y, $m, $d) = explode('-', $date);
    list($jy, $jm, $jd) = gregorian_to_jalali((int)$y, (int)$m, (int)$d);
    $chartLabels[] = convertToPersianNumber($jy) . '/' .
        convertToPersianNumber(str_pad($jm, 2, '0', STR_PAD_LEFT)) . '/' .
        convertToPersianNumber(str_pad($jd, 2, '0', STR_PAD_LEFT));
    $chartData[]   = $floatAmount;
}
// ═══════════════════════════════════════════════════════════════════
//  پایان بخش محاسبه نمودار
// ═══════════════════════════════════════════════════════════════════
?>
<div class="cards-row">
    <div class="card metric-card">
        <p class="metric-title">فروش امروز</p>
        <div class="metric-value"><span><?php echo number_format($todaySales); ?> ریال</span><span class="metric-badge">$</span></div>
    </div>
    <div class="card metric-card">
        <p class="metric-title">فروش ماهانه</p>
        <div class="metric-value metric-positive"><span><?php echo number_format($monthlySales); ?> ریال</span><span class="metric-badge">↗</span></div>
    </div>
    <div class="card metric-card">
        <p class="metric-title">تعداد کالا</p>
        <div class="metric-value"><span><?php echo number_format($productsCount); ?></span><span class="metric-badge">🛒</span></div>
    </div>
    <div class="card metric-card">
        <p class="metric-title">تعداد مشتریان</p>
        <div class="metric-value"><span><?php echo number_format($customersCount); ?></span><span class="metric-badge">👥</span></div>
    </div>
</div>

<div class="panel-row">
    <div class="card panel">
        <h2>نمودار فروش ماهانه</h2>
        <?php if ($hasSalesData): ?>
            <div style="position: relative; height: 380px; width: 100%; padding: 10px 4px;">
                <canvas id="monthlySalesChart"></canvas>
            </div>
            <!-- Chart.js از CDN -->
            <script src="assets/js/chart.umd.min.js"></script>
            <script>
                (function() {
                    const labels = <?php echo json_encode($chartLabels, JSON_UNESCAPED_UNICODE); ?>;
                    const data = <?php echo json_encode($chartData,   JSON_UNESCAPED_UNICODE); ?>;

                    // تبدیل اعداد انگلیسی به فارسی
                    function toPersianNum(num) {
                        const persian = ['۰', '۱', '۲', '۳', '۴', '۵', '۶', '۷', '۸', '۹'];
                        return String(num).replace(/\d/g, d => persian[d]);
                    }

                    function formatMoney(n) {
                        return toPersianNum(Math.round(n).toLocaleString('en-US')) + ' ریال';
                    }

                    // فشرده‌سازی اعداد بزرگ برای محور Y
                    function formatCompact(n) {
                        if (n >= 1_000_000_000) return toPersianNum((n / 1_000_000_000).toFixed(1)) + ' میلیارد';
                        if (n >= 1_000_000) return toPersianNum((n / 1_000_000).toFixed(1)) + ' میلیون';
                        if (n >= 1_000) return toPersianNum((n / 1_000).toFixed(0)) + ' هزار';
                        return toPersianNum(n);
                    }

                    const ctx = document.getElementById('monthlySalesChart').getContext('2d');
                    new Chart(ctx, {
                        type: 'line',
                        data: {
                            labels: labels,
                            datasets: [{
                                label: 'مبلغ فروش',
                                data: data,
                                borderColor: '#1769e0',
                                backgroundColor: 'rgba(23, 105, 224, 0.12)',
                                borderWidth: 2.5,
                                fill: true,
                                tension: 0.35,
                                pointRadius: 4,
                                pointHoverRadius: 7,
                                pointBackgroundColor: '#1769e0',
                                pointBorderColor: '#ffffff',
                                pointBorderWidth: 2
                            }]
                        },
                        options: {
                            responsive: true,
                            maintainAspectRatio: false,
                            interaction: {
                                intersect: false,
                                mode: 'index'
                            },
                            plugins: {
                                legend: {
                                    display: false
                                },
                                tooltip: {
                                    rtl: true,
                                    textDirection: 'rtl',
                                    backgroundColor: '#1f3256',
                                    padding: 12,
                                    cornerRadius: 8,
                                    titleFont: {
                                        family: 'Tahoma, Vazirmatn, sans-serif',
                                        size: 13,
                                        weight: '600'
                                    },
                                    bodyFont: {
                                        family: 'Tahoma, Vazirmatn, sans-serif',
                                        size: 12
                                    },
                                    callbacks: {
                                        title: function(items) {
                                            return 'تاریخ: ' + items[0].label;
                                        },
                                        label: function(context) {
                                            return 'فروش: ' + formatMoney(context.parsed.y);
                                        }
                                    }
                                }
                            },
                            scales: {
                                x: {
                                    ticks: {
                                        font: {
                                            size: 10,
                                            family: 'Tahoma, sans-serif'
                                        },
                                        maxRotation: 45,
                                        autoSkip: true,
                                        maxTicksLimit: 10,
                                        color: '#64748b'
                                    },
                                    grid: {
                                        display: false
                                    }
                                },
                                y: {
                                    beginAtZero: true,
                                    ticks: {
                                        callback: function(value) {
                                            return formatCompact(value);
                                        },
                                        font: {
                                            size: 11,
                                            family: 'Tahoma, sans-serif'
                                        },
                                        color: '#64748b'
                                    },
                                    grid: {
                                        color: 'rgba(0,0,0,0.05)'
                                    }
                                }
                            }
                        }
                    });
                })();
            </script>
        <?php else: ?>
            <div class="empty-state" style="padding: 40px 20px; text-align: center; color: #94a3b8;">
                در ۳۰ روز گذشته فاکتور فروش قطعی ثبت نشده است.
            </div>
        <?php endif; ?>
    </div>
</div>

<div class="table-panel">
    <div class="card panel">
        <h2>کالاهای کم‌موجودی</h2>

        <div class="table-wrapper">
            <table class="action-table dashboard-low-stock-table">
                <thead>
                    <tr>
                        <th>نام کالا</th>
                        <th>موجودی</th>
                    </tr>
                </thead>

                <tbody id="lowStockTableBody">
                    <tr>
                        <td colspan="2" class="empty-state">
                            در حال دریافت اطلاعات...
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <div id="lowStockPagination"></div>
    </div>

    <div class="card panel">
        <h2>آخرین فاکتورها</h2>
        <div class="table-wrapper">
            <table class="action-table">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>مشتری</th>
                        <th>تاریخ</th>
                        <th>وضعیت پرداخت</th>
                        <th>مبلغ کل</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($latestSalesInvoices)): ?>

                        <tr>
                            <td colspan="5" class="empty-state">فاکتور فروش قطعی موجود نیست</td>
                        </tr>

                    <?php else: ?>

                        <?php foreach ($latestSalesInvoices as $index => $invoice): ?>

                            <?php
                            $paymentStatusLabels = [
                                'paid'   => 'پرداخت شده',
                                'unpaid' => 'پرداخت نشده',
                                'partial' => 'پرداخت جزئی',
                            ];

                            $paymentStatus = $paymentStatusLabels[$invoice['payment_status']]
                                ?? $invoice['payment_status'];

                            $invoiceDate = '';

                            if (!empty($invoice['invoice_date'])) {
                                $invoiceDate = jdate(
                                    'Y/m/d',
                                    strtotime($invoice['invoice_date'])
                                );
                            }
                            ?>

                            <tr>
                                <td>
                                    <?php echo $index + 1; ?>
                                </td>

                                <td>
                                    <?php
                                    echo htmlspecialchars(
                                        trim($invoice['customer_name']),
                                        ENT_QUOTES,
                                        'UTF-8'
                                    );
                                    ?>
                                </td>

                                <td>
                                    <?php echo htmlspecialchars($invoiceDate, ENT_QUOTES, 'UTF-8'); ?>
                                </td>

                                <td>
                                    <?php echo htmlspecialchars($paymentStatus, ENT_QUOTES, 'UTF-8'); ?>
                                </td>

                                <td>
                                    <?php
                                    echo number_format(
                                        (float) $invoice['payable_amount']
                                    );
                                    ?>
                                    ریال
                                </td>
                            </tr>

                        <?php endforeach; ?>

                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>