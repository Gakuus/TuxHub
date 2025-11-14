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
$grupos_activos = [];
$grupos_inactivos = [];
$errors = [];

try {
    if ($rol_actual === 'profesor') {
        $stmt = $conn->prepare("
            SELECT g.id, g.nombre, g.activa
            FROM grupos g
            INNER JOIN grupos_profesores gp ON gp.grupo_id = g.id
            WHERE gp.profesor_id = ?
            ORDER BY g.activa DESC, g.nombre
        ");
        $stmt->bind_param("i", $user_id);
    } else {
        $stmt = $conn->prepare("SELECT id, nombre, activa FROM grupos ORDER BY activa DESC, nombre");
    }
    $stmt->execute();
    $result = $stmt->get_result();
    $grupos_disponibles = $result->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
    
    // Separar grupos activos e inactivos
    foreach ($grupos_disponibles as $grupo) {
        // Asumimos que 'activa' es 1 para activo, 0 para inactivo
        if ($grupo['activa'] == 1) {
            $grupos_activos[] = $grupo;
        } else {
            $grupos_inactivos[] = $grupo;
        }
    }
    
} catch (Exception $e) {
    $errors[] = "Error al cargar los grupos: " . $e->getMessage();
}

// ==============================
// Variables iniciales
// ==============================
$old = ['nombre'=>'','cedula'=>'','email'=>'','rol'=>'','password'=>'','password_confirm'=>'','grupos'=>[]];
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
    $password_confirm = $_POST['password_confirm'] ?? '';
    $rol = strtolower(trim($_POST['rol'] ?? ''));
    $grupos = $_POST['grupos'] ?? [];

    $old = compact('nombre','cedula','email','rol','password','password_confirm','grupos');

    // ==============================
    // Validaciones
    // ==============================
    if ($nombre === '' || $cedula === '' || $password === '') {
        $errors[] = "Complete todos los campos obligatorios.";
    }

    if (!preg_match('/^\d{8}$/', $cedula)) {
        $errors[] = "La cédula debe tener exactamente 8 dígitos.";
    }

    if ($password !== $password_confirm) {
        $errors[] = "Las contraseñas no coinciden.";
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

        // Verifica que los grupos elegidos pertenezcan al profesor y estén activos
        if (!empty($grupos)) {
            $stmt = $conn->prepare("
                SELECT gp.grupo_id, g.activa 
                FROM grupos_profesores gp 
                INNER JOIN grupos g ON g.id = gp.grupo_id 
                WHERE gp.profesor_id = ?
            ");
            $stmt->bind_param("i", $user_id);
            $stmt->execute();
            $result = $stmt->get_result();
            $grupos_permitidos = [];
            while ($row = $result->fetch_assoc()) {
                $grupos_permitidos[$row['grupo_id']] = $row['activa'];
            }
            $stmt->close();

            foreach ($grupos as $gid) {
                if (!array_key_exists($gid, $grupos_permitidos)) {
                    $errors[] = "No puede asignar alumnos a un grupo que no le pertenece.";
                } elseif ($grupos_permitidos[$gid] != 1) {
                    $errors[] = "No puede asignar alumnos a un grupo inactivo.";
                }
            }
        }
    } elseif ($rol_actual === 'administrador') {
        $roles_validos = ['alumno','profesor','administrador'];
        if (!in_array($rol, $roles_validos, true)) {
            $errors[] = "Rol inválido.";
        }
        
        // Verificar que los grupos seleccionados estén activos (para admin)
        if (!empty($grupos)) {
            $stmt = $conn->prepare("SELECT id, activa FROM grupos WHERE id IN (" . implode(',', array_fill(0, count($grupos), '?')) . ")");
            $types = str_repeat('i', count($grupos));
            $stmt->bind_param($types, ...$grupos);
            $stmt->execute();
            $result = $stmt->get_result();
            $estados_grupos = [];
            while ($row = $result->fetch_assoc()) {
                $estados_grupos[$row['id']] = $row['activa'];
            }
            $stmt->close();

            foreach ($grupos as $gid) {
                if (isset($estados_grupos[$gid]) && $estados_grupos[$gid] != 1) {
                    $errors[] = "No puede asignar usuarios a grupos inactivos.";
                    break;
                }
            }
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
                $old = ['nombre'=>'','cedula'=>'','email'=>'','rol'=>'','password'=>'','password_confirm'=>'','grupos'=>[]];
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
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Registrar Usuario - Sistema Institucional</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
<link rel="stylesheet" href="/Agora/Agora/css/register.css">
</head>
<body>
<div class="container-card">
    <div class="card">
        <div class="institutional-header">
            <h4><i class="bi bi-person-plus-fill"></i>Registrar Nuevo Usuario</h4>
        </div>
        
        <div class="card-body">
            <?php if ($success): ?>
            <div class="alert alert-success">
                <i class="bi bi-check-circle-fill"></i> Usuario registrado correctamente.
            </div>
            <?php endif; ?>

            <?php if (!empty($errors)): ?>
            <div class="alert alert-danger">
                <h6><i class="bi bi-exclamation-triangle-fill"></i> Errores encontrados:</h6>
                <ul class="mb-0 mt-2"><?php foreach ($errors as $e): ?><li><?= htmlspecialchars($e) ?></li><?php endforeach; ?></ul>
            </div>
            <?php endif; ?>

            <form method="POST" novalidate id="registerForm">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">

                <div class="form-section">
                    <h6 class="form-section-title"><i class="bi bi-person-badge"></i> Información Personal</h6>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Nombre completo *</label>
                            <input type="text" name="nombre" class="form-control" maxlength="50" required value="<?= htmlspecialchars($old['nombre']) ?>">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Cédula *</label>
                            <input type="text" name="cedula" class="form-control" maxlength="8" minlength="8" pattern="\d{8}" required value="<?= htmlspecialchars($old['cedula']) ?>">
                            <small class="text-muted">8 dígitos sin puntos ni guiones</small>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Correo electrónico</label>
                        <input type="email" name="email" class="form-control" maxlength="50" value="<?= htmlspecialchars($old['email']) ?>">
                        <small class="text-muted">Debe ser un correo real con dominio existente</small>
                    </div>
                </div>

                <div class="form-section">
                    <h6 class="form-section-title"><i class="bi bi-shield-lock"></i> Seguridad</h6>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Contraseña *</label>
                            <div class="password-input-group">
                                <input type="password" name="password" id="password" class="form-control" minlength="8" maxlength="24" required>
                                <button type="button" class="password-toggle">
                                    <i class="bi bi-eye"></i>
                                </button>
                            </div>
                            <div class="password-strength" id="passwordStrength"></div>
                            <div class="strength-labels">
                                <span>Débil</span>
                                <span>Fuerte</span>
                            </div>
                            <div class="password-requirements mt-2">
                                <div id="lengthReq" class="requirement-not-met">Mínimo 8 caracteres</div>
                                <div id="lowercaseReq" class="requirement-not-met">Una minúscula</div>
                                <div id="uppercaseReq" class="requirement-not-met">Una mayúscula</div>
                                <div id="numberReq" class="requirement-not-met">Un número</div>
                                <div id="specialReq" class="requirement-not-met">Un símbolo</div>
                            </div>
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Repetir Contraseña *</label>
                            <div class="password-input-group">
                                <input type="password" name="password_confirm" id="password_confirm" class="form-control" minlength="8" maxlength="24" required>
                                <button type="button" class="password-toggle">
                                    <i class="bi bi-eye"></i>
                                </button>
                            </div>
                            <div id="passwordMatch" class="form-text mt-2"></div>
                        </div>
                    </div>
                </div>

                <div class="form-section">
                    <h6 class="form-section-title"><i class="bi bi-people"></i> Roles y Grupos</h6>
                    
                    <?php if ($rol_actual === 'administrador'): ?>
                    <div class="mb-3">
                        <label class="form-label">Rol del usuario *</label>
                        <select name="rol" class="form-select" required>
                            <option value="">-- Seleccione un rol --</option>
                            <option value="profesor" <?= $old['rol']==='profesor'?'selected':'' ?>>Profesor</option>
                            <option value="alumno" <?= $old['rol']==='alumno'?'selected':'' ?>>Alumno</option>
                            <option value="administrador" <?= $old['rol']==='administrador'?'selected':'' ?>>Administrador</option>
                        </select>
                    </div>
                    <?php else: ?>
                    <input type="hidden" name="rol" value="alumno">
                    <?php endif; ?>

                    <div class="mb-3">
                        <label class="form-label">
                            <?= $rol_actual === 'profesor' ? 'Asignar al grupo' : 'Grupos disponibles' ?>
                        </label>
                        
                        <!-- Filtros para grupos -->
                        <div class="filter-buttons">
                            <div class="btn-group" role="group">
                                <button type="button" class="btn btn-filter active" data-filter="all">
                                    <i class="bi bi-collection"></i> Todos
                                </button>
                                <button type="button" class="btn btn-filter" data-filter="active">
                                    <i class="bi bi-check-circle"></i> Activos
                                </button>
                                <button type="button" class="btn btn-filter" data-filter="inactive">
                                    <i class="bi bi-pause-circle"></i> Inactivos
                                </button>
                            </div>
                        </div>

                        <select name="grupos[]" class="form-select" id="gruposSelect" <?= $rol_actual === 'profesor' ? '' : 'multiple size="4"' ?>>
                            <?php if (!empty($grupos_activos)): ?>
                                <optgroup label="Grupos Activos" data-estado="activo">
                                <?php foreach ($grupos_activos as $g): ?>
                                <option value="<?= $g['id'] ?>" class="grupo-activo" <?= in_array($g['id'],$old['grupos'])?'selected':'' ?>>
                                    <?= htmlspecialchars($g['nombre']) ?>
                                </option>
                                <?php endforeach; ?>
                                </optgroup>
                            <?php endif; ?>
                            
                            <?php if (!empty($grupos_inactivos)): ?>
                                <optgroup label="Grupos Inactivos" data-estado="inactivo">
                                <?php foreach ($grupos_inactivos as $g): ?>
                                <option value="<?= $g['id'] ?>" class="grupo-inactivo" <?= in_array($g['id'],$old['grupos'])?'selected':'' ?>>
                                    <?= htmlspecialchars($g['nombre']) ?>
                                </option>
                                <?php endforeach; ?>
                                </optgroup>
                            <?php endif; ?>
                        </select>
                        <?php if ($rol_actual !== 'profesor'): ?>
                        <small class="text-muted">Mantén presionada la tecla Ctrl para seleccionar múltiples grupos</small>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="d-grid gap-2">
                    <button class="btn btn-primary btn-lg" id="submitBtn">
                        <i class="bi bi-person-plus"></i> Registrar Usuario
                    </button>
                </div>
            </form>

        </div>
    </div>
</div>

<script src="/Agora/Agora/assets/register.js"></script>
</body>
</html>