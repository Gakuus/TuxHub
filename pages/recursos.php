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
?>
<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <title>Recursos</title>
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
  <style>
    .table-responsive {
      overflow-x: auto;
    }
    .btn-edit {
      cursor: pointer;
    }
    .badge {
      font-size: 0.75em;
    }
    .form-select-sm {
      padding: 0.25rem 0.5rem;
      font-size: 0.875rem;
    }
    .usage-form {
      background: #f8f9fa;
      padding: 8px;
      border-radius: 5px;
      margin-bottom: 5px;
    }
    .action-buttons {
      min-width: 200px;
    }
    .filter-active {
      background-color: #0d6efd !important;
      color: white !important;
    }
    .filters-section {
      background: #f8f9fa;
      border-radius: 8px;
      padding: 15px;
      margin-bottom: 20px;
    }
  </style>
</head>
<body class="bg-light">
<div class="container py-4">

  <div class="d-flex justify-content-between align-items-center mb-3">
    <h3 class="mb-0">Gestión de Recursos</h3>
    <div>
      <small class="text-muted">Usuario: <strong><?= htmlspecialchars($_SESSION['user_name'] ?? 'Usuario') ?></strong> (<?= htmlspecialchars(ucfirst($rol)) ?>)</small>
    </div>
  </div>

  <!-- Errores / avisos -->
  <?php foreach ($errors as $err): ?>
    <div class="alert alert-danger"><?= htmlspecialchars($err) ?></div>
  <?php endforeach; ?>
  <?php foreach ($notices as $n): ?>
    <div class="alert alert-success"><?= htmlspecialchars($n) ?></div>
  <?php endforeach; ?>

  <!-- Filtros y Botón Nuevo -->
  <div class="filters-section">
    <div class="row">
      <div class="col-md-8">
        <!-- Filtro por Tipo -->
        <div class="mb-3">
          <label class="form-label fw-bold">Filtrar por tipo:</label>
          <div class="btn-group btn-group-sm" role="group">
            <a href="dashboard.php?page=recursos<?= !empty($filtro_salon) ? '&salon=' . urlencode($filtro_salon) : '' ?>" 
               class="btn btn-outline-primary <?= empty($filtro_tipo) ? 'filter-active' : '' ?>">
              Todos los tipos
            </a>
            <a href="dashboard.php?page=recursos&tipo=Alargue<?= !empty($filtro_salon) ? '&salon=' . urlencode($filtro_salon) : '' ?>" 
               class="btn btn-outline-primary <?= $filtro_tipo === 'Alargue' ? 'filter-active' : '' ?>">
              Alargues
            </a>
            <a href="dashboard.php?page=recursos&tipo=Llave<?= !empty($filtro_salon) ? '&salon=' . urlencode($filtro_salon) : '' ?>" 
               class="btn btn-outline-primary <?= $filtro_tipo === 'Llave' ? 'filter-active' : '' ?>">
              Llaves
            </a>
            <a href="dashboard.php?page=recursos&tipo=Control<?= !empty($filtro_salon) ? '&salon=' . urlencode($filtro_salon) : '' ?>" 
               class="btn btn-outline-primary <?= $filtro_tipo === 'Control' ? 'filter-active' : '' ?>">
              Controles
            </a>
          </div>
        </div>

        <!-- Filtro por Salón -->
        <div class="mb-3">
          <label class="form-label fw-bold">Filtrar por salón:</label>
          <form method="GET" action="dashboard.php" class="d-flex gap-2">
            <input type="hidden" name="page" value="recursos">
            <input type="hidden" name="tipo" value="<?= htmlspecialchars($filtro_tipo) ?>">
            <select name="salon" class="form-select form-select-sm" style="max-width: 250px;" onchange="this.form.submit()">
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
                 class="btn btn-sm btn-outline-secondary">
                <i class="bi bi-x-circle"></i>
              </a>
            <?php endif; ?>
          </form>
        </div>
      </div>
      <div class="col-md-4 text-end">
        <!-- Botón Nuevo (solo admin) -->
        <?php if ($rol === 'admin'): ?>
          <a href="dashboard.php?page=agregar_recurso" class="btn btn-primary">
            <i class="bi bi-plus-circle"></i> Nuevo recurso
          </a>
        <?php endif; ?>
      </div>
    </div>
  </div>

  <!-- Información de filtros activos -->
  <?php if (!empty($filtro_tipo) || !empty($filtro_salon)): ?>
    <div class="alert alert-info py-2">
      <i class="bi bi-funnel"></i> 
      Filtros activos:
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
      <a href="dashboard.php?page=recursos" class="btn btn-sm btn-outline-info ms-2">
        <i class="bi bi-x-circle"></i> Limpiar todos los filtros
      </a>
    </div>
  <?php endif; ?>

  <!-- Tabla -->
  <div class="card shadow-sm mb-4">
    <div class="card-body p-2">
      <div class="table-responsive">
        <table class="table table-striped table-hover table-sm align-middle text-center mb-0">
          <thead class="table-dark">
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
                <tr>
                  <td><?= $r['id'] ?></td>
                  <td><?= htmlspecialchars($r['nombre']) ?></td>
                  <td>
                    <span class="badge bg-secondary"><?= htmlspecialchars($r['tipo']) ?></span>
                  </td>
                  <td><?= htmlspecialchars($r['nombre_salon'] ?? '—') ?></td>
                  <td>
                    <span class="badge bg-<?=
                      $r['estado'] === 'Disponible' ? 'success' :
                      ($r['estado'] === 'Ocupado' ? 'warning' : 
                      ($r['estado'] === 'Reservado' ? 'info' : 'secondary'))
                    ?>"><?= htmlspecialchars($r['estado']) ?></span>
                  </td>
                  <td>
                    <?= htmlspecialchars($r['usuario_nombre'] ?? '—') ?>
                    <?php if (!empty($r['usuario_rol'])): ?>
                      <div class="small text-muted">(<?= ucfirst(htmlspecialchars($r['usuario_rol'])) ?>)</div>
                    <?php endif; ?>
                  </td>
                  <td><?= htmlspecialchars($r['grupo_nombre'] ?? '—') ?></td>
                  <td><?= htmlspecialchars($r['descripcion'] ?? '') ?></td>

                  <td class="action-buttons">
                    <!-- ADMIN: Solo Editar, Eliminar y Desmarcar -->
                    <?php if ($rol === 'admin'): ?>
                      <div class="d-flex flex-column gap-2">
                        <!-- Botones Editar y Eliminar -->
                        <div class="btn-group btn-group-sm w-100" role="group">
                          <a href="dashboard.php?page=agregar_recursos&edit=<?= $r['id'] ?>" class="btn btn-warning" title="Editar">
                            <i class="bi bi-pencil"></i> Editar
                          </a>
                          <a href="/Agora/Agora/backend/recursos_backend.php?delete=<?= $r['id'] ?>" class="btn btn-danger" title="Eliminar"
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
                            <button type="submit" class="btn btn-success btn-sm" title="Desmarcar recurso">
                              <i class="bi bi-check-circle"></i> Desmarcar
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
                          
                          <div class="row g-1 align-items-center">
                            <div class="col-5">
                              <select name="salon_id" class="form-select form-select-sm" required>
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
                            <div class="col-4">
                              <select name="grupo_id" class="form-select form-select-sm">
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
                            <div class="col-3">
                              <?php if ($r['estado'] === 'Disponible' || $r['estado'] === 'Reservado'): ?>
                                <button type="submit" name="tipo_uso" value="ocupar" class="btn btn-warning btn-sm w-100" title="Marcar como Ocupado">
                                  <i class="bi bi-play-circle"></i>
                                </button>
                              <?php elseif ($r['estado'] === 'Ocupado' && (int)($r['usuario_id'] ?? 0) === $user_id): ?>
                                <button type="submit" name="tipo_uso" value="liberar" class="btn btn-success btn-sm w-100" title="Marcar como Disponible">
                                  <i class="bi bi-check-circle"></i>
                                </button>
                              <?php else: ?>
                                <button type="button" class="btn btn-outline-secondary btn-sm w-100" disabled>
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
                              <button type="submit" class="btn btn-info btn-sm" title="Reservar">
                                <i class="bi bi-bookmark-check"></i> Reservar
                              </button>
                            </form>
                          <?php elseif ($r['estado'] === 'Reservado' && (int)($r['usuario_id'] ?? 0) === $user_id): ?>
                            <form method="POST" action="/Agora/Agora/backend/recursos_backend.php" class="d-inline">
                              <input type="hidden" name="accion" value="cancelar_reserva">
                              <input type="hidden" name="id" value="<?= $r['id'] ?>">
                              <button type="submit" class="btn btn-secondary btn-sm" title="Cancelar Reserva">
                                <i class="bi bi-bookmark-x"></i> Cancelar
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
                          
                          <div class="row g-1 align-items-center">
                            <div class="col-5">
                              <select name="salon_id" class="form-select form-select-sm" required>
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
                            <div class="col-4">
                              <select name="grupo_id" class="form-select form-select-sm">
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
                            <div class="col-3">
                              <?php if ($r['estado'] === 'Disponible' || $r['estado'] === 'Reservado'): ?>
                                <button type="submit" name="tipo_uso" value="ocupar" class="btn btn-warning btn-sm w-100" title="Marcar como Ocupado">
                                  <i class="bi bi-play-circle"></i>
                                </button>
                              <?php elseif ($r['estado'] === 'Ocupado' && (int)($r['usuario_id'] ?? 0) === $user_id): ?>
                                <button type="submit" name="tipo_uso" value="liberar" class="btn btn-success btn-sm w-100" title="Marcar como Disponible">
                                  <i class="bi bi-check-circle"></i>
                                </button>
                              <?php else: ?>
                                <button type="button" class="btn btn-outline-secondary btn-sm w-100" disabled>
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
                              <button type="submit" class="btn btn-info btn-sm" title="Reservar">
                                <i class="bi bi-bookmark-check"></i> Reservar
                              </button>
                            </form>
                          <?php elseif ($r['estado'] === 'Reservado' && (int)($r['usuario_id'] ?? 0) === $user_id): ?>
                            <form method="POST" action="/Agora/Agora/backend/recursos_backend.php" class="d-inline">
                              <input type="hidden" name="accion" value="cancelar_reserva">
                              <input type="hidden" name="id" value="<?= $r['id'] ?>">
                              <button type="submit" class="btn btn-secondary btn-sm" title="Cancelar Reserva">
                                <i class="bi bi-bookmark-x"></i> Cancelar
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
                <td colspan="9" class="text-center py-3">
                  <?php if (!empty($filtro_tipo) || !empty($filtro_salon)): ?>
                    No hay recursos que coincidan con los filtros aplicados.
                  <?php else: ?>
                    No hay recursos registrados.
                  <?php endif; ?>
                </td>
              </tr>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>

</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
// Procesar mensajes de URL
document.addEventListener('DOMContentLoaded', function() {
  const urlParams = new URLSearchParams(window.location.search);
  if (urlParams.has('success') || urlParams.has('error')) {
    // Limpiar parámetros de la URL sin recargar la página
    const newUrl = window.location.pathname + '?page=recursos';
    window.history.replaceState({}, document.title, newUrl);
  }
});
</script>
</body>
</html>