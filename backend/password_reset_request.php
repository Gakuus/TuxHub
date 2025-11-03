<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/db_connection.php';
require_once __DIR__ . '/send_email.php';

// Iniciar sesión
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Generar CSRF token si no existe
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Procesar formulario POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validar CSRF token
    if (empty($_POST['csrf_token']) || empty($_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        header("Location: ../password_reset.php?error=Token de seguridad inválido");
        exit;
    }

    $email = trim($_POST['email'] ?? '');

    // Validaciones de entrada
    if (empty($email)) {
        header("Location: ../password_reset.php?error=Ingrese un correo válido");
        exit;
    }

    if (strlen($email) > 50) {
        header("Location: ../password_reset.php?error=El correo debe tener máximo 50 caracteres");
        exit;
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        header("Location: ../password_reset.php?error=Formato de correo electrónico inválido");
        exit;
    }

    // Rate limiting por IP
    $ip = $_SERVER['REMOTE_ADDR'];
    $stmt = $conn->prepare("SELECT COUNT(*) as count FROM password_resets WHERE ip_address = ? AND created_at > DATE_SUB(NOW(), INTERVAL 1 HOUR)");
    $stmt->bind_param("s", $ip);
    $stmt->execute();
    $result = $stmt->get_result();
    $rateData = $result->fetch_assoc();
    $stmt->close();

    if ($rateData['count'] >= 5) {
        header("Location: ../password_reset.php?error=Demasiadas solicitudes. Intente más tarde.");
        exit;
    }

    // Buscar usuario por email (Prepared Statement - Seguro contra SQL Injection)
    $stmt = $conn->prepare("SELECT id, nombre FROM usuarios WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    // Mensaje genérico para evitar enumeración de usuarios
    $mensajeExito = "Si el correo existe en nuestro sistema, se ha enviado un enlace de recuperación.";

    if ($result->num_rows === 0) {
        // Simular delay para evitar timing attacks
        sleep(1);
        // Redirigir a página de éxito aunque el email no exista (seguridad)
        header("Location: ../password_reset_success.php");
        exit;
    }

    $user = $result->fetch_assoc();
    $token = bin2hex(random_bytes(32));
    $expira = date("Y-m-d H:i:s", strtotime("+1 hour"));

    // Guardar token en BD (Prepared Statement - Seguro contra SQL Injection)
    $stmt = $conn->prepare("INSERT INTO password_resets (user_id, token, expira, ip_address) VALUES (?, ?, ?, ?)
                            ON DUPLICATE KEY UPDATE token = VALUES(token), expira = VALUES(expira), ip_address = VALUES(ip_address), created_at = NOW()");
    $stmt->bind_param("isss", $user['id'], $token, $expira, $ip);
    
    if (!$stmt->execute()) {
        header("Location: ../password_reset.php?error=Error interno. Intente más tarde.");
        exit;
    }
    $stmt->close();

    // Enviar email
    $link = "https://dbitsp.tailff9876.ts.net/Agora/Agora/password_reset_form.php?token=" . urlencode($token);
    
    $mensajeEmail = "
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset='UTF-8'>
            <title>Recuperación de Contraseña</title>
            <style>
                body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                .header { background: #007bff; color: white; padding: 20px; text-align: center; }
                .content { background: #f9f9f9; padding: 20px; }
                .button { display: inline-block; background: #007bff; color: white; padding: 12px 24px; text-decoration: none; border-radius: 4px; }
                .footer { text-align: center; margin-top: 20px; font-size: 12px; color: #666; }
            </style>
        </head>
        <body>
            <div class='container'>
                <div class='header'>
                    <h2>Recuperación de Contraseña - Agora</h2>
                </div>
                <div class='content'>
                    <p>Hola <strong>{$user['nombre']}</strong>,</p>
                    <p>Has solicitado restablecer tu contraseña. Haz clic en el siguiente botón para continuar:</p>
                    <p style='text-align: center;'>
                        <a href='$link' class='button'>Restablecer Contraseña</a>
                    </p>
                    <p>O copia y pega este enlace en tu navegador:</p>
                    <p style='word-break: break-all; background: #eee; padding: 10px; border-radius: 4px;'>$link</p>
                    <p><strong>Este enlace expirará en 1 hora.</strong></p>
                    <p>Si no solicitaste este cambio, por favor ignora este mensaje.</p>
                </div>
                <div class='footer'>
                    <p>Este es un mensaje automático, por favor no respondas a este correo.</p>
                </div>
            </div>
        </body>
        </html>
    ";

    if (enviarCorreo($email, "Recuperar contraseña - Agora", $mensajeEmail)) {
        // Redirigir a página de éxito
        header("Location: ../password_reset_success.php");
    } else {
        header("Location: ../password_reset.php?error=No se pudo enviar el correo. Intente más tarde.");
    }
    
    exit;
}

// Si se accede directamente al archivo sin POST, redirigir al formulario
header("Location: ../password_reset.php");
exit;
?>