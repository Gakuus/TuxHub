<?php
require_once __DIR__ . '/backend/helpers.php';

header("X-Frame-Options: DENY");
header("X-Content-Type-Options: nosniff");
header("Referrer-Policy: strict-origin-when-cross-origin");
header("Content-Security-Policy: "
    . "default-src 'self'; "
    . "script-src 'self' https://cdn.jsdelivr.net https://www.google.com https://www.gstatic.com 'unsafe-inline'; "
    . "style-src 'self' https://cdn.jsdelivr.net https://fonts.googleapis.com 'unsafe-inline'; "
    . "font-src 'self' https://cdn.jsdelivr.net https://fonts.gstatic.com; "
    . "img-src 'self' data: https:; "
    . "connect-src 'self' https://cdn.jsdelivr.net https://www.google.com; "
    . "frame-src 'self' https://www.google.com;");

/* ============================================
   🔐 CONTROL DE SESIÓN E INACTIVIDAD MEJORADO
   ============================================ */
$timeout = 900;

if (isset($_SESSION['last_activity'])) {
    $inactividad = time() - $_SESSION['last_activity'];
    if ($inactividad > $timeout) {
        session_unset();
        session_destroy();
        header("Location: index.php?error=inactividad");
        exit();
    }
}

$_SESSION['last_activity'] = time();

if (!isset($_SESSION['user_id'])) {
    header("Location: index.php?error=requerido");
    exit();
}

// Validación adicional de datos de sesión
if (!isset($_SESSION['rol']) || !isset($_SESSION['user_name'])) {
    session_unset();
    session_destroy();
    header("Location: index.php?error=sesion_invalida");
    exit();
}

/* ============================================
   VARIABLES DE SESIÓN SANITIZADAS
   ============================================ */
$rol = htmlspecialchars($_SESSION['rol'], ENT_QUOTES, 'UTF-8') ?? "Invitado";
$rol_lower = strtolower($rol);
$nombre = htmlspecialchars($_SESSION['user_name'], ENT_QUOTES, 'UTF-8') ?? "Usuario";
$user_id = $_SESSION['user_id'] ?? null;

// Sanitizar parámetro page
$page = isset($_GET['page']) ? htmlspecialchars($_GET['page'], ENT_QUOTES, 'UTF-8') : "home";

// Validar conexión a base de datos (MySQLi)
require_once 'backend/db_connection.php';

// Verificar si la conexión está activa
if (!$conn || !$conn->ping()) {
    error_log("Error de conexión a la base de datos en dashboard");
    header("Location: index.php?error=db_conexion");
    exit();
}

// Mapa de CSS específico por página
$page_css_map = [
    'home' => 'home.css',
    'salones' => 'salones.css',
    'recursos' => 'recursos.css',
    'horarios' => 'horarios.css',
    'agregar_materias' => 'materias.css',
    'gestionar_contenido' => 'gestionar_contenido.css',
    'registrar' => 'register.css'
];
$page_css = $page_css_map[$page] ?? null;
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Dashboard de gestión académica">
    <meta name="robots" content="noindex, nofollow">
    <meta name="csrf-token" content="<?= csrf_token() ?>">
    <title>Dashboard - Agora</title>

    <!-- CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link rel="stylesheet" href="css/variables.css">
    <link rel="stylesheet" href="css/components.css">
    <link rel="stylesheet" href="css/dashboard.css">
    <?php if ($page_css): ?>
    <link rel="stylesheet" href="css/<?= $page_css ?>">
    <?php endif; ?>
    <link rel="stylesheet" href="css/chat.css">
    <link rel="icon" href="img/Logo.png" type="image/x-icon">
</head>
<body>

