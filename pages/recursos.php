<?php
// pages/recursos.php
session_start();
require_once __DIR__ . '/../backend/db_connection.php';
$conn->set_charset('utf8mb4');

// Mostrar errores de PHP (durante desarrollo) — opcional
ini_set('display_errors', 1);
error_reporting(E_ALL);

// ---------- validar sesión ----------
if (!isset($_SESSION['user_id'])) {
    echo "<div class='m-4 alert alert-danger'>Sin sesión activa. Inicia sesión.</div>";
    exit;
}

$rol = strtolower($_SESSION['rol'] ?? 'invitado');
$user_id = (int)($_SESSION['user_id'] ?? 0);
$errors = [];
$notices = [];

// Procesar mensajes de éxito/error
if (isset($_GET['success'])) {
    $notices[] = $_GET['success'];
}
if (isset($_GET['error'])) {
    $errors[] = $_GET['error'];
}

// ---------- Obtener filtros ----------
$filtro_tipo = $_GET['tipo'] ?? '';
$filtro_salon = $_GET['salon'] ?? '';

// ---------- consultas principales con manejo de errores ----------
try {
    // Construir consulta base
    $sql_base = "
      SELECT r.*,
             s.nombre_salon,
             g.nombre AS grupo_nombre,
             u.nombre AS usuario_nombre,
             u.rol AS usuario_rol
      FROM recursos r
      LEFT JOIN salones s ON r.salon_id = s.id
      LEFT JOIN grupos g ON r.grupo_id = g.id
      LEFT JOIN usuarios u ON r.usuario_id = u.id
    ";
    
    // Preparar condiciones WHERE
    $conditions = [];
    $params = [];
    $types = '';
    
    // Aplicar filtro de tipo si existe
    if (!empty($filtro_tipo)) {
        $conditions[] = "r.tipo = ?";
        $params[] = $filtro_tipo;
        $types .= 's';
    }
    
    // Aplicar filtro de salón si existe
    if (!empty($filtro_salon)) {
        $conditions[] = "r.salon_id = ?";
        $params[] = $filtro_salon;
        $types .= 'i';
    }
    
    // Construir consulta final
    if (!empty($conditions)) {
        $sql_base .= " WHERE " . implode(" AND ", $conditions);
    }
    
    $sql_base .= " ORDER BY r.id DESC";
    
    // Ejecutar consulta
    if (!empty($params)) {
        $stmt = $conn->prepare($sql_base);
        $stmt->bind_param($types, ...$params);
        $stmt->execute();
        $res_recursos = $stmt->get_result();
    } else {
        $res_recursos = $conn->query($sql_base);
    }
    
    if ($res_recursos === false) throw new Exception("Error al consultar recursos: " . $conn->error);

    // Consultar salones para los formularios y filtros
    $salones = $conn->query("SELECT id, nombre_salon FROM salones ORDER BY nombre_salon ASC");
    
    // Consultar grupos según el rol del usuario
    if ($rol === 'admin') {
        // Admin ve todos los grupos
        $grupos = $conn->query("SELECT id, nombre FROM grupos ORDER BY nombre ASC");
    } elseif ($rol === 'profesor') {
        // Profesor ve los grupos que tiene asignados
        $sql_grupos = "SELECT DISTINCT g.id, g.nombre 
                      FROM grupos g 
                      INNER JOIN usuarios u ON u.grupo_id = g.id 
                      WHERE u.rol = 'alumno' 
                      ORDER BY g.nombre ASC";
        $grupos = $conn->query($sql_grupos);
    } elseif ($rol === 'alumno') {
        // Alumno ve solo su grupo
        $sql_grupos = "SELECT g.id, g.nombre 
                      FROM grupos g 
                      INNER JOIN usuarios u ON u.grupo_id = g.id 
                      WHERE u.id = ?";
        $stmt = $conn->prepare($sql_grupos);
        $stmt->bind_param('i', $user_id);
        $stmt->execute();
        $grupos = $stmt->get_result();
    }

} catch (Exception $e) {
    $errors[] = $e->getMessage();
    if (!isset($res_recursos)) $res_recursos = null;
    if (!isset($salones)) $salones = null;
    if (!isset($grupos)) $grupos = null;
}

