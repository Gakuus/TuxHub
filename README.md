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

Copiar `.env.example` como `.env` y completar:

```env
# Base de datos
DB_HOST=127.0.0.1
DB_USER=usuario
DB_PASS=contraseña
DB_NAME=db_agora

# SMTP (opcional, para recuperación de contraseña)
SMTP_HOST=smtp.gmail.com
SMTP_USER=tu_correo@gmail.com
SMTP_PASS=contraseña_app

# reCAPTCHA v2 (opcional)
RECAPTCHA_SITE_KEY=tu_site_key
RECAPTCHA_SECRET_KEY=tu_secret_key

# AI Chat (opcional)
# Groq (gratis, sin tarjeta): https://console.groq.com
CHAT_PROVIDER=openai
CHAT_API_URL=https://api.groq.com/openai/v1/chat/completions
CHAT_API_KEY=gsk_tu_key
CHAT_MODEL=llama-3.1-8b-instant
```

> `.env` está protegido por `.htaccess` y no se puede acceder vía web.

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
- **Chat AI**: Widget flotante con asistente virtual. Responde preguntas sobre el proyecto usando Groq (gratis), OpenAI, Gemini, Ollama o DeepSeek. CSRF + rate limiting + XSS-safe
- **Toast notifications**: Sistema de notificaciones toast con glass-morphism, animación spring-like y barra de progreso. Convierte automáticamente los alerts PHP tradicionales en toasts visualmente modernos

## Licencia

Sin licencia definida.
