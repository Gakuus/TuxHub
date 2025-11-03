<?php
session_start();

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
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Dashboard de gestión académica">
    <meta name="robots" content="noindex, nofollow">
    <title>Dashboard - Agora</title>

    <!-- CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link rel="stylesheet" href="css/dashboard.css">
</head>
<body>

<div id="wrapper">
    <!-- Sidebar -->
    <nav class="sidebar d-flex flex-column" id="sidebarMenu">
        <a href="dashboard.php?page=home" class="sidebar-header p-3 text-decoration-none text-center">
            <img src="img/Logo.png" alt="Logo" loading="lazy">
        </a>

        <ul class="nav flex-column p-2 flex-grow-1">
            <li class="nav-item">
                <a class="nav-link <?= $page == 'home' ? 'active' : ''; ?>" href="dashboard.php?page=home">
                    <i class="bi bi-grid-fill me-2"></i> <span class="nav-text">Dashboard</span>
                </a>
            </li>

            <li class="nav-item">
                <a class="nav-link <?= $page == 'profesores' ? 'active' : ''; ?>" href="dashboard.php?page=profesores">
                    <i class="bi bi-people-fill me-2"></i> <span class="nav-text">Profesores</span>
                </a>
            </li>

            <li class="nav-item">
                <a class="nav-link <?= $page == 'salones' ? 'active' : ''; ?>" href="dashboard.php?page=salones">
                    <i class="bi bi-door-open-fill me-2"></i> <span class="nav-text">Salones</span>
                </a>
            </li>

            <li class="nav-item">
                <a class="nav-link <?= $page == 'recursos' ? 'active' : ''; ?>" href="dashboard.php?page=recursos">
                    <i class="bi bi-box-seam-fill me-2"></i> <span class="nav-text">Recursos</span>
                </a>
            </li>

            <li class="nav-item">
                <a class="nav-link <?= $page == 'horarios' ? 'active' : ''; ?>" href="dashboard.php?page=horarios">
                    <i class="bi bi-calendar-week-fill me-2"></i> <span class="nav-text">Horarios</span>
                </a>
            </li>

            <?php if ($rol_lower === "admin"): ?>
                <li class="nav-item mt-3 pt-3 border-top border-secondary"></li>

                <li class="nav-item">
                    <a class="nav-link <?= $page == 'registrar' ? 'active' : ''; ?>" href="dashboard.php?page=registrar">
                        <i class="bi bi-person-plus-fill me-2"></i> <span class="nav-text">Registrar Usuario</span>
                    </a>
                </li>

                <li class="nav-item">
                    <a class="nav-link <?= $page == 'gestionar_contenido' && ($_GET['tipo'] ?? '') == 'noticias' ? 'active' : ''; ?>" 
                    href="dashboard.php?page=gestionar_contenido&tipo=noticias">
                        <i class="bi bi-newspaper me-2"></i> <span class="nav-text">Noticias</span>
                    </a>
                </li>

                <li class="nav-item">
                    <a class="nav-link <?= $page == 'gestionar_contenido' && ($_GET['tipo'] ?? '') == 'avisos' ? 'active' : ''; ?>" 
                    href="dashboard.php?page=gestionar_contenido&tipo=avisos">
                        <i class="bi bi-exclamation-triangle me-2"></i> <span class="nav-text">Avisos</span>
                    </a>
                </li>

                <li class="nav-item">
                    <a class="nav-link <?= $page == 'cargar_horarios' ? 'active' : ''; ?>" href="dashboard.php?page=cargar_horarios">
                        <i class="bi bi-plus-circle me-2"></i> <span class="nav-text">Cargar Horarios</span>
                    </a>
                </li>

                <li class="nav-item">
                    <a class="nav-link <?= $page == 'asignar_grupos' ? 'active' : ''; ?>" href="dashboard.php?page=asignar_grupos">
                        <i class="bi bi-diagram-3-fill me-2"></i> <span class="nav-text">Asignar Grupos</span>
                    </a>
                </li>

                <li class="nav-item">
                    <a class="nav-link <?= $page == 'agregar_salon' ? 'active' : ''; ?>" href="dashboard.php?page=agregar_salon">
                        <i class="bi bi-building-add me-2"></i> <span class="nav-text">Agregar Salón</span>
                    </a>
                </li>

                <li class="nav-item">
                    <a class="nav-link <?= $page == 'agregar_recursos' ? 'active' : ''; ?>" href="dashboard.php?page=agregar_recursos">
                        <i class="bi bi-plus-square me-2"></i> <span class="nav-text">Agregar Recursos</span>
                    </a>
                </li>

                <li class="nav-item">
                    <a class="nav-link <?= $page == 'agregar_materias' ? 'active' : ''; ?>" href="dashboard.php?page=agregar_materias">
                        <i class="bi bi-book me-2"></i> <span class="nav-text">Agregar Materias</span>
                    </a>
                </li>

                <li class="nav-item">
                    <a class="nav-link <?= $page == 'grupos' ? 'active' : ''; ?>" href="dashboard.php?page=grupos">
                        <i class="bi bi-collection-fill me-2"></i> <span class="nav-text">Grupos</span>
                    </a>
                </li>

            <?php elseif ($rol_lower === "profesor"): ?>
                <li class="nav-item mt-3 pt-3 border-top border-secondary"></li>

                <li class="nav-item">
                    <a class="nav-link <?= $page == 'registrar' ? 'active' : ''; ?>" href="dashboard.php?page=registrar">
                        <i class="bi bi-person-plus-fill me-2"></i> <span class="nav-text">Registrar Alumno</span>
                    </a>
                </li>
            <?php endif; ?>
        </ul>
    </nav>

    <div class="sidebar-overlay" id="sidebarOverlay"></div>

    <!-- MAIN CONTENT -->
    <div class="main-content">
        <header class="main-header">
            <div class="container-fluid">
                <div class="row align-items-center">
                    <div class="col-auto">
                        <button class="btn btn-outline-dark toggle-btn" id="toggleSidebar">
                            <i class="bi bi-list"></i>
                        </button>
                    </div>
                    <div class="col">
                        <h2 class="h5 mb-0 text-dark welcome-text" id="welcomeText">
                            Bienvenido, <span class="fw-bold"><?= $nombre ?></span> (<?= $rol ?>)
                        </h2>
                    </div>
                    <div class="col-auto">
                        <div class="d-flex align-items-center gap-2">
                            <!-- Botón Configuración -->
                            <div class="dropdown">
                                <button class="btn btn-outline-secondary d-flex align-items-center" id="settingsDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                                    <i class="bi bi-gear-fill"></i>
                                </button>
                                <ul class="dropdown-menu dropdown-menu-end shadow" aria-labelledby="settingsDropdown">
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
                                            <span><img id="langIcon" src="https://flagcdn.com/w20/es.png" alt="Español" width="20" height="14" loading="lazy"></span>
                                        </button>
                                    </li>
                                </ul>
                            </div>

                            <a href="backend/logout.php" class="btn btn-outline-danger">
                                <i class="bi bi-box-arrow-right"></i>
                            </a>
                        </div>
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
                                    echo '<div class="alert alert-danger">❌ Error: archivo de página no encontrado.</div>';
                                    error_log("Archivo no encontrado: " . $file_path);
                                }
                            } else {
                                echo '<div class="alert alert-warning">⚠️ Página no encontrada o sin permisos.</div>';
                            }
                            ?>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>
</div>

<!-- JavaScript -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="assets/dashboard.js"></script>
</body>
</html>