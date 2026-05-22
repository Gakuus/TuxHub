<?php
require_once __DIR__ . '/../backend/db_connection.php';

$nombre = htmlspecialchars($_SESSION['user_name'] ?? 'Usuario', ENT_QUOTES, 'UTF-8');
$rol = $_SESSION['rol'] ?? 'invitado';
$user_id = (int) ($_SESSION['user_id'] ?? 0);
$rol_label = match($rol) {
    'admin'    => 'Administrador',
    'profesor' => 'Profesor',
    'alumno'   => 'Alumno',
    default    => 'Invitado'
};

$stats = [
    'materias' => 0, 'grupos' => 0, 'horarios' => 0, 'salones' => 0,
    'usuarios' => 0, 'profesores' => 0, 'alumnos' => 0,
    'salones_disponibles' => 0, 'salones_ocupados' => 0,
    'recursos_disponibles' => 0, 'recursos_ocupados' => 0,
    'horarios_hoy' => 0,
];

try {
    $stats['materias'] = (int) ($conn->query("SELECT COUNT(*) as c FROM materias")->fetch_assoc()['c'] ?? 0);
    $stats['grupos']   = (int) ($conn->query("SELECT COUNT(*) as c FROM grupos")->fetch_assoc()['c'] ?? 0);
    $stats['horarios'] = (int) ($conn->query("SELECT COUNT(*) as c FROM horarios")->fetch_assoc()['c'] ?? 0);
    $stats['salones']  = (int) ($conn->query("SELECT COUNT(*) as c FROM salones")->fetch_assoc()['c'] ?? 0);
    $stats['usuarios'] = (int) ($conn->query("SELECT COUNT(*) as c FROM usuarios")->fetch_assoc()['c'] ?? 0);
    $stats['profesores'] = (int) ($conn->query("SELECT COUNT(*) as c FROM usuarios WHERE rol='profesor'")->fetch_assoc()['c'] ?? 0);
    $stats['alumnos']    = (int) ($conn->query("SELECT COUNT(*) as c FROM usuarios WHERE rol='alumno'")->fetch_assoc()['c'] ?? 0);

    $r = $conn->query("SELECT estado, COUNT(*) as c FROM salones GROUP BY estado");
    while ($row = $r->fetch_assoc()) {
        if ($row['estado'] === 'disponible') $stats['salones_disponibles'] = (int) $row['c'];
        else $stats['salones_ocupados'] = (int) $row['c'];
    }

    $r2 = $conn->query("SELECT estado, COUNT(*) as c FROM recursos GROUP BY estado");
    while ($row = $r2->fetch_assoc()) {
        if ($row['estado'] === 'Disponible') $stats['recursos_disponibles'] = (int) $row['c'];
        else $stats['recursos_ocupados'] = ((int) ($stats['recursos_ocupados'])) + (int) $row['c'];
    }

    $hoy = date('w'); // 0=Sun,1=Mon...
    $hoy = $hoy === 0 ? 5 : $hoy - 1; // convert to 1=Lunes..5=Viernes
    if ($hoy >= 1 && $hoy <= 5) {
        $stmt = $conn->prepare("SELECT COUNT(*) as c FROM horarios WHERE dia_id = ?");
        $stmt->bind_param("i", $hoy);
        $stmt->execute();
        $stats['horarios_hoy'] = (int) $stmt->get_result()->fetch_assoc()['c'];
        $stmt->close();
    }
} catch (Exception $e) {
    error_log("Error fetching dashboard stats: " . $e->getMessage());
}

$avisos   = $conn->query("SELECT * FROM avisos ORDER BY fecha_publicacion DESC LIMIT 5");
$noticias = $conn->query("SELECT * FROM noticias ORDER BY fecha_publicacion DESC LIMIT 6");