<div id="wrapper">
    <!-- Sidebar -->
    <nav class="sidebar" id="sidebarMenu">
        <!-- Brand -->
        <div class="sidebar-brand">
            <img src="img/Logo.png" alt="Agora" class="sidebar-logo">
            <div class="sidebar-brand-text">
                <span class="sidebar-brand-name">Agora</span>
                <span class="sidebar-brand-sub">Gestión del Instituto</span>
            </div>
        </div>

        <!-- User profile -->
        <div class="sidebar-user">
            <div class="sidebar-avatar"><?= strtoupper(substr($nombre, 0, 1)) ?></div>
            <div class="sidebar-user-info">
                <span class="sidebar-user-name"><?= htmlspecialchars($nombre) ?></span>
                <span class="sidebar-user-role role-<?= $rol_lower ?>">
                    <i class="bi <?= $rol_lower === 'admin' ? 'bi-shield-fill-check' : ($rol_lower === 'profesor' ? 'bi-person-vcard-fill' : 'bi-mortarboard-fill') ?>"></i>
                    <?= $rol ?>
                </span>
            </div>
        </div>

        <!-- Navigation -->
        <div class="sidebar-nav">
            <div class="nav-section">
                <span class="nav-section-title">Principal</span>
                <a class="nav-link <?= $page == 'home' ? 'active' : '' ?>" href="dashboard.php?page=home">
                    <i class="bi bi-grid-fill"></i>
                    <span class="nav-text">Dashboard</span>
                </a>
            </div>

            <div class="nav-section">
                <span class="nav-section-title">Académico</span>
                <a class="nav-link <?= $page == 'horarios' ? 'active' : '' ?>" href="dashboard.php?page=horarios">
                    <i class="bi bi-calendar-week-fill"></i>
                    <span class="nav-text">Horarios</span>
                </a>
                <a class="nav-link <?= $page == 'salones' ? 'active' : '' ?>" href="dashboard.php?page=salones">
                    <i class="bi bi-door-open-fill"></i>
                    <span class="nav-text">Salones</span>
                </a>
                <a class="nav-link <?= $page == 'recursos' ? 'active' : '' ?>" href="dashboard.php?page=recursos">
                    <i class="bi bi-box-seam-fill"></i>
                    <span class="nav-text">Recursos</span>
                </a>
                <a class="nav-link <?= $page == 'profesores' ? 'active' : '' ?>" href="dashboard.php?page=profesores">
                    <i class="bi bi-person-badge"></i>
                    <span class="nav-text">Profesores</span>
                </a>
            </div>

            <?php if ($rol_lower === "admin"): ?>
            <div class="nav-section">
                <span class="nav-section-title">Administración</span>
                <a class="nav-link <?= $page == 'grupos' ? 'active' : '' ?>" href="dashboard.php?page=grupos">
                    <i class="bi bi-collection-fill"></i>
                    <span class="nav-text">Grupos</span>
                </a>
                <a class="nav-link <?= $page == 'agregar_materias' ? 'active' : '' ?>" href="dashboard.php?page=agregar_materias">
                    <i class="bi bi-book"></i>
                    <span class="nav-text">Materias</span>
                </a>
                <a class="nav-link <?= $page == 'registrar' ? 'active' : '' ?>" href="dashboard.php?page=registrar">
                    <i class="bi bi-person-plus-fill"></i>
                    <span class="nav-text">Usuarios</span>
                </a>
                <a class="nav-link <?= $page == 'gestionar_contenido' && ($_GET['tipo'] ?? '') == 'noticias' ? 'active' : '' ?>"
                   href="dashboard.php?page=gestionar_contenido&tipo=noticias">
                    <i class="bi bi-newspaper"></i>
                    <span class="nav-text">Noticias</span>
                </a>
                <a class="nav-link <?= $page == 'gestionar_contenido' && ($_GET['tipo'] ?? '') == 'avisos' ? 'active' : '' ?>"
                   href="dashboard.php?page=gestionar_contenido&tipo=avisos">
                    <i class="bi bi-exclamation-triangle"></i>
                    <span class="nav-text">Avisos</span>
                </a>
            </div>

            <div class="nav-section">
                <span class="nav-section-title">Configuración</span>
                <a class="nav-link <?= $page == 'cargar_horarios' ? 'active' : '' ?>" href="dashboard.php?page=cargar_horarios">
                    <i class="bi bi-plus-circle"></i>
                    <span class="nav-text">Cargar Horarios</span>
                </a>
                <a class="nav-link <?= $page == 'asignar_grupos' ? 'active' : '' ?>" href="dashboard.php?page=asignar_grupos">
                    <i class="bi bi-diagram-3-fill"></i>
                    <span class="nav-text">Asignar Grupos</span>
                </a>
                <a class="nav-link <?= $page == 'agregar_salon' ? 'active' : '' ?>" href="dashboard.php?page=agregar_salon">
                    <i class="bi bi-building-add"></i>
                    <span class="nav-text">Agregar Salón</span>
                </a>
                <a class="nav-link <?= $page == 'agregar_recursos' ? 'active' : '' ?>" href="dashboard.php?page=agregar_recursos">
                    <i class="bi bi-plus-square"></i>
                    <span class="nav-text">Agregar Recurso</span>
                </a>
            </div>
            <?php elseif ($rol_lower === "profesor"): ?>
            <div class="nav-section">
                <span class="nav-section-title">Docente</span>
                <a class="nav-link <?= $page == 'registrar' ? 'active' : '' ?>" href="dashboard.php?page=registrar">
                    <i class="bi bi-person-plus-fill"></i>
                    <span class="nav-text">Registrar Alumno</span>
                </a>
            </div>
            <?php endif; ?>
        </div>

        <!-- Bottom actions -->
        <div class="sidebar-footer">
            <button class="sidebar-footer-btn" id="themeToggle" title="Cambiar tema">
                <i class="bi bi-moon-fill"></i>
                <span>Modo oscuro</span>
            </button>
            <a href="backend/logout.php" class="sidebar-footer-btn sidebar-logout" title="Cerrar sesión">
                <i class="bi bi-box-arrow-right"></i>
                <span>Cerrar sesión</span>
            </a>
        </div>
    </nav>

    <div class="sidebar-overlay" id="sidebarOverlay"></div>

    <!-- MAIN CONTENT -->
    <div class="main-content">
        <header class="main-header">
            <div class="container-fluid">
                <div class="row align-items-center">
                    <div class="col-auto">
                        <button class="toggle-btn" id="toggleSidebar" aria-label="Toggle sidebar">
                            <i class="bi bi-list"></i>
                        </button>
                    </div>
                    <div class="col">
                        <h2 class="welcome-text">
                            Bienvenido, <span class="fw-bold"><?= $nombre ?></span>
                            <span class="role-indicator role-<?= $rol_lower ?>"><?= $rol ?></span>
                        </h2>
                    </div>
                    <div class="col-auto d-flex align-items-center gap-2">
                        <button class="header-btn" id="headerThemeToggle" title="Cambiar tema">
                            <i class="bi bi-moon-fill"></i>
                        </button>
                        <a href="backend/logout.php" class="header-btn header-logout" title="Cerrar sesión">
                            <i class="bi bi-box-arrow-right"></i>
                        </a>
                    </div>
                </div>
            </div>
        </header>

        <main class="p-3">
            <div class="container-fluid">
                <div class="responsive-grid">
                    <div class="card">
                        <div class="card-body">
                            <?php
                            // Definir páginas permitidas por rol
                            $allowed_pages = ['home', 'profesores', 'salones', 'horarios', 'recursos'];

                            if ($rol_lower === 'admin') {
                                $allowed_pages = array_merge($allowed_pages, [
                                    'registrar', 'gestionar_contenido', 'cargar_horarios', 'asignar_grupos', 
                                    'agregar_salon', 'agregar_recursos', 'agregar_materias', 'grupos'
                                ]);
                            } elseif ($rol_lower === 'profesor') {
                                $allowed_pages[] = 'registrar';
                            }

                            // Incluir página solicitada con validación
                            if (in_array($page, $allowed_pages)) {
                                $page_file = match($page) {
                                    'registrar' => 'registrar_usuario.php',
                                    'gestionar_contenido' => 'gestionar_contenido.php',
                                    'cargar_horarios' => 'cargar_horarios.php',
                                    'asignar_grupos' => 'asignar_grupos.php',
                                    'agregar_salon' => 'agregar_salon.php',
                                    'agregar_recursos' => 'agregar_recurso.php',
                                    'agregar_materias' => 'agregar_materias.php',
                                    'grupos' => 'grupo.php',
                                    'recursos' => 'recursos.php',
                                    default => $page . '.php'
                                };

                                $file_path = "pages/{$page_file}";
                                if (file_exists($file_path) && is_file($file_path)) {
                                    include $file_path;
                                } else {
                                    echo '<div class="alert alert-danger">Error: archivo de página no encontrado.</div>';
                                    error_log("Archivo no encontrado: " . $file_path);
                                }
                            } else {
                                echo '<div class="alert alert-warning">Página no encontrada o sin permisos.</div>';
                            }
                            ?>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>
