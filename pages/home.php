<?php
require_once __DIR__ . '/../backend/db_connection.php';

$nombre = htmlspecialchars($_SESSION['user_name'] ?? 'Usuario', ENT_QUOTES, 'UTF-8');
$rol = $_SESSION['rol'] ?? 'invitado';
$rol_label = match($rol) {
    'admin'    => 'Administrador',
    'profesor' => 'Profesor',
    'alumno'   => 'Alumno',
    default    => 'Invitado'
};

$total_materias = 0;
$total_grupos   = 0;
$total_horarios = 0;
$total_salones  = 0;

try {
    $total_materias = (int) ($conn->query("SELECT COUNT(*) as c FROM materias")->fetch_assoc()['c'] ?? 0);
    $total_grupos   = (int) ($conn->query("SELECT COUNT(*) as c FROM grupos")->fetch_assoc()['c'] ?? 0);
    $total_horarios = (int) ($conn->query("SELECT COUNT(*) as c FROM horarios")->fetch_assoc()['c'] ?? 0);
    $total_salones  = (int) ($conn->query("SELECT COUNT(*) as c FROM salones")->fetch_assoc()['c'] ?? 0);
} catch (Exception $e) {
    error_log("Error fetching stats: " . $e->getMessage());
}

$avisos  = $conn->query("SELECT * FROM avisos ORDER BY fecha_publicacion DESC LIMIT 5");
$noticias = $conn->query("SELECT * FROM noticias ORDER BY fecha_publicacion DESC LIMIT 6");

$horario = null;
if (isset($_SESSION['user_id']) && $rol === 'profesor') {
    $uid = (int) $_SESSION['user_id'];
    $stmt = $conn->prepare("
        SELECT m.nombre_materia, g.nombre AS grupo_nombre, s.nombre_salon,
               d.nombre_dia, b.hora_inicio, b.hora_fin
        FROM horarios h
        JOIN materias m ON m.id = h.materia_id
        JOIN grupos g ON g.id = h.grupo_id
        JOIN salones s ON s.id = h.salon_id
        JOIN dias d ON d.id = h.dia_id
        JOIN bloques_horarios b ON b.id = h.bloque_id
        WHERE h.profesor_id = ?
        ORDER BY h.dia_id, b.hora_inicio
        LIMIT 10
    ");
    $stmt->bind_param("i", $uid);
    $stmt->execute();
    $res = $stmt->get_result();
    $horario = $res->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
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
            <div class="stat-value blue"><?= $total_materias ?></div>
            <div class="stat-label"><i class="bi bi-book"></i> Materias</div>
        </div>
        <div class="stat-card">
            <div class="stat-value green"><?= $total_grupos ?></div>
            <div class="stat-label"><i class="bi bi-people-fill"></i> Grupos</div>
        </div>
        <div class="stat-card">
            <div class="stat-value yellow"><?= $total_horarios ?></div>
            <div class="stat-label"><i class="bi bi-calendar-week-fill"></i> Horarios</div>
        </div>
        <div class="stat-card">
            <div class="stat-value red"><?= $total_salones ?></div>
            <div class="stat-label"><i class="bi bi-building"></i> Salones</div>
        </div>
    </div>

    <!-- Profesor: My Schedule -->
    <?php if ($horario && count($horario) > 0): ?>
    <div class="card mb-4">
        <div class="card-header">
            <i class="bi bi-calendar-check-fill"></i> Mi Horario
        </div>
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

    <!-- Avisos -->
    <?php if ($avisos && $avisos->num_rows > 0): ?>
    <div class="card mb-4">
        <div class="card-header">
            <i class="bi bi-megaphone-fill"></i> Avisos Importantes
        </div>
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
                    <small class="alert-date">
                        <i class="bi bi-clock"></i> <?= date("d/m/Y H:i", strtotime($aviso['fecha_publicacion'])) ?>
                    </small>
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
                <img src="<?= htmlspecialchars($noticia['imagen_ruta'], ENT_QUOTES, 'UTF-8') ?>"
                     alt="<?= htmlspecialchars($noticia['titulo']) ?>" class="news-image" loading="lazy">
                <?php else: ?>
                <div class="news-image-placeholder">
                    <i class="bi bi-image"></i>
                </div>
                <?php endif; ?>
                <div class="news-body">
                    <div class="news-title"><?= htmlspecialchars($noticia['titulo']) ?></div>
                    <div class="news-excerpt">
                        <?= nl2br(htmlspecialchars(substr($noticia['contenido'], 0, 120))) ?>...
                    </div>
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
    $no_avisos   = !$avisos || $avisos->num_rows === 0;
    $no_noticias = !$noticias || $noticias->num_rows === 0;
    $no_horario  = !$horario || count($horario) === 0;
    if ($no_avisos && $no_noticias && $no_horario):
    ?>
    <div class="empty-state">
        <i class="bi bi-emoji-wink"></i>
        <h4>¡Bienvenido a Ágora!</h4>
        <p>Comienza explorando los módulos del panel de navegación.</p>
    </div>
    <?php endif; ?>

</section>
