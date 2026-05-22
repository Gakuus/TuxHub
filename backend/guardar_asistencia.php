<?php
require_once __DIR__ . '/db_connection.php';
session_start();

// ===================
// MOSTRAR HISTORIAL
// ===================
if (isset($_GET['historial'])) {
    $rol = $_SESSION['rol'] ?? null;
    $user_id = $_SESSION['user_id'] ?? null;
    $fecha = $_GET['fecha'] ?? '';
    $grupo_id = $_GET['grupo_id'] ?? '';

    $where = [];
    if ($rol === 'profesor') $where[] = "a.usuario_id = '$user_id'";
    if (!empty($fecha)) $where[] = "a.fecha = '$fecha'";
    if (!empty($grupo_id)) $where[] = "a.grupo_id = '$grupo_id'";
    $where_sql = $where ? "WHERE " . implode(" AND ", $where) : "";

    $q = $conn->query("
        SELECT a.*, u.nombre AS profesor, g.nombre AS grupo, s.nombre_salon AS salon,
               d.nombre_dia AS dia, bh.hora_inicio, bh.hora_fin
        FROM asistencias a
        INNER JOIN usuarios u ON a.usuario_id = u.id
        INNER JOIN grupos g ON a.grupo_id = g.id
        LEFT JOIN salones s ON a.salon_id = s.id
        LEFT JOIN dias d ON a.dia_id = d.id
        LEFT JOIN bloques_horarios bh ON a.bloque_id = bh.id
        $where_sql
        ORDER BY a.fecha DESC, d.id, bh.hora_inicio
    ");

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
                <td>" . ($r['estado'] === 'asistio' ? '✅ Asistió' : '❌ Inasistencia') . "</td>
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
    $usuario_id = $_POST['usuario_id'] ?? '';
    $grupo_id = $_POST['grupo_id'] ?? '';
    $fecha = $_POST['fecha'] ?? '';
    $estado = $_POST['estado'] ?? '';
    $salon_id = $_POST['salon_id'] ?: 'NULL';
    $justificacion = $conn->real_escape_string($_POST['justificacion'] ?? '');

    $dia_id = 'NULL';
    $bloque_id = 'NULL';
    if (!empty($_POST['horario_id'])) {
        $h_id = (int)$_POST['horario_id'];
        $h_res = $conn->query("SELECT dia_id, bloque_id FROM horarios WHERE id = $h_id");
        if ($h_row = $h_res->fetch_assoc()) {
            $dia_id = $h_row['dia_id'];
            $bloque_id = $h_row['bloque_id'];
        }
    }

    $sql = "
        INSERT INTO asistencias (usuario_id, grupo_id, fecha, estado, justificacion, dia_id, bloque_id, salon_id)
        VALUES ('$usuario_id', '$grupo_id', '$fecha', '$estado', '$justificacion', $dia_id, $bloque_id, $salon_id)
    ";

    if ($conn->query($sql)) {
        echo json_encode(["status" => "success", "message" => "✅ Asistencia registrada correctamente."]);
    } else {
        echo json_encode(["status" => "danger", "message" => "⚠️ Error: " . $conn->error]);
    }
}
