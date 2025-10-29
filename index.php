<?php
session_start();

if (isset($_SESSION['user_id'])) {
    header("Location: dashboard.php");
    exit();
}

$error_code = $_GET['error'] ?? null;
$error_messages = [
    'requerido' => "⚠️ Debes iniciar sesión para continuar.",
    'campos' => "⚠️ Debe completar todos los campos.",
    'cedula_formato' => "⚠️ La cédula debe contener solo números.",
    'cedula_larga' => "⚠️ La cédula no puede superar los 8 dígitos.",
    'credenciales' => "❌ Credenciales incorrectas.",
    'pass_invalida' => "⚠️ La contraseña debe tener entre 8 y 24 caracteres.",
    'captcha' => "⚠️ Verifica el CAPTCHA.",
    'bloqueado' => "🚫 Demasiados intentos. Intenta de nuevo en unos minutos."
];
$error_message = $error_messages[$error_code] ?? null;
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Iniciar Sesión - Agora</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <script src="https://www.google.com/recaptcha/api.js" async defer></script>
    <style>
        body {
            margin: 0;
            padding: 0;
            min-height: 100vh;
            display: flex; 
            align-items: center; 
            justify-content: center;
            background: url('img/itsp.jpeg') no-repeat center center fixed;
            background-size: cover;
            position: relative;
            overflow: hidden;
        }
        body::before {
            content: "";
            position: absolute;
            inset: 0;
            background: rgba(0, 0, 0, 0.4);
            backdrop-filter: blur(8px);
            z-index: 0;
        }
        .login-card {
            position: relative;
            z-index: 1;
            max-width: 400px; 
            width: 100%;
            background: rgba(255, 255, 255, 0.9);
            backdrop-filter: blur(10px);
            padding: 2rem; 
            border-radius: 1rem;
            box-shadow: 0 8px 30px rgba(0, 0, 0, 0.3);
            animation: fadeIn 0.6s ease-in-out;
        }
        @keyframes fadeIn { 
            from { opacity:0; transform:scale(0.95);} 
            to { opacity:1; transform:scale(1);} 
        }
        .text-danger { font-size: 0.875rem; }
        .login-card h2 {
            font-weight: bold;
            color: #0d6efd;
        }
        .forgot-password {
            display: block;
            text-align: center;
            margin-top: 1rem;
            font-size: 0.9rem;
            color: #0d6efd;
            text-decoration: none;
            transition: color 0.3s ease, transform 0.2s ease;
        }
        .forgot-password:hover {
            color: #084298;
            transform: scale(1.03);
            text-decoration: underline;
        }

        /* ================================================== */
        /* 💡 Corrección para el botón de mostrar contraseña */
        /* ================================================== */
        .password-wrapper {
            /* Necesario para que el botón con position: absolute se posicione con respecto a este div */
            position: relative;
        }

        .password-wrapper input {
            /* Añade espacio suficiente a la derecha para que el ícono no tape el texto */
            padding-right: 40px !important; 
        }

        .toggle-password {
            position: absolute;
            /* Fija la posición horizontal desde el borde derecho */
            right: 10px; 
            /* Centra verticalmente: 50% de arriba menos la mitad de la altura del botón */
            top: 50%;
            transform: translateY(-50%);
            /* Asegura que el botón se vea por encima del input */
            z-index: 2; 
            
            /* Estilos cosméticos del botón */
            background: none;
            border: none;
            cursor: pointer;
            color: #6c757d;
            font-size: 1.25rem;
            line-height: 1;
            padding: 0; /* Remueve cualquier padding predeterminado */
            display: flex; /* Asegura un mejor centrado del ícono */
            align-items: center;
            justify-content: center;
            height: 100%; /* Ocupa la altura del wrapper para mejor centrado vertical */
        }

        .toggle-password:hover {
            color: #0d6efd;
        }
        /* ================================================== */
        
    </style>
</head>
<body>

<div class="login-card">
    <div class="text-center mb-4">
        <img src="img/Logo.png" alt="Logo de Agora" style="width: 100px; height: auto;">
        <h2 class="mt-2">Agora</h2>
        <p class="text-muted">Gestión del Instituto</p>
    </div>

    <?php if ($error_message && in_array($error_code, ['requerido','bloqueado'])): ?>
        <div class="alert alert-warning alert-dismissible fade show" role="alert">
            <?= htmlspecialchars($error_message) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <form action="backend/login_handler.php" method="POST" novalidate>
        <div class="mb-3">
            <label for="cedula" class="form-label">Cédula</label>
            <input type="text" name="cedula" id="cedula" class="form-control" 
                   maxlength="8" pattern="\d*" required placeholder="Ingrese su cédula">
            <?php if ($error_code && in_array($error_code, ['cedula_formato', 'cedula_larga', 'credenciales'])): ?>
                <div class="text-danger mt-1"><?= htmlspecialchars($error_message) ?></div>
            <?php endif; ?>
        </div>

        <div class="mb-3 password-wrapper">
            <label for="password" class="form-label">Contraseña</label>
            <input type="password" name="password" id="password" class="form-control" 
                   minlength="8" maxlength="24" required placeholder="Ingrese su contraseña">
            <button type="button" class="toggle-password" id="togglePassword" aria-label="Mostrar contraseña">
                <i class="bi bi-eye-fill" id="eyeIcon"></i>
            </button>
            <?php if ($error_code && in_array($error_code, ['pass_invalida', 'credenciales'])): ?>
                <div class="text-danger mt-1"><?= htmlspecialchars($error_message) ?></div>
            <?php endif; ?>
        </div>

        <div class="g-recaptcha mb-3" data-sitekey="6LeskeMrAAAAACQ7-Uo7bDdkOJ5e6EyWA6zL9HEF"></div>
        <?php if ($error_code === 'captcha'): ?>
            <div class="text-danger mt-1"><?= htmlspecialchars($error_message) ?></div>
        <?php endif; ?>

        <button type="submit" class="btn btn-primary w-100 fw-bold">
            <i class="bi bi-box-arrow-in-right"></i> Iniciar Sesión
        </button>

        <a href="password_reset_request.php" class="forgot-password">
            ¿Olvidaste tu contraseña?
        </a>
    </form>
</div>

<script>
    const togglePassword = document.getElementById('togglePassword');
    const passwordInput = document.getElementById('password');
    const eyeIcon = document.getElementById('eyeIcon');

    togglePassword.addEventListener('click', () => {
        // Toggle the input type
        const isVisible = passwordInput.type === 'text';
        passwordInput.type = isVisible ? 'password' : 'text';
        
        // Toggle the icon class (bi-eye-slash-fill is the crossed-out eye)
        eyeIcon.classList.toggle('bi-eye-fill', isVisible);
        eyeIcon.classList.toggle('bi-eye-slash-fill', !isVisible);
        
        // Update the aria-label for accessibility
        togglePassword.setAttribute('aria-label', isVisible ? 'Mostrar contraseña' : 'Ocultar contraseña');
    });
</script>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>