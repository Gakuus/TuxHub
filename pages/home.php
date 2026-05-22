<?php
require_once __DIR__ . '/../backend/db_connection.php';

// Traer los avisos
$avisos = $conn->query("SELECT * FROM avisos ORDER BY fecha_publicacion DESC LIMIT 5");

// Traer las noticias
$noticias = $conn->query("SELECT * FROM noticias ORDER BY fecha_publicacion DESC LIMIT 6");

// Stats
$total_materias = $conn->query("SELECT COUNT(*) as c FROM materias")->fetch_assoc()['c'] ?? 0;
$total_grupos = $conn->query("SELECT COUNT(*) as c FROM grupos")->fetch_assoc()['c'] ?? 0;
$total_horarios = $conn->query("SELECT COUNT(*) as c FROM horarios")->fetch_assoc()['c'] ?? 0;
$total_salones = $conn->query("SELECT COUNT(*) as c FROM salones")->fetch_assoc()['c'] ?? 0;

// Datos del usuario
$nombre = $_SESSION['nombre'] ?? 'Usuario';
$rol = $_SESSION['rol'] ?? 'invitado';
$rol_label = match($rol) { 'admin' => 'Administrador', 'profesor' => 'Profesor', 'alumno' => 'Alumno', default => 'Invitado' };

// Horario del usuario
$horario = null;
if (isset($_SESSION['user_id']) && $rol === 'profesor') {
    $uid = (int)$_SESSION['user_id'];
    $h = $conn->query("SELECT h.*, m.nombre_materia, g.nombre as grupo_nombre, s.nombre_salon, d.nombre_dia,
                       b.hora_inicio, b.hora_fin
                       FROM horarios h
                       JOIN materias m ON m.id = h.materia_id
                       JOIN grupos g ON g.id = h.grupo_id
                       JOIN salones s ON s.id = h.salon_id
                       JOIN dias d ON d.id = h.dia_id
                       JOIN bloques_horarios b ON b.id = h.bloque_id
                       WHERE h.profesor_id = $uid
                       ORDER BY h.dia_id, b.hora_inicio
                       LIMIT 10");
    if ($h) $horario = $h->fetch_all(MYSQLI_ASSOC);
}
?>

<section class="home-section">
  <div class="page-header">
    <h2><i class="bi bi-house-door"></i> Bienvenido, <?= htmlspecialchars($nombre) ?></h2>
    <span class="badge-user status-badge <?= $rol === 'admin' ? 'available' : ($rol === 'profesor' ? 'reserved' : 'inactive') ?>">
      <i class="bi bi-<?= $rol === 'admin' ? 'shield-lock' : ($rol === 'profesor' ? 'mortarboard' : 'person') ?>"></i>
      <?= htmlspecialchars($rol_label) ?>
    </span>
  </div>

  <div class="stats-grid">
    <div class="stat-card">
      <div class="stat-value blue"><?= $total_materias ?></div>
      <div class="stat-label"><i class="bi bi-book"></i> Materias</div>
    </div>
    <div class="stat-card">
      <div class="stat-value green"><?= $total_grupos ?></div>
      <div class="stat-label"><i class="bi bi-people"></i> Grupos</div>
    </div>
    <div class="stat-card">
      <div class="stat-value yellow"><?= $total_horarios ?></div>
      <div class="stat-label"><i class="bi bi-calendar-week"></i> Horarios</div>
    </div>
    <div class="stat-card">
      <div class="stat-value red"><?= $total_salones ?></div>
      <div class="stat-label"><i class="bi bi-building"></i> Salones</div>
    </div>
  </div>

  <?php if ($horario && count($horario) > 0): ?>
    <div class="card mb-4">
      <div class="card-header"><i class="bi bi-calendar-check"></i> Mi Horario</div>
      <div class="card-body">
        <div class="table-responsive">
          <table class="table table-striped">
            <thead>
              <tr>
                <th>Día</th>
                <th>Horario</th>
                <th>Materia</th>
                <th>Grupo</th>
                <th>Salón</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($horario as $h): ?>
                <tr>
                  <td><?= htmlspecialchars($h['nombre_dia']) ?></td>
                  <td><?= htmlspecialchars($h['hora_inicio']) ?> - <?= htmlspecialchars($h['hora_fin']) ?></td>
                  <td><?= htmlspecialchars($h['nombre_materia']) ?></td>
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

  <?php if ($avisos && $avisos->num_rows > 0): ?>
    <div class="card mb-4">
      <div class="card-header"><i class="bi bi-megaphone"></i> Avisos Importantes</div>
      <div class="card-body">
        <?php while ($aviso = $avisos->fetch_assoc()): ?>
          <div class="alert-card mb-3 p-3 rounded-3" style="background:rgba(255,255,255,0.6);backdrop-filter:blur(12px);-webkit-backdrop-filter:blur(12px);border:1px solid rgba(255,255,255,0.3);border-radius:14px;transition:all .3s ease;">
            <div class="d-flex justify-content-between align-items-start mb-2">
              <h5 class="fw-bold mb-0"><?= htmlspecialchars($aviso['titulo']) ?></h5>
              <span class="badge bg-danger">Importante</span>
            </div>
            <p class="text-muted mb-2"><?= nl2br(htmlspecialchars($aviso['mensaje'])) ?></p>
            <small class="text-muted"><i class="bi bi-clock me-1"></i><?= date("d/m/Y H:i", strtotime($aviso['fecha_publicacion'])) ?></small>
          </div>
        <?php endwhile; ?>
      </div>
    </div>
  <?php endif; ?>

  <?php if ($noticias && $noticias->num_rows > 0): ?>
    <section class="mb-4">
      <h4 class="fw-bold mb-3"><i class="bi bi-newspaper text-primary me-2"></i>Últimas Noticias</h4>
      <div class="row g-3">
        <?php while ($noticia = $noticias->fetch_assoc()): ?>
          <div class="col-md-6 col-lg-4">
            <div class="news-card h-100 p-3 rounded-3" style="background:rgba(255,255,255,0.6);backdrop-filter:blur(12px);-webkit-backdrop-filter:blur(12px);border:1px solid rgba(255,255,255,0.3);border-radius:14px;transition:all .3s ease;">
              <?php if (!empty($noticia['imagen'])): ?>
                <div class="mb-2 rounded-2 overflow-hidden" style="max-height:150px;">
                  <img src="data:image/jpeg;base64,<?= base64_encode($noticia['imagen']) ?>" class="w-100" style="object-fit:cover;height:150px;" alt="Imagen">
                </div>
              <?php endif; ?>
              <h6 class="fw-bold"><?= htmlspecialchars($noticia['titulo']) ?></h6>
              <p class="small text-muted mb-2"><?= nl2br(htmlspecialchars(substr($noticia['contenido'], 0, 100))) ?>...</p>
              <small class="text-muted"><i class="bi bi-calendar me-1"></i><?= date("d/m/Y", strtotime($noticia['fecha_publicacion'])) ?></small>
            </div>
          </div>
        <?php endwhile; ?>
      </div>
    </section>
  <?php endif; ?>

  <?php if ($avisos === false || $avisos->num_rows === 0): ?>
    <?php if (!$horario || count($horario) === 0): ?>
      <div class="empty-state">
        <i class="bi bi-emoji-wink"></i>
        <h4>¡Bienvenido a Ágora!</h4>
        <p>Comienza explorando los módulos del panel de navegación.</p>
      </div>
    <?php endif; ?>
  <?php endif; ?>
</section>
