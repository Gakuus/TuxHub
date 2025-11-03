<?php
// TEMPORAL: Para debug - ELIMINAR DESPUÉS DE FUNCIONAR
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);

session_start();
require_once 'db_connection.php';

// ========= CONFIGURACIÓN DE SEGURIDAD =========
ini_set('session.cookie_httponly', 1);
ini_set('session.cookie_secure', 1); // Para HTTPS
ini_set('session.use_strict_mode', 1);

// Headers de seguridad
header("X-Frame-Options: DENY");
header("X-Content-Type-Options: nosniff");

// ========= FUNCIONES AUXILIARES =========
function redirect_error($code) {
    header("Location: ../index.php?error=$code");
    exit();
}

function limpiar_input($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
    return $data;
}

function registrar_intento_fallido($conn, $ip, $cedula = null) {
    try {
        $cedula_limpia = $cedula ? limpiar_input($cedula) : null;
        
        // Registrar en tabla de intentos
        $stmt = $conn->prepare("INSERT INTO login_intentos (ip, cedula_intento, intentos, ultimo_intento) 
                               VALUES (?, ?, 1, NOW())
                               ON DUPLICATE KEY UPDATE 
                               intentos = intentos + 1, 
                               ultimo_intento = NOW()");
        $stmt->bind_param("ss", $ip, $cedula_limpia);
        $stmt->execute();
    } catch (Exception $e) {
        error_log("Error en registrar_intento_fallido: " . $e->getMessage());
    }
}

function verificar_bloqueo_ip($conn, $ip) {
    $max_intentos = 5;
    $tiempo_bloqueo = 300; // 5 minutos
    
    try {
        $stmt = $conn->prepare("SELECT intentos, UNIX_TIMESTAMP(ultimo_intento) AS t 
                               FROM login_intentos 
                               WHERE ip = ?");
        $stmt->bind_param("s", $ip);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($row = $result->fetch_assoc()) {
            if ($row['intentos'] >= $max_intentos && (time() - $row['t'] < $tiempo_bloqueo)) {
                return true;
            }
        }
    } catch (Exception $e) {
        error_log("Error en verificar_bloqueo_ip: " . $e->getMessage());
    }
    return false;
}

// ========= CREAR TABLAS SI NO EXISTEN =========
try {
    $conn->query("CREATE TABLE IF NOT EXISTS login_intentos (
        ip VARCHAR(45) PRIMARY KEY,
        cedula_intento VARCHAR(8),
        intentos INT DEFAULT 0,
        ultimo_intento TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_cedula (cedula_intento),
        INDEX idx_tiempo (ultimo_intento)
    )");

    $conn->query("CREATE TABLE IF NOT EXISTS login_logs (
        id INT AUTO_INCREMENT PRIMARY KEY,
        usuario_id INT,
        ip VARCHAR(45),
        user_agent TEXT,
        exito BOOLEAN,
        fecha TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_usuario (usuario_id),
        INDEX idx_fecha (fecha)
    )");
} catch (Exception $e) {
    error_log("Error creando tablas: " . $e->getMessage());
    // Continuar sin las tablas de logs si hay error
}

// ========= VALIDACIÓN INICIAL =========
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    error_log("Intento de acceso directo a login_handler desde IP: " . ($_SERVER['REMOTE_ADDR'] ?? 'DESCONOCIDA'));
    redirect_error('metodo_invalido');
}

// ========= VALIDACIÓN CSRF =========
if (empty($_POST['csrf_token']) || empty($_SESSION['csrf_token']) || 
    !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
    error_log("CSRF fail desde IP: " . ($_SERVER['REMOTE_ADDR'] ?? 'DESCONOCIDA'));
    redirect_error('csrf');
}

// ========= CAPTCHA =========
$recaptcha_secret = "6LeskeMrAAAAAEwZ1CwNR2BhiQrDiM47qQPdJ4mr";
$response = $_POST['g-recaptcha-response'] ?? '';
$remoteip = $_SERVER['REMOTE_ADDR'] ?? '';

if (empty($response)) {
    redirect_error('captcha');
}

try {
    $verify = file_get_contents(
        "https://www.google.com/recaptcha/api/siteverify?secret=$recaptcha_secret&response=$response&remoteip=$remoteip"
    );

    if ($verify === false) {
        error_log("Error de conexión con reCAPTCHA");
        redirect_error('captcha');
    }

    $captcha_data = json_decode($verify, true);
    if (!$captcha_data['success']) {
        error_log("CAPTCHA fail: " . implode(',', $captcha_data['error-codes'] ?? []));
        redirect_error('captcha');
    }
} catch (Exception $e) {
    error_log("Error en CAPTCHA: " . $e->getMessage());
    redirect_error('captcha');
}

// ========= VALIDACIÓN DE INPUTS =========
$cedula = limpiar_input($_POST['cedula'] ?? '');
$password = $_POST['password'] ?? '';

// Validaciones básicas
if (empty($cedula) || empty($password)) {
    redirect_error('campos');
}

if (!ctype_digit($cedula)) {
    redirect_error('cedula_formato');
}

if (strlen($cedula) > 8) {
    redirect_error('cedula_larga');
}

// ========= CONTROL DE INTENTOS =========
if (verificar_bloqueo_ip($conn, $remoteip)) {
    redirect_error('bloqueado');
}

// ========= VERIFICACIÓN DE USUARIO =========
try {
    // Consulta SIMPLIFICADA - solo las columnas que definitivamente existen
    $stmt = $conn->prepare("
        SELECT u.id, u.nombre, u.password, u.rol, u.grupo_id, g.nombre AS grupo
        FROM usuarios u
        LEFT JOIN grupos g ON u.grupo_id = g.id
        WHERE u.cedula = ?
    ");
    
    if (!$stmt) {
        throw new Exception("Error en prepare: " . $conn->error);
    }
    
    $stmt->bind_param("s", $cedula);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows !== 1) {
        registrar_intento_fallido($conn, $remoteip, $cedula);
        redirect_error('credenciales');
    }

    $user = $result->fetch_assoc();
    
    // ========= VERIFICAR CONTRASEÑA =========
    $is_admin = strtolower($user['rol']) === 'admin';
    $pass_ok = false;

    // Intentar primero con password_hash
    if (password_verify($password, $user['password'])) {
        $pass_ok = true;
    } 
    // Fallback para contraseñas legacy (md5)
    elseif (md5($password) === $user['password']) {
        $pass_ok = true;
        // Actualizar a password_hash si es necesario
        $new_hash = password_hash($password, PASSWORD_DEFAULT);
        $update_stmt = $conn->prepare("UPDATE usuarios SET password = ? WHERE id = ?");
        $update_stmt->bind_param("si", $new_hash, $user['id']);
        $update_stmt->execute();
    }

    if (!$pass_ok) {
        registrar_intento_fallido($conn, $remoteip, $cedula);
        redirect_error('credenciales');
    }

    // ========= VALIDAR LONGITUD DE CONTRASEÑA =========
    if (!$is_admin && (strlen($password) < 8 || strlen($password) > 24)) {
        redirect_error('pass_invalida');
    }

    // ========= LIMPIAR INTENTOS FALLIDOS =========
    $conn->query("DELETE FROM login_intentos WHERE ip = '$remoteip'");

    // ========= REGISTRAR LOGIN EXITOSO =========
    try {
        $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? '';
        $stmt_log = $conn->prepare("INSERT INTO login_logs (usuario_id, ip, user_agent, exito) VALUES (?, ?, ?, 1)");
        $stmt_log->bind_param("iss", $user['id'], $remoteip, $user_agent);
        $stmt_log->execute();
    } catch (Exception $e) {
        error_log("Error registrando log: " . $e->getMessage());
        // Continuar aunque falle el log
    }

    // ========= INICIAR SESIÓN =========
    session_regenerate_id(true);

    $_SESSION['user_id'] = $user['id'];
    $_SESSION['user_name'] = $user['nombre'];
    $_SESSION['rol'] = $user['rol'];
    $_SESSION['grupo_id'] = $user['grupo_id'];
    $_SESSION['grupo_nombre'] = $user['grupo'];
    $_SESSION['last_activity'] = time();
    $_SESSION['ip_address'] = $remoteip;
    $_SESSION['user_agent'] = $_SERVER['HTTP_USER_AGENT'] ?? '';

    // Generar nuevo token CSRF
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));

    // ========= REDIRECCIÓN =========
    header("Location: ../dashboard.php");
    exit();

} catch (Exception $e) {
    error_log("Error crítico en login: " . $e->getMessage());
    registrar_intento_fallido($conn, $remoteip, $cedula);
    redirect_error('credenciales');
}
?>