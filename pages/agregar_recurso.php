<?php
// pages/agregar_recurso.php
session_start();
require_once __DIR__ . '/../backend/db_connection.php';
$conn->set_charset('utf8mb4');

// Mostrar errores de PHP (durante desarrollo)
ini_set('display_errors', 1);
error_reporting(E_ALL);

// ---------- validar sesión y permisos ----------
if (!isset($_SESSION['user_id'])) {
    header('Location: dashboard.php?page=recursos');
    exit;
}

$rol = strtolower($_SESSION['rol'] ?? 'invitado');
if ($rol !== 'admin') {
    header('Location: dashboard.php?page=recursos');
    exit;
}

$user_id = (int)($_SESSION['user_id'] ?? 0);
$errors = [];
$notices = [];
$recurso_data = [];

// ---------- Si estamos editando, cargar datos del recurso ----------
$edit_id = isset($_GET['edit']) ? (int)$_GET['edit'] : 0;
$is_editing = ($edit_id > 0);

if ($is_editing) {
    try {
        $sql = "
          SELECT r.*,
                 s.nombre_salon,
                 g.nombre AS grupo_nombre,
                 u.nombre AS usuario_nombre,
                 u.rol AS usuario_rol
          FROM recursos r
          LEFT JOIN salones s ON r.salon_id = s.id
          LEFT JOIN grupos g ON r.grupo_id = g.id
          LEFT JOIN usuarios u ON r.usuario_id = u.id
          WHERE r.id = ?
        ";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param('i', $edit_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 0) {
            $errors[] = "Recurso no encontrado";
            $is_editing = false;
        } else {
            $recurso_data = $result->fetch_assoc();
        }
        $stmt->close();
    } catch (Exception $e) {
        $errors[] = "Error al cargar recurso: " . $e->getMessage();
        $is_editing = false;
    }
}

// ---------- consultas para selects ----------
try {
    // Salones
    $res_salones = $conn->query("SELECT id, nombre_salon FROM salones ORDER BY nombre_salon ASC");
    if ($res_salones === false) throw new Exception("Error al consultar salones: " . $conn->error);

    // Grupos
    $res_grupos = $conn->query("SELECT id, nombre FROM grupos ORDER BY nombre ASC");
    if ($res_grupos === false) throw new Exception("Error al consultar grupos: " . $conn->error);

} catch (Exception $e) {
    $errors[] = $e->getMessage();
    if (!isset($res_salones)) $res_salones = null;
    if (!isset($res_grupos)) $res_grupos = null;
}
?>
<div class="d-flex justify-content-between align-items-center mb-4">
    <h4 class="mb-0"><?= $is_editing ? '✏️ Editar Recurso' : '➕ Agregar Recurso' ?></h4>
    <a href="dashboard.php?page=recursos" class="btn btn-secondary">
        <i class="bi bi-arrow-left me-1"></i> Volver a Recursos
    </a>
</div>

<!-- Errores / avisos -->
<?php foreach ($errors as $err): ?>
    <div class="alert alert-danger"><?= htmlspecialchars($err) ?></div>
<?php endforeach; ?>

