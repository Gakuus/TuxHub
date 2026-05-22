<?php
require_once __DIR__ . '/backend/helpers.php';

$error = $_GET['error'] ?? null;
$success = $_GET['success'] ?? null;
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Recuperar contraseña — Agora</title>
    <link rel="preload" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" as="style">
    <link rel="preload" href="css/login.css" as="style">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="css/login.css" rel="stylesheet">
    <meta name="referrer" content="strict-origin-when-cross-origin">
    <meta name="theme-color" content="#667eea">
    <meta name="description" content="Recuperar contraseña - Agora">
    <style>
        .login-card {
            max-width: 460px;
            margin: 0 auto;
        }
        .btn-back {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
            width: 100%;
            height: 50px;
            padding: 0 1.5rem;
            background: rgba(255,255,255,0.08);
            border: 1.5px solid rgba(255,255,255,0.15);
            border-radius: 14px;
            font-family: inherit;
            font-size: 1rem;
            font-weight: 500;
            color: rgba(255,255,255,0.7);
            cursor: pointer;
            transition: all 0.3s ease;
            text-decoration: none;
        }
        .btn-back:hover {
            background: rgba(255,255,255,0.14);
            color: #fff;
            text-decoration: none;
        }
        .info-text {
            font-size: 0.85rem;
            color: rgba(255,255,255,0.5);
            text-align: center;
            margin: 0 0 1.25rem;
            line-height: 1.5;
        }
        .char-count {
            font-size: 0.725rem;
            color: rgba(255,255,255,0.35);
            text-align: right;
            padding-right: 0.25rem;
            margin-top: 0.25rem;
        }
        .char-count.warning {
            color: rgba(239,68,68,0.7);
        }
        .login-footer {
            margin-top: 1rem;
        }
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
                <div class="login-header">
                    <img src="img/Logo.png" alt="Agora" class="login-logo" width="80" height="80">
                    <h2 class="login-title">Recuperar contraseña</h2>
                    <p class="login-subtitle">Ingresa tu correo electrónico</p>
                </div>

                <p class="info-text">
                    Te enviaremos un enlace para restablecer tu contraseña. Debe coincidir con el correo registrado en el sistema.
                </p>

                <div class="alert-container <?= $error || $success ? 'visible' : '' ?>">
                    <?php if ($error): ?>
                    <div class="alert-glass alert-danger" role="alert">
                        <i class="bi bi-exclamation-circle"></i>
                        <span><?= htmlspecialchars($error) ?></span>
                    </div>
                    <?php elseif ($success): ?>
                    <div class="alert-glass" role="alert">
                        <i class="bi bi-check-circle"></i>
                        <span><?= htmlspecialchars($success) ?></span>
                    </div>
                    <?php endif; ?>
                </div>

                <form action="backend/password_reset_request.php" method="POST" novalidate id="recoveryForm">
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'] ?? csrf_token()) ?>">

                    <div class="input-group">
                        <div class="input-wrapper">
                            <input type="email" name="email" id="email"
                                class="form-input" maxlength="50" required
                                placeholder=" " inputmode="email" autocomplete="email">
                            <label for="email" class="float-label">
                                <i class="bi bi-envelope"></i> Correo electrónico
                            </label>
                            <span class="input-icon"><i class="bi bi-envelope"></i></span>
                        </div>
                        <div class="char-count" id="charCount">0/50</div>
                    </div>

                    <button type="submit" class="btn-login" id="submitBtn">
                        <span>Enviar enlace de recuperación</span>
                        <div class="spinner" id="loginSpinner">
                            <div class="spinner-ring"></div>
                        </div>
                    </button>

                    <a href="index.php" class="btn-back">
                        <i class="bi bi-arrow-left"></i> Volver al inicio de sesión
                    </a>
                </form>
            </div>
            <p class="copyright">&copy; <?= date('Y') ?> Agora — Todos los derechos reservados</p>
        </main>
    </div>

    <div class="loading-overlay" id="loadingOverlay">
        <div class="loading-content">
            <div class="loading-ring"></div>
            <p>Enviando solicitud...</p>
        </div>
    </div>

    <script>
    const emailInput = document.getElementById('email');
    const charCount = document.getElementById('charCount');
    const form = document.getElementById('recoveryForm');
    const submitBtn = document.getElementById('submitBtn');
    const overlay = document.getElementById('loadingOverlay');

    emailInput.addEventListener('input', () => {
        const len = emailInput.value.length;
        charCount.textContent = len + '/50';
        charCount.classList.toggle('warning', len > 45);
    });

    form.addEventListener('submit', (e) => {
        emailInput.classList.remove('is-invalid');
        let valid = true;

        if (!emailInput.value.match(/^[^@\s]+@[^@\s]+\.[^@\s]+$/)) {
            emailInput.classList.add('is-invalid');
            valid = false;
        }
        if (emailInput.value.length > 50) {
            emailInput.classList.add('is-invalid');
            valid = false;
        }

        if (!valid) {
            e.preventDefault();
            return;
        }

        submitBtn.classList.add('loading');
        submitBtn.disabled = true;
        overlay.classList.add('active');
    });
    </script>
</body>
</html>
