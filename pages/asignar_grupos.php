<?php
if (!isset($_SESSION)) session_start();

require_once __DIR__ . '/../backend/db_connection.php';
require_once __DIR__ . '/../backend/helpers.php';

// Solo administradores
if ($_SESSION['rol'] !== 'admin') {
    echo "<div class='alert alert-danger mt-4 text-center'>Acceso denegado. Solo administradores pueden gestionar grupos.</div>";
    exit;
}

// ==============================
// 1. Obtener profesores y turnos
// ==============================
$profesores = $conn->query("SELECT id, nombre FROM usuarios WHERE rol = 'profesor' ORDER BY nombre");

// Turnos únicos desde bloques_horarios
$turnos = [];
$res_turnos = $conn->query("SELECT DISTINCT turno FROM bloques_horarios ORDER BY FIELD(turno,'mañana','tarde','noche')");
while ($t = $res_turnos->fetch_assoc()) $turnos[] = $t['turno'];

// ==============================
// 2. Procesar envío del formulario
// ==============================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['profesor_id'], $_POST['grupos'])) {
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== csrf_token()) {
        echo "<div class='alert alert-danger text-center mt-3'>Error de validación CSRF. Intente nuevamente.</div>";
    } else {
        $profesor_id = intval($_POST['profesor_id']);
        $grupos_asignados = $_POST['grupos'] ?? [];

        $stmt_del = $conn->prepare("DELETE FROM grupos_profesores WHERE profesor_id = ?");
        $stmt_del->bind_param("i", $profesor_id);
        $stmt_del->execute();
        $stmt_del->close();

        if (!empty($grupos_asignados)) {
            $stmt = $conn->prepare("INSERT INTO grupos_profesores (profesor_id, grupo_id) VALUES (?, ?)");
            foreach ($grupos_asignados as $grupo_id) {
                $stmt->bind_param("ii", $profesor_id, $grupo_id);
                $stmt->execute();
            }
            $stmt->close();
        }

        echo "<div class='alert alert-success text-center mt-3'>Grupos actualizados correctamente.</div>";
    }
}

// ==============================
// 3. Obtener grupos y filtrar por turno y estado si se seleccionó
// ==============================
$selected_turno = $_POST['turno'] ?? '';
$selected_estado = $_POST['estado'] ?? '';

$grupos_query = "SELECT id, nombre, turno, activo FROM grupos WHERE 1=1";
$params = [];
$types = '';

if ($selected_turno !== '') {
    $grupos_query .= " AND turno = ?";
    $params[] = $selected_turno;
    $types .= 's';
}

if ($selected_estado !== '') {
    $grupos_query .= " AND activo = ?";
    $params[] = (int)$selected_estado;
    $types .= 'i';
}

$grupos_query .= " ORDER BY nombre";

if (!empty($params)) {
    $stmt = $conn->prepare($grupos_query);
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $grupos = $stmt->get_result();
    $stmt->close();
} else {
    $grupos = $conn->query($grupos_query);
}

// ==============================
// 4. Grupos asignados al profesor
// ==============================
$grupos_profesor = [];
if (isset($_POST['profesor_id']) && $_POST['profesor_id'] !== '') {
    $profesor_id = intval($_POST['profesor_id']);
    $stmt = $conn->prepare("SELECT grupo_id FROM grupos_profesores WHERE profesor_id = ?");
    $stmt->bind_param("i", $profesor_id);
    $stmt->execute();
    $res = $stmt->get_result();
    $stmt->close();
    $grupos_profesor = array_column($res->fetch_all(MYSQLI_ASSOC), 'grupo_id');
}
?>

<section class="asignar-grupos">
  <div class="page-header">
    <h2><i class="bi bi-people"></i> Asignar Grupos a Profesores</h2>
  </div>

  <form method="POST" class="card">
    <div class="card-body">
      <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">

      <div class="mb-3">
        <label class="form-label">Profesor</label>
        <select name="profesor_id" class="form-select" onchange="this.form.submit()" required>
          <option value="">Seleccionar profesor...</option>
          <?php $profesores->data_seek(0); while ($p = $profesores->fetch_assoc()): ?>
            <option value="<?= $p['id'] ?>" <?= (isset($_POST['profesor_id']) && $_POST['profesor_id'] == $p['id']) ? 'selected' : '' ?>>
              <?= htmlspecialchars($p['nombre']) ?>
            </option>
          <?php endwhile; ?>
        </select>
      </div>

      <?php if (isset($_POST['profesor_id']) && $_POST['profesor_id'] !== ''): ?>
      <div class="row mb-3">
        <div class="col-md-6">
          <label class="form-label">Turno</label>
          <select name="turno" class="form-select" onchange="this.form.submit()">
            <option value="">-- Todos los turnos --</option>
            <?php foreach($turnos as $t): ?>
              <option value="<?= htmlspecialchars($t) ?>" <?= ($selected_turno == $t) ? 'selected' : '' ?>>
                <?= ucfirst(htmlspecialchars($t)) ?>
              </option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="col-md-6">
          <label class="form-label">Estado</label>
          <select name="estado" class="form-select" onchange="this.form.submit()">
            <option value="">-- Todos los estados --</option>
            <option value="1" <?= ($selected_estado === '1') ? 'selected' : '' ?>>Activos</option>
            <option value="0" <?= ($selected_estado === '0') ? 'selected' : '' ?>>Inactivos</option>
          </select>
        </div>
      </div>

      <div class="d-flex justify-content-between align-items-center mb-3">
        <label class="form-label mb-0 fw-bold">Grupos</label>
        <label class="checkbox-item mb-0" style="cursor:pointer;">
          <input type="checkbox" id="seleccionar-todos" onchange="document.querySelectorAll('.grupo-checkbox').forEach(c=>c.checked=this.checked)">
          <span>Seleccionar todos</span>
        </label>
      </div>

      <div class="checkbox-grid">
        <?php $grupos->data_seek(0); while ($g = $grupos->fetch_assoc()): ?>
          <label class="checkbox-item" for="grupo_<?= $g['id'] ?>" style="<?= !$g['activo'] ? 'opacity:0.6;' : '' ?>">
            <input type="checkbox" class="grupo-checkbox" name="grupos[]" value="<?= $g['id'] ?>" id="grupo_<?= $g['id'] ?>" <?= in_array($g['id'], $grupos_profesor) ? 'checked' : '' ?>>
            <span><?= htmlspecialchars($g['nombre']) ?> (<?= htmlspecialchars($g['turno']) ?>)<?= !$g['activo'] ? ' <small class="text-muted">Inactivo</small>' : '' ?></span>
          </label>
        <?php endwhile; ?>
      </div>

      <div class="action-row">
        <button type="submit" class="btn btn-success"><i class="bi bi-save"></i> Guardar Cambios</button>
      </div>
      <?php endif; ?>
    </div>
  </form>
</section>

<script src="assets/asignar_grupos.js"></script>
