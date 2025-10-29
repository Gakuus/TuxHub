<?php
session_start();
require_once __DIR__ . '/db_connection.php';

$rol = strtolower($_SESSION['rol'] ?? '');
$user_id = $_SESSION['user_id'];

if ($rol === 'admin') {
    $query = "
        SELECT a.id, u.nombre AS profesor, g.nombre AS grupo, a.dia, h.dia AS dia_horario,
               CONCAT(h.hora_inicio, ' - ', h.hora_fin) AS horario, s.nombre AS salon, a.estado, a.justificacion
        FROM asistencias a
        LEFT JOIN usuarios u ON a.usuario_id = u.id
        LEFT JOIN grupos g ON a.grupo_id = g.id
        LEFT JOIN salones s ON a.salon_id = s.id
        LEFT JOIN horarios h ON a.horario_id = h.id
        ORDER BY a.fecha DESC
    ";
} elseif ($rol === 'profesor') {
    $query = "
        SELECT a.id, g.nombre AS grupo, a.dia, h.dia AS dia_horario,
               CONCAT(h.hora_inicio, ' - ', h.hora_fin) AS horario, s.nombre AS salon, a.estado, a.justificacion
        FROM asistencias a
        LEFT JOIN grupos g ON a.grupo_id = g.id
        LEFT JOIN salones s ON a.salon_id = s.id
        LEFT JOIN horarios h ON a.horario_id = h.id
        WHERE a.usuario_id = $user_id
        ORDER BY a.fecha DESC
    ";
} else { // alumno
    $query = "
        SELECT u.nombre AS profesor, g.nombre AS grupo, a.dia, a.estado, a.justificacion
        FROM asistencias a
        INNER JOIN grupos g ON a.grupo_id = g.id
        INNER JOIN usuarios u ON a.usuario_id = u.id
        INNER JOIN alumnos_grupos ag ON g.id = ag.grupo_id
        WHERE ag.alumno_id = $user_id
        ORDER BY a.fecha DESC
    ";
}

$res = $conn->query($query);
if (!$res || $res->num_rows === 0) {
    echo "<p class='text-center text-muted'>No hay asistencias registradas.</p>";
    exit;
}

echo "<table class='table table-striped table-hover'>
<thead>
<tr>";
if ($rol === 'admin' || $rol === 'alumno') echo "<th>Profesor</th>";
echo "<th>Grupo</th><th>Día</th><th>Horario</th><th>Salón</th><th>Estado</th><th>Justificación</th>
</tr></thead><tbody>";

while ($row = $res->fetch_assoc()) {
    echo "<tr>";
    if ($rol === 'admin' || $rol === 'alumno') echo "<td>{$row['profesor']}</td>";
    echo "<td>{$row['grupo']}</td>
          <td>{$row['dia'] ?? $row['dia_horario']}</td>
          <td>{$row['horario'] ?? '-'}</td>
          <td>{$row['salon'] ?? '-'}</td>
          <td>{$row['estado']}</td>
          <td>{$row['justificacion']}</td>
          </tr>";
}
echo "</tbody></table>";
