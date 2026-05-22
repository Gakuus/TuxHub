<?php
require_once __DIR__ . '/helpers.php';
require_once __DIR__ . '/db_connection.php';

$token = $_GET['token'] ?? '';
$error = $_GET['error'] ?? null;
$token_valido = false;
$token_user_id = null;

// Validar token antes de mostrar el formulario
if ($token) {
    $stmt = $conn->prepare("SELECT user_id FROM password_resets WHERE token = ? AND expira > NOW()");
    $stmt->bind_param("s", $token);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows === 1) {
        $token_valido = true;
        $token_user_id = $result->fetch_assoc()['user_id'];
    }
    $stmt->close();
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nueva contraseña — Agora</title>
    <link rel="preload" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" as="style">
    <link rel="preload" href="../css/login.css" as="style">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="../css/login.css" rel="stylesheet">
    <meta name="referrer" content="strict-origin-when-cross-origin">
    <meta name="theme-color" content="#667eea">
    <meta name="description" content="Restablecer contraseña - Agora">
    <style>
        .login-card {
            max-width: 460px;
            margin: 0 auto;
        }
        .password-requirements {
            font-size: 0.8rem;
            margin: 0.5rem 0 0;
            padding: 0;
        }
        .password-requirements li {
            list-style: none;
            padding: 0.2rem 0;
            padding-left: 1.25rem;
            position: relative;
            font-size: 0.8rem;
            color: rgba(255,255,255,0.4);
        }
        .password-requirements li::before {
            content: '○';
            position: absolute;
            left: 0;
            color: rgba(255,255,255,0.2);
        }
        .password-requirements li.valid {
            color: #34d399;
        }
        .password-requirements li.valid::before {
            content: '✓';
            color: #34d399;
        }
        .password-requirements li.invalid {
            color: rgba(255,255,255,0.4);
        }
        .strength-bar {
            height: 4px;
            border-radius: 4px;
            background: rgba(255,255,255,0.1);
            margin-top: 0.5rem;
            overflow: hidden;
        }
        .strength-bar-fill {
            height: 100%;
            width: 0%;
            border-radius: 4px;
            transition: width 0.3s ease, background-color 0.3s ease;
        }
        .strength-label {
            font-size: 0.7rem;
            color: rgba(255,255,255,0.35);
            text-align: center;
            margin-top: 0.25rem;
        }
        .btn-login:disabled {
            opacity: 0.4;
            cursor: not-allowed;
            transform: none;
            box-shadow: none;
        }
        .token-error {
            text-align: center;
        }
        .token-error .error-icon {
            font-size: 3rem;
            color: #f87171;
            margin-bottom: 1rem;
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
                <?php if (!$token_valido): ?>
                    <div class="login-header">
                        <img src="../img/Logo.png" alt="Agora" class="login-logo" width="80" height="80">
                        <h2 class="login-title">Enlace inválido</h2>
                        <p class="login-subtitle">El enlace de recuperación no es válido o ha expirado</p>
                    </div>
                    <div class="token-error">
                        <div class="error-icon"><i class="bi bi-shield-exclamation"></i></div>
                        <p style="color:rgba(255,255,255,0.5);font-size:0.85rem;margin-bottom:1.5rem;">
                            Los enlaces de recuperación expiran después de 1 hora. Solicita uno nuevo para continuar.
                        </p>
                        <a href="../password_reset_request.php" class="btn-login" style="text-decoration:none;display:inline-flex;">
                            <i class="bi bi-arrow-clockwise"></i> Solicitar nuevo enlace
                        </a>
                    </div>
                <?php else: ?>
                    <div class="login-header">
                        <img src="../img/Logo.png" alt="Agora" class="login-logo" width="80" height="80">
                        <h2 class="login-title">Nueva contraseña</h2>
                        <p class="login-subtitle">Debe cumplir con los requisitos de seguridad</p>
                    </div>

                    <div class="alert-container <?= $error ? 'visible' : '' ?>">
                        <?php if ($error): ?>
                        <div class="alert-glass alert-danger" role="alert">
                            <i class="bi bi-exclamation-circle"></i>
                            <span><?= htmlspecialchars($error) ?></span>
                        </div>
                        <?php endif; ?>
                    </div>

                    <form action="password_update.php" method="POST" novalidate id="resetForm">
                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'] ?? csrf_token()) ?>">
                        <input type="hidden" name="token" value="<?= htmlspecialchars($token) ?>">

                        <div class="input-group">
                            <div class="input-wrapper">
                                <input type="password" name="password" id="password"
                                    class="form-input" minlength="8" maxlength="24" required
                                    placeholder=" " autocomplete="new-password">
                                <label for="password" class="float-label">
                                    <i class="bi bi-lock"></i> Nueva contraseña
                                </label>
                                <span class="input-icon"><i class="bi bi-lock"></i></span>
                                <button type="button" class="toggle-password" id="togglePassword" tabindex="-1" aria-label="Mostrar contraseña">
                                    <i class="bi bi-eye" id="eyeIcon"></i>
                                </button>
                            </div>
                        </div>

                        <div class="strength-bar">
                            <div class="strength-bar-fill" id="strengthFill"></div>
                        </div>
                        <div class="strength-label" id="strengthLabel">Nivel de seguridad</div>

                        <ul class="password-requirements" id="requirements">
                            <li id="req-length">8 a 24 caracteres</li>
                            <li id="req-uppercase">Una letra mayúscula</li>
                            <li id="req-lowercase">Una letra minúscula</li>
                            <li id="req-number">Un número</li>
                            <li id="req-special">Un carácter especial (!@#$%^&*?)</li>
                        </ul>

                        <button type="submit" class="btn-login" disabled id="submitBtn">
                            <span>Actualizar contraseña</span>
                            <div class="spinner" id="loginSpinner">
                                <div class="spinner-ring"></div>
                            </div>
                        </button>

                        <a href="../index.php" class="btn-back" style="display:inline-flex;align-items:center;justify-content:center;gap:0.5rem;width:100%;height:50px;padding:0 1.5rem;background:rgba(255,255,255,0.08);border:1.5px solid rgba(255,255,255,0.15);border-radius:14px;font-family:inherit;font-size:1rem;font-weight:500;color:rgba(255,255,255,0.7);cursor:pointer;transition:all 0.3s ease;text-decoration:none;margin-top:0.75rem;">
                            <i class="bi bi-arrow-left"></i> Volver al inicio
                        </a>
                    </form>
                <?php endif; ?>
            </div>
            <p class="copyright">&copy; <?= date('Y') ?> Agora — Todos los derechos reservados</p>
        </main>
    </div>

    <?php if ($token_valido): ?>
    <div class="loading-overlay" id="loadingOverlay">
        <div class="loading-content">
            <div class="loading-ring"></div>
            <p>Actualizando contraseña...</p>
        </div>
    </div>

    <script>
    const passwordInput = document.getElementById('password');
    const submitBtn = document.getElementById('submitBtn');
    const strengthFill = document.getElementById('strengthFill');
    const strengthLabel = document.getElementById('strengthLabel');
    const overlay = document.getElementById('loadingOverlay');
    const form = document.getElementById('resetForm');

    const reqs = {
        length: document.getElementById('req-length'),
        uppercase: document.getElementById('req-uppercase'),
        lowercase: document.getElementById('req-lowercase'),
        number: document.getElementById('req-number'),
        special: document.getElementById('req-special')
    };

    const labels = ['Muy débil', 'Débil', 'Regular', 'Buena', 'Fuerte'];

    passwordInput.addEventListener('input', () => {
        const v = passwordInput.value;
        const checks = {
            length: v.length >= 8 && v.length <= 24,
            uppercase: /[A-Z]/.test(v),
            lowercase: /[a-z]/.test(v),
            number: /\d/.test(v),
            special: /[!@#$%^&*?]/.test(v)
        };

        let count = 0;
        for (const [key, ok] of Object.entries(checks)) {
            reqs[key].classList.toggle('valid', ok);
            reqs[key].classList.toggle('invalid', !ok);
            if (ok) count++;
        }

        const pct = count * 20;
        strengthFill.style.width = pct + '%';

        const colors = ['#ef4444', '#f97316', '#eab308', '#22c55e', '#22c55e'];
        strengthFill.style.backgroundColor = colors[count - 1] || '#ef4444';
        strengthLabel.textContent = labels[count - 1] || 'Nivel de seguridad';

        submitBtn.disabled = count < 5;
    });

    document.getElementById('togglePassword').addEventListener('click', () => {
        const type = passwordInput.type === 'password' ? 'text' : 'password';
        passwordInput.type = type;
        document.getElementById('eyeIcon').className = type === 'password' ? 'bi bi-eye' : 'bi bi-eye-slash';
    });

    form.addEventListener('submit', () => {
        submitBtn.classList.add('loading');
        submitBtn.disabled = true;
        overlay.classList.add('active');
    });
    </script>
    <?php endif; ?>
</body>
</html>
