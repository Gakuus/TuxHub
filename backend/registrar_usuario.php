<?php
// ¡Incluimos nuestro guardián al principio de todo!
// Si el usuario no es admin, el script de abajo nunca se ejecutará.
require_once 'auth_admin.php';
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registrar Nuevo Usuario - Panel Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <style>
        body { background-color: #f8f9fa; }
        .register-container { max-width: 600px; margin: 50px auto; padding: 30px; background: white; border-radius: 10px; box-shadow: 0 4px 8px rgba(0,0,0,0.1); }
    </style>
</head>
<body>
    <div class="container">
        <div class="register-container">
            <h2 class="text-center mb-4"><i class="bi bi-person-plus-fill"></i> Registrar Nuevo Usuario</h2>
            
            <?php
            // Mostrar mensajes de éxito o error que vienen desde el backend
            if (isset($_GET['success'])) {
                echo '<div class="alert alert-success">Usuario registrado correctamente.</div>';
            }
            if (isset($_GET['error'])) {
                $error = $_GET['error'];
                $mensaje = 'Ha ocurrido un error.';
                if ($error === 'campos_vacios') $mensaje = 'Por favor, complete todos los campos.';
                if ($error === 'cedula_existe') $mensaje = 'La cédula ingresada ya está registrada.';
                if ($error === 'db_error') $mensaje = 'Error al guardar en la base de datos.';
                echo '<div class="alert alert-danger">' . $mensaje . '</div>';
            }
            ?>

            <form action="handle_register.php" method="POST">
                <div class="mb-3">
                    <label for="nombre" class="form-label">Nombre Completo</label>
                    <input type="text" class="form-control" id="nombre" name="nombre" required>
                </div>
                <div class="mb-3">
                    <label for="cedula" class="form-label">Cédula (sin puntos ni guion)</label>
                    <input type="text" class="form-control" id="cedula" name="cedula" required pattern="\d{7,8}">
                </div>
                <div class="mb-3">
                    <label for="password" class="form-label">Contraseña</label>
                    <input type="password" class="form-control" id="password" name="password" required>
                </div>
                <div class="mb-3">
                    <label for="rol" class="form-label">Rol del Usuario</label>
                    <select class="form-select" id="rol" name="rol" required>
                        <option value="profesor">Profesor</option>
                        <option value="alumno">Alumno</option>
                        <option value="administrador">Administrador</option>
                    </select>
                </div>
                <div class="d-grid mt-4">
                    <button type="submit" class="btn btn-primary">Registrar Usuario</button>
                </div>
            </form>
            <div class="text-center mt-3">
                <a href="../dashboard.html">Volver al Panel</a>
            </div>
        </div>
    </div>
</body>
</html>