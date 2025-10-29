<?php
require_once __DIR__ . '/db_connection.php';

$tipo = $_GET['tipo'] ?? 'noticias';
$id = $_GET['id'] ?? null;

if ($id) {
    $tabla = $tipo === 'avisos' ? 'avisos' : 'noticias';
    $stmt = $conn->prepare("DELETE FROM $tabla WHERE id=?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $stmt->close();
}

// ✅ Redirección compatible y segura
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
