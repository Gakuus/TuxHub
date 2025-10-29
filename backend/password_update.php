<?php

// Incluir la conexión a la base de datos
require_once __DIR__ . '/db_connection.php';

// --- CONFIGURACIÓN ---
const MIN_PASS_LENGTH = 8;
const MAX_PASS_LENGTH = 24;

// --- FUNCIONES AUXILIARES ---

/**
 * Muestra un mensaje al usuario y termina el script.
 *
 * @param string $message El mensaje a mostrar.
 * @param string $type El tipo de mensaje ('success' o 'error').
 */
function displayPage(string $message, string $type = 'success'): void
{
    $title = ($type === 'success') ? 'Operación Exitosa' : 'Ocurrió un Error';
    $icon = ($type === 'success') ? '✅' : '❌';
    $color = ($type === 'success') ? '#28a745' : '#dc3545';
    $link = ($type === 'success') ? "<a href='../index.php' class='button'>Iniciar Sesión</a>" : "<a href='#' onclick='window.history.back();' class='button'>Volver a Intentar</a>";

    // Usamos HEREDOC para una plantilla HTML limpia
    echo <<<HTML
    <!DOCTYPE html>
    <html lang="es">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>$title</title>
        <style>
            @import url('https://fonts.googleapis.com/css2?family=Poppins:wght@400;600&display=swap');
            body {
                font-family: 'Poppins', sans-serif;
                background: url('/Agora/Agora/img/itsp.jpeg') no-repeat center center fixed;
        background-size: cover;
                display: flex;
                justify-content: center;
                align-items: center;
                height: 100vh;
                margin: 0;
                color: #333;
            }
            
            
            
            .container {
                background-color: #ffffff;
                padding: 40px;
                border-radius: 12px;
                box-shadow: 0 8px 16px rgba(0, 0, 0, 0.1);
                text-align: center;
                max-width: 400px;
                width: 90%;
            }
            .icon {
                font-size: 48px;
            }
            h3 {
                color: $color;
                margin: 20px 0;
            }
            .button {
                display: inline-block;
                padding: 12px 24px;
                background-color: #007bff;
                color: #ffffff;
                text-decoration: none;
                border-radius: 8px;
                font-weight: 600;
                transition: background-color 0.3s ease;
            }
            .button:hover {
                background-color: #0056b3;
            }
        </style>
    </head>
    <body>
        <div class="container">
            <div class="icon">$icon</div>
            <h3>$message</h3>
            $link
        </div>
    </body>
    </html>
HTML;
    exit(); // Detenemos la ejecución del script después de mostrar el mensaje
}


// --- LÓGICA PRINCIPAL ---

// Solo proceder si la petición es POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    // Si no es POST, no hacemos nada o redirigimos
    header('Location: ../index.php');
    exit();
}

// Recoger y limpiar datos del formulario
$token = $_POST['token'] ?? '';
$password = $_POST['password'] ?? '';

// Validar longitud de la contraseña
$pass_len = strlen($password);
if ($pass_len < MIN_PASS_LENGTH || $pass_len > MAX_PASS_LENGTH) {
    displayPage("La contraseña debe tener entre " . MIN_PASS_LENGTH . " y " . MAX_PASS_LENGTH . " caracteres.", 'error');
}

// Iniciar transacción para garantizar la integridad de los datos
$conn->begin_transaction();

try {
    // 1. Buscar el ID de usuario asociado al token (y verificar que no haya expirado)
    // Se recomienda añadir una columna 'expires_at' a tu tabla 'password_resets'
    $stmt = $conn->prepare("SELECT user_id FROM password_resets WHERE token = ?"); // AND expires_at > NOW()
    $stmt->bind_param("s", $token);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 0) {
        throw new Exception("El token proporcionado no es válido o ha expirado.");
    }

    $user_id = $result->fetch_assoc()['user_id'];
    $stmt->close();

    // 2. Hashear la nueva contraseña
    $hash = password_hash($password, PASSWORD_DEFAULT);

    // 3. Actualizar la contraseña en la tabla de usuarios
    $stmt = $conn->prepare("UPDATE usuarios SET password = ? WHERE id = ?");
    $stmt->bind_param("si", $hash, $user_id);
    if (!$stmt->execute()) {
        throw new Exception("No se pudo actualizar la contraseña.");
    }
    $stmt->close();

    // 4. Eliminar el token para que no pueda ser reutilizado
    $stmt = $conn->prepare("DELETE FROM password_resets WHERE token = ?");
    $stmt->bind_param("s", $token);
    if (!$stmt->execute()) {
        throw new Exception("No se pudo invalidar el token de reinicio.");
    }
    $stmt->close();

    // Si todo salió bien, confirmar la transacción
    $conn->commit();

    displayPage("Tu contraseña ha sido actualizada con éxito.", 'success');

} catch (Exception $e) {
    // Si algo falla, revertir todos los cambios
    $conn->rollback();
    // Opcional: registrar el error $e->getMessage() en un log para depuración
    displayPage("Hubo un problema al procesar tu solicitud. Por favor, inténtalo de nuevo.", 'error');
}