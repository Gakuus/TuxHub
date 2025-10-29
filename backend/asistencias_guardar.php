<?php
require_once __DIR__ . '/db_connection.php';
session_start();

$usuario_id = $_POST['usuario_id'];
$fecha = $_POST['fecha'];
$grupo_id = $_POST['grupo_id'] ?: 'NULL';
$estado = $_POST['estado'];
$horario_id = $_POST['horario_id'] ?: 'NULL';
$salon_id = $_POST['salon_id'] ?: 'NULL';
$justificacion = $conn->real_escape_string($_POST['justificacion'] ?? '');

if ($estado === 'inasistencia') {
    $grupo_id = $horario_id = $salon_id = 'NULL';
}

$sql = "
INSERT INTO asistencias (usuario_id, grupo_id, fecha, estado, horario_id, salon_id, justificacion)
VALUES ('$usuario_id', $grupo_id, '$fecha', '$estado', $horario_id, $salon_id, '$justificacion')
";

if ($conn->query($sql)) {
    echo json_encode(['ok' => true, 'msg' => '✅ Asistencia registrada correctamente.']);
} else {
    echo json_encode(['ok' => false, 'msg' => '⚠️ Error al guardar: '.$conn->error]);
}
