# Sistema Agora - Gestión Académica

Sistema web para la gestión integral de un instituto educativo. Administra horarios, salones, recursos, grupos, materias, asistencias y usuarios con roles diferenciados.

## Requisitos

- PHP 8.0+
- MySQL / MariaDB
- Composer
- Servidor web (Apache / Nginx)

## Instalación

```bash
# Clonar el repositorio
git clone <repo-url> && cd TuxHub

# Instalar dependencias de PHP
composer install

# Configurar base de datos (ver db_connection.php)
# Importar esquema SQL (pendiente de crear)

# Configurar servidor web para que apunte al directorio raíz
```

## Configuración

> **Importante:** Las credenciales están actualmente hardcodeadas. Se recomienda migrar a variables de entorno (`.env`).

| Archivo | Parámetro | Descripción |
|---------|-----------|-------------|
| `backend/db_connection.php` | `$host`, `$user`, `$password`, `$database` | Conexión MySQL |
| `backend/send_email.php` | `$mail->Username`, `$mail->Password` | SMTP Gmail |
| `backend/login_handler.php` | `$recaptcha_secret` | reCAPTCHA v2 secret key |
| `index.php` | `data-sitekey` | reCAPTCHA v2 site key |

## Estructura del proyecto

```
├── assets/          # JavaScript (ES6 classes por módulo)
├── backend/         # Handlers PHP (API endpoints)
├── css/             # Hojas de estilo individuales
├── img/             # Imágenes y logo
├── pages/           # Vistas parciales (incluidas desde dashboard.php)
├── vendor/          # Dependencias Composer
├── dashboard.php    # Shell principal post-login
├── index.php        # Página de login
├── composer.json    # Dependencias PHP
└── password_*.php   # Recuperación de contraseña
```

## Roles de usuario

| Rol | Permisos |
|-----|----------|
| **Admin** | Acceso completo: CRUD usuarios, materias, grupos, salones, recursos, horarios, contenido, asistencias |
| **Profesor** | Registrar alumnos, ver horarios, marcar asistencia, gestionar recursos |
| **Alumno** | Vista de horarios y recursos (solo lectura) |

## Funcionalidades principales

- **Autenticación**: Login con cédula (8 dígitos), reCAPTCHA v2, rate limiting por IP, CSRF tokens, sesión con timeout (15 min)
- **Dashboard**: Panel con sidebar dinámico según rol, modo oscuro, selector de idioma
- **Salones**: CRUD con capacidad, ubicación, equipamiento; estado en tiempo real (disponible/ocupado)
- **Recursos**: Gestión de llaves, controles remotos, alargues; préstamo con asignación a grupo
- **Horarios**: Visualización por grupo/día/bloque horario
- **Materias**: CRUD con activar/desactivar
- **Grupos**: CRUD con activar/desactivar y asignación de profesor
- **Asistencias**: Registro y consulta por fecha, grupo, profesor
- **Contenido**: Noticias y avisos con imágenes (CRUD admin)
- **Recuperación de contraseña**: Vía email con PHPMailer + Gmail SMTP

## Licencia

Sin licencia definida.
