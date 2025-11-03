<?php
require_once __DIR__ . '/db_connection.php';

$tipo = $_GET['tipo'] ?? 'noticias';
$id = $_GET['id'] ?? null;

try {
    if ($id) {
        $tabla = $tipo === 'avisos' ? 'avisos' : 'noticias';
        $stmt = $conn->prepare("DELETE FROM $tabla WHERE id=?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $stmt->close();
    }

    // Redirección exitosa
    $baseURL = "https://" . $_SERVER['HTTP_HOST'] . "/Agora/Agora/dashboard.php";
    $url = $baseURL . "?page=gestionar_contenido&tipo=" . urlencode($tipo) . "&deleted=1";
    
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
?>