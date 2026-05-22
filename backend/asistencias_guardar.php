<?php
require_once __DIR__ . '/db_connection.php';
require_once __DIR__ . '/helpers.php';

require_auth();
csrf_verify();

$usuario_id = (int)($_POST['usuario_id'] ?? 0);
$fecha = $_POST['fecha'] ?? '';
$grupo_id = !empty($_POST['grupo_id']) ? (int)$_POST['grupo_id'] : null;
$estado = $_POST['estado'] ?? '';
$salon_id = !empty($_POST['salon_id']) ? (int)$_POST['salon_id'] : null;
$justificacion = $_POST['justificacion'] ?? '';

if ($estado === 'inasistencia') {
    $grupo_id = $salon_id = null;
}

$sql = "INSERT INTO asistencias (usuario_id, grupo_id, fecha, estado, salon_id, justificacion) VALUES (?, ?, ?, ?, ?, ?)";
$stmt = $conn->prepare($sql);
$stmt->bind_param("iissss", $usuario_id, $grupo_id, $fecha, $estado, $salon_id, $justificacion);

if ($stmt->execute()) {
    echo json_encode(['ok' => true, 'msg' => 'Asistencia registrada correctamente.']);
} else {
    echo json_encode(['ok' => false, 'msg' => 'Error al guardar: '.$stmt->error]);
}