</div>

<!-- Chat Widget -->
<div class="chat-container" id="chatContainer">
    <div class="chat-header">
        <div class="chat-header-title">
            <svg viewBox="0 0 24 24"><path d="M12 2C6.48 2 2 6.48 2 12c0 1.88.54 3.63 1.48 5.12L2 22l5.12-1.48C8.37 21.46 10.12 22 12 22c5.52 0 10-4.48 10-10S17.52 2 12 2z"/></svg>
            Asistente Agora
        </div>
        <button class="chat-header-close" id="chatCloseBtn" aria-label="Cerrar chat">
            <svg viewBox="0 0 24 24"><path d="M19 6.41L17.59 5 12 10.59 6.41 5 5 6.41 10.59 12 5 17.59 6.41 19 12 13.41 17.59 19 19 17.59 13.41 12z"/></svg>
        </button>
    </div>
    <div class="chat-messages" id="chatMessages">
        <div class="chat-greeting">
            <svg viewBox="0 0 24 24"><path d="M12 2C6.48 2 2 6.48 2 12c0 1.88.54 3.63 1.48 5.12L2 22l5.12-1.48C8.37 21.46 10.12 22 12 22c5.52 0 10-4.48 10-10S17.52 2 12 2z"/></svg>
            <div>¡Hola! Soy el asistente del Sistema Agora.<br>Pregúntame sobre el proyecto, su arquitectura, seguridad o cómo usar los módulos.</div>
        </div>
    </div>
    <div class="chat-input-area">
        <input type="text" id="chatInput" placeholder="Escribe tu pregunta..." maxlength="2000">
        <button id="chatSendBtn">Enviar</button>
    </div>
