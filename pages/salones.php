<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/../backend/db_connection.php';

// ==============================
// 0. Validar sesión
// ==============================
if (!isset($_SESSION['user_id'])) {
    header("Location: ../index.html?error=requerido");
    exit();
}

$user_id = (int)$_SESSION['user_id'];
$rol = strtolower(trim($_SESSION['rol'] ?? 'invitado'));
$user_name = $_SESSION['user_name'] ?? 'Usuario';
$recursos_disponibles = ['television', 'computadoras', 'pizarra', 'proyector', 'aire_acondicionado'];

// ==============================
// 1. Obtener grupos del profesor o admin
// ==============================
$grupos_profesor = [];
$grupo_seleccionado = null;
$horarios_grupo = [];

if (in_array($rol, ['profesor', 'admin', 'administrador'])) {

    $sql_grupos = "
        SELECT g.id, g.nombre, g.turno
        FROM grupos g
        INNER JOIN grupos_profesores gp ON gp.grupo_id = g.id
        WHERE gp.profesor_id = ?
        ORDER BY g.turno, g.nombre
    ";
    $stmt = $conn->prepare($sql_grupos);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $grupos_profesor = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();

    // Guardar selección de grupo
    if (isset($_POST['grupo_id'])) {
        $_SESSION['grupo_seleccionado'] = (int)$_POST['grupo_id'];
    }
    $grupo_seleccionado = $_SESSION['grupo_seleccionado'] ?? ($grupos_profesor[0]['id'] ?? null);

    // ==============================
    // 2. Obtener horarios del grupo (puede tener varios bloques)
    // ==============================
    if ($grupo_seleccionado) {
        $sql_h = "
            SELECT 
                h.id,
                d.nombre_dia AS dia,
                b.hora_inicio,
                b.hora_fin,
                m.nombre_materia AS materia,
                s.nombre_salon
            FROM horarios h
            INNER JOIN dias d ON h.dia_id = d.id
            INNER JOIN bloques_horarios b ON h.bloque_id = b.id
            INNER JOIN materias m ON h.materia_id = m.id
            LEFT JOIN salones s ON h.salon_id = s.id
            WHERE h.grupo_id = ? AND h.profesor_id = ?
            ORDER BY d.id, b.hora_inicio
        ";
        $stmt = $conn->prepare($sql_h);
        $stmt->bind_param("ii", $grupo_seleccionado, $user_id);
        $stmt->execute();
        $horarios_grupo = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
    }
}

