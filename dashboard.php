<?php
session_start();

/* ============================================
   🔐 CONTROL DE SESIÓN E INACTIVIDAD
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

/* ============================================
   VARIABLES DE SESIÓN
   ============================================ */
$rol = $_SESSION['rol'] ?? "Invitado";
$rol_lower = strtolower($rol);
$nombre = $_SESSION['user_name'] ?? "Usuario";
$user_id = $_SESSION['user_id'] ?? null;
$page = $_GET['page'] ?? "home";

require_once 'backend/db_connection.php';
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Dashboard - Agora</title>

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
<link rel="stylesheet" href="css/styles.css">

<style>
/* ======= BASE GENERAL ======= */
body {
    background-color: #f4f6f8;
    overflow-x: hidden;
    font-family: "Inter", system-ui, sans-serif;
}

/* ======= SIDEBAR ======= */
.sidebar {
    width: 260px;
    background-color: #212529;
    transition: transform 0.3s ease, width 0.3s ease;
    height: 100vh;
    overflow-y: auto;
    position: fixed;
    left: 0;
    top: 0;
    z-index: 1050;
}

.sidebar-header img {
    height: auto;
    width: 140px;
    max-width: 80%;
}

.nav-link {
    color: #ccc;
    padding: 10px 15px;
    border-radius: 8px;
    transition: all 0.2s;
    font-size: clamp(0.9rem, 1vw, 1rem);
}

.nav-link.active,
.nav-link:hover {
    background-color: #0d6efd;
    color: #fff !important;
}

/* ======= MAIN CONTENT ======= */
.main-content {
    margin-left: 260px;
    transition: margin-left 0.3s ease;
    min-height: 100vh;
    padding-bottom: 2rem;
}

.main-header {
    position: sticky;
    top: 0;
    z-index: 1000;
    background: #fff;
}

/* ======= GRID DE TARJETAS ======= */
.card {
    border: none;
    border-radius: 14px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.08);
    background: #fff;
    transition: transform 0.2s ease, box-shadow 0.2s ease;
}
.card:hover {
    transform: translateY(-3px);
    box-shadow: 0 4px 12px rgba(0,0,0,0.12);
}

.card-body {
    padding: clamp(1rem, 2vw, 1.5rem);
}

/* GRID FLEXIBLE → SE ADAPTA A CUALQUIER PANTALLA */
.responsive-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(min(100%, 300px), 1fr));
    gap: clamp(0.8rem, 2vw, 1.2rem);
}

/* ======= OVERLAY MÓVIL ======= */
.sidebar-overlay {
    display: none;
    background: rgba(0,0,0,0.5);
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    z-index: 1040;
}

/* ======= VERSIÓN MÓVIL ======= */
@media (max-width: 992px) {
    .sidebar {
        transform: translateX(-100%);
    }

    .sidebar.show {
        transform: translateX(0);
    }

    .main-content {
        margin-left: 0;
    }

    .toggle-btn {
        display: inline-block;
    }

    .sidebar-overlay.show {
        display: block;
    }
}

/* ======= TABLET Y PANTALLAS GRANDES ======= */
@media (min-width: 993px) and (max-width: 1300px) {
    .sidebar {
        width: 220px;
    }

    .main-content {
        margin-left: 220px;
    }

    .nav-link {
        font-size: 0.9rem;
    }
}

/* ======= ULTRAWIDE ======= */
@media (min-width: 1600px) {
    .responsive-grid {
        grid-template-columns: repeat(auto-fit, minmax(340px, 1fr));
        gap: 1.5rem;
    }

    .card-body {
        padding: 2rem;
    }

    .nav-link {
        font-size: 1.1rem;
    }
}

/* ======= MODO OSCURO ======= */
body.dark-mode {
    background-color: #121212;
    color: #e4e4e4;
}

body.dark-mode .card {
    background-color: #1f1f1f;
    color: #e4e4e4;
}

body.dark-mode .main-header {
    background-color: #1c1c1c;
    color: #fff;
}

body.dark-mode .sidebar {
    background-color: #1b1b1b;
}

