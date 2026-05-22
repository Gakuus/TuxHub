<?php
require_once __DIR__ . "/../backend/db_connection.php";
session_start();

// ==========================
// Variables de sesión
// ==========================
$rol = strtolower($_SESSION['rol'] ?? "invitado");
$usuario_id = $_SESSION['user_id'] ?? null;
$grupo_id = $_GET['grupo_id'] ?? ($_SESSION['grupo_id'] ?? null);
$turno_seleccionado = $_GET['turno'] ?? '';
$filtro_estado_materias = $_GET['estado_materias'] ?? 'activas';
$filtro_estado_grupos = $_GET['estado_grupos'] ?? 'activos';

// ==========================
// Días fijos y cargar días desde tabla
// ==========================
$dias_result = $conn->query("SELECT id, nombre_dia FROM dias ORDER BY id");
$dias = [];
if($dias_result && $dias_result->num_rows){
    while($d = $dias_result->fetch_assoc()){
        $dias[$d['id']] = $d['nombre_dia'];
    }
} else {
    $dias = [1=>'Lunes',2=>'Martes',3=>'Miércoles',4=>'Jueves',5=>'Viernes'];
}

// ==========================
// Obtener grupos según rol y turno CON FILTRO DE ESTADO
// ==========================
$grupos = [];
if($rol === "admin"){
    $sql = "SELECT id, nombre, activo FROM grupos";
    $params = [];
    $types = "";
    
    $conditions = [];
    
    if($turno_seleccionado){
        $conditions[] = "turno = ?";
        $params[] = $turno_seleccionado;
        $types .= "s";
    }
    
    if ($filtro_estado_grupos === 'activos') {
        $conditions[] = "(activo = 1 OR activo IS NULL)";
    } elseif ($filtro_estado_grupos === 'inactivos') {
        $conditions[] = "activo = 0";
    }
    
    if (!empty($conditions)) {
        $sql .= " WHERE " . implode(" AND ", $conditions);
    }
    
    $sql .= " ORDER BY nombre";
    
    if($params){
        $stmt = $conn->prepare($sql);
        $stmt->bind_param($types, ...$params);
        $stmt->execute();
        $res = $stmt->get_result();
    } else {
        $res = $conn->query($sql);
    }
    
    while($g = $res->fetch_assoc()) $grupos[] = $g;
    if(isset($stmt)) $stmt->close();
    
} elseif($rol === "profesor"){
    $sql = "
        SELECT g.id, g.nombre, g.activo
        FROM grupos g
        JOIN grupos_profesores gp ON gp.grupo_id = g.id
        WHERE gp.profesor_id = ?
    ";
    $params = [$usuario_id];
    $types = "i";
    
    if($turno_seleccionado){
        $sql .= " AND g.turno = ?";
        $params[] = $turno_seleccionado;
        $types .= "s";
    }
    
    if ($filtro_estado_grupos === 'activos') {
        $sql .= " AND (g.activo = 1 OR g.activo IS NULL)";
    } elseif ($filtro_estado_grupos === 'inactivos') {
        $sql .= " AND g.activo = 0";
    }
    
    $sql .= " ORDER BY g.nombre";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $res = $stmt->get_result();
    while($g = $res->fetch_assoc()) $grupos[] = $g;
    $stmt->close();
    
} elseif($rol === "alumno"){
    if($grupo_id){
        $stmt = $conn->prepare("SELECT id, nombre, activo FROM grupos WHERE id=?");
        $stmt->bind_param("i", $grupo_id);
        $stmt->execute();
        $res = $stmt->get_result();
        if($res->num_rows) $grupos[] = $res->fetch_assoc();
        $stmt->close();
    }
}

