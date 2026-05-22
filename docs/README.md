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

## Credenciales por defecto

- Admin: `00000000` / `admin123`
