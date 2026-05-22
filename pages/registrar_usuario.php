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
        // Solo puede crear alumnos
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
<div class="register-section">
    <div class="page-header">
        <h2><i class="bi bi-person-plus-fill"></i> Registrar Nuevo Usuario</h2>
    </div>

    <div class="stats-grid">
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

    <div class="card">
        <div class="card-body">
            <!-- Step Indicators -->
            <div class="steps-bar d-flex justify-content-center align-items-center mb-4">
                <div class="step-item text-center active" data-step="1">
                    <div class="step-circle rounded-circle d-inline-flex align-items-center justify-content-center bg-primary text-white" style="width:36px;height:36px;">1</div>
                    <div class="step-label small mt-1 fw-medium">Datos Personales</div>
                </div>
                <div class="step-line flex-grow-1 mx-2" style="height:2px;background:var(--border-color);max-width:80px;"></div>
                <div class="step-item text-center" data-step="2">
                    <div class="step-circle rounded-circle d-inline-flex align-items-center justify-content-center bg-light text-muted" style="width:36px;height:36px;border:2px solid var(--border-color);">2</div>
                    <div class="step-label small mt-1 fw-medium">Contraseña</div>
                </div>
                <div class="step-line flex-grow-1 mx-2" style="height:2px;background:var(--border-color);max-width:80px;"></div>
                <div class="step-item text-center" data-step="3">
                    <div class="step-circle rounded-circle d-inline-flex align-items-center justify-content-center bg-light text-muted" style="width:36px;height:36px;border:2px solid var(--border-color);">3</div>
                    <div class="step-label small mt-1 fw-medium">Rol y Confirmación</div>
                </div>
            </div>

            <form method="POST" novalidate id="registerForm">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">

                <!-- Step 1: Datos Personales -->
                <div class="step-content" data-step="1">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Cédula *</label>
                            <input type="text" name="cedula" class="form-control" maxlength="8" minlength="8" pattern="\d{8}" required value="<?= htmlspecialchars($old['cedula']) ?>" placeholder="12345678">
                            <small class="text-muted">8 dígitos sin puntos ni guiones</small>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Nombre completo *</label>
                            <input type="text" name="nombre" class="form-control" maxlength="50" required value="<?= htmlspecialchars($old['nombre']) ?>" placeholder="Nombre y apellido">
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Correo electrónico</label>
                            <input type="email" name="email" class="form-control" maxlength="50" value="<?= htmlspecialchars($old['email']) ?>" placeholder="correo@ejemplo.com">
                            <small class="text-muted">Debe ser un correo real con dominio existente</small>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Teléfono</label>
                            <input type="tel" name="telefono" class="form-control" maxlength="15" placeholder="0412-1234567">
                        </div>
                    </div>
                </div>

                <!-- Step 2: Contraseña -->
                <div class="step-content d-none" data-step="2">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Contraseña *</label>
                            <div class="password-input-group position-relative">
                                <input type="password" name="password" id="password" class="form-control" minlength="8" maxlength="24" required>
                                <button type="button" class="password-toggle position-absolute top-50 end-0 translate-middle-y btn btn-link text-muted" style="z-index:5;">
                                    <i class="bi bi-eye"></i>
                                </button>
                            </div>
                            <div class="password-strength mt-2" id="passwordStrength" style="height:4px;background:#e9ecef;border-radius:2px;overflow:hidden;">
                                <div class="strength-bar" style="height:100%;width:0%;background:var(--danger);transition:width 0.3s ease;"></div>
                            </div>
                            <div class="d-flex justify-content-between small text-muted mt-1">
                                <span>Débil</span>
                                <span>Fuerte</span>
                            </div>
                            <div class="password-requirements mt-2">
                                <div id="lengthReq" class="requirement-not-met small"><i class="bi bi-x-circle me-1"></i> Mínimo 8 caracteres</div>
                                <div id="lowercaseReq" class="requirement-not-met small"><i class="bi bi-x-circle me-1"></i> Una minúscula</div>
                                <div id="uppercaseReq" class="requirement-not-met small"><i class="bi bi-x-circle me-1"></i> Una mayúscula</div>
                                <div id="numberReq" class="requirement-not-met small"><i class="bi bi-x-circle me-1"></i> Un número</div>
                                <div id="specialReq" class="requirement-not-met small"><i class="bi bi-x-circle me-1"></i> Un símbolo</div>
                            </div>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Repetir Contraseña *</label>
                            <div class="password-input-group position-relative">
                                <input type="password" name="password_confirm" id="password_confirm" class="form-control" minlength="8" maxlength="24" required>
                                <button type="button" class="password-toggle position-absolute top-50 end-0 translate-middle-y btn btn-link text-muted" style="z-index:5;">
                                    <i class="bi bi-eye"></i>
                                </button>
                            </div>
                            <div id="passwordMatch" class="form-text mt-2"></div>
                        </div>
                    </div>
                </div>

                <!-- Step 3: Rol y Confirmación -->
                <div class="step-content d-none" data-step="3">
                    <div class="row">
                        <?php if ($rol_actual === 'administrador'): ?>
                        <div class="col-md-6 mb-3">
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

                        <div class="col-md-6 mb-3">
                            <label class="form-label">Repetir Contraseña *</label>
                            <input type="password" name="password_confirm" class="form-control" minlength="8" maxlength="24" required>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">
                            <?= $rol_actual === 'profesor' ? 'Asignar al grupo' : 'Grupos disponibles' ?>
                        </label>
                        <div class="filter-group mb-2" role="group">
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

                    <div class="mb-3">
                        <div class="checkbox-item">
                            <input type="checkbox" id="terminos" required>
                            <label for="terminos">Acepto los términos y condiciones del registro</label>
                        </div>
                    </div>
                </div>

                <!-- Navigation Buttons -->
                <div class="action-row d-flex justify-content-between">
                    <button type="button" class="btn btn-outline-secondary" id="prevBtn" disabled>
                        <i class="bi bi-arrow-left"></i> Anterior
                    </button>
                    <button type="button" class="btn btn-primary" id="nextBtn">
                        Siguiente <i class="bi bi-arrow-right"></i>
                    </button>
                    <button type="submit" class="btn btn-success d-none" id="submitBtn">
                        <i class="bi bi-person-plus"></i> Registrar Usuario
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="assets/register.js"></script>
