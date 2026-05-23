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
│   └── chat.js            # Widget chat AI
├── css/                   # Hojas de estilo
│   └── chat.css           # Estilos del chat AI
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

## Configuración

El proyecto usa un archivo `.env` para todas las credenciales sensibles (base de datos, SMTP, reCAPTCHA, AI Chat). Ver `.env.example` como plantilla.

### AI Chat

Widget flotante accesible desde el dashboard. Soporta:

| Proveedor | API Key | Costo |
|-----------|---------|-------|
| **Groq** | Desde [console.groq.com](https://console.groq.com) | Gratis |
| **OpenAI** | Desde platform.openai.com | Pago |
| **Gemini** | Desde aistudio.google.com | Free tier (limitado) |
| **Ollama** | Local (localhost:11434) | Gratis, sin internet |
| **DeepSeek** | Desde platform.deepseek.com | Crédito inicial gratis |

Ejemplo de config para Groq (recomendado):

```env
CHAT_PROVIDER=openai
CHAT_API_URL=https://api.groq.com/openai/v1/chat/completions
CHAT_API_KEY=gsk_tu_key
CHAT_MODEL=llama-3.1-8b-instant
```

> Seguridad: API key solo server-side, CSRF con hash_equals, rate limiting 20 req/min, respuestas sanitizadas contra XSS.

### Toast Notifications

Sistema de notificaciones toast implementado en `css/dashboard.css` y `dashboard.php`.

**Cómo funciona:**

- Los mensajes del servidor se renderizan como `<div class="alert alert-success/danger/warning/info">` de Bootstrap.
- Al cargar la página, un script en `dashboard.php` detecta estos elementos y los convierte en toasts `.toast-notification` con el mismo contenido y clase de color.
- Los alerts originales se ocultan (`display: none`), los toasts se agregan al `body`.

**Diseño visual:**

| Elemento | Descripción |
|----------|-------------|
| Fondo | Glass-morphism con `backdrop-filter: blur()` |
| Barra izquierda | Gradiente lineal según tipo (verde/rojo/ámbar/azul) |
| Icono | Círculo con gradiente y SVG inline |
| Progreso | Barra horizontal que se reduce en 4s, luego el toast se cierra |
| Posición | `fixed; bottom: 1.5rem; left: 50%; translateX(-50%)` — centrado inferior |
| Animación | `scale(0.8 → 1)` + `translateY(20px → 0)` con `cubic-bezier` tipo spring |

**Tipos y colores:**

- `.alert-success` → verde (icono check)
- `.alert-danger` → rojo (icono X)
- `.alert-warning` → ámbar (icono alerta)
- `.alert-info` → azul (icono info, sin progreso)

## Credenciales por defecto

- Admin: `00000000` / `admin123`
