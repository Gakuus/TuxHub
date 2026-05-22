<?php
require_once __DIR__ . '/db_connection.php';
require_once __DIR__ . '/helpers.php';

require_auth();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    if (!rate_limit_check($ip, 'asistencia', 30, 60)) {
        http_response_code(429);
        echo json_encode(['ok' => false, 'msg' => 'Demasiadas solicitudes.']);
        exit;
    }
}

// ===================
// MOSTRAR HISTORIAL
// ===================
if (isset($_GET['historial'])) {
    $rol = $_SESSION['rol'] ?? null;
    $user_id = (int)($_SESSION['user_id'] ?? 0);
    $fecha = $_GET['fecha'] ?? '';
    $grupo_id = !empty($_GET['grupo_id']) ? (int)$_GET['grupo_id'] : null;

    $conditions = [];
    $params = [];
    $types = "";

    if ($rol === 'profesor' && $user_id) {
        $conditions[] = "a.usuario_id = ?";
        $params[] = $user_id;
        $types .= "i";
    }
    if (!empty($fecha)) {
        $conditions[] = "a.fecha = ?";
        $params[] = $fecha;
        $types .= "s";
    }
    if ($grupo_id) {
        $conditions[] = "a.grupo_id = ?";
        $params[] = $grupo_id;
        $types .= "i";
    }

    $where_sql = $conditions ? "WHERE " . implode(" AND ", $conditions) : "";

    $sql = "SELECT a.*, u.nombre AS profesor, g.nombre AS grupo, s.nombre_salon AS salon,
                   d.nombre_dia AS dia, bh.hora_inicio, bh.hora_fin
            FROM asistencias a
            INNER JOIN usuarios u ON a.usuario_id = u.id
            INNER JOIN grupos g ON a.grupo_id = g.id
            LEFT JOIN salones s ON a.salon_id = s.id
            LEFT JOIN dias d ON a.dia_id = d.id
            LEFT JOIN bloques_horarios bh ON a.bloque_id = bh.id
            $where_sql
            ORDER BY a.fecha DESC, d.id, bh.hora_inicio";

    $stmt = $conn->prepare($sql);
    if ($params) {
        $stmt->bind_param($types, ...$params);
    }
    $stmt->execute();
    $q = $stmt->get_result();

    echo "<table class='table table-bordered table-hover'>
        <thead class='table-dark text-center'>
            <tr><th>Fecha</th><th>Profesor</th><th>Grupo</th><th>Día</th><th>Horario</th><th>Salón</th><th>Estado</th><th>Justificación</th></tr>
        </thead><tbody>";

    if ($q->num_rows) {
        while ($r = $q->fetch_assoc()) {
            echo "<tr>
                <td>" . htmlspecialchars($r['fecha'] ?? '', ENT_QUOTES, 'UTF-8') . "</td>
                <td>" . htmlspecialchars($r['profesor'] ?? '', ENT_QUOTES, 'UTF-8') . "</td>
                <td>" . htmlspecialchars($r['grupo'] ?? '', ENT_QUOTES, 'UTF-8') . "</td>
                <td>" . htmlspecialchars($r['dia'] ?? '', ENT_QUOTES, 'UTF-8') . "</td>
                <td>" . ($r['hora_inicio'] ? htmlspecialchars("{$r['hora_inicio']} - {$r['hora_fin']}", ENT_QUOTES, 'UTF-8') : '') . "</td>
                <td>" . htmlspecialchars($r['salon'] ?? '', ENT_QUOTES, 'UTF-8') . "</td>
                <td>" . ($r['estado'] === 'asistio' ? 'Asistio' : 'Inasistencia') . "</td>
                <td>" . htmlspecialchars($r['justificacion'] ?? '', ENT_QUOTES, 'UTF-8') . "</td>
            </tr>";
        }
    } else {
        echo "<tr><td colspan='8' class='text-center'>Sin registros</td></tr>";
    }

    echo "</tbody></table>";
    exit;
}

// ===================
// GUARDAR ASISTENCIA
// ===================
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();
    $usuario_id = (int)($_POST['usuario_id'] ?? 0);
    $grupo_id = !empty($_POST['grupo_id']) ? (int)$_POST['grupo_id'] : null;
    $fecha = $_POST['fecha'] ?? '';
    $estado = $_POST['estado'] ?? '';
    $salon_id = !empty($_POST['salon_id']) ? (int)$_POST['salon_id'] : null;
    $justificacion = $_POST['justificacion'] ?? '';

    $dia_id = null;
    $bloque_id = null;
    if (!empty($_POST['horario_id'])) {
        $h_id = (int)$_POST['horario_id'];
        $stmt_h = $conn->prepare("SELECT dia_id, bloque_id FROM horarios WHERE id = ?");
        $stmt_h->bind_param("i", $h_id);
        $stmt_h->execute();
        $h_res = $stmt_h->get_result();
        if ($h_row = $h_res->fetch_assoc()) {
            $dia_id = $h_row['dia_id'];
            $bloque_id = $h_row['bloque_id'];
        }
        $stmt_h->close();
    }

    $stmt = $conn->prepare("INSERT INTO asistencias (usuario_id, grupo_id, fecha, estado, justificacion, dia_id, bloque_id, salon_id) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("issssiii", $usuario_id, $grupo_id, $fecha, $estado, $justificacion, $dia_id, $bloque_id, $salon_id);

    if ($stmt->execute()) {
        echo json_encode(["status" => "success", "message" => "Asistencia registrada correctamente."]);
    } else {
        app_log('error', 'Error guardando asistencia', ['error' => $stmt->error]);
echo json_encode(["status" => "danger", "message" => "Error al guardar la asistencia"]);
    }
}
