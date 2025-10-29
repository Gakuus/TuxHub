<?php
session_start();
require_once 'db_connection.php';

function redirect_error($code) {
    header("Location: ../index.php?error=$code");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') redirect_error('metodo_invalido');

// ========= CAPTCHA =========
$recaptcha_secret = "6LeskeMrAAAAAEwZ1CwNR2BhiQrDiM47qQPdJ4mr";
$response = $_POST['g-recaptcha-response'] ?? '';
$remoteip = $_SERVER['REMOTE_ADDR'];

$verify = file_get_contents("https://www.google.com/recaptcha/api/siteverify?secret=$recaptcha_secret&response=$response&remoteip=$remoteip");
$captcha_success = json_decode($verify)->success ?? false;
if (!$captcha_success) redirect_error('captcha');

// ========= Campos =========
$cedula = trim($_POST['cedula'] ?? '');
$password = $_POST['password'] ?? '';

if ($cedula === '' || $password === '') redirect_error('campos');
if (!ctype_digit($cedula)) redirect_error('cedula_formato');
if (strlen($cedula) > 8) redirect_error('cedula_larga');

// ========= Control de intentos fallidos =========
$ip = $_SERVER['REMOTE_ADDR'];
$max_intentos = 5;
$tiempo_bloqueo = 300; // 5 minutos

$conn->query("CREATE TABLE IF NOT EXISTS login_intentos (
    ip VARCHAR(45) PRIMARY KEY,
    intentos INT DEFAULT 0,
    ultimo_intento TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)");

$stmt = $conn->prepare("SELECT intentos, UNIX_TIMESTAMP(ultimo_intento) AS t FROM login_intentos WHERE ip=?");
$stmt->bind_param("s", $ip);
$stmt->execute();
$res = $stmt->get_result();
$bloqueado = false;

if ($row = $res->fetch_assoc()) {
    if ($row['intentos'] >= $max_intentos && time() - $row['t'] < $tiempo_bloqueo) {
        redirect_error('bloqueado');
    }
}

// ========= Verificación de usuario =========
$stmt = $conn->prepare("
    SELECT u.id, u.nombre, u.password, u.rol, u.grupo_id, g.nombre AS grupo
    FROM usuarios u
    LEFT JOIN grupos g ON u.grupo_id = g.id
    WHERE u.cedula = ?
");
$stmt->bind_param("s", $cedula);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows !== 1) {
    registrarIntento($conn, $ip);
    redirect_error('credenciales');
}

$user = $result->fetch_assoc();
$is_admin = strtolower($user['rol']) === 'admin';

// ========= Validar contraseña =========
$pass_ok = password_verify($password, $user['password'])
        || ($is_admin && $password === $user['password']); // admin con vieja pass

if (!$pass_ok) {
    registrarIntento($conn, $ip);
    redirect_error('credenciales');
}

// ========= Validar longitud (excepto admin) =========
if (!$is_admin && (strlen($password) < 8 || strlen($password) > 24)) {
    redirect_error('pass_invalida');
}

// ========= Resetear intentos fallidos =========
$conn->query("DELETE FROM login_intentos WHERE ip = '$ip'");

// ========= Registrar login =========
$conn->query("CREATE TABLE IF NOT EXISTS login_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    usuario_id INT,
    ip VARCHAR(45),
    exito BOOLEAN,
    fecha TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)");
$stmt = $conn->prepare("INSERT INTO login_logs (usuario_id, ip, exito) VALUES (?, ?, 1)");
$stmt->bind_param("is", $user['id'], $ip);
$stmt->execute();

// ========= Iniciar sesión =========
$_SESSION['user_id'] = $user['id'];
$_SESSION['user_name'] = $user['nombre'];
$_SESSION['rol'] = $user['rol'];
$_SESSION['grupo_id'] = $user['grupo_id'];
$_SESSION['grupo_nombre'] = $user['grupo'];
$_SESSION['last_activity'] = time();

header("Location: ../dashboard.php");
exit();

// ========= Función auxiliar =========
function registrarIntento($conn, $ip) {
    $conn->query("INSERT INTO login_intentos (ip, intentos, ultimo_intento) 
                  VALUES ('$ip', 1, NOW())
                  ON DUPLICATE KEY UPDATE intentos = intentos + 1, ultimo_intento = NOW()");
}
