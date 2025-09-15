<?php
// backend/db_connection.php

$host = "127.0.0.1";
$user = "root";     // Usuario por defecto de XAMPP
$password = "CREDENCIALES_REVOCADAS";     // Contraseña por defecto de XAMPP
$database = "instituto_db";

// Crear conexión con MySQLi orientado a objetos
$conn = new mysqli($host, $user, $password, $database);

// Chequear por errores de conexión
if ($conn->connect_error) {
    // Detiene la ejecución y muestra el error
    die("Error de conexión: " . $conn->connect_error);
}

// Asegurar que la conexión use el formato de caracteres UTF-8
$conn->set_charset("utf8");
?>