<?php
require_once __DIR__ . '/../backend/db_connection.php';

// Traer los avisos
$avisos = $conn->query("SELECT * FROM avisos ORDER BY fecha_publicacion DESC LIMIT 5");

// Traer las noticias
$noticias = $conn->query("SELECT * FROM noticias ORDER BY fecha_publicacion DESC LIMIT 6");
?>

<div class="container-fluid">
    <!-- ================== BANNER CON LOGO Y DIFUMINADO ================== -->
    <div id="bannerCarousel" class="carousel slide position-relative mb-5" data-bs-ride="carousel" data-bs-interval="3000">
        <div class="carousel-inner">
            <div class="carousel-item active">
                <img src="img/its.jpg" class="d-block w-100" style="height: 400px; object-fit: cover;">
            </div>
            <div class="carousel-item">
                <img src="img/its2.jpeg" class="d-block w-100" style="height: 400px; object-fit: cover;">
            </div>
            <div class="carousel-item">
                <img src="img/its3.jpeg" class="d-block w-100" style="height: 400px; object-fit: cover;">
            </div>
        </div>

        <!-- Capa de difuminado -->
        <div class="position-absolute top-0 start-0 w-100 h-100" 
             style="background: rgba(0,0,0,0.5); backdrop-filter: blur(3px);">
        </div>

        <!-- Logo en el centro por encima de la difuminación -->
        <div class="position-absolute top-50 start-50 translate-middle text-center">
            <img src="img/Logo.png" alt="Logo" style="max-height: 400px; filter: drop-shadow(0 0 10px rgba(0,0,0,0.8));">
        </div>
    </div>

    <!-- ================== PRESENTACIÓN EMPRESA ================== -->
    <div class="mb-5 text-center">
        <h3 class="mb-3 text-primary"><i class="bi bi-building"></i>Praça & CO</h3>
        <p class="lead mx-auto" style="max-width: 800px;">
Bienvenido a <strong>Praça & CO.</strong>, el Ágora del conocimiento y la comunidad, donde cada interacción es una solución. Con una profunda experiencia en el mercado y un compromiso con la sabiduría compartida, trabajamos con pasión y excelencia para alcanzar los mejores resultados junto a nuestros miembros.
        </p>
        <p class="text-muted mx-auto" style="max-width: 700px;">
      Nuestro equipo está formado por desarrolladores, diseñadores y especialistas en tecnología altamente capacitados, lo que nos permite ofrecer soluciones web integrales, modernas y adaptadas a las necesidades de cada cliente.
Creemos en la innovación, la colaboración y la mejora continua como pilares fundamentales para crear experiencias digitales de alto impacto.
        </p>
    </div>

    <!-- ================== AVISOS ================== -->
    <div class="mb-5">
        <h4 class="text-danger mb-3"><i class="bi bi-exclamation-triangle-fill"></i> Avisos Importantes</h4>
        <?php if ($avisos && $avisos->num_rows > 0): ?>
            <div class="list-group shadow-sm">
                <?php while ($aviso = $avisos->fetch_assoc()): ?>
                    <div class="list-group-item list-group-item-action d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="mb-1 fw-bold"><?= htmlspecialchars($aviso['titulo']) ?></h6>
                            <p class="mb-1"><?= nl2br(htmlspecialchars($aviso['mensaje'])) ?></p>
                            <small class="text-muted">Publicado: <?= date("d/m/Y H:i", strtotime($aviso['fecha_publicacion'])) ?></small>
                        </div>
                        <i class="bi bi-megaphone-fill text-danger fs-4"></i>
                    </div>
                <?php endwhile; ?>
            </div>
        <?php else: ?>
            <div class="alert alert-info">No hay avisos por el momento.</div>
        <?php endif; ?>
    </div>

    <!-- ================== NOTICIAS ================== -->
    <div>
        <h4 class="text-primary mb-3"><i class="bi bi-newspaper"></i> Noticias</h4>
        <?php if ($noticias && $noticias->num_rows > 0): ?>
            <div class="row row-cols-1 row-cols-md-3 g-4">
                <?php while ($noticia = $noticias->fetch_assoc()): ?>
                    <div class="col">
                        <div class="card h-100 shadow-sm border-0">
                            <?php if (!empty($noticia['imagen'])): ?>
                                <img src="data:image/jpeg;base64,<?= base64_encode($noticia['imagen']) ?>" 
                                     class="card-img-top" alt="Imagen de noticia" 
                                     style="object-fit: cover; height: 180px;">
                            <?php endif; ?>
                            <div class="card-body">
                                <h5 class="card-title"><?= htmlspecialchars($noticia['titulo']) ?></h5>
                                <p class="card-text">
                                    <?= nl2br(htmlspecialchars(substr($noticia['contenido'], 0, 150))) ?>...
                                </p>
                            </div>
                            <div class="card-footer bg-white border-0">
                                <small class="text-muted">
                                    Publicado: <?= date("d/m/Y H:i", strtotime($noticia['fecha_publicacion'])) ?>
                                </small>
                            </div>
                        </div>
                    </div>
                <?php endwhile; ?>
            </div>
        <?php else: ?>
            <div class="alert alert-info">No hay noticias publicadas aún.</div>
        <?php endif; ?>
    </div>
</div>
