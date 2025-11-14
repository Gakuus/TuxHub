<?php
// backend/recursos_backend.php
session_start();
require_once 'db_connection.php';

// Verificar que el usuario esté autenticado
if (!isset($_SESSION['user_id'])) {
    header('Location: /Agora/Agora/dashboard.php?page=recursos&error=' . urlencode('No autorizado'));
    exit;
}

$user_id = (int)$_SESSION['user_id'];
$rol = $_SESSION['rol'] ?? '';

// Función para crear página de confirmación
function mostrarConfirmacion($mensaje, $tipo = 'success') {
    $color = $tipo === 'success' ? 'success' : 'danger';
    $icono = $tipo === 'success' ? 'check-circle' : 'exclamation-triangle';
    
    echo <<<HTML
    <!DOCTYPE html>
    <html lang="es">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Confirmación</title>
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
        <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
        <style>
            body {
                background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                min-height: 100vh;
                display: flex;
                align-items: center;
                justify-content: center;
            }
            .confirmation-card {
                background: white;
                border-radius: 15px;
                padding: 2rem;
                box-shadow: 0 10px 30px rgba(0,0,0,0.2);
                text-align: center;
                max-width: 500px;
                width: 90%;
            }
            .confirmation-icon {
                font-size: 4rem;
                margin-bottom: 1rem;
            }
            .countdown {
                font-size: 0.9rem;
                color: #6c757d;
                margin-top: 1rem;
            }
        </style>
    </head>
    <body>
        <div class="confirmation-card">
            <div class="confirmation-icon text-$color">
                <i class="bi bi-$icono"></i>
            </div>
            <h3 class="mb-3">{$mensaje}</h3>
            <p class="countdown">Redirigiendo a recursos en <span id="countdown">3</span> segundos...</p>
            <div class="mt-4">
                <a href="/Agora/Agora/dashboard.php?page=recursos" class="btn btn-$color">
                    <i class="bi bi-arrow-left"></i> Volver a Recursos
                </a>
            </div>
        </div>

        <script>
            let seconds = 3;
            const countdownElement = document.getElementById('countdown');
            
            const countdown = setInterval(() => {
                seconds--;
                countdownElement.textContent = seconds;
                
                if (seconds <= 0) {
                    clearInterval(countdown);
                    window.location.href = '/Agora/Agora/dashboard.php?page=recursos';
                }
            }, 1000);
        </script>
    </body>
    </html>
HTML;
    exit;
}