body.dark-mode .nav-link {
    color: #aaa;
}

body.dark-mode .nav-link.active,
body.dark-mode .nav-link:hover {
    background-color: #0d6efd;
    color: #fff !important;
}
</style>

</head>
<body>

<div id="wrapper">

    <!-- Sidebar -->
    <nav class="sidebar d-flex flex-column" id="sidebarMenu">
        <a href="dashboard.php?page=home" class="sidebar-header p-3 text-decoration-none text-center">
            <img src="img/Logo.png" alt="Logo">
        </a>

        <ul class="nav flex-column p-2 flex-grow-1">
            <li class="nav-item">
                <a class="nav-link <?= $page == 'home' ? 'active' : ''; ?>" href="dashboard.php?page=home">
                    <i class="bi bi-grid-fill me-2"></i> Dashboard
                </a>
            </li>

            <li class="nav-item">
                <a class="nav-link <?= $page == 'profesores' ? 'active' : ''; ?>" href="dashboard.php?page=profesores">
                    <i class="bi bi-people-fill me-2"></i> Profesores
                </a>
            </li>

            <li class="nav-item">
                <a class="nav-link <?= $page == 'salones' ? 'active' : ''; ?>" href="dashboard.php?page=salones">
                    <i class="bi bi-door-open-fill me-2"></i> Salones
                </a>
            </li>

            <!-- ✅ NUEVO: Recursos debajo de Salones -->
            <li class="nav-item">
                <a class="nav-link <?= $page == 'recursos' ? 'active' : ''; ?>" href="dashboard.php?page=recursos">
                    <i class="bi bi-box-seam-fill me-2"></i> Recursos
                </a>
            </li>

            <li class="nav-item">
                <a class="nav-link <?= $page == 'horarios' ? 'active' : ''; ?>" href="dashboard.php?page=horarios">
                    <i class="bi bi-calendar-week-fill me-2"></i> Horarios
                </a>
            </li>

            <?php if ($rol_lower === "admin"): ?>
                <li class="nav-item mt-3 pt-3 border-top border-secondary"></li>

                <li class="nav-item">
                    <a class="nav-link <?= $page == 'registrar' ? 'active' : ''; ?>" href="dashboard.php?page=registrar">
                        <i class="bi bi-person-plus-fill me-2"></i> Registrar Usuario
                    </a>
                </li>

                <li class="nav-item">
                    <a class="nav-link <?= $page == 'gestionar_contenido' && ($_GET['tipo'] ?? '') == 'noticias' ? 'active' : ''; ?>" 
                    href="dashboard.php?page=gestionar_contenido&tipo=noticias">
                        <i class="bi bi-newspaper me-2"></i> Noticias
                    </a>
                </li>

                <li class="nav-item">
                    <a class="nav-link <?= $page == 'gestionar_contenido' && ($_GET['tipo'] ?? '') == 'avisos' ? 'active' : ''; ?>" 
                    href="dashboard.php?page=gestionar_contenido&tipo=avisos">
                        <i class="bi bi-exclamation-triangle me-2"></i> Avisos
                    </a>
                </li>

                <li class="nav-item">
                    <a class="nav-link <?= $page == 'cargar_horarios' ? 'active' : ''; ?>" href="dashboard.php?page=cargar_horarios">
                        <i class="bi bi-plus-circle me-2"></i> Cargar Horarios
                    </a>
                </li>

                <li class="nav-item">
                    <a class="nav-link <?= $page == 'asignar_grupos' ? 'active' : ''; ?>" href="dashboard.php?page=asignar_grupos">
                        <i class="bi bi-diagram-3-fill me-2"></i> Asignar Grupos
                    </a>
                </li>

                <li class="nav-item">
                    <a class="nav-link <?= $page == 'agregar_salon' ? 'active' : ''; ?>" href="dashboard.php?page=agregar_salon">
                        <i class="bi bi-building-add me-2"></i> Agregar Salón
                    </a>
                </li>

                <!-- ✅ NUEVO: Agregar Recursos debajo de Agregar Salón -->
                <li class="nav-item">
                    <a class="nav-link <?= $page == 'agregar_recursos' ? 'active' : ''; ?>" href="dashboard.php?page=agregar_recursos">
                        <i class="bi bi-plus-square me-2"></i> Agregar Recursos
                    </a>
                </li>

                <li class="nav-item">
                    <a class="nav-link <?= $page == 'agregar_materias' ? 'active' : ''; ?>" href="dashboard.php?page=agregar_materias">
                        <i class="bi bi-book me-2"></i> Agregar Materias
                    </a>
                </li>

                <li class="nav-item">
                    <a class="nav-link <?= $page == 'grupos' ? 'active' : ''; ?>" href="dashboard.php?page=grupos">
                        <i class="bi bi-collection-fill me-2"></i> Grupos
                    </a>
                </li>

            <?php elseif ($rol_lower === "profesor"): ?>
                <li class="nav-item mt-3 pt-3 border-top border-secondary"></li>

                <li class="nav-item">
                    <a class="nav-link <?= $page == 'registrar' ? 'active' : ''; ?>" href="dashboard.php?page=registrar">
                        <i class="bi bi-person-plus-fill me-2"></i> Registrar Alumno
                    </a>
                </li>
            <?php endif; ?>
        </ul>
    </nav>

    <div class="sidebar-overlay" id="sidebarOverlay"></div>

    <!-- MAIN -->
    <div class="main-content">
        <header class="main-header p-3 border-bottom shadow-sm d-flex justify-content-between align-items-center">
            <div class="d-flex align-items-center">
                <button class="btn btn-outline-dark me-2 toggle-btn" id="toggleSidebar">
                    <i class="bi bi-list"></i>
                </button>
                <h2 class="h5 mb-0 text-dark" id="welcomeText">
                    Bienvenido, <span class="fw-bold"><?= htmlspecialchars($nombre) ?></span> (<?= htmlspecialchars($rol) ?>)
                </h2>
            </div>

            <div class="d-flex align-items-center gap-2">
                <!-- Botón Configuración -->
                <div class="dropdown">
                    <button class="btn btn-outline-secondary d-flex align-items-center" id="settingsDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                        <i class="bi bi-gear-fill"></i>
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end shadow" aria-labelledby="settingsDropdown" style="min-width: 220px;">
                        <li class="dropdown-header fw-semibold text-center">⚙️ Configuración</li>
                        <li><hr class="dropdown-divider"></li>
                        <li>
                            <button class="dropdown-item d-flex justify-content-between align-items-center" id="themeToggle">
                                <span id="themeLabel">Modo oscuro</span>
                                <i class="bi bi-moon-fill"></i>
                            </button>
                        </li>
                        <li>
                            <button class="dropdown-item d-flex justify-content-between align-items-center" id="languageToggle">
                                <span id="langLabel">Español</span>
                                <span><img id="langIcon" src="https://flagcdn.com/w20/es.png" alt="Español" width="20" height="14"></span>
                            </button>
                        </li>
                    </ul>
                </div>

                <a href="backend/logout.php" class="btn btn-outline-danger">
                    <i class="bi bi-box-arrow-right"></i>
                </a>
            </div>
        </header>

        <main class="p-3">
            <div class="responsive-grid">
                <div class="card">
                    <div class="card-body">
                        <?php
                        $allowed_pages = ['home', 'profesores', 'salones', 'horarios','recursos'];

                        if ($rol_lower === 'admin') {
                            $allowed_pages = array_merge($allowed_pages, [
                                'registrar','gestionar_contenido','cargar_horarios','asignar_grupos','agregar_salon','agregar_recursos','agregar_materias','grupos'
                            ]);
                        } elseif ($rol_lower === 'profesor') {
                            $allowed_pages[] = 'registrar';
                        }

                        if (in_array($page, $allowed_pages)) {
                            switch ($page) {
                                case 'registrar': $page_file = 'registrar_usuario.php'; break;
                                case 'gestionar_contenido': $page_file = 'gestionar_contenido.php'; break;
                                case 'cargar_horarios': $page_file = 'cargar_horarios.php'; break;
                                case 'asignar_grupos': $page_file = 'asignar_grupos.php'; break;
                                case 'agregar_salon': $page_file = 'agregar_salon.php'; break;
                                case 'agregar_recursos': $page_file = 'agregar_recurso.php'; break;
                                case 'agregar_materias': $page_file = 'agregar_materias.php'; break;
                                case 'grupos': $page_file = 'grupo.php'; break;
                                case 'recursos': $page_file = 'recursos.php'; break;
                                default: $page_file = $page . '.php'; break;
                            }

                            $file_path = "pages/{$page_file}";
                            if (file_exists($file_path)) include $file_path;
                            else echo '<div class="alert alert-danger">❌ Error: archivo de página no encontrado.</div>';
                        } else {
                            echo '<div class="alert alert-warning">⚠️ Página no encontrada o sin permisos.</div>';
                        }
                        ?>
                    </div>
                </div>
            </div>
        </main>
    </div>
