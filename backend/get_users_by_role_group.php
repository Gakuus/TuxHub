<?php
// backend/get_users_by_role_group.php
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/db_connection.php';
$conn->set_charset('utf8mb4');

$raw = file_get_contents('php://input');
$data = json_decode($raw, true);

$role = $data['role'] ?? '';
$grupo_id = isset($data['grupo_id']) ? (int)$data['grupo_id'] : 0;

if (!$role || !$grupo_id) {
    echo json_encode(['error' => 'Faltan parámetros role o grupo_id.']);
    exit;
}

$allowed = ['profesor','alumno'];
if (!in_array($role, $allowed)) {
    echo json_encode(['error' => 'Role inválido.']);
    exit;
}

$sql = "SELECT id, nombre, COALESCE(cedula,'') AS cedula FROM usuarios WHERE rol = ? AND grupo_id = ? ORDER BY nombre ASC";
$stmt = $conn->prepare($sql);
if (!$stmt) {
    echo json_encode(['error' => 'Error prepare: ' . $conn->error]);
    exit;
}
$stmt->bind_param('si', $role, $grupo_id);
$stmt->execute();
$res = $stmt->get_result();
$users = [];
while ($u = $res->fetch_assoc()) $users[] = $u;

echo json_encode(['users' => $users]);
