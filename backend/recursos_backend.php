<?php
// /Agora/Agora/backend/recursos_backend.php
session_start();
require_once 'db_connection.php';
$conn->set_charset('utf8mb4');

header('Content-Type: application/json');

// Verificar que el usuario es admin
if (!isset($_SESSION['user_id']) || strtolower($_SESSION['rol'] ?? '') !== 'admin') {
    echo json_encode(['error' => 'No autorizado']);
    exit;
}

// DEBUG: Log de todos los datos recibidos
error_log("=== RECURSOS_BACKEND DEBUG ===");
error_log("POST data: " . print_r($_POST, true));
error_log("GET data: " . print_r($_GET, true));

$accion = $_POST['accion'] ?? '';
$id = isset($_POST['id']) ? (int)$_POST['id'] : 0;

error_log("Acción recibida: " . $accion);
error_log("ID recibido: " . $id);

try {
    if ($accion === 'crear') {
        error_log("Procesando CREAR recurso");
        
        // Crear nuevo recurso
        $nombre = trim($_POST['nombre'] ?? '');
        $tipo = $_POST['tipo'] ?? '';
        $estado = $_POST['estado'] ?? 'Disponible';
        $salon_id = !empty($_POST['salon_id']) ? (int)$_POST['salon_id'] : NULL;
        $grupo_id = !empty($_POST['grupo_id']) ? (int)$_POST['grupo_id'] : NULL;
        $usuario_id = !empty($_POST['usuario_id']) ? (int)$_POST['usuario_id'] : NULL;
        $descripcion = trim($_POST['descripcion'] ?? '');

        error_log("Datos: nombre=$nombre, tipo=$tipo, estado=$estado, salon_id=$salon_id, grupo_id=$grupo_id, usuario_id=$usuario_id");

        if (empty($nombre) || empty($tipo)) {
            throw new Exception('Nombre y tipo son requeridos');
        }

        $sql = "INSERT INTO recursos (nombre, tipo, estado, salon_id, grupo_id, usuario_id, descripcion) 
                VALUES (?, ?, ?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);
        if (!$stmt) {
            throw new Exception('Error prepare: ' . $conn->error);
        }
        
        $stmt->bind_param('sssiiis', $nombre, $tipo, $estado, $salon_id, $grupo_id, $usuario_id, $descripcion);
        
        if ($stmt->execute()) {
            error_log("Recurso creado exitosamente, ID: " . $stmt->insert_id);
            echo json_encode(['success' => true, 'message' => 'Recurso creado exitosamente', 'id' => $stmt->insert_id]);
        } else {
            throw new Exception('Error al crear recurso: ' . $stmt->error);
        }

    } elseif ($accion === 'actualizar' && $id > 0) {
        error_log("Procesando ACTUALIZAR recurso ID: $id");
        
        // Actualizar recurso existente
        $nombre = trim($_POST['nombre'] ?? '');
        $tipo = $_POST['tipo'] ?? '';
        $estado = $_POST['estado'] ?? 'Disponible';
        $salon_id = !empty($_POST['salon_id']) ? (int)$_POST['salon_id'] : NULL;
        $grupo_id = !empty($_POST['grupo_id']) ? (int)$_POST['grupo_id'] : NULL;
        $usuario_id = !empty($_POST['usuario_id']) ? (int)$_POST['usuario_id'] : NULL;
        $descripcion = trim($_POST['descripcion'] ?? '');

        error_log("Datos: nombre=$nombre, tipo=$tipo, estado=$estado, salon_id=$salon_id, grupo_id=$grupo_id, usuario_id=$usuario_id");

        if (empty($nombre) || empty($tipo)) {
            throw new Exception('Nombre y tipo son requeridos');
        }

        $sql = "UPDATE recursos SET nombre = ?, tipo = ?, estado = ?, salon_id = ?, grupo_id = ?, usuario_id = ?, descripcion = ? 
                WHERE id = ?";
        $stmt = $conn->prepare($sql);
        if (!$stmt) {
            throw new Exception('Error prepare: ' . $conn->error);
        }
        
        $stmt->bind_param('sssiiisi', $nombre, $tipo, $estado, $salon_id, $grupo_id, $usuario_id, $descripcion, $id);
        
        if ($stmt->execute()) {
            error_log("Recurso actualizado exitosamente");
            echo json_encode(['success' => true, 'message' => 'Recurso actualizado exitosamente']);
        } else {
            throw new Exception('Error al actualizar recurso: ' . $stmt->error);
        }

    } else {
        error_log("Acción no válida: $accion, ID: $id");
        throw new Exception('Acción no válida. Acción: ' . $accion . ', ID: ' . $id);
    }

} catch (Exception $e) {
    error_log("Error en recursos_backend: " . $e->getMessage());
    echo json_encode(['error' => $e->getMessage()]);
}