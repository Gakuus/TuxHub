<?php
// logout.php
session_start();

// Verificar token CSRF si es una solicitud POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        http_response_code(403);
        exit('Token CSRF inválido');
    }
}

// Configuración de seguridad
ini_set('session.cookie_httponly', 1);
ini_set('session.cookie_secure', 1); // Solo para HTTPS
ini_set('session.cookie_samesite', 'Strict');

// Regenerar ID de sesión para prevenir session fixation
session_regenerate_id(true);

// Guardar información del logout para auditoría (opcional)
if (isset($_SESSION['user_id'])) {
    $user_id = $_SESSION['user_id'];
    $logout_time = date('Y-m-d H:i:s');
    // Aquí podrías guardar en un log: "Usuario $user_id cerró sesión a las $logout_time"
}

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