// Calcular estadísticas
$stats = [
    'total' => 0,
    'disponibles' => 0,
    'ocupados' => 0,
    'reservados' => 0
];

if ($res_recursos && $res_recursos->num_rows > 0) {
    $res_recursos->data_seek(0); // Reset pointer
    while($r = $res_recursos->fetch_assoc()) {
        $stats['total']++;
        switch($r['estado']) {
            case 'Disponible': $stats['disponibles']++; break;
            case 'Ocupado': $stats['ocupados']++; break;
            case 'Reservado': $stats['reservados']++; break;
        }
    }
    $res_recursos->data_seek(0); // Reset pointer again for main loop
}
?>
<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <title>Gestión de Recursos - Sistema Agora</title>
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
  <link rel="stylesheet" href="/Agora/Agora/css/recursos.css">
</head>
<body class="recursos-container">
<div class="container py-4">

  <!-- Header Section -->
  <div class="recursos-header">
    <div class="row align-items-center">
      <div class="col-md-8">
        <h1 class="page-title">
          <i class="bi bi-tools me-2"></i>Gestión de Recursos
        </h1>
        <p class="page-subtitle">
          Administra y controla el uso de recursos institucionales en tiempo real
        </p>
      </div>
      <div class="col-md-4 text-md-end">
        <div class="user-badge">
          <i class="bi bi-person-circle me-2"></i>
          <?= htmlspecialchars($_SESSION['user_name'] ?? 'Usuario') ?> • <?= htmlspecialchars(ucfirst($rol)) ?>
        </div>
      </div>
    </div>
  </div>

  <!-- Errores / avisos -->
  <?php foreach ($errors as $err): ?>
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
      <i class="bi bi-exclamation-triangle-fill me-2"></i>
      <?= htmlspecialchars($err) ?>
      <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
  <?php endforeach; ?>
  <?php foreach ($notices as $n): ?>
    <div class="alert alert-success alert-dismissible fade show" role="alert">
      <i class="bi bi-check-circle-fill me-2"></i>
      <?= htmlspecialchars($n) ?>
      <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
  <?php endforeach; ?>

  <!-- Stats Grid -->
  <div id="statsContainer" class="stats-grid mb-4">
    <div class="stat-card fade-in">
      <div class="stat-value"><?= $stats['total'] ?></div>
      <div class="stat-label">Total Recursos</div>
    </div>
    <div class="stat-card fade-in">
      <div class="stat-value" style="background: linear-gradient(135deg, #198754 0%, #20c997 100%); -webkit-background-clip: text; -webkit-text-fill-color: transparent; background-clip: text;">
        <?= $stats['disponibles'] ?>
      </div>
      <div class="stat-label">Disponibles</div>
    </div>
    <div class="stat-card fade-in">
      <div class="stat-value" style="background: linear-gradient(135deg, #ffc107 0%, #ffda6a 100%); -webkit-background-clip: text; -webkit-text-fill-color: transparent; background-clip: text;">
        <?= $stats['ocupados'] ?>
      </div>
      <div class="stat-label">Ocupados</div>
    </div>
    <div class="stat-card fade-in">
      <div class="stat-value" style="background: linear-gradient(135deg, #0dcaf0 0%, #0baccc 100%); -webkit-background-clip: text; -webkit-text-fill-color: transparent; background-clip: text;">
        <?= $stats['reservados'] ?>
      </div>
      <div class="stat-label">Reservados</div>
    </div>
  </div>

  <!-- View Toggle and Search -->
  <div class="d-flex justify-content-between align-items-center mb-4">
    <div class="view-toggle" id="viewToggle">
      <button class="view-btn active" data-view="table">
        <i class="bi bi-table"></i> Vista Tabla
      </button>
      <button class="view-btn" data-view="grid">
        <i class="bi bi-grid-3x3-gap"></i> Vista Grid
      </button>
    </div>
    
    <div class="d-flex gap-2">
      <div class="input-group" style="max-width: 300px;">
        <span class="input-group-text"><i class="bi bi-search"></i></span>
        <input type="text" id="searchResources" class="form-control" placeholder="Buscar recursos...">
      </div>
      <button class="btn btn-outline-primary" onclick="recursosManager.exportData('csv')">
        <i class="bi bi-download"></i> Exportar
      </button>
    </div>
  </div>

  <!-- Filtros y Botón Nuevo -->
  <div class="filters-section">
    <div class="row">
      <div class="col-md-8">
        <!-- Filtro por Tipo -->
        <div class="filter-group">
          <div class="filter-label">
            <i class="bi bi-funnel"></i>Filtrar por tipo:
          </div>
          <div class="filter-buttons">
            <a href="dashboard.php?page=recursos<?= !empty($filtro_salon) ? '&salon=' . urlencode($filtro_salon) : '' ?>" 
               class="filter-btn <?= empty($filtro_tipo) ? 'filter-active' : '' ?>">
              <i class="bi bi-collection"></i> Todos los tipos
            </a>
            <a href="dashboard.php?page=recursos&tipo=Alargue<?= !empty($filtro_salon) ? '&salon=' . urlencode($filtro_salon) : '' ?>" 
               class="filter-btn <?= $filtro_tipo === 'Alargue' ? 'filter-active' : '' ?>">
              <i class="bi bi-plug"></i> Alargues
            </a>
            <a href="dashboard.php?page=recursos&tipo=Llave<?= !empty($filtro_salon) ? '&salon=' . urlencode($filtro_salon) : '' ?>" 
               class="filter-btn <?= $filtro_tipo === 'Llave' ? 'filter-active' : '' ?>">
              <i class="bi bi-key"></i> Llaves
            </a>
            <a href="dashboard.php?page=recursos&tipo=Control<?= !empty($filtro_salon) ? '&salon=' . urlencode($filtro_salon) : '' ?>" 
               class="filter-btn <?= $filtro_tipo === 'Control' ? 'filter-active' : '' ?>">
              <i class="bi bi-controller"></i> Controles
            </a>
          </div>
        </div>

        <!-- Filtro por Salón -->
        <div class="filter-group">
          <div class="filter-label">
            <i class="bi bi-building"></i>Filtrar por salón:
          </div>
          <form method="GET" action="dashboard.php" class="d-flex gap-2 align-items-center">
            <input type="hidden" name="page" value="recursos">
            <input type="hidden" name="tipo" value="<?= htmlspecialchars($filtro_tipo) ?>">
            <select name="salon" class="form-select form-select-custom" style="max-width: 250px;" onchange="this.form.submit()">
              <option value="">Todos los salones</option>
              <?php if ($salones && $salones->num_rows > 0): 
                $salones->data_seek(0);
                while($s = $salones->fetch_assoc()): ?>
                  <option value="<?= $s['id'] ?>" <?= $filtro_salon == $s['id'] ? 'selected' : '' ?>>
                    <?= htmlspecialchars($s['nombre_salon']) ?>
                  </option>
              <?php endwhile; endif; ?>
            </select>
            <?php if (!empty($filtro_salon)): ?>
              <a href="dashboard.php?page=recursos<?= !empty($filtro_tipo) ? '&tipo=' . urlencode($filtro_tipo) : '' ?>" 
                 class="btn btn-sm btn-outline-secondary clear-filters">
                <i class="bi bi-x-circle"></i> Limpiar
              </a>
            <?php endif; ?>
          </form>
        </div>
      </div>
      <div class="col-md-4 text-end">
        <!-- Botón Nuevo (solo admin) -->
        <?php if ($rol === 'admin'): ?>
          <a href="dashboard.php?page=agregar_recurso" class="btn btn-primary-custom">
            <i class="bi bi-plus-circle me-2"></i> Nuevo Recurso
          </a>
        <?php endif; ?>
      </div>
    </div>
  </div>

  <!-- Información de filtros activos -->
  <?php if (!empty($filtro_tipo) || !empty($filtro_salon)): ?>
    <div class="alert alert-info py-3">
      <div class="d-flex justify-content-between align-items-center">
        <div>
          <i class="bi bi-funnel me-2"></i> 
          <strong>Filtros activos:</strong>
          <?php if (!empty($filtro_tipo)): ?>
            <span class="badge bg-primary me-2">Tipo: <?= htmlspecialchars($filtro_tipo) ?></span>
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
            <span class="badge bg-primary me-2">Salón: <?= htmlspecialchars($salon_nombre) ?></span>
          <?php endif; ?>
        </div>
        <a href="dashboard.php?page=recursos" class="btn btn-sm btn-outline-info clear-filters">
          <i class="bi bi-x-circle me-1"></i> Limpiar todos
        </a>
      </div>
    </div>
  <?php endif; ?>

  <!-- Results Counter -->
  <div id="results-counter"></div>

  <!-- Main Content -->
  <div class="recursos-main">
    <!-- Table View -->
    <div class="table-container">
      <div class="table-responsive">
        <table class="table table-hover align-middle">
          <thead>
            <tr>
              <th>ID</th>
              <th>Nombre</th>
              <th>Tipo</th>
              <th>Salón</th>
              <th>Estado</th>
              <th>Asignado a</th>
              <th>Grupo</th>
              <th>Descripción</th>
              <th class="action-buttons">Acciones</th>
            </tr>
          </thead>
          <tbody>
            <?php if ($res_recursos && $res_recursos->num_rows > 0): ?>
              <?php while ($r = $res_recursos->fetch_assoc()): ?>
                <tr class="fade-in">
                  <td><strong>#<?= $r['id'] ?></strong></td>
                  <td>
                    <div class="fw-bold text-dark"><?= htmlspecialchars($r['nombre']) ?></div>
                  </td>
                  <td>
                    <span class="resource-type"><?= htmlspecialchars($r['tipo']) ?></span>
                  </td>
                  <td>
                    <?php if (!empty($r['nombre_salon'])): ?>
                      <div class="d-flex align-items-center gap-1">
                        <i class="bi bi-building text-primary"></i>
                        <span><?= htmlspecialchars($r['nombre_salon']) ?></span>
                      </div>
                    <?php else: ?>
                      <span class="text-muted">—</span>
                    <?php endif; ?>
                  </td>
                  <td>
                    <span class="status-badge status-<?= strtolower($r['estado']) ?>">
                      <i class="bi bi-<?= 
                        $r['estado'] === 'Disponible' ? 'check-circle' : 
                        ($r['estado'] === 'Ocupado' ? 'play-circle' : 'bookmark')
                      ?> me-1"></i>
                      <?= htmlspecialchars($r['estado']) ?>
                    </span>
                  </td>
                  <td>
                    <?php if (!empty($r['usuario_nombre'])): ?>
                      <div class="fw-semibold"><?= htmlspecialchars($r['usuario_nombre']) ?></div>
                      <?php if (!empty($r['usuario_rol'])): ?>
                        <div class="small text-muted">(<?= ucfirst(htmlspecialchars($r['usuario_rol'])) ?>)</div>
                      <?php endif; ?>
                    <?php else: ?>
                      <span class="text-muted">—</span>
                    <?php endif; ?>
                  </td>
                  <td>
                    <?php if (!empty($r['grupo_nombre'])): ?>
                      <span class="badge bg-light text-dark"><?= htmlspecialchars($r['grupo_nombre']) ?></span>
                    <?php else: ?>
                      <span class="text-muted">—</span>
                    <?php endif; ?>
                  </td>
                  <td>
                    <?php if (!empty($r['descripcion'])): ?>
                      <span class="text-muted"><?= htmlspecialchars($r['descripcion']) ?></span>
                    <?php else: ?>
                      <span class="text-muted">—</span>
                    <?php endif; ?>
                  </td>

                  <td class="action-buttons">
                    <!-- ADMIN: Solo Editar, Eliminar y Desmarcar -->
                    <?php if ($rol === 'admin'): ?>
                      <div class="d-flex flex-column gap-2">
                        <!-- Botones Editar y Eliminar -->
                        <div class="btn-group w-100" role="group">
                          <a href="dashboard.php?page=agregar_recursos&edit=<?= $r['id'] ?>" class="btn btn-warning-custom" title="Editar recurso">
                            <i class="bi bi-pencil"></i> Editar
                          </a>
                          <a href="/Agora/Agora/backend/recursos_backend.php?delete=<?= $r['id'] ?>" class="btn btn-danger-custom" title="Eliminar recurso"
                             onclick="return confirm('¿Estás seguro de eliminar el recurso <?= htmlspecialchars($r['nombre']) ?>?');">
                            <i class="bi bi-trash"></i>
                          </a>
                        </div>

                        <!-- Solo botón para desmarcar si está ocupado -->
                        <?php if ($r['estado'] === 'Ocupado'): ?>
                          <form method="POST" action="/Agora/Agora/backend/recursos_backend.php" class="text-center">
                            <input type="hidden" name="accion" value="marcar_uso">
                            <input type="hidden" name="id" value="<?= $r['id'] ?>">
                            <input type="hidden" name="tipo_uso" value="liberar">
                            <button type="submit" class="btn btn-success-custom w-100" title="Desmarcar recurso">
                              <i class="bi bi-check-circle me-1"></i> Desmarcar
                            </button>
                          </form>
                        <?php endif; ?>
                      </div>

                    <!-- PROFESOR: Marcar uso/reserva -->
                    <?php elseif ($rol === 'profesor'): ?>
                      <div class="d-flex flex-column gap-2">
                        <!-- Formulario para Marcar Uso -->
                        <form method="POST" action="/Agora/Agora/backend/recursos_backend.php" class="usage-form">
                          <input type="hidden" name="accion" value="marcar_uso">
                          <input type="hidden" name="id" value="<?= $r['id'] ?>">
                          
                          <div class="form-grid">
                            <div>
                              <select name="salon_id" class="form-select-custom" required>
                                <option value="">Salón</option>
                                <?php if ($salones && $salones->num_rows > 0): 
                                  $salones->data_seek(0);
                                  while($s = $salones->fetch_assoc()): ?>
                                    <option value="<?= $s['id'] ?>" <?= $r['salon_id']==$s['id'] ? 'selected' : '' ?>>
                                      <?= htmlspecialchars($s['nombre_salon']) ?>
                                    </option>
                                <?php endwhile; endif; ?>
                              </select>
                            </div>
                            <div>
                              <select name="grupo_id" class="form-select-custom">
                                <option value="">Grupo</option>
                                <?php if ($grupos && $grupos->num_rows > 0): 
                                  $grupos->data_seek(0);
                                  while($g = $grupos->fetch_assoc()): ?>
                                    <option value="<?= $g['id'] ?>" <?= $r['grupo_id']==$g['id'] ? 'selected' : '' ?>>
                                      <?= htmlspecialchars($g['nombre']) ?>
                                    </option>
                                <?php endwhile; endif; ?>
                              </select>
                            </div>
                            <div>
                              <?php if ($r['estado'] === 'Disponible' || $r['estado'] === 'Reservado'): ?>
                                <button type="submit" name="tipo_uso" value="ocupar" class="btn btn-warning-custom w-100" title="Marcar como Ocupado">
                                  <i class="bi bi-play-circle"></i>
                                </button>
                              <?php elseif ($r['estado'] === 'Ocupado' && (int)($r['usuario_id'] ?? 0) === $user_id): ?>
                                <button type="submit" name="tipo_uso" value="liberar" class="btn btn-success-custom w-100" title="Marcar como Disponible">
                                  <i class="bi bi-check-circle"></i>
                                </button>
                              <?php else: ?>
                                <button type="button" class="btn btn-outline-custom w-100" disabled>
                                  <i class="bi bi-lock"></i>
                                </button>
                              <?php endif; ?>
                            </div>
                          </div>
                        </form>

                        <!-- Botón Reservar/Cancelar Reserva -->
                        <div class="text-center">
                          <?php if ($r['estado'] === 'Disponible'): ?>
                            <form method="POST" action="/Agora/Agora/backend/recursos_backend.php" class="d-inline">
                              <input type="hidden" name="accion" value="reservar">
                              <input type="hidden" name="id" value="<?= $r['id'] ?>">
                              <button type="submit" class="btn btn-outline-custom" title="Reservar recurso">
                                <i class="bi bi-bookmark-check me-1"></i> Reservar
                              </button>
                            </form>
                          <?php elseif ($r['estado'] === 'Reservado' && (int)($r['usuario_id'] ?? 0) === $user_id): ?>
                            <form method="POST" action="/Agora/Agora/backend/recursos_backend.php" class="d-inline">
                              <input type="hidden" name="accion" value="cancelar_reserva">
                              <input type="hidden" name="id" value="<?= $r['id'] ?>">
                              <button type="submit" class="btn btn-outline-custom" title="Cancelar Reserva">
                                <i class="bi bi-bookmark-x me-1"></i> Cancelar
                              </button>
                            </form>
                          <?php endif; ?>
                        </div>
                      </div>

                    <!-- ALUMNO: Marcar uso/reserva -->
                    <?php elseif ($rol === 'alumno'): ?>
                      <div class="d-flex flex-column gap-2">
                        <!-- Formulario para Marcar Uso -->
                        <form method="POST" action="/Agora/Agora/backend/recursos_backend.php" class="usage-form">
                          <input type="hidden" name="accion" value="marcar_uso">
                          <input type="hidden" name="id" value="<?= $r['id'] ?>">
                          
                          <div class="form-grid">
                            <div>
                              <select name="salon_id" class="form-select-custom" required>
                                <option value="">Salón</option>
                                <?php if ($salones && $salones->num_rows > 0): 
                                  $salones->data_seek(0);
                                  while($s = $salones->fetch_assoc()): ?>
                                    <option value="<?= $s['id'] ?>" <?= $r['salon_id']==$s['id'] ? 'selected' : '' ?>>
                                      <?= htmlspecialchars($s['nombre_salon']) ?>
                                    </option>
                                <?php endwhile; endif; ?>
                              </select>
                            </div>
                            <div>
                              <select name="grupo_id" class="form-select-custom">
                                <option value="">Grupo</option>
                                <?php if ($grupos && $grupos->num_rows > 0): 
                                  $grupos->data_seek(0);
                                  while($g = $grupos->fetch_assoc()): ?>
                                    <option value="<?= $g['id'] ?>" <?= $r['grupo_id']==$g['id'] ? 'selected' : '' ?>>
                                      <?= htmlspecialchars($g['nombre']) ?>
                                    </option>
                                <?php endwhile; endif; ?>
                              </select>
                            </div>
                            <div>
                              <?php if ($r['estado'] === 'Disponible' || $r['estado'] === 'Reservado'): ?>
                                <button type="submit" name="tipo_uso" value="ocupar" class="btn btn-warning-custom w-100" title="Marcar como Ocupado">
                                  <i class="bi bi-play-circle"></i>
                                </button>
                              <?php elseif ($r['estado'] === 'Ocupado' && (int)($r['usuario_id'] ?? 0) === $user_id): ?>
                                <button type="submit" name="tipo_uso" value="liberar" class="btn btn-success-custom w-100" title="Marcar como Disponible">
                                  <i class="bi bi-check-circle"></i>
                                </button>
                              <?php else: ?>
                                <button type="button" class="btn btn-outline-custom w-100" disabled>
                                  <i class="bi bi-lock"></i>
                                </button>
                              <?php endif; ?>
                            </div>
                          </div>
                        </form>

                        <!-- Botón Reservar/Cancelar Reserva -->
                        <div class="text-center">
                          <?php if ($r['estado'] === 'Disponible'): ?>
                            <form method="POST" action="/Agora/Agora/backend/recursos_backend.php" class="d-inline">
                              <input type="hidden" name="accion" value="reservar">
                              <input type="hidden" name="id" value="<?= $r['id'] ?>">
                              <button type="submit" class="btn btn-outline-custom" title="Reservar recurso">
                                <i class="bi bi-bookmark-check me-1"></i> Reservar
                              </button>
                            </form>
                          <?php elseif ($r['estado'] === 'Reservado' && (int)($r['usuario_id'] ?? 0) === $user_id): ?>
                            <form method="POST" action="/Agora/Agora/backend/recursos_backend.php" class="d-inline">
                              <input type="hidden" name="accion" value="cancelar_reserva">
                              <input type="hidden" name="id" value="<?= $r['id'] ?>">
                              <button type="submit" class="btn btn-outline-custom" title="Cancelar Reserva">
                                <i class="bi bi-bookmark-x me-1"></i> Cancelar
                              </button>
                            </form>
                          <?php endif; ?>
                        </div>
                      </div>
                    <?php endif; ?>
                  </td>
                </tr>
              <?php endwhile; ?>
            <?php else: ?>
              <tr>
                <td colspan="9" class="text-center py-5">
                  <div class="empty-state">
                    <i class="bi bi-inbox"></i>
                    <h4 class="text-muted">
                      <?php if (!empty($filtro_tipo) || !empty($filtro_salon)): ?>
                        No hay recursos que coincidan con los filtros aplicados.
                      <?php else: ?>
                        No hay recursos registrados.
                      <?php endif; ?>
                    </h4>
                    <p class="text-muted"><?= $rol === 'admin' ? 'Puedes agregar nuevos recursos usando el botón "Nuevo Recurso".' : 'Contacta al administrador para agregar recursos.' ?></p>
                  </div>
                </td>
              </tr>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>

    <!-- Grid View (Hidden by default) -->
    <div class="resource-grid d-none">
      <?php if ($res_recursos && $res_recursos->num_rows > 0): 
        $res_recursos->data_seek(0);
        while ($r = $res_recursos->fetch_assoc()): ?>
          <div class="resource-card fade-in">
            <div class="resource-header">
              <h3 class="resource-name"><?= htmlspecialchars($r['nombre']) ?></h3>
              <span class="resource-type"><?= htmlspecialchars($r['tipo']) ?></span>
            </div>
            
            <div class="resource-info">
              <div class="info-item">
                <i class="bi bi-building"></i>
                <span><?= htmlspecialchars($r['nombre_salon'] ?? 'Sin salón asignado') ?></span>
              </div>
              
              <div class="info-item">
                <i class="bi bi-person"></i>
                <span><?= htmlspecialchars($r['usuario_nombre'] ?? 'No asignado') ?></span>
              </div>
              
              <div class="info-item">
                <i class="bi bi-people"></i>
                <span><?= htmlspecialchars($r['grupo_nombre'] ?? 'Sin grupo') ?></span>
              </div>
              
              <?php if (!empty($r['descripcion'])): ?>
                <div class="info-item">
                  <i class="bi bi-chat-left-text"></i>
                  <span><?= htmlspecialchars($r['descripcion']) ?></span>
                </div>
              <?php endif; ?>
            </div>

            <div class="d-flex justify-content-between align-items-center mb-3">
              <span class="status-badge status-<?= strtolower($r['estado']) ?>">
                <i class="bi bi-<?= 
                  $r['estado'] === 'Disponible' ? 'check-circle' : 
                  ($r['estado'] === 'Ocupado' ? 'play-circle' : 'bookmark')
                ?> me-1"></i>
                <?= htmlspecialchars($r['estado']) ?>
              </span>
              <small class="text-muted">ID: #<?= $r['id'] ?></small>
            </div>

            <!-- Actions for Grid View -->
            <div class="action-buttons">
              <!-- Las acciones serían similares a la vista tabla -->
              <!-- Se implementarían de manera similar -->
            </div>
          </div>
        <?php endwhile; ?>
      <?php endif; ?>
    </div>
  </div>

</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="/Agora/Agora/assets/recursos.js"></script>
</body>
</html>