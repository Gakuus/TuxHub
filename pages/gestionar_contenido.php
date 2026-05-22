<?php
require_once __DIR__ . '/../backend/auth_admin.php';
require_once __DIR__ . '/../backend/db_connection.php';
require_once __DIR__ . '/../backend/helpers.php';

$tipo = $_GET['tipo'] ?? 'noticias';
$tipo_attr = htmlspecialchars($tipo, ENT_QUOTES, 'UTF-8');
$tipo_url = urlencode($tipo);
$tabla = $tipo === 'avisos' ? 'avisos' : 'noticias';
$csrf_param = '&csrf_token=' . urlencode(csrf_token());
$busqueda = trim($_GET['q'] ?? '');
$q_param = !empty($busqueda) ? '&q=' . urlencode($busqueda) : '';

// Paginación
$current_page = max(1, (int)($_GET['page'] ?? 1));
$per_page = 15;

$where_search = '';
$search_param = '';
if (!empty($busqueda)) {
    $where_search = " WHERE titulo LIKE ? ";
    $search_param = '%' . $busqueda . '%';
}

$stmt_count = $conn->prepare("SELECT COUNT(*) AS total FROM $tabla $where_search");
if (!empty($busqueda)) $stmt_count->bind_param("s", $search_param);
$stmt_count->execute();
$total = (int)$stmt_count->get_result()->fetch_assoc()['total'];
$stmt_count->close();
$paginfo = paginate($total, $current_page, $per_page);

// Cargar listado paginado
$stmt_list = $conn->prepare("SELECT * FROM $tabla $where_search ORDER BY fecha_publicacion DESC LIMIT ? OFFSET ?");
if (!empty($busqueda)) {
    $stmt_list->bind_param("sii", $search_param, $per_page, $paginfo['offset']);
} else {
    $stmt_list->bind_param("ii", $per_page, $paginfo['offset']);
}
$stmt_list->execute();
$items = $stmt_list->get_result();

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

