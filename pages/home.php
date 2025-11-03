<?php
require_once __DIR__ . '/../backend/db_connection.php';

// Traer los avisos
$avisos = $conn->query("SELECT * FROM avisos ORDER BY fecha_publicacion DESC LIMIT 5");

// Traer las noticias
$noticias = $conn->query("SELECT * FROM noticias ORDER BY fecha_publicacion DESC LIMIT 6");
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inicio - Sistema Agora</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link rel="stylesheet" href="/Agora/Agora/css/home.css">
</head>
<body>
    <div class="container-fluid px-0">
        <!-- ================== HERO SECTION CON CAROUSEL ================== -->
        <section class="hero-section position-relative overflow-hidden mb-5">
            <div id="bannerCarousel" class="carousel slide" data-bs-ride="carousel" data-bs-interval="4000">
                <div class="carousel-indicators">
                    <button type="button" data-bs-target="#bannerCarousel" data-bs-slide-to="0" class="active"></button>
                    <button type="button" data-bs-target="#bannerCarousel" data-bs-slide-to="1"></button>
                    <button type="button" data-bs-target="#bannerCarousel" data-bs-slide-to="2"></button>
                </div>
                <div class="carousel-inner">
                    <div class="carousel-item active">
                        <img src="img/its.jpg" class="d-block w-100" alt="Instituto Tecnológico" style="height: 500px; object-fit: cover;">
                        <div class="carousel-caption d-none d-md-block">
                            <h5>Excelencia Educativa</h5>
                            <p>Formando profesionales del futuro</p>
                        </div>
                    </div>
                    <div class="carousel-item">
                        <img src="img/its2.jpeg" class="d-block w-100" alt="Campus" style="height: 500px; object-fit: cover;">
                        <div class="carousel-caption d-none d-md-block">
                            <h5>Infraestructura Moderna</h5>
                            <p>Espacios diseñados para el aprendizaje</p>
                        </div>
                    </div>
                    <div class="carousel-item">
                        <img src="img/its3.jpeg" class="d-block w-100" alt="Tecnología" style="height: 500px; object-fit: cover;">
                        <div class="carousel-caption d-none d-md-block">
                            <h5>Tecnología de Vanguardia</h5>
                            <p>Preparándonos para los desafíos del mañana</p>
                        </div>
                    </div>
                </div>
                <button class="carousel-control-prev" type="button" data-bs-target="#bannerCarousel" data-bs-slide="prev">
                    <span class="carousel-control-prev-icon" aria-hidden="true"></span>
                    <span class="visually-hidden">Anterior</span>
                </button>
                <button class="carousel-control-next" type="button" data-bs-target="#bannerCarousel" data-bs-slide="next">
                    <span class="carousel-control-next-icon" aria-hidden="true"></span>
                    <span class="visually-hidden">Siguiente</span>
                </button>
            </div>

            <!-- Overlay con gradiente y logo -->
            <div class="hero-overlay position-absolute top-0 start-0 w-100 h-100 d-flex align-items-center">
                <div class="container">
                    <div class="row align-items-center">
                        <div class="col-lg-6 text-center text-lg-start text-white">
                            <h1 class="display-4 fw-bold mb-3">Bienvenido a <span class="text-warning">Agora</span></h1>
                            <p class="lead mb-4">Plataforma de gestión integral del Instituto</p>
                            <div class="d-flex flex-wrap gap-3 justify-content-center justify-content-lg-start">
                                <a href="#about" class="btn btn-primary btn-lg px-4">
                                    <i class="bi bi-info-circle me-2"></i>Conócenos
                                </a>
                                <a href="#news" class="btn btn-outline-light btn-lg px-4">
                                    <i class="bi bi-newspaper me-2"></i>Noticias
                                </a>
                            </div>
                        </div>
                        <div class="col-lg-6 text-center mt-4 mt-lg-0">
                            <img src="img/Logo.png" alt="Logo Agora" class="hero-logo img-fluid" style="max-height: 300px; filter: drop-shadow(0 0 20px rgba(0,0,0,0.5));">
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- ================== ABOUT SECTION ================== -->
        <section id="about" class="py-5 mb-5 bg-light">
            <div class="container">
                <div class="row align-items-center">
                    <div class="col-lg-8 mx-auto text-center">
                        <span class="badge bg-primary rounded-pill px-3 py-2 mb-3">Sobre Nosotros</span>
                        <h2 class="display-5 fw-bold mb-4">Praça & CO</h2>
                        <div class="row g-4 mb-4">
                            <div class="col-md-4">
                                <div class="feature-card p-4 rounded-3 h-100 bg-white shadow-sm">
                                    <i class="bi bi-lightbulb display-6 text-primary mb-3"></i>
                                    <h5>Innovación</h5>
                                    <p class="text-muted mb-0">Soluciones tecnológicas de vanguardia</p>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="feature-card p-4 rounded-3 h-100 bg-white shadow-sm">
                                    <i class="bi bi-people display-6 text-success mb-3"></i>
                                    <h5>Colaboración</h5>
                                    <p class="text-muted mb-0">Trabajo en equipo especializado</p>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="feature-card p-4 rounded-3 h-100 bg-white shadow-sm">
                                    <i class="bi bi-graph-up-arrow display-6 text-warning mb-3"></i>
                                    <h5>Mejora Continua</h5>
                                    <p class="text-muted mb-0">Evolución y crecimiento constante</p>
                                </div>
                            </div>
                        </div>
                        <p class="lead text-muted mb-4">
                            Bienvenido a <strong class="text-primary">Praça & CO</strong>, el Ágora del conocimiento y la comunidad, 
                            donde cada interacción es una solución. Con una profunda experiencia en el mercado y un compromiso 
                            con la sabiduría compartida, trabajamos con pasión y excelencia para alcanzar los mejores resultados 
                            junto a nuestros miembros.
                        </p>
                        <p class="text-muted">
                            Nuestro equipo está formado por desarrolladores, diseñadores y especialistas en tecnología 
                            altamente capacitados, lo que nos permite ofrecer soluciones web integrales, modernas y adaptadas 
                            a las necesidades de cada cliente. Creemos en la innovación, la colaboración y la mejora continua 
                            como pilares fundamentales para crear experiencias digitales de alto impacto.
                        </p>
                    </div>
                </div>
            </div>
        </section>

        <!-- ================== AVISOS SECTION ================== -->
        <section class="mb-5">
            <div class="container">
                <div class="d-flex align-items-center mb-4">
                    <div class="bg-danger rounded p-2 me-3">
                        <i class="bi bi-exclamation-triangle-fill text-white fs-4"></i>
                    </div>
                    <div>
                        <h3 class="fw-bold mb-1">Avisos Importantes</h3>
                        <p class="text-muted mb-0">Información relevante y actualizaciones</p>
                    </div>
                </div>

                <?php if ($avisos && $avisos->num_rows > 0): ?>
                    <div class="row g-4">
                        <?php while ($aviso = $avisos->fetch_assoc()): ?>
                            <div class="col-lg-6">
                                <div class="alert-card p-4 rounded-3 border-start border-4 border-danger shadow-sm h-100">
                                    <div class="d-flex justify-content-between align-items-start mb-2">
                                        <h5 class="fw-bold text-danger mb-0"><?= htmlspecialchars($aviso['titulo']) ?></h5>
                                        <i class="bi bi-megaphone-fill text-danger fs-5"></i>
                                    </div>
                                    <p class="text-muted mb-3"><?= nl2br(htmlspecialchars($aviso['mensaje'])) ?></p>
                                    <div class="d-flex justify-content-between align-items-center">
                                        <small class="text-muted">
                                            <i class="bi bi-clock me-1"></i>
                                            <?= date("d/m/Y H:i", strtotime($aviso['fecha_publicacion'])) ?>
                                        </small>
                                        <span class="badge bg-light text-danger small">Importante</span>
                                    </div>
                                </div>
                            </div>
                        <?php endwhile; ?>
                    </div>
                <?php else: ?>
                    <div class="text-center py-5">
                        <i class="bi bi-inbox display-1 text-muted mb-3"></i>
                        <h4 class="text-muted">No hay avisos por el momento</h4>
                        <p class="text-muted">Toda la información importante aparecerá aquí</p>
                    </div>
                <?php endif; ?>
            </div>
        </section>

        <!-- ================== NOTICIAS SECTION ================== -->
        <section id="news" class="py-5 bg-light">
            <div class="container">
                <div class="text-center mb-5">
                    <span class="badge bg-primary rounded-pill px-3 py-2 mb-3">Actualidad</span>
                    <h2 class="display-5 fw-bold mb-3">Últimas Noticias</h2>
                    <p class="lead text-muted">Mantente informado sobre lo que sucede en nuestra institución</p>
                </div>

                <?php if ($noticias && $noticias->num_rows > 0): ?>
                    <div class="row row-cols-1 row-cols-md-2 row-cols-lg-3 g-4">
                        <?php while ($noticia = $noticias->fetch_assoc()): ?>
                            <div class="col">
                                <div class="card news-card h-100 border-0 shadow-sm overflow-hidden">
                                    <?php if (!empty($noticia['imagen'])): ?>
                                        <div class="card-img-container position-relative">
                                            <img src="data:image/jpeg;base64,<?= base64_encode($noticia['imagen']) ?>" 
                                                 class="card-img-top" alt="Imagen de noticia" 
                                                 style="height: 200px; object-fit: cover;">
                                            <div class="card-img-overlay-top d-flex justify-content-between align-items-start p-3">
                                                <span class="badge bg-primary">Novedad</span>
                                                <span class="badge bg-white text-dark">
                                                    <i class="bi bi-calendar me-1"></i>
                                                    <?= date("d/m/Y", strtotime($noticia['fecha_publicacion'])) ?>
                                                </span>
                                            </div>
                                        </div>
                                    <?php else: ?>
                                        <div class="card-img-container position-relative bg-light" style="height: 200px;">
                                            <div class="card-img-placeholder w-100 h-100 d-flex align-items-center justify-content-center">
                                                <i class="bi bi-newspaper display-4 text-muted"></i>
                                            </div>
                                            <div class="card-img-overlay-top d-flex justify-content-between align-items-start p-3">
                                                <span class="badge bg-primary">Novedad</span>
                                                <span class="badge bg-white text-dark">
                                                    <i class="bi bi-calendar me-1"></i>
                                                    <?= date("d/m/Y", strtotime($noticia['fecha_publicacion'])) ?>
                                                </span>
                                            </div>
                                        </div>
                                    <?php endif; ?>
                                    <div class="card-body d-flex flex-column">
                                        <h5 class="card-title fw-bold"><?= htmlspecialchars($noticia['titulo']) ?></h5>
                                        <p class="card-text text-muted flex-grow-1">
                                            <?= nl2br(htmlspecialchars(substr($noticia['contenido'], 0, 120))) ?>...
                                        </p>
                                        <div class="mt-auto">
                                            <a href="#" class="btn btn-outline-primary btn-sm stretched-link">
                                                Leer más <i class="bi bi-arrow-right ms-1"></i>
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endwhile; ?>
                    </div>
                    
                    <div class="text-center mt-5">
                        <a href="#" class="btn btn-primary px-4">
                            <i class="bi bi-archive me-2"></i>Ver todas las noticias
                        </a>
                    </div>
                <?php else: ?>
                    <div class="text-center py-5">
                        <i class="bi bi-newspaper display-1 text-muted mb-3"></i>
                        <h4 class="text-muted">No hay noticias publicadas</h4>
                        <p class="text-muted">Próximamente encontrarás las últimas novedades aquí</p>
                    </div>
                <?php endif; ?>
            </div>
        </section>

        <!-- ================== FOOTER SECTION ================== -->
        <footer class="bg-dark text-white py-4 mt-5">
            <div class="container">
                <div class="row align-items-center">
                    <div class="col-md-6">
                        <img src="img/Logo.png" alt="Logo Agora" style="height: 40px;" class="me-2">
                        <span class="fw-bold">Sistema Agora</span>
                    </div>
                    <div class="col-md-6 text-md-end">
                        <small>&copy; 2025 Praça & CO. Todos los derechos reservados.</small>
                    </div>
                </div>
            </div>
        </footer>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="/Agora/Agora/assets/home.js"></script>
</body>
</html>