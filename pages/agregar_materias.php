<?php
require_once __DIR__ . "/../backend/db_connection.php";
$conn->set_charset("utf8mb4");
session_start();

$rol = $_SESSION['rol'] ?? "Invitado";

if ($rol !== "admin") {
    echo "<div class='alert alert-danger'>Acceso denegado</div>";
    exit;
}

$mensaje = "";

// === ELIMINAR MATERIA ===
if (isset($_GET['eliminar'])) {
    $id = (int) $_GET['eliminar'];

    $conn->begin_transaction();

    try {
        // Con ON DELETE CASCADE, solo necesitamos eliminar la materia
        // Las dependencias se eliminarán automáticamente
        if ($conn->query("DELETE FROM materias WHERE id = $id")) {
            $conn->commit();
            $mensaje = "<div class='alert alert-success'>✅ Materia y todos sus registros relacionados eliminados correctamente.</div>";
        }
    } catch (mysqli_sql_exception $e) {
        $conn->rollback();
        $mensaje = "<div class='alert alert-danger'>
            ❌ Error al eliminar la materia: " . htmlspecialchars($e->getMessage()) . "
        </div>";
    }
}

// === AGREGAR MATERIA ===
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nombre_materia = trim($conn->real_escape_string($_POST['nombre_materia'] ?? ''));

    if (!empty($nombre_materia)) {
        if (strlen($nombre_materia) > 24) {
            $mensaje = "<div class='alert alert-danger'>⚠️ El nombre no puede superar los 24 caracteres.</div>";
        } else {
            $check = $conn->query("SELECT id FROM materias WHERE nombre_materia = '$nombre_materia'");
            if ($check->num_rows > 0) {
                $mensaje = "<div class='alert alert-warning'>⚠️ La materia ya existe.</div>";
            } else {
                if ($conn->query("INSERT INTO materias (nombre_materia) VALUES ('$nombre_materia')")) {
                    $mensaje = "<div class='alert alert-success'>✅ Materia agregada correctamente.</div>";
                    $_POST['nombre_materia'] = '';
                } else {
                    $mensaje = "<div class='alert alert-danger'>❌ Error al insertar: " . htmlspecialchars($conn->error) . "</div>";
                }
            }
        }
    } else {
        $mensaje = "<div class='alert alert-danger'>⚠️ Debe ingresar el nombre de la materia.</div>";
    }
}

// === LISTAR MATERIAS ===
$materias = $conn->query("SELECT id, nombre_materia FROM materias ORDER BY nombre_materia");
?>

<div class="container-fluid px-0">
    <h1 class="mt-4"><i class="bi bi-book"></i> Agregar Materia</h1>

    <?php if (!empty($mensaje)) echo $mensaje; ?>

    <div class="card mb-4 shadow-sm">
        <div class="card-header"><i class="bi bi-plus-circle"></i> Nueva Materia</div>
        <div class="card-body">
            <form method="post" class="row g-3">
                <div class="col-md-6">
                    <label class="form-label">Nombre de la Materia</label>
                    <input type="text" name="nombre_materia" class="form-control"
                           placeholder="Ingrese el nombre" maxlength="24" 
                           value="<?= htmlspecialchars($_POST['nombre_materia'] ?? '') ?>" required>
                    <small class="text-muted">Máximo 24 caracteres.</small>
                </div>
                <div class="col-12 mt-3">
                    <button class="btn btn-success" type="submit">
                        <i class="bi bi-check-circle"></i> Agregar Materia
                    </button>
                </div>
            </form>
        </div>
    </div>

    <div class="card shadow-sm">
        <div class="card-header"><i class="bi bi-list"></i> Materias Existentes</div>
        <div class="card-body">
            <table class="table table-striped table-hover align-middle">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Nombre de Materia</th>
                        <th class="text-center">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($materias->num_rows > 0): ?>
                        <?php while ($m = $materias->fetch_assoc()): ?>
                            <tr>
                                <td><?= $m['id'] ?></td>
                                <td><?= htmlspecialchars($m['nombre_materia']) ?></td>
                                <td class="text-center">
                                    <a href="?eliminar=<?= $m['id'] ?>"
                                       class="btn btn-sm btn-outline-danger"
                                       onclick="return confirm('¿Seguro que desea eliminar esta materia? Se eliminarán automáticamente todos los horarios y registros relacionados.');">
                                        <i class="bi bi-trash"></i> Eliminar
                                    </a>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="3" class="text-center">No hay materias cargadas</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>