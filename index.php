<?php
require_once __DIR__ . '/backend/config.php';
session_start();

if (isset($_SESSION['user_id'])) {
    header("Location: dashboard.php");
    exit();
}

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$error_code = $_GET['error'] ?? null;
$error_messages = [
    'requerido'       => "Debes iniciar sesión para continuar.",
    'campos'          => "Completa todos los campos.",
    'cedula_formato'  => "La cédula debe contener solo números.",
    'cedula_larga'    => "La cédula debe tener exactamente 8 dígitos.",
    'credenciales'    => "Credenciales incorrectas.",
    'pass_invalida'   => "La contraseña debe tener entre 8 y 24 caracteres.",

    'bloqueado'       => "Demasiados intentos. Intenta en unos minutos.",
    'csrf'            => "Error de seguridad. Recarga la página.",
    'metodo_invalido' => "Acceso incorrecto.",
    'captcha'         => "Verificación de seguridad fallida. Intenta de nuevo."
];
$error_message = $error_messages[$error_code] ?? null;
$has_error = $error_message !== null;

$is_mobile = preg_match('/(android|iphone|ipod|blackberry|windows phone)/i', $_SERVER['HTTP_USER_AGENT']);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" href="img/Logo.png" type="image/x-icon">
    <title>Iniciar Sesión — Agora</title>

    <link rel="preload" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" as="style">
    <link rel="preload" href="css/login.css" as="style">

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="css/login.css" rel="stylesheet">

    <meta http-equiv="Content-Security-Policy" content="
        default-src 'self';
        script-src 'self' https://cdn.jsdelivr.net https://www.google.com https://www.gstatic.com 'unsafe-inline';
        style-src 'self' https://cdn.jsdelivr.net https://fonts.googleapis.com 'unsafe-inline';
        font-src 'self' https://cdn.jsdelivr.net https://fonts.gstatic.com;
        img-src 'self' data: https:;
        connect-src 'self' https://cdn.jsdelivr.net https://www.google.com;
        frame-src 'self' https://www.google.com;
    ">
    <meta name="referrer" content="strict-origin-when-cross-origin">
    <meta name="theme-color" content="#667eea">
    <meta name="description" content="Sistema de gestión institucional — Agora">
</head>
<body>

    <!-- Floating background orbs -->
    <div class="bg-orbs" aria-hidden="true">
        <div class="orb orb-1"></div>
        <div class="orb orb-2"></div>
        <div class="orb orb-3"></div>
        <div class="orb orb-4"></div>
    </div>

    <div class="login-wrapper">

        <!-- Brand panel (desktop) -->
        <aside class="brand-panel">
            <div class="brand-content">
                <img src="img/Logo.png" alt="Agora" class="brand-logo" width="100" height="100">
                <h1 class="brand-title">Agora</h1>
                <p class="brand-subtitle">Gestión del Instituto</p>
                <p class="brand-desc">
                    Plataforma integral para la administración académica, horarios, recursos y más.
                </p>
                <div class="brand-features">
                    <span><i class="bi bi-calendar-check"></i> Horarios</span>
                    <span><i class="bi bi-people"></i> Grupos</span>
                    <span><i class="bi bi-building"></i> Salones</span>
                    <span><i class="bi bi-journal-text"></i> Materias</span>
                </div>
            </div>
        </aside>

        <!-- Login card -->
        <main class="login-main">
            <div class="login-card">
                <div class="login-header">
                    <img src="img/Logo.png" alt="Agora" class="login-logo" width="80" height="80">
                    <h2 class="login-title">Bienvenido</h2>
                    <p class="login-subtitle">Inicia sesión para continuar</p>
                </div>

                <!-- Error alert -->
                <div class="alert-container <?= $has_error ? 'visible' : '' ?>">
                    <div class="alert alert-glass <?= in_array($error_code, ['bloqueado','csrf','metodo_invalido']) ? 'alert-danger' : '' ?>" role="alert">
                        <i class="bi <?= in_array($error_code, ['bloqueado','csrf']) ? 'bi-shield-exclamation' : 'bi-exclamation-circle' ?>"></i>
                        <span><?= htmlspecialchars($error_message) ?></span>
                        <button type="button" class="alert-close" data-dismiss="alert" aria-label="Cerrar">&times;</button>
                    </div>
                </div>

                <form action="backend/login_handler.php" method="POST" novalidate id="loginForm">
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">

                    <!-- Cédula -->
                    <div class="input-group <?= $error_code === 'cedula_formato' || $error_code === 'cedula_larga' ? 'has-error' : '' ?>">
                        <div class="input-wrapper">
                            <input type="text" name="cedula" id="cedula"
                                class="form-input" maxlength="8" pattern="\d{8}" required
                                placeholder=" " inputmode="numeric" autocomplete="username">
                            <label for="cedula" class="float-label">
                                <i class="bi bi-person-badge"></i> Cédula
                            </label>
                            <span class="input-icon"><i class="bi bi-person-badge"></i></span>
                        </div>
                        <small class="input-hint">8 dígitos, solo números</small>
                    </div>

                    <!-- Contraseña -->
                    <div class="input-group <?= $error_code === 'pass_invalida' ? 'has-error' : '' ?>">
                        <div class="input-wrapper">
                            <input type="password" name="password" id="password"
                                class="form-input" required minlength="8" maxlength="24"
                                placeholder=" " autocomplete="current-password">
                            <label for="password" class="float-label">
                                <i class="bi bi-lock"></i> Contraseña
                            </label>
                            <button type="button" class="toggle-password" id="togglePassword" tabindex="-1" aria-label="Mostrar contraseña">
                                <i class="bi bi-eye" id="eyeIcon"></i>
                            </button>
                        </div>
                        <small class="input-hint">8 a 24 caracteres</small>
                    </div>

                    <?php $recaptcha_site_key = env('RECAPTCHA_SITE_KEY'); ?>
                    <?php if (!empty($recaptcha_site_key)): ?>
                    <div class="g-recaptcha mb-3" data-sitekey="<?= htmlspecialchars($recaptcha_site_key) ?>"></div>
                    <?php endif; ?>

                    <!-- Submit -->
                    <button type="submit" class="btn-login" id="submitBtn">
                        <span id="submitText">Iniciar Sesión</span>
                        <div class="spinner" id="loginSpinner">
                            <div class="spinner-ring"></div>
                        </div>
                    </button>

                    <!-- Security notice -->
                    <div class="security-row">
                        <span><i class="bi bi-shield-check"></i> Conexión segura</span>
                        <span><i class="bi bi-lock"></i> Encriptado SSL</span>
                    </div>
                </form>

                <div class="login-footer">
                    <a href="password_reset_request.php" class="footer-link">
                        <i class="bi bi-key"></i> ¿Olvidaste tu contraseña?
                    </a>
                </div>
            </div>

            <p class="copyright">
                &copy; <?= date('Y') ?> Agora — Todos los derechos reservados
            </p>
        </main>

    </div>

    <!-- Loading overlay -->
    <div class="loading-overlay" id="loadingOverlay">
        <div class="loading-content">
            <div class="loading-ring"></div>
            <p>Verificando credenciales...</p>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="assets/login.js"></script>
    <?php if (!empty(env('RECAPTCHA_SITE_KEY'))): ?>
    <script src="https://www.google.com/recaptcha/api.js" async defer></script>
    <?php endif; ?>
</body>
</html>
