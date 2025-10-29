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
               h.hora_inicio, h.hora_fin, h.dia
        FROM asistencias a
        INNER JOIN usuarios u ON a.usuario_id = u.id
        INNER JOIN grupos g ON a.grupo_id = g.id
        LEFT JOIN salones s ON a.salon_id = s.id
        LEFT JOIN horarios h ON a.horario_id = h.id
        $where_sql
        ORDER BY a.fecha DESC, h.dia, h.hora_inicio
    ");

    echo "<table class='table table-bordered table-hover'>
        <thead class='table-dark text-center'>
            <tr><th>Fecha</th><th>Profesor</th><th>Grupo</th><th>Día</th><th>Horario</th><th>Salón</th><th>Estado</th><th>Justificación</th></tr>
        </thead><tbody>";

    if ($q->num_rows) {
        while ($r = $q->fetch_assoc()) {
            echo "<tr>
                <td>{$r['fecha']}</td>
                <td>{$r['profesor']}</td>
                <td>{$r['grupo']}</td>
                <td>{$r['dia']}</td>
                <td>".($r['hora_inicio'] ? "{$r['hora_inicio']} - {$r['hora_fin']}" : "")."</td>
                <td>{$r['salon']}</td>
                <td>".($r['estado']==='asistio'?'✅ Asistió':'❌ Inasistencia')."</td>
                <td>{$r['justificacion']}</td>
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
    $horario_id = $_POST['horario_id'] ?: 'NULL';
    $salon_id = $_POST['salon_id'] ?: 'NULL';
    $justificacion = $conn->real_escape_string($_POST['justificacion'] ?? '');

    $sql = "
        INSERT INTO asistencias (usuario_id, grupo_id, fecha, estado, horario_id, salon_id, justificacion)
        VALUES ('$usuario_id', '$grupo_id', '$fecha', '$estado', $horario_id, $salon_id, '$justificacion')
    ";

    if ($conn->query($sql)) {
        echo json_encode(["status" => "success", "message" => "✅ Asistencia registrada correctamente."]);
    } else {
        echo json_encode(["status" => "danger", "message" => "⚠️ Error: " . $conn->error]);
    }
}
