<?php
require_once 'auth_admin.php';
require_once 'db_connection.php';
require_once __DIR__ . '/helpers.php';

csrf_verify();

$tipo = $_POST['tipo'];
$id = $_POST['id'] ?? null;
$titulo = trim($_POST['titulo']);
$autor_id = $_SESSION['user_id'];

try {
    if ($tipo === 'noticias') {
        $contenido = trim($_POST['contenido']);
        $imagen_ruta = null;

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
            $tmp_name = $_FILES['imagen_file']['tmp_name'];
            
            // Validar tamaño máximo (5MB)
            $max_size = 5 * 1024 * 1024;
            if ($_FILES['imagen_file']['size'] > $max_size) {
                throw new Exception("La imagen no puede superar los 5MB");
            }
            
            // Validar que sea una imagen real usando getimagesize()
            $img_info = @getimagesize($tmp_name);
            if ($img_info === false) {
                throw new Exception("El archivo no es una imagen válida");
            }
            
            $allowed_mime = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
            if (!in_array($img_info['mime'], $allowed_mime)) {
                throw new Exception("Solo se permiten archivos de imagen (JPG, PNG, GIF, WebP)");
            }
            
            if (!is_dir('uploads')) {
                mkdir('uploads', 0755, true);
            }
            $filename = 'uploads/' . uniqid() . '_' . basename($_FILES['imagen_file']['name']);
            move_uploaded_file($tmp_name, $filename);
            $imagen_ruta = $filename;
        }

        if ($id) {
            // UPDATE
            if ($imagen_ruta) {
                $stmt = $conn->prepare("UPDATE noticias SET titulo=?, contenido=?, imagen_ruta=? WHERE id=?");
                $stmt->bind_param("sssi", $titulo, $contenido, $imagen_ruta, $id);
            } else {
                $stmt = $conn->prepare("UPDATE noticias SET titulo=?, contenido=? WHERE id=?");
                $stmt->bind_param("ssi", $titulo, $contenido, $id);
            }
            $stmt->execute();
            $stmt->close();
        } else {
            // INSERT
            $stmt = $conn->prepare("INSERT INTO noticias (titulo, contenido, imagen_ruta, autor_id) VALUES (?, ?, ?, ?)");
            $stmt->bind_param("sssi", $titulo, $contenido, $imagen_ruta, $autor_id);
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
    $url = base_url() . "/dashboard.php?page=gestionar_contenido&tipo=" . urlencode($tipo) . "&success=1";
    
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
    $url = base_url() . "/dashboard.php?page=gestionar_contenido&tipo=" . urlencode($tipo) . "&error=" . urlencode($e->getMessage());
    
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