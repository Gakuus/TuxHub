<?php
require_once __DIR__ . "/../backend/db_connection.php";
require_once __DIR__ . "/../backend/helpers.php";
$conn->set_charset("utf8mb4");
session_start();

$rol = $_SESSION['rol'] ?? "Invitado";

if ($rol !== "admin") {
    echo "<div class='alert alert-danger'>Acceso denegado</div>";
    exit;
}

$mensaje = "";

$csrf_param = '&csrf_token=' . urlencode(csrf_token());

// === DESACTIVAR MATERIA ===
if (isset($_GET['desactivar'])) {
    csrf_verify_get();
    $id = (int) $_GET['desactivar'];
    
    $stmt = $conn->prepare("SELECT id, nombre_materia, activa FROM materias WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $check = $stmt->get_result();
    $stmt->close();
    
    if ($check->num_rows > 0) {
        $materia = $check->fetch_assoc();
        
        if ($materia['activa'] == 1) {
            $stmt = $conn->prepare("UPDATE materias SET activa = 0 WHERE id = ?");
            $stmt->bind_param("i", $id);
            $result = $stmt->execute();
            $stmt->close();
            
            if ($result) {
                $mensaje = "<div class='alert alert-warning'>Materia '" . htmlspecialchars($materia['nombre_materia'], ENT_QUOTES, 'UTF-8') . "' desactivada correctamente.</div>";
                echo "<script>setTimeout(() => { window.location.href = 'dashboard.php?page=agregar_materias'; }, 1000);</script>";
            } else {
                $mensaje = "<div class='alert alert-danger'>Error al desactivar.</div>";
            }
        } else {
            $mensaje = "<div class='alert alert-warning'>La materia ya est&aacute; inactiva.</div>";
        }
    } else {
        $mensaje = "<div class='alert alert-danger'>La materia no existe.</div>";
    }
}

// === ACTIVAR MATERIA ===
if (isset($_GET['activar'])) {
    csrf_verify_get();
    $id = (int) $_GET['activar'];
    
    $stmt = $conn->prepare("SELECT id, nombre_materia, activa FROM materias WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $check = $stmt->get_result();
    $stmt->close();
    
    if ($check->num_rows > 0) {
        $materia = $check->fetch_assoc();
        
        if ($materia['activa'] == 0) {
            $stmt = $conn->prepare("UPDATE materias SET activa = 1 WHERE id = ?");
            $stmt->bind_param("i", $id);
            $result = $stmt->execute();
            $stmt->close();
            
            if ($result) {
                $mensaje = "<div class='alert alert-success'>Materia '" . htmlspecialchars($materia['nombre_materia'], ENT_QUOTES, 'UTF-8') . "' activada correctamente.</div>";
                echo "<script>setTimeout(() => { window.location.href = 'dashboard.php?page=agregar_materias'; }, 1000);</script>";
            } else {
                $mensaje = "<div class='alert alert-danger'>Error al activar.</div>";
            }
        } else {
            $mensaje = "<div class='alert alert-warning'>La materia ya est&aacute; activa.</div>";
        }
    } else {
        $mensaje = "<div class='alert alert-danger'>La materia no existe.</div>";
    }
}

// === AGREGAR MATERIA ===
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Verificar CSRF
    if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        $mensaje = "<div class='alert alert-danger'>Error de seguridad. Intente nuevamente.</div>";
    } else {
        $nombre_materia = trim($_POST['nombre_materia'] ?? '');

        if (!empty($nombre_materia)) {
            if (strlen($nombre_materia) > 24) {
                $mensaje = "<div class='alert alert-danger'>El nombre no puede superar los 24 caracteres.</div>";
            } else {
                // Verificar si la materia existe (activa o inactiva)
                $check = $conn->query("SELECT id, activa FROM materias WHERE nombre_materia = '$nombre_materia'");
                if ($check->num_rows > 0) {
                    $materia_existente = $check->fetch_assoc();
                    // Si existe pero está inactiva, reactivarla
                    if ($materia_existente['activa'] == 0) {
                        if ($conn->query("UPDATE materias SET activa = 1 WHERE id = {$materia_existente['id']}")) {
                            $mensaje = "<div class='alert alert-success'>Materia reactivada correctamente.</div>";
                            $_POST['nombre_materia'] = '';
                        }
                    } else {
                        $mensaje = "<div class='alert alert-warning'>La materia ya existe y est&aacute; activa.</div>";
                    }
                } else {
                    // Crear nueva materia
                    if ($conn->query("INSERT INTO materias (nombre_materia, activa) VALUES ('$nombre_materia', 1)")) {
                        $mensaje = "<div class='alert alert-success'>Materia agregada correctamente.</div>";
                        $_POST['nombre_materia'] = '';
                    } else {
                        $mensaje = "<div class='alert alert-danger'>Error al insertar: " . htmlspecialchars($conn->error) . "</div>";
                    }
                }
            }
        } else {
            $mensaje = "<div class='alert alert-danger'>Debe ingresar el nombre de la materia.</div>";
        }
    }
}

// === VERIFICAR ESTRUCTURA DE LA TABLA ===
$table_structure = $conn->query("DESCRIBE materias");
error_log("DEBUG: Estructura de la tabla materias:");
while ($column = $table_structure->fetch_assoc()) {
    error_log("DEBUG - Columna: " . $column['Field'] . " - Tipo: " . $column['Type'] . " - Default: " . $column['Default']);
}

// === FILTRO PARA VER INACTIVAS ===
$ver_inactivas = isset($_GET['ver_inactivas']) && $_GET['ver_inactivas'] == '1';
$filtro_estado = $ver_inactivas ? "WHERE m.activa = 0" : "WHERE m.activa = 1";

// === LISTAR MATERIAS ===
$materias = $conn->query("SELECT m.id, m.nombre_materia, m.activa, COUNT(h.id) as total_horarios 
                         FROM materias m 
                         LEFT JOIN horarios h ON m.id = h.materia_id 
                         $filtro_estado
                         GROUP BY m.id 
                         ORDER BY m.nombre_materia");

// Contar materias activas e inactivas
$estadisticas = $conn->query("SELECT 
    SUM(activa = 1) as activas,
    SUM(activa = 0) as inactivas
FROM materias")->fetch_assoc();
?>
<div class="gestion-materias">
    <div class="page-header">
        <h2><i class="bi bi-book"></i> Gesti&oacute;n de Materias</h2>
        <div class="header-actions">
            <a href="dashboard.php?page=agregar_materias&ver_inactivas=<?= $ver_inactivas ? '0' : '1' ?>" 
               class="btn btn-<?= $ver_inactivas ? 'success' : 'outline-secondary' ?> btn-sm">
                <i class="bi bi-<?= $ver_inactivas ? 'eye' : 'eye-slash' ?>"></i>
                <?= $ver_inactivas ? 'Ver Activas' : 'Ver Inactivas' ?>
            </a>
        </div>
    </div>

    <?php if (!empty($mensaje)) echo $mensaje; ?>

    <div class="stats-grid mb-4">
        <div class="stat-card">
            <div class="stat-value green"><?= $estadisticas['activas'] ?? 0 ?></div>
            <div class="stat-label"><i class="bi bi-check-circle"></i> Materias Activas</div>
        </div>
        <div class="stat-card">
            <div class="stat-value" style="color:var(--text-muted);"><?= $estadisticas['inactivas'] ?? 0 ?></div>
            <div class="stat-label"><i class="bi bi-eye-slash"></i> Materias Inactivas</div>
        </div>
    </div>

    <div class="card mb-4">
        <div class="card-header"><i class="bi bi-plus-circle"></i> Nueva Materia</div>
        <div class="card-body">
            <form method="post" class="row g-3">
                <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                <div class="col-md-6">
                    <label class="form-label">Nombre de la Materia</label>
                    <input type="text" name="nombre_materia" class="form-control"
                           placeholder="Ingrese el nombre" maxlength="24" 
                           value="<?= htmlspecialchars($_POST['nombre_materia'] ?? '') ?>" required>
                    <small class="text-muted">M&aacute;ximo 24 caracteres. Si la materia existe pero est&aacute; inactiva, se reactivar&aacute; autom&aacute;ticamente.</small>
                </div>
                <div class="col-12">
                    <button class="btn btn-success" type="submit">
                        <i class="bi bi-check-circle"></i> Agregar/Reactivar Materia
                    </button>
                </div>
            </form>
        </div>
    </div>

    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <span><i class="bi bi-list"></i> <?= $ver_inactivas ? 'Materias Inactivas' : 'Materias Activas' ?></span>
        </div>
        <div class="card-body">
            <?php if ($materias->num_rows > 0): ?>
                <div class="table-responsive">
                    <table class="table table-striped table-hover align-middle">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Nombre de Materia</th>
                                <th class="text-center">Estado</th>
                                <th class="text-center">Horarios</th>
                                <th class="text-center">Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($m = $materias->fetch_assoc()): ?>
                                <tr>
                                    <td><?= $m['id'] ?></td>
                                    <td><?= htmlspecialchars($m['nombre_materia']) ?></td>
                                    <td class="text-center">
                                        <span class="status-badge <?= $m['activa'] ? 'available' : 'inactive' ?>">
                                            <?= $m['activa'] ? 'Activa' : 'Inactiva' ?>
                                        </span>
                                    </td>
                                    <td class="text-center">
                                        <span class="badge bg-<?= $m['total_horarios'] > 0 ? 'warning' : 'secondary' ?>">
                                            <?= $m['total_horarios'] ?> horario(s)
                                        </span>
                                    </td>
                                    <td class="text-center">
                                        <?php if ($m['activa']): ?>
                                            <a href="dashboard.php?page=agregar_materias&desactivar=<?= $m['id'] ?><?= $csrf_param ?>" 
                                               class="btn btn-sm btn-outline-warning"
                                               onclick="return confirm('Desactivar \'<?= htmlspecialchars($m['nombre_materia']) ?>\'?\\n\\nNo aparecer&aacute; en los listados normales.');">
                                                <i class="bi bi-eye-slash"></i> Desactivar
                                            </a>
                                        <?php else: ?>
                                            <a href="dashboard.php?page=agregar_materias&activar=<?= $m['id'] ?><?= $csrf_param ?>" 
                                               class="btn btn-sm btn-outline-success"
                                               onclick="return confirm('Reactivar \'<?= htmlspecialchars($m['nombre_materia']) ?>\'?\\n\\nVolver&aacute; a aparecer en los listados normales.');">
                                                <i class="bi bi-eye"></i> Reactivar
                                            </a>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <div class="empty-state">
                    <i class="bi bi-inbox"></i>
                    <p class="text-muted mt-2">
                        <?= $ver_inactivas ? 'No hay materias inactivas' : 'No hay materias activas' ?>
                    </p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<script src="assets/materias.js"></script>
