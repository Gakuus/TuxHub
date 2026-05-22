<?php
require_once __DIR__ . '/db_connection.php';
require_once __DIR__ . '/send_email.php';
require_once __DIR__ . '/helpers.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();

    $email = trim($_POST['email'] ?? '');

    if (empty($email) || strlen($email) > 50 || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        header("Location: ../password_reset_request.php?error=" . urlencode("Formato de correo electrónico inválido"));
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
        app_log('warning', 'Rate limit excedido en recuperación de contraseña', ['ip' => $ip]);
        header("Location: ../password_reset_request.php?error=" . urlencode("Demasiadas solicitudes. Intente más tarde."));
        exit;
    }

    // Buscar usuario por email
    $stmt = $conn->prepare("SELECT id, nombre FROM usuarios WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 0) {
        app_log('info', 'Solicitud de recuperación para email no registrado', ['email' => $email]);
        sleep(1);
        header("Location: ../password_reset_success.php");
        exit;
    }

    $user = $result->fetch_assoc();
    $token = bin2hex(random_bytes(32));
    $expira = date("Y-m-d H:i:s", strtotime("+1 hour"));

    $stmt = $conn->prepare("INSERT INTO password_resets (user_id, token, expira, ip_address) VALUES (?, ?, ?, ?)
                            ON DUPLICATE KEY UPDATE token = VALUES(token), expira = VALUES(expira), ip_address = VALUES(ip_address), created_at = NOW()");
    $stmt->bind_param("isss", $user['id'], $token, $expira, $ip);

    if (!$stmt->execute()) {
        app_log('error', 'Error al guardar token de recuperación', ['user_id' => $user['id']]);
        header("Location: ../password_reset_request.php?error=" . urlencode("Error interno. Intente más tarde."));
        exit;
    }
    $stmt->close();

    $link = base_url() . "/backend/password_reset_form.php?token=" . urlencode($token);

    $mensajeEmail = "
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset='UTF-8'>
            <title>Recuperación de Contraseña</title>
            <style>
                body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                .header { background: #667eea; color: white; padding: 20px; text-align: center; border-radius: 8px 8px 0 0; }
                .content { background: #f9f9f9; padding: 20px; }
                .button { display: inline-block; background: #667eea; color: white; padding: 12px 24px; text-decoration: none; border-radius: 8px; font-weight: 600; }
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
                    <p><strong>Este enlace expirar&aacute; en 1 hora.</strong></p>
                    <p>Si no solicitaste este cambio, por favor ignora este mensaje.</p>
                </div>
                <div class='footer'>
                    <p>Este es un mensaje automático, por favor no respondas a este correo.</p>
                </div>
            </div>
        </body>
        </html>
    ";

    app_log('info', 'Enviando correo de recuperación', ['user_id' => $user['id'], 'email' => $email]);

    if (enviarCorreo($email, "Recuperar contraseña - Agora", $mensajeEmail)) {
        header("Location: ../password_reset_success.php");
    } else {
        app_log('error', 'Fallo al enviar correo de recuperación', ['user_id' => $user['id']]);
        header("Location: ../password_reset_request.php?error=" . urlencode("No se pudo enviar el correo. Intente más tarde."));
    }

    exit;
}

header("Location: ../password_reset_request.php");
exit;
?>