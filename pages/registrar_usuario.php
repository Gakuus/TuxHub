<?php
require_once __DIR__ . '/../backend/db_connection.php';
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: ../index.html");
    exit();
}

$conn->set_charset('utf8mb4');
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

$user_id = (int)$_SESSION['user_id'];
$rol_actual = strtolower(trim($_SESSION['rol'] ?? ''));
if ($rol_actual === 'admin') $rol_actual = 'administrador';

// ==============================
// CSRF Token
// ==============================
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
$csrf_token = $_SESSION['csrf_token'];

// ==============================
// Cargar grupos según rol
// ==============================
$grupos_disponibles = [];
$errors = [];

try {
    if ($rol_actual === 'profesor') {
        $stmt = $conn->prepare("
            SELECT g.id, g.nombre
            FROM grupos g
            INNER JOIN grupos_profesores gp ON gp.grupo_id = g.id
            WHERE gp.profesor_id = ?
            ORDER BY g.nombre
        ");
        $stmt->bind_param("i", $user_id);
    } else {
        $stmt = $conn->prepare("SELECT id, nombre FROM grupos ORDER BY nombre");
    }
    $stmt->execute();
    $grupos_disponibles = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
} catch (Exception $e) {
    $errors[] = "Error al cargar los grupos.";
}

// ==============================
// Variables iniciales
// ==============================
$old = ['nombre'=>'','cedula'=>'','email'=>'','rol'=>'','password'=>'','grupos'=>[]];
$success = false;

// ==============================
// Procesar formulario
// ==============================
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $posted_csrf = $_POST['csrf_token'] ?? '';
    if (!hash_equals($csrf_token, $posted_csrf)) {
        die("Token CSRF inválido.");
    }

    $nombre = trim($_POST['nombre'] ?? '');
    $cedula = trim($_POST['cedula'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $rol = strtolower(trim($_POST['rol'] ?? ''));
    $grupos = $_POST['grupos'] ?? [];

    $old = compact('nombre','cedula','email','rol','password','grupos');

    // ==============================
    // Validaciones
    // ==============================
    if ($nombre === '' || $cedula === '' || $password === '') {
        $errors[] = "Complete todos los campos obligatorios.";
    }

    if (!preg_match('/^\d{8}$/', $cedula)) {
        $errors[] = "La cédula debe tener exactamente 8 dígitos.";
    }

    if (strlen($password) < 8 || strlen($password) > 24) {
        $errors[] = "La contraseña debe tener entre 8 y 24 caracteres.";
    }

    if (!preg_match('/[a-z]/', $password) ||
        !preg_match('/[A-Z]/', $password) ||
        !preg_match('/\d/', $password) ||
        !preg_match('/[\W_]/', $password)) {
        $errors[] = "La contraseña debe incluir mayúscula, minúscula, número y símbolo.";
    }

    if ($email !== '') {
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = "El correo electrónico no es válido.";
        } else {
            // Verificar si el dominio del email existe (tiene registros MX)
            $dominio = substr(strrchr($email, "@"), 1);
            if ($dominio === false || !checkdnsrr($dominio, "MX")) {
                $errors[] = "El dominio del correo electrónico no existe o no puede recibir correos.";
            }
        }
    }

    // ==============================
    // Reglas según rol actual
    // ==============================
    if ($rol_actual === 'profesor') {
        // ✅ Solo puede crear alumnos
        $rol = 'alumno';

        // Verifica que los grupos elegidos pertenezcan al profesor
        if (!empty($grupos)) {
            $stmt = $conn->prepare("
                SELECT grupo_id FROM grupos_profesores WHERE profesor_id = ?
            ");
            $stmt->bind_param("i", $user_id);
            $stmt->execute();
            $permitidos = array_column($stmt->get_result()->fetch_all(MYSQLI_ASSOC), 'grupo_id');
            $stmt->close();

            foreach ($grupos as $gid) {
                if (!in_array($gid, $permitidos)) {
                    $errors[] = "No puede asignar alumnos a un grupo que no le pertenece.";
                }
            }
        }
    } elseif ($rol_actual === 'administrador') {
        $roles_validos = ['alumno','profesor','administrador'];
        if (!in_array($rol, $roles_validos, true)) {
            $errors[] = "Rol inválido.";
        }
    } else {
        $errors[] = "No tiene permisos para registrar usuarios.";
    }

    // ==============================
    // Inserción si todo OK
    // ==============================
    if (empty($errors)) {
        try {
            $conn->begin_transaction();

            // Evitar duplicados (cédula y correo)
            $stmt = $conn->prepare("
                SELECT id, cedula, email
                FROM usuarios
                WHERE cedula = ? OR (email <> '' AND email = ?)
                LIMIT 1
            ");
            $stmt->bind_param("ss", $cedula, $email);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($result->num_rows > 0) {
                $row = $result->fetch_assoc();
                if ($row['cedula'] === $cedula) {
                    $errors[] = "Ya existe un usuario con esa cédula.";
                }
                if ($email !== '' && $row['email'] === $email) {
                    $errors[] = "Ya existe un usuario con ese correo electrónico.";
                }
            }
            $stmt->close();

            if (empty($errors)) {
                $password_hash = password_hash($password, PASSWORD_DEFAULT);

                // Insertar usuario base
                $stmt = $conn->prepare("
                    INSERT INTO usuarios (cedula, nombre, email, password, rol)
                    VALUES (?, ?, ?, ?, ?)
                ");
                $stmt->bind_param("sssss", $cedula, $nombre, $email, $password_hash, $rol);
                $stmt->execute();
                $nuevo_id = $stmt->insert_id;
                $stmt->close();

                // Si es alumno -> asignar primer grupo
                if ($rol === 'alumno' && !empty($grupos)) {
                    $grupo_id = (int)$grupos[0];
                    $stmt = $conn->prepare("UPDATE usuarios SET grupo_id = ? WHERE id = ?");
                    $stmt->bind_param("ii", $grupo_id, $nuevo_id);
                    $stmt->execute();
                    $stmt->close();
                }

                // Si es profesor -> vincular grupos (sin borrar existentes)
                if ($rol === 'profesor' && !empty($grupos)) {
                    $stmt = $conn->prepare("
                        INSERT IGNORE INTO grupos_profesores (grupo_id, profesor_id)
                        VALUES (?, ?)
                    ");
                    foreach ($grupos as $gid) {
                        $stmt->bind_param("ii", $gid, $nuevo_id);
                        $stmt->execute();
                    }
                    $stmt->close();
                }

                $conn->commit();
                $success = true;
                $old = ['nombre'=>'','cedula'=>'','email'=>'','rol'=>'','password'=>'','grupos'=>[]];
            } else {
                $conn->rollback();
            }
        } catch (Exception $e) {
            $conn->rollback();
            $errors[] = "Error al registrar usuario: " . $e->getMessage();
        }
    }
}
?>
<!doctype html>
<html lang="es">
<head>
<meta charset="utf-8">
<title>Registrar Usuario</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<div class="container py-5">
<div class="card shadow mx-auto" style="max-width:700px;">
<div class="card-body">
<h4 class="card-title text-center mb-4">Registrar Usuario</h4>

<?php if ($success): ?>
<div class="alert alert-success">✅ Usuario registrado correctamente.</div>
<?php endif; ?>

<?php if (!empty($errors)): ?>
<div class="alert alert-danger">
<ul class="mb-0"><?php foreach ($errors as $e): ?><li><?= htmlspecialchars($e) ?></li><?php endforeach; ?></ul>
</div>
<?php endif; ?>

<form method="POST" novalidate>
<input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">

<div class="mb-3">
<label class="form-label">Nombre completo</label>
<input type="text" name="nombre" class="form-control" maxlength="50" required value="<?= htmlspecialchars($old['nombre']) ?>">
</div>

<div class="mb-3">
<label class="form-label">Cédula</label>
<input type="text" name="cedula" class="form-control" maxlength="8" minlength="8" pattern="\d{8}" required value="<?= htmlspecialchars($old['cedula']) ?>">
</div>

<div class="mb-3">
<label class="form-label">Correo (opcional)</label>
<input type="email" name="email" class="form-control" maxlength="50" value="<?= htmlspecialchars($old['email']) ?>">
<small class="text-muted">Debe ser un correo real (con dominio existente).</small>
</div>

<div class="mb-3">
<label class="form-label">Contraseña</label>
<input type="password" name="password" class="form-control" minlength="8" maxlength="24" required>
</div>

<?php if ($rol_actual === 'administrador'): ?>
<div class="mb-3">
<label class="form-label">Rol</label>
<select name="rol" class="form-select" required>
<option value="">-- Seleccione --</option>
<option value="profesor" <?= $old['rol']==='profesor'?'selected':'' ?>>Profesor</option>
<option value="alumno" <?= $old['rol']==='alumno'?'selected':'' ?>>Alumno</option>
<option value="administrador" <?= $old['rol']==='administrador'?'selected':'' ?>>Administrador</option>
</select>
</div>
<?php else: ?>
<input type="hidden" name="rol" value="alumno">
<?php endif; ?>

<div class="mb-3">
<label class="form-label"><?= $rol_actual === 'profesor' ? 'Asignar al grupo' : 'Grupos disponibles' ?></label>
<select name="grupos[]" class="form-select" <?= $rol_actual === 'profesor' ? '' : 'multiple size="4"' ?>>
<?php foreach ($grupos_disponibles as $g): ?>
<option value="<?= $g['id'] ?>" <?= in_array($g['id'],$old['grupos'])?'selected':'' ?>>
<?= htmlspecialchars($g['nombre']) ?>
</option>
<?php endforeach; ?>
</select>
<?php if ($rol_actual !== 'profesor'): ?>
<small class="text-muted">Ctrl + clic para seleccionar varios.</small>
<?php endif; ?>
</div>

<div class="d-grid">
<button class="btn btn-primary">Registrar</button>
</div>
</form>

<div class="text-center mt-3">
<a href="../dashboard.php" class="text-decoration-none">← Volver al panel</a>
</div>
</div>
</div>
</div>
</body>
</html>
