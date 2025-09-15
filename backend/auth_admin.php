<?php
// backend/auth_admin.php

// Inicia la sesión para poder acceder a las variables de sesión.
session_start();

// 1. Verifica si el usuario ha iniciado sesión.
// 2. Verifica si el rol del usuario es 'administrador'.
// La variable $_SESSION['user_rol'] fue creada en tu login_handler.php.
if (!isset($_SESSION['user_rol']) || $_SESSION['user_rol'] !== 'admin') {
    
    // Si no es un administrador, lo redirige a la página de inicio de sesión.
    // Puedes cambiar '../index.html' por la página a la que quieras enviarlos.
    header("Location: ../index.html?error=acceso_denegado");
    
    // Detiene la ejecución del script para que no se muestre nada del contenido protegido.
    exit();
}
?>