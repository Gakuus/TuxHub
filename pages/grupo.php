<?php
session_start();
require_once __DIR__ . '/../backend/db_connection.php';

// Verificación de sesión
if (!isset($_SESSION['user_id'])) {
    header("Location: ../index.php?error=requerido");
    exit();
}

$rol = $_SESSION['rol'] ?? "Invitado";
$rol_lower = strtolower($rol);
$nombre = $_SESSION['user_name'] ?? "Usuario";

$mensaje = "";

// === VERIFICAR Y CREAR COLUMNA activa SI NO EXISTE ===
$check_column = $conn->query("SHOW COLUMNS FROM grupos LIKE 'activa'");
if ($check_column->num_rows == 0) {
    $conn->query("ALTER TABLE grupos ADD COLUMN activa TINYINT(1) DEFAULT 1");
    $conn->query("UPDATE grupos SET activa = 1");
    $mensaje = "<div class='alert alert-info'>✅ Se agregó la columna 'activa' a la tabla grupos.</div>";
}

// === DESACTIVAR GRUPO ===
if (isset($_GET['desactivar'])) {
    $id = (int) $_GET['desactivar'];
    
    // Verificar que el grupo existe
    $check = $conn->query("SELECT nombre FROM grupos WHERE id = $id");
    if ($check->num_rows > 0) {
        $grupo = $check->fetch_assoc();
        
        if ($conn->query("UPDATE grupos SET activa = 0 WHERE id = $id")) {
            $mensaje = "<div class='alert alert-warning'>✅ Grupo '{$grupo['nombre']}' desactivado correctamente.</div>";
            echo "<script>setTimeout(() => { window.location.href = 'dashboard.php?page=grupos'; }, 1000);</script>";
        } else {
            $mensaje = "<div class='alert alert-danger'>❌ Error al desactivar: " . htmlspecialchars($conn->error) . "</div>";
        }
    } else {
        $mensaje = "<div class='alert alert-danger'>❌ El grupo no existe.</div>";
    }
}

// === ACTIVAR GRUPO ===
if (isset($_GET['activar'])) {
    $id = (int) $_GET['activar'];
    
    // Verificar que el grupo existe
    $check = $conn->query("SELECT nombre FROM grupos WHERE id = $id");
    if ($check->num_rows > 0) {
        $grupo = $check->fetch_assoc();
        
        if ($conn->query("UPDATE grupos SET activa = 1 WHERE id = $id")) {
            $mensaje = "<div class='alert alert-success'>✅ Grupo '{$grupo['nombre']}' activado correctamente.</div>";
            echo "<script>setTimeout(() => { window.location.href = 'dashboard.php?page=grupos'; }, 1000);</script>";
        } else {
            $mensaje = "<div class='alert alert-danger'>❌ Error al activar: " . htmlspecialchars($conn->error) . "</div>";
        }
    } else {
        $mensaje = "<div class='alert alert-danger'>❌ El grupo no existe.</div>";
    }
}

// === AGREGAR GRUPO ===
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['nombre_grupo'])) {
    $nombre_grupo = trim($conn->real_escape_string($_POST['nombre_grupo'] ?? ''));
    $turno = trim($conn->real_escape_string($_POST['turno'] ?? ''));

    if (!empty($nombre_grupo) && !empty($turno)) {
        if (strlen($nombre_grupo) > 24) {
            $mensaje = "<div class='alert alert-danger'>⚠️ El nombre del grupo no puede superar los 24 caracteres.</div>";
        } else {
            // Verificar si el grupo existe (activo o inactivo)
            $check = $conn->query("SELECT id, activa FROM grupos WHERE nombre = '$nombre_grupo' AND turno = '$turno'");
            if ($check->num_rows > 0) {
                $grupo_existente = $check->fetch_assoc();
                // Si existe pero está inactivo, reactivarlo
                if ($grupo_existente['activa'] == 0) {
                    if ($conn->query("UPDATE grupos SET activa = 1 WHERE id = {$grupo_existente['id']}")) {
                        $mensaje = "<div class='alert alert-success'>✅ Grupo reactivado correctamente.</div>";
                        $_POST['nombre_grupo'] = '';
                    }
                } else {
                    $mensaje = "<div class='alert alert-warning'>⚠️ El grupo ya existe y está activo.</div>";
                }
            } else {
                // Crear nuevo grupo
                if ($conn->query("INSERT INTO grupos (nombre, turno, activa) VALUES ('$nombre_grupo', '$turno', 1)")) {
                    $mensaje = "<div class='alert alert-success'>✅ Grupo agregado correctamente.</div>";
                    $_POST['nombre_grupo'] = '';
                } else {
                    $mensaje = "<div class='alert alert-danger'>❌ Error al insertar: " . htmlspecialchars($conn->error) . "</div>";
                }
            }
        }
    } else {
        $mensaje = "<div class='alert alert-danger'>⚠️ Debe ingresar el nombre del grupo y seleccionar un turno.</div>";
    }
}

