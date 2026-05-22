<?php
require_once __DIR__ . '/db_connection.php';
require_once __DIR__ . '/helpers.php';

csrf_verify();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    if (!rate_limit_check($ip, 'grupos', 20, 60)) {
        http_response_code(429);
        echo '<h2>Demasiadas solicitudes. Intenta más tarde.</h2>';
        exit;
    }
}

if (!isset($_SESSION['user_id'])) {
    header("Location: ../index.php?error=requerido");
    exit();
}
$rol = $_SESSION['rol'] ?? '';
if (strtolower($rol) !== 'admin') {
    header("Location: ../dashboard.php?page=grupos&msg=" . urlencode("ERR:No autorizado."));
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: ../dashboard.php?page=grupos");
    exit();
}

$nombre = trim($_POST['nombre_grupo'] ?? '');
$turno = trim($_POST['turno'] ?? '');

if ($nombre === '') {
    header("Location: ../dashboard.php?page=grupos&msg=" . urlencode("ERR:Nombre vacío."));
    exit();
}
if ($turno === '') {
    header("Location: ../dashboard.php?page=grupos&msg=" . urlencode("ERR:Debe seleccionar un turno."));
    exit();
}

try {
    $stmt = $conn->prepare("INSERT INTO grupos (nombre, turno) VALUES (?, ?)");
    $stmt->bind_param("ss", $nombre, $turno);
    $stmt->execute();
    $stmt->close();
    header("Location: ../dashboard.php?page=grupos&msg=" . urlencode("Grupo agregado correctamente."));
    exit();
} catch (Exception $e) {
    app_log('error', 'Error al crear grupo', ['nombre' => $nombre, 'turno' => $turno, 'error' => $e->getMessage()]);
    header("Location: ../dashboard.php?page=grupos&msg=" . urlencode("ERR:No se pudo crear el grupo."));
    exit();
}
