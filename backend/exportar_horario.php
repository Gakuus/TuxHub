<?php
require_once __DIR__ . '/helpers.php';
require_once __DIR__ . '/db_connection.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: ../index.php?error=requerido');
    exit;
}

$rol = strtolower($_SESSION['rol'] ?? '');
$user_id = (int)$_SESSION['user_id'];

$grupo_id = (int)($_GET['grupo_id'] ?? 0);
$filtro_estado = $_GET['estado_materias'] ?? 'activas';

$conditions = ["h.activo = 1"];
$params = [];
$types = '';

if ($grupo_id > 0) {
    $conditions[] = "h.grupo_id = ?";
    $params[] = $grupo_id;
    $types .= 'i';
}

if ($rol === 'profesor') {
    $conditions[] = "h.profesor_id = ?";
    $params[] = $user_id;
    $types .= 'i';
}

// Filter by materia state
if ($filtro_estado === 'activas') {
    $conditions[] = "m.activa = 1";
} elseif ($filtro_estado === 'inactivas') {
    $conditions[] = "m.activa = 0";
}

$where = "WHERE " . implode(" AND ", $conditions);

$sql = "SELECT 
            g.nombre AS grupo,
            d.nombre_dia AS dia,
            bh.hora_inicio,
            bh.hora_fin,
            m.nombre_materia AS materia,
            u.nombre AS profesor,
            s.nombre_salon AS salon
        FROM horarios h
        INNER JOIN grupos g ON h.grupo_id = g.id
        INNER JOIN dias d ON h.dia_id = d.id
        INNER JOIN bloques_horarios bh ON h.bloque_id = bh.id
        INNER JOIN materias m ON h.materia_id = m.id
        LEFT JOIN usuarios u ON h.profesor_id = u.id
        LEFT JOIN salones s ON h.salon_id = s.id
        $where
        ORDER BY g.nombre, d.id, bh.hora_inicio";

$stmt = $conn->prepare($sql);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$result = $stmt->get_result();

$filename = 'horarios_' . date('Y-m-d') . '.csv';

header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Pragma: no-cache');
header('Expires: 0');

$output = fopen('php://output', 'w');

// BOM for Excel UTF-8 compatibility
fprintf($output, chr(0xEF) . chr(0xBB) . chr(0xBF));

fputcsv($output, ['Grupo', 'Día', 'Hora Inicio', 'Hora Fin', 'Materia', 'Profesor', 'Salón']);

while ($row = $result->fetch_assoc()) {
    fputcsv($output, [
        $row['grupo'],
        $row['dia'],
        $row['hora_inicio'],
        $row['hora_fin'],
        $row['materia'],
        $row['profesor'],
        $row['salon'],
    ]);
}

fclose($output);
exit;
