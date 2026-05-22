<?php
require_once __DIR__ . '/db_connection.php';
require_once __DIR__ . '/helpers.php';

require_auth();

$tipo = $_GET['tipo'] ?? 'noticias';
$id = $_GET['id'] ?? null;

// CSRF check for GET requests
$token = $_GET['csrf_token'] ?? '';
$stored = $_SESSION['csrf_token'] ?? '';
if (empty($token) || empty($stored) || !hash_equals($stored, $token)) {
    header('Location: dashboard.php?page=gestionar_contenido&tipo=' . urlencode($tipo) . '&error=csrf');
    exit;
}

try {
    if ($id) {
        $tabla = $tipo === 'avisos' ? 'avisos' : 'noticias';
        $stmt = $conn->prepare("DELETE FROM $tabla WHERE id=?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $stmt->close();
    }

    $url = base_url() . "/dashboard.php?page=gestionar_contenido&tipo=" . urlencode($tipo) . "&deleted=1";
    
    echo "
    <script>
      window.location.replace('$url');
    </script>
    <noscript>
      <meta http-equiv='refresh' content='0;url=$url'>
    </noscript>";
    exit();

} catch (Exception $e) {
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
?>