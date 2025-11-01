<?php
// pages/agregar_recurso.php
session_start();
require_once __DIR__ . '/../backend/db_connection.php';
$conn->set_charset('utf8mb4');

// Mostrar errores de PHP (durante desarrollo)
ini_set('display_errors', 1);
error_reporting(E_ALL);

// ---------- validar sesión y permisos ----------
if (!isset($_SESSION['user_id'])) {
    header('Location: dashboard.php?page=recursos');
    exit;
}

$rol = strtolower($_SESSION['rol'] ?? 'invitado');
if ($rol !== 'admin') {
    header('Location: dashboard.php?page=recursos');
    exit;
}

$user_id = (int)($_SESSION['user_id'] ?? 0);
$errors = [];
$notices = [];
$recurso_data = [];

// ---------- Si estamos editando, cargar datos del recurso ----------
$edit_id = isset($_GET['edit']) ? (int)$_GET['edit'] : 0;
$is_editing = ($edit_id > 0);

if ($is_editing) {
    try {
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
          WHERE r.id = ?
        ";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param('i', $edit_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 0) {
            $errors[] = "Recurso no encontrado";
            $is_editing = false;
        } else {
            $recurso_data = $result->fetch_assoc();
        }
        $stmt->close();
    } catch (Exception $e) {
        $errors[] = "Error al cargar recurso: " . $e->getMessage();
        $is_editing = false;
    }
}

// ---------- consultas para selects ----------
try {
    // Salones
    $res_salones = $conn->query("SELECT id, nombre_salon FROM salones ORDER BY nombre_salon ASC");
    if ($res_salones === false) throw new Exception("Error al consultar salones: " . $conn->error);

} catch (Exception $e) {
    $errors[] = $e->getMessage();
    if (!isset($res_salones)) $res_salones = null;
}
?>
<div class="d-flex justify-content-between align-items-center mb-4">
    <h4 class="mb-0"><?= $is_editing ? '✏️ Editar Recurso' : '➕ Agregar Recurso' ?></h4>
    <a href="dashboard.php?page=recursos" class="btn btn-secondary">
        <i class="bi bi-arrow-left me-1"></i> Volver a Recursos
    </a>
</div>

<!-- Errores / avisos -->
<?php foreach ($errors as $err): ?>
    <div class="alert alert-danger"><?= htmlspecialchars($err) ?></div>
<?php endforeach; ?>

<!-- Formulario -->
<form method="POST" action="/Agora/Agora/backend/recursos_backend.php" class="row g-3" id="formRecurso">
    <input type="hidden" name="id" value="<?= $is_editing ? $recurso_data['id'] : '' ?>">
    
    <div class="col-md-6">
        <label for="nombre" class="form-label">Nombre del Recurso *</label>
        <input type="text" class="form-control" id="nombre" name="nombre" 
               value="<?= $is_editing ? htmlspecialchars($recurso_data['nombre']) : '' ?>" 
               maxlength="24" required>
        <div class="form-text">Máximo 24 caracteres</div>
    </div>
    
    <div class="col-md-6">
        <label for="tipo" class="form-label">Tipo *</label>
        <select class="form-select" id="tipo" name="tipo" required onchange="toggleSalonField()">
            <option value="">Seleccionar tipo</option>
            <option value="Alargue" <?= $is_editing && $recurso_data['tipo']=='Alargue' ? 'selected' : '' ?>>Alargue</option>
            <option value="Control" <?= $is_editing && $recurso_data['tipo']=='Control' ? 'selected' : '' ?>>Control</option>
            <option value="Llave" <?= $is_editing && $recurso_data['tipo']=='Llave' ? 'selected' : '' ?>>Llave</option>
        </select>
    </div>
    
    <!-- Campo de Salón (solo para Llave y Control) -->
    <div class="col-12" id="salonField" style="display: none;">
        <label for="salon_id" class="form-label">Salón Asignado *</label>
        <select class="form-select" id="salon_id" name="salon_id">
            <option value="">— Seleccionar salón —</option>
            <?php if ($res_salones && $res_salones->num_rows > 0): 
                while($s = $res_salones->fetch_assoc()): ?>
                    <option value="<?= $s['id'] ?>" 
                        <?= $is_editing && $recurso_data['salon_id']==$s['id'] ? 'selected' : '' ?>>
                        <?= htmlspecialchars($s['nombre_salon']) ?>
                    </option>
            <?php endwhile; endif; ?>
        </select>
        <div class="form-text">Este campo es obligatorio para Llaves y Controles</div>
    </div>
    
    <div class="col-12">
        <label for="descripcion" class="form-label">Descripción</label>
        <textarea class="form-control" id="descripcion" name="descripcion" 
                  rows="3" maxlength="255"><?= $is_editing ? htmlspecialchars($recurso_data['descripcion'] ?? '') : '' ?></textarea>
        <div class="form-text">Máximo 255 caracteres</div>
    </div>
    
    <div class="col-12">
        <?php if ($is_editing): ?>
            <button type="submit" name="accion" value="actualizar" class="btn btn-primary">
                💾 Actualizar Recurso
            </button>
        <?php else: ?>
            <button type="submit" name="accion" value="crear" class="btn btn-primary">
                ➕ Crear Recurso
            </button>
        <?php endif; ?>
    </div>
</form>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('formRecurso');
    const tipoSelect = document.getElementById('tipo');
    const salonField = document.getElementById('salonField');
    const salonSelect = document.getElementById('salon_id');

    // Función para mostrar/ocultar el campo de salón
    function toggleSalonField() {
        const tipo = tipoSelect.value;
        
        if (tipo === 'Llave' || tipo === 'Control') {
            salonField.style.display = 'block';
            salonSelect.required = true;
        } else {
            salonField.style.display = 'none';
            salonSelect.required = false;
            salonSelect.value = '';
        }
    }

    // Ejecutar al cargar la página para establecer el estado inicial
    toggleSalonField();

    // Validación y envío del formulario
    form.addEventListener('submit', function(e) {
        const nombre = document.getElementById('nombre').value.trim();
        const tipo = document.getElementById('tipo').value;
        const salon_id = document.getElementById('salon_id').value;
        
        if (!nombre) {
            alert('Por favor ingresa un nombre para el recurso');
            document.getElementById('nombre').focus();
            e.preventDefault();
            return;
        }
        
        if (!tipo) {
            alert('Por favor selecciona un tipo de recurso');
            document.getElementById('tipo').focus();
            e.preventDefault();
            return;
        }

        // Validar salón para Llaves y Controles
        if ((tipo === 'Llave' || tipo === 'Control') && !salon_id) {
            alert('Por favor selecciona un salón para el recurso de tipo ' + tipo);
            document.getElementById('salon_id').focus();
            e.preventDefault();
            return;
        }
    });

    // También ejecutar cuando cambie el tipo (por si acaso)
    tipoSelect.addEventListener('change', toggleSalonField);
});
</script>