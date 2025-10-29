<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require __DIR__ . '/../vendor/autoload.php';

function enviarCorreo($destinatario, $asunto, $contenidoHTML) {
    $mail = new PHPMailer(true);

    try {
        // Config Gmail SMTP
        $mail->isSMTP();
        $mail->Host = 'smtp.gmail.com';
        $mail->SMTPAuth = true;
        $mail->Username = 'correo_revocado@example.com';
        $mail->Password = 'CREDENCIALES_REVOCADAS';
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = 587;

        // Remitente y destinatario
        $mail->setFrom('correo_revocado@example.com', 'Agora - Recuperación de contraseña');
        $mail->addAddress($destinatario);

        // Contenido
        $mail->isHTML(true);
        $mail->Subject = $asunto;
        $mail->Body    = $contenidoHTML;

        $mail->send();
        return true;
    } catch (Exception $e) {
        return false;
    }
}
