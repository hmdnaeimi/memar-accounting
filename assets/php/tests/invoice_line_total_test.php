<?php
/**
 * Test for calculateLineTotal() — per-unit discount must scale with quantity.
 * Run: C:\laragon\bin\php\php-8.3.30-Win32-vs16-x64\php.exe assets/php/tests/invoice_line_total_test.php
 */
require_once __DIR__ . '/../invoice_common.php';

$tests = [
    // [qty, price, discount, expected line_total]
    [3, 45000, 5000, 120000],   // (45000×3) − (5000×3) = 135000 − 15000
    [1, 45000, 5000, 40000],    // qty=1 unchanged behavior
    [3, 45000, 0, 135000],      // zero discount unchanged
    [5, 100000, 10000, 450000], // (100000×5) − (10000×5)
    [2, 100000, 10000, 180000], // (100000×2) − (10000×2)  (Test 5 row 2)
];

$pass = 0;
foreach ($tests as $t) {
    [$qty, $price, $disc, $expected] = $t;
    $got = calculateLineTotal((string) $qty, (string) $price, (string) $disc);
    $ok = bccomp($got, (string) $expected, 2) === 0;
    printf(
        "%s qty=%d price=%d disc=%d → got=%s expected=%s\n",
        $ok ? 'PASS' : 'FAIL',
        $qty, $price, $disc, $got, $expected
    );
    if ($ok) { $pass++; }
}

printf("%d/%d passed\n", $pass, count($tests));
exit($pass === count($tests) ? 0 : 1);