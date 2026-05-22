<?php
session_start();
require_once __DIR__ . '/../backend/db_connection.php';
require_once __DIR__ . '/../backend/helpers.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: ../index.php?error=requerido");
    exit();
}

$rol = $_SESSION['rol'] ?? "Invitado";
$rol_lower = strtolower($rol);
$csrf_param = '&csrf_token=' . urlencode(csrf_token());
$nombre = $_SESSION['user_name'] ?? "Usuario";

$mensaje = "";

// === DESACTIVAR GRUPO ===
if (isset($_GET['desactivor'])) {
    if ($rol_lower !== 'admin') {
        $mensaje = '<div class="alert alert-danger">❌ No tienes permisos.</div>';
    } else {
        csrf_verify_get();
        $id = (int) $_GET['desactivor'];
        
        $stmt = $conn->prepare("SELECT nombre FROM grupos WHERE id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $check = $stmt->get_result();
        $stmt->close();
        
        if ($check->num_rows > 0) {
            $grupo = $check->fetch_assoc();
            
            $stmt = $conn->prepare("UPDATE grupos SET activo = 0 WHERE id = ?");
            $stmt->bind_param("i", $id);
            $updated = $stmt->execute();
            $stmt->close();
            
            if ($updated) {
                $mensaje = "<div class='alert alert-warning'>✅ Grupo '" . htmlspecialchars($grupo['nombre'], ENT_QUOTES, 'UTF-8') . "' desactivodo correctamente.</div>";
                echo "<script>setTimeout(() => { window.location.href = 'dashboard.php?page=grupos'; }, 1000);</script>";
            } else {
                $mensaje = "<div class='alert alert-danger'>❌ Error al desactivor.</div>";
            }
        } else {
            $mensaje = "<div class='alert alert-danger'>❌ El grupo no existe.</div>";
        }
    }
}

// === ACTIVAR GRUPO ===
if (isset($_GET['activor'])) {
    if ($rol_lower !== 'admin') {
        $mensaje = '<div class="alert alert-danger">❌ No tienes permisos.</div>';
    } else {
        csrf_verify_get();
        $id = (int) $_GET['activor'];
        
        $stmt = $conn->prepare("SELECT nombre FROM grupos WHERE id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $check = $stmt->get_result();
        $stmt->close();
        
        if ($check->num_rows > 0) {
            $grupo = $check->fetch_assoc();
            
            $stmt = $conn->prepare("UPDATE grupos SET activo = 1 WHERE id = ?");
            $stmt->bind_param("i", $id);
            $updated = $stmt->execute();
            $stmt->close();
            
            if ($updated) {
                $mensaje = "<div class='alert alert-success'>✅ Grupo '" . htmlspecialchars($grupo['nombre'], ENT_QUOTES, 'UTF-8') . "' activodo correctamente.</div>";
                echo "<script>setTimeout(() => { window.location.href = 'dashboard.php?page=grupos'; }, 1000);</script>";
            } else {
                $mensaje = "<div class='alert alert-danger'>❌ Error al activor.</div>";
            }
        } else {
            $mensaje = '<div class="alert alert-danger">❌ El grupo no existe.</div>';
        }
    }
}

// === AGREGAR GRUPO ===
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['nombre_grupo'])) {
    csrf_verify();
    $nombre_grupo = trim($conn->real_escape_string($_POST['nombre_grupo'] ?? ''));
    $turno = trim($conn->real_escape_string($_POST['turno'] ?? ''));

    if (!empty($nombre_grupo) && !empty($turno)) {
        if (strlen($nombre_grupo) > 24) {
            $mensaje = "<div class='alert alert-danger'>⚠️ El nombre del grupo no puede superar los 24 caracteres.</div>";
        } else {
            $check = $conn->query("SELECT id, activo FROM grupos WHERE nombre = '$nombre_grupo' AND turno = '$turno'");
            if ($check->num_rows > 0) {
                $grupo_existente = $check->fetch_assoc();
                if ($grupo_existente['activo'] == 0) {
                    if ($conn->query("UPDATE grupos SET activo = 1 WHERE id = {$grupo_existente['id']}")) {
                        $mensaje = "<div class='alert alert-success'>✅ Grupo reactivodo correctamente.</div>";
                        $_POST['nombre_grupo'] = '';
                    }
                } else {
                    $mensaje = "<div class='alert alert-warning'>⚠️ El grupo ya existe y está activo.</div>";
                }
            } else {
                if ($conn->query("INSERT INTO grupos (nombre, turno, activo) VALUES ('$nombre_grupo', '$turno', 1)")) {
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
    $turnos = ['mañana', 'tarde', 'noche'];
}

// === FILTROS PARA LA TABLA ===
$ver_inactivos = isset($_GET['ver_inactivos']) && $_GET['ver_inactivos'] == '1';
$filtro_turno = isset($_GET['filtro_turno']) && $_GET['filtro_turno'] != '' ? $_GET['filtro_turno'] : '';

$where_conditions = [];
if ($ver_inactivos) {
    $where_conditions[] = "g.activo = 0";
} else {
    $where_conditions[] = "g.activo = 1";
}

if (!empty($filtro_turno)) {
    $where_conditions[] = "g.turno = '" . $conn->real_escape_string($filtro_turno) . "'";
}

$where_clause = "";
if (!empty($where_conditions)) {
    $where_clause = "WHERE " . implode(" AND ", $where_conditions);
}

$result = $conn->query("SELECT g.*, COUNT(h.id) as total_horarios 
                       FROM grupos g 
                       LEFT JOIN horarios h ON g.id = h.grupo_id 
                       $where_clause
                       GROUP BY g.id 
                       ORDER BY g.id DESC");

$estadisticas_query = $conn->query("SELECT 
    SUM(activo = 1) as activos,
    SUM(activo = 0) as inactivos
FROM grupos");
$estadisticas = $estadisticas_query ? $estadisticas_query->fetch_assoc() : ['activos' => 0, 'inactivos' => 0];

$estadisticas_turnos = $conn->query("SELECT 
    turno,
    COUNT(*) as total,
    SUM(activo = 1) as activos,
    SUM(activo = 0) as inactivos
FROM grupos 
GROUP BY turno 
ORDER BY turno");
?>

<div class="grupos-section">
    <div class="page-header">
        <h2>
            <i class="bi bi-people"></i>
            Gestión de Grupos
        </h2>
        <div class="header-actions">
            <span class="status-badge available">
                <i class="bi bi-person-circle"></i>
                <?= htmlspecialchars($nombre, ENT_QUOTES, 'UTF-8') ?>
            </span>
        </div>
    </div>

    <?php if (!empty($mensaje)) echo $mensaje; ?>

    <!-- Estadísticas generales -->
    <div class="stats-grid mb-4">
        <div class="stat-card">
            <div class="stat-value green"><?= (int)($estadisticas['activos'] ?? 0) ?></div>
            <div class="stat-label"><i class="bi bi-check-circle"></i>Grupos Activos</div>
        </div>
        <div class="stat-card">
            <div class="stat-value red"><?= (int)($estadisticas['inactivos'] ?? 0) ?></div>
            <div class="stat-label"><i class="bi bi-eye-slash"></i>Grupos Inactivos</div>
        </div>
        <div class="stat-card">
            <div class="stat-value blue"><?= !empty($filtro_turno) ? htmlspecialchars(ucfirst($filtro_turno), ENT_QUOTES, 'UTF-8') : 'Todos' ?></div>
            <div class="stat-label"><i class="bi bi-filter"></i>Filtro Activo</div>
        </div>
    </div>

    <!-- Estadísticas por turno -->
    <?php if ($estadisticas_turnos && $estadisticas_turnos->num_rows > 0): ?>
    <div class="card mb-4">
        <div class="card-header">
            <i class="bi bi-bar-chart"></i>Grupos por Turno
        </div>
        <div class="card-body">
            <div class="row g-2">
                <?php while ($stat = $estadisticas_turnos->fetch_assoc()): ?>
                    <div class="col-md-4">
                        <div class="d-flex align-items-center gap-2">
                            <strong><?= htmlspecialchars(ucfirst($stat['turno']), ENT_QUOTES, 'UTF-8') ?>:</strong>
                            <span class="badge bg-success"><?= (int)$stat['activos'] ?> activos</span>
                            <span class="badge bg-secondary"><?= (int)$stat['inactivos'] ?> inactivos</span>
                            <span class="badge bg-info"><?= (int)$stat['total'] ?> total</span>
                        </div>
                    </div>
                <?php endwhile; ?>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- Formulario para agregar grupo -->
    <div class="card mb-4">
        <div class="card-header">
            <i class="bi bi-plus-circle"></i>Nuevo Grupo
        </div>
        <div class="card-body">
            <form method="post" class="row g-3">
                <div class="col-md-5">
                    <label class="form-label">Nombre del Grupo</label>
                    <input type="text" name="nombre_grupo" class="form-control" 
                           placeholder="Ingrese el nombre del grupo" 
                           maxlength="24" value="<?= htmlspecialchars($_POST['nombre_grupo'] ?? '', ENT_QUOTES, 'UTF-8') ?>" required>
                    <small class="text-muted">Máximo 24 caracteres</small>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Turno</label>
                    <select name="turno" class="form-select" required>
                        <option value="">-- Seleccione turno --</option>
                        <?php foreach($turnos as $t): ?>
                            <option value="<?= htmlspecialchars($t, ENT_QUOTES, 'UTF-8') ?>" <?= (isset($_POST['turno']) && $_POST['turno'] == $t) ? 'selected' : '' ?>>
                                <?= htmlspecialchars(ucfirst($t), ENT_QUOTES, 'UTF-8') ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-3 d-flex align-items-end">
                    <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                    <button type="submit" class="btn btn-primary w-100">
                        <i class="bi bi-check-circle"></i> Agregar/Reactivar Grupo
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Tabla de grupos -->
    <div class="card">
        <div class="card-header">
            <i class="bi bi-list"></i>
            <?= $ver_inactivos ? 'Grupos Inactivos' : 'Grupos Activos' ?>
            <?= !empty($filtro_turno) ? ' - ' . htmlspecialchars(ucfirst($filtro_turno), ENT_QUOTES, 'UTF-8') : '' ?>
        </div>
        <div class="card-body">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <div class="d-flex gap-2 align-items-center">
                    <label class="form-label mb-0">Turno:</label>
                    <select class="form-select form-select-sm" style="width: auto;" onchange="window.location.href='dashboard.php?page=grupos&ver_inactivos=<?= $ver_inactivos ? '1' : '0' ?>&filtro_turno='+this.value">
                        <option value="">Todos los turnos</option>
                        <?php foreach($turnos as $t): ?>
                            <option value="<?= htmlspecialchars($t, ENT_QUOTES, 'UTF-8') ?>" <?= $filtro_turno == $t ? 'selected' : '' ?>>
                                <?= htmlspecialchars(ucfirst($t), ENT_QUOTES, 'UTF-8') ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="d-flex gap-2">
                    <a href="dashboard.php?page=grupos&ver_inactivos=<?= $ver_inactivos ? '0' : '1' ?>&filtro_turno=<?= htmlspecialchars($filtro_turno, ENT_QUOTES, 'UTF-8') ?>" 
                       class="btn btn-outline-<?= $ver_inactivos ? 'success' : 'secondary' ?> btn-sm">
                        <i class="bi bi-<?= $ver_inactivos ? 'eye' : 'eye-slash' ?>"></i>
                        <?= $ver_inactivos ? 'Ver Activos' : 'Ver Inactivos' ?>
                    </a>
                    <?php if (!empty($filtro_turno) || $ver_inactivos): ?>
                        <a href="dashboard.php?page=grupos" class="btn btn-outline-primary btn-sm">
                            <i class="bi bi-x-circle"></i> Limpiar Filtros
                        </a>
                    <?php endif; ?>
                </div>
            </div>

            <div class="table-responsive">
                <table class="table table-hover align-middle">
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
                                    <td><?= (int)$row['id'] ?></td>
                                    <td><strong><?= htmlspecialchars($row['nombre'], ENT_QUOTES, 'UTF-8') ?></strong></td>
                                    <td>
                                        <span class="badge bg-info">
                                            <?= htmlspecialchars(ucfirst($row['turno']), ENT_QUOTES, 'UTF-8') ?>
                                        </span>
                                    </td>
                                    <td>
                                        <span class="status-badge <?= $row['activo'] ? 'available' : 'inactive' ?>">
                                            <?= $row['activo'] ? 'Activo' : 'Inactivo' ?>
                                        </span>
                                    </td>
                                    <td>
                                        <span class="badge bg-<?= $row['total_horarios'] > 0 ? 'warning' : 'secondary' ?>">
                                            <?= (int)$row['total_horarios'] ?> horario(s)
                                        </span>
                                    </td>
                                    <td>
                                        <?php if ($rol_lower === 'admin'): ?>
                                            <?php if ($row['activo']): ?>
                                                <a href="dashboard.php?page=grupos&desactivor=<?= (int)$row['id'] ?>&filtro_turno=<?= htmlspecialchars($filtro_turno, ENT_QUOTES, 'UTF-8') ?>&ver_inactivos=<?= $ver_inactivos ? '1' : '0' ?><?= $csrf_param ?>" 
                                                   class="btn btn-outline-warning btn-sm"
                                                   onclick="return confirmAction('desactivor', '<?= addslashes(htmlspecialchars($row['nombre'], ENT_QUOTES, 'UTF-8')) ?>');">
                                                    <i class="bi bi-eye-slash"></i> Desactivor
                                                </a>
                                            <?php else: ?>
                                                <a href="dashboard.php?page=grupos&activor=<?= (int)$row['id'] ?>&filtro_turno=<?= htmlspecialchars($filtro_turno, ENT_QUOTES, 'UTF-8') ?>&ver_inactivos=<?= $ver_inactivos ? '1' : '0' ?><?= $csrf_param ?>" 
                                                   class="btn btn-outline-success btn-sm"
                                                   onclick="return confirmAction('activor', '<?= addslashes(htmlspecialchars($row['nombre'], ENT_QUOTES, 'UTF-8')) ?>');">
                                                    <i class="bi bi-eye"></i> Reactivor
                                                </a>
                                            <?php endif; ?>
                                        <?php else: ?>
                                            <span class="text-muted">—</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="6">
                                    <div class="empty-state">
                                        <i class="bi bi-inbox"></i>
                                        <h4>
                                            <?= $ver_inactivos ? 'No hay grupos inactivos' : 'No hay grupos activos' ?>
                                            <?= !empty($filtro_turno) ? ' en el turno ' . htmlspecialchars(ucfirst($filtro_turno), ENT_QUOTES, 'UTF-8') : '' ?>
                                        </h4>
                                    </div>
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
