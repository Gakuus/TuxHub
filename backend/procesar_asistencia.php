<?php
session_start();
require_once __DIR__ . '/db_connection.php';
require_once __DIR__ . '/helpers.php';

header('Content-Type: application/json');

$action = $_POST['action'] ?? $_GET['action'] ?? '';

try {
    switch ($action) {
        case 'cargar_grupos':
            $profesor_id = (int)($_POST['profesor_id'] ?? 0);
            if (!$profesor_id) {
                echo json_encode(['success' => false, 'message' => 'Profesor no especificado']);
                exit;
            }
            $stmt = $conn->prepare("
                SELECT g.id, g.nombre AS nombre_grupo
                FROM grupos g
                JOIN grupos_profesores gp ON gp.grupo_id = g.id
                WHERE gp.profesor_id = ?
                ORDER BY g.nombre
            ");
            $stmt->bind_param("i", $profesor_id);
            $stmt->execute();
            $result = $stmt->get_result();
            $grupos = $result->fetch_all(MYSQLI_ASSOC);
            echo json_encode(['success' => true, 'grupos' => $grupos]);
            break;

        case 'historial':
            $profesor_id = (int)($_POST['profesor_id'] ?? $_GET['profesor_id'] ?? 0);
            $where = $profesor_id ? "WHERE a.usuario_id = ?" : "";
            $params = $profesor_id ? [$profesor_id] : [];
            $types = $profesor_id ? "i" : "";

            $sql = "
                SELECT a.*, u.nombre AS nombre_profesor, g.nombre AS nombre_grupo,
                       s.nombre_salon, b.hora_inicio, b.hora_fin, d.nombre_dia
                FROM asistencias a
                JOIN usuarios u ON a.usuario_id = u.id
                JOIN grupos g ON a.grupo_id = g.id
                LEFT JOIN salones s ON a.salon_id = s.id
                LEFT JOIN bloques_horarios b ON a.bloque_id = b.id
                LEFT JOIN dias d ON a.dia_id = d.id
                $where
                ORDER BY a.fecha DESC
                LIMIT 50
            ";
            $stmt = $conn->prepare($sql);
            if ($params) $stmt->bind_param($types, ...$params);
            $stmt->execute();
            $result = $stmt->get_result();
            $historial = $result->fetch_all(MYSQLI_ASSOC);
            echo json_encode(['success' => true, 'historial' => $historial]);
            break;

        default:
            $registrar_asistencia = $_POST['registrar_asistencia'] ?? null;
            if (!$registrar_asistencia) {
                echo json_encode(['success' => false, 'message' => 'Acción no válida']);
                exit;
            }

            $usuario_id = (int)($_POST['usuario_id'] ?? 0);
            $fecha = $_POST['fecha'] ?? '';
            $grupo_id = (int)($_POST['grupo_id'] ?? 0);
            $bloque_id = (int)($_POST['bloque_id'] ?? 0);
            $salon_id = !empty($_POST['salon_id']) ? (int)$_POST['salon_id'] : null;
            $estado = $_POST['estado'] ?? '';
            $justificacion = $_POST['justificacion'] ?? '';

            if (!$usuario_id || !$fecha || !$grupo_id || !$bloque_id || !$estado) {
                echo json_encode(['success' => false, 'message' => 'Campos obligatorios faltantes']);
                exit;
            }

            $dia_numero = date('N', strtotime($fecha));
            $dias_map = [1 => 'Lunes', 2 => 'Martes', 3 => 'Miércoles', 4 => 'Jueves', 5 => 'Viernes', 6 => 'Sábado', 7 => 'Domingo'];
            $dia_nombre = $dias_map[$dia_numero] ?? 'Lunes';

            $stmt_d = $conn->prepare("SELECT id FROM dias WHERE nombre_dia = ?");
            $stmt_d->bind_param("s", $dia_nombre);
            $stmt_d->execute();
            $r_d = $stmt_d->get_result()->fetch_assoc();
            $dia_id = $r_d['id'] ?? 1;
            $stmt_d->close();

            $check = $conn->prepare("SELECT id FROM asistencias WHERE usuario_id = ? AND fecha = ? AND bloque_id = ?");
            $check->bind_param("isi", $usuario_id, $fecha, $bloque_id);
            $check->execute();
            if ($check->get_result()->num_rows > 0) {
                echo json_encode(['success' => false, 'message' => 'Ya existe registro para esta fecha y bloque']);
                exit;
            }
            $check->close();

            $stmt = $conn->prepare("INSERT INTO asistencias (usuario_id, fecha, dia_id, grupo_id, bloque_id, salon_id, estado, justificacion) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("isiiisss", $usuario_id, $fecha, $dia_id, $grupo_id, $bloque_id, $salon_id, $estado, $justificacion);
            $stmt->execute();
            echo json_encode(['success' => true, 'message' => 'Asistencia registrada correctamente']);
            break;
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}
