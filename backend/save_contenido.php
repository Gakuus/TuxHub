<?php
session_start();
require_once 'auth_admin.php';
require_once 'db_connection.php';

$tipo = $_POST['tipo'];
$id = $_POST['id'] ?? null;
$titulo = trim($_POST['titulo']);
$autor_id = $_SESSION['user_id'];

try {
    if ($tipo === 'noticias') {
        $contenido = trim($_POST['contenido']);
        $imagenData = null;

        // Validar campos requeridos
        if (empty($titulo) || empty($contenido)) {
            throw new Exception("Todos los campos son requeridos");
        }

        // Validar longitud
        if (strlen($titulo) > 24) {
            throw new Exception("El título no puede tener más de 24 caracteres");
        }

        if (strlen($contenido) > 255) {
            throw new Exception("El contenido no puede tener más de 255 caracteres");
        }

        // Procesar imagen
        if (isset($_FILES['imagen_file']) && $_FILES['imagen_file']['error'] === UPLOAD_ERR_OK) {
            // Validar tipo de archivo
            $allowed_types = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp'];
            $file_type = $_FILES['imagen_file']['type'];
            
            if (!in_array($file_type, $allowed_types)) {
                throw new Exception("Solo se permiten archivos de imagen (JPG, PNG, GIF, WebP)");
            }
            
            $tmp_name = $_FILES['imagen_file']['tmp_name'];
            $imagenData = file_get_contents($tmp_name);
        }

        $null = null;

        if ($id) {
            // UPDATE
            if ($imagenData) {
                $stmt = $conn->prepare("UPDATE noticias SET titulo=?, contenido=?, imagen=? WHERE id=?");
                $stmt->bind_param("ssbi", $titulo, $contenido, $null, $id);
                $stmt->send_long_data(2, $imagenData);
            } else {
                $stmt = $conn->prepare("UPDATE noticias SET titulo=?, contenido=? WHERE id=?");
                $stmt->bind_param("ssi", $titulo, $contenido, $id);
            }
            $stmt->execute();
            $stmt->close();
        } else {
            // INSERT
            $stmt = $conn->prepare("INSERT INTO noticias (titulo, contenido, imagen, autor_id) VALUES (?, ?, ?, ?)");
            $stmt->bind_param("ssbi", $titulo, $contenido, $null, $autor_id);
            if ($imagenData) {
                $stmt->send_long_data(2, $imagenData);
            }
            $stmt->execute();
            $id = $conn->insert_id;
            $stmt->close();
        }

    } else {
        // Guardar AVISOS
        $mensaje = trim($_POST['mensaje']);
        
        // Validar campos requeridos
        if (empty($titulo) || empty($mensaje)) {
            throw new Exception("Todos los campos son requeridos");
        }

        // Validar longitud
        if (strlen($titulo) > 24) {
            throw new Exception("El título no puede tener más de 24 caracteres");
        }

        if (strlen($mensaje) > 255) {
            throw new Exception("El mensaje no puede tener más de 255 caracteres");
        }

        if ($id) {
            $stmt = $conn->prepare("UPDATE avisos SET titulo=?, mensaje=? WHERE id=?");
            $stmt->bind_param("ssi", $titulo, $mensaje, $id);
            $stmt->execute();
            $stmt->close();
        } else {
            $stmt = $conn->prepare("INSERT INTO avisos (titulo, mensaje, autor_id) VALUES (?, ?, ?)");
            $stmt->bind_param("ssi", $titulo, $mensaje, $autor_id);
            $stmt->execute();
            $id = $conn->insert_id;
            $stmt->close();
        }
    }

    // Redirección exitosa
    $baseURL = "https://" . $_SERVER['HTTP_HOST'] . "/Agora/Agora/dashboard.php";
    $url = $baseURL . "?page=gestionar_contenido&tipo=" . urlencode($tipo) . "&success=1";
    
    echo "
    <script>
      window.location.replace('$url');
    </script>
    <noscript>
      <meta http-equiv='refresh' content='0;url=$url'>
    </noscript>";
    exit();

} catch (Exception $e) {
    // Redirección con error
    $baseURL = "https://" . $_SERVER['HTTP_HOST'] . "/Agora/Agora/dashboard.php";
    $url = $baseURL . "?page=gestionar_contenido&tipo=" . urlencode($tipo) . "&error=" . urlencode($e->getMessage());
    
    echo "
    <script>
      window.location.replace('$url');
    </script>
    <noscript>
      <meta http-equiv='refresh' content='0;url=$url'>
    </noscript>";
    exit();
}

$conn->close();
?>