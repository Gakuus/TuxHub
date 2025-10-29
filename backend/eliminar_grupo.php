<?php
// IMPORTANTE: NO imprimir nada antes de los header()
session_start();

// Ajusta la ruta al archivo de conexión si tu estructura es distinta
require_once __DIR__ . '/db_connection.php';

// Verificar sesión y rol
if (!isset($_SESSION['user_id'])) {
    header("Location: ../index.php?error=requerido");
    exit();
}

$rol = $_SESSION['rol'] ?? '';
if (strtolower($rol) !== 'admin') {
    // No autorizado
    header("Location: ../dashboard.php?page=grupos&msg=" . urlencode("ERR:No autorizado."));
    exit();
}

// Validar ID
$id = isset($_GET['id']) ? intval($_GET['id']) : 0;
if ($id <= 0) {
    header("Location: ../dashboard.php?page=grupos&msg=" . urlencode("ERR:ID inválido."));
    exit();
}

// Intentar eliminar
try {
    $stmt = $conn->prepare("DELETE FROM grupos WHERE id = ?");
    if (!$stmt) {
        header("Location: ../dashboard.php?page=grupos&msg=" . urlencode("ERR:Fallo al preparar la consulta."));
        exit();
    }
    $stmt->bind_param("i", $id);
    $stmt->execute();

    if ($stmt->affected_rows > 0) {
        $stmt->close();
        header("Location: ../dashboard.php?page=grupos&msg=" . urlencode("Grupo eliminado correctamente."));
        exit();
    } else {
        $stmt->close();
        header("Location: ../dashboard.php?page=grupos&msg=" . urlencode("ERR:No se encontró el grupo o ya fue eliminado."));
        exit();
    }
} catch (Exception $e) {
    header("Location: ../dashboard.php?page=grupos&msg=" . urlencode("ERR:Error al eliminar: " . $e->getMessage()));
    exit();
}
