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
    $token = $_POST['csrf_token'] ?? '';
    $stored = $_SESSION['csrf_token'] ?? '';
    if (empty($token) || empty($stored) || !hash_equals($stored, $token)) {
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
            // Insertar en la base de datos
            $stmt = $conn->prepare("INSERT INTO salones (nombre_salon, capacidad, ubicacion, observaciones, estado)
                                    VALUES (?, ?, ?, ?, 'disponible')");
            $stmt->bind_param("siss", $nombre, $capacidad, $ubicacion, $observaciones);

            if ($stmt->execute()) {
                $salon_id = $stmt->insert_id;

                // Insertar recursos en tabla salon_recursos
                if (!empty($recursos_sel)) {
                    $stmt_r = $conn->prepare("INSERT INTO salon_recursos (salon_id, recurso, cantidad) VALUES (?, ?, 1)");
                    foreach ($recursos_sel as $r) {
                        if (in_array($r, $recursos_disponibles)) {
                            $stmt_r->bind_param("is", $salon_id, $r);
                            $stmt_r->execute();
                        }
                    }
                    $stmt_r->close();
                }

                echo '<div class="alert alert-success mt-3">Salón registrado correctamente.</div>';
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

<script src="assets/agregar_salon.js"></script>
