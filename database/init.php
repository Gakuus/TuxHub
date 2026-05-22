<?php
/**
 * Inicializador de base de datos — Sistema Agora
 *
 * Uso:
 *   php database/init.php              # Crear tablas nuevas (no destruye datos)
 *   php database/init.php --force       # DROP + CREATE (reconstrucción completa)
 *
 * Lee credenciales del .env automáticamente.
 */

$required_exts = ['mysqli', 'json', 'mbstring'];
$missing = [];
foreach ($required_exts as $ext) {
    if (!extension_loaded($ext)) $missing[] = $ext;
}

if ($missing) {
    echo "❌ Extensiones PHP faltantes: " . implode(', ', $missing) . "\n";
    echo "   Instálalas y vuelve a intentar.\n";
    exit(1);
}

$force = in_array('--force', $argv ?? []);

// Cargar .env
$env_file = __DIR__ . '/../.env';
if (file_exists($env_file)) {
    $lines = file($env_file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        $line = trim($line);
        if ($line === '' || str_starts_with($line, '#')) continue;
        if (str_contains($line, '=')) {
            [$key, $value] = explode('=', $line, 2);
            $_ENV[trim($key)] = trim($value);
        }
    }
}

$db_host = $_ENV['DB_HOST'] ?? '127.0.0.1';
$db_user = $_ENV['DB_USER'] ?? 'root';
$db_pass = $_ENV['DB_PASS'] ?? '';
$db_name = $_ENV['DB_NAME'] ?? 'db_agora';
$db_port = (int) ($_ENV['DB_PORT'] ?? 3306);

echo "🔧 Conectando a MySQL en {$db_host}:{$db_port}...\n";

$conn = new mysqli($db_host, $db_user, $db_pass, null, $db_port);

if ($conn->connect_error) {
    echo "❌ Error de conexión: " . $conn->connect_error . "\n";
    echo "   Verifica tus credenciales en el archivo .env\n";
    exit(1);
}

echo "✅ Conectado.\n\n";

// Modo reconstrucción total
if ($force) {
    echo "⚠️  Modo --force: eliminando base de datos {$db_name}...\n";
    $conn->query("DROP DATABASE IF EXISTS `{$db_name}`");
    echo "✅ Base de datos eliminada.\n\n";
}

// Ejecutar schema.sql
echo "📦 Ejecutando schema.sql...\n";
$schema = file_get_contents(__DIR__ . '/schema.sql');
if ($schema === false) {
    echo "❌ No se pudo leer database/schema.sql\n";
    exit(1);
}

if ($conn->multi_query($schema)) {
    do { if ($result = $conn->store_result()) $result->free(); } while ($conn->next_result());
    echo "✅ Schema ejecutado correctamente.\n";
} else {
    echo "❌ Error en schema: " . $conn->error . "\n";
    exit(1);
}

// Ejecutar seed.sql
echo "🌱 Ejecutando seed.sql...\n";
$seed = file_get_contents(__DIR__ . '/seed.sql');
if ($seed === false) {
    echo "❌ No se pudo leer database/seed.sql\n";
    exit(1);
}

$conn->select_db($db_name);
if ($conn->multi_query($seed)) {
    do { if ($result = $conn->store_result()) $result->free(); } while ($conn->next_result());
    echo "✅ Seed ejecutado correctamente.\n";
} else {
    echo "❌ Error en seed: " . $conn->error . "\n";
    exit(1);
}

$conn->close();

echo "\n";
echo "═══════════════════════════════════════\n";
echo "  🎉 Base de datos inicializada.\n";
echo "  DB: {$db_name}\n";
echo "  Admin: cédula 00000000 / pass admin123\n";
echo "═══════════════════════════════════════\n";
