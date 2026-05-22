<?php
// pages/recursos.php
session_start();
require_once __DIR__ . '/../backend/db_connection.php';
$conn->set_charset('utf8mb4');

if (env('APP_ENV', 'production') === 'development') {
    ini_set('display_errors', 1);
    error_reporting(E_ALL);
}

if (!isset($_SESSION['user_id'])) {
    echo "<div class='m-4 alert alert-danger'>Sin sesión activa. Inicia sesión.</div>";
    exit;
}

$rol = strtolower($_SESSION['rol'] ?? 'invitado');
$user_id = (int)($_SESSION['user_id'] ?? 0);
$errors = [];
$notices = [];

if (isset($_GET['success'])) $notices[] = $_GET['success'];
if (isset($_GET['error'])) $errors[] = $_GET['error'];

$filtro_tipo = $_GET['tipo'] ?? '';
$filtro_salon = $_GET['salon'] ?? '';
$busqueda = trim($_GET['q'] ?? '');
$current_page = max(1, (int)($_GET['page'] ?? 1));
$per_page = 12;
$q_param = !empty($busqueda) ? '&q=' . urlencode($busqueda) : '';

try {
    $conditions = [];
    $params = [];
    $types = '';

    if (!empty($busqueda)) {
        $conditions[] = "r.nombre LIKE ?";
        $params[] = '%' . $busqueda . '%';
        $types .= 's';
    }
    if (!empty($filtro_tipo)) {
        $conditions[] = "r.tipo = ?";
        $params[] = $filtro_tipo;
        $types .= 's';
    }
    if (!empty($filtro_salon)) {
        $conditions[] = "r.salon_id = ?";
        $params[] = $filtro_salon;
        $types .= 'i';
    }

    $where = '';
    if (!empty($conditions)) {
        $where = " WHERE " . implode(" AND ", $conditions);
    }

    // COUNT total
    $sql_count = "SELECT COUNT(*) AS total FROM recursos r" . $where;
    $total = 0;
    if (!empty($params)) {
        $stmt = $conn->prepare($sql_count);
        $stmt->bind_param($types, ...$params);
        $stmt->execute();
        $total = (int)$stmt->get_result()->fetch_assoc()['total'];
    } else {
        $total = (int)$conn->query($sql_count)->fetch_assoc()['total'];
    }

    $paginfo = paginate($total, $current_page, $per_page);

    // Data query with LIMIT
    $sql_data = "
      SELECT r.*,
             s.nombre_salon,
             g.nombre AS grupo_nombre,
             u.nombre AS usuario_nombre,
             u.rol AS usuario_rol
      FROM recursos r
      LEFT JOIN salones s ON r.salon_id = s.id
      LEFT JOIN grupos g ON r.grupo_id = g.id
      LEFT JOIN usuarios u ON r.usuario_id = u.id
      $where
      ORDER BY r.id DESC
      LIMIT ? OFFSET ?
    ";
    $data_params = $params;
    $data_types = $types;
    $data_params[] = $per_page;
    $data_types .= 'i';
    $data_params[] = $paginfo['offset'];
    $data_types .= 'i';

    $stmt = $conn->prepare($sql_data);
    $stmt->bind_param($data_types, ...$data_params);
    $stmt->execute();
    $res_recursos = $stmt->get_result();

    if ($res_recursos === false) throw new Exception("Error al consultar recursos: " . $conn->error);

    $salones = $conn->query("SELECT id, nombre_salon FROM salones ORDER BY nombre_salon ASC");

    if ($rol === 'admin') {
        $grupos = $conn->query("SELECT id, nombre FROM grupos ORDER BY nombre ASC");
    } elseif ($rol === 'profesor') {
        $grupos = $conn->query("SELECT DISTINCT g.id, g.nombre FROM grupos g INNER JOIN usuarios u ON u.grupo_id = g.id WHERE u.rol = 'alumno' ORDER BY g.nombre ASC");
    } elseif ($rol === 'alumno') {
        $stmt = $conn->prepare("SELECT g.id, g.nombre FROM grupos g INNER JOIN usuarios u ON u.grupo_id = g.id WHERE u.id = ?");
        $stmt->bind_param('i', $user_id);
        $stmt->execute();
        $grupos = $stmt->get_result();
    }

} catch (Exception $e) {
    $errors[] = $e->getMessage();
    if (!isset($res_recursos)) $res_recursos = null;
    if (!isset($salones)) $salones = null;
    if (!isset($grupos)) $grupos = null;
    $paginfo = paginate(0, 1, $per_page);
}

