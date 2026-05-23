<?php
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/../backend/db_connection.php';
require_once __DIR__ . '/../backend/helpers.php';

// ==============================
// 0. Validar sesión
// ==============================
if (!isset($_SESSION['user_id'])) {
    header("Location: ../index.html?error=requerido");
    exit();
}

$user_id = (int)$_SESSION['user_id'];
$rol = strtolower(trim($_SESSION['rol'] ?? 'invitado'));
$user_name = $_SESSION['user_name'] ?? 'Usuario';
$recursos_disponibles = ['television', 'computadoras', 'pizarra', 'proyector', 'aire_acondicionado'];

// ==============================
// 1. Obtener grupos del profesor o admin
// ==============================
$grupos_profesor = [];
$grupo_seleccionado = null;
$horarios_grupo = [];

if (in_array($rol, ['profesor', 'admin', 'administrador'])) {

    $sql_grupos = "
        SELECT g.id, g.nombre, g.turno
        FROM grupos g
        INNER JOIN grupos_profesores gp ON gp.grupo_id = g.id
        WHERE gp.profesor_id = ?
        ORDER BY g.turno, g.nombre
    ";
    $stmt = $conn->prepare($sql_grupos);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $grupos_profesor = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();

    // Guardar selección de grupo
    if (isset($_POST['grupo_id'])) {
        $_SESSION['grupo_seleccionado'] = (int)$_POST['grupo_id'];
    }
    $grupo_seleccionado = $_SESSION['grupo_seleccionado'] ?? ($grupos_profesor[0]['id'] ?? null);

    // ==============================
    // 2. Obtener horarios del grupo (puede tener varios bloques)
    // ==============================
    if ($grupo_seleccionado) {
        $sql_h = "
            SELECT 
                h.id,
                d.nombre_dia AS dia,
                b.hora_inicio,
                b.hora_fin,
                m.nombre_materia AS materia,
                s.nombre_salon
            FROM horarios h
            INNER JOIN dias d ON h.dia_id = d.id
            INNER JOIN bloques_horarios b ON h.bloque_id = b.id
            INNER JOIN materias m ON h.materia_id = m.id
            LEFT JOIN salones s ON h.salon_id = s.id
            WHERE h.grupo_id = ? AND h.profesor_id = ?
            ORDER BY d.id, b.hora_inicio
        ";
        $stmt = $conn->prepare($sql_h);
        $stmt->bind_param("ii", $grupo_seleccionado, $user_id);
        $stmt->execute();
        $horarios_grupo = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
    }
}