<!-- Formulario -->
<form method="POST" action="/Agora/Agora/backend/recursos_backend.php" class="row g-3" id="formRecurso">
    <input type="hidden" name="id" value="<?= $is_editing ? $recurso_data['id'] : '' ?>">
    
    <div class="col-md-6">
        <label for="nombre" class="form-label">Nombre del Recurso *</label>
        <input type="text" class="form-control" id="nombre" name="nombre" 
               value="<?= $is_editing ? htmlspecialchars($recurso_data['nombre']) : '' ?>" 
               maxlength="24" required>
        <div class="form-text">Máximo 24 caracteres</div>
    </div>
    
    <div class="col-md-6">
        <label for="tipo" class="form-label">Tipo *</label>
        <select class="form-select" id="tipo" name="tipo" required>
            <option value="">Seleccionar tipo</option>
            <option value="Alargue" <?= $is_editing && $recurso_data['tipo']=='Alargue' ? 'selected' : '' ?>>Alargue</option>
            <option value="Control" <?= $is_editing && $recurso_data['tipo']=='Control' ? 'selected' : '' ?>>Control</option>
            <option value="Llave" <?= $is_editing && $recurso_data['tipo']=='Llave' ? 'selected' : '' ?>>Llave</option>
        </select>
    </div>
    
    <div class="col-md-6">
        <label for="estado" class="form-label">Estado *</label>
        <select class="form-select" id="estado" name="estado" required>
            <option value="Disponible" <?= $is_editing && $recurso_data['estado']=='Disponible' ? 'selected' : '' ?>>Disponible</option>
            <option value="Ocupado" <?= $is_editing && $recurso_data['estado']=='Ocupado' ? 'selected' : '' ?>>Ocupado</option>
            <option value="Mantenimiento" <?= $is_editing && $recurso_data['estado']=='Mantenimiento' ? 'selected' : '' ?>>Mantenimiento</option>
        </select>
    </div>
    
    <div class="col-md-6">
        <label for="salon_id" class="form-label">Salón</label>
        <select class="form-select" id="salon_id" name="salon_id">
            <option value="">— Sin salón —</option>
            <?php if ($res_salones && $res_salones->num_rows > 0): 
                while($s = $res_salones->fetch_assoc()): ?>
                    <option value="<?= $s['id'] ?>" 
                        <?= $is_editing && $recurso_data['salon_id']==$s['id'] ? 'selected' : '' ?>>
                        <?= htmlspecialchars($s['nombre_salon']) ?>
                    </option>
            <?php endwhile; endif; ?>
        </select>
    </div>
    
    <div class="col-md-6">
        <label for="grupo_id" class="form-label">Grupo</label>
        <select class="form-select" id="grupo_id" name="grupo_id">
            <option value="">— Sin grupo —</option>
            <?php if ($res_grupos && $res_grupos->num_rows > 0):
                while($g = $res_grupos->fetch_assoc()): ?>
                    <option value="<?= $g['id'] ?>" 
                        <?= $is_editing && $recurso_data['grupo_id']==$g['id'] ? 'selected' : '' ?>>
                        <?= htmlspecialchars($g['nombre']) ?>
                    </option>
            <?php endwhile; endif; ?>
        </select>
    </div>

    <!-- Selector de Rol -->
    <div class="col-md-6">
        <label for="asignado_role" class="form-label">Rol del Usuario</label>
        <select class="form-select" id="asignado_role">
            <option value="">— Seleccionar rol —</option>
            <option value="profesor" <?= $is_editing && isset($recurso_data['usuario_rol']) && $recurso_data['usuario_rol']=='profesor' ? 'selected' : '' ?>>Profesor</option>
            <option value="alumno" <?= $is_editing && isset($recurso_data['usuario_rol']) && $recurso_data['usuario_rol']=='alumno' ? 'selected' : '' ?>>Alumno</option>
        </select>
        <div class="form-text" id="rolHelp">Selecciona el tipo de usuario</div>
    </div>
    
    <div class="col-md-6">
        <label for="usuario_id" class="form-label">Usuario Asignado</label>
        <select class="form-select" id="usuario_id" name="usuario_id">
            <option value="">— Selecciona rol primero —</option>
            <?php if ($is_editing && !empty($recurso_data['usuario_id'])): ?>
                <option value="<?= $recurso_data['usuario_id'] ?>" selected>
                    <?= htmlspecialchars($recurso_data['usuario_nombre'] ?? 'Usuario') ?> 
                    (<?= htmlspecialchars($recurso_data['usuario_rol'] ?? '') ?>)
                </option>
            <?php endif; ?>
        </select>
        <div class="form-text" id="usuarioHelp">Selecciona un rol para cargar usuarios</div>
    </div>
    
    <div class="col-12">
        <label for="descripcion" class="form-label">Descripción</label>
        <textarea class="form-control" id="descripcion" name="descripcion" 
                  rows="3" maxlength="255"><?= $is_editing ? htmlspecialchars($recurso_data['descripcion'] ?? '') : '' ?></textarea>
        <div class="form-text">Máximo 255 caracteres</div>
    </div>
    
    <div class="col-12">
        <?php if ($is_editing): ?>
            <button type="submit" name="accion" value="actualizar" class="btn btn-primary">
                💾 Actualizar Recurso
            </button>
        <?php else: ?>
            <button type="submit" name="accion" value="crear" class="btn btn-primary">
                ➕ Crear Recurso
            </button>
        <?php endif; ?>
    </div>
