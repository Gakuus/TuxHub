<?php
// backend/login_handler.php

session_start(); // Inicia la sesión para manejar variables de usuario

// 1. Incluir la conexión a la BD
require_once 'db_connection.php';

// --- NUEVA VALIDACIÓN ---
// Verificar que la solicitud sea por método POST
if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    // Si no, redirigir al formulario de login
    header("Location: ../index.html"); // Asegúrate que esta sea la ruta a tu login
    exit();
}

// 2. Validación de que los campos no estén vacíos
if (!isset($_POST['cedula']) || empty(trim($_POST['cedula'])) || !isset($_POST['password']) || empty($_POST['password'])) {
    // Redirigir de vuelta al login con un mensaje de error
    header("Location: ../index.html?error=campos_vacios");
    exit();
}

// Se obtienen los datos del formulario
$cedula = $_POST['cedula'];
$password_ingresada = $_POST['password'];

// 3. Preparar la consulta para buscar por CÉDULA
$stmt = $conn->prepare("SELECT id, nombre, password, rol FROM usuarios WHERE cedula = ?");
$stmt->bind_param("s", $cedula); // "s" significa que el parámetro es un string
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 1) {
    $user = $result->fetch_assoc();

    // 4. VERIFICACIÓN SEGURA DE LA CONTRASEÑA (¡MUY IMPORTANTE!)
    // Compara la contraseña ingresada con el hash guardado en la base de datos.
    if (password_verify($password_ingresada, $user['password'])) {
        
        // 5. Autenticación exitosa: Guardar datos en la sesión
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['user_name'] = $user['nombre'];
        $_SESSION['user_rol'] = $user['rol'];

        // Redirigir al dashboard
        header("Location: ../dashboard.html");
        exit();

    } else {
        // Contraseña incorrecta: redirigir con error
        header("Location: ../index.html?error=credenciales_invalidas");
        exit();
    }
} else {
    // Usuario no encontrado: redirigir con el MISMO error para no dar pistas
    header("Location: ../index.html?error=credenciales_invalidas");
    exit();
}

$stmt->close();
$conn->close();
?>