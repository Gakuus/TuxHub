<?php
require_once __DIR__ . '/backend/helpers.php';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Correo Enviado — Agora</title>
    <link rel="preload" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" as="style">
    <link rel="preload" href="css/login.css" as="style">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="css/login.css" rel="stylesheet">
    <meta name="referrer" content="strict-origin-when-cross-origin">
    <meta name="theme-color" content="#667eea">
    <meta name="description" content="Correo enviado - Agora">
    <style>
        .login-card {
            max-width: 460px;
            margin: 0 auto;
            text-align: center;
        }
        .success-icon {
            font-size: 3.5rem;
            color: #34d399;
            margin-bottom: 0.5rem;
        }
        .success-title {
            font-size: 1.5rem;
            font-weight: 700;
            color: #fff;
            margin: 0 0 0.5rem;
        }
        .success-text {
            font-size: 0.9rem;
            color: rgba(255,255,255,0.6);
            line-height: 1.6;
            margin-bottom: 1.5rem;
        }
        .info-card {
            background: rgba(255,255,255,0.06);
            border: 1px solid rgba(255,255,255,0.1);
            border-radius: 14px;
            padding: 1rem 1.25rem;
            margin-bottom: 1.5rem;
            text-align: left;
        }
        .info-card-title {
            font-size: 0.8rem;
            font-weight: 600;
            color: rgba(255,255,255,0.5);
            text-transform: uppercase;
            letter-spacing: 0.05em;
            margin-bottom: 0.5rem;
        }
        .info-card ul {
            list-style: none;
            padding: 0;
            margin: 0;
        }
        .info-card ul li {
            font-size: 0.85rem;
            color: rgba(255,255,255,0.55);
            padding: 0.25rem 0;
            padding-left: 1.25rem;
            position: relative;
        }
        .info-card ul li::before {
            content: '•';
            position: absolute;
            left: 0;
            color: rgba(255,255,255,0.2);
        }
        .actions {
            display: flex;
            flex-direction: column;
            gap: 0.75rem;
        }
        .btn-primary-custom {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
            width: 100%;
            height: 50px;
            padding: 0 1.5rem;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border: none;
            border-radius: 14px;
            font-family: inherit;
            font-size: 1rem;
            font-weight: 600;
            color: #fff;
            cursor: pointer;
            transition: all 0.3s ease;
            text-decoration: none;
        }
        .btn-primary-custom:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 30px rgba(102,126,234,0.35);
            color: #fff;
            text-decoration: none;
        }
        .btn-secondary-custom {
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
        .btn-secondary-custom:hover {
            background: rgba(255,255,255,0.14);
            color: #fff;
            text-decoration: none;
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
                <div class="success-icon">
                    <i class="bi bi-check-circle-fill"></i>
                </div>
                <h2 class="success-title">Correo enviado</h2>
                <p class="success-text">
                    Si el correo electrónico existe en nuestro sistema, recibirás un enlace para restablecer tu contraseña en los próximos minutos.
                </p>

                <div class="info-card">
                    <div class="info-card-title">¿No recibiste el correo?</div>
                    <ul>
                        <li>Revisa tu carpeta de spam o correo no deseado</li>
                        <li>Verifica que escribiste correctamente tu dirección de email</li>
                        <li>Espera unos minutos e intenta nuevamente</li>
                    </ul>
                </div>

                <div class="actions">
                    <a href="index.php" class="btn-primary-custom">
                        <i class="bi bi-box-arrow-in-right"></i> Volver al inicio de sesión
                    </a>
                    <a href="password_reset_request.php" class="btn-secondary-custom">
                        <i class="bi bi-arrow-clockwise"></i> Intentar con otro correo
                    </a>
                </div>
            </div>
            <p class="copyright">&copy; <?= date('Y') ?> Agora — Todos los derechos reservados</p>
        </main>
    </div>
</body>
</html>
