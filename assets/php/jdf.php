<?php
function jdate($format, $timestamp = null)
{
    if ($timestamp === null) {
        $timestamp = time();
    }

    $date = getdate($timestamp);
    list($jy, $jm, $jd) = gregorian_to_jalali($date['year'], $date['mon'], $date['mday']);

    $weekdayNames = [
        'یکشنبه',
        'دوشنبه',
        'سه‌شنبه',
        'چهارشنبه',
        'پنجشنبه',
        'جمعه',
        'شنبه'
    ];

    $monthNames = [
        1 => 'فروردین',
        2 => 'اردیبهشت',
        3 => 'خرداد',
        4 => 'تیر',
        5 => 'مرداد',
        6 => 'شهریور',
        7 => 'مهر',
        8 => 'آبان',
        9 => 'آذر',
        10 => 'دی',
        11 => 'بهمن',
        12 => 'اسفند'
    ];

    $weekday = $date['wday'];
    $formatted = '';
    $len = strlen($format);

    for ($i = 0; $i < $len; $i++) {
        $char = $format[$i];
        switch ($char) {
            case 'l':
                $formatted .= $weekdayNames[$weekday];
                break;
            case 'j':
                $formatted .= convertToPersianNumber($jd);
                break;
            case 'F':
                $formatted .= $monthNames[$jm];
                break;
            case 'Y':
                $formatted .= convertToPersianNumber($jy);
                break;
            default:
                $formatted .= $char;
                break;
        }
    }

    return $formatted;
}

function convertToPersianNumber($number)
{
    $persian = ['۰', '۱', '۲', '۳', '۴', '۵', '۶', '۷', '۸', '۹'];
    return str_replace(range(0, 9), $persian, (string)$number);
}

function gregorian_to_jalali($g_y, $g_m, $g_d)
{
    $gy = $g_y - 1600;
    $gm = $g_m - 1;
    $gd = $g_d - 1;

    $g_day_no = 365 * $gy + (int)(($gy + 3) / 4) - (int)(($gy + 99) / 100) + (int)(($gy + 399) / 400);
    for ($i = 0; $i < $gm; ++$i) {
        $g_day_no += [31, 28, 31, 30, 31, 30, 31, 31, 30, 31, 30, 31][$i];
    }
    if ($gm > 1 && (($g_y % 4 == 0 && $g_y % 100 != 0) || ($g_y % 400 == 0))) {
        $g_day_no++;
    }
    $g_day_no += $gd;

    $j_day_no = $g_day_no - 79;
    $j_np = (int)($j_day_no / 12053);
    $j_day_no %= 12053;
    $jy = 979 + 33 * $j_np + 4 * (int)($j_day_no / 1461);
    $j_day_no %= 1461;

    if ($j_day_no >= 366) {
        $jy += (int)(($j_day_no - 1) / 365);
        $j_day_no = ($j_day_no - 1) % 365;
    }

    for ($i = 0; $i < 11 && $j_day_no >= ([31, 31, 31, 31, 31, 31, 30, 30, 30, 30, 30, 29][$i]); ++$i) {
        $j_day_no -= ([31, 31, 31, 31, 31, 31, 30, 30, 30, 30, 30, 29][$i]);
    }
    $jm = $i + 1;
    $jd = $j_day_no + 1;

    return [$jy, $jm, $jd];
}