</form>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const rolSelect = document.getElementById('asignado_role');
    const grupoSelect = document.getElementById('grupo_id');
    const usuarioSelect = document.getElementById('usuario_id');
    const usuarioHelp = document.getElementById('usuarioHelp');
    const rolHelp = document.getElementById('rolHelp');
    const form = document.getElementById('formRecurso');

    function cargarUsuarios() {
        const rol = rolSelect.value;
        const grupoId = grupoSelect.value;
        
        console.log('Cargando usuarios para:', { rol, grupoId });
        
        // Resetear select
        usuarioSelect.innerHTML = '<option value="">— Cargando usuarios... —</option>';
        usuarioSelect.disabled = true;
        
        if (!rol) {
            usuarioSelect.innerHTML = '<option value="">— Selecciona un rol primero —</option>';
            usuarioHelp.textContent = 'Selecciona un rol para cargar usuarios';
            usuarioSelect.disabled = false;
            return;
        }

        // Para PROFESORES: no necesita grupo, para ALUMNOS: sí necesita grupo
        if (rol === 'alumno' && !grupoId) {
            usuarioSelect.innerHTML = '<option value="">— Selecciona un grupo —</option>';
            usuarioHelp.textContent = 'Para alumnos, selecciona un grupo';
            usuarioSelect.disabled = false;
            return;
        }

        // Actualizar mensaje de ayuda
        if (rol === 'profesor') {
            rolHelp.textContent = 'Los profesores se muestran de todos los grupos';
            usuarioHelp.textContent = 'Cargando todos los profesores...';
        } else {
            rolHelp.textContent = 'Los alumnos se filtran por grupo';
            usuarioHelp.textContent = 'Cargando alumnos del grupo seleccionado...';
        }

        // Hacer petición AJAX - RUTA CORREGIDA
        const url = '/Agora/Agora/backend/get_users_by_role_group.php';
        console.log('Haciendo petición a:', url);
        
        fetch(url, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                role: rol,
                grupo_id: parseInt(grupoId) || 0
            })
        })
        .then(response => {
            console.log('Respuesta del servidor:', response.status, response.statusText);
            if (!response.ok) {
                throw new Error('Error en el servidor: ' + response.status + ' - ' + response.statusText);
            }
            return response.json();
        })
        .then(data => {
            console.log('Respuesta del servidor:', data);
            usuarioSelect.disabled = false;
            
            if (data.error) {
                usuarioSelect.innerHTML = '<option value="">— Error: ' + data.error + ' —</option>';
                usuarioHelp.textContent = 'Error al cargar usuarios';
                return;
            }

            if (data.users && data.users.length > 0) {
                usuarioSelect.innerHTML = '<option value="">— Seleccionar usuario —</option>';
                
                data.users.forEach(usuario => {
                    const option = document.createElement('option');
                    option.value = usuario.id;
                    option.textContent = usuario.nombre + (usuario.cedula ? ' (' + usuario.cedula + ')' : '');
                    
                    // Si estamos editando, seleccionar el usuario actual
                    <?php if ($is_editing && !empty($recurso_data['usuario_id'])): ?>
                        if (usuario.id == <?= $recurso_data['usuario_id'] ?>) {
                            option.selected = true;
                        }
                    <?php endif; ?>
                    
                    usuarioSelect.appendChild(option);
                });
                
                usuarioHelp.textContent = 'Se encontraron ' + data.users.length + ' usuarios';
            } else {
                usuarioSelect.innerHTML = '<option value="">— No hay usuarios —</option>';
                if (rol === 'profesor') {
                    usuarioHelp.textContent = 'No se encontraron profesores';
                } else {
                    usuarioHelp.textContent = 'No se encontraron alumnos en este grupo';
                }
            }
        })
        .catch(error => {
            console.error('Error completo:', error);
            usuarioSelect.innerHTML = '<option value="">— Error de conexión —</option>';
            usuarioSelect.disabled = false;
            usuarioHelp.textContent = 'Error al cargar usuarios: ' + error.message;
        });
    }

    // Event listeners
    rolSelect.addEventListener('change', function() {
        const rol = this.value;
        
        // Actualizar mensajes según el rol
        if (rol === 'profesor') {
            rolHelp.textContent = 'Los profesores se muestran de todos los grupos';
            usuarioHelp.textContent = 'Selecciona "Profesor" para cargar todos los profesores';
            // Para profesores, cargar inmediatamente sin esperar grupo
            cargarUsuarios();
        } else if (rol === 'alumno') {
            rolHelp.textContent = 'Los alumnos se filtran por grupo';
            usuarioHelp.textContent = 'Selecciona un grupo para cargar alumnos';
            // Si ya hay grupo seleccionado, cargar inmediatamente
            if (grupoSelect.value) {
                cargarUsuarios();
            } else {
                usuarioSelect.innerHTML = '<option value="">— Selecciona un grupo —</option>';
            }
        } else {
            usuarioSelect.innerHTML = '<option value="">— Selecciona un rol —</option>';
            usuarioHelp.textContent = 'Selecciona un rol para cargar usuarios';
        }
    });

    grupoSelect.addEventListener('change', function() {
        // Solo cargar automáticamente si el rol es "alumno"
        if (rolSelect.value === 'alumno') {
            cargarUsuarios();
        }
    });

    // Cargar usuarios automáticamente si estamos editando
    <?php if ($is_editing && !empty($recurso_data['usuario_id'])): ?>
        setTimeout(() => {
            if (rolSelect.value) {
                cargarUsuarios();
            }
        }, 100);
    <?php endif; ?>

    // Validación y envío del formulario - CORREGIDO
    form.addEventListener('submit', function(e) {
        e.preventDefault(); // Prevenir envío normal
        
        const nombre = document.getElementById('nombre').value.trim();
        const tipo = document.getElementById('tipo').value;
        
        if (!nombre) {
            alert('Por favor ingresa un nombre para el recurso');
            document.getElementById('nombre').focus();
            return;
        }
        
        if (!tipo) {
            alert('Por favor selecciona un tipo de recurso');
            document.getElementById('tipo').focus();
            return;
        }

        // Mostrar loading
        const submitBtn = form.querySelector('button[type="submit"]');
        const originalText = submitBtn.innerHTML;
        submitBtn.innerHTML = '⏳ Guardando...';
        submitBtn.disabled = true;

        // Crear FormData del formulario (ahora incluye la acción automáticamente)
        const formData = new FormData(form);
        
        // Debug: mostrar datos que se enviarán
        console.log('Datos a enviar:');
        for (let pair of formData.entries()) {
            console.log(pair[0] + ': ' + pair[1]);
        }
        
        fetch('/Agora/Agora/backend/recursos_backend.php', {
            method: 'POST',
            body: formData
        })
        .then(response => {
            console.log('Respuesta HTTP:', response.status, response.statusText);
            return response.json();
        })
        .then(data => {
            console.log('Respuesta JSON:', data);
            if (data.success) {
                alert(data.message);
                // Redirigir a la lista de recursos
                window.location.href = 'dashboard.php?page=recursos';
            } else {
                alert('Error: ' + (data.error || 'Error desconocido'));
                submitBtn.innerHTML = originalText;
                submitBtn.disabled = false;
            }
        })
        .catch(error => {
            console.error('Error completo:', error);
            alert('Error de conexión: ' + error.message);
            submitBtn.innerHTML = originalText;
            submitBtn.disabled = false;
        });
    });
});
</script>