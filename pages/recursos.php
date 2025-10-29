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

// ---------- consultas principales con manejo de errores ----------
try {
    // Recursos con LEFT JOINs a salones, grupos y usuario asignado
    $sql = "
      SELECT r.*,
             s.nombre_salon,
             g.nombre AS grupo_nombre,
             u.nombre AS usuario_nombre,
             u.rol AS usuario_rol
      FROM recursos r
      LEFT JOIN salones s ON r.salon_id = s.id
      LEFT JOIN grupos g ON r.grupo_id = g.id
      LEFT JOIN usuarios u ON r.usuario_id = u.id
      ORDER BY r.id DESC
    ";
    $res_recursos = $conn->query($sql);
    if ($res_recursos === false) throw new Exception("Error al consultar recursos: " . $conn->error);

} catch (Exception $e) {
    $errors[] = $e->getMessage();
    if (!isset($res_recursos)) $res_recursos = null;
}
?>
<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <title>Recursos</title>
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <style>
    .table-responsive {
      overflow-x: auto;
    }
    .btn-edit {
      cursor: pointer;
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
    <div class="alert alert-info"><?= htmlspecialchars($n) ?></div>
  <?php endforeach; ?>

  <!-- Botón Nuevo (solo admin) -->
  <?php if ($rol === 'admin'): ?>
    <div class="mb-3 text-end">
      <a href="agregar_recurso.php" class="btn btn-primary">➕ Nuevo recurso</a>
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
              <th>Acciones</th>
            </tr>
          </thead>
          <tbody>
            <?php if ($res_recursos && $res_recursos->num_rows > 0): ?>
              <?php while ($r = $res_recursos->fetch_assoc()): ?>
                <tr>
                  <td><?= $r['id'] ?></td>
                  <td><?= htmlspecialchars($r['nombre']) ?></td>
                  <td><?= htmlspecialchars($r['tipo']) ?></td>
                  <td><?= htmlspecialchars($r['nombre_salon'] ?? '—') ?></td>
                  <td>
                    <span class="badge bg-<?=
                      $r['estado'] === 'Disponible' ? 'success' :
                      ($r['estado'] === 'Ocupado' ? 'warning' : 'secondary')
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

                  <td>
                    <!-- Admin: editar / eliminar -->
                    <?php if ($rol === 'admin'): ?>
                      <a href="agregar_recurso.php?edit=<?= $r['id'] ?>" class="btn btn-sm btn-warning">✏️</a>
                      <a href="../backend/recursos_backend.php?delete=<?= $r['id'] ?>" class="btn btn-sm btn-danger"
                         onclick="return confirm('Confirmar eliminación');">🗑️</a>

                    <!-- Profesor: sólo actualizar estado -->
                    <?php elseif ($rol === 'profesor'): ?>
                      <form method="POST" action="../backend/recursos_backend.php" class="d-inline">
                        <input type="hidden" name="accion" value="actualizar_estado">
                        <input type="hidden" name="id" value="<?= $r['id'] ?>">
                        <select name="estado" class="form-select form-select-sm d-inline w-auto" onchange="this.form.submit()">
                          <option value="Disponible" <?= $r['estado']==='Disponible' ? 'selected' : '' ?>>Disponible</option>
                          <option value="Ocupado" <?= $r['estado']==='Ocupado' ? 'selected' : '' ?>>Ocupado</option>
                          <option value="Mantenimiento" <?= $r['estado']==='Mantenimiento' ? 'selected' : '' ?>>Mantenimiento</option>
                        </select>
                      </form>

                    <!-- Alumno: tomar/devolver si es Alargue -->
                    <?php elseif ($rol === 'alumno'): ?>
                      <?php if ($r['tipo'] === 'Alargue'): ?>
                        <?php if ($r['estado'] === 'Disponible'): ?>
                          <form method="POST" action="../backend/recursos_backend.php" class="d-inline">
                            <input type="hidden" name="accion" value="tomar">
                            <input type="hidden" name="id" value="<?= $r['id'] ?>">
                            <button class="btn btn-sm btn-success">Tomar</button>
                          </form>
                        <?php elseif ((int)($r['usuario_id'] ?? 0) === $user_id): ?>
                          <form method="POST" action="../backend/recursos_backend.php" class="d-inline">
                            <input type="hidden" name="accion" value="devolver">
                            <input type="hidden" name="id" value="<?= $r['id'] ?>">
                            <button class="btn btn-sm btn-secondary">Devolver</button>
                          </form>
                        <?php endif; ?>
                      <?php endif; ?>
                    <?php endif; ?>
                  </td>
                </tr>
              <?php endwhile; ?>
            <?php else: ?>
              <tr><td colspan="9">No hay recursos registrados.</td></tr>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>

</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>