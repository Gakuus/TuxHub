<?php
require_once __DIR__ . '/helpers.php';
require_once __DIR__ . '/db_connection.php';

require_role('admin');

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
    app_log('error', 'Error al eliminar grupo', ['id' => $id, 'error' => $e->getMessage()]);
    header("Location: ../dashboard.php?page=grupos&msg=" . urlencode("ERR:Error interno al eliminar el grupo."));
    exit();
}
