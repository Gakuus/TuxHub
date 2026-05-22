# Sistema Agora

Sistema de gestión académica para instituciones educativas.

## Stack

- **Backend:** PHP 8.4
- **Base de datos:** MariaDB 11.8
- **Frontend:** Bootstrap 5.3, CSS vanilla, JavaScript vanilla
- **Dependencias:** PHPMailer (Composer)

## Estructura del proyecto

```
/
├── index.php              # Login
├── dashboard.php          # Dashboard principal (SPA layout)
├── password_reset_*.php   # Recuperación de contraseña
├── backend/               # Lógica PHP (controladores)
│   ├── db_connection.php  # Conexion a BD (lee .env)
│   ├── helpers.php        # Funciones utilitarias
│   ├── config.php         # Cargador de .env
│   └── *.php              # Endpoints
├── pages/                 # Vistas (incluidas por dashboard.php)
├── assets/                # JavaScript
├── css/                   # Hojas de estilo
├── img/                   # Imagenes
├── uploads/noticias/      # Imagenes de noticias
├── database/              # Schema + seed SQL
│   └── init.php           # Instalador autónomo
├── vendor/                # Composer dependencies
└── docs/                  # Documentación
```

## Instalación

```bash
php database/init.php
```

## Ejecutar

```bash
php -S localhost:8000
```

## Credenciales por defecto

- Admin: `00000000` / `admin123`
