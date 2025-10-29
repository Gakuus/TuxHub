<?php
session_start();
require_once __DIR__ . '/../backend/db_connection.php';

// Verificación de sesión
if (!isset($_SESSION['user_id'])) {
    header("Location: ../index.php?error=requerido");
    exit();
}

$rol = $_SESSION['rol'] ?? "Invitado";
$rol_lower = strtolower($rol);
$nombre = $_SESSION['user_name'] ?? "Usuario";

// Obtener lista de grupos con turno
$result = $conn->query("SELECT * FROM grupos ORDER BY id DESC");

// Obtener turnos únicos desde bloques_horarios
$turnos = [];
$turnos_res = $conn->query("SELECT DISTINCT turno FROM bloques_horarios ORDER BY turno");
while ($t = $turnos_res->fetch_assoc()) $turnos[] = $t['turno'];

// Mensaje (desde la redirección del backend)
$mensaje = $_GET['msg'] ?? '';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Gestión de Grupos</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        .container { max-width: 950px; margin: 40px auto; background:#fff; padding:20px; border-radius:8px; box-shadow:0 6px 18px rgba(0,0,0,.06); }
        .top { display:flex; justify-content:space-between; align-items:center; margin-bottom:15px; }
        .btn { padding:6px 10px; border-radius:6px; text-decoration:none; color:#fff; display:inline-block; }
        .btn-back { background:#0d6efd; }
        .btn-delete { background:#d9534f; border:none; padding:6px 10px; border-radius:6px; color:#fff; cursor:pointer; text-decoration:none; }
        .btn-create { background:#28a745; color:#fff; padding:6px 10px; border-radius:6px; }
        table { width:100%; border-collapse:collapse; margin-top:12px; }
        th, td { border:1px solid #ececec; padding:8px; text-align:center; }
        th { background:#f3f4f6; }
        .mensaje { padding:10px; border-radius:6px; margin-bottom:12px; }
        .success { background:#e6ffed; border:1px solid #c6f0d0; color:#135c2b; }
        .error { background:#fff1f0; border:1px solid #f5c2c7; color:#842029; }
        select, input[type=text] { padding:8px; border-radius:6px; border:1px solid #ccc; }
    </style>
</head>
<body>
<div class="container">
    <div class="top">
        <h2>Gestión de Grupos</h2>
    </div>

    <?php if (!empty($mensaje)): ?>
        <?php
            $tipo = 'success';
            if (strpos($mensaje, 'ERR:') === 0) { $tipo = 'error'; $mensaje = substr($mensaje,4); }
        ?>
        <div class="mensaje <?= $tipo === 'success' ? 'success' : 'error' ?>">
            <?= htmlspecialchars($mensaje) ?>
        </div>
    <?php endif; ?>

    <!-- Form para añadir grupo -->
    <form method="post" action="../Agora/backend/grupos_create.php" style="display:flex; gap:8px; margin-bottom:16px;">
        <input type="text" name="nombre_grupo" placeholder="Nombre del grupo" required style="flex:1;">
        <select name="turno" required>
            <option value="">-- Seleccione turno --</option>
            <?php foreach($turnos as $t): ?>
                <option value="<?= htmlspecialchars($t) ?>"><?= htmlspecialchars(ucfirst($t)) ?></option>
            <?php endforeach; ?>
        </select>
        <button type="submit" class="btn-create">Añadir</button>
    </form>

    <!-- Tabla de grupos -->
    <table>
        <thead>
            <tr><th>ID</th><th>Nombre</th><th>Turno</th><th>Acciones</th></tr>
        </thead>
        <tbody>
            <?php while ($row = $result->fetch_assoc()): ?>
                <tr>
                    <td><?= htmlspecialchars($row['id']) ?></td>
                    <td><?= htmlspecialchars($row['nombre']) ?></td>
                    <td><?= htmlspecialchars($row['turno']) ?></td>
                    <td>
                        <?php if ($rol_lower === 'admin'): ?>
                            <a href="../Agora/backend/eliminar_grupo.php?id=<?= (int)$row['id'] ?>"
                               class="btn-delete"
                               onclick="return confirm('¿Eliminar el grupo <?= addslashes(htmlspecialchars($row['nombre'])) ?>?');"
                            >Eliminar</a>
                        <?php else: ?>
                            —
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endwhile; ?>
        </tbody>
    </table>
</div>
</body>
</html>
