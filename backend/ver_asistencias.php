<?php
session_start();
require_once __DIR__ . '/db_connection.php';

$rol = strtolower($_SESSION['rol'] ?? '');
$user_id = (int)($_SESSION['user_id'] ?? 0);

if ($rol === 'admin') {
    $sql = "
        SELECT a.id, u.nombre AS profesor, g.nombre AS grupo,
               d.nombre_dia AS dia_horario,
               CONCAT(bh.hora_inicio, ' - ', bh.hora_fin) AS horario, s.nombre_salon AS salon, a.estado, a.justificacion,
               GROUP_CONCAT(DISTINCT sr.recurso SEPARATOR ', ') AS recursos
        FROM asistencias a
        LEFT JOIN usuarios u ON a.usuario_id = u.id
        LEFT JOIN grupos g ON a.grupo_id = g.id
        LEFT JOIN salones s ON a.salon_id = s.id
        LEFT JOIN dias d ON a.dia_id = d.id
        LEFT JOIN bloques_horarios bh ON a.bloque_id = bh.id
        LEFT JOIN salon_recursos sr ON s.id = sr.salon_id
        ORDER BY a.fecha DESC
    ";
    $res = $conn->query($sql);
} elseif ($rol === 'profesor') {
    $sql = "
        SELECT a.id, g.nombre AS grupo,
               d.nombre_dia AS dia_horario,
               CONCAT(bh.hora_inicio, ' - ', bh.hora_fin) AS horario, s.nombre_salon AS salon, a.estado, a.justificacion,
               GROUP_CONCAT(DISTINCT sr.recurso SEPARATOR ', ') AS recursos
        FROM asistencias a
        LEFT JOIN grupos g ON a.grupo_id = g.id
        LEFT JOIN salones s ON a.salon_id = s.id
        LEFT JOIN dias d ON a.dia_id = d.id
        LEFT JOIN bloques_horarios bh ON a.bloque_id = bh.id
        LEFT JOIN salon_recursos sr ON s.id = sr.salon_id
        WHERE a.usuario_id = ?
        ORDER BY a.fecha DESC
    ";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('i', $user_id);
    $stmt->execute();
    $res = $stmt->get_result();
    $stmt->close();
} else { // alumno
    $sql = "
        SELECT u.nombre AS profesor, g.nombre AS grupo, a.dia, a.estado, a.justificacion
        FROM asistencias a
        INNER JOIN grupos g ON a.grupo_id = g.id
        INNER JOIN usuarios u ON a.usuario_id = u.id
        INNER JOIN alumnos_grupos ag ON g.id = ag.grupo_id
        WHERE ag.alumno_id = ?
        ORDER BY a.fecha DESC
    ";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('i', $user_id);
    $stmt->execute();
    $res = $stmt->get_result();
    $stmt->close();
}

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
    if ($rol === 'admin' || $rol === 'alumno') echo "<td>" . htmlspecialchars($row['profesor'] ?? '') . "</td>";
    echo "<td>" . htmlspecialchars($row['grupo'] ?? '') . "</td>
          <td>" . htmlspecialchars($row['dia_horario'] ?? '', ENT_QUOTES, 'UTF-8') . "</td>
          <td>" . htmlspecialchars($row['horario'] ?? '-') . "</td>
          <td>" . htmlspecialchars($row['salon'] ?? '-') . "</td>
          <td>" . htmlspecialchars($row['estado'] ?? '') . "</td>
          <td>" . htmlspecialchars($row['justificacion'] ?? '') . "</td>
          </tr>";
}
echo "</tbody></table>";
