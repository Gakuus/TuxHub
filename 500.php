<?php http_response_code(500); ?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Error del servidor — Agora</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="css/login.css" rel="stylesheet">
    <meta name="robots" content="noindex, nofollow">
    <style>
        .error-container { text-align: center; padding: 2rem; }
        .error-code { font-size: 6rem; font-weight: 800; background: linear-gradient(135deg, #f87171, #dc2626); -webkit-background-clip: text; -webkit-text-fill-color: transparent; line-height: 1; }
        .error-title { font-size: 1.5rem; font-weight: 700; color: #fff; margin: 1rem 0 0.5rem; }
        .error-text { color: rgba(255,255,255,0.6); margin-bottom: 2rem; }
        .btn-back { display: inline-flex; align-items: center; gap: 0.5rem; padding: 0.75rem 2rem; background: linear-gradient(135deg, #667eea, #764ba2); border: none; border-radius: 14px; color: #fff; text-decoration: none; font-weight: 600; transition: all 0.3s ease; }
        .btn-back:hover { transform: translateY(-2px); box-shadow: 0 8px 30px rgba(102,126,234,0.35); color: #fff; }
    </style>
</head>
<body>
    <div class="bg-orbs" aria-hidden="true">
        <div class="orb orb-1"></div><div class="orb orb-2"></div><div class="orb orb-3"></div><div class="orb orb-4"></div>
    </div>
    <div class="login-wrapper">
        <main class="login-main">
            <div class="login-card error-container">
                <div class="error-code">500</div>
                <h2 class="error-title">Error del servidor</h2>
                <p class="error-text">Ocurrió un error inesperado. Nuestro equipo ha sido notificado.</p>
                <a href="dashboard.php" class="btn-back"><i class="bi bi-arrow-clockwise"></i> Reintentar</a>
                <p class="copyright" style="margin-top:2rem">&copy; <?= date('Y') ?> Agora</p>
            </div>
        </main>
    </div>
</body>
</html>