// === OBTENER TURNOS DESDE BLOQUES_HORARIOS ===
$turnos = [];
$turnos_query = $conn->query("SELECT DISTINCT turno FROM bloques_horarios ORDER BY turno");
if ($turnos_query && $turnos_query->num_rows > 0) {
    while ($t = $turnos_query->fetch_assoc()) {
        $turnos[] = $t['turno'];
    }
} else {
    // Si no hay turnos en bloques_horarios, usar valores por defecto
    $turnos = ['mañana', 'tarde', 'noche'];
}

// === FILTROS PARA LA TABLA ===
$ver_inactivos = isset($_GET['ver_inactivos']) && $_GET['ver_inactivos'] == '1';
$filtro_turno = isset($_GET['filtro_turno']) && $_GET['filtro_turno'] != '' ? $_GET['filtro_turno'] : '';

// Construir la consulta con filtros
$where_conditions = [];
if ($ver_inactivos) {
    $where_conditions[] = "g.activa = 0";
} else {
    $where_conditions[] = "g.activa = 1";
}

if (!empty($filtro_turno)) {
    $where_conditions[] = "g.turno = '" . $conn->real_escape_string($filtro_turno) . "'";
}

$where_clause = "";
if (!empty($where_conditions)) {
    $where_clause = "WHERE " . implode(" AND ", $where_conditions);
}

