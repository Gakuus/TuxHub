<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once 'db_connection.php';

header('Content-Type: application/json');

$action = $_GET['action'] ?? '';

try {
    switch ($action) {
        case 'grupos':
            getGruposProfesor();
            break;
        case 'bloques':
            getBloquesProfesor();
            break;
        case 'historial':
            getHistorialAsistencias();
            break;
        case 'debug_relations':
            debugRelations();
            break;
        default:
            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                registrarAsistencia();
            } else {
                echo json_encode(['success' => false, 'message' => 'Acción no válida: ' . $action]);
            }
            break;
    }
} catch (Exception $e) {
    error_log("Error general: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Error del servidor: ' . $e->getMessage()]);
}

function debugRelations() {
    global $conn;
    
    $profesor_id = $_GET['profesor_id'] ?? 2;
    
    // Ver grupos del profesor
    $grupos_query = "SELECT gp.*, g.nombre as grupo_nombre 
                     FROM grupos_profesores gp 
                     INNER JOIN grupos g ON gp.grupo_id = g.id 
                     WHERE gp.profesor_id = ?";
    $stmt = $conn->prepare($grupos_query);
    $stmt->bind_param("i", $profesor_id);
    $stmt->execute();
    $grupos_result = $stmt->get_result();
    $grupos_profesor = [];
    while ($row = $grupos_result->fetch_assoc()) {
        $grupos_profesor[] = $row;
    }
    
    // Ver bloques del profesor
    $bloques_query = "SELECT pb.*, bh.turno, bh.hora_inicio, bh.hora_fin, g.nombre as grupo_nombre
                      FROM profesor_bloques pb
                      INNER JOIN bloques_horarios bh ON pb.bloque_id = bh.id
                      INNER JOIN grupos g ON pb.grupo_id = g.id
                      WHERE pb.profesor_id = ?";
    $stmt = $conn->prepare($bloques_query);
    $stmt->bind_param("i", $profesor_id);
    $stmt->execute();
    $bloques_result = $stmt->get_result();
    $bloques_profesor = [];
    while ($row = $bloques_result->fetch_assoc()) {
        $bloques_profesor[] = $row;
    }
    
    echo json_encode([
        'success' => true,
        'grupos_profesor' => $grupos_profesor,
        'bloques_profesor' => $bloques_profesor,
        'message' => 'Grupos: ' . count($grupos_profesor) . ', Bloques: ' . count($bloques_profesor)
    ]);
}

function getGruposProfesor() {
    global $conn;
    
    if (!isset($_GET['profesor_id'])) {
        echo json_encode(['success' => false, 'message' => 'No se recibió profesor_id']);
        return;
    }
    
    $profesor_id = intval($_GET['profesor_id']);
    
    // Usar grupos_profesores en lugar de profesor_bloques
    $query = "
        SELECT DISTINCT g.id, g.nombre 
        FROM grupos g 
        INNER JOIN grupos_profesores gp ON g.id = gp.grupo_id 
        WHERE gp.profesor_id = ?
        ORDER BY g.nombre
    ";
    
    $stmt = $conn->prepare($query);
    if ($stmt) {
        $stmt->bind_param("i", $profesor_id);
        if ($stmt->execute()) {
            $result = $stmt->get_result();
            $grupos = [];
            while ($row = $result->fetch_assoc()) {
                $grupos[] = $row;
            }
            echo json_encode(['success' => true, 'grupos' => $grupos]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Error ejecutando consulta: ' . $stmt->error]);
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'Error preparando consulta: ' . $conn->error]);
    }
}

function getBloquesProfesor() {
    global $conn;
    
    if (!isset($_GET['profesor_id'])) {
        echo json_encode(['success' => false, 'message' => 'No se recibió profesor_id']);
        return;
    }
    
    $profesor_id = intval($_GET['profesor_id']);
    $fecha = $_GET['fecha'] ?? date('Y-m-d');
    
    // Obtener el día de la semana de la fecha (1=Lunes, 7=Domingo)
    $timestamp = strtotime($fecha);
    $dia_numero = date('N', $timestamp);
    
    // Mapear número de día a nombre
    $dias_map = [
        1 => 'Lunes',
        2 => 'Martes', 
        3 => 'Miércoles',
        4 => 'Jueves',
        5 => 'Viernes',
        6 => 'Sábado',
        7 => 'Domingo'
    ];
    
    $dia_nombre = $dias_map[$dia_numero] ?? 'Lunes';
    
    // Consulta corregida - usar profesor_bloques para los horarios
    $query = "
        SELECT bh.id, bh.turno as nombre_bloque, bh.hora_inicio, bh.hora_fin, 
               ? as nombre_dia, g.nombre as nombre_grupo, pb.grupo_id
        FROM bloques_horarios bh
        INNER JOIN profesor_bloques pb ON bh.id = pb.bloque_id
        INNER JOIN grupos g ON pb.grupo_id = g.id
        WHERE pb.profesor_id = ?
        ORDER BY bh.hora_inicio
    ";
    
    $stmt = $conn->prepare($query);
    if ($stmt) {
        $stmt->bind_param("si", $dia_nombre, $profesor_id);
        if ($stmt->execute()) {
            $result = $stmt->get_result();
            $bloques = [];
            while ($row = $result->fetch_assoc()) {
                // Formatear horas
                $row['hora_inicio'] = substr($row['hora_inicio'], 0, 5);
                $row['hora_fin'] = substr($row['hora_fin'], 0, 5);
                $bloques[] = $row;
            }
            echo json_encode(['success' => true, 'bloques' => $bloques, 'dia_actual' => $dia_nombre]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Error ejecutando consulta: ' . $stmt->error]);
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'Error preparando consulta: ' . $conn->error]);
    }
}

function getHistorialAsistencias() {
    global $conn;
    
    $profesor_id = $_GET['profesor_id'] ?? null;
    $where = "";
    $params = [];
    $types = "";
    
    if ($profesor_id) {
        $where = "WHERE a.usuario_id = ?";
        $params[] = intval($profesor_id);
        $types .= "i";
    }
    
    $query = "
        SELECT a.*, u.nombre as nombre_profesor, g.nombre as nombre_grupo, 
               s.nombre_salon, bh.turno as nombre_bloque, bh.hora_inicio, bh.hora_fin, d.nombre_dia,
               CONCAT(bh.turno, ' (', d.nombre_dia, ' ', TIME_FORMAT(bh.hora_inicio, '%H:%i'), '-', TIME_FORMAT(bh.hora_fin, '%H:%i'), ')') as bloque_info
        FROM asistencias a
        INNER JOIN usuarios u ON a.usuario_id = u.id
        INNER JOIN grupos g ON a.grupo_id = g.id
        LEFT JOIN salones s ON a.salon_id = s.id
        INNER JOIN bloques_horarios bh ON a.bloque_id = bh.id
        INNER JOIN dias d ON a.dia_id = d.id
        $where
        ORDER BY a.fecha DESC, bh.hora_inicio DESC
        LIMIT 50
    ";
    
    $stmt = $conn->prepare($query);
    if ($stmt) {
        if (!empty($params)) {
            $stmt->bind_param($types, ...$params);
        }
        
        if ($stmt->execute()) {
            $result = $stmt->get_result();
            $asistencias = [];
            while ($row = $result->fetch_assoc()) {
                $asistencias[] = $row;
            }
            echo json_encode(['success' => true, 'asistencias' => $asistencias]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Error ejecutando consulta: ' . $stmt->error]);
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'Error preparando consulta: ' . $conn->error]);
    }
}

function registrarAsistencia() {
    global $conn;
    
    if (!isset($_POST['registrar_asistencia'])) {
        echo json_encode(['success' => false, 'message' => 'Solicitud inválida']);
        return;
    }
    
    $usuario_id = intval($_POST['usuario_id']);
    $fecha = $conn->real_escape_string($_POST['fecha']);
    $grupo_id = intval($_POST['grupo_id']);
    $bloque_id = intval($_POST['bloque_id']);
    $salon_id = !empty($_POST['salon_id']) ? intval($_POST['salon_id']) : NULL;
    $estado = $conn->real_escape_string($_POST['estado']);
    $justificacion = !empty($_POST['justificacion']) ? $conn->real_escape_string($_POST['justificacion']) : NULL;
    
    // Obtener el dia_id de la fecha
    $timestamp = strtotime($fecha);
    $dia_numero = date('N', $timestamp);
    
    $dia_query = "SELECT id FROM dias WHERE nombre_dia = ?";
    $stmt = $conn->prepare($dia_query);
    
    // Mapear número a nombre de día
    $dias_map = [
        1 => 'Lunes',
        2 => 'Martes', 
        3 => 'Miércoles',
        4 => 'Jueves',
        5 => 'Viernes',
        6 => 'Sábado',
        7 => 'Domingo'
    ];
    
    $dia_nombre = $dias_map[$dia_numero] ?? 'Lunes';
    $stmt->bind_param("s", $dia_nombre);
    $stmt->execute();
    $dia_result = $stmt->get_result();
    $dia_row = $dia_result->fetch_assoc();
    $dia_id = $dia_row['id'] ?? 1;
    
    // Verificar si ya existe registro
    $check_query = "SELECT id FROM asistencias WHERE usuario_id = ? AND fecha = ? AND bloque_id = ?";
    $stmt = $conn->prepare($check_query);
    $stmt->bind_param("isi", $usuario_id, $fecha, $bloque_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        echo json_encode(['success' => false, 'message' => 'Ya existe un registro de asistencia para esta fecha y bloque horario']);
        return;
    }
    
    $query = "INSERT INTO asistencias (usuario_id, fecha, dia_id, grupo_id, bloque_id, salon_id, estado, justificacion) 
              VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
    
    $stmt = $conn->prepare($query);
    if ($stmt) {
        $stmt->bind_param("isiiisss", $usuario_id, $fecha, $dia_id, $grupo_id, $bloque_id, $salon_id, $estado, $justificacion);
        
        if ($stmt->execute()) {
            echo json_encode(['success' => true, 'message' => 'Asistencia registrada correctamente']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Error al registrar asistencia: ' . $stmt->error]);
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'Error preparando consulta: ' . $conn->error]);
    }
}
?>