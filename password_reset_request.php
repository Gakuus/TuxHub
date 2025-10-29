<?php
require_once __DIR__ . '/backend/db_connection.php';
session_start();
$error = $_GET['error'] ?? null;
$success = $_GET['success'] ?? null;
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Recuperar contraseña - Agora</title>
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
    
        /* Capa de difuminado del fondo */
        body::before {
            content: "";
            position: absolute;
            inset: 0;
            background: rgba(0, 0, 0, 0.4); /* oscurece un poco el fondo */
            backdrop-filter: blur(8px); /* efecto difuminado */
            z-index: 0;
        }
    
    
    
    .card {
        border: none;
        border-radius: 20px;
        box-shadow: 0 10px 30px rgba(0,0,0,0.15);
        width: 100%;
        max-width: 420px;
    }
    .btn-primary {
        background-color: #007bff;
        border: none;
    }
    .btn-primary:hover {
        background-color: #0056b3;
    }
    .btn-secondary {
        background-color: #6c757d;
        border: none;
    }
    .btn-secondary:hover {
        background-color: #565e64;
    }
</style>
</head>
<body>
<div class="card p-4">
    <h3 class="text-center mb-4 fw-bold text-primary">🔑 Recuperar contraseña</h3>

    <?php if ($error): ?>
        <div class="alert alert-danger text-center"><?= htmlspecialchars($error) ?></div>
    <?php elseif ($success): ?>
        <div class="alert alert-success text-center"><?= htmlspecialchars($success) ?></div>
    <?php endif; ?>

    <form action="backend/password_reset_request.php" method="POST" id="recoveryForm" novalidate>
        <div class="mb-3">
            <label for="email" class="form-label">Correo electrónico</label>
            <input type="email" name="email" id="email" class="form-control" placeholder="ejemplo@correo.com" required>
            <div class="invalid-feedback">Por favor, ingresa un correo electrónico válido.</div>
        </div>
        <button type="submit" class="btn btn-primary w-100 mb-2">Enviar enlace de recuperación</button>
        <a href="index.php" class="btn btn-secondary w-100">⬅️ Volver al login</a>
    </form>
</div>

<script>
// Validación en frontend
const form = document.getElementById('recoveryForm');
const emailInput = document.getElementById('email');

form.addEventListener('submit', (e) => {
    if (!emailInput.value.match(/^[^@\s]+@[^@\s]+\.[^@\s]+$/)) {
        emailInput.classList.add('is-invalid');
        e.preventDefault();
    } else {
        emailInput.classList.remove('is-invalid');
    }
});
</script>
</body>
</html>
