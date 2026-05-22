<?php
require_once __DIR__ . '/helpers.php';

// Verificar token CSRF si es una solicitud POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        http_response_code(403);
        exit('Token CSRF inválido');
    }
}

// Registrar logout en log
if (isset($_SESSION['user_id'])) {
    app_log('info', 'Usuario cerró sesión', ['user_id' => $_SESSION['user_id']]);
}

// Regenerar ID de sesión para prevenir session fixation
session_regenerate_id(true);

// Limpiar completamente la sesión
$_SESSION = [];

// Destruir cookie de sesión
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(
        session_name(), 
        '', 
        time() - 3600,
        $params['path'],
        $params['domain'],
        $params['secure'],
        $params['httponly']
    );
}

// Destruir la sesión
$session_destroyed = session_destroy();

// Limpiar cualquier buffer de salida
while (ob_get_level()) {
    ob_end_clean();
}

// Redireccionar
if ($session_destroyed) {
    header("Location: ../index.php?logout=success");
} else {
    header("Location: ../index.php?logout=error");
}
exit();