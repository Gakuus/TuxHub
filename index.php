<?php
session_start();

if (isset($_SESSION['user_id'])) {
    header("Location: dashboard.php");
    exit();
}

// Generar token CSRF si no existe
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$error_code = $_GET['error'] ?? null;
$error_messages = [
    'requerido' => "⚠️ Debes iniciar sesión para continuar.",
    'campos' => "⚠️ Debe completar todos los campos.",
    'cedula_formato' => "⚠️ La cédula debe contener solo números.",
    'cedula_larga' => "⚠️ La cédula debe tener exactamente 8 dígitos.", // CORREGIDO: 8 dígitos exactos
    'credenciales' => "❌ Credenciales incorrectas.",
    'pass_invalida' => "⚠️ La contraseña debe tener entre 8 y 24 caracteres.",
    'captcha' => "⚠️ Verifica el CAPTCHA.",
    'bloqueado' => "🚫 Demasiados intentos. Intenta de nuevo en unos minutos.",
    'csrf' => "⚠️ Error de seguridad. Recarga la página e intenta nuevamente.",
    'metodo_invalido' => "⚠️ Método de acceso incorrecto."
];
$error_message = $error_messages[$error_code] ?? null;

// Detectar dispositivo para optimizaciones
$is_mobile = preg_match('/(android|iphone|ipod|blackberry|windows phone)/i', $_SERVER['HTTP_USER_AGENT']);
$is_tablet = preg_match('/(ipad|tablet|playbook|silk)|(android(?!.*mobile))/i', $_SERVER['HTTP_USER_AGENT']);
?>
<!DOCTYPE html>
<html lang="es" class="<?= $is_mobile ? 'mobile' : ($is_tablet ? 'tablet' : 'desktop') ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=yes">
    <title>Iniciar Sesión - Agora</title>
    
    <!-- Preload crítico -->
    <link rel="preload" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" as="style">
    <link rel="preload" href="css/login.css" as="style">
    
    <!-- Hojas de estilo -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="css/login.css" rel="stylesheet">
    
    <!-- reCAPTCHA -->
    <script src="https://www.google.com/recaptcha/api.js" async defer></script>
    
    <!-- Meta tags de seguridad y SEO -->
    <meta http-equiv="Content-Security-Policy" content="
        default-src 'self';
        script-src 'self' https://cdn.jsdelivr.net https://www.google.com https://www.gstatic.com 'unsafe-inline';
        style-src 'self' https://cdn.jsdelivr.net https://fonts.googleapis.com 'unsafe-inline';
        font-src 'self' https://cdn.jsdelivr.net https://fonts.gstatic.com;
        img-src 'self' data: https:;
        frame-src 'self' https://www.google.com;
        connect-src 'self' https://cdn.jsdelivr.net;
    ">
    <meta name="referrer" content="strict-origin-when-cross-origin">
    <meta name="theme-color" content="#0d6efd">
    <meta name="description" content="Sistema de gestión del Instituto - Agora">
    
    <?php if ($is_mobile): ?>
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <?php endif; ?>
</head>
<body>

<div class="login-container">
    <div class="login-card">
        <div class="login-header">
            <img src="img/Logo.png" alt="Logo de Agora" class="login-logo" 
                 width="90" height="90" loading="eager">
            <h1 class="login-title">Agora</h1>
            <p class="login-subtitle">Gestión del Instituto</p>
        </div>

        <?php if ($error_message): ?>
            <div class="alert <?= in_array($error_code, ['bloqueado', 'csrf', 'metodo_invalido']) ? 'alert-security' : 'alert-warning' ?> alert-dismissible fade show" role="alert">
                <i class="bi bi-shield-exclamation"></i> 
                <?= htmlspecialchars($error_message) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Cerrar"></button>
            </div>
        <?php endif; ?>

        <form action="backend/login_handler.php" method="POST" novalidate id="loginForm" class="needs-validation">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">
            
            <div class="mb-3">
                <label for="cedula" class="form-label">
                    <i class="bi bi-person-badge" aria-hidden="true"></i> Cédula
                </label>
                <input type="text" 
                       name="cedula" 
                       id="cedula" 
                       class="form-control" 
                       maxlength="8" 
                       pattern="\d{8}" 
                       required 
                       placeholder="Ej: 12345678"
                       title="Ingrese su número de cédula de 8 dígitos"
                       inputmode="numeric"
                       autocomplete="username"
                       <?= $is_mobile ? 'autocapitalize="none"' : '' ?>
                       aria-describedby="cedulaHint">
                <small id="cedulaHint" class="input-hint">
                    Solo números, exactamente 8 dígitos
                </small>
                
                <?php if ($error_code && in_array($error_code, ['cedula_formato', 'cedula_larga'])): ?>
                    <div class="text-danger mt-1">
                        <i class="bi bi-exclamation-triangle"></i> 
                        <?= htmlspecialchars($error_message) ?>
                    </div>
                <?php endif; ?>
            </div>

            <div class="mb-3">
                <label for="password" class="form-label">
                    <i class="bi bi-lock" aria-hidden="true"></i> Contraseña
                </label>
                <div class="password-wrapper">
                    <input type="password" 
                           name="password" 
                           id="password" 
                           class="form-control" 
                           required 
                           placeholder="Ingrese su contraseña"
                           title="Ingrese su contraseña"
                           autocomplete="current-password"
                           minlength="8"
                           maxlength="24"
                           aria-describedby="passwordHint">
                    <button type="button" 
                            class="toggle-password" 
                            id="togglePassword" 
                            aria-label="Mostrar contraseña"
                            tabindex="0">
                        <i class="bi bi-eye-fill" id="eyeIcon" aria-hidden="true"></i>
                    </button>
                </div>
                <small id="passwordHint" class="input-hint">
                    La contraseña debe tener entre 8 y 24 caracteres
                </small>
            </div>

            <div class="mb-3">
                <div class="g-recaptcha" data-sitekey="6LeskeMrAAAAACQ7-Uo7bDdkOJ5e6EyWA6zL9HEF" data-size="normal"></div>
                <small class="input-hint">Verificación de seguridad</small>
            </div>

            <button type="submit" class="btn btn-primary w-100 fw-bold" id="submitBtn">
                <i class="bi bi-box-arrow-in-right" aria-hidden="true"></i> 
                <span id="submitText">Iniciar Sesión</span>
            </button>

            <div class="security-notice" role="note">
                <i class="bi bi-shield-check" aria-hidden="true"></i> 
                Conexión segura protegida por encriptación
            </div>

            <a href="password_reset_request.php" class="forgot-password" aria-label="Recuperar contraseña">
                <i class="bi bi-key" aria-hidden="true"></i> 
                ¿Olvidaste tu contraseña?
            </a>
        </form>
    </div>
</div>

<!-- Scripts al final del body para mejor performance -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="assets/login.js"></script>

</body>
</html>