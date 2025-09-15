<?php
// backend/handle_register.php

// 1. ¡Protegemos también este script!
// Así evitamos que alguien envíe datos a este archivo sin ser admin.
require_once 'auth_admin.php';

// 2. Incluir la conexión a la BD
require_once 'db_connection.php';

// 3. Verificar que la solicitud sea por método POST
if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: registrar_usuario.php");
    exit();
}

// 4. Recoger y validar los datos del formulario
$nombre = trim($_POST['nombre']);
$cedula = trim($_POST['cedula']);
$password = $_POST['password']; // No usamos trim en la contraseña
$rol = trim($_POST['rol']);

if (empty($nombre) || empty($cedula) || empty($password) || empty($rol)) {
    header("Location: registrar_usuario.php?error=campos_vacios");
    exit();
}

// 5. Hashear la contraseña (¡SEGURIDAD PRIMERO!)
$password_hashed = password_hash($password, PASSWORD_DEFAULT);

// 6. Verificar si la cédula ya existe en la BD
$stmt = $conn->prepare("SELECT id FROM usuarios WHERE cedula = ?");
$stmt->bind_param("s", $cedula);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    // La cédula ya existe, redirigir con error
    header("Location: registrar_usuario.php?error=cedula_existe");
    exit();
}
$stmt->close();

// 7. Insertar el nuevo usuario en la base de datos
$stmt = $conn->prepare("INSERT INTO usuarios (nombre, cedula, password, rol) VALUES (?, ?, ?, ?)");
$stmt->bind_param("ssss", $nombre, $cedula, $password_hashed, $rol);

if ($stmt->execute()) {
    // Éxito: redirigir con mensaje de éxito
    header("Location: registrar_usuario.php?success=1");
} else {
    // Error: redirigir con mensaje de error
    header("Location: registrar_usuario.php?error=db_error");
}

$stmt->close();
$conn->close();
?>