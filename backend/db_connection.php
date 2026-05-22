<?php
require_once __DIR__ . '/config.php';

$db_host = env('DB_HOST', '127.0.0.1');
$db_user = env('DB_USER', 'root');
$db_pass = env('DB_PASS', '');
$db_name = env('DB_NAME', 'db_agora');
$db_port = (int) env('DB_PORT', 3306);

$conn = new mysqli($db_host, $db_user, $db_pass, $db_name, $db_port);

if ($conn->connect_error) {
    error_log("Error de conexión MySQL: " . $conn->connect_error);
    $conn = null;
    if (strpos($_SERVER['PHP_SELF'] ?? '', 'index.php') === false &&
        strpos($_SERVER['PHP_SELF'] ?? '', 'login_handler.php') === false) {
        header("Location: index.php?error=db");
        exit();
    }
}

if ($conn) {
    $conn->set_charset("utf8mb4");
}
