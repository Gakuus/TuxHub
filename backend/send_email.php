<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

if (session_status() === PHP_SESSION_NONE) session_start();
require __DIR__ . '/config.php';
require __DIR__ . '/../vendor/autoload.php';

function enviarCorreo($destinatario, $asunto, $contenidoHTML) {
    $mail = new PHPMailer(true);

    try {
        $mail->isSMTP();
        $mail->Host = env('SMTP_HOST', 'smtp.gmail.com');
        $mail->SMTPAuth = true;
        $mail->Username = env('SMTP_USER');
        $mail->Password = env('SMTP_PASS');
        $mail->SMTPSecure = env('SMTP_SECURE', 'tls');
        $mail->Port = (int)env('SMTP_PORT', 587);

        if (empty($mail->Username) || empty($mail->Password)) {
            error_log("send_email: SMTP_USER o SMTP_PASS no configurados en .env");
            return false;
        }

        $from_email = env('SMTP_FROM_EMAIL', $mail->Username);
        $from_name = env('SMTP_FROM_NAME', 'Agora');
        $mail->setFrom($from_email, $from_name);
        $mail->addAddress($destinatario);

        $mail->isHTML(true);
        $mail->Subject = $asunto;
        $mail->Body    = $contenidoHTML;

        $mail->send();
        return true;
    } catch (Exception $e) {
        error_log("send_email: " . $e->getMessage());
        return false;
    }
}
