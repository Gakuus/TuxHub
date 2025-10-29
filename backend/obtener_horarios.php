<?php
require_once __DIR__ . '/db_connection.php';

$profesor_id = $_GET['profesor_id'] ?? null;
$grupo_id = $_GET['grupo_id'] ?? null;

if (!$profesor_id || !$grupo_id) {
    echo json_encode([]);
    exit;
}

$sql = "
    SELECT id, dia, TIME_FORMAT(hora_inicio, '%H:%i') AS hora_inicio,
           TIME_FORMAT(hora_fin, '%H:%i') AS hora_fin
    FROM horarios
    WHERE profesor_id = '$profesor_id' AND grupo_id = '$grupo_id'
    ORDER BY FIELD(dia,'Lunes','Martes','Miércoles','Jueves','Viernes'), hora_inicio
";
$result = $conn->query($sql);

$data = [];
while ($row = $result->fetch_assoc()) $data[] = $row;

header('Content-Type: application/json');
echo json_encode($data);