// ==========================
// Obtener bloques horarios del grupo
// ==========================
$bloques = [];
if($grupo_id){
    $stmt = $conn->prepare("
        SELECT b.id, b.hora_inicio, b.hora_fin
        FROM bloques_horarios b
        JOIN grupos g ON g.turno = b.turno
        WHERE g.id=?
        ORDER BY b.hora_inicio
    ");
    $stmt->bind_param("i", $grupo_id);
    $stmt->execute();
    $res = $stmt->get_result();
    while($b = $res->fetch_assoc()) $bloques[] = $b;
    $stmt->close();
}

// fallback si no hay bloques
if(empty($bloques)){
    $bloques = [
        ['id'=>1,'hora_inicio'=>'07:00','hora_fin'=>'07:45'],
        ['id'=>2,'hora_inicio'=>'07:50','hora_fin'=>'08:35'],
        ['id'=>3,'hora_inicio'=>'08:40','hora_fin'=>'09:25'],
        ['id'=>4,'hora_inicio'=>'09:30','hora_fin'=>'10:15'],
        ['id'=>5,'hora_inicio'=>'10:20','hora_fin'=>'11:05'],
        ['id'=>6,'hora_inicio'=>'11:10','hora_fin'=>'11:55'],
        ['id'=>7,'hora_inicio'=>'12:00','hora_fin'=>'12:45'],
        ['id'=>8,'hora_inicio'=>'12:50','hora_fin'=>'13:35'],
    ];
}

// ==========================
// Obtener horarios del grupo con filtro de estado
// ==========================
$horarios = [];
$materias_activas = [];
$materias_inactivas = [];

if($grupo_id){
    $sql = "
        SELECT h.*, u.nombre AS profesor, m.nombre_materia, s.nombre_salon, m.activa AS materia_activa
        FROM horarios h
        LEFT JOIN usuarios u ON u.id = h.profesor_id
        LEFT JOIN materias m ON m.id = h.materia_id
        LEFT JOIN salones s ON s.id = h.salon_id
        WHERE h.grupo_id = ?
    ";
    
    $params = [$grupo_id];
    $types = "i";
    
    if ($filtro_estado_materias === 'activas') {
        $sql .= " AND (m.activa = 1 OR m.activa IS NULL)";
    } elseif ($filtro_estado_materias === 'inactivas') {
        $sql .= " AND m.activa = 0";
    }
    
    $sql .= " ORDER BY h.dia_id, h.bloque_id";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $res = $stmt->get_result();
    while($h = $res->fetch_assoc()){
        $horarios[$h['dia_id']][$h['bloque_id']] = $h;
        
        if ($h['materia_activa'] == 1) {
            $materias_activas[$h['materia_id']] = true;
        } elseif ($h['materia_activa'] == 0) {
            $materias_inactivas[$h['materia_id']] = true;
        }
    }
    $stmt->close();
}

// ==========================
// Funciones auxiliares
// ==========================
function horaEnMinutos($hora){
    list($h,$m) = explode(":",$hora);
    return $h*60 + $m;
}

function getMateriaClass($materiaNombre, $esActiva = true) {
    $materia = strtolower($materiaNombre);
    
    $baseClass = '';
    if (strpos($materia, 'matem') !== false) $baseClass = 'materia-1';
    elseif (strpos($materia, 'lengua') !== false || strpos($materia, 'castellano') !== false || strpos($materia, 'literatura') !== false) $baseClass = 'materia-2';
    elseif (strpos($materia, 'ciencia') !== false || strpos($materia, 'física') !== false || strpos($materia, 'química') !== false || strpos($materia, 'biología') !== false) $baseClass = 'materia-3';
    elseif (strpos($materia, 'historia') !== false || strpos($materia, 'sociales') !== false || strpos($materia, 'geografía') !== false) $baseClass = 'materia-4';
    elseif (strpos($materia, 'inglés') !== false || strpos($materia, 'ingles') !== false || strpos($materia, 'idioma') !== false) $baseClass = 'materia-5';
    elseif (strpos($materia, 'educación física') !== false || strpos($materia, 'deporte') !== false || strpos($materia, 'deportes') !== false) $baseClass = 'materia-6';
    elseif (strpos($materia, 'arte') !== false || strpos($materia, 'música') !== false || strpos($materia, 'danza') !== false || strpos($materia, 'teatro') !== false) $baseClass = 'materia-7';
    elseif (strpos($materia, 'tecnología') !== false || strpos($materia, 'informática') !== false || strpos($materia, 'computación') !== false) $baseClass = 'materia-8';
    else $baseClass = 'materia-default';
    
    if (!$esActiva) {
        $baseClass .= ' materia-inactiva';
    }
    
    return $baseClass;
}

function isGrupoActivo($grupo) {
    return $grupo['activo'] == 1 || $grupo['activo'] === null;
}

$total_materias = count($materias_activas) + count($materias_inactivas);
$materias_activas_count = count($materias_activas);
$materias_inactivas_count = count($materias_inactivas);

$grupos_activos = array_filter($grupos, 'isGrupoActivo');
$grupos_inactivos = array_filter($grupos, function($grupo) {
    return $grupo['activo'] == 0;
});
$grupos_activos_count = count($grupos_activos);
$grupos_inactivos_count = count($grupos_inactivos);

$grupo_activo = false;
if ($grupo_id && !empty($grupos)) {
    foreach($grupos as $g) {
        if($g['id'] == $grupo_id) {
            $grupo_activo = isGrupoActivo($g);
            break;
        }
    }
}

?>

<div class="schedule-section">
    <div class="page-header">
        <h2>
            <i class="bi bi-calendar-week"></i>
            Horarios Escolares
        </h2>
        <p class="text-muted">Visualiza y gestiona los horarios de clases</p>
    </div>

    <!-- Panel de selección -->
    <?php if($rol === "admin" || $rol === "profesor"): ?>
    <div class="card mb-4">
        <div class="card-header">
            <i class="bi bi-funnel"></i>Filtros de Búsqueda
        </div>
        <div class="card-body">
            <form method="get" action="" id="horarioForm">
                <input type="hidden" name="page" value="horarios">
                <div class="row g-3 align-items-end">
                    <div class="col-md-3 col-lg-2">
                        <label class="form-label">Turno</label>
                        <select name="turno" class="form-select" onchange="this.form.submit()">
                            <option value="">-- Todos --</option>
                            <option value="mañana" <?= ($turno_seleccionado == 'mañana') ? "selected" : "" ?>>Mañana</option>
                            <option value="tarde" <?= ($turno_seleccionado == 'tarde') ? "selected" : "" ?>>Tarde</option>
                            <option value="noche" <?= ($turno_seleccionado == 'noche') ? "selected" : "" ?>>Noche</option>
                        </select>
                    </div>
                    <div class="col-md-3 col-lg-2">
                        <label class="form-label">Estado Grupos</label>
                        <select name="estado_grupos" class="form-select" onchange="this.form.submit()">
                            <option value="activos" <?= ($filtro_estado_grupos == 'activos') ? "selected" : "" ?>>Activos</option>
                            <option value="inactivos" <?= ($filtro_estado_grupos == 'inactivos') ? "selected" : "" ?>>Inactivos</option>
                            <option value="todos" <?= ($filtro_estado_grupos == 'todos') ? "selected" : "" ?>>Todos</option>
                        </select>
                    </div>
                    <div class="col-md-3 col-lg-2">
                        <label class="form-label">Grupo</label>
                        <select name="grupo_id" class="form-select" required onchange="this.form.submit()">
                            <option value="">-- Seleccione un grupo --</option>
                            <?php foreach($grupos as $g): 
                                $esActivo = isGrupoActivo($g);
                            ?>
                                <option value="<?= htmlspecialchars($g['id'], ENT_QUOTES, 'UTF-8') ?>" 
                                        <?= ($grupo_id == $g['id']) ? "selected" : "" ?>
                                        <?= !$esActivo ? 'class="text-warning"' : '' ?>>
                                    <?= htmlspecialchars($g['nombre'], ENT_QUOTES, 'UTF-8') ?>
                                    <?= !$esActivo ? ' (Inactivo)' : '' ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-2 col-lg-2">
                        <label class="form-label">Estado Materias</label>
                        <select name="estado_materias" class="form-select" onchange="this.form.submit()">
                            <option value="activas" <?= ($filtro_estado_materias == 'activas') ? "selected" : "" ?>>Activas</option>
                            <option value="inactivas" <?= ($filtro_estado_materias == 'inactivas') ? "selected" : "" ?>>Inactivas</option>
                            <option value="todas" <?= ($filtro_estado_materias == 'todas') ? "selected" : "" ?>>Todas</option>
                        </select>
                    </div>
                    <div class="col-md-1 col-lg-1">
                        <button type="button" class="btn btn-primary w-100" onclick="horariosManager.exportHorario()" title="Exportar horario">
                            <i class="bi bi-download"></i>
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>
    <?php endif; ?>

    <!-- Indicador de grupo seleccionado y estadísticas -->
    <?php if($grupo_id && !empty($grupos)): 
        $grupo_nombre = '';
        foreach($grupos as $g) {
            if($g['id'] == $grupo_id) {
                $grupo_nombre = $g['nombre'];
                break;
            }
        }
    ?>
    <div class="card mb-4 <?= !$grupo_activo ? 'border-warning' : '' ?>">
        <div class="card-body">
            <div class="d-flex align-items-center justify-content-between">
                <div>
                    <h5 class="mb-0 fw-bold">
                        <i class="bi bi-people-fill me-2"></i>
                        Grupo: <?= htmlspecialchars($grupo_nombre, ENT_QUOTES, 'UTF-8') ?>
                        <?php if(!$grupo_activo): ?>
                            <span class="badge bg-warning ms-2">
                                <i class="bi bi-pause-circle me-1"></i>Inactivo
                            </span>
                        <?php endif; ?>
                    </h5>
                    <small>
                        Horario actual - <?= count($bloques) ?> bloques horarios | 
                        <span class="text-success">
                            <i class="bi bi-check-circle"></i> <?= $materias_activas_count ?> materias activas
                        </span>
                        <?php if($materias_inactivas_count > 0): ?>
                        | <span class="text-warning">
                            <i class="bi bi-exclamation-triangle"></i> <?= $materias_inactivas_count ?> materias inactivas
                        </span>
                        <?php endif; ?>
                    </small>
                </div>
                <div class="badge bg-light text-primary fs-6 p-2">
                    <i class="bi bi-clock me-1"></i><?= htmlspecialchars($bloques[0]['hora_inicio'], ENT_QUOTES, 'UTF-8') ?> - <?= htmlspecialchars(end($bloques)['hora_fin'], ENT_QUOTES, 'UTF-8') ?>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- Tabla de horarios -->
    <?php if($grupo_id): ?>
    <div class="schedule-container">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0" id="horarioTable">
                <thead>
                    <tr>
                        <th class="time-cell">Horario</th>
                        <?php foreach($dias as $d_id => $d_nombre): ?>
                            <th class="text-center"><?= htmlspecialchars($d_nombre, ENT_QUOTES, 'UTF-8') ?></th>
                        <?php endforeach; ?>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach($bloques as $index => $b): ?>
                        <tr class="schedule-row">
                            <td class="time-cell">
                                <div class="d-flex flex-column">
                                    <span class="fw-bold"><?= htmlspecialchars($b['hora_inicio'], ENT_QUOTES, 'UTF-8') ?></span>
                                    <span class="text-muted small"><?= htmlspecialchars($b['hora_fin'], ENT_QUOTES, 'UTF-8') ?></span>
                                </div>
                            </td>
                            <?php foreach($dias as $d_id => $d_nombre): ?>
                                <?php if(isset($horarios[$d_id][$b['id']])): 
                                    $h = $horarios[$d_id][$b['id']];
                                    $esActiva = $h['materia_activa'] == 1;
                                    $materia_class = getMateriaClass($h['nombre_materia'], $esActiva);
                                ?>
                                    <td class="class-cell">
                                        <div class="class-card <?= htmlspecialchars($materia_class, ENT_QUOTES, 'UTF-8') ?>" 
                                             data-materia="<?= htmlspecialchars($h['nombre_materia'], ENT_QUOTES, 'UTF-8') ?>"
                                             data-profesor="<?= htmlspecialchars($h['profesor'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                                             data-salon="<?= htmlspecialchars($h['nombre_salon'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                                             data-activa="<?= $esActiva ? 'true' : 'false' ?>">
                                            <div class="class-content">
                                                <h6>
                                                    <?= htmlspecialchars($h['nombre_materia'], ENT_QUOTES, 'UTF-8') ?>
                                                    <?php if(!$esActiva): ?>
                                                        <span class="badge bg-warning ms-1" title="Materia inactiva">
                                                            <i class="bi bi-pause-circle"></i>
                                                        </span>
                                                    <?php endif; ?>
                                                </h6>
                                                <div class="class-details">
                                                    <small>
                                                        <i class="bi bi-person me-1"></i>
                                                        <?= htmlspecialchars($h['profesor'] ?? '', ENT_QUOTES, 'UTF-8') ?>
                                                    </small>
                                                    <small>
                                                        <i class="bi bi-geo-alt me-1"></i>
                                                        <?= htmlspecialchars($h['nombre_salon'] ?? '', ENT_QUOTES, 'UTF-8') ?>
                                                    </small>
                                                    <?php if(!$esActiva): ?>
                                                    <small class="text-warning">
                                                        <i class="bi bi-exclamation-triangle me-1"></i>
                                                        Materia inactiva
                                                    </small>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                        </div>
                                    </td>
                                <?php else: ?>
                                    <td class="empty-cell">
                                        <div class="text-center py-3">
                                            <small>Libre</small>
                                        </div>
                                    </td>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php else: ?>
    <div class="empty-state">
        <i class="bi bi-calendar-x"></i>
        <h4>Selecciona un grupo</h4>
        <p>Para visualizar el horario, por favor selecciona un grupo de la lista superior.</p>
        <?php if($grupos_activos_count > 0 || $grupos_inactivos_count > 0): ?>
        <div class="stats-grid mt-4" style="max-width: 400px; margin-left: auto; margin-right: auto;">
            <div class="stat-card">
                <div class="stat-value green"><?= $grupos_activos_count ?></div>
                <div class="stat-label">
                    <i class="bi bi-check-circle"></i>Grupos Activos
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-value yellow"><?= $grupos_inactivos_count ?></div>
                <div class="stat-label">
                    <i class="bi bi-exclamation-triangle"></i>Grupos Inactivos
                </div>
            </div>
        </div>
        <?php endif; ?>
    </div>
    <?php endif; ?>
</div>