</div>
<button class="chat-btn" id="chatToggleBtn" aria-label="Abrir chat">
    <svg viewBox="0 0 24 24"><path d="M12 2C6.48 2 2 6.48 2 12c0 1.88.54 3.63 1.48 5.12L2 22l5.12-1.48C8.37 21.46 10.12 22 12 22c5.52 0 10-4.48 10-10S17.52 2 12 2z"/></svg>
</button>

<!-- JavaScript -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="assets/dashboard.js"></script>
<script src="assets/ui.js"></script>
<script src="assets/chat.js"></script>
<script>
// Auto-convert server-side alerts to ToastSystem notifications
document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('.alert-success, .alert-danger, .alert-warning, .alert-info').forEach(function (el) {
        var msg = '';
        el.childNodes.forEach(function (node) {
            if (node.nodeType === 3) {
                msg += node.textContent;
            }
        });
        msg = msg.replace(/[✅❌⚠️×]/g, '').trim();
        if (!msg) return;
        var type = 'info';
        if (el.classList.contains('alert-success')) type = 'success';
        else if (el.classList.contains('alert-danger')) type = 'error';
        else if (el.classList.contains('alert-warning')) type = 'warning';
        var titles = { success: 'Operación exitosa', error: 'Error', warning: 'Aviso', info: 'Información' };
        ToastSystem.show({ type: type, title: titles[type], message: msg, duration: 4000 });
        el.style.display = 'none';
    });
});
</script>
</body>
</html>
