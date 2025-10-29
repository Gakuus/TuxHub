<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/db_connection.php';
require_once __DIR__ . '/send_email.php'; // usará PHPMailer

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');

    if (empty($email)) {
        header("Location: ../password_reset_request.php?error=Ingrese un correo válido");
        exit;
    }

    // Buscar usuario por email
    $stmt = $conn->prepare("SELECT id, nombre FROM usuarios WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 0) {
        header("Location: ../password_reset_request.php?error=No existe una cuenta con ese correo.");
        exit;
    }

    $user = $result->fetch_assoc();
    $token = bin2hex(random_bytes(32));
    $expira = date("Y-m-d H:i:s", strtotime("+1 hour"));

    // Guardar token en BD
    $stmt = $conn->prepare("INSERT INTO password_resets (user_id, token, expira) VALUES (?, ?, ?)
                            ON DUPLICATE KEY UPDATE token=?, expira=?");
    $stmt->bind_param("issss", $user['id'], $token, $expira, $token, $expira);
    $stmt->execute();

    // Enviar email
    $link = "https://dbitsp.tailff9876.ts.net/Agora/Agora/backend/password_reset_form.php?token=$token";
    $mensaje = "
        <h2>Recuperación de contraseña</h2>
        <p>Hola {$user['nombre']},</p>
        <p>Haz clic en el siguiente enlace para restablecer tu contraseña (válido por 1 hora):</p>
        <a href='$link'>$link</a>
    ";

    if (enviarCorreo($email, "Recuperar contraseña - Agora", $mensaje)) {
        header("Location: ../password_reset_request.php?success=Se ha enviado un enlace a tu correo.");
    } else {
        header("Location: ../password_reset_request.php?error=No se pudo enviar el correo.");
    }
}