// ==============================
// 3. Procesar acciones POST
// ==============================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['accion'], $_POST['salon_id'])) {
    csrf_verify();
    $salon_id = (int)$_POST['salon_id'];
    $accion = $_POST['accion'];

    if (!in_array($rol, ['profesor', 'admin', 'administrador'])) exit();

    // === MARCAR EN USO ===
    if ($accion === 'marcar_uso') {
        $grupo_id = (int)($_POST['grupo_id'] ?? 0);
        $bloques = $_POST['bloques'] ?? [];

        // Validar que haya al menos un bloque seleccionado
        if (empty($bloques)) {
            $_SESSION['error'] = "Debes seleccionar al menos un bloque horario.";
            echo "<script>window.location.href = '" . 'dashboard.php?page=salones' . "';</script>";
            exit();
        }

        foreach ($bloques as $horario_id) {
            $hora_inicio = null;
            $hora_fin = null;

            $stmt = $conn->prepare("
                SELECT b.hora_inicio, b.hora_fin 
                FROM horarios h
                INNER JOIN bloques_horarios b ON h.bloque_id = b.id
                WHERE h.id = ?
            ");
            $stmt->bind_param("i", $horario_id);
            $stmt->execute();
            $stmt->bind_result($hora_inicio, $hora_fin);
            $stmt->fetch();
            $stmt->close();

            if ($hora_inicio && $hora_fin) {
                $stmt = $conn->prepare("
                    INSERT INTO salon_usos (salon_id, profesor_id, grupo_id, fecha, hora_inicio, hora_fin, estado)
                    VALUES (?, ?, ?, CURDATE(), ?, ?, 'en_uso')
                ");
                $stmt->bind_param("iiiss", $salon_id, $user_id, $grupo_id, $hora_inicio, $hora_fin);
                $stmt->execute();
                $stmt->close();
            }
        }

        $stmt_upd = $conn->prepare("UPDATE salones SET estado='ocupado' WHERE id=?");
        $stmt_upd->bind_param("i", $salon_id);
        $stmt_upd->execute();
        $stmt_upd->close();
        
        echo "<script>window.location.href = '" . 'dashboard.php?page=salones' . "';</script>";
        exit();
    }

    // === LIBERAR (profesor) ===
    if ($accion === 'liberar') {
        $stmt = $conn->prepare("
            UPDATE salon_usos 
            SET estado='finalizado'
            WHERE salon_id=? AND profesor_id=? AND estado='en_uso'
        ");
        $stmt->bind_param("ii", $salon_id, $user_id);
        $stmt->execute();
        $stmt->close();

        $stmt_upd = $conn->prepare("UPDATE salones SET estado='disponible' WHERE id=?");
        $stmt_upd->bind_param("i", $salon_id);
        $stmt_upd->execute();
        $stmt_upd->close();
        
        echo "<script>window.location.href = '" . 'dashboard.php?page=salones' . "';</script>";
        exit();
    }

    // === DESMARCAR USO (admin) ===
    if ($accion === 'desmarcar_uso' && in_array($rol, ['admin','administrador'])) {
        $stmt = $conn->prepare("
            UPDATE salon_usos 
            SET estado='finalizado'
            WHERE salon_id=? AND estado='en_uso'
        ");
        $stmt->bind_param("i", $salon_id);
        $stmt->execute();
        $stmt->close();

        $stmt_upd = $conn->prepare("UPDATE salones SET estado='disponible' WHERE id=?");
        $stmt_upd->bind_param("i", $salon_id);
        $stmt_upd->execute();
        $stmt_upd->close();
        
        echo "<script>window.location.href = '" . 'dashboard.php?page=salones' . "';</script>";
        exit();
    }

    // === EDITAR SALÓN (admins y profesores) ===
    if ($accion === 'editar' && in_array($rol, ['admin','administrador','profesor'])) {
        $nombre = trim($_POST['nombre_salon']);
        $capacidad = (int)$_POST['capacidad'];
        $ubicacion = trim($_POST['ubicacion']);
        $observaciones = trim($_POST['observaciones']);
        $recursos_sel = $_POST['recursos'] ?? [];

        // Para profesores, solo permitir editar observaciones
        if ($rol === 'profesor') {
            $stmt = $conn->prepare("
                UPDATE salones 
                SET observaciones=? 
                WHERE id=?
            ");
            $stmt->bind_param("si", $observaciones, $salon_id);
        } else {
            // Para admins, permitir editar todos los campos
            $stmt = $conn->prepare("
                UPDATE salones 
                SET nombre_salon=?, capacidad=?, ubicacion=?, observaciones=? 
                WHERE id=?
            ");
            $stmt->bind_param("sissi", $nombre, $capacidad, $ubicacion, $observaciones, $salon_id);

            $stmt->execute();
            $stmt->close();

            // Actualizar recursos en salon_recursos
            $stmt = $conn->prepare("DELETE FROM salon_recursos WHERE salon_id=?");
            $stmt->bind_param("i", $salon_id);
            $stmt->execute();
            $stmt->close();

            if (!empty($recursos_sel)) {
                $stmt = $conn->prepare("INSERT INTO salon_recursos (salon_id, recurso, cantidad) VALUES (?, ?, 1)");
                foreach ($recursos_sel as $r) {
                    if (in_array($r, $recursos_disponibles)) {
                        $stmt->bind_param("is", $salon_id, $r);
                        $stmt->execute();
                    }
                }
                $stmt->close();
            }

            echo "<script>window.location.href = '" . 'dashboard.php?page=salones' . "';</script>";
            exit();
        }
        
        $stmt->execute();
        $stmt->close();
        
        echo "<script>window.location.href = '" . 'dashboard.php?page=salones' . "';</script>";
        exit();
    }

    // === ELIMINAR SALÓN (solo admins) ===
    if ($accion === 'eliminar' && in_array($rol, ['admin','administrador'])) {
        $stmt = $conn->prepare("
            SELECT COUNT(*) as usos_activos 
            FROM salon_usos 
            WHERE salon_id=? AND estado='en_uso'
        ");
        $stmt->bind_param("i", $salon_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $usos_activos = $result->fetch_assoc()['usos_activos'];
        $stmt->close();

        if ($usos_activos > 0) {
            $_SESSION['error'] = "No se puede eliminar el salón porque tiene usos activos. Primero desmarca el uso.";
            echo "<script>window.location.href = '" . 'dashboard.php?page=salones' . "';</script>";
            exit();
        } else {
            $stmt = $conn->prepare("DELETE FROM salon_usos WHERE salon_id=?");
            $stmt->bind_param("i", $salon_id);
            $stmt->execute();
            $stmt->close();

            $stmt = $conn->prepare("DELETE FROM salones WHERE id=?");
            $stmt->bind_param("i", $salon_id);
            $stmt->execute();
            $stmt->close();
            
            echo "<script>window.location.href = '" . 'dashboard.php?page=salones' . "';</script>";
            exit();
        }
    }
}

// ==============================
// 4. Consulta principal de salones (último uso activo)
// ==============================
$sql = "
    SELECT 
        s.id AS salon_id,
        s.nombre_salon,
        s.capacidad,
        s.estado,
        s.ubicacion,
        s.observaciones,
        g.nombre AS grupo_actual,
        u.nombre AS profesor_actual,
        su.hora_inicio,
        su.hora_fin
    FROM salones s
    LEFT JOIN (
        SELECT su1.*
        FROM salon_usos su1
        WHERE su1.estado='en_uso'
    ) su ON su.salon_id = s.id
    LEFT JOIN grupos g ON su.grupo_id = g.id
    LEFT JOIN usuarios u ON su.profesor_id = u.id
    ORDER BY s.nombre_salon
";
$result = $conn->query($sql);

// Fetch recursos from salon_recursos
$recursos_por_salon = [];
$rec_res = $conn->query("SELECT salon_id, recurso FROM salon_recursos ORDER BY salon_id, recurso");
if ($rec_res) {
    while ($rr = $rec_res->fetch_assoc()) {
        $recursos_por_salon[$rr['salon_id']][] = $rr['recurso'];
    }
}
?>

<div class="salones-section">
    <div class="page-header">
        <h2>
            <i class="bi bi-building"></i>
            Gestión de Salones
        </h2>
        <div class="header-actions">
            <span class="status-badge available">
                <i class="bi bi-person-circle"></i>
                <?= htmlspecialchars($user_name, ENT_QUOTES, 'UTF-8') ?> • <?= htmlspecialchars(ucfirst($rol), ENT_QUOTES, 'UTF-8') ?>
            </span>
        </div>
    </div>

    <!-- Mensajes de estado -->
    <?php if (!empty($_SESSION['error'])): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="bi bi-exclamation-triangle-fill me-2"></i>
            <?= htmlspecialchars($_SESSION['error'] ?? '', ENT_QUOTES, 'UTF-8'); unset($_SESSION['error']); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <?php if (!empty($_SESSION['success'])): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="bi bi-check-circle-fill me-2"></i>
            <?= htmlspecialchars($_SESSION['success'] ?? '', ENT_QUOTES, 'UTF-8'); unset($_SESSION['success']); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <!-- Selector de Grupo para Profesores -->
    <?php if (in_array($rol, ['profesor','admin','administrador']) && !empty($grupos_profesor)): ?>
        <div class="selection-panel">
            <div class="filter-label">
                <i class="bi bi-people-fill"></i>Seleccionar Grupo
            </div>
            <form method="POST">
                <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                <div class="filter-buttons">
                    <select name="grupo_id" class="form-select" onchange="this.form.submit()" style="max-width: 300px;">
                        <?php foreach ($grupos_profesor as $g): ?>
                            <option value="<?= htmlspecialchars($g['id'], ENT_QUOTES, 'UTF-8') ?>" <?= $g['id']==$grupo_seleccionado?'selected':'' ?>>
                                <?= htmlspecialchars($g['nombre'], ENT_QUOTES, 'UTF-8') ?> • <?= htmlspecialchars(ucfirst($g['turno']), ENT_QUOTES, 'UTF-8') ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </form>
        </div>
    <?php endif; ?>

    <!-- Grid de Salones -->
    <div class="cards-grid">
        <?php if ($result->num_rows > 0): ?>
            <?php while ($row = $result->fetch_assoc()): 
                $recursos_list = $recursos_por_salon[$row['salon_id']] ?? [];
            ?>
            <div class="salon-card card">
                <div class="salon-header">
                    <div>
                        <h3 class="salon-name"><?= htmlspecialchars($row['nombre_salon'], ENT_QUOTES, 'UTF-8') ?></h3>
                        <div class="info-item">
                            <i class="bi bi-geo-alt"></i>
                            <span><?= htmlspecialchars($row['ubicacion'] ?? 'Sin ubicación', ENT_QUOTES, 'UTF-8') ?></span>
                        </div>
                    </div>
                    <span class="status-badge <?= $row['estado'] === 'disponible' ? 'available' : 'occupied' ?>">
                        <i class="bi bi-<?= $row['estado'] === 'disponible' ? 'check-circle' : 'x-circle' ?>"></i>
                        <?= htmlspecialchars(ucfirst($row['estado']), ENT_QUOTES, 'UTF-8') ?>
                    </span>
                </div>

                <div class="salon-info">
                    <div class="info-item">
                        <i class="bi bi-people"></i>
                        <span>Capacidad: <strong><?= (int)$row['capacidad'] ?></strong> personas</span>
                    </div>
                    
                    <?php if ($row['estado'] === 'ocupado'): ?>
                        <div class="info-item">
                            <i class="bi bi-person-check"></i>
                            <span>Profesor: <strong><?= htmlspecialchars($row['profesor_actual'] ?? 'No asignado', ENT_QUOTES, 'UTF-8') ?></strong></span>
                        </div>
                        <div class="info-item">
                            <i class="bi bi-mortarboard"></i>
                            <span>Grupo: <strong><?= htmlspecialchars($row['grupo_actual'] ?? 'No asignado', ENT_QUOTES, 'UTF-8') ?></strong></span>
                        </div>
                        <div class="info-item">
                            <i class="bi bi-clock"></i>
                            <span>Horario: <strong><?= htmlspecialchars($row['hora_inicio'] ?? '-', ENT_QUOTES, 'UTF-8') ?> - <?= htmlspecialchars($row['hora_fin'] ?? '-', ENT_QUOTES, 'UTF-8') ?></strong></span>
                        </div>
                    <?php endif; ?>

                    <?php if (!empty($recursos_list)): ?>
                        <div class="info-item">
                            <i class="bi bi-tools"></i>
                            <span>Recursos disponibles:</span>
                        </div>
                        <div class="resources-grid">
                            <?php foreach ($recursos_list as $recurso): ?>
                                <span class="resource-tag">
                                    <i class="bi bi-<?= 
                                        $recurso === 'television' ? 'tv' : 
                                        ($recurso === 'computadoras' ? 'pc-display' : 
                                        ($recurso === 'pizarra' ? 'easel' : 
                                        ($recurso === 'proyector' ? 'projector' : 
                                        ($recurso === 'aire_acondicionado' ? 'snow' : 'circle')))) 
                                    ?>"></i>
                                    <?= htmlspecialchars(ucfirst(str_replace('_', ' ', $recurso)), ENT_QUOTES, 'UTF-8') ?>
                                </span>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>

                    <?php if (!empty($row['observaciones'])): ?>
                        <div class="info-item">
                            <i class="bi bi-chat-left-text"></i>
                            <span><em><?= htmlspecialchars($row['observaciones'], ENT_QUOTES, 'UTF-8') ?></em></span>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Acciones para Profesores y Admins -->
                <?php if (in_array($rol, ['profesor','admin','administrador'])): ?>
                    <div class="action-row">
                        <?php if ($row['estado'] === 'disponible'): ?>
                            <form method="POST" class="w-100">
                                <input type="hidden" name="salon_id" value="<?= (int)$row['salon_id'] ?>">
                                <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                                <input type="hidden" name="accion" value="marcar_uso">
                                <?php if ($grupo_seleccionado): ?>
                                    <input type="hidden" name="grupo_id" value="<?= (int)$grupo_seleccionado ?>">
                                    <?php if (count($horarios_grupo) > 0): ?>
                                        <div class="mb-3">
                                            <label class="form-label fw-semibold">
                                                <i class="bi bi-clock me-1"></i>Seleccionar horarios:
                                            </label>
                                            <div class="time-slots">
                                                <?php foreach ($horarios_grupo as $h): ?>
                                                    <label class="time-slot-item">
                                                        <input type="checkbox" name="bloques[]" value="<?= htmlspecialchars($h['id'], ENT_QUOTES, 'UTF-8') ?>" class="me-2"> 
                                                        <?= htmlspecialchars($h['dia'], ENT_QUOTES, 'UTF-8') ?> • 
                                                        <?= htmlspecialchars(substr($h['hora_inicio'],0,5), ENT_QUOTES, 'UTF-8') ?>-<?= htmlspecialchars(substr($h['hora_fin'],0,5), ENT_QUOTES, 'UTF-8') ?> • 
                                                        <?= htmlspecialchars($h['materia'], ENT_QUOTES, 'UTF-8') ?>
                                                    </label>
                                                <?php endforeach; ?>
                                            </div>
                                        </div>
                                    <?php else: ?>
                                        <p class="text-muted small">
                                            <i class="bi bi-info-circle me-1"></i>
                                            No hay horarios cargados para este grupo.
                                        </p>
                                    <?php endif; ?>
                                <?php endif; ?>
                                <button type="submit" class="btn btn-primary w-100">
                                    <i class="bi bi-play-circle me-1"></i>Marcar en Uso
                                </button>
                            </form>
                        <?php elseif ($row['estado'] === 'ocupado' && $row['profesor_actual'] === $user_name): ?>
                            <form method="POST" class="w-100">
                                <input type="hidden" name="salon_id" value="<?= (int)$row['salon_id'] ?>">
                                <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                                <input type="hidden" name="accion" value="liberar">
                                <button type="submit" class="btn btn-success w-100">
                                    <i class="bi bi-stop-circle me-1"></i>Liberar Salón
                                </button>
                            </form>
                        <?php endif; ?>
                    </div>

                    <!-- Panel de Edición -->
                    <div class="card-body">
                        <h6 class="fw-bold mb-3">
                            <i class="bi bi-pencil-square me-2 text-warning"></i>
                            <?= $rol === 'profesor' ? 'Agregar Observaciones' : 'Panel de Administración' ?>
                        </h6>
                        
                        <?php if (in_array($rol, ['admin','administrador'])): ?>
                            <div class="action-row">
                                <?php if ($row['estado'] === 'ocupado'): ?>
                                    <form method="POST" class="w-100 mb-2">
                                        <input type="hidden" name="salon_id" value="<?= (int)$row['salon_id'] ?>">
                                        <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                                        <input type="hidden" name="accion" value="desmarcar_uso">
                                        <button type="submit" class="btn btn-secondary w-100" 
                                                onclick="return confirm('¿Estás seguro de desmarcar el uso de este salón?')">
                                            <i class="bi bi-x-circle me-1"></i>Desmarcar Uso
                                        </button>
                                    </form>
                                <?php endif; ?>

                                <form method="POST" class="w-100 mb-2">
                                    <input type="hidden" name="salon_id" value="<?= (int)$row['salon_id'] ?>">
                                    <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                                    <input type="hidden" name="accion" value="eliminar">
                                    <button type="submit" class="btn btn-danger w-100"
                                            onclick="return confirm('¿ESTÁS SEGURO? Esta acción eliminará permanentemente el salón.')">
                                        <i class="bi bi-trash me-1"></i>Eliminar Salón
                                    </button>
                                </form>
                            </div>
                        <?php endif; ?>

                        <form method="POST" class="mt-3">
                            <input type="hidden" name="salon_id" value="<?= (int)$row['salon_id'] ?>">
                            <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                            <input type="hidden" name="accion" value="editar">
                            
                            <?php if (in_array($rol, ['admin','administrador'])): ?>
                                <div class="row g-2">
                                    <div class="col-md-6">
                                        <input type="text" name="nombre_salon" value="<?= htmlspecialchars($row['nombre_salon'], ENT_QUOTES, 'UTF-8') ?>" 
                                               class="form-control" placeholder="Nombre del salón" required>
                                    </div>
                                    <div class="col-md-6">
                                        <input type="number" name="capacidad" value="<?= (int)$row['capacidad'] ?>" 
                                               class="form-control" placeholder="Capacidad" required min="1">
                                    </div>
                                    <div class="col-12">
                                        <input type="text" name="ubicacion" value="<?= htmlspecialchars($row['ubicacion'], ENT_QUOTES, 'UTF-8') ?>" 
                                               class="form-control" placeholder="Ubicación" required>
                                    </div>
                                </div>

                                <label class="form-label fw-semibold mt-2">Recursos:</label>
                                <div class="checkbox-grid">
                                    <?php foreach ($recursos_disponibles as $r): ?>
                                        <label class="checkbox-item">
                                            <input type="checkbox" name="recursos[]" value="<?= htmlspecialchars($r, ENT_QUOTES, 'UTF-8') ?>" 
                                                   <?= in_array($r, $recursos_list) ? 'checked' : '' ?>>
                                            <span><?= htmlspecialchars(ucfirst(str_replace('_', ' ', $r)), ENT_QUOTES, 'UTF-8') ?></span>
                                        </label>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>

                            <div class="col-12 mt-2">
                                <textarea name="observaciones" class="form-control" 
                                          placeholder="<?= $rol === 'profesor' ? 'Agrega tus observaciones sobre este salón...' : 'Observaciones' ?>" 
                                          rows="3"><?= htmlspecialchars($row['observaciones'] ?? '', ENT_QUOTES, 'UTF-8') ?></textarea>
                            </div>

                            <button type="submit" class="btn btn-warning w-100 mt-2">
                                <i class="bi bi-pencil-square me-1"></i>
                                <?= $rol === 'profesor' ? 'Guardar Observaciones' : 'Guardar Cambios' ?>
                            </button>
                        </form>
                    </div>
                <?php endif; ?>
            </div>
            <?php endwhile; ?>
        <?php else: ?>
            <div class="empty-state">
                <i class="bi bi-building-slash"></i>
                <h4>No hay salones registrados</h4>
                <p>Actualmente no hay salones disponibles en el sistema.</p>
            </div>
        <?php endif; ?>
    </div>
</div>