// ==============================
// 3. Procesar acciones POST
// ==============================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['accion'], $_POST['salon_id'])) {
    $salon_id = (int)$_POST['salon_id'];
    $accion = $_POST['accion'];

    if (!in_array($rol, ['profesor', 'admin', 'administrador'])) exit();

    // === MARCAR EN USO ===
    if ($accion === 'marcar_uso') {
        $grupo_id = (int)($_POST['grupo_id'] ?? 0);
        $bloques = $_POST['bloques'] ?? [];

        // Validar que haya al menos un bloque seleccionado
        if (empty($bloques)) {
            $_SESSION['error'] = "Debes seleccionar al menos un bloque horario.";
            echo "<script>window.location.href = '" . $_SERVER['PHP_SELF'] . "';</script>";
            exit();
        }

        foreach ($bloques as $horario_id) {
            $hora_inicio = null;
            $hora_fin = null;

            $stmt = $conn->prepare("
                SELECT b.hora_inicio, b.hora_fin 
                FROM horarios h
                INNER JOIN bloques_horarios b ON h.bloque_id = b.id
                WHERE h.id = ?
            ");
            $stmt->bind_param("i", $horario_id);
            $stmt->execute();
            $stmt->bind_result($hora_inicio, $hora_fin);
            $stmt->fetch();
            $stmt->close();

            if ($hora_inicio && $hora_fin) {
                $stmt = $conn->prepare("
                    INSERT INTO salon_usos (salon_id, profesor_id, grupo_id, fecha, hora_inicio, hora_fin, estado)
                    VALUES (?, ?, ?, CURDATE(), ?, ?, 'en_uso')
                ");
                $stmt->bind_param("iiiss", $salon_id, $user_id, $grupo_id, $hora_inicio, $hora_fin);
                $stmt->execute();
                $stmt->close();
            }
        }

        $conn->query("UPDATE salones SET estado='ocupado' WHERE id=$salon_id");
        
        // Recargar con JavaScript
        echo "<script>window.location.href = '" . $_SERVER['PHP_SELF'] . "';</script>";
        exit();
    }

    // === LIBERAR (profesor) ===
    if ($accion === 'liberar') {
        $stmt = $conn->prepare("
            UPDATE salon_usos 
            SET estado='finalizado'
            WHERE salon_id=? AND profesor_id=? AND estado='en_uso'
        ");
        $stmt->bind_param("ii", $salon_id, $user_id);
        $stmt->execute();
        $stmt->close();

        $conn->query("UPDATE salones SET estado='disponible' WHERE id=$salon_id");
        
        // Recargar con JavaScript
        echo "<script>window.location.href = '" . $_SERVER['PHP_SELF'] . "';</script>";
        exit();
    }

    // === DESMARCAR USO (admin) ===
    if ($accion === 'desmarcar_uso' && in_array($rol, ['admin','administrador'])) {
        // Finalizar todos los usos activos del salón
        $stmt = $conn->prepare("
            UPDATE salon_usos 
            SET estado='finalizado'
            WHERE salon_id=? AND estado='en_uso'
        ");
        $stmt->bind_param("i", $salon_id);
        $stmt->execute();
        $stmt->close();

        // Cambiar estado del salón a disponible
        $conn->query("UPDATE salones SET estado='disponible' WHERE id=$salon_id");
        
        // Recargar con JavaScript
        echo "<script>window.location.href = '" . $_SERVER['PHP_SELF'] . "';</script>";
        exit();
    }

    // === EDITAR SALÓN (solo admins) ===
    if ($accion === 'editar' && in_array($rol, ['admin','administrador'])) {
        $nombre = trim($_POST['nombre_salon']);
        $capacidad = (int)$_POST['capacidad'];
        $ubicacion = trim($_POST['ubicacion']);
        $observaciones = trim($_POST['observaciones']);
        $recursos_sel = $_POST['recursos'] ?? [];

        $recursos_array = array_fill_keys($recursos_disponibles, false);
        foreach ($recursos_sel as $r) {
            if (isset($recursos_array[$r])) $recursos_array[$r] = true;
        }
        $recursos_json = json_encode($recursos_array);

        $stmt = $conn->prepare("
            UPDATE salones 
            SET nombre_salon=?, capacidad=?, ubicacion=?, observaciones=?, recursos=? 
            WHERE id=?
        ");
        $stmt->bind_param("sisssi", $nombre, $capacidad, $ubicacion, $observaciones, $recursos_json, $salon_id);
        $stmt->execute();
        $stmt->close();
        
        // Recargar con JavaScript
        echo "<script>window.location.href = '" . $_SERVER['PHP_SELF'] . "';</script>";
        exit();
    }

    // === ELIMINAR SALÓN (solo admins) ===
    if ($accion === 'eliminar' && in_array($rol, ['admin','administrador'])) {
        // Verificar si hay usos activos antes de eliminar
        $stmt = $conn->prepare("
            SELECT COUNT(*) as usos_activos 
            FROM salon_usos 
            WHERE salon_id=? AND estado='en_uso'
        ");
        $stmt->bind_param("i", $salon_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $usos_activos = $result->fetch_assoc()['usos_activos'];
        $stmt->close();

        if ($usos_activos > 0) {
            $_SESSION['error'] = "No se puede eliminar el salón porque tiene usos activos. Primero desmarca el uso.";
            echo "<script>window.location.href = '" . $_SERVER['PHP_SELF'] . "';</script>";
            exit();
        } else {
            // Eliminar registros relacionados en salon_usos
            $stmt = $conn->prepare("DELETE FROM salon_usos WHERE salon_id=?");
            $stmt->bind_param("i", $salon_id);
            $stmt->execute();
            $stmt->close();

            // Eliminar el salón
            $stmt = $conn->prepare("DELETE FROM salones WHERE id=?");
            $stmt->bind_param("i", $salon_id);
            $stmt->execute();
            $stmt->close();
            
            // Recargar con JavaScript
            echo "<script>window.location.href = '" . $_SERVER['PHP_SELF'] . "';</script>";
            exit();
        }
    }
}

// ==============================
// 4. Consulta principal de salones (último uso activo)
// ==============================
$sql = "
    SELECT 
        s.id AS salon_id,
        s.nombre_salon,
        s.capacidad,
        s.recursos,
        s.estado,
        s.ubicacion,
        s.observaciones,
        g.nombre AS grupo_actual,
        u.nombre AS profesor_actual,
        su.hora_inicio,
        su.hora_fin
    FROM salones s
    LEFT JOIN (
        SELECT su1.*
        FROM salon_usos su1
        WHERE su1.estado='en_uso'
    ) su ON su.salon_id = s.id
    LEFT JOIN grupos g ON su.grupo_id = g.id
    LEFT JOIN usuarios u ON su.profesor_id = u.id
    ORDER BY s.nombre_salon
";
$result = $conn->query($sql);
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title>Gestión de Salones</title>
<style>
body{font-family:"Segoe UI",Arial,sans-serif;background:#f0f2f5;margin:0;padding:20px;}
h1{text-align:center;margin-bottom:18px;}
.selector-grupo{text-align:center;margin-bottom:18px;}
.selector-grupo select{padding:8px;border-radius:6px;border:1px solid #ccc;}
.cards{display:grid;grid-template-columns:repeat(auto-fit,minmax(320px,1fr));gap:24px;max-width:1300px;margin:0 auto;}
.card{background:#fff;border-radius:12px;padding:18px;box-shadow:0 4px 10px rgba(0,0,0,.08);transition:.15s;position:relative;}
.card:hover{transform:translateY(-4px);box-shadow:0 8px 18px rgba(0,0,0,.12);}
.estado{font-weight:700;padding:6px 10px;border-radius:6px;display:inline-block;margin-bottom:10px;}
.disponible{background:#d4edda;color:#155724;}
.ocupado{background:#f8d7da;color:#721c24;}
button{margin-top:8px;padding:8px 12px;border:none;border-radius:6px;cursor:pointer;font-weight:700;}
button.marcar{background:#007bff;color:#fff;}
button.liberar{background:#28a745;color:#fff;}
button.desmarcar{background:#6c757d;color:#fff;}
button.editar{background:#ffc107;color:#000;}
button.eliminar{background:#dc3545;color:#fff;}
form.edit-form{margin-top:12px;background:#f8f9fa;padding:10px;border-radius:8px;}
input,textarea,select{width:100%;padding:8px;margin-bottom:8px;border:1px solid #ccc;border-radius:6px;}
.checkbox-group{display:flex;flex-wrap:wrap;gap:8px;}
.checkbox-group label{background:#e9ecef;padding:6px 8px;border-radius:6px;cursor:pointer;}
.admin-actions{margin-top:12px;padding-top:12px;border-top:1px solid #dee2e6;}
@media(max-width:600px){.cards{grid-template-columns:1fr;}}
</style>
</head>
<body>
<h1>Gestión de Salones</h1>

<?php if (!empty($_SESSION['error'])): ?>
    <p style="color:red;text-align:center;"><?= $_SESSION['error']; unset($_SESSION['error']); ?></p>
<?php endif; ?>

<?php if (!empty($_SESSION['success'])): ?>
    <p style="color:green;text-align:center;"><?= $_SESSION['success']; unset($_SESSION['success']); ?></p>
<?php endif; ?>

<?php if (in_array($rol, ['profesor','admin','administrador'])): ?>
<div class="selector-grupo">
    <form method="POST" style="display:inline-block;">
        <label><strong>Seleccionar grupo:</strong></label>
        <select name="grupo_id" onchange="this.form.submit()">
            <?php foreach ($grupos_profesor as $g): ?>
                <option value="<?= $g['id'] ?>" <?= $g['id']==$grupo_seleccionado?'selected':'' ?>>
                    <?= htmlspecialchars($g['nombre']) ?> (<?= ucfirst($g['turno']) ?>)
                </option>
            <?php endforeach; ?>
        </select>
    </form>
</div>
<?php endif; ?>

<div class="cards">
<?php while ($row = $result->fetch_assoc()): 
    $recursos_json = $row['recursos'] ?? '';
    $recursos_list = '—';
    if ($recursos_json) {
        $decoded = json_decode($recursos_json,true);
        if (is_array($decoded)) $recursos_list = implode(', ', array_keys(array_filter($decoded)));
    }
?>
<div class="card">
    <h2><?= htmlspecialchars($row['nombre_salon']) ?></h2>
    <div class="estado <?= $row['estado'] ?>"><?= ucfirst($row['estado']) ?></div>
    <p><strong>Capacidad:</strong> <?= $row['capacidad'] ?></p>
    <p><strong>Ubicación:</strong> <?= htmlspecialchars($row['ubicacion'] ?? '-') ?></p>
    <p><strong>Grupo actual:</strong> <?= htmlspecialchars($row['grupo_actual'] ?? '-') ?></p>
    <p><strong>Profesor:</strong> <?= htmlspecialchars($row['profesor_actual'] ?? '-') ?></p>
    <p><strong>Horario:</strong> <?= htmlspecialchars($row['hora_inicio'] ?? '-') ?> <?= $row['hora_fin'] ? ' - ' . htmlspecialchars($row['hora_fin']) : '' ?></p>
    <p><strong>Recursos:</strong> <?= htmlspecialchars($recursos_list) ?></p>

    <?php if (in_array($rol, ['profesor','admin','administrador'])): ?>
        <!-- Acciones para profesores y admins -->
        <form method="POST" style="margin-top:10px;">
            <input type="hidden" name="salon_id" value="<?= $row['salon_id'] ?>">
            <input type="hidden" name="accion" value="marcar_uso">
            <?php if ($grupo_seleccionado): ?>
                <input type="hidden" name="grupo_id" value="<?= $grupo_seleccionado ?>">
                <?php if (count($horarios_grupo)>0): ?>
                    <label><strong>Seleccionar bloques horarios:</strong></label>
                    <select name="bloques[]" multiple size="4" required>
                        <?php foreach ($horarios_grupo as $h): ?>
                            <option value="<?= $h['id'] ?>">
                                <?= htmlspecialchars($h['dia']) ?> — <?= substr($h['hora_inicio'],0,5) ?> a <?= substr($h['hora_fin'],0,5) ?> — <?= htmlspecialchars($h['materia']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                <?php else: ?>
                    <p style="color:#888;">No hay horarios cargados para este grupo.</p>
                <?php endif; ?>
            <?php endif; ?>

            <?php if ($row['estado'] === 'disponible'): ?>
                <button type="submit" class="marcar">Marcar en uso</button>
            <?php elseif ($row['estado'] === 'ocupado' && $row['profesor_actual'] === $user_name): ?>
                <button type="submit" name="accion" value="liberar" class="liberar">Liberar</button>
            <?php endif; ?>
        </form>

        <?php if (in_array($rol, ['admin','administrador'])): ?>
        <!-- Acciones exclusivas para administradores -->
        <div class="admin-actions">
            <!-- Desmarcar uso (solo cuando está ocupado) -->
            <?php if ($row['estado'] === 'ocupado'): ?>
                <form method="POST" style="display:inline-block; margin-right:8px;">
                    <input type="hidden" name="salon_id" value="<?= $row['salon_id'] ?>">
                    <input type="hidden" name="accion" value="desmarcar_uso">
                    <button type="submit" class="desmarcar" onclick="return confirm('¿Estás seguro de que quieres desmarcar el uso de este salón?')">
                        Desmarcar Uso
                    </button>
                </form>
            <?php endif; ?>

            <!-- Eliminar salón -->
            <form method="POST" style="display:inline-block;">
                <input type="hidden" name="salon_id" value="<?= $row['salon_id'] ?>">
                <input type="hidden" name="accion" value="eliminar">
                <button type="submit" class="eliminar" onclick="return confirm('¿Estás seguro de que quieres ELIMINAR este salón? Esta acción no se puede deshacer.')">
                    Eliminar Salón
                </button>
            </form>

            <!-- Editar salón -->
            <form method="POST" class="edit-form">
                <input type="hidden" name="salon_id" value="<?= $row['salon_id'] ?>">
                <input type="hidden" name="accion" value="editar">
                <input type="text" name="nombre_salon" value="<?= htmlspecialchars($row['nombre_salon']) ?>" placeholder="Nombre" required>
                <input type="number" name="capacidad" value="<?= $row['capacidad'] ?>" placeholder="Capacidad" required min="1">
                <input type="text" name="ubicacion" value="<?= htmlspecialchars($row['ubicacion']) ?>" placeholder="Ubicación" required>
                <textarea name="observaciones" placeholder="Observaciones"><?= htmlspecialchars($row['observaciones'] ?? '') ?></textarea>
                <div class="checkbox-group">
                    <?php foreach ($recursos_disponibles as $r): ?>
                        <label><input type="checkbox" name="recursos[]" value="<?= $r ?>" <?= strpos($recursos_list,$r)!==false?'checked':'' ?>> <?= ucfirst($r) ?></label>
                    <?php endforeach; ?>
                </div>
                <button type="submit" class="editar">Guardar cambios</button>
            </form>
        </div>
        <?php endif; ?>
    <?php endif; ?>
</div>
<?php endwhile; ?>
</div>
</body>
</html>