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
    $allowed_hosts = env('ALLOWED_HOSTS', '');
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    if (!empty($allowed_hosts)) {
        $allowed = array_map('trim', explode(',', $allowed_hosts));
        if (!in_array($host, $allowed, true)) {
            $host = $allowed[0] ?? 'localhost';
        }
    }
    $dir = dirname($_SERVER['SCRIPT_NAME']);
    $dir = $dir === '/' || $dir === '\\' ? '' : $dir;
    $parts = explode('/', trim($dir, '/'));
    array_pop($parts);
    $base = implode('/', $parts);
    return "$scheme://$host" . ($base ? "/$base" : '');
}

function redirect_to(string $path): void {
    header("Location: " . base_url() . "/$path");
    exit;
}

function rate_limit_check(string $ip, string $action = 'default', int $max = 10, int $window = 60): bool {
    $file = __DIR__ . '/../logs/ratelimit_' . $action . '.json';
    $now = time();
    $data = [];
    if (file_exists($file)) {
        $data = json_decode(file_get_contents($file), true) ?? [];
    }
    // Limpiar entradas viejas
    $data = array_filter($data, fn($t) => ($now - $t) < $window);
    // Contar intentos de esta IP
    $attempts = 0;
    foreach ($data as $entry) {
        if (isset($entry['ip']) && $entry['ip'] === $ip) {
            $attempts++;
        }
    }
    if ($attempts >= $max) {
        return false;
    }
    $data[] = ['ip' => $ip, 'time' => $now];
    file_put_contents($file, json_encode($data), LOCK_EX);
    return true;
}

function sanitize_filename(string $filename): string {
    $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
    $safe_ext = preg_replace('/[^a-z0-9]/', '', $ext);
    return bin2hex(random_bytes(8)) . '.' . $safe_ext;
}

function paginate(int $total, int $page, int $per_page): array {
    $total_pages = max(1, (int)ceil($total / $per_page));
    $page = max(1, min($page, $total_pages));
    return [
        'current'  => $page,
        'per_page' => $per_page,
        'total'    => $total,
        'pages'    => $total_pages,
        'offset'   => ($page - 1) * $per_page,
    ];
}

function validate_password_strength(string $password): ?string {
    $len = strlen($password);
    if ($len < 8 || $len > 24) return 'La contraseña debe tener entre 8 y 24 caracteres.';
    if (!preg_match('/[A-Z]/', $password)) return 'Debe contener al menos una mayúscula.';
    if (!preg_match('/[a-z]/', $password)) return 'Debe contener al menos una minúscula.';
    if (!preg_match('/[0-9]/', $password)) return 'Debe contener al menos un número.';
    return null;
}

function render_pagination(array $paginfo, string $url_base): string {
    $html = '';
    if ($paginfo['pages'] <= 1) return $html;
    $html .= '<nav aria-label="Paginación"><ul class="pagination pagination-sm justify-content-center mb-0">';
    $page = $paginfo['current'];
    $pages = $paginfo['pages'];

    // Previous
    $prev_disabled = $page <= 1 ? ' disabled' : '';
    $html .= '<li class="page-item' . $prev_disabled . '"><a class="page-link" href="' . $url_base . '&page=' . ($page - 1) . '">&laquo;</a></li>';

    $start = max(1, $page - 2);
    $end = min($pages, $page + 2);
    if ($start > 1) {
        $html .= '<li class="page-item"><a class="page-link" href="' . $url_base . '&page=1">1</a></li>';
        if ($start > 2) $html .= '<li class="page-item disabled"><span class="page-link">&hellip;</span></li>';
    }
    for ($i = $start; $i <= $end; $i++) {
        $active = $i === $page ? ' active' : '';
        $html .= '<li class="page-item' . $active . '"><a class="page-link" href="' . $url_base . '&page=' . $i . '">' . $i . '</a></li>';
    }
    if ($end < $pages) {
        if ($end < $pages - 1) $html .= '<li class="page-item disabled"><span class="page-link">&hellip;</span></li>';
        $html .= '<li class="page-item"><a class="page-link" href="' . $url_base . '&page=' . $pages . '">' . $pages . '</a></li>';
    }

    // Next
    $next_disabled = $page >= $pages ? ' disabled' : '';
    $html .= '<li class="page-item' . $next_disabled . '"><a class="page-link" href="' . $url_base . '&page=' . ($page + 1) . '">&raquo;</a></li>';
    $html .= '</ul></nav>';
    return $html;
}