</div>

<script>
/* =======================
   🌙 MODO OSCURO / CLARO
   ======================= */
const body = document.body;
const themeToggle = document.getElementById('themeToggle');
const themeLabel = document.getElementById('themeLabel');

if (localStorage.getItem('theme') === 'dark') {
    body.classList.add('dark-mode');
    themeLabel.textContent = "Modo claro";
} else {
    themeLabel.textContent = "Modo oscuro";
}

themeToggle.addEventListener('click', () => {
    body.classList.toggle('dark-mode');
    const isDark = body.classList.contains('dark-mode');
    themeLabel.textContent = isDark ? "Modo claro" : "Modo oscuro";
    localStorage.setItem('theme', isDark ? 'dark' : 'light');
});

/* =======================
   🌐 CAMBIO DE IDIOMA
   ======================= */
const langToggle = document.getElementById('languageToggle');
const langLabel = document.getElementById('langLabel');
const langIcon = document.getElementById('langIcon');
const welcomeText = document.getElementById('welcomeText');

function setLanguage(lang) {
    if (lang === 'en') {
        langLabel.textContent = 'English';
        langIcon.src = "https://flagcdn.com/w20/gb.png";
        welcomeText.innerHTML = `Welcome, <span class="fw-bold"><?= htmlspecialchars($nombre) ?></span> (<?= htmlspecialchars($rol) ?>)`;
    } else {
        langLabel.textContent = 'Español';
        langIcon.src = "https://flagcdn.com/w20/es.png";
        welcomeText.innerHTML = `Bienvenido, <span class="fw-bold"><?= htmlspecialchars($nombre) ?></span> (<?= htmlspecialchars($rol) ?>)`;
    }
    localStorage.setItem('lang', lang);
}

