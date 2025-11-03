<?php
require_once __DIR__ . '/../backend/auth_admin.php';
require_once __DIR__ . '/../backend/db_connection.php';

$tipo = $_GET['tipo'] ?? 'noticias'; // noticias o avisos
$tabla = $tipo === 'avisos' ? 'avisos' : 'noticias';

// Cargar listado
$items = $conn->query("SELECT * FROM $tabla ORDER BY fecha_publicacion DESC");

// Si hay edición
$id = $_GET['id'] ?? null;
$item = null;
if ($id) {
    $stmt = $conn->prepare("SELECT * FROM $tabla WHERE id=?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $item = $stmt->get_result()->fetch_assoc();
    $stmt->close();
}
?>

<div class="container mt-4">
  <h2>
    <?php if ($tipo === 'avisos'): ?>
      <i class="bi bi-exclamation-triangle"></i> Administrar Avisos
    <?php else: ?>
      <i class="bi bi-newspaper"></i> Administrar Noticias
    <?php endif; ?>
  </h2>

  <!-- Mensaje de éxito -->
  <?php if (isset($_GET['success'])): ?>
    <div class="alert alert-success alert-dismissible fade show mt-3" role="alert">
      <i class="bi bi-check-circle"></i> ¡Guardado correctamente!
      <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
  <?php endif; ?>

  <!-- Formulario -->
  <div class="card my-4 shadow-sm">
    <div class="card-body">
      <h5 class="card-title"><?= $id ? "Editar" : "Nueva" ?> <?= ucfirst($tipo) ?></h5>
      <form method="POST" action="../Agora/backend/save_contenido.php" enctype="multipart/form-data">
        <input type="hidden" name="tipo" value="<?= $tipo ?>">
        <input type="hidden" name="id" value="<?= $item['id'] ?? '' ?>">
        <!-- Campo oculto para redirección -->
        <input type="hidden" name="redirect_to" value="dashboard.php?page=gestionar_contenido&tipo=<?= $tipo ?>">

        <div class="mb-3">
          <label class="form-label">Título (máximo 24 caracteres)</label>
          <input type="text" name="titulo" class="form-control" required 
                 maxlength="24" 
                 value="<?= htmlspecialchars($item['titulo'] ?? '') ?>">
          <div class="form-text text-muted">
            <span id="titulo-counter">0</span>/24 caracteres
          </div>
        </div>

        <?php if ($tipo === 'noticias'): ?>
          <div class="mb-3">
            <label class="form-label">Contenido (máximo 255 caracteres)</label>
            <textarea name="contenido" rows="5" class="form-control" required 
                      maxlength="255"><?= htmlspecialchars($item['contenido'] ?? '') ?></textarea>
            <div class="form-text text-muted">
              <span id="contenido-counter">0</span>/255 caracteres
            </div>
          </div>

          <div class="mb-3">
            <label class="form-label">Imagen (solo archivos de imagen)</label>
            <input type="file" name="imagen_file" class="form-control" 
                   accept="image/jpeg,image/jpg,image/png,image/gif,image/webp"
                   onchange="validateImage(this)">
            <div class="form-text text-muted">
              Formatos permitidos: JPG, PNG, GIF, WebP
            </div>
          </div>

          <?php if (!empty($item['imagen'])): ?>
            <div class="mb-3">
              <p>Imagen actual:</p>
              <img src="data:image/jpeg;base64,<?= base64_encode($item['imagen']) ?>" 
                   alt="Imagen" class="img-fluid rounded shadow-sm" style="max-height:150px;">
            </div>
          <?php endif; ?>

        <?php else: ?>
          <div class="mb-3">
            <label class="form-label">Mensaje (máximo 255 caracteres)</label>
            <textarea name="mensaje" rows="3" class="form-control" required 
                      maxlength="255"><?= htmlspecialchars($item['mensaje'] ?? '') ?></textarea>
            <div class="form-text text-muted">
              <span id="mensaje-counter">0</span>/255 caracteres
            </div>
          </div>
        <?php endif; ?>

        <button type="submit" class="btn btn-success"><i class="bi bi-save"></i> Guardar</button>
        <a href="dashboard.php?page=gestionar_contenido&tipo=<?= $tipo ?>" class="btn btn-secondary">Cancelar</a>
      </form>
    </div>
  </div>

  <!-- Listado -->
  <div class="table-responsive">
    <table class="table table-striped align-middle text-center">
      <thead class="table-dark">
        <tr>
          <th>ID</th>
          <th>Título</th>
          <th>Fecha</th>
          <th>Acciones</th>
        </tr>
      </thead>
      <tbody>
        <?php while ($row = $items->fetch_assoc()): ?>
          <tr>
            <td><?= $row['id'] ?></td>
            <td><?= htmlspecialchars($row['titulo']) ?></td>
            <td><?= date("d/m/Y H:i", strtotime($row['fecha_publicacion'])) ?></td>
            <td>
              <a href="dashboard.php?page=gestionar_contenido&tipo=<?= $tipo ?>&id=<?= $row['id'] ?>" class="btn btn-warning btn-sm">
                <i class="bi bi-pencil"></i> Editar
              </a>
              <a href="../Agora/backend/delete_contenido.php?tipo=<?= $tipo ?>&id=<?= $row['id'] ?>&redirect_to=<?= urlencode('dashboard.php?page=gestionar_contenido&tipo=' . $tipo) ?>" 
                 class="btn btn-danger btn-sm" 
                 onclick="return confirm('¿Eliminar este registro?');">
                <i class="bi bi-trash"></i> Eliminar
              </a>
            </td>
          </tr>
        <?php endwhile; ?>
      </tbody>
    </table>
  </div>
</div>

<!-- Incluir archivos CSS y JS externos -->
<link rel="stylesheet" href="/Agora/Agora/css/gestionar_contenido.css">
<script src="/Agora/Agora/assets/gestionar_contenido.js"></script>