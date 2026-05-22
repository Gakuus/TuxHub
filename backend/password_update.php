<?php
require_once __DIR__ . '/helpers.php';
require_once __DIR__ . '/db_connection.php';

const MIN_PASS_LENGTH = 8;
const MAX_PASS_LENGTH = 24;

function displayPage(string $message, string $type = 'success'): void
{
    $icon = ($type === 'success') ? 'bi-check-circle-fill' : 'bi-exclamation-circle';
    $icon_color = ($type === 'success') ? '#34d399' : '#f87171';
    $title = ($type === 'success') ? 'Contraseña actualizada' : 'Error';
    $link = ($type === 'success')
        ? '<a href="../index.php" class="btn-primary-custom"><i class="bi bi-box-arrow-in-right"></i> Iniciar sesión</a>'
        : '<a href="javascript:history.back()" class="btn-secondary-custom"><i class="bi bi-arrow-left"></i> Volver a intentar</a>';

    echo <<<HTML
    <!DOCTYPE html>
    <html lang="es">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>{$title} — Agora</title>
        <link rel="preload" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" as="style">
        <link rel="preload" href="../css/login.css" as="style">
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
        <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
        <link href="../css/login.css" rel="stylesheet">
        <meta name="referrer" content="strict-origin-when-cross-origin">
        <meta name="theme-color" content="#667eea">
        <style>
            .login-card { max-width: 460px; margin: 0 auto; text-align: center; }
            .result-icon { font-size: 3.5rem; color: {$icon_color}; margin-bottom: 0.5rem; }
            .result-title { font-size: 1.5rem; font-weight: 700; color: #fff; margin: 0 0 0.75rem; }
            .result-text { font-size: 0.9rem; color: rgba(255,255,255,0.6); line-height: 1.6; margin-bottom: 1.5rem; }
            .btn-primary-custom {
                display: inline-flex; align-items: center; justify-content: center; gap: 0.5rem;
                width: 100%; height: 50px; padding: 0 1.5rem;
                background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                border: none; border-radius: 14px; font-family: inherit; font-size: 1rem; font-weight: 600;
                color: #fff; cursor: pointer; transition: all 0.3s ease; text-decoration: none;
            }
            .btn-primary-custom:hover { transform: translateY(-2px); box-shadow: 0 8px 30px rgba(102,126,234,0.35); color: #fff; }
            .btn-secondary-custom {
                display: inline-flex; align-items: center; justify-content: center; gap: 0.5rem;
                width: 100%; height: 50px; padding: 0 1.5rem;
                background: rgba(255,255,255,0.08); border: 1.5px solid rgba(255,255,255,0.15);
                border-radius: 14px; font-family: inherit; font-size: 1rem; font-weight: 500;
                color: rgba(255,255,255,0.7); cursor: pointer; transition: all 0.3s ease; text-decoration: none;
            }
            .btn-secondary-custom:hover { background: rgba(255,255,255,0.14); color: #fff; }
        </style>
    </head>
    <body>
        <div class="bg-orbs" aria-hidden="true">
            <div class="orb orb-1"></div>
            <div class="orb orb-2"></div>
            <div class="orb orb-3"></div>
            <div class="orb orb-4"></div>
        </div>
        <div class="login-wrapper">
            <main class="login-main">
                <div class="login-card">
                    <div class="result-icon"><i class="bi {$icon}"></i></div>
                    <h2 class="result-title">{$title}</h2>
                    <p class="result-text">{$message}</p>
                    {$link}
                </div>
                <p class="copyright">&copy; 2026 Agora — Todos los derechos reservados</p>
            </main>
        </div>
    </body>
    </html>
HTML;
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../index.php');
    exit();
}

// CSRF check
csrf_verify();

$token = $_POST['token'] ?? '';
$password = $_POST['password'] ?? '';

$pass_len = strlen($password);
if ($pass_len < MIN_PASS_LENGTH || $pass_len > MAX_PASS_LENGTH) {
    displayPage("La contraseña debe tener entre " . MIN_PASS_LENGTH . " y " . MAX_PASS_LENGTH . " caracteres.", 'error');
}
if (!preg_match('/[A-Z]/', $password)) {
    displayPage("La contraseña debe contener al menos una mayúscula.", 'error');
}
if (!preg_match('/[a-z]/', $password)) {
    displayPage("La contraseña debe contener al menos una minúscula.", 'error');
}
if (!preg_match('/[0-9]/', $password)) {
    displayPage("La contraseña debe contener al menos un número.", 'error');
}

$conn->begin_transaction();

try {
    $stmt = $conn->prepare("SELECT user_id FROM password_resets WHERE token = ? AND expira > NOW()");
    $stmt->bind_param("s", $token);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 0) {
        throw new Exception("El enlace de recuperación no es válido o ha expirado.");
    }

    $user_id = $result->fetch_assoc()['user_id'];
    $stmt->close();

    $hash = password_hash($password, PASSWORD_DEFAULT);

    $stmt = $conn->prepare("UPDATE usuarios SET password = ? WHERE id = ?");
    $stmt->bind_param("si", $hash, $user_id);
    if (!$stmt->execute()) {
        throw new Exception("No se pudo actualizar la contraseña.");
    }
    $stmt->close();

    $stmt = $conn->prepare("DELETE FROM password_resets WHERE token = ?");
    $stmt->bind_param("s", $token);
    if (!$stmt->execute()) {
        throw new Exception("No se pudo invalidar el token.");
    }
    $stmt->close();

    $conn->commit();

    app_log('info', 'Contraseña restablecida exitosamente', ['user_id' => $user_id]);

    displayPage("Tu contraseña ha sido actualizada con éxito. Ya puedes iniciar sesión.", 'success');

} catch (Exception $e) {
    $conn->rollback();
    app_log('error', 'Error al restablecer contraseña', ['error' => $e->getMessage()]);
    displayPage("Hubo un problema al procesar tu solicitud. El enlace puede haber expirado. Por favor, solicita uno nuevo.", 'error');
}