// CREAR NUEVO RECURSO
if (isset($_POST['accion']) && $_POST['accion'] === 'crear') {
    // Verificar permisos de admin
    if ($rol !== 'admin') {
        mostrarConfirmacion('❌ No tienes permisos para crear recursos', 'danger');
    }
    
    $nombre = trim($_POST['nombre'] ?? '');
    $tipo = $_POST['tipo'] ?? '';
    $estado = 'Disponible'; // Siempre disponible al crear
    $salon_id = !empty($_POST['salon_id']) ? (int)$_POST['salon_id'] : NULL;
    $grupo_id = NULL; // No se asigna grupo al crear
    $usuario_id = NULL; // No se asigna usuario al crear
    $descripcion = trim($_POST['descripcion'] ?? '');
    
    // Validaciones
    if (empty($nombre) || empty($tipo)) {
        mostrarConfirmacion('❌ El nombre y tipo son obligatorios', 'danger');
    }
    
    if (strlen($nombre) > 24) {
        mostrarConfirmacion('❌ El nombre no puede tener más de 24 caracteres', 'danger');
    }
    
    if (strlen($descripcion) > 255) {
        mostrarConfirmacion('❌ La descripción no puede tener más de 255 caracteres', 'danger');
    }
    
    // Validación específica para Llaves y Controles
    if (($tipo === 'Llave' || $tipo === 'Control') && empty($salon_id)) {
        mostrarConfirmacion('❌ Para recursos de tipo ' . $tipo . ' debes seleccionar un salón', 'danger');
    }
    
    // Para Alargues, no debe tener salón asignado
    if ($tipo === 'Alargue' && !empty($salon_id)) {
        $salon_id = NULL;
    }
    
    // Insertar nuevo recurso
    $sql = "INSERT INTO recursos (nombre, tipo, estado, salon_id, grupo_id, usuario_id, descripcion) 
            VALUES (?, ?, ?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('sssiiis', $nombre, $tipo, $estado, $salon_id, $grupo_id, $usuario_id, $descripcion);
    
    if ($stmt->execute()) {
        mostrarConfirmacion('✅ Recurso creado correctamente', 'success');
    } else {
        mostrarConfirmacion('❌ Error al crear el recurso: ' . $conn->error, 'danger');
    }
    exit;
}

// ACTUALIZAR RECURSO
if (isset($_POST['accion']) && $_POST['accion'] === 'actualizar') {
    // Verificar permisos de admin
    if ($rol !== 'admin') {
        mostrarConfirmacion('❌ No tienes permisos para editar recursos', 'danger');
    }
    
    $id = (int)$_POST['id'];
    $nombre = trim($_POST['nombre'] ?? '');
    $tipo = $_POST['tipo'] ?? '';
    $estado = $_POST['estado'] ?? 'Disponible';
    $salon_id = !empty($_POST['salon_id']) ? (int)$_POST['salon_id'] : NULL;
    $grupo_id = !empty($_POST['grupo_id']) ? (int)$_POST['grupo_id'] : NULL;
    $usuario_id = !empty($_POST['usuario_id']) ? (int)$_POST['usuario_id'] : NULL;
    $descripcion = trim($_POST['descripcion'] ?? '');
    
    // Validaciones
    if (empty($nombre) || empty($tipo)) {
        mostrarConfirmacion('❌ El nombre y tipo son obligatorios', 'danger');
    }
    
    if (strlen($nombre) > 24) {
        mostrarConfirmacion('❌ El nombre no puede tener más de 24 caracteres', 'danger');
    }
    
    if (strlen($descripcion) > 255) {
        mostrarConfirmacion('❌ La descripción no puede tener más de 255 caracteres', 'danger');
    }
    
    // Validación específica para Llaves y Controles
    if (($tipo === 'Llave' || $tipo === 'Control') && empty($salon_id)) {
        mostrarConfirmacion('❌ Para recursos de tipo ' . $tipo . ' debes seleccionar un salón', 'danger');
    }
    
    // Para Alargues, no debe tener salón asignado
    if ($tipo === 'Alargue' && !empty($salon_id)) {
        $salon_id = NULL;
    }
    
    // Actualizar recurso
    $sql = "UPDATE recursos SET nombre = ?, tipo = ?, estado = ?, salon_id = ?, grupo_id = ?, usuario_id = ?, descripcion = ? 
            WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('sssiiisi', $nombre, $tipo, $estado, $salon_id, $grupo_id, $usuario_id, $descripcion, $id);
    
    if ($stmt->execute()) {
        if ($stmt->affected_rows > 0) {
            mostrarConfirmacion('✅ Recurso actualizado correctamente', 'success');
        } else {
            mostrarConfirmacion('❌ No se encontró el recurso a actualizar', 'danger');
        }
    } else {
        mostrarConfirmacion('❌ Error al actualizar el recurso: ' . $conn->error, 'danger');
    }
    exit;
}

// Marcar uso (ocupar/liberar)
if (isset($_POST['accion']) && $_POST['accion'] === 'marcar_uso') {
    $id = (int)$_POST['id'];
    $salon_id = !empty($_POST['salon_id']) ? (int)$_POST['salon_id'] : NULL;
    $grupo_id = !empty($_POST['grupo_id']) ? (int)$_POST['grupo_id'] : NULL;
    $tipo_uso = $_POST['tipo_uso']; // 'ocupar' o 'liberar'
    $mantener_salon = isset($_POST['mantener_salon']) ? (int)$_POST['mantener_salon'] : 0;
    
    // Verificar que el grupo sea obligatorio para ocupar
    if ($tipo_uso === 'ocupar' && empty($grupo_id)) {
        mostrarConfirmacion('❌ Debes seleccionar un grupo para usar el recurso', 'danger');
    }
    
    // Verificar que el salón sea obligatorio para ocupar (excepto para alargues)
    if ($tipo_uso === 'ocupar' && empty($salon_id) && $mantener_salon === 0) {
        mostrarConfirmacion('❌ Debes seleccionar un salón para usar el recurso', 'danger');
    }
    
    if ($tipo_uso === 'ocupar') {
        // Ocupar el recurso - permitir a todos los roles
        $sql = "UPDATE recursos SET estado = 'Ocupado', usuario_id = ?, salon_id = ?, grupo_id = ? WHERE id = ? AND (estado = 'Disponible' OR estado = 'Reservado')";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param('iiii', $user_id, $salon_id, $grupo_id, $id);
    } else {
        // Liberar el recurso - manejo diferente según el tipo de recurso
        if ($rol === 'admin') {
            // Admin puede liberar cualquier recurso
            if ($mantener_salon === 1) {
                // Para controles y llaves: mantener el salón
                $sql = "UPDATE recursos SET estado = 'Disponible', usuario_id = NULL, grupo_id = NULL WHERE id = ? AND estado = 'Ocupado'";
            } else {
                // Para alargues: limpiar el salón
                $sql = "UPDATE recursos SET estado = 'Disponible', usuario_id = NULL, salon_id = NULL, grupo_id = NULL WHERE id = ? AND estado = 'Ocupado'";
            }
            $stmt = $conn->prepare($sql);
            $stmt->bind_param('i', $id);
        } else {
            // Otros usuarios solo pueden liberar los que ellos ocuparon
            if ($mantener_salon === 1) {
                // Para controles y llaves: mantener el salón
                $sql = "UPDATE recursos SET estado = 'Disponible', usuario_id = NULL, grupo_id = NULL WHERE id = ? AND usuario_id = ? AND estado = 'Ocupado'";
            } else {
                // Para alargues: limpiar el salón
                $sql = "UPDATE recursos SET estado = 'Disponible', usuario_id = NULL, salon_id = NULL, grupo_id = NULL WHERE id = ? AND usuario_id = ? AND estado = 'Ocupado'";
            }
            $stmt = $conn->prepare($sql);
            $stmt->bind_param('ii', $id, $user_id);
        }
    }
    
    if ($stmt->execute()) {
        if ($stmt->affected_rows > 0) {
            $mensaje = $tipo_uso === 'ocupar' ? '✅ Recurso marcado como en uso' : '✅ Recurso liberado correctamente';
            mostrarConfirmacion($mensaje, 'success');
        } else {
            $mensaje = $tipo_uso === 'ocupar' ? 
                '❌ No se pudo ocupar el recurso. Puede que ya esté en uso.' : 
                '❌ No se pudo liberar el recurso.';
            mostrarConfirmacion($mensaje, 'danger');
        }
    } else {
        mostrarConfirmacion('❌ Error en la base de datos', 'danger');
    }
    exit;
}

// Reservar recurso
if (isset($_POST['accion']) && $_POST['accion'] === 'reservar') {
    $id = (int)$_POST['id'];
    
    // Permitir reservar a todos los roles excepto invitados
    if ($rol === 'invitado') {
        mostrarConfirmacion('❌ No tienes permisos para reservar recursos', 'danger');
    }
    
    $sql = "UPDATE recursos SET estado = 'Reservado', usuario_id = ? WHERE id = ? AND estado = 'Disponible'";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('ii', $user_id, $id);
    
    if ($stmt->execute()) {
        if ($stmt->affected_rows > 0) {
            mostrarConfirmacion('✅ Recurso reservado correctamente', 'success');
        } else {
            mostrarConfirmacion('❌ No se pudo reservar el recurso. Puede que ya no esté disponible.', 'danger');
        }
    } else {
        mostrarConfirmacion('❌ Error en la base de datos', 'danger');
    }
    exit;
}

// Cancelar reserva
if (isset($_POST['accion']) && $_POST['accion'] === 'cancelar_reserva') {
    $id = (int)$_POST['id'];
    
    if ($rol === 'admin') {
        $sql = "UPDATE recursos SET estado = 'Disponible', usuario_id = NULL WHERE id = ? AND estado = 'Reservado'";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param('i', $id);
    } else {
        $sql = "UPDATE recursos SET estado = 'Disponible', usuario_id = NULL WHERE id = ? AND usuario_id = ? AND estado = 'Reservado'";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param('ii', $id, $user_id);
    }
    
    if ($stmt->execute()) {
        if ($stmt->affected_rows > 0) {
            mostrarConfirmacion('✅ Reserva cancelada correctamente', 'success');
        } else {
            mostrarConfirmacion('❌ No se pudo cancelar la reserva', 'danger');
        }
    } else {
        mostrarConfirmacion('❌ Error en la base de datos', 'danger');
    }
    exit;
}

// Eliminar recurso (solo admin)
if (isset($_GET['delete'])) {
    if ($rol === 'admin') {
        $id = (int)$_GET['delete'];
        $sql = "DELETE FROM recursos WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param('i', $id);
        
        if ($stmt->execute()) {
            mostrarConfirmacion('✅ Recurso eliminado correctamente', 'success');
        } else {
            mostrarConfirmacion('❌ Error al eliminar el recurso', 'danger');
        }
    } else {
        mostrarConfirmacion('❌ No tienes permisos para eliminar recursos', 'danger');
    }
    exit;
}

// Si no se reconoce la acción
mostrarConfirmacion('❌ Acción no reconocida', 'danger');
?>