<?php
require_once __DIR__ . '/db_connection.php';
require_once __DIR__ . '/helpers.php';

require_auth();

$profesor_id = (int)($_GET['profesor_id'] ?? 0);
$grupo_id = (int)($_GET['grupo_id'] ?? 0);

if (!$profesor_id || !$grupo_id) {
    echo json_encode([]);
    exit;
}

$sql = "SELECT h.id, d.nombre_dia AS dia, TIME_FORMAT(bh.hora_inicio, '%H:%i') AS hora_inicio,
               TIME_FORMAT(bh.hora_fin, '%H:%i') AS hora_fin
        FROM horarios h
        JOIN dias d ON h.dia_id = d.id
        JOIN bloques_horarios bh ON h.bloque_id = bh.id
        WHERE h.profesor_id = ? AND h.grupo_id = ?
        ORDER BY d.id, bh.hora_inicio";
$stmt = $conn->prepare($sql);
$stmt->bind_param("ii", $profesor_id, $grupo_id);
$stmt->execute();
$result = $stmt->get_result();

$data = [];
while ($row = $result->fetch_assoc()) $data[] = $row;

header('Content-Type: application/json');
echo json_encode($data);
