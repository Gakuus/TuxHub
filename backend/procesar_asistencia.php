<?php
require_once __DIR__ . '/helpers.php';
require_once __DIR__ . '/db_connection.php';
require_role('admin');

header('Content-Type: application/json; charset=utf-8');

$action = $_POST['action'] ?? '';

if ($action === 'guardar_asistencia') {
    csrf_verify();
}

switch ($action) {
    case 'cargar_grupos':
        $profesor_id = intval($_POST['profesor_id'] ?? 0);
        if (!$profesor_id) {
            echo json_encode(['success' => false, 'message' => 'ID de profesor requerido']);
            exit;
        }
        $stmt = $conn->prepare("
            SELECT g.id, g.nombre AS nombre_grupo
            FROM horarios h
            JOIN grupos g ON h.grupo_id = g.id
            WHERE h.profesor_id = ?
            GROUP BY g.id, g.nombre
            ORDER BY g.nombre
        ");
        $stmt->bind_param("i", $profesor_id);
        $stmt->execute();
        $res = $stmt->get_result();
        $grupos = [];
        while ($row = $res->fetch_assoc()) {
            $grupos[] = $row;
        }
        echo json_encode(['success' => true, 'grupos' => $grupos]);
        break;

    case 'cargar_historial':
        $profesor_id = intval($_POST['profesor_id'] ?? 0);
        if (!$profesor_id) {
            echo json_encode(['success' => false, 'message' => 'ID de profesor requerido']);
            exit;
        }
        $stmt = $conn->prepare("
            SELECT
                a.fecha,
                g.nombre AS nombre_grupo,
                b.nombre AS nombre_bloque,
                s.nombre_salon,
                a.estado,
                a.justificacion
            FROM asistencias a
            LEFT JOIN grupos g ON a.grupo_id = g.id
            LEFT JOIN bloques b ON a.bloque_id = b.id
            LEFT JOIN salones s ON a.salon_id = s.id
            WHERE a.profesor_id = ?
            ORDER BY a.fecha DESC
            LIMIT 100
        ");
        $stmt->bind_param("i", $profesor_id);
        $stmt->execute();
        $res = $stmt->get_result();
        $historial = [];
        while ($row = $res->fetch_assoc()) {
            $historial[] = $row;
        }
        echo json_encode(['success' => true, 'historial' => $historial]);
        break;

    case 'guardar_asistencia':
        $usuario_id = intval($_POST['usuario_id'] ?? 0);
        $fecha = $_POST['fecha'] ?? '';
        $grupo_id = intval($_POST['grupo_id'] ?? 0);
        $bloque_id = intval($_POST['bloque_id'] ?? 0);
        $estado = $_POST['estado'] ?? '';
        $justificacion = $_POST['justificacion'] ?? '';
        $salon_id = intval($_POST['salon_id'] ?? 0);

        if (!$usuario_id || !$fecha || !$grupo_id || !$bloque_id || !$estado) {
            echo json_encode(['success' => false, 'message' => 'Campos obligatorios faltantes']);
            exit;
        }

        $stmt = $conn->prepare("
            INSERT INTO asistencias (profesor_id, fecha, grupo_id, bloque_id, salon_id, estado, justificacion)
            VALUES (?, ?, ?, ?, ?, ?, ?)
            ON DUPLICATE KEY UPDATE estado = VALUES(estado), justificacion = VALUES(justificacion)
        ");
        $stmt->bind_param("isiiiis", $usuario_id, $fecha, $grupo_id, $bloque_id, $salon_id, $estado, $justificacion);
        if ($stmt->execute()) {
            app_log('info', 'Asistencia registrada', ['profesor_id' => $usuario_id, 'fecha' => $fecha]);
            echo json_encode(['success' => true, 'message' => 'Asistencia registrada correctamente']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Error al guardar']);
        }
        break;

    default:
        echo json_encode(['success' => false, 'message' => 'Acción no válida']);
}
