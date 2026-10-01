<?php
/**
 * auth.php — احراز هویت تک‌مدیره برنامه
 *
 * حساب پیش‌فرض:
 * username: memar
 * password: Aa@123456
 *
 * رمز عبور به صورت plaintext ذخیره نمی‌شود؛ مقدار زیر hash شده است.
 */

declare(strict_types=1);

if (session_status() === PHP_SESSION_NONE) {
    $secure = !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off';

    session_set_cookie_params([
        'lifetime' => 0,
        'path' => '/',
        'secure' => $secure,
        'httponly' => true,
        'samesite' => 'Lax',
    ]);

    session_start();
}

const AUTH_USERNAME = 'memar';
const AUTH_PASSWORD_HASH = '$2y$12$nXM7C4pe.TFpGU4OM04QiO1B3y62LK0Gzmu/REj8cAiFpwVw4XvoS';

function is_authenticated(): bool
{
    return isset($_SESSION['auth_user'])
        && is_string($_SESSION['auth_user'])
        && hash_equals(AUTH_USERNAME, $_SESSION['auth_user']);
}

function authenticate(string $username, string $password): bool
{
    if (!hash_equals(AUTH_USERNAME, $username)) {
        return false;
    }

    if (!password_verify($password, AUTH_PASSWORD_HASH)) {
        return false;
    }

    session_regenerate_id(true);
    $_SESSION['auth_user'] = AUTH_USERNAME;

    return true;
}

function logout_user(): void
{
    $_SESSION = [];

    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(
            session_name(),
            '',
            time() - 42000,
            $params['path'],
            $params['domain'] ?? '',
            (bool) $params['secure'],
            (bool) $params['httponly']
        );
    }

    session_destroy();
}

function require_login(): void
{
    if (is_authenticated()) {
        return;
    }

    $script = basename((string) ($_SERVER['SCRIPT_FILENAME'] ?? ''));
    if ($script === 'login.php') {
        return;
    }

    $requestMethod = strtoupper((string) ($_SERVER['REQUEST_METHOD'] ?? 'GET'));
    $accept = (string) ($_SERVER['HTTP_ACCEPT'] ?? '');
    $isAjax = isset($_SERVER['HTTP_X_REQUESTED_WITH'])
        && strtolower((string) $_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
    $expectsJson = $isAjax || str_contains($accept, 'application/json');

    if ($expectsJson) {
        if (!headers_sent()) {
            http_response_code(401);
            header('Content-Type: application/json; charset=UTF-8');
        }
        echo json_encode([
            'success' => false,
            'message' => 'برای انجام این عملیات باید وارد حساب کاربری شوید.',
            'redirect' => 'login.php',
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    $next = $_SERVER['REQUEST_URI'] ?? 'index.php';
    if ($requestMethod !== 'GET') {
        $next = 'index.php';
    }

    header('Location: login.php?next=' . rawurlencode($next));
    exit;
}
