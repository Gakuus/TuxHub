<?php

function init_session(): void {
    if (session_status() === PHP_SESSION_ACTIVE) return;

    $is_https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
                || ($_SERVER['SERVER_PORT'] ?? 80) == 443;

    ini_set('session.cookie_httponly', 1);
    if ($is_https) {
        ini_set('session.cookie_secure', 1);
    }
    ini_set('session.use_strict_mode', 1);
    ini_set('session.cookie_samesite', 'Lax');
    ini_set('session.use_only_cookies', 1);

    session_start();
}

init_session();

function app_log(string $level, string $message, array $context = []): void {
    $log_dir = __DIR__ . '/../logs';
    if (!is_dir($log_dir)) {
        @mkdir($log_dir, 0755, true);
    }

    $entry = [
        'time'    => date('Y-m-d H:i:s'),
        'level'   => strtoupper($level),
        'message' => $message,
        'ip'      => $_SERVER['REMOTE_ADDR'] ?? 'cli',
        'user_id' => $_SESSION['user_id'] ?? null,
    ];

    if ($context) {
        $entry['context'] = $context;
    }

    $file = $log_dir . '/app-' . date('Y-m-d') . '.log';
    file_put_contents($file, json_encode($entry, JSON_UNESCAPED_UNICODE) . "\n", FILE_APPEND | LOCK_EX);
}

function csrf_verify(): void {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') return;
    $token = $_POST['csrf_token'] ?? '';
    $stored = $_SESSION['csrf_token'] ?? '';
    if (empty($token) || empty($stored) || !hash_equals($stored, $token)) {
        http_response_code(403);
        header('Content-Type: application/json');
        echo json_encode(['ok' => false, 'msg' => 'CSRF inválido']);
        exit;
    }
}

function csrf_token(): string {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function csrf_verify_get(): void {
    $token = $_GET['csrf_token'] ?? '';
    $stored = $_SESSION['csrf_token'] ?? '';
    if (empty($token) || empty($stored) || !hash_equals($stored, $token)) {
        $page = $_GET['page'] ?? 'dashboard';
        header('Location: dashboard.php?page=' . urlencode($page) . '&error=csrf');
        exit;
    }
}

function require_auth(): void {
    if (!isset($_SESSION['user_id'])) {
        header('Location: index.php?error=requerido');
        exit;
    }
}

function require_role(string $role): void {
    require_auth();
    $rol = $_SESSION['rol'] ?? '';
    if (strtolower($rol) !== strtolower($role)) {
        http_response_code(403);
        echo json_encode(['ok' => false, 'msg' => 'No autorizado']);
        exit;
    }
}

function json_response(mixed $data, int $code = 200): void {
    http_response_code($code);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

function base_url(): string {
    $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    $dir = dirname($_SERVER['SCRIPT_NAME']);
    $dir = $dir === '/' || $dir === '\\' ? '' : $dir;
    $parts = explode('/', trim($dir, '/'));
    array_pop($parts); // remove backend/ from path
    $base = implode('/', $parts);
    return "$scheme://$host" . ($base ? "/$base" : '');
}

function redirect_to(string $path): void {
    header("Location: " . base_url() . "/$path");
    exit;
}
