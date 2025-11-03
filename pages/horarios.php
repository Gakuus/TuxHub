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
// Obtener grupos según rol y turno
// ==========================
$grupos = [];
if($rol === "admin"){
    $sql = "SELECT id, nombre FROM grupos";
    $params = [];
    $types = "";
    
    if($turno_seleccionado){
        $sql .= " WHERE turno = ?";
        $params[] = $turno_seleccionado;
        $types .= "s";
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
        SELECT g.id, g.nombre
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
    
    $sql .= " ORDER BY g.nombre";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $res = $stmt->get_result();
    while($g = $res->fetch_assoc()) $grupos[] = $g;
    $stmt->close();
    
} elseif($rol === "alumno"){
    if($grupo_id){
        $stmt = $conn->prepare("SELECT id, nombre FROM grupos WHERE id=?");
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
// Obtener horarios del grupo
// ==========================
$horarios = [];
if($grupo_id){
    $stmt = $conn->prepare("
        SELECT h.*, u.nombre AS profesor, m.nombre_materia, s.nombre_salon
        FROM horarios h
        LEFT JOIN usuarios u ON u.id = h.profesor_id
        LEFT JOIN materias m ON m.id = h.materia_id
        LEFT JOIN salones s ON s.id = h.salon_id
        WHERE h.grupo_id = ?
        ORDER BY h.dia_id, h.bloque_id
    ");
    $stmt->bind_param("i", $grupo_id);
    $stmt->execute();
    $res = $stmt->get_result();
    while($h = $res->fetch_assoc()){
        $horarios[$h['dia_id']][$h['bloque_id']] = $h;
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
        <!-- Header con efectos visuales -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="page-header animate-fade-in">
                    <h1 class="display-5 fw-bold text-primary mb-2">
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
                <div class="selection-panel card border-0 shadow-lg animate-slide-up">
                    <div class="card-body p-4">
                        <form method="get" action="" id="horarioForm">
                            <input type="hidden" name="page" value="horarios">
                            <div class="row g-3 align-items-end">
                                <!-- Selector de turno -->
                                <div class="col-md-4 col-lg-3">
                                    <label class="form-label fw-semibold text-dark">Turno</label>
                                    <select name="turno" class="form-select form-select-lg shadow-sm" onchange="this.form.submit()">
                                        <option value="">-- Todos los turnos --</option>
                                        <option value="mañana" <?= ($turno_seleccionado == 'mañana') ? "selected" : "" ?>>🌅 Mañana</option>
                                        <option value="tarde" <?= ($turno_seleccionado == 'tarde') ? "selected" : "" ?>>☀️ Tarde</option>
                                        <option value="noche" <?= ($turno_seleccionado == 'noche') ? "selected" : "" ?>>🌙 Noche</option>
                                    </select>
                                </div>
                                <!-- Selector de grupo -->
                                <div class="col-md-6 col-lg-5">
                                    <label class="form-label fw-semibold text-dark">Grupo</label>
                                    <select name="grupo_id" class="form-select form-select-lg shadow-sm" required onchange="this.form.submit()">
                                        <option value="">-- Seleccione un grupo --</option>
                                        <?php foreach($grupos as $g): ?>
                                            <option value="<?= $g['id'] ?>" <?= ($grupo_id == $g['id']) ? "selected" : "" ?>>
                                                <?= htmlspecialchars($g['nombre']) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <!-- Botón de acción -->
                                <div class="col-md-2 col-lg-2">
                                    <button type="button" class="btn btn-primary btn-lg w-100 shadow" onclick="exportHorario()">
                                        <i class="bi bi-download me-2"></i>Exportar
                                    </button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <!-- Indicador de grupo seleccionado -->
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
                <div class="selected-group-indicator animate-bounce-in">
                    <div class="d-flex align-items-center justify-content-between p-3 rounded-3 bg-primary text-white">
                        <div>
                            <h5 class="mb-0 fw-bold">
                                <i class="bi bi-people-fill me-2"></i>
                                Grupo: <?= htmlspecialchars($grupo_nombre) ?>
                            </h5>
                            <small class="opacity-75">Horario actual</small>
                        </div>
                        <div class="badge bg-light text-primary fs-6">
                            <?= count($bloques) ?> bloques horarios
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
                <div class="schedule-container card border-0 shadow-lg animate-fade-in">
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0" id="horarioTable">
                                <thead class="table-dark">
                                    <tr>
                                        <th class="bg-primary text-white border-0 py-3 px-4" style="width: 120px;">
                                            <i class="bi bi-clock me-2"></i>Horario
                                        </th>
                                        <?php foreach($dias as $d_id => $d_nombre): ?>
                                            <th class="bg-secondary text-white border-0 py-3 text-center">
                                                <?= $d_nombre ?>
                                            </th>
                                        <?php endforeach; ?>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach($bloques as $index => $b): 
                                        $row_class = $index % 2 === 0 ? 'bg-light' : 'bg-white';
                                    ?>
                                        <tr class="<?= $row_class ?> schedule-row animate-fade-in" style="animation-delay: <?= $index * 0.1 ?>s">
                                            <td class="time-cell fw-bold text-dark py-3 px-4 border-end">
                                                <div class="d-flex flex-column">
                                                    <span class="h6 mb-1"><?= $b['hora_inicio'] ?></span>
                                                    <span class="text-muted small"><?= $b['hora_fin'] ?></span>
                                                </div>
                                            </td>
                                            <?php foreach($dias as $d_id => $d_nombre): ?>
                                                <?php if(isset($horarios[$d_id][$b['id']])): 
                                                    $h = $horarios[$d_id][$b['id']];
                                                    $materia_color = 'primary';
                                                    $badge_class = 'bg-primary';
                                                ?>
                                                    <td class="class-cell py-3 px-3 border-end">
                                                        <div class="class-card <?= $badge_class ?> text-white p-3 rounded-3 shadow-sm h-100">
                                                            <div class="class-content">
                                                                <h6 class="fw-bold mb-2"><?= htmlspecialchars($h['nombre_materia']) ?></h6>
                                                                <div class="class-details">
                                                                    <small class="d-block mb-1 opacity-90">
                                                                        <i class="bi bi-person me-1"></i>
                                                                        <?= htmlspecialchars($h['profesor']) ?>
                                                                    </small>
                                                                    <small class="d-block opacity-75">
                                                                        <i class="bi bi-geo-alt me-1"></i>
                                                                        <?= htmlspecialchars($h['nombre_salon']) ?>
                                                                    </small>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </td>
                                                <?php else: ?>
                                                    <td class="empty-cell py-3 px-3 border-end bg-light">
                                                        <div class="text-center text-muted">
                                                            <i class="bi bi-dash-lg d-block fs-4 mb-1"></i>
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
        </div>
        <?php else: ?>
        <!-- Estado vacío -->
        <div class="row mt-5">
            <div class="col-12 col-md-8 mx-auto">
                <div class="empty-state text-center animate-pulse">
                    <div class="empty-icon mb-4">
                        <i class="bi bi-calendar-x display-1 text-muted"></i>
                    </div>
                    <h4 class="text-muted mb-3">Selecciona un grupo</h4>
                    <p class="text-muted mb-4">Para visualizar el horario, por favor selecciona un grupo de la lista superior.</p>
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