setLanguage(localStorage.getItem('lang') || 'es');

langToggle.addEventListener('click', () => {
    const current = localStorage.getItem('lang') === 'en' ? 'es' : 'en';
    setLanguage(current);
});

/* =======================
   ⏰ SESIÓN AUTOMÁTICA
   ======================= */
let tiempoInactividad = 15 * 60 * 1000;
let temporizador;
function reiniciarTemporizador() {
    clearTimeout(temporizador);
    temporizador = setTimeout(() => {
        alert("Tu sesión ha expirado por inactividad.");
        window.location.href = "backend/logout.php";
    }, tiempoInactividad);
}
document.addEventListener("mousemove", reiniciarTemporizador);
document.addEventListener("keydown", reiniciarTemporizador);
reiniciarTemporizador();

/* =======================
   📱 TOGGLE SIDEBAR MÓVIL
   ======================= */
const sidebar = document.getElementById('sidebarMenu');
const sidebarOverlay = document.getElementById('sidebarOverlay');
document.getElementById('toggleSidebar').addEventListener('click', () => {
    sidebar.classList.toggle('show');
    sidebarOverlay.classList.toggle('show');
});
sidebarOverlay.addEventListener('click', () => {
    sidebar.classList.remove('show');
    sidebarOverlay.classList.remove('show');
});
</script>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
