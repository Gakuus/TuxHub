<?php
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/../backend/db_connection.php';

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
?>

<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title>Agregar Salón</title>
<link rel="stylesheet" href="/Agora/Agora/css/agregar_salon.css">
</head>
<body>

<h1>Agregar Nuevo Salón</h1>

<form method="POST" onsubmit="return validarFormulario()">
    <input type="hidden" name="accion" value="agregar_salon">

    <label>Nombre del salón (máx. 24 caracteres):</label>
    <input type="text" name="nombre_salon" maxlength="24" required>

    <label>Capacidad (máx. 50 personas):</label>
    <input type="text" name="capacidad" 
           oninput="validarNumero(this)" 
           onkeypress="return (event.charCode >= 48 && event.charCode <= 57)"
           placeholder="Solo números del 1 al 50"
           required>

    <label>Ubicación (máx. 24 caracteres):</label>
    <input type="text" name="ubicacion" maxlength="24" required>

    <label>Observaciones (máx. 255 caracteres):</label>
    <textarea name="observaciones" rows="3" maxlength="255" placeholder="Opcional..."></textarea>

    <label>Recursos disponibles:</label>
    <div class="checkbox-group">
        <?php foreach ($recursos_disponibles as $r): ?>
            <label><input type="checkbox" name="recursos[]" value="<?= $r ?>"> <?= ucfirst($r) ?></label>
        <?php endforeach; ?>
    </div>

    <button type="submit">Agregar salón</button>
</form>

<script src="/Agora/Agora/assets/agregar_salon.js"></script>
</body>
</html>