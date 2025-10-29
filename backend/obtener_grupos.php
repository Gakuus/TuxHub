<?php
require_once __DIR__ . '/db_connection.php';

$profesor_id = $_GET['profesor_id'] ?? null;
if (!$profesor_id) {
    echo json_encode([]);
    exit;
}

$sql = "
    SELECT g.id, g.nombre
    FROM grupos g
    INNER JOIN grupos_profesores gp ON gp.grupo_id = g.id
    WHERE gp.profesor_id = '$profesor_id'
    ORDER BY g.nombre
";
$result = $conn->query($sql);

$data = [];
while ($row = $result->fetch_assoc()) {
    $data[] = $row;
}

header('Content-Type: application/json');
echo json_encode($data);
