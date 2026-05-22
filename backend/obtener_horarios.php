<?php
require_once __DIR__ . '/db_connection.php';

$profesor_id = $_GET['profesor_id'] ?? null;
$grupo_id = $_GET['grupo_id'] ?? null;

if (!$profesor_id || !$grupo_id) {
    echo json_encode([]);
    exit;
}

$sql = "
    SELECT h.id, d.nombre_dia AS dia, TIME_FORMAT(bh.hora_inicio, '%H:%i') AS hora_inicio,
           TIME_FORMAT(bh.hora_fin, '%H:%i') AS hora_fin
    FROM horarios h
    JOIN dias d ON h.dia_id = d.id
    JOIN bloques_horarios bh ON h.bloque_id = bh.id
    WHERE h.profesor_id = '$profesor_id' AND h.grupo_id = '$grupo_id'
    ORDER BY d.id, bh.hora_inicio
";
$result = $conn->query($sql);

$data = [];
while ($row = $result->fetch_assoc()) $data[] = $row;

header('Content-Type: application/json');
echo json_encode($data);
