<?php
require_once __DIR__ . '/db_connection.php';
require_once __DIR__ . '/helpers.php';

require_auth();

$profesor_id = (int)($_GET['profesor_id'] ?? 0);
if (!$profesor_id) {
    echo json_encode([]);
    exit;
}

$sql = "SELECT g.id, g.nombre
        FROM grupos g
        INNER JOIN grupos_profesores gp ON gp.grupo_id = g.id
        WHERE gp.profesor_id = ?
        ORDER BY g.nombre";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $profesor_id);
$stmt->execute();
$result = $stmt->get_result();

$data = [];
while ($row = $result->fetch_assoc()) {
    $data[] = $row;
}

header('Content-Type: application/json');
echo json_encode($data);
