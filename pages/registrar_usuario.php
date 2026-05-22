<?php
require_once __DIR__ . '/../backend/db_connection.php';
require_once __DIR__ . '/../backend/helpers.php';
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

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
$csrf_token = $_SESSION['csrf_token'];

$grupos_disponibles = [];
$grupos_activos = [];
$grupos_inactivos = [];
$errors = [];

try {
    if ($rol_actual === 'profesor') {
        $stmt = $conn->prepare("
            SELECT g.id, g.nombre, g.activo
            FROM grupos g
            INNER JOIN grupos_profesores gp ON gp.grupo_id = g.id
            WHERE gp.profesor_id = ?
            ORDER BY g.activo DESC, g.nombre
        ");
        $stmt->bind_param("i", $user_id);
    } else {
        $stmt = $conn->prepare("SELECT id, nombre, activo FROM grupos ORDER BY activo DESC, nombre");
    }
    $stmt->execute();
    $result = $stmt->get_result();
    $grupos_disponibles = $result->fetch_all(MYSQLI_ASSOC);
    $stmt->close();

    foreach ($grupos_disponibles as $grupo) {
        if ($grupo['activo'] == 1) {
            $grupos_activos[] = $grupo;
        } else {
            $grupos_inactivos[] = $grupo;
        }
    }
} catch (Exception $e) {
    $errors[] = "Error al cargar los grupos: " . $e->getMessage();
}

$old = ['nombre' => '', 'cedula' => '', 'email' => '', 'rol' => '', 'password' => '', 'password_confirm' => '', 'grupos' => []];
$success = false;

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

    $old = compact('nombre', 'cedula', 'email', 'rol', 'password', 'password_confirm', 'grupos');

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
            $dominio = substr(strrchr($email, "@"), 1);
            if ($dominio === false || !checkdnsrr($dominio, "MX")) {
                $errors[] = "El dominio del correo electrónico no existe o no puede recibir correos.";
            }
        }
    }

    if ($rol_actual === 'profesor') {
        $rol = 'alumno';
        if (!empty($grupos)) {
            $stmt = $conn->prepare("
                SELECT gp.grupo_id, g.activo
                FROM grupos_profesores gp
                INNER JOIN grupos g ON g.id = gp.grupo_id
                WHERE gp.profesor_id = ?
            ");
            $stmt->bind_param("i", $user_id);
            $stmt->execute();
            $result = $stmt->get_result();
            $grupos_permitidos = [];
            while ($row = $result->fetch_assoc()) {
                $grupos_permitidos[$row['grupo_id']] = $row['activo'];
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
        $roles_validos = ['alumno', 'profesor', 'administrador'];
        if (!in_array($rol, $roles_validos, true)) {
            $errors[] = "Rol inválido.";
        }

        if (!empty($grupos)) {
            $stmt = $conn->prepare("SELECT id, activo FROM grupos WHERE id IN (" . implode(',', array_fill(0, count($grupos), '?')) . ")");
            $types = str_repeat('i', count($grupos));
            $stmt->bind_param($types, ...$grupos);
            $stmt->execute();
            $result = $stmt->get_result();
            $estados_grupos = [];
            while ($row = $result->fetch_assoc()) {
                $estados_grupos[$row['id']] = $row['activo'];
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

    if (empty($errors)) {
        try {
            $conn->begin_transaction();

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

                $stmt = $conn->prepare("
                    INSERT INTO usuarios (cedula, nombre, email, password, rol)
                    VALUES (?, ?, ?, ?, ?)
                ");
                $stmt->bind_param("sssss", $cedula, $nombre, $email, $password_hash, $rol);
                $stmt->execute();
                $nuevo_id = $stmt->insert_id;
                $stmt->close();

                if ($rol === 'alumno' && !empty($grupos)) {
                    $grupo_id = (int)$grupos[0];
                    $stmt = $conn->prepare("UPDATE usuarios SET grupo_id = ? WHERE id = ?");
                    $stmt->bind_param("ii", $grupo_id, $nuevo_id);
                    $stmt->execute();
                    $stmt->close();
                }

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
                $old = ['nombre' => '', 'cedula' => '', 'email' => '', 'rol' => '', 'password' => '', 'password_confirm' => '', 'grupos' => []];
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
<div class="register-section">
    <div class="page-header">
        <h2><i class="bi bi-person-plus-fill"></i> Registrar Nuevo Usuario</h2>
    </div>

    <div class="stats-grid mb-4">
        <div class="stat-card">
            <div class="stat-value blue"><?= htmlspecialchars(count($grupos_activos) + count($grupos_inactivos)) ?></div>
            <div class="stat-label"><i class="bi bi-people"></i> Grupos Disponibles</div>
        </div>
        <div class="stat-card">
            <div class="stat-value green"><?= htmlspecialchars(count($grupos_activos)) ?></div>
            <div class="stat-label"><i class="bi bi-check-circle"></i> Grupos Activos</div>
        </div>
        <div class="stat-card">
            <div class="stat-value yellow"><?= htmlspecialchars(count($grupos_inactivos)) ?></div>
            <div class="stat-label"><i class="bi bi-pause-circle"></i> Grupos Inactivos</div>
        </div>
    </div>

    <?php if ($success): ?>
    <div class="alert-glass alert-success">
        <i class="bi bi-check-circle-fill"></i>
        <div>
            <strong>Usuario registrado correctamente.</strong>
        </div>
    </div>
    <?php endif; ?>

    <?php if (!empty($errors)): ?>
    <div class="alert-glass alert-danger">
        <i class="bi bi-exclamation-triangle-fill"></i>
        <div>
            <strong>Errores encontrados:</strong>
            <ul>
                <?php foreach ($errors as $e): ?>
                <li><?= htmlspecialchars($e) ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    </div>
    <?php endif; ?>

    <div class="register-card">
        <!-- Step Indicators -->
        <div class="steps-bar">
            <div class="step-item" data-step="1">
                <div class="step-circle active" id="circle1">
                    <span class="step-num">1</span>
                    <span class="step-check"><i class="bi bi-check-lg"></i></span>
                </div>
                <div class="step-label active" id="label1">Datos Personales</div>
            </div>
            <div class="step-line" id="line1"></div>
            <div class="step-item" data-step="2">
                <div class="step-circle" id="circle2">
                    <span class="step-num">2</span>
                    <span class="step-check"><i class="bi bi-check-lg"></i></span>
                </div>
                <div class="step-label" id="label2">Contraseña</div>
            </div>
            <div class="step-line" id="line2"></div>
            <div class="step-item" data-step="3">
                <div class="step-circle" id="circle3">
                    <span class="step-num">3</span>
                    <span class="step-check"><i class="bi bi-check-lg"></i></span>
                </div>
                <div class="step-label" id="label3">Rol y Confirmación</div>
            </div>
        </div>

        <form method="POST" novalidate id="registerForm">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">

            <!-- ===== STEP 1: Datos Personales ===== -->
            <div class="step-content active" data-step="1">
                <div class="row-custom">
                    <div class="input-group">
                        <div class="input-wrapper">
                            <input type="text" name="cedula" id="cedula"
                                class="form-input" maxlength="8" minlength="8" pattern="\d{8}" required
                                placeholder=" " inputmode="numeric"
                                value="<?= htmlspecialchars($old['cedula']) ?>">
                            <label for="cedula" class="float-label">
                                <i class="bi bi-person-badge"></i> Cédula
                            </label>
                            <span class="input-icon"><i class="bi bi-person-badge"></i></span>
                        </div>
                        <small class="form-hint">8 dígitos, solo números *</small>
                    </div>

                    <div class="input-group">
                        <div class="input-wrapper">
                            <input type="text" name="nombre" id="nombre"
                                class="form-input" maxlength="50" required
                                placeholder=" "
                                value="<?= htmlspecialchars($old['nombre']) ?>">
                            <label for="nombre" class="float-label">
                                <i class="bi bi-person"></i> Nombre completo
                            </label>
                            <span class="input-icon"><i class="bi bi-person"></i></span>
                        </div>
                        <small class="form-hint">Nombre y apellido *</small>
                    </div>

                    <div class="input-group">
                        <div class="input-wrapper">
                            <input type="email" name="email" id="email"
                                class="form-input" maxlength="50"
                                placeholder=" "
                                value="<?= htmlspecialchars($old['email']) ?>">
                            <label for="email" class="float-label">
                                <i class="bi bi-envelope"></i> Correo electrónico
                            </label>
                            <span class="input-icon"><i class="bi bi-envelope"></i></span>
                        </div>
                        <small class="form-hint">Debe ser un correo con dominio existente</small>
                    </div>

                    <div class="input-group">
                        <div class="input-wrapper">
                            <input type="tel" name="telefono" id="telefono"
                                class="form-input" maxlength="15"
                                placeholder=" ">
                            <label for="telefono" class="float-label">
                                <i class="bi bi-telephone"></i> Teléfono
                            </label>
                            <span class="input-icon"><i class="bi bi-telephone"></i></span>
                        </div>
                        <small class="form-hint">Ej: 0412-1234567</small>
                    </div>
                </div>
            </div>

            <!-- ===== STEP 2: Contraseña ===== -->
            <div class="step-content" data-step="2">
                <div class="row-custom">
                    <div class="input-group">
                        <div class="input-wrapper">
                            <input type="password" name="password" id="password"
                                class="form-input" minlength="8" maxlength="24" required
                                placeholder=" ">
                            <label for="password" class="float-label">
                                <i class="bi bi-lock"></i> Contraseña
                            </label>
                            <span class="input-icon"><i class="bi bi-lock"></i></span>
                            <button type="button" class="password-toggle" data-for="password" tabindex="-1" aria-label="Mostrar contraseña">
                                <i class="bi bi-eye"></i>
                            </button>
                        </div>
                        <div class="password-strength-bar">
                            <div class="strength-fill" id="strengthFill"></div>
                        </div>
                        <div class="password-strength-labels">
                            <span>Débil</span>
                            <span>Fuerte</span>
                        </div>
                        <div class="password-requirements" id="requirements">
                            <div class="requirement-item" data-req="length">
                                <i class="bi bi-x-circle"></i> Mínimo 8 caracteres
                            </div>
                            <div class="requirement-item" data-req="lowercase">
                                <i class="bi bi-x-circle"></i> Una minúscula
                            </div>
                            <div class="requirement-item" data-req="uppercase">
                                <i class="bi bi-x-circle"></i> Una mayúscula
                            </div>
                            <div class="requirement-item" data-req="number">
                                <i class="bi bi-x-circle"></i> Un número
                            </div>
                            <div class="requirement-item" data-req="special">
                                <i class="bi bi-x-circle"></i> Un símbolo
                            </div>
                        </div>
                    </div>

                    <div class="input-group">
                        <div class="input-wrapper">
                            <input type="password" name="password_confirm" id="password_confirm"
                                class="form-input" minlength="8" maxlength="24" required
                                placeholder=" ">
                            <label for="password_confirm" class="float-label">
                                <i class="bi bi-lock-fill"></i> Repetir contraseña
                            </label>
                            <span class="input-icon"><i class="bi bi-lock-fill"></i></span>
                            <button type="button" class="password-toggle" data-for="password_confirm" tabindex="-1" aria-label="Mostrar contraseña">
                                <i class="bi bi-eye"></i>
                            </button>
                        </div>
                        <div class="password-match" id="passwordMatch"></div>
                    </div>
                </div>
            </div>

            <!-- ===== STEP 3: Rol y Confirmación ===== -->
            <div class="step-content" data-step="3">
                <div class="row-custom">
                    <?php if ($rol_actual === 'administrador'): ?>
                    <div class="input-group">
                        <div class="input-wrapper">
                            <select name="rol" id="rol" class="form-select" required>
                                <option value="">-- Seleccione un rol --</option>
                                <option value="profesor" <?= $old['rol'] === 'profesor' ? 'selected' : '' ?>>Profesor</option>
                                <option value="alumno" <?= $old['rol'] === 'alumno' ? 'selected' : '' ?>>Alumno</option>
                                <option value="administrador" <?= $old['rol'] === 'administrador' ? 'selected' : '' ?>>Administrador</option>
                            </select>
                            <span class="input-icon"><i class="bi bi-person-badge"></i></span>
                        </div>
                        <small class="form-hint">Rol del usuario *</small>
                    </div>
                    <?php else: ?>
                    <input type="hidden" name="rol" value="alumno">
                    <?php endif; ?>
                </div>

                <div class="input-group mt-3">
                    <label class="form-label fw-semibold small">
                        <?= $rol_actual === 'profesor' ? 'Asignar al grupo' : 'Grupos disponibles' ?>
                    </label>
                    <div class="filter-group mb-2">
                        <button type="button" class="btn-filter active" data-filter="all">
                            <i class="bi bi-collection"></i> Todos
                        </button>
                        <button type="button" class="btn-filter" data-filter="active">
                            <i class="bi bi-check-circle"></i> Activos
                        </button>
                        <button type="button" class="btn-filter" data-filter="inactive">
                            <i class="bi bi-pause-circle"></i> Inactivos
                        </button>
                    </div>

                    <select name="grupos[]" class="form-select" id="gruposSelect" <?= $rol_actual === 'profesor' ? '' : 'multiple size="4"' ?>>
                        <?php if (!empty($grupos_activos)): ?>
                        <optgroup label="Grupos Activos" data-estado="activo">
                            <?php foreach ($grupos_activos as $g): ?>
                            <option value="<?= $g['id'] ?>" data-estado="activo" <?= in_array($g['id'], $old['grupos']) ? 'selected' : '' ?>>
                                <?= htmlspecialchars($g['nombre']) ?>
                            </option>
                            <?php endforeach; ?>
                        </optgroup>
                        <?php endif; ?>
                        <?php if (!empty($grupos_inactivos)): ?>
                        <optgroup label="Grupos Inactivos" data-estado="inactivo">
                            <?php foreach ($grupos_inactivos as $g): ?>
                            <option value="<?= $g['id'] ?>" data-estado="inactivo" <?= in_array($g['id'], $old['grupos']) ? 'selected' : '' ?>>
                                <?= htmlspecialchars($g['nombre']) ?>
                            </option>
                            <?php endforeach; ?>
                        </optgroup>
                        <?php endif; ?>
                    </select>
                    <?php if ($rol_actual !== 'profesor'): ?>
                    <small class="form-hint">Mantén Ctrl para seleccionar múltiples</small>
                    <?php endif; ?>
                </div>

                <div class="input-group mt-3">
                    <div class="checkbox-item">
                        <input type="checkbox" id="terminos" required>
                        <label for="terminos">Acepto los términos y condiciones del registro</label>
                    </div>
                </div>
            </div>

            <!-- Navigation Buttons -->
            <div class="action-row">
                <button type="button" class="btn-nav btn-nav-prev" id="prevBtn" disabled>
                    <i class="bi bi-arrow-left"></i> Anterior
                </button>
                <button type="button" class="btn-nav btn-nav-next" id="nextBtn">
                    Siguiente <i class="bi bi-arrow-right"></i>
                </button>
                <button type="submit" class="btn-nav btn-nav-submit d-none" id="submitBtn">
                    <i class="bi bi-person-plus"></i> Registrar Usuario
                </button>
            </div>
        </form>
    </div>
</div>

<script src="assets/register.js"></script>
