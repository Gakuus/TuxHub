<?php
if (!isset($_SESSION)) session_start();

require_once __DIR__ . '/../backend/db_connection.php';

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
    $profesor_id = intval($_POST['profesor_id']);
    $grupos_asignados = $_POST['grupos'] ?? [];

    // Borramos asignaciones anteriores
    $conn->query("DELETE FROM grupos_profesores WHERE profesor_id = $profesor_id");

    // Insertamos nuevas asignaciones
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

// ==============================
// 3. Obtener grupos y filtrar por turno y estado si se seleccionó
// ==============================
$selected_turno = $_POST['turno'] ?? '';
$selected_estado = $_POST['estado'] ?? '';

$grupos_query = "SELECT id, nombre, turno, activa FROM grupos WHERE 1=1";

if ($selected_turno !== '') {
    $grupos_query .= " AND turno = '" . $conn->real_escape_string($selected_turno) . "'";
}

if ($selected_estado !== '') {
    $grupos_query .= " AND activa = " . intval($selected_estado);
}

$grupos_query .= " ORDER BY nombre";
$grupos = $conn->query($grupos_query);

// ==============================
// 4. Grupos asignados al profesor
// ==============================
$grupos_profesor = [];
if (isset($_POST['profesor_id']) && $_POST['profesor_id'] !== '') {
    $profesor_id = intval($_POST['profesor_id']);
    $res = $conn->query("SELECT grupo_id FROM grupos_profesores WHERE profesor_id = $profesor_id");
    $grupos_profesor = array_column($res->fetch_all(MYSQLI_ASSOC), 'grupo_id');
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Asignar Grupos a Profesores</title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <!-- CSS personalizado -->
    <link rel="stylesheet" href="/Agora/Agora/css/asignar_grupos.css">
</head>
<body>
    <div class="container-fluid mt-4">
        <h2 class="mb-4 text-center">
            <i class="bi bi-people"></i> Asignar Grupos a Profesores
        </h2>

        <form method="POST" class="p-4 bg-white rounded shadow-sm">
            <!-- Profesor -->
            <div class="mb-3">
                <label class="form-label fw-bold">Profesor:</label>
                <select name="profesor_id" class="form-select" onchange="this.form.submit()" required>
                    <option value="">Seleccionar profesor...</option>
                    <?php while ($p = $profesores->fetch_assoc()): ?>
                        <option value="<?= $p['id'] ?>" <?= (isset($_POST['profesor_id']) && $_POST['profesor_id'] == $p['id']) ? 'selected' : '' ?>>
                            <?= htmlspecialchars($p['nombre']) ?>
                        </option>
                    <?php endwhile; ?>
                </select>
            </div>

            <!-- Filtros -->
            <?php if (isset($_POST['profesor_id']) && $_POST['profesor_id'] !== ''): ?>
            <div class="row mb-3">
                <!-- Turno -->
                <div class="col-md-6">
                    <label class="form-label fw-bold">Turno:</label>
                    <select name="turno" class="form-select" onchange="this.form.submit()">
                        <option value="">-- Todos los turnos --</option>
                        <?php foreach($turnos as $t): ?>
                            <option value="<?= htmlspecialchars($t) ?>" <?= ($selected_turno == $t) ? 'selected' : '' ?>>
                                <?= ucfirst(htmlspecialchars($t)) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <!-- Estado -->
                <div class="col-md-6">
                    <label class="form-label fw-bold">Estado:</label>
                    <select name="estado" class="form-select" onchange="this.form.submit()">
                        <option value="">-- Todos los estados --</option>
                        <option value="1" <?= ($selected_estado === '1') ? 'selected' : '' ?>>Activos</option>
                        <option value="0" <?= ($selected_estado === '0') ? 'selected' : '' ?>>Inactivos</option>
                    </select>
                </div>
            </div>
            <?php endif; ?>

            <!-- Grupos -->
            <?php if (isset($_POST['profesor_id']) && $_POST['profesor_id'] !== ''): ?>
                <div class="mb-3">
                    <label class="form-label fw-bold">Grupos:</label>
                    
                    <!-- Contadores -->
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <span class="badge bg-success" id="contador-activos">0 activos</span>
                            <span class="badge bg-secondary" id="contador-inactivos">0 inactivos</span>
                        </div>
                        <div class="col-md-6 text-end">
                            <span class="badge bg-primary" id="contador-total">0 seleccionados</span>
                        </div>
                    </div>
                    
                    <div class="row" id="grupos-container">
                        <?php while ($g = $grupos->fetch_assoc()): ?>
                            <div class="col-md-4 mb-2 grupo-item" data-estado="<?= $g['activa'] ? 'activo' : 'inactivo' ?>">
                                <div class="form-check">
                                    <input class="form-check-input grupo-checkbox" 
                                           type="checkbox"
                                           name="grupos[]" 
                                           value="<?= $g['id'] ?>"
                                           id="grupo_<?= $g['id'] ?>"
                                           <?= in_array($g['id'], $grupos_profesor) ? 'checked' : '' ?>
                                           data-estado="<?= $g['activa'] ? 'activo' : 'inactivo' ?>">
                                    <label class="form-check-label <?= $g['activa'] ? '' : 'text-muted' ?>" for="grupo_<?= $g['id'] ?>">
                                        <?= htmlspecialchars($g['nombre']) ?> (<?= htmlspecialchars($g['turno']) ?>)
                                        <?php if (!$g['activa']): ?>
                                            <span class="badge bg-secondary ms-1">Inactivo</span>
                                        <?php endif; ?>
                                    </label>
                                </div>
                            </div>
                        <?php endwhile; ?>
                    </div>
                </div>

                <button type="submit" class="btn btn-primary w-100 mt-3">
                    <i class="bi bi-save"></i> Guardar Cambios
                </button>
            <?php endif; ?>
        </form>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <!-- JavaScript personalizado -->
    <script src="/Agora/Agora/assets/asignar_grupos.js"></script>
</body>
</html>