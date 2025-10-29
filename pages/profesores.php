<?php
if (!isset($_SESSION)) session_start();
require_once __DIR__ . '/../backend/db_connection.php';

$rol = $_SESSION['rol'] ?? null;
$user_id = $_SESSION['user_id'] ?? null;
$fecha_hoy = date("Y-m-d");

if (!$rol) die("⚠️ No hay sesión iniciada.");

// Obtener el día de la semana actual (1=Lunes, 7=Domingo)
$dia_actual_numero = date('N');
$dia_actual_nombre = '';
switch($dia_actual_numero) {
    case 1: $dia_actual_nombre = 'Lunes'; break;
    case 2: $dia_actual_nombre = 'Martes'; break;
    case 3: $dia_actual_nombre = 'Miércoles'; break;
    case 4: $dia_actual_nombre = 'Jueves'; break;
    case 5: $dia_actual_nombre = 'Viernes'; break;
    case 6: $dia_actual_nombre = 'Sábado'; break;
    case 7: $dia_actual_nombre = 'Domingo'; break;
}

// Cargar profesores (para admin)
$profesores = [];
if ($rol === 'admin') {
    $profesores_query = $conn->query("SELECT id, nombre FROM usuarios WHERE rol='profesor' ORDER BY nombre");
    while ($p = $profesores_query->fetch_assoc()) {
        $profesores[] = $p;
    }
}

// Cargar salones
$salones = [];
$salones_query = $conn->query("SELECT id, nombre_salon FROM salones ORDER BY nombre_salon");
while ($s = $salones_query->fetch_assoc()) {
    $salones[] = $s;
}
?>

<div class="container mt-4">
    <h2 class="text-center mb-4">📅 Gestión de Asistencias - Profesores</h2>

    <!-- FORMULARIO DE REGISTRO -->
    <?php if ($rol !== 'alumno'): ?>
    <div class="card shadow-sm mb-4">
        <div class="card-body">
            <h5>Registrar nueva asistencia</h5>
            <form id="formAsistencia">
                <input type="hidden" name="registrar_asistencia" value="1">
                <div class="row g-2">
                    
                    <?php if ($rol === 'admin'): ?>
                    <div class="col-md-3">
                        <label>Profesor</label>
                        <select name="usuario_id" id="selectProfesor" class="form-select" required>
                            <option value="">Seleccionar</option>
                            <?php foreach ($profesores as $profesor): ?>
                                <option value="<?= $profesor['id'] ?>"><?= htmlspecialchars($profesor['nombre']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <?php else: ?>
                        <input type="hidden" id="selectProfesor" name="usuario_id" value="<?= $user_id ?>">
                    <?php endif; ?>

                    <div class="col-md-3">
                        <label>Fecha</label>
                        <input type="date" name="fecha" id="fechaInput" class="form-control" value="<?= $fecha_hoy ?>" required>
                    </div>

                    <div class="col-md-3">
                        <label>Grupo</label>
                        <select name="grupo_id" id="selectGrupo" class="form-select" required>
                            <option value="">Seleccionar grupo</option>
                        </select>
                    </div>

                    <div class="col-md-3">
                        <label>Estado</label>
                        <select name="estado" class="form-select" required>
                            <option value="asistio">Asistió</option>
                            <option value="inasistencia">No asistió</option>
                        </select>
                    </div>

                    <div class="col-md-3">
                        <label>Bloque Horario</label>
                        <select name="bloque_id" id="selectBloque" class="form-select" required>
                            <option value="">Seleccionar bloque</option>
                        </select>
                    </div>

                    <div class="col-md-3">
                        <label>Salón</label>
                        <select name="salon_id" class="form-select">
                            <option value="">Seleccionar salón</option>
                            <?php foreach ($salones as $salon): ?>
                                <option value="<?= $salon['id'] ?>"><?= htmlspecialchars($salon['nombre_salon']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="col-md-12">
                        <label>Justificación</label>
                        <textarea name="justificacion" class="form-control" rows="2" placeholder="Opcional"></textarea>
                    </div>

                    <div class="col-md-12 text-end">
                        <button type="submit" class="btn btn-success mt-2">Guardar</button>
                    </div>
                </div>
            </form>
        </div>
    </div>
    <?php endif; ?>



    <!-- HISTORIAL -->
    <div class="card shadow-sm">
        <div class="card-body">
            <h5>Historial de Asistencias</h5>
            <div id="tablaAsistencias">Cargando...</div>
        </div>
    </div>
</div>

<script>
    const rolUsuario = "<?= $rol ?>";
    const userId = "<?= $user_id ?>";
    const diaActualNumero = <?= $dia_actual_numero ?>;
</script>
<script src="/Agora/Agora/assets/profesores.js"></script>