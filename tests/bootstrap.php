<?php
require_once __DIR__ . '/../backend/helpers.php';
require_once __DIR__ . '/../backend/config.php';

// Override session for CLI
if (session_status() === PHP_SESSION_NONE) {
    $_SESSION = [];
}
