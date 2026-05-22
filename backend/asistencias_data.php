<?php
require_once __DIR__ . '/db_connection.php';
require_once __DIR__ . '/helpers.php';

require_auth();

$rol = $_SESSION['rol'] ?? null;
$user_id = (int)($_SESSION['user_id'] ?? 0);

$accion = $_GET['accion'] ?? '';
header('Content-Type: application/json');

switch ($accion) {

    case 'grupos':
        $profesor_id = (int)($_GET['profesor_id'] ?? 0);
        if ($rol === 'admin' && !$profesor_id) {
            $res = $conn->query("SELECT id, nombre FROM grupos ORDER BY nombre");
        } else {
            $id = $profesor_id ?: $user_id;
            $stmt = $conn->prepare("SELECT g.id, g.nombre FROM grupos g INNER JOIN grupos_profesores gp ON gp.grupo_id = g.id WHERE gp.profesor_id = ? ORDER BY g.nombre");
            $stmt->bind_param("i", $id);
            $stmt->execute();
            $res = $stmt->get_result();
        }
        echo json_encode($res->fetch_all(MYSQLI_ASSOC));
        break;

    case 'horarios':
        $grupo_id = (int)($_GET['grupo_id'] ?? 0);
        $profesor_id = (int)($_GET['profesor_id'] ?? 0);
        if (!$grupo_id || !$profesor_id) {
            echo json_encode([]);
            exit;
        }
        $stmt = $conn->prepare("SELECT h.id, d.nombre_dia AS dia, bh.hora_inicio, bh.hora_fin FROM horarios h JOIN dias d ON h.dia_id = d.id JOIN bloques_horarios bh ON h.bloque_id = bh.id WHERE h.grupo_id = ? AND h.profesor_id = ? ORDER BY d.id, bh.hora_inicio");
        $stmt->bind_param("ii", $grupo_id, $profesor_id);
        $stmt->execute();
        $res = $stmt->get_result();
        echo json_encode($res->fetch_all(MYSQLI_ASSOC));
        break;

    case 'historial':
        $conditions = [];
        $params = [];
        $types = "";

        if ($rol === 'profesor' && $user_id) {
            $conditions[] = "a.usuario_id = ?";
            $params[] = $user_id;
            $types .= "i";
        }
        if ($rol === 'alumno' && $user_id) {
            $conditions[] = "a.usuario_id IN (SELECT gp.profesor_id FROM grupos_profesores gp INNER JOIN alumnos_grupos ag ON ag.grupo_id = gp.grupo_id WHERE ag.alumno_id = ?)";
            $params[] = $user_id;
            $types .= "i";
        }

        $where_sql = $conditions ? "WHERE " . implode(" AND ", $conditions) : "";

        $sql = "SELECT a.*, u.nombre AS profesor, g.nombre AS grupo, s.nombre_salon AS salon,
                       d.nombre_dia AS dia, bh.hora_inicio, bh.hora_fin
                FROM asistencias a
                INNER JOIN usuarios u ON a.usuario_id = u.id
                INNER JOIN grupos g ON a.grupo_id = g.id
                LEFT JOIN salones s ON a.salon_id = s.id
                LEFT JOIN dias d ON a.dia_id = d.id
                LEFT JOIN bloques_horarios bh ON a.bloque_id = bh.id
                $where_sql
                ORDER BY a.fecha DESC, d.id, bh.hora_inicio";

        $stmt = $conn->prepare($sql);
        if ($params) {
            $stmt->bind_param($types, ...$params);
        }
        $stmt->execute();
        $res = $stmt->get_result();
        echo json_encode($res->fetch_all(MYSQLI_ASSOC));
        break;
}
