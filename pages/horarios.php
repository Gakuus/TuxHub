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
$filtro_estado_materias = $_GET['estado_materias'] ?? 'activas'; // Filtro: activas, inactivas, todas
$filtro_estado_grupos = $_GET['estado_grupos'] ?? 'activos'; // Nuevo filtro: activos, inactivos, todos

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
    $sql = "SELECT id, nombre, activa FROM grupos";
    $params = [];
    $types = "";
    
    $conditions = [];
    
    if($turno_seleccionado){
        $conditions[] = "turno = ?";
        $params[] = $turno_seleccionado;
        $types .= "s";
    }
    
    // Aplicar filtro de estado de grupos
    if ($filtro_estado_grupos === 'activos') {
        $conditions[] = "(activa = 1 OR activa IS NULL)";
    } elseif ($filtro_estado_grupos === 'inactivos') {
        $conditions[] = "activa = 0";
    }
    // Si es 'todos', no aplicamos filtro
    
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
        SELECT g.id, g.nombre, g.activa
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
    
    // Aplicar filtro de estado de grupos para profesores
    if ($filtro_estado_grupos === 'activos') {
        $sql .= " AND (g.activa = 1 OR g.activa IS NULL)";
    } elseif ($filtro_estado_grupos === 'inactivos') {
        $sql .= " AND g.activa = 0";
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
        $stmt = $conn->prepare("SELECT id, nombre, activa FROM grupos WHERE id=?");
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
$materias_activas = []; // Para contar materias activas
$materias_inactivas = []; // Para contar materias inactivas

if($grupo_id){
    // Construir la consulta base
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
    
    // Aplicar filtro de estado de materias
    if ($filtro_estado_materias === 'activas') {
        $sql .= " AND (m.activa = 1 OR m.activa IS NULL)";
    } elseif ($filtro_estado_materias === 'inactivas') {
        $sql .= " AND m.activa = 0";
    }
    // Si es 'todas', no aplicamos filtro
    
    $sql .= " ORDER BY h.dia_id, h.bloque_id";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $res = $stmt->get_result();
    while($h = $res->fetch_assoc()){
        $horarios[$h['dia_id']][$h['bloque_id']] = $h;
        
        // Contar materias activas e inactivas
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

// Función para determinar la clase CSS según la materia
function getMateriaClass($materiaNombre, $esActiva = true) {
    $materia = strtolower($materiaNombre);
    
    $baseClass = '';
    if (strpos($materia, 'matem') !== false) $baseClass = 'matematica';
    elseif (strpos($materia, 'lengua') !== false || strpos($materia, 'castellano') !== false || strpos($materia, 'literatura') !== false) $baseClass = 'lengua';
    elseif (strpos($materia, 'ciencia') !== false || strpos($materia, 'física') !== false || strpos($materia, 'química') !== false || strpos($materia, 'biología') !== false) $baseClass = 'ciencias';
    elseif (strpos($materia, 'historia') !== false || strpos($materia, 'sociales') !== false || strpos($materia, 'geografía') !== false) $baseClass = 'historia';
    elseif (strpos($materia, 'inglés') !== false || strpos($materia, 'ingles') !== false || strpos($materia, 'idioma') !== false) $baseClass = 'ingles';
    elseif (strpos($materia, 'educación física') !== false || strpos($materia, 'deporte') !== false || strpos($materia, 'deportes') !== false) $baseClass = 'educacion-fisica';
    elseif (strpos($materia, 'arte') !== false || strpos($materia, 'música') !== false || strpos($materia, 'danza') !== false || strpos($materia, 'teatro') !== false) $baseClass = 'arte';
    elseif (strpos($materia, 'tecnología') !== false || strpos($materia, 'informática') !== false || strpos($materia, 'computación') !== false) $baseClass = 'tecnologia';
    else $baseClass = 'default';
    
    // Agregar clase de estado si la materia está inactiva
    if (!$esActiva) {
        $baseClass .= ' materia-inactiva';
    }
    
    return $baseClass;
}

// Función para determinar si un grupo está activo
function isGrupoActivo($grupo) {
    return $grupo['activa'] == 1 || $grupo['activa'] === null;
}

// Obtener estadísticas de materias
$total_materias = count($materias_activas) + count($materias_inactivas);
$materias_activas_count = count($materias_activas);
$materias_inactivas_count = count($materias_inactivas);

// Obtener estadísticas de grupos
$grupos_activos = array_filter($grupos, 'isGrupoActivo');
$grupos_inactivos = array_filter($grupos, function($grupo) {
    return $grupo['activa'] == 0;
});
$grupos_activos_count = count($grupos_activos);
$grupos_inactivos_count = count($grupos_inactivos);

// Verificar si el grupo seleccionado está activo
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

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Horarios - Sistema Escolar</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    <link href="/Agora/Agora/css/horarios.css" rel="stylesheet">
</head>
<body>
    <div class="container-fluid py-4">
        <!-- Header -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="page-header animate-fade-in">
                    <h1 class="display-5 fw-bold">
                        <i class="bi bi-calendar-week me-3"></i>Horarios Escolares
                    </h1>
                    <p class="text-muted">Visualiza y gestiona los horarios de clases</p>
                </div>
            </div>
        </div>

        <!-- Panel de selección -->
        <?php if($rol === "admin" || $rol === "profesor"): ?>
        <div class="row mb-4">
            <div class="col-12">
                <div class="selection-panel card animate-slide-in">
                    <div class="card-header py-3">
                        <h5 class="mb-0"><i class="bi bi-funnel me-2"></i>Filtros de Búsqueda</h5>
                    </div>
                    <div class="card-body p-4">
                        <form method="get" action="" id="horarioForm">
                            <input type="hidden" name="page" value="horarios">
                            <div class="row g-3 align-items-end">
                                <!-- Selector de turno -->
                                <div class="col-md-3 col-lg-2">
                                    <label class="form-label fw-semibold">Turno</label>
                                    <select name="turno" class="form-select form-select-lg" onchange="this.form.submit()">
                                        <option value="">-- Todos --</option>
                                        <option value="mañana" <?= ($turno_seleccionado == 'mañana') ? "selected" : "" ?>>Mañana</option>
                                        <option value="tarde" <?= ($turno_seleccionado == 'tarde') ? "selected" : "" ?>>Tarde</option>
                                        <option value="noche" <?= ($turno_seleccionado == 'noche') ? "selected" : "" ?>>Noche</option>
                                    </select>
                                </div>
                                <!-- Filtro de estado de grupos -->
                                <div class="col-md-3 col-lg-2">
                                    <label class="form-label fw-semibold">Estado Grupos</label>
                                    <select name="estado_grupos" class="form-select form-select-lg" onchange="this.form.submit()">
                                        <option value="activos" <?= ($filtro_estado_grupos == 'activos') ? "selected" : "" ?>>Activos</option>
                                        <option value="inactivos" <?= ($filtro_estado_grupos == 'inactivos') ? "selected" : "" ?>>Inactivos</option>
                                        <option value="todos" <?= ($filtro_estado_grupos == 'todos') ? "selected" : "" ?>>Todos</option>
                                    </select>
                                </div>
                                <!-- Selector de grupo -->
                                <div class="col-md-3 col-lg-2">
                                    <label class="form-label fw-semibold">Grupo</label>
                                    <select name="grupo_id" class="form-select form-select-lg" required onchange="this.form.submit()">
                                        <option value="">-- Seleccione un grupo --</option>
                                        <?php foreach($grupos as $g): 
                                            $esActivo = isGrupoActivo($g);
                                        ?>
                                            <option value="<?= $g['id'] ?>" 
                                                    <?= ($grupo_id == $g['id']) ? "selected" : "" ?>
                                                    <?= !$esActivo ? 'class="text-warning"' : '' ?>>
                                                <?= htmlspecialchars($g['nombre']) ?>
                                                <?= !$esActivo ? ' (Inactivo)' : '' ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <!-- Filtro de estado de materias -->
                                <div class="col-md-2 col-lg-2">
                                    <label class="form-label fw-semibold">Estado Materias</label>
                                    <select name="estado_materias" class="form-select form-select-lg" onchange="this.form.submit()">
                                        <option value="activas" <?= ($filtro_estado_materias == 'activas') ? "selected" : "" ?>>Activas</option>
                                        <option value="inactivas" <?= ($filtro_estado_materias == 'inactivas') ? "selected" : "" ?>>Inactivas</option>
                                        <option value="todas" <?= ($filtro_estado_materias == 'todas') ? "selected" : "" ?>>Todas</option>
                                    </select>
                                </div>
                                <!-- Botón de acción -->
                                <div class="col-md-1 col-lg-1">
                                    <button type="button" class="btn btn-primary btn-lg w-100" onclick="horariosManager.exportHorario()" title="Exportar horario">
                                        <i class="bi bi-download"></i>
                                    </button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
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
        <div class="row mb-4">
            <div class="col-12">
                <div class="selected-group-indicator animate-fade-in <?= !$grupo_activo ? 'grupo-inactivo' : '' ?>">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <h5 class="mb-0 fw-bold">
                                <i class="bi bi-people-fill me-2"></i>
                                Grupo: <?= htmlspecialchars($grupo_nombre) ?>
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
                            <i class="bi bi-clock me-1"></i><?= $bloques[0]['hora_inicio'] ?> - <?= end($bloques)['hora_fin'] ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <!-- Tabla de horarios -->
        <?php if($grupo_id): ?>
        <div class="row">
            <div class="col-12">
                <div class="schedule-container animate-fade-in">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0" id="horarioTable">
                            <thead>
                                <tr>
                                    <th class="time-cell">Horario</th>
                                    <?php foreach($dias as $d_id => $d_nombre): ?>
                                        <th class="text-center"><?= $d_nombre ?></th>
                                    <?php endforeach; ?>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach($bloques as $index => $b): ?>
                                    <tr class="schedule-row">
                                        <td class="time-cell">
                                            <div class="d-flex flex-column">
                                                <span class="fw-bold"><?= $b['hora_inicio'] ?></span>
                                                <span class="text-muted small"><?= $b['hora_fin'] ?></span>
                                            </div>
                                        </td>
                                        <?php foreach($dias as $d_id => $d_nombre): ?>
                                            <?php if(isset($horarios[$d_id][$b['id']])): 
                                                $h = $horarios[$d_id][$b['id']];
                                                $esActiva = $h['materia_activa'] == 1;
                                                $materia_class = getMateriaClass($h['nombre_materia'], $esActiva);
                                            ?>
                                                <td class="class-cell">
                                                    <div class="class-card <?= $materia_class ?>" 
                                                         data-materia="<?= htmlspecialchars($h['nombre_materia']) ?>"
                                                         data-profesor="<?= htmlspecialchars($h['profesor']) ?>"
                                                         data-salon="<?= htmlspecialchars($h['nombre_salon']) ?>"
                                                         data-activa="<?= $esActiva ? 'true' : 'false' ?>">
                                                        <div class="class-content">
                                                            <h6>
                                                                <?= htmlspecialchars($h['nombre_materia']) ?>
                                                                <?php if(!$esActiva): ?>
                                                                    <span class="badge bg-warning ms-1" title="Materia inactiva">
                                                                        <i class="bi bi-pause-circle"></i>
                                                                    </span>
                                                                <?php endif; ?>
                                                            </h6>
                                                            <div class="class-details">
                                                                <small>
                                                                    <i class="bi bi-person me-1"></i>
                                                                    <?= htmlspecialchars($h['profesor']) ?>
                                                                </small>
                                                                <small>
                                                                    <i class="bi bi-geo-alt me-1"></i>
                                                                    <?= htmlspecialchars($h['nombre_salon']) ?>
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
            </div>
        </div>
        <?php else: ?>
        <!-- Estado vacío -->
        <div class="row mt-5">
            <div class="col-12 col-md-8 mx-auto">
                <div class="empty-state">
                    <div class="empty-icon mb-4">
                        <i class="bi bi-calendar-x"></i>
                    </div>
                    <h4 class="text-muted mb-3">Selecciona un grupo</h4>
                    <p class="text-muted mb-4">Para visualizar el horario, por favor selecciona un grupo de la lista superior.</p>
                    <?php if($grupos_activos_count > 0 || $grupos_inactivos_count > 0): ?>
                    <div class="stats-container mt-4">
                        <div class="row text-center">
                            <div class="col-6">
                                <div class="stat-item">
                                    <h5 class="text-success"><?= $grupos_activos_count ?></h5>
                                    <small>Grupos Activos</small>
                                </div>
                            </div>
                            <div class="col-6">
                                <div class="stat-item">
                                    <h5 class="text-warning"><?= $grupos_inactivos_count ?></h5>
                                    <small>Grupos Inactivos</small>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <?php endif; ?>
    </div>

    <!-- Notifications Container -->
    <div id="notifications-container"></div>

    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="/Agora/Agora/assets/horarios.js"></script>
</body>
</html>