<section class="gestion-contenido">
  <div class="page-header">
    <h2>
      <i class="bi bi-<?= $tipo === 'avisos' ? 'exclamation-triangle' : 'newspaper' ?>"></i>
      Administrar <?= ucfirst($tipo_attr) ?>
    </h2>
    <div class="header-actions">
      <a href="dashboard.php?page=gestionar_contenido&tipo=noticias" class="btn btn-<?= $tipo === 'noticias' ? 'primary' : 'outline-secondary' ?> btn-sm">
        <i class="bi bi-newspaper"></i> Noticias
      </a>
      <a href="dashboard.php?page=gestionar_contenido&tipo=avisos" class="btn btn-<?= $tipo === 'avisos' ? 'primary' : 'outline-secondary' ?> btn-sm">
        <i class="bi bi-exclamation-triangle"></i> Avisos
      </a>
    </div>
  </div>

  <?php if (isset($_GET['success'])): ?>
    <div class="alert alert-success alert-dismissible fade show" role="alert">
      <i class="bi bi-check-circle"></i> ¡Guardado correctamente!
      <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
  <?php endif; ?>

  <div class="card mb-4">
    <div class="card-header">
      <i class="bi bi-<?= $id ? 'pencil-square' : 'plus-circle' ?>"></i>
      <?= $id ? "Editar" : "Nueva" ?> <?= ucfirst($tipo_attr) ?>
    </div>
    <div class="card-body">
      <form method="POST" action="backend/save_contenido.php" enctype="multipart/form-data">
        <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
        <input type="hidden" name="tipo" value="<?= $tipo_attr ?>">
        <input type="hidden" name="id" value="<?= $item['id'] ?? '' ?>">
        <input type="hidden" name="redirect_to" value="dashboard.php?page=gestionar_contenido&tipo=<?= $tipo_url ?>">

        <div class="mb-3">
          <label class="form-label">Título (máximo 24 caracteres)</label>
          <input type="text" name="titulo" class="form-control" required
                 maxlength="24"
                 value="<?= htmlspecialchars($item['titulo'] ?? '') ?>"
                 oninput="document.getElementById('titulo-counter').textContent=this.value.length">
          <div class="form-text text-muted">
            <span id="titulo-counter"><?= strlen($item['titulo'] ?? '') ?></span>/24 caracteres
          </div>
        </div>

        <?php if ($tipo === 'noticias'): ?>
          <div class="mb-3">
            <label class="form-label">Contenido (máximo 255 caracteres)</label>
            <textarea name="contenido" rows="5" class="form-control" required
                      maxlength="255"
                      oninput="document.getElementById('contenido-counter').textContent=this.value.length"><?= htmlspecialchars($item['contenido'] ?? '') ?></textarea>
            <div class="form-text text-muted">
              <span id="contenido-counter"><?= strlen($item['contenido'] ?? '') ?></span>/255 caracteres
            </div>
          </div>

          <div class="mb-3">
            <label class="form-label">Imagen</label>
            <div class="file-input-glass" style="position:relative;border:2px dashed rgba(13,110,253,0.25);border-radius:12px;padding:2rem 1rem;text-align:center;background:rgba(13,110,253,0.03);cursor:pointer;transition:all .3s ease;">
              <i class="bi bi-cloud-arrow-up fs-1 text-primary mb-2" style="display:block;"></i>
              <p class="mb-1 fw-medium">Haz clic para seleccionar una imagen</p>
              <small class="text-muted">Formatos: JPG, PNG, GIF, WebP</small>
              <input type="file" name="imagen_file" class="form-control" accept="image/jpeg,image/jpg,image/png,image/gif,image/webp" onchange="validateImage(this);previewImage(this)" style="position:absolute;inset:0;opacity:0;cursor:pointer;">
            </div>
            <div class="preview-img-container mt-2 text-center"></div>
          </div>

          <?php if (!empty($item['imagen_ruta'])): ?>
            <div class="mb-3">
              <p class="mb-1 text-muted small">Imagen actual:</p>
              <img src="<?= htmlspecialchars($item['imagen_ruta'], ENT_QUOTES, 'UTF-8') ?>"
                   alt="Imagen" class="rounded-2 shadow-sm" style="max-height:150px;">
            </div>
          <?php endif; ?>

        <?php else: ?>
          <div class="mb-3">
            <label class="form-label">Mensaje (máximo 255 caracteres)</label>
            <textarea name="mensaje" rows="3" class="form-control" required
                      maxlength="255"
                      oninput="document.getElementById('mensaje-counter').textContent=this.value.length"><?= htmlspecialchars($item['mensaje'] ?? '') ?></textarea>
            <div class="form-text text-muted">
              <span id="mensaje-counter"><?= strlen($item['mensaje'] ?? '') ?></span>/255 caracteres
            </div>
          </div>
        <?php endif; ?>

        <button type="submit" class="btn btn-success"><i class="bi bi-save"></i> Guardar</button>
        <a href="dashboard.php?page=gestionar_contenido&tipo=<?= $tipo_url ?>" class="btn btn-outline-secondary">Cancelar</a>
      </form>
    </div>
  </div>

  <div class="row mb-3">
    <div class="col-md-6 col-lg-4">
      <div class="input-group">
        <span class="input-group-text bg-transparent"><i class="bi bi-search"></i></span>
        <input type="text" id="searchContenido" class="form-control" placeholder="Buscar por título..."
               value="<?= htmlspecialchars($busqueda, ENT_QUOTES, 'UTF-8') ?>"
               autocomplete="off">
        <?php if (!empty($busqueda)): ?>
        <a href="dashboard.php?page=gestionar_contenido&tipo=<?= $tipo_url ?>" class="btn btn-outline-secondary"><i class="bi bi-x-lg"></i></a>
        <?php endif; ?>
      </div>
    </div>
  </div>

  <div class="card">
    <div class="card-header"><i class="bi bi-list-ul"></i> Listado de <?= ucfirst($tipo_attr) ?></div>
    <div class="card-body p-0">
      <div class="table-responsive">
        <table class="table table-striped table-hover align-middle text-center mb-0">
          <thead>
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
                <td class="text-start"><?= htmlspecialchars($row['titulo']) ?></td>
                <td><span class="badge bg-light text-dark"><?= date("d/m/Y H:i", strtotime($row['fecha_publicacion'])) ?></span></td>
                <td>
                  <div class="d-flex gap-1 justify-content-center">
                    <a href="dashboard.php?page=gestionar_contenido&tipo=<?= $tipo_url ?>&id=<?= $row['id'] ?>" class="btn btn-warning btn-sm">
                      <i class="bi bi-pencil"></i> Editar
                    </a>
                    <a href="backend/delete_contenido.php?tipo=<?= $tipo_url ?>&id=<?= $row['id'] ?><?= $csrf_param ?>"
                       class="btn btn-danger btn-sm"
                       onclick="return confirm('¿Eliminar este registro?');">
                      <i class="bi bi-trash"></i> Eliminar
                    </a>
                  </div>
                </td>
              </tr>
            <?php endwhile; ?>
          </tbody>
        </table>
      </div>
    </div>
    <div class="card-footer">
      <?= render_pagination($paginfo, 'dashboard.php?page=gestionar_contenido&tipo=' . $tipo_url . $q_param) ?>
    </div>
  </div>
</section>

<script src="assets/gestionar_contenido.js"></script>
<script>
document.addEventListener('DOMContentLoaded', () => {
    const input = document.getElementById('searchContenido');
    if (!input) return;
    let timer = null;
    input.addEventListener('input', () => {
        clearTimeout(timer);
        timer = setTimeout(() => {
            const q = input.value.trim();
            const params = new URLSearchParams(window.location.search);
            params.set('page', 'gestionar_contenido');
            if (q) { params.set('q', q); } else { params.delete('q'); }
            params.delete('page');
            window.location.href = 'dashboard.php?page=gestionar_contenido&' + params.toString();
        }, 400);
    });
});
</script>
