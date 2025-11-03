<?php
session_start();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Correo Enviado - Agora</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background: url('img/itsp.jpeg') no-repeat center center fixed;
            background-size: cover;
            position: relative;
            overflow: hidden;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        body::before {
            content: "";
            position: absolute;
            inset: 0;
            background: rgba(0, 0, 0, 0.4);
            backdrop-filter: blur(8px);
            z-index: 0;
        }
        
        .card {
            border: none;
            border-radius: 20px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.15);
            width: 100%;
            max-width: 450px;
            position: relative;
            z-index: 1;
        }
        
        .success-icon {
            font-size: 4rem;
            color: #28a745;
            margin-bottom: 1rem;
        }
        
        .btn-primary {
            background-color: #007bff;
            border: none;
        }
        
        .btn-primary:hover {
            background-color: #0056b3;
        }
    </style>
</head>
<body>
    <div class="card p-4 text-center">
        <div class="success-icon">✅</div>
        <h3 class="text-center mb-3 fw-bold text-success">¡Correo Enviado!</h3>
        
        <p class="mb-4">Si el correo electrónico existe en nuestro sistema, recibirás un enlace para restablecer tu contraseña en los próximos minutos.</p>
        
        <div class="alert alert-info">
            <small>
                <strong>¿No recibiste el correo?</strong><br>
                • Revisa tu carpeta de spam<br>
                • Verifica que escribiste correctamente tu email<br>
                • Espera unos minutos e intenta nuevamente
            </small>
        </div>
        
        <div class="d-grid gap-2">
            <a href="index.php" class="btn btn-primary">Volver al Inicio de Sesión</a>
            <a href="password_reset.php" class="btn btn-outline-secondary">Intentar con otro correo</a>
        </div>
    </div>
</body>
</html>