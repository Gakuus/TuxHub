<?php
require_once __DIR__ . "/../backend/db_connection.php";
$conn->set_charset("utf8mb4");
session_start();

error_reporting(E_ALL);
ini_set('display_errors', 1);
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

// ==========================
// Permisos y variables
// ==========================
$rol = $_SESSION['rol'] ?? "Invitado";
$usuario_id = $_SESSION['user_id'] ?? null;

// Solo admin puede cargar horarios
if($rol !== "admin"){
    echo "<div class='alert alert-danger'>Acceso denegado. Solo administradores pueden cargar horarios.</div>";
    exit;
}

$mensajes = [];
$grupo_id = $_POST['grupo_id'] ?? $_GET['grupo_id'] ?? 0;
$profesor_id = $_POST['profesor_id'] ?? 0;

// ==========================
// Función para cargar datos
// ==========================
function fetchAllAssoc($conn, $sql) {
    $res = $conn->query($sql);
    if (!$res) return ['error'=>$conn->error];
    return $res->fetch_all(MYSQLI_ASSOC);
}

// ==========================
// Cargar profesores y otras tablas
// ==========================
$profesores = fetchAllAssoc($conn,"SELECT id,nombre FROM usuarios WHERE rol='Profesor' ORDER BY nombre");
$materias   = fetchAllAssoc($conn,"SELECT id,nombre_materia FROM materias ORDER BY nombre_materia");
$salones    = fetchAllAssoc($conn,"SELECT id,nombre_salon FROM salones ORDER BY nombre_salon");
$dias       = fetchAllAssoc($conn,"SELECT id, nombre_dia FROM dias ORDER BY id");

if(isset($dias['error']) || empty($dias)) {
    $dias = [
        ['id'=>1,'nombre_dia'=>'Lunes'],
        ['id'=>2,'nombre_dia'=>'Martes'],
        ['id'=>3,'nombre_dia'=>'Miércoles'],
        ['id'=>4,'nombre_dia'=>'Jueves'],
        ['id'=>5,'nombre_dia'=>'Viernes']
    ];
}

// ==========================
// Cargar grupos según profesor seleccionado
// ==========================
$grupos = [];
if($profesor_id){
    $stmt = $conn->prepare("
        SELECT g.id, g.nombre, g.turno
        FROM grupos g
        JOIN grupos_profesores gp ON gp.grupo_id = g.id
        WHERE gp.profesor_id = ?
        ORDER BY g.nombre
    ");
    $stmt->bind_param("i",$profesor_id);
    $stmt->execute();
    $res = $stmt->get_result();
    while($g = $res->fetch_assoc()) $grupos[] = $g;
    $stmt->close();
}

// ==========================
// Turno y bloques del grupo
// ==========================
$turno_grupo = null;
$bloques = [];
if($grupo_id){
    $stmt = $conn->prepare("SELECT turno FROM grupos WHERE id=?");
    $stmt->bind_param("i",$grupo_id);
    $stmt->execute();
    $res = $stmt->get_result();
    if($res && $res->num_rows) $turno_grupo = $res->fetch_assoc()['turno'];
    $stmt->close();

    if($turno_grupo){
        $stmt = $conn->prepare("SELECT id,hora_inicio,hora_fin FROM bloques_horarios WHERE turno=? ORDER BY hora_inicio");
        $stmt->bind_param("s",$turno_grupo);
        $stmt->execute();
        $res = $stmt->get_result();
        while($r=$res->fetch_assoc()) $bloques[]=$r;
        $stmt->close();
    }
}

// ==========================
// PROCESAR GUARDADO
// ==========================
if($_SERVER['REQUEST_METHOD']==='POST' && isset($_POST['guardar'])){
    $profesor_id = intval($_POST['profesor_id']);
    $materia_id  = intval($_POST['materia_id']);
    $dia_id      = intval($_POST['dia_id'] ?? 0);
    $bloque_inicio_id = intval($_POST['bloque_id']);
    $duracion = intval($_POST['duracion'] ?? 1);
    $duracion = max(1,min(4,$duracion));

    if($dia_id <= 0){
        $mensajes[] = ['tipo'=>'danger','texto'=>"Debe seleccionar un día válido."];
        goto FIN_PROCESO;
    }

    // Manejo de salón
    if(!empty($_POST['nuevo_salon'])){
        $nuevo_salon = trim($_POST['nuevo_salon']);
        if($nuevo_salon===''){
            $mensajes[] = ['tipo'=>'danger','texto'=>"Nombre de salón vacío."];
            goto FIN_PROCESO;
        }
        $stmt = $conn->prepare("INSERT INTO salones (nombre_salon) VALUES (?)");
        $stmt->bind_param("s",$nuevo_salon);
        if($stmt->execute()){
            $salon_id = $stmt->insert_id;
        } else {
            $mensajes[] = ['tipo'=>'danger','texto'=>"Error creando salón: ".$stmt->error];
            $stmt->close();
            goto FIN_PROCESO;
        }
        $stmt->close();
    } else {
        $salon_id = intval($_POST['salon_id']);
        if(!$salon_id){
            $mensajes[] = ['tipo'=>'danger','texto'=>"Debe seleccionar o crear un salón."];
            goto FIN_PROCESO;
        }
    }

    // Buscar índice de bloque de inicio
    $indice_inicio = null;
    foreach($bloques as $i=>$b){
        if($b['id']==$bloque_inicio_id){
            $indice_inicio=$i;
            break;
        }
    }
    if($indice_inicio===null){
        $mensajes[] = ['tipo'=>'danger','texto'=>"Bloque de inicio inválido."];
        goto FIN_PROCESO;
    }

    // Bloques a insertar
    $bloques_a_insertar=[];
    for($k=0;$k<$duracion;$k++){
        $idx = $indice_inicio+$k;
        if(!isset($bloques[$idx])){
            $mensajes[] = ['tipo'=>'danger','texto'=>"No hay suficientes bloques consecutivos para la duración solicitada."];
            goto FIN_PROCESO;
        }
        $bloques_a_insertar[]=$bloques[$idx]['id'];
    }

    // Validaciones de conflicto
    foreach ($bloques_a_insertar as $bid) {
        $conflict_checks = [
            [
                "SELECT COUNT(*) AS cnt FROM horarios WHERE grupo_id=? AND dia_id=? AND bloque_id=?",
                [$grupo_id, $dia_id, $bid],
                "El grupo ya tiene clase en ese día y bloque."
            ],
            [
                "SELECT COUNT(*) AS cnt FROM horarios WHERE profesor_id=? AND dia_id=? AND bloque_id=?",
                [$profesor_id, $dia_id, $bid],
                "El profesor ya está ocupado en ese día y bloque."
            ],
            [
                "SELECT COUNT(*) AS cnt FROM horarios WHERE salon_id=? AND dia_id=? AND bloque_id=?",
                [$salon_id, $dia_id, $bid],
                "El salón ya está ocupado en ese día y bloque."
            ],
        ];

        foreach ($conflict_checks as [$query, $params, $msg]) {
            [$id, $d, $b] = $params;
            $stmt = $conn->prepare($query);
            $stmt->bind_param("iii", $id, $d, $b);
            $stmt->execute();
            $r = $stmt->get_result()->fetch_assoc();
            $stmt->close();
            if ($r['cnt'] > 0) {
                $mensajes[] = ['tipo'=>'danger','texto'=>$msg];
                goto FIN_PROCESO;
            }
        }
    }

    // Insertar horarios
    $ins = $conn->prepare("INSERT INTO horarios (grupo_id,profesor_id,materia_id,salon_id,dia_id,bloque_id) VALUES (?,?,?,?,?,?)");
    foreach($bloques_a_insertar as $bid){
        $ins->bind_param("iiiiii",$grupo_id,$profesor_id,$materia_id,$salon_id,$dia_id,$bid);
        if(!$ins->execute()){
            $mensajes[] = ['tipo'=>'danger','texto'=>"Error guardando horario en bloque $bid: ".$ins->error];
            $ins->close();
            goto FIN_PROCESO;
        }
    }
    $ins->close();
    $mensajes[]=['tipo'=>'success','texto'=>"✅ Horario(s) guardado(s) correctamente."];
}

FIN_PROCESO:
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cargar Horario</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <link rel="stylesheet" href="/Agora/Agora/css/cargar_horario.css">
</head>
<body>
    <div class="container mt-4">
        <h2 class="mb-4"><i class="bi bi-plus-circle"></i> Cargar Horario</h2>

        <?php foreach($mensajes as $m): ?>
            <div class="alert alert-<?= $m['tipo'] ?>"><?= htmlspecialchars($m['texto']) ?></div>
        <?php endforeach; ?>

        <form method="post" class="row g-3">
            <!-- Seleccionar Profesor -->
            <div class="col-md-3">
                <label class="form-label">Profesor</label>
                <select name="profesor_id" class="form-select" onchange="this.form.submit()" required>
                    <option value="">-- Seleccione --</option>
                    <?php foreach($profesores as $p): ?>
                        <option value="<?= $p['id'] ?>" <?= ($profesor_id==$p['id'])?'selected':'' ?>>
                            <?= htmlspecialchars($p['nombre']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <!-- Seleccionar Grupo -->
            <div class="col-md-3">
                <label class="form-label">Grupo</label>
                <select name="grupo_id" class="form-select" onchange="this.form.submit()" required>
                    <option value="">-- Seleccione --</option>
                    <?php foreach($grupos as $g): ?>
                        <option value="<?= $g['id'] ?>" <?= ($grupo_id==$g['id'])?'selected':'' ?>>
                            <?= htmlspecialchars($g['nombre']) ?> (<?= htmlspecialchars($g['turno']) ?>)
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <!-- Materia -->
            <div class="col-md-3">
                <label class="form-label">Materia</label>
                <select name="materia_id" class="form-select" required>
                    <option value="">-- Seleccione --</option>
                    <?php foreach($materias as $m): ?>
                        <option value="<?= $m['id'] ?>"><?= htmlspecialchars($m['nombre_materia']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <!-- Salón -->
            <div class="col-md-3">
                <label class="form-label">Salón</label>
                <select name="salon_id" id="salon_id" class="form-select" onchange="mostrarCampoSalon(this)">
                    <option value="">-- Seleccione --</option>
                    <?php foreach($salones as $s): ?>
                        <option value="<?= $s['id'] ?>"><?= htmlspecialchars($s['nombre_salon']) ?></option>
                    <?php endforeach; ?>
                    <option value="nuevo">➕ Agregar nuevo salón</option>
                </select>
            </div>

            <div class="col-md-3" id="nuevoSalonDiv" style="display:none;">
                <label class="form-label">Nuevo Salón</label>
                <input type="text" name="nuevo_salon" class="form-control" placeholder="Ej: Laboratorio 2">
            </div>

            <!-- Día y Bloque -->
            <?php if($turno_grupo): ?>
            <div class="col-md-3">
                <label class="form-label">Día</label>
                <select name="dia_id" class="form-select" required>
                    <option value="">-- Seleccione día --</option>
                    <?php foreach($dias as $d): ?>
                        <option value="<?= $d['id'] ?>"><?= htmlspecialchars($d['nombre_dia']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="col-md-6">
                <label class="form-label">Bloque inicio (Turno: <?= htmlspecialchars($turno_grupo) ?>)</label>
                <select name="bloque_id" class="form-select" required>
                    <option value="">-- Seleccione bloque --</option>
                    <?php foreach($bloques as $b): ?>
                        <option value="<?= $b['id'] ?>"><?= $b['hora_inicio'] ?> - <?= $b['hora_fin'] ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="col-md-3">
                <label class="form-label">Duración (bloques consecutivos)</label>
                <input type="number" name="duracion" class="form-control" min="1" max="<?= count($bloques) ?>" value="1" required>
                <small class="text-muted">Máx <?= count($bloques) ?> bloques consecutivos</small>
            </div>

            <div class="col-12">
                <button class="btn btn-success" type="submit" name="guardar">Guardar</button>
            </div>
            <?php endif; ?>
        </form>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="/Agora/Agora/assets/cargar_horario.js"></script>
</body>
</html>