// Obtener lista de grupos con filtros aplicados
$result = $conn->query("SELECT g.*, COUNT(h.id) as total_horarios 
                       FROM grupos g 
                       LEFT JOIN horarios h ON g.id = h.grupo_id 
                       $where_clause
                       GROUP BY g.id 
                       ORDER BY g.id DESC");

// Contar grupos activos e inactivos
$estadisticas_query = $conn->query("SELECT 
    SUM(activa = 1) as activos,
    SUM(activa = 0) as inactivos
FROM grupos");
$estadisticas = $estadisticas_query ? $estadisticas_query->fetch_assoc() : ['activos' => 0, 'inactivos' => 0];

// Contar grupos por turno para estadísticas
$estadisticas_turnos = $conn->query("SELECT 
    turno,
    COUNT(*) as total,
    SUM(activa = 1) as activos,
    SUM(activa = 0) as inactivos
FROM grupos 
GROUP BY turno 
ORDER BY turno");
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Gestión de Grupos</title>
    <link rel="stylesheet" href="/Agora/Agora/css/grupos.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css" rel="stylesheet">
</head>
<body>
<div class="grupos-container">
    <div class="grupos-top">
        <h2><i class="bi bi-people"></i> Gestión de Grupos</h2>
    </div>

    <?php if (!empty($mensaje)) echo $mensaje; ?>

    <!-- Estadísticas generales -->
    <div class="stats-container">
        <div class="stat-card success">
            <h5><i class="bi bi-check-circle"></i> Grupos Activos</h5>
            <h2><?= $estadisticas['activos'] ?? 0 ?></h2>
        </div>
        <div class="stat-card secondary">
            <h5><i class="bi bi-eye-slash"></i> Grupos Inactivos</h5>
            <h2><?= $estadisticas['inactivos'] ?? 0 ?></h2>
        </div>
        <div class="stat-card info">
            <h5><i class="bi bi-filter"></i> Filtro Activo</h5>
            <h2><?= !empty($filtro_turno) ? htmlspecialchars(ucfirst($filtro_turno)) : 'Todos' ?></h2>
        </div>
    </div>

    <!-- Estadísticas por turno -->
    <?php if ($estadisticas_turnos && $estadisticas_turnos->num_rows > 0): ?>
    <div class="turno-stats">
        <?php while ($stat = $estadisticas_turnos->fetch_assoc()): ?>
            <div class="turno-stat">
                <strong><?= htmlspecialchars(ucfirst($stat['turno'])) ?>:</strong>
                <span class="badge badge-success"><?= $stat['activos'] ?> activos</span>
                <span class="badge badge-secondary"><?= $stat['inactivos'] ?> inactivos</span>
                <span class="badge badge-info"><?= $stat['total'] ?> total</span>
            </div>
        <?php endwhile; ?>
    </div>
    <?php endif; ?>

    <!-- Form para añadir grupo -->
    <div class="form-container">
        <h4><i class="bi bi-plus-circle"></i> Nuevo Grupo</h4>
        <form method="post" class="form-row">
            <div class="form-group">
                <label>Nombre del Grupo</label>
                <input type="text" name="nombre_grupo" class="grupos-input" 
                       placeholder="Ingrese el nombre del grupo" 
                       maxlength="24" value="<?= htmlspecialchars($_POST['nombre_grupo'] ?? '') ?>" required>
                <small style="color:#6c757d; font-size:12px;">Máximo 24 caracteres</small>
            </div>
            <div class="form-group">
                <label>Turno</label>
                <select name="turno" class="grupos-select" required>
                    <option value="">-- Seleccione turno --</option>
                    <?php foreach($turnos as $t): ?>
                        <option value="<?= htmlspecialchars($t) ?>" <?= (isset($_POST['turno']) && $_POST['turno'] == $t) ? 'selected' : '' ?>>
                            <?= htmlspecialchars(ucfirst($t)) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <button type="submit" class="btn-create">
                    <i class="bi bi-check-circle"></i> Agregar/Reactivar Grupo
                </button>
            </div>
        </form>
    </div>

    <!-- Tabla de grupos -->
    <div style="background:#fff; padding:20px; border-radius:8px; border:1px solid #e9ecef;">
        <div class="header-actions">
            <h4 style="margin:0;">
                <i class="bi bi-list"></i> 
                <?= $ver_inactivos ? 'Grupos Inactivos' : 'Grupos Activos' ?>
                <?= !empty($filtro_turno) ? ' - ' . htmlspecialchars(ucfirst($filtro_turno)) : '' ?>
            </h4>
            <div style="display:flex; gap:10px;">
                <!-- Filtro por turno -->
                <div class="filtro-group">
                    <label>Turno:</label>
                    <select class="grupos-select" onchange="window.location.href='dashboard.php?page=grupos&ver_inactivos=<?= $ver_inactivos ? '1' : '0' ?>&filtro_turno='+this.value">
                        <option value="">Todos los turnos</option>
                        <?php foreach($turnos as $t): ?>
                            <option value="<?= htmlspecialchars($t) ?>" <?= $filtro_turno == $t ? 'selected' : '' ?>>
                                <?= htmlspecialchars(ucfirst($t)) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <!-- Filtro activos/inactivos -->
                <a href="dashboard.php?page=grupos&ver_inactivos=<?= $ver_inactivos ? '0' : '1' ?>&filtro_turno=<?= $filtro_turno ?>" 
                   class="btn-outline-<?= $ver_inactivos ? 'success' : 'secondary' ?> btn-sm">
                    <i class="bi bi-<?= $ver_inactivos ? 'eye' : 'eye-slash' ?>"></i>
                    <?= $ver_inactivos ? 'Ver Activos' : 'Ver Inactivos' ?>
                </a>
                
                <!-- Limpiar filtros -->
                <?php if (!empty($filtro_turno) || $ver_inactivos): ?>
                    <a href="dashboard.php?page=grupos" class="btn-outline-primary btn-sm">
                        <i class="bi bi-x-circle"></i> Limpiar Filtros
                    </a>
                <?php endif; ?>
            </div>
        </div>

        <table class="grupos-table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Nombre</th>
                    <th>Turno</th>
                    <th>Estado</th>
                    <th>Horarios</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($result && $result->num_rows > 0): ?>
                    <?php while ($row = $result->fetch_assoc()): ?>
                        <tr>
                            <td><?= htmlspecialchars($row['id']) ?></td>
                            <td><?= htmlspecialchars($row['nombre']) ?></td>
                            <td>
                                <span class="badge badge-info">
                                    <?= htmlspecialchars(ucfirst($row['turno'])) ?>
                                </span>
                            </td>
                            <td>
                                <span class="badge badge-<?= $row['activa'] ? 'success' : 'secondary' ?>">
                                    <?= $row['activa'] ? 'Activo' : 'Inactivo' ?>
                                </span>
                            </td>
                            <td>
                                <span class="badge badge-<?= $row['total_horarios'] > 0 ? 'warning' : 'secondary' ?>">
                                    <?= $row['total_horarios'] ?> horario(s)
                                </span>
                            </td>
                            <td>
                                <?php if ($rol_lower === 'admin'): ?>
                                    <?php if ($row['activa']): ?>
                                        <a href="dashboard.php?page=grupos&desactivar=<?= $row['id'] ?>&filtro_turno=<?= $filtro_turno ?>&ver_inactivos=<?= $ver_inactivos ?>" 
                                           class="btn-outline-warning"
                                           onclick="return confirmAction('desactivar', '<?= addslashes(htmlspecialchars($row['nombre'])) ?>');">
                                            <i class="bi bi-eye-slash"></i> Desactivar
                                        </a>
                                    <?php else: ?>
                                        <a href="dashboard.php?page=grupos&activar=<?= $row['id'] ?>&filtro_turno=<?= $filtro_turno ?>&ver_inactivos=<?= $ver_inactivos ?>" 
                                           class="btn-outline-success"
                                           onclick="return confirmAction('activar', '<?= addslashes(htmlspecialchars($row['nombre'])) ?>');">
                                            <i class="bi bi-eye"></i> Reactivar
                                        </a>
                                    <?php endif; ?>
                                <?php else: ?>
                                    —
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="6" class="empty-state">
                            <i class="bi bi-inbox"></i>
                            <?= $ver_inactivos ? 'No hay grupos inactivos' : 'No hay grupos activos' ?>
                            <?= !empty($filtro_turno) ? ' en el turno ' . htmlspecialchars(ucfirst($filtro_turno)) : '' ?>
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<script src="Agora/Agora/assets/grupos.js"></script>
</body>
</html>