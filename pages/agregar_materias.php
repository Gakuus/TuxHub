<?php
require_once __DIR__ . "/../backend/db_connection.php";
$conn->set_charset("utf8mb4");
session_start();

$rol = $_SESSION['rol'] ?? "Invitado";

if ($rol !== "admin") {
    echo "<div class='alert alert-danger'>Acceso denegado</div>";
    exit;
}

$mensaje = "";

// === DEBUG: Mostrar parámetros recibidos ===
if (isset($_GET['desactivar']) || isset($_GET['activar'])) {
    error_log("DEBUG: GET parameters - " . print_r($_GET, true));
}

// === DESACTIVAR MATERIA ===
if (isset($_GET['desactivar'])) {
    $id = (int) $_GET['desactivar'];
    error_log("DEBUG: Intentando DESACTIVAR materia ID: $id");
    
    // Verificar que la materia existe y está activa
    $check = $conn->query("SELECT id, nombre_materia, activa FROM materias WHERE id = $id");
    if ($check->num_rows > 0) {
        $materia = $check->fetch_assoc();
        error_log("DEBUG: Materia encontrada - ID: {$materia['id']}, Nombre: {$materia['nombre_materia']}, Activa: {$materia['activa']}");
        
        if ($materia['activa'] == 1) {
            $result = $conn->query("UPDATE materias SET activa = 0 WHERE id = $id");
            error_log("DEBUG: Resultado UPDATE: " . ($result ? "TRUE" : "FALSE"));
            
            if ($result) {
                $mensaje = "<div class='alert alert-warning'>✅ Materia '{$materia['nombre_materia']}' desactivada correctamente.</div>";
                error_log("DEBUG: Materia desactivada exitosamente");
                echo "<script>setTimeout(() => { window.location.href = 'dashboard.php?page=agregar_materias'; }, 1000);</script>";
            } else {
                $error = $conn->error;
                $mensaje = "<div class='alert alert-danger'>❌ Error al desactivar: " . htmlspecialchars($error) . "</div>";
                error_log("DEBUG: Error en UPDATE: " . $error);
            }
        } else {
            $mensaje = "<div class='alert alert-warning'>⚠️ La materia ya está inactiva.</div>";
            error_log("DEBUG: La materia ya estaba inactiva");
        }
    } else {
        $mensaje = "<div class='alert alert-danger'>❌ La materia no existe.</div>";
        error_log("DEBUG: Materia no encontrada con ID: $id");
    }
}

// === ACTIVAR MATERIA ===
if (isset($_GET['activar'])) {
    $id = (int) $_GET['activar'];
    error_log("DEBUG: Intentando ACTIVAR materia ID: $id");
    
    // Verificar que la materia existe y está inactiva
    $check = $conn->query("SELECT id, nombre_materia, activa FROM materias WHERE id = $id");
    if ($check->num_rows > 0) {
        $materia = $check->fetch_assoc();
        error_log("DEBUG: Materia encontrada - ID: {$materia['id']}, Nombre: {$materia['nombre_materia']}, Activa: {$materia['activa']}");
        
        if ($materia['activa'] == 0) {
            $result = $conn->query("UPDATE materias SET activa = 1 WHERE id = $id");
            error_log("DEBUG: Resultado UPDATE: " . ($result ? "TRUE" : "FALSE"));
            
            if ($result) {
                $mensaje = "<div class='alert alert-success'>✅ Materia '{$materia['nombre_materia']}' activada correctamente.</div>";
                error_log("DEBUG: Materia activada exitosamente");
                echo "<script>setTimeout(() => { window.location.href = 'dashboard.php?page=agregar_materias'; }, 1000);</script>";
            } else {
                $error = $conn->error;
                $mensaje = "<div class='alert alert-danger'>❌ Error al activar: " . htmlspecialchars($error) . "</div>";
                error_log("DEBUG: Error en UPDATE: " . $error);
            }
        } else {
            $mensaje = "<div class='alert alert-warning'>⚠️ La materia ya está activa.</div>";
            error_log("DEBUG: La materia ya estaba activa");
        }
    } else {
        $mensaje = "<div class='alert alert-danger'>❌ La materia no existe.</div>";
        error_log("DEBUG: Materia no encontrada con ID: $id");
    }
}

// === AGREGAR MATERIA ===
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nombre_materia = trim($conn->real_escape_string($_POST['nombre_materia'] ?? ''));

    if (!empty($nombre_materia)) {
        if (strlen($nombre_materia) > 24) {
            $mensaje = "<div class='alert alert-danger'>⚠️ El nombre no puede superar los 24 caracteres.</div>";
        } else {
            // Verificar si la materia existe (activa o inactiva)
            $check = $conn->query("SELECT id, activa FROM materias WHERE nombre_materia = '$nombre_materia'");
            if ($check->num_rows > 0) {
                $materia_existente = $check->fetch_assoc();
                // Si existe pero está inactiva, reactivarla
                if ($materia_existente['activa'] == 0) {
                    if ($conn->query("UPDATE materias SET activa = 1 WHERE id = {$materia_existente['id']}")) {
                        $mensaje = "<div class='alert alert-success'>✅ Materia reactivada correctamente.</div>";
                        $_POST['nombre_materia'] = '';
                    }
                } else {
                    $mensaje = "<div class='alert alert-warning'>⚠️ La materia ya existe y está activa.</div>";
                }
            } else {
                // Crear nueva materia
                if ($conn->query("INSERT INTO materias (nombre_materia, activa) VALUES ('$nombre_materia', 1)")) {
                    $mensaje = "<div class='alert alert-success'>✅ Materia agregada correctamente.</div>";
                    $_POST['nombre_materia'] = '';
                } else {
                    $mensaje = "<div class='alert alert-danger'>❌ Error al insertar: " . htmlspecialchars($conn->error) . "</div>";
                }
            }
        }
    } else {
        $mensaje = "<div class='alert alert-danger'>⚠️ Debe ingresar el nombre de la materia.</div>";
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

<div class="container-fluid px-0 gestion-materias">
    <h1 class="mt-4"><i class="bi bi-book"></i> Gestión de Materias</h1>

    <?php if (!empty($mensaje)) echo $mensaje; ?>

    <!-- Información de debug (solo para desarrollo) -->
    <div class="alert alert-info debug-info d-none">
        <strong>Debug Info:</strong><br>
        GET: <?= htmlspecialchars(print_r($_GET, true)) ?><br>
        Filtro: <?= $filtro_estado ?><br>
        Estadísticas: Activas=<?= $estadisticas['activas'] ?? 0 ?>, Inactivas=<?= $estadisticas['inactivas'] ?? 0 ?>
    </div>

    <!-- Estadísticas -->
    <div class="row mb-4 stats-container">
        <div class="col-md-6">
            <div class="card bg-success text-white stats-card">
                <div class="card-body">
                    <h5 class="card-title"><i class="bi bi-check-circle"></i> Materias Activas</h5>
                    <h2 class="card-text"><?= $estadisticas['activas'] ?? 0 ?></h2>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card bg-secondary text-white stats-card">
                <div class="card-body">
                    <h5 class="card-title"><i class="bi bi-eye-slash"></i> Materias Inactivas</h5>
                    <h2 class="card-text"><?= $estadisticas['inactivas'] ?? 0 ?></h2>
                </div>
            </div>
        </div>
    </div>

    <div class="card mb-4 shadow-sm form-container">
        <div class="card-header"><i class="bi bi-plus-circle"></i> Nueva Materia</div>
        <div class="card-body">
            <form method="post" class="row g-3 materia-form">
                <div class="col-md-6">
                    <label class="form-label">Nombre de la Materia</label>
                    <input type="text" name="nombre_materia" class="form-control materia-input"
                           placeholder="Ingrese el nombre" maxlength="24" 
                           value="<?= htmlspecialchars($_POST['nombre_materia'] ?? '') ?>" required>
                    <small class="text-muted form-help">Máximo 24 caracteres. Si la materia existe pero está inactiva, se reactivará automáticamente.</small>
                </div>
                <div class="col-12 mt-3">
                    <button class="btn btn-success submit-btn" type="submit">
                        <i class="bi bi-check-circle"></i> Agregar/Reactivar Materia
                    </button>
                </div>
            </form>
        </div>
    </div>

    <div class="card shadow-sm table-container">
        <div class="card-header d-flex justify-content-between align-items-center table-header">
            <div>
                <i class="bi bi-list"></i> 
                <?= $ver_inactivas ? 'Materias Inactivas' : 'Materias Activas' ?>
            </div>
            <div>
                <a href="dashboard.php?page=agregar_materias&ver_inactivas=<?= $ver_inactivas ? '0' : '1' ?>" 
                   class="btn btn-<?= $ver_inactivas ? 'success' : 'outline-secondary' ?> btn-sm toggle-view-btn">
                    <i class="bi bi-<?= $ver_inactivas ? 'eye' : 'eye-slash' ?>"></i>
                    <?= $ver_inactivas ? 'Ver Activas' : 'Ver Inactivas' ?>
                </a>
            </div>
        </div>
        <div class="card-body">
            <?php if ($materias->num_rows > 0): ?>
                <div class="table-responsive">
                    <table class="table table-striped table-hover align-middle materias-table">
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
                                <tr class="materia-row">
                                    <td class="materia-id"><?= $m['id'] ?></td>
                                    <td class="materia-nombre"><?= htmlspecialchars($m['nombre_materia']) ?></td>
                                    <td class="text-center">
                                        <span class="badge estado-badge bg-<?= $m['activa'] ? 'success' : 'secondary' ?>">
                                            <?= $m['activa'] ? 'Activa' : 'Inactiva' ?>
                                        </span>
                                    </td>
                                    <td class="text-center">
                                        <span class="badge horarios-badge bg-<?= $m['total_horarios'] > 0 ? 'warning' : 'secondary' ?>">
                                            <?= $m['total_horarios'] ?> horario(s)
                                        </span>
                                    </td>
                                    <td class="text-center acciones-cell">
                                        <?php if ($m['activa']): ?>
                                            <a href="dashboard.php?page=agregar_materias&desactivar=<?= $m['id'] ?>" 
                                               class="btn btn-sm btn-outline-warning desactivar-btn"
                                               onclick="return confirm('¿Desactivar \'<?= htmlspecialchars($m['nombre_materia']) ?>\'?\\n\\nNo aparecerá en los listados normales.');">
                                                <i class="bi bi-eye-slash"></i> Desactivar
                                            </a>
                                        <?php else: ?>
                                            <a href="dashboard.php?page=agregar_materias&activar=<?= $m['id'] ?>" 
                                               class="btn btn-sm btn-outline-success activar-btn"
                                               onclick="return confirm('¿Reactivar \'<?= htmlspecialchars($m['nombre_materia']) ?>\'?\\n\\nVolverá a aparecer en los listados normales.');">
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
                <div class="text-center py-4 empty-state">
                    <i class="bi bi-inbox empty-icon"></i>
                    <p class="text-muted mt-2 empty-message">
                        <?= $ver_inactivas ? 
                            'No hay materias inactivas' : 
                            'No hay materias activas' ?>
                    </p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Incluir archivos CSS y JS -->
<link rel="stylesheet" href="/Agora/Agora/css/materias.css">
<script src="/Agora/Agora/assets/materias.js"></script>