<?php
// backend/db_connection.php

$host     = "127.0.0.1";       // Servidor MySQL
$user     = "Agora";           // Usuario asignado
$password = "CREDENCIALES_REVOCADAS"; // Contraseña asignada
$database = "db_Agora";        // Nombre de la base de datos
$port     = 3306;              // Puerto MySQL

// Crear conexión con MySQLi orientado a objetos
$conn = new mysqli($host, $user, $password, $database, $port);

// Chequear por errores de conexión
if ($conn->connect_error) {
    die("❌ Error de conexión: " . $conn->connect_error);
}

// Asegurar que la conexión use el formato de caracteres UTF-8
$conn->set_charset("utf8mb4");
?>