$horario = null;
if ($user_id && in_array($rol, ['profesor', 'alumno'])) {
    $col = $rol === 'profesor' ? 'h.profesor_id' : 'h.grupo_id';
    $val = $rol === 'profesor' ? $user_id : (int) ($_SESSION['grupo_id'] ?? 0);
    if ($val) {
        $stmt = $conn->prepare("
            SELECT m.nombre_materia, g.nombre AS grupo_nombre, s.nombre_salon,
                   d.nombre_dia, b.hora_inicio, b.hora_fin
            FROM horarios h
            JOIN materias m ON m.id = h.materia_id
            JOIN grupos g ON g.id = h.grupo_id
            JOIN salones s ON s.id = h.salon_id
            JOIN dias d ON d.id = h.dia_id
            JOIN bloques_horarios b ON b.id = h.bloque_id
            WHERE $col = ?
            ORDER BY h.dia_id, b.hora_inicio
            LIMIT 10
        ");
        $stmt->bind_param("i", $val);
        $stmt->execute();
        $res = $stmt->get_result();
        $horario = $res->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
    }
}

// Today's classes
$clases_hoy = null;
$dia_hoy_nombre = match((int)date('w')) { 1=>'Lunes',2=>'Martes',3=>'Miércoles',4=>'Jueves',5=>'Viernes',default=>null };
if ($dia_hoy_nombre && $user_id && in_array($rol, ['profesor', 'alumno'])) {
    $col = $rol === 'profesor' ? 'h.profesor_id' : 'h.grupo_id';
    $val = $rol === 'profesor' ? $user_id : (int) ($_SESSION['grupo_id'] ?? 0);
    if ($val) {
        $stmt = $conn->prepare("
            SELECT m.nombre_materia, g.nombre AS grupo_nombre, s.nombre_salon,
                   b.hora_inicio, b.hora_fin
            FROM horarios h
            JOIN materias m ON m.id = h.materia_id
            JOIN grupos g ON g.id = h.grupo_id
            JOIN salones s ON s.id = h.salon_id
            JOIN dias d ON d.id = h.dia_id
            JOIN bloques_horarios b ON b.id = h.bloque_id
            WHERE $col = ? AND d.nombre_dia = ?
            ORDER BY b.hora_inicio
        ");
        $stmt->bind_param("is", $val, $dia_hoy_nombre);
        $stmt->execute();
        $clases_hoy = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
    }
}

// Recent users (admin only)
$usuarios_recientes = null;
if ($rol === 'admin') {
    $r = $conn->query("SELECT id, cedula, nombre, rol, created_at FROM usuarios ORDER BY created_at DESC LIMIT 5");
    if ($r) $usuarios_recientes = $r->fetch_all(MYSQLI_ASSOC);
}
?>
<section class="home-section">

    <!-- Page Header -->
    <div class="page-header">
        <h2><i class="bi bi-house-door-fill"></i> Bienvenido, <?= $nombre ?></h2>
        <span class="badge-user">
            <i class="bi bi-<?= $rol === 'admin' ? 'shield-lock' : ($rol === 'profesor' ? 'mortarboard-fill' : 'person-fill') ?>"></i>
            <?= htmlspecialchars($rol_label) ?>
        </span>
    </div>

    <!-- Stats Grid -->
    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-value blue"><?= $stats['materias'] ?></div>
            <div class="stat-label"><i class="bi bi-book"></i> Materias</div>
        </div>
        <div class="stat-card">
            <div class="stat-value green"><?= $stats['grupos'] ?></div>
            <div class="stat-label"><i class="bi bi-people-fill"></i> Grupos</div>
        </div>
        <div class="stat-card">
            <div class="stat-value yellow"><?= $stats['horarios'] ?></div>
            <div class="stat-label"><i class="bi bi-calendar-week-fill"></i> Horarios</div>
        </div>
        <div class="stat-card">
            <div class="stat-value red"><?= $stats['salones'] ?></div>
            <div class="stat-label"><i class="bi bi-building"></i> Salones</div>
        </div>
        <div class="stat-card">
            <div class="stat-value purple"><?= $stats['usuarios'] ?></div>
            <div class="stat-label"><i class="bi bi-people"></i> Usuarios</div>
        </div>
        <div class="stat-card">
            <div class="stat-value info"><?= $stats['horarios_hoy'] ?></div>
            <div class="stat-label"><i class="bi bi-clock"></i> Clases Hoy</div>
        </div>
    </div>

    <!-- Quick Actions + Today's Schedule Row -->
    <div class="dashboard-row">
        <!-- Quick Actions -->
        <div class="dashboard-col dashboard-col-narrow">
            <div class="card h-100">
                <div class="card-header"><i class="bi bi-lightning-charge-fill"></i> Acciones Rápidas</div>
                <div class="card-body">
                    <div class="quick-actions">
                        <?php if ($rol === 'admin'): ?>
                        <a href="dashboard.php?page=registrar" class="quick-action">
                            <span class="qa-icon" style="background:rgba(99,102,241,0.12);color:var(--primary-color);"><i class="bi bi-person-plus-fill"></i></span>
                            <span class="qa-text">Registrar Usuario</span>
                        </a>
                        <a href="dashboard.php?page=agregar_salon" class="quick-action">
                            <span class="qa-icon" style="background:rgba(34,197,94,0.12);color:#16a34a;"><i class="bi bi-building-add"></i></span>
                            <span class="qa-text">Nuevo Salón</span>
                        </a>
                        <a href="dashboard.php?page=cargar_horarios" class="quick-action">
                            <span class="qa-icon" style="background:rgba(251,191,36,0.12);color:#b45309;"><i class="bi bi-plus-circle"></i></span>
                            <span class="qa-text">Cargar Horario</span>
                        </a>
                        <a href="dashboard.php?page=asignar_grupos" class="quick-action">
                            <span class="qa-icon" style="background:rgba(139,92,246,0.12);color:#7c3aed;"><i class="bi bi-diagram-3-fill"></i></span>
                            <span class="qa-text">Asignar Grupos</span>
                        </a>
                        <a href="dashboard.php?page=gestionar_contenido&tipo=noticias" class="quick-action">
                            <span class="qa-icon" style="background:rgba(236,72,153,0.12);color:#db2777;"><i class="bi bi-newspaper"></i></span>
                            <span class="qa-text">Nueva Noticia</span>
                        </a>
                        <?php elseif ($rol === 'profesor'): ?>
                        <a href="dashboard.php?page=profesores" class="quick-action">
                            <span class="qa-icon" style="background:rgba(99,102,241,0.12);color:var(--primary-color);"><i class="bi bi-person-badge"></i></span>
                            <span class="qa-text">Tomar Asistencia</span>
                        </a>
                        <a href="dashboard.php?page=registrar" class="quick-action">
                            <span class="qa-icon" style="background:rgba(34,197,94,0.12);color:#16a34a;"><i class="bi bi-person-plus-fill"></i></span>
                            <span class="qa-text">Registrar Alumno</span>
                        </a>
                        <?php endif; ?>
                        <a href="dashboard.php?page=horarios" class="quick-action">
                            <span class="qa-icon" style="background:rgba(14,165,233,0.12);color:#0284c7;"><i class="bi bi-calendar-week-fill"></i></span>
                            <span class="qa-text">Ver Horarios</span>
                        </a>
                        <a href="dashboard.php?page=salones" class="quick-action">
                            <span class="qa-icon" style="background:rgba(251,191,36,0.12);color:#b45309;"><i class="bi bi-door-open-fill"></i></span>
                            <span class="qa-text">Ver Salones</span>
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <!-- Today's Classes -->
        <div class="dashboard-col dashboard-col-wide">
            <?php if ($clases_hoy): ?>
            <div class="card h-100">
                <div class="card-header">
                    <i class="bi bi-calendar-check-fill"></i> Clases de Hoy — <?= htmlspecialchars($dia_hoy_nombre) ?>
                    <span class="badge ms-2" style="background:var(--primary-color);color:#fff;font-size:0.65rem;"><?= count($clases_hoy) ?></span>
                </div>
                <div class="card-body p-0">
                    <div class="today-classes">
                        <?php foreach ($clases_hoy as $c): ?>
                        <div class="today-class-item">
                            <div class="class-time">
                                <span class="class-time-start"><?= htmlspecialchars(substr($c['hora_inicio'],0,5)) ?></span>
                                <span class="class-time-end"><?= htmlspecialchars(substr($c['hora_fin'],0,5)) ?></span>
                            </div>
                            <div class="class-dot"></div>
                            <div class="class-info">
                                <span class="class-materia"><?= htmlspecialchars($c['nombre_materia']) ?></span>
                                <span class="class-detail"><?= htmlspecialchars($c['grupo_nombre']) ?> · <?= htmlspecialchars($c['nombre_salon']) ?></span>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
            <?php else: ?>
            <div class="card h-100">
                <div class="card-header"><i class="bi bi-calendar-check-fill"></i> Clases de Hoy</div>
                <div class="card-body d-flex align-items-center justify-content-center" style="min-height:160px;">
                    <div class="text-center text-muted">
                        <i class="bi bi-emoji-neutral" style="font-size:2rem;"></i>
                        <p class="mb-0 small mt-2">Sin clases programadas para hoy</p>
                    </div>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Resource & Salon Status Row -->
    <div class="dashboard-row">
        <div class="dashboard-col dashboard-col-wide">
            <!-- Schedule (existing for profesor/alumno) -->
            <?php if ($horario && count($horario) > 0): ?>
            <div class="card mb-0">
                <div class="card-header"><i class="bi bi-calendar-week-fill"></i> Mi Horario Semanal</div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table mb-0 home-schedule-table">
                            <thead>
                                <tr>
                                    <th><i class="bi bi-calendar3"></i> Día</th>
                                    <th><i class="bi bi-clock"></i> Horario</th>
                                    <th><i class="bi bi-book"></i> Materia</th>
                                    <th><i class="bi bi-people"></i> Grupo</th>
                                    <th><i class="bi bi-door-open"></i> Salón</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($horario as $h): ?>
                                <tr>
                                    <td><span class="dia-badge"><?= htmlspecialchars($h['nombre_dia']) ?></span></td>
                                    <td><?= htmlspecialchars($h['hora_inicio']) ?> – <?= htmlspecialchars($h['hora_fin']) ?></td>
                                    <td class="fw-semibold"><?= htmlspecialchars($h['nombre_materia']) ?></td>
                                    <td><?= htmlspecialchars($h['grupo_nombre']) ?></td>
                                    <td><?= htmlspecialchars($h['nombre_salon']) ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            <?php endif; ?>
        </div>

        <!-- Status Cards Column -->
        <div class="dashboard-col dashboard-col-narrow">
            <!-- Salones Status -->
            <div class="card mb-3">
                <div class="card-header"><i class="bi bi-building"></i> Estado de Salones</div>
                <div class="card-body">
                    <div class="status-chart">
                        <div class="status-bar">
                            <div class="status-bar-fill" style="width:<?= $stats['salones'] ? round(($stats['salones_disponibles']/$stats['salones'])*100) : 0 ?>%;background:var(--success);"></div>
                        </div>
                        <div class="status-legend">
                            <span><span class="dot" style="background:var(--success);"></span> <?= $stats['salones_disponibles'] ?> Disponibles</span>
                            <span><span class="dot" style="background:var(--danger);"></span> <?= $stats['salones_ocupados'] ?> Ocupados</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Recursos Status -->
            <div class="card mb-3">
                <div class="card-header"><i class="bi bi-box-seam"></i> Recursos</div>
                <div class="card-body">
                    <div class="status-chart">
                        <div class="status-bar">
                            <div class="status-bar-fill" style="width:<?php $tr = $stats['recursos_disponibles']+$stats['recursos_ocupados']; echo $tr ? round(($stats['recursos_disponibles']/$tr)*100) : 0 ?>%;background:var(--primary-color);"></div>
                        </div>
                        <div class="status-legend">
                            <span><span class="dot" style="background:var(--primary-color);"></span> <?= $stats['recursos_disponibles'] ?> Disponibles</span>
                            <span><span class="dot" style="background:var(--warning);"></span> <?= $stats['recursos_ocupados'] ?> En uso</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Professors/Alumnos Mini Stats -->
            <?php if ($rol === 'admin'): ?>
            <div class="card">
                <div class="card-header"><i class="bi bi-people-fill"></i> Comunidad</div>
                <div class="card-body">
                    <div class="mini-stats">
                        <div class="mini-stat">
                            <span class="mini-stat-value"><?= $stats['profesores'] ?></span>
                            <span class="mini-stat-label">Profesores</span>
                        </div>
                        <div class="mini-stat">
                            <span class="mini-stat-value"><?= $stats['alumnos'] ?></span>
                            <span class="mini-stat-label">Alumnos</span>
                        </div>
                        <div class="mini-stat">
                            <span class="mini-stat-value"><?= max(0, $stats['usuarios'] - $stats['profesores'] - $stats['alumnos'] - 1) ?></span>
                            <span class="mini-stat-label">Admins</span>
                        </div>
                    </div>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Recent Users (admin only) -->
    <?php if ($rol === 'admin' && $usuarios_recientes): ?>
    <div class="card mb-4">
        <div class="card-header"><i class="bi bi-clock-history"></i> Últimos Usuarios Registrados</div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table mb-0 home-schedule-table">
                    <thead>
                        <tr>
                            <th>Cédula</th>
                            <th>Nombre</th>
                            <th>Rol</th>
                            <th>Registrado</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($usuarios_recientes as $u): ?>
                        <tr>
                            <td><span class="dia-badge"><?= htmlspecialchars($u['cedula']) ?></span></td>
                            <td class="fw-semibold"><?= htmlspecialchars($u['nombre']) ?></td>
                            <td><span class="status-badge <?= $u['rol'] === 'admin' ? 'available' : ($u['rol'] === 'profesor' ? 'reserved' : 'inactive') ?>"><?= htmlspecialchars(ucfirst($u['rol'])) ?></span></td>
                            <td class="text-muted small"><?= date("d/m/Y H:i", strtotime($u['created_at'])) ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- Avisos -->
    <?php if ($avisos && $avisos->num_rows > 0): ?>
    <div class="card mb-4">
        <div class="card-header"><i class="bi bi-megaphone-fill"></i> Avisos Importantes</div>
        <div class="card-body">
            <?php while ($aviso = $avisos->fetch_assoc()): ?>
            <div class="alert-card">
                <div class="alert-icon"><i class="bi bi-megaphone-fill"></i></div>
                <div class="alert-content">
                    <div class="d-flex justify-content-between align-items-start gap-2">
                        <div class="alert-title"><?= htmlspecialchars($aviso['titulo']) ?></div>
                        <span class="status-badge reserved">Importante</span>
                    </div>
                    <p class="alert-text"><?= nl2br(htmlspecialchars($aviso['mensaje'])) ?></p>
                    <small class="alert-date"><i class="bi bi-clock"></i> <?= date("d/m/Y H:i", strtotime($aviso['fecha_publicacion'])) ?></small>
                </div>
            </div>
            <?php endwhile; ?>
        </div>
    </div>
    <?php endif; ?>

    <!-- Noticias -->
    <?php if ($noticias && $noticias->num_rows > 0): ?>
    <div class="mb-4">
        <div class="d-flex align-items-center gap-2 mb-3">
            <i class="bi bi-newspaper" style="font-size:1.25rem;color:var(--primary-color);"></i>
            <h4 class="fw-bold mb-0">Últimas Noticias</h4>
        </div>
        <div class="news-grid">
            <?php while ($noticia = $noticias->fetch_assoc()): ?>
            <div class="news-card">
                <?php if (!empty($noticia['imagen_ruta'])): ?>
                <img src="<?= htmlspecialchars($noticia['imagen_ruta'], ENT_QUOTES, 'UTF-8') ?>" alt="<?= htmlspecialchars($noticia['titulo']) ?>" class="news-image" loading="lazy">
                <?php else: ?>
                <div class="news-image-placeholder"><i class="bi bi-image"></i></div>
                <?php endif; ?>
                <div class="news-body">
                    <div class="news-title"><?= htmlspecialchars($noticia['titulo']) ?></div>
                    <div class="news-excerpt"><?= nl2br(htmlspecialchars(substr($noticia['contenido'], 0, 120))) ?>...</div>
                    <div class="news-meta">
                        <span class="news-date"><i class="bi bi-calendar3"></i> <?= date("d/m/Y", strtotime($noticia['fecha_publicacion'])) ?></span>
                        <span class="news-category">Noticia</span>
                    </div>
                </div>
            </div>
            <?php endwhile; ?>
        </div>
    </div>
    <?php endif; ?>

    <!-- Empty state -->
    <?php
    $no_content = (!$avisos || $avisos->num_rows === 0)
        && (!$noticias || $noticias->num_rows === 0)
        && (!$horario || count($horario) === 0)
        && !$clases_hoy;
    if ($no_content):
    ?>
    <div class="empty-state">
        <i class="bi bi-emoji-wink"></i>
        <h4>¡Bienvenido a Ágora!</h4>
        <p>Comienza explorando los módulos del panel de navegación.</p>
    </div>
    <?php endif; ?>

</section>
