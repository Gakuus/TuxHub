<?php
require_once 'auth_admin.php';
require_once 'db_connection.php';

$tipo = $_POST['tipo'];
$id = $_POST['id'] ?? null;
$titulo = trim($_POST['titulo']);
$autor_id = $_SESSION['user_id'];

if ($tipo === 'noticias') {
    $contenido = trim($_POST['contenido']);
    $imagenData = null;

    // Si subieron archivo de imagen
    if (isset($_FILES['imagen_file']) && $_FILES['imagen_file']['error'] === UPLOAD_ERR_OK) {
        $tmp_name = $_FILES['imagen_file']['tmp_name'];
        $imagenData = file_get_contents($tmp_name); // bytes binarios
    }

    // Si pasaron URL de imagen
    if (empty($imagenData) && !empty($_POST['imagen_url'])) {
        $url = trim($_POST['imagen_url']);
        $imgContent = @file_get_contents($url);
        if ($imgContent !== false) {
            $imagenData = $imgContent;
        }
    }

    $null = null; // necesario para bind_param con tipo 'b'

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
    } else {
        // INSERT
        $stmt = $conn->prepare("INSERT INTO noticias (titulo, contenido, imagen, autor_id) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("ssbi", $titulo, $contenido, $null, $autor_id);
        if ($imagenData) {
            $stmt->send_long_data(2, $imagenData);
        }
        $stmt->execute();
        $id = $conn->insert_id;
    }
    $stmt->close();

} else {
    // Guardar AVISOS
    $mensaje = trim($_POST['mensaje']);
    if ($id) {
        $stmt = $conn->prepare("UPDATE avisos SET titulo=?, mensaje=? WHERE id=?");
        $stmt->bind_param("ssi", $titulo, $mensaje, $id);
        $stmt->execute();
    } else {
        $stmt = $conn->prepare("INSERT INTO avisos (titulo, mensaje, autor_id) VALUES (?, ?, ?)");
        $stmt->bind_param("ssi", $titulo, $mensaje, $autor_id);
        $stmt->execute();
        $id = $conn->insert_id;
    }
    $stmt->close();
}

$conn->close();


$baseURL = "https://" . $_SERVER['HTTP_HOST'] . "/Agora/Agora/dashboard.php";
$url = $baseURL . "?tipo=" . urlencode($tipo) . "&success=1";

echo "
<script>
  window.location.replace('$url');
</script>
<noscript>
  <meta http-equiv='refresh' content='0;url=$url'>
</noscript>
";
exit();

