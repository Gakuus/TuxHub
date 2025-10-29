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
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title>Agregar Salón</title>
<style>
body {font-family:"Segoe UI",Arial,sans-serif;background:#f5f7fa;margin:0;padding:20px;}
h1 {text-align:center;margin-bottom:20px;color:#333;}
form {background:#fff;padding:20px;border-radius:10px;max-width:600px;margin:0 auto;
      box-shadow:0 4px 10px rgba(0,0,0,0.08);}
input,textarea,select {width:100%;padding:10px;margin-bottom:10px;border:1px solid #ccc;
      border-radius:6px;}
.checkbox-group {display:flex;flex-wrap:wrap;gap:10px;}
.checkbox-group label {background:#e9ecef;padding:6px 10px;border-radius:6px;cursor:pointer;}
button {background:#007bff;color:#fff;padding:10px 15px;border:none;border-radius:6px;
        cursor:pointer;font-weight:600;}
button:hover {background:#0056b3;}
</style>
</head>
<body>

<h1>Agregar Nuevo Salón</h1>

<form method="POST">
    <input type="hidden" name="accion" value="agregar_salon">

    <label>Nombre del salón:</label>
    <input type="text" name="nombre_salon" required>

    <label>Capacidad:</label>
    <input type="number" name="capacidad" min="1" required>

    <label>Ubicación:</label>
    <input type="text" name="ubicacion" required>

    <label>Observaciones:</label>
    <textarea name="observaciones" rows="3" placeholder="Opcional..."></textarea>

    <label>Recursos disponibles:</label>
    <div class="checkbox-group">
        <?php foreach ($recursos_disponibles as $r): ?>
            <label><input type="checkbox" name="recursos[]" value="<?= $r ?>"> <?= ucfirst($r) ?></label>
        <?php endforeach; ?>
    </div>

    <button type="submit">Agregar salón</button>
</form>

</body>
</html>
