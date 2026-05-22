<?php
// Protecciones básicas
require_once __DIR__ . '/auth_admin.php';
require_once __DIR__ . '/db_connection.php';
require_once __DIR__ . '/helpers.php';

csrf_verify();

$ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
if (!rate_limit_check($ip, 'register', 20, 60)) {
    http_response_code(429);
    header("Location: ../pages/registrar_usuario.php?error=rate_limit");
    exit;
}

// Validamos que sea POST
if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: ../pages/registrar_usuario.php");
    exit();
}

// Activamos excepciones para mysqli
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

try {
    // Sanitización de entrada
    $nombre   = isset($_POST['nombre']) ? trim($_POST['nombre']) : '';
    $cedula   = isset($_POST['cedula']) ? trim($_POST['cedula']) : '';
    $email    = isset($_POST['email']) ? trim($_POST['email']) : null;
    $password = isset($_POST['password']) ? $_POST['password'] : '';
    $rol      = isset($_POST['rol']) ? trim($_POST['rol']) : '';
    $grupo_id = (!empty($_POST['grupo_id'])) ? intval($_POST['grupo_id']) : null;

    // Validaciones mínimas
    if ($nombre === '' || $cedula === '' || $password === '' || $rol === '') {
        header("Location: ../pages/registrar_usuario.php?error=campos_vacios");
        exit();
    }

    if (!preg_match('/^\d{7,8}$/', $cedula)) {
        header("Location: ../pages/registrar_usuario.php?error=campos_invalidos");
        exit();
    }

    if ($email && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        header("Location: ../pages/registrar_usuario.php?error=campos_invalidos");
        exit();
    }

    if (!in_array($rol, ["alumno", "profesor", "administrador"])) {
        header("Location: ../pages/registrar_usuario.php?error=campos_invalidos");
        exit();
    }

    $pass_len = strlen($password);
    if ($pass_len < 8 || $pass_len > 24) {
        header("Location: ../pages/registrar_usuario.php?error=pass_length");
        exit();
    }
    if (!preg_match('/[A-Z]/', $password)) {
        header("Location: ../pages/registrar_usuario.php?error=pass_mayus");
        exit();
    }
    if (!preg_match('/[a-z]/', $password)) {
        header("Location: ../pages/registrar_usuario.php?error=pass_minus");
        exit();
    }
    if (!preg_match('/[0-9]/', $password)) {
        header("Location: ../pages/registrar_usuario.php?error=pass_digit");
        exit();
    }

    // Generar hash seguro
    $password_hashed = password_hash($password, PASSWORD_DEFAULT);

    // Valor para columna "grupo"
    $grupo_nombre = null;
    if ($rol === "alumno" && $grupo_id !== null) {
        // Traer nombre real del grupo (opcional para mantener integridad)
        $stmt = $conn->prepare("SELECT nombre FROM grupos WHERE id = ?");
        $stmt->bind_param("i", $grupo_id);
        $stmt->execute();
        $res = $stmt->get_result();
        if ($row = $res->fetch_assoc()) {
            $grupo_nombre = $row['nombre'];
        }
        $stmt->close();
    }

    // 🚀 Inserción segura
    $sql = "INSERT INTO usuarios (cedula, nombre, email, password, rol,grupo_id) 
            VALUES (?, ?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param(
        "sssssi",
        $cedula,
        $nombre,
        $email,
        $password_hashed,
        $rol,
        $grupo_id
    );
    $stmt->execute();
    $stmt->close();
    $conn->close();

    header("Location: ../pages/registrar_usuario.php?success=1");
    exit();

} catch (mysqli_sql_exception $e) {
    // Usuario duplicado (MySQL error code 1062)
    if ($e->getCode() === 1062) {
        header("Location: ../pages/registrar_usuario.php?error=cedula_existe");
        exit();
    }

    error_log("Error MySQL al insertar usuario: [{$e->getCode()}] {$e->getMessage()}");
    header("Location: ../pages/registrar_usuario.php?error=db_error");
    exit();

} catch (Exception $e) {
    error_log("Error inesperado en handle_register.php: " . $e->getMessage());
    header("Location: ../pages/registrar_usuario.php?error=server_error");
    exit();
}
