<?php
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/../backend/db_connection.php';
require_once __DIR__ . '/../backend/helpers.php';

// =======================
// Seguridad de acceso
// =======================
$rol = strtolower($_SESSION['rol'] ?? 'invitado');
if ($rol !== 'admin') {
    echo '<div class="alert alert-danger">⛔ No tienes permiso para acceder a esta sección.</div>';
    exit();
}

// =======================
// Recursos disponibles
// =======================
$recursos_disponibles = [
    'television',
    'computadoras',
    'pizarra',
    'proyector',
    'aire_acondicionado'
];

// =======================
// Procesar formulario
// =======================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['accion']) && $_POST['accion'] === 'agregar_salon') {
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== csrf_token()) {
        echo '<div class="alert alert-danger mt-3">❌ Error de validación CSRF. Intente nuevamente.</div>';
    } else {
        $nombre = trim($_POST['nombre_salon']);
        $capacidad = (int)$_POST['capacidad'];
        $ubicacion = trim($_POST['ubicacion']);
        $observaciones = trim($_POST['observaciones']);
        $recursos_sel = $_POST['recursos'] ?? [];

        // Validar longitud de campos
        $errores = [];
        if (strlen($nombre) > 24) {
            $errores[] = 'El nombre no puede tener más de 24 caracteres.';
        }
        if ($capacidad > 50 || $capacidad < 1) {
            $errores[] = 'La capacidad debe estar entre 1 y 50 personas.';
        }
        if (strlen($ubicacion) > 24) {
            $errores[] = 'La ubicación no puede tener más de 24 caracteres.';
        }
        if (strlen($observaciones) > 255) {
            $errores[] = 'Las observaciones no pueden tener más de 255 caracteres.';
        }

        if (empty($errores)) {
            // Crear array de recursos con valores booleanos
            $recursos_array = array_fill_keys($recursos_disponibles, false);
            foreach ($recursos_sel as $r) {
                if (isset($recursos_array[$r])) {
                    $recursos_array[$r] = true;
                }
            }

            $recursos_json = json_encode($recursos_array, JSON_UNESCAPED_UNICODE);

            // Insertar en la base de datos
            $stmt = $conn->prepare("INSERT INTO salones (nombre_salon, capacidad, ubicacion, observaciones, recursos, estado)
                                    VALUES (?, ?, ?, ?, ?, 'disponible')");
            $stmt->bind_param("sisss", $nombre, $capacidad, $ubicacion, $observaciones, $recursos_json);

            if ($stmt->execute()) {
                echo '<div class="alert alert-success mt-3">✅ Salón agregado correctamente.</div>';
            } else {
                echo '<div class="alert alert-danger mt-3">❌ Error al agregar salón: ' . htmlspecialchars($stmt->error) . '</div>';
            }

            $stmt->close();
        } else {
            foreach ($errores as $error) {
                echo '<div class="alert alert-danger mt-3">❌ ' . htmlspecialchars($error) . '</div>';
            }
        }
    }
}
?>

<section class="agregar-salon">
  <div class="page-header">
    <h2><i class="bi bi-building-add"></i> Agregar Nuevo Salón</h2>
  </div>

  <form method="POST" class="card" onsubmit="return validarFormulario()">
    <div class="card-body">
      <input type="hidden" name="accion" value="agregar_salon">
      <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">

      <div class="mb-3">
        <label class="form-label">Nombre del salón (máx. 24 caracteres)</label>
        <input type="text" name="nombre_salon" class="form-control" maxlength="24" required>
      </div>

      <div class="mb-3">
        <label class="form-label">Capacidad (máx. 50 personas)</label>
        <input type="text" name="capacidad" class="form-control"
               oninput="validarNumero(this)"
               onkeypress="return (event.charCode >= 48 && event.charCode <= 57)"
               placeholder="Solo números del 1 al 50"
               required>
      </div>

      <div class="mb-3">
        <label class="form-label">Ubicación (máx. 24 caracteres)</label>
        <input type="text" name="ubicacion" class="form-control" maxlength="24" required>
      </div>

      <div class="mb-3">
        <label class="form-label">Descripción / Observaciones (máx. 255 caracteres)</label>
        <textarea name="observaciones" class="form-control" rows="3" maxlength="255" placeholder="Opcional..."></textarea>
      </div>

      <div class="mb-3">
        <label class="form-label">Recursos disponibles</label>
        <div class="checkbox-grid">
          <?php foreach ($recursos_disponibles as $r): ?>
            <label class="checkbox-item">
              <input type="checkbox" name="recursos[]" value="<?= $r ?>">
              <span><?= ucfirst($r) ?></span>
            </label>
          <?php endforeach; ?>
        </div>
      </div>

      <button type="submit" class="btn btn-success"><i class="bi bi-save"></i> Agregar salón</button>
    </div>
  </form>
</section>

<script src="/Agora/Agora/assets/agregar_salon.js"></script>