$stats = ['total' => $total, 'disponibles' => 0, 'ocupados' => 0, 'reservados' => 0];

$recurso_rows = [];
if (isset($res_recursos) && $res_recursos && $res_recursos->num_rows > 0) {
    while ($r = $res_recursos->fetch_assoc()) {
        $recurso_rows[] = $r;
        switch ($r['estado']) {
            case 'Disponible': $stats['disponibles']++; break;
            case 'Ocupado': $stats['ocupados']++; break;
            case 'Reservado': $stats['reservados']++; break;
        }
    }
}
$total_recursos = count($recurso_rows);
?>
<div class="recursos-section">
    <div class="page-header">
        <h2>
            <i class="bi bi-tools"></i>
            Gestión de Recursos
        </h2>
        <div class="header-actions">
            <span class="status-badge available">
                <i class="bi bi-person-circle"></i>
                <?= htmlspecialchars($_SESSION['user_name'] ?? 'Usuario', ENT_QUOTES, 'UTF-8') ?> • <?= htmlspecialchars(ucfirst($rol), ENT_QUOTES, 'UTF-8') ?>
            </span>
        </div>
    </div>

    <!-- Errores / avisos -->
    <?php foreach ($errors as $err): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="bi bi-exclamation-triangle-fill me-2"></i>
            <?= htmlspecialchars($err, ENT_QUOTES, 'UTF-8') ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endforeach; ?>
    <?php foreach ($notices as $n): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="bi bi-check-circle-fill me-2"></i>
            <?= htmlspecialchars($n, ENT_QUOTES, 'UTF-8') ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endforeach; ?>

    <!-- Stats Grid -->
    <div class="stats-grid mb-4">
        <div class="stat-card">
            <div class="stat-value"><?= (int)$stats['total'] ?></div>
            <div class="stat-label"><i class="bi bi-box"></i>Total Recursos</div>
        </div>
        <div class="stat-card">
            <div class="stat-value green"><?= (int)$stats['disponibles'] ?></div>
            <div class="stat-label"><i class="bi bi-check-circle"></i>Disponibles</div>
        </div>
        <div class="stat-card">
            <div class="stat-value yellow"><?= (int)$stats['ocupados'] ?></div>
            <div class="stat-label"><i class="bi bi-play-circle"></i>Ocupados</div>
        </div>
        <div class="stat-card">
            <div class="stat-value blue"><?= (int)$stats['reservados'] ?></div>
            <div class="stat-label"><i class="bi bi-bookmark"></i>Reservados</div>
        </div>
    </div>

    <!-- Filtros -->
    <div class="row mb-4">
        <div class="col-md-8">
            <div class="selection-panel">
                <div class="filter-label">
                    <i class="bi bi-funnel"></i>Filtrar por tipo:
                </div>
                <div class="filter-buttons">
                    <a href="dashboard.php?page=recursos<?= $q_param ?><?= !empty($filtro_salon) ? '&salon=' . urlencode($filtro_salon) : '' ?>" 
                       class="filter-btn <?= empty($filtro_tipo) ? 'active' : '' ?>">
                        <i class="bi bi-collection"></i> Todos
                    </a>
                    <a href="dashboard.php?page=recursos&tipo=Alargue<?= $q_param ?><?= !empty($filtro_salon) ? '&salon=' . urlencode($filtro_salon) : '' ?>" 
                       class="filter-btn <?= $filtro_tipo === 'Alargue' ? 'active' : '' ?>">
                        <i class="bi bi-plug"></i> Alargues
                    </a>
                    <a href="dashboard.php?page=recursos&tipo=Llave<?= $q_param ?><?= !empty($filtro_salon) ? '&salon=' . urlencode($filtro_salon) : '' ?>" 
                       class="filter-btn <?= $filtro_tipo === 'Llave' ? 'active' : '' ?>">
                        <i class="bi bi-key"></i> Llaves
                    </a>
                    <a href="dashboard.php?page=recursos&tipo=Control<?= $q_param ?><?= !empty($filtro_salon) ? '&salon=' . urlencode($filtro_salon) : '' ?>" 
                       class="filter-btn <?= $filtro_tipo === 'Control' ? 'active' : '' ?>">
                        <i class="bi bi-controller"></i> Controles
                    </a>
                </div>
            </div>
        </div>
        <div class="col-md-4 text-end">
            <?php if ($rol === 'admin'): ?>
                <a href="dashboard.php?page=agregar_recursos" class="btn btn-primary">
                    <i class="bi bi-plus-circle me-2"></i> Nuevo Recurso
                </a>
            <?php endif; ?>
        </div>
    </div>

    <!-- Filtro por Salón -->
    <div class="selection-panel">
        <div class="filter-label">
            <i class="bi bi-building"></i>Filtrar por salón:
        </div>
        <div class="filter-buttons">
            <form method="GET" action="dashboard.php" class="d-flex gap-2 align-items-center">
                <input type="hidden" name="page" value="recursos">
                <input type="hidden" name="tipo" value="<?= htmlspecialchars($filtro_tipo, ENT_QUOTES, 'UTF-8') ?>">
                <select name="salon" class="form-select" style="max-width: 250px;" onchange="this.form.submit()">
                    <option value="">Todos los salones</option>
                    <?php if ($salones && $salones->num_rows > 0): 
                        $salones->data_seek(0);
                        while($s = $salones->fetch_assoc()): ?>
                            <option value="<?= htmlspecialchars($s['id'], ENT_QUOTES, 'UTF-8') ?>" <?= $filtro_salon == $s['id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($s['nombre_salon'], ENT_QUOTES, 'UTF-8') ?>
                            </option>
                    <?php endwhile; endif; ?>
                </select>
                <?php if (!empty($filtro_salon)): ?>
                    <a href="dashboard.php?page=recursos<?= !empty($filtro_tipo) ? '&tipo=' . urlencode($filtro_tipo) : '' ?>" 
                       class="btn btn-outline-secondary btn-sm">
                        <i class="bi bi-x-circle"></i> Limpiar
                    </a>
                <?php endif; ?>
            </form>
        </div>
    </div>

    <!-- Búsqueda en tiempo real -->
    <div class="row mb-3">
        <div class="col-md-6 col-lg-4">
            <div class="input-group">
                <span class="input-group-text bg-transparent"><i class="bi bi-search"></i></span>
                <input type="text" id="searchRecursos" class="form-control" placeholder="Buscar recurso..."
                       value="<?= htmlspecialchars($busqueda, ENT_QUOTES, 'UTF-8') ?>"
                       autocomplete="off">
                <?php if (!empty($busqueda)): ?>
                <a href="dashboard.php?page=recursos<?= !empty($filtro_tipo) ? '&tipo=' . urlencode($filtro_tipo) : '' ?><?= !empty($filtro_salon) ? '&salon=' . urlencode($filtro_salon) : '' ?>"
                   class="btn btn-outline-secondary"><i class="bi bi-x-lg"></i></a>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Información de filtros activos -->
    <?php if (!empty($filtro_tipo) || !empty($filtro_salon) || !empty($busqueda)): ?>
        <div class="alert alert-info py-2 mb-4">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <i class="bi bi-funnel me-2"></i> 
                    <strong>Filtros activos:</strong>
                    <?php if (!empty($busqueda)): ?>
                        <span class="badge bg-secondary me-2">Búsqueda: "<?= htmlspecialchars($busqueda, ENT_QUOTES, 'UTF-8') ?>"</span>
                    <?php endif; ?>
                    <?php if (!empty($filtro_tipo)): ?>
                        <span class="badge bg-primary me-2">Tipo: <?= htmlspecialchars($filtro_tipo, ENT_QUOTES, 'UTF-8') ?></span>
                    <?php endif; ?>
                    <?php if (!empty($filtro_salon)): 
                        $salon_nombre = '';
                        if ($salones) {
                            $salones->data_seek(0);
                            while($s = $salones->fetch_assoc()) {
                                if ($s['id'] == $filtro_salon) {
                                    $salon_nombre = $s['nombre_salon'];
                                    break;
                                }
                            }
                        }
                    ?>
                        <span class="badge bg-primary me-2">Salón: <?= htmlspecialchars($salon_nombre, ENT_QUOTES, 'UTF-8') ?></span>
                    <?php endif; ?>
                </div>
                <a href="dashboard.php?page=recursos" class="btn btn-outline-info btn-sm">
                    <i class="bi bi-x-circle me-1"></i> Limpiar todos
                </a>
            </div>
        </div>
    <?php endif; ?>

    <!-- Grid de Recursos -->
    <div class="recursos-grid">
        <?php if ($total_recursos > 0): ?>
            <?php foreach ($recurso_rows as $r): ?>
                <div class="recurso-card card">
                    <div class="recurso-header">
                        <span class="recurso-type badge"><?= htmlspecialchars($r['tipo'], ENT_QUOTES, 'UTF-8') ?></span>
                        <span class="recurso-status status-badge status-<?= htmlspecialchars(strtolower($r['estado']), ENT_QUOTES, 'UTF-8') ?>">
                            <i class="bi bi-<?= 
                                $r['estado'] === 'Disponible' ? 'check-circle' : 
                                ($r['estado'] === 'Ocupado' ? 'play-circle' : 'bookmark')
                            ?> me-1"></i>
                            <?= htmlspecialchars($r['estado'], ENT_QUOTES, 'UTF-8') ?>
                        </span>
                    </div>
                    <h5 class="recurso-title"><?= htmlspecialchars($r['nombre'], ENT_QUOTES, 'UTF-8') ?></h5>
                    <?php if (!empty($r['descripcion'])): ?>
                        <p class="recurso-desc"><?= htmlspecialchars($r['descripcion'], ENT_QUOTES, 'UTF-8') ?></p>
                    <?php endif; ?>
                    <div class="recurso-meta">
                        <?php if (!empty($r['nombre_salon'])): ?>
                            <span class="resource-tag">
                                <i class="bi bi-building"></i>
                                <?= htmlspecialchars($r['nombre_salon'], ENT_QUOTES, 'UTF-8') ?>
                            </span>
                        <?php endif; ?>
                        <?php if (!empty($r['usuario_nombre'])): ?>
                            <span class="resource-tag">
                                <i class="bi bi-person"></i>
                                <?= htmlspecialchars($r['usuario_nombre'], ENT_QUOTES, 'UTF-8') ?>
                                <?php if (!empty($r['usuario_rol'])): ?>
                                    (<?= htmlspecialchars(ucfirst($r['usuario_rol']), ENT_QUOTES, 'UTF-8') ?>)
                                <?php endif; ?>
                            </span>
                        <?php endif; ?>
                        <?php if (!empty($r['grupo_nombre'])): ?>
                            <span class="resource-tag">
                                <i class="bi bi-people"></i>
                                <?= htmlspecialchars($r['grupo_nombre'], ENT_QUOTES, 'UTF-8') ?>
                            </span>
                        <?php endif; ?>
                    </div>
                    <?php if ($rol === 'admin'): ?>
                        <div class="action-row">
                            <a href="dashboard.php?page=agregar_recursos&edit=<?= (int)$r['id'] ?>" class="btn btn-warning btn-sm" title="Editar recurso">
                                <i class="bi bi-pencil"></i> Editar
                            </a>
                            <a href="backend/recursos_backend.php?delete=<?= (int)$r['id'] ?>" class="btn btn-danger btn-sm" title="Eliminar recurso"
                               onclick="return confirm('¿Estás seguro de eliminar el recurso <?= htmlspecialchars($r['nombre'], ENT_QUOTES, 'UTF-8') ?>?');">
                                <i class="bi bi-trash"></i>
                            </a>
                            <?php if ($r['estado'] === 'Ocupado'): ?>
                                <form method="POST" action="backend/recursos_backend.php">
                                    <input type="hidden" name="accion" value="marcar_uso">
                                    <input type="hidden" name="id" value="<?= (int)$r['id'] ?>">
                                    <input type="hidden" name="tipo_uso" value="liberar">
                                    <input type="hidden" name="mantener_salon" value="<?= $r['tipo'] !== 'Alargue' ? '1' : '0' ?>">
                                    <button type="submit" class="btn btn-success btn-sm" title="Desmarcar recurso">
                                        <i class="bi bi-check-circle me-1"></i> Desmarcar
                                    </button>
                                </form>
                            <?php endif; ?>
                        </div>
                    <?php elseif ($rol === 'profesor'): ?>
                        <div class="action-row">
                            <form method="POST" action="backend/recursos_backend.php" class="usage-form w-100" data-tipo="<?= htmlspecialchars($r['tipo'], ENT_QUOTES, 'UTF-8') ?>">
                                <input type="hidden" name="accion" value="marcar_uso">
                                <input type="hidden" name="id" value="<?= (int)$r['id'] ?>">
                                <input type="hidden" name="mantener_salon" value="<?= $r['tipo'] !== 'Alargue' ? '1' : '0' ?>">
                                <div class="row g-2">
                                    <?php if ($r['tipo'] === 'Alargue' || empty($r['salon_id'])): ?>
                                        <div class="col-4">
                                            <select name="salon_id" class="form-select form-select-sm" <?= $r['tipo'] === 'Alargue' ? '' : 'required' ?>>
                                                <option value="">Salón</option>
                                                <?php if ($salones && $salones->num_rows > 0): 
                                                    $salones->data_seek(0);
                                                    while($s = $salones->fetch_assoc()): ?>
                                                        <option value="<?= htmlspecialchars($s['id'], ENT_QUOTES, 'UTF-8') ?>" <?= $r['salon_id']==$s['id'] ? 'selected' : '' ?>>
                                                            <?= htmlspecialchars($s['nombre_salon'], ENT_QUOTES, 'UTF-8') ?>
                                                        </option>
                                                <?php endwhile; endif; ?>
                                            </select>
                                        </div>
                                    <?php else: ?>
                                        <div class="col-4">
                                            <input type="hidden" name="salon_id" value="<?= htmlspecialchars($r['salon_id'], ENT_QUOTES, 'UTF-8') ?>">
                                            <span class="resource-tag">
                                                <i class="bi bi-building"></i>
                                                <?= htmlspecialchars($r['nombre_salon'], ENT_QUOTES, 'UTF-8') ?>
                                            </span>
                                        </div>
                                    <?php endif; ?>
                                    <div class="col-4">
                                        <select name="grupo_id" class="form-select form-select-sm" required>
                                            <option value="">Grupo</option>
                                            <?php if ($grupos && $grupos->num_rows > 0): 
                                                $grupos->data_seek(0);
                                                while($g = $grupos->fetch_assoc()): ?>
                                                    <option value="<?= htmlspecialchars($g['id'], ENT_QUOTES, 'UTF-8') ?>" <?= $r['grupo_id']==$g['id'] ? 'selected' : '' ?>>
                                                        <?= htmlspecialchars($g['nombre'], ENT_QUOTES, 'UTF-8') ?>
                                                    </option>
                                            <?php endwhile; endif; ?>
                                        </select>
                                    </div>
                                    <div class="col-4">
                                        <?php if ($r['estado'] === 'Disponible'): ?>
                                            <button type="submit" name="tipo_uso" value="ocupar" class="btn btn-warning btn-sm w-100">
                                                <i class="bi bi-play-circle"></i> Usar
                                            </button>
                                        <?php elseif ($r['estado'] === 'Ocupado' && (int)($r['usuario_id'] ?? 0) === $user_id): ?>
                                            <button type="submit" name="tipo_uso" value="liberar" class="btn btn-success btn-sm w-100">
                                                <i class="bi bi-check-circle"></i> Liberar
                                            </button>
                                        <?php elseif ($r['estado'] === 'Reservado' && (int)($r['usuario_id'] ?? 0) === $user_id): ?>
                                            <button type="submit" name="tipo_uso" value="ocupar" class="btn btn-warning btn-sm w-100">
                                                <i class="bi bi-play-circle"></i> Usar
                                            </button>
                                        <?php else: ?>
                                            <button type="button" class="btn btn-outline-secondary btn-sm w-100" disabled>
                                                <i class="bi bi-lock"></i> No disponible
                                            </button>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </form>
                            <div class="d-flex gap-2 mt-2">
                                <?php if ($r['estado'] === 'Disponible'): ?>
                                    <form method="POST" action="backend/recursos_backend.php">
                                        <input type="hidden" name="accion" value="reservar">
                                        <input type="hidden" name="id" value="<?= (int)$r['id'] ?>">
                                        <button type="submit" class="btn btn-outline-primary btn-sm">
                                            <i class="bi bi-bookmark-check"></i> Reservar
                                        </button>
                                    </form>
                                <?php elseif ($r['estado'] === 'Reservado' && (int)($r['usuario_id'] ?? 0) === $user_id): ?>
                                    <form method="POST" action="backend/recursos_backend.php">
                                        <input type="hidden" name="accion" value="cancelar_reserva">
                                        <input type="hidden" name="id" value="<?= (int)$r['id'] ?>">
                                        <button type="submit" class="btn btn-outline-danger btn-sm">
                                            <i class="bi bi-bookmark-x"></i> Cancelar
                                        </button>
                                    </form>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php elseif ($rol === 'alumno'): ?>
                        <div class="action-row">
                            <form method="POST" action="backend/recursos_backend.php" class="usage-form w-100" data-tipo="<?= htmlspecialchars($r['tipo'], ENT_QUOTES, 'UTF-8') ?>">
                                <input type="hidden" name="accion" value="marcar_uso">
                                <input type="hidden" name="id" value="<?= (int)$r['id'] ?>">
                                <input type="hidden" name="mantener_salon" value="<?= $r['tipo'] !== 'Alargue' ? '1' : '0' ?>">
                                <div class="row g-2">
                                    <?php if (($r['tipo'] === 'Alargue' && empty($r['salon_id'])) || ($r['tipo'] !== 'Alargue' && empty($r['salon_id']))): ?>
                                        <div class="col-4">
                                            <select name="salon_id" class="form-select form-select-sm" <?= $r['tipo'] === 'Alargue' ? '' : 'required' ?>>
                                                <option value="">Salón</option>
                                                <?php if ($salones && $salones->num_rows > 0): 
                                                    $salones->data_seek(0);
                                                    while($s = $salones->fetch_assoc()): ?>
                                                        <option value="<?= htmlspecialchars($s['id'], ENT_QUOTES, 'UTF-8') ?>" <?= $r['salon_id']==$s['id'] ? 'selected' : '' ?>>
                                                            <?= htmlspecialchars($s['nombre_salon'], ENT_QUOTES, 'UTF-8') ?>
                                                        </option>
                                                <?php endwhile; endif; ?>
                                            </select>
                                        </div>
                                    <?php else: ?>
                                        <div class="col-4">
                                            <input type="hidden" name="salon_id" value="<?= htmlspecialchars($r['salon_id'], ENT_QUOTES, 'UTF-8') ?>">
                                            <span class="resource-tag">
                                                <i class="bi bi-building"></i>
                                                <?= htmlspecialchars($r['nombre_salon'], ENT_QUOTES, 'UTF-8') ?>
                                            </span>
                                        </div>
                                    <?php endif; ?>
                                    <div class="col-4">
                                        <select name="grupo_id" class="form-select form-select-sm" required>
                                            <option value="">Grupo</option>
                                            <?php if ($grupos && $grupos->num_rows > 0): 
                                                $grupos->data_seek(0);
                                                while($g = $grupos->fetch_assoc()): ?>
                                                    <option value="<?= htmlspecialchars($g['id'], ENT_QUOTES, 'UTF-8') ?>" <?= $r['grupo_id']==$g['id'] ? 'selected' : '' ?>>
                                                        <?= htmlspecialchars($g['nombre'], ENT_QUOTES, 'UTF-8') ?>
                                                    </option>
                                            <?php endwhile; endif; ?>
                                        </select>
                                    </div>
                                    <div class="col-4">
                                        <?php if ($r['estado'] === 'Disponible'): ?>
                                            <button type="submit" name="tipo_uso" value="ocupar" class="btn btn-warning btn-sm w-100">
                                                <i class="bi bi-play-circle"></i> Usar
                                            </button>
                                        <?php elseif ($r['estado'] === 'Ocupado' && (int)($r['usuario_id'] ?? 0) === $user_id): ?>
                                            <button type="submit" name="tipo_uso" value="liberar" class="btn btn-success btn-sm w-100">
                                                <i class="bi bi-check-circle"></i> Liberar
                                            </button>
                                        <?php elseif ($r['estado'] === 'Ocupado'): ?>
                                            <button type="button" class="btn btn-outline-secondary btn-sm w-100" disabled>
                                                <i class="bi bi-lock"></i> Ocupado
                                            </button>
                                        <?php elseif ($r['estado'] === 'Reservado'): ?>
                                            <button type="button" class="btn btn-outline-info btn-sm w-100" disabled>
                                                <i class="bi bi-bookmark"></i> Reservado
                                            </button>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </form>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <div class="empty-state" style="grid-column: 1 / -1;">
                <i class="bi bi-inbox"></i>
                <h4>
                    <?php if (!empty($filtro_tipo) || !empty($filtro_salon)): ?>
                        No hay recursos que coincidan con los filtros aplicados.
                    <?php else: ?>
                        No hay recursos registrados.
                    <?php endif; ?>
                </h4>
                <p><?= $rol === 'admin' ? 'Puedes agregar nuevos recursos usando el botón "Nuevo Recurso".' : 'Contacta al administrador para agregar recursos.' ?></p>
            </div>
        <?php endif; ?>
    </div>

    <?php
    $url_params = ['page' => 'recursos'];
    if (!empty($busqueda)) $url_params['q'] = $busqueda;
    if (!empty($filtro_tipo)) $url_params['tipo'] = $filtro_tipo;
    if (!empty($filtro_salon)) $url_params['salon'] = $filtro_salon;
    echo render_pagination($paginfo, 'dashboard.php?' . http_build_query($url_params));
    ?>
</div>

<script>
document.addEventListener('DOMContentLoaded', () => {
    const input = document.getElementById('searchRecursos');
    if (!input) return;

    let timer = null;
    const buildUrl = (q) => {
        const params = new URLSearchParams(window.location.search);
        params.set('page', 'recursos');
        if (q) { params.set('q', q); } else { params.delete('q'); }
        params.delete('page');
        return 'dashboard.php?page=recursos&' + params.toString();
    };

    input.addEventListener('input', () => {
        clearTimeout(timer);
        timer = setTimeout(() => {
            const q = input.value.trim();
            window.location.href = buildUrl(q);
        }, 400);
    });
});
</script>
