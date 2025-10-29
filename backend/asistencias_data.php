<?php
require_once __DIR__ . '/db_connection.php';
session_start();

$rol = $_SESSION['rol'] ?? null;
$user_id = $_SESSION['user_id'] ?? null;

$accion = $_GET['accion'] ?? '';
header('Content-Type: application/json');

switch ($accion) {

    case 'grupos':
        $profesor_id = $_GET['profesor_id'] ?? null;
        if ($rol === 'admin' && !$profesor_id) {
            $sql = "SELECT id, nombre FROM grupos ORDER BY nombre";
        } else {
            $id = $profesor_id ?: $user_id;
            $sql = "
                SELECT g.id, g.nombre
                FROM grupos g
                INNER JOIN grupos_profesores gp ON gp.grupo_id = g.id
                WHERE gp.profesor_id = '$id'
                ORDER BY g.nombre
            ";
        }
        $res = $conn->query($sql);
        echo json_encode($res->fetch_all(MYSQLI_ASSOC));
        break;

    case 'horarios':
        $grupo_id = $_GET['grupo_id'] ?? null;
        $profesor_id = $_GET['profesor_id'] ?? null;
        if (!$grupo_id || !$profesor_id) {
            echo json_encode([]);
            exit;
        }
        $sql = "
            SELECT id, dia, hora_inicio, hora_fin
            FROM horarios
            WHERE grupo_id='$grupo_id' AND profesor_id='$profesor_id'
            ORDER BY FIELD(dia,'Lunes','Martes','Miércoles','Jueves','Viernes'), hora_inicio
        ";
        $res = $conn->query($sql);
        echo json_encode($res->fetch_all(MYSQLI_ASSOC));
        break;

    case 'historial':
        $where = [];
        if ($rol === 'profesor') $where[] = "a.usuario_id='$user_id'";
        if ($rol === 'alumno') {
            $where[] = "a.usuario_id IN (
                SELECT gp.profesor_id
                FROM grupos_profesores gp
                INNER JOIN alumnos_grupos ag ON ag.grupo_id = gp.grupo_id
                WHERE ag.alumno_id = '$user_id'
            )";
        }
        $where_sql = $where ? "WHERE " . implode(" AND ", $where) : "";

        $sql = "
            SELECT a.*, u.nombre AS profesor, g.nombre AS grupo, s.nombre_salon AS salon,
                   h.hora_inicio, h.hora_fin, h.dia
            FROM asistencias a
            INNER JOIN usuarios u ON a.usuario_id = u.id
            INNER JOIN grupos g ON a.grupo_id = g.id
            LEFT JOIN salones s ON a.salon_id = s.id
            LEFT JOIN horarios h ON a.horario_id = h.id
            $where_sql
            ORDER BY a.fecha DESC, h.dia, h.hora_inicio
        ";
        $res = $conn->query($sql);
        echo json_encode($res->fetch_all(MYSQLI_ASSOC));
        break;
}
