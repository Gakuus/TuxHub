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

<div class="container mt-4">
    <h2 class="mb-4"><i class="bi bi-calendar-week"></i> Horarios</h2>

    <!-- Select grupos -->
    <?php if($rol === "admin" || $rol === "profesor"): ?>
        <form method="get" action="">
            <input type="hidden" name="page" value="horarios">
            <div class="row g-2">
                <!-- Selector de turno -->
                <div class="col-md-3">
                    <select name="turno" class="form-select" onchange="this.form.submit()">
                        <option value="">-- Todos los turnos --</option>
                        <option value="mañana" <?= ($turno_seleccionado == 'mañana') ? "selected" : "" ?>>Mañana</option>
                        <option value="tarde" <?= ($turno_seleccionado == 'tarde') ? "selected" : "" ?>>Tarde</option>
                        <option value="noche" <?= ($turno_seleccionado == 'noche') ? "selected" : "" ?>>Noche</option>
                    </select>
                </div>
                <!-- Selector de grupo -->
                <div class="col-md-4">
                    <select name="grupo_id" class="form-select" required onchange="this.form.submit()">
                        <option value="">-- Seleccione un grupo --</option>
                        <?php foreach($grupos as $g): ?>
                            <option value="<?= $g['id'] ?>" <?= ($grupo_id == $g['id']) ? "selected" : "" ?>>
                                <?= htmlspecialchars($g['nombre']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
        </form>
    <?php endif; ?>

    <!-- Tabla de horarios -->
    <?php if($grupo_id): ?>
        <div class="table-responsive mt-3">
            <table class="table table-bordered text-center align-middle shadow-sm">
                <thead class="table-dark">
                    <tr>
                        <th>Hora</th>
                        <?php foreach($dias as $d_id => $d_nombre): ?>
                            <th><?= $d_nombre ?></th>
                        <?php endforeach; ?>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach($bloques as $b): ?>
                        <tr>
                            <td><strong><?= $b['hora_inicio'] ?> - <?= $b['hora_fin'] ?></strong></td>
                            <?php foreach($dias as $d_id => $d_nombre): ?>
                                <?php if(isset($horarios[$d_id][$b['id']])): 
                                    $h = $horarios[$d_id][$b['id']];
                                    $contenido = "<b>".htmlspecialchars($h['nombre_materia'])."</b><br>"
                                               . "<small>".htmlspecialchars($h['profesor'])."</small><br>"
                                               . "<span class='text-muted'>".htmlspecialchars($h['nombre_salon'])."</span>";
                                ?>
                                    <td><?= $contenido ?></td>
                                <?php else: ?>
                                    <td></td>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>