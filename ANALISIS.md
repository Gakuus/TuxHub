# Análisis Técnico — Sistema Agora

## Resumen

Proyecto PHP sin framework para gestión educativa. Arquitectura clásica de archivos PHP planos con MySQLi, Bootstrap 5 vanilla y JS dividido en clases ES6.

---

## 🛡️ Seguridad — Estado final

### ✅ Corregido — CRÍTICO

| # | Vulnerabilidad | Solución |
|---|---------------|----------|
| 1 | **SQL Injection** (8 archivos) | Todas las consultas migradas a prepared statements con `bind_param()`. Último fichero corregido: `pages/agregar_materias.php` (SELECT/INSERT/UPDATE con interpolación directa) |
| 2 | **CSRF faltante** (7 endpoints) | `csrf_verify()` agregado en todos los POST handlers |
| 3 | **XSS reflejado** (4 archivos, 17+ lugares) | `htmlspecialchars()`, `urlencode()`, `json_encode()` según contexto |
| 4 | **Rutas hardcodeadas** | `/Agora/backend/` y `/Agora/Agora/` eliminados, reemplazados con rutas relativas |
| 5 | **Information disclosure** (10 archivos) | `$conn->error` / `$stmt->error` expuestos → logging + mensajes genéricos |
| 6 | **Subida de archivos insegura** | `save_contenido.php`: nombre con `bin2hex(random_bytes(8))` + extensión saneada, ruta absoluta |
| 7 | **reCAPTCHA server-side** | `login_handler.php` verifica con Google API si `RECAPTCHA_SECRET_KEY` está configurada |
| 8 | **Host Header Injection** | `base_url()` ahora valida contra `ALLOWED_HOSTS` del `.env` |

### ✅ Corregido — ALTA

| # | Mejora | Detalle |
|---|--------|---------|
| 9 | **Centralización de sesión** | `init_session()` con httponly, samesite, strict mode, secure flag |
| 10 | **Autenticación consistente** | `require_auth()` en todos los backend files |
| 11 | **Logging estructurado** | `app_log()` con JSON en `logs/app-YYYY-MM-DD.log` |
| 12 | **Password reset rediseñado** | 4 páginas con diseño login + CSRF + validación token + rate limiting + mensajes genéricos |
| 13 | **Validación servidor contraseña** | `handle_register.php`, `password_update.php`: mayúscula, minúscula, dígito, longitud 8-24 |
| 14 | **Rate limiting** | 6 POST endpoints + helper `rate_limit_check()` |
| 15 | **CSP headers** | `index.php` y `dashboard.php` con CSP restrictivo |
| 16 | **HTTPS redirect** | Listo en `.htaccess` (comentado hasta tener SSL) |
| 17 | **Páginas de error** | `403.php`, `404.php`, `500.php` con diseño login |
| 18 | **.htaccess mejorado** | Permissions-Policy, ErrorDocument, protección vendor/.git/backend |
| 19 | **Debug display eliminado** | `error_reporting(E_ALL)` quitado de `password_reset_request.php`, `horarios_profesores.php` |
| 20 | **Debug SQL DESCRIBE eliminado** | `agregar_materias.php` exponía schema de tablas en error_log |
| 21 | **$_SERVER['PHP_SELF'] eliminado** | 8 ocurrencias en `salones.php` reemplazadas por string fijo |

### ✅ Funcionalidades nuevas

| # | Característica | Detalle |
|---|---------------|---------|
| 22 | **Paginación** | 3 páginas: `recursos.php` (12/pág), `gestionar_contenido.php` (15/pág), `grupo.php` (20/pág). Helpers: `paginate()`, `render_pagination()` |
| 23 | **Búsqueda en tiempo real** | `recursos.php` (por nombre), `gestionar_contenido.php` (por título). Debounce 400ms, respeta filtros y paginación |
| 24 | **Exportación CSV** | `backend/exportar_horario.php` genera CSV con BOM, respeta filtros. Botón en `horarios.php` |
| 25 | **Archivo faltante creado** | `backend/procesar_asistencia.php` (3 endpoints: cargar_grupos, cargar_historial, guardar_asistencia) |
| 26 | **CSS variables globales** | `css/variables.css` con paleta unificada y dark mode |
| 27 | **Protección uploads** | `.htaccess` en `uploads/` bloquea ejecución PHP y listado |
| 28 | **Helpers nuevos** | `sanitize_filename()`, `paginate()`, `render_pagination()`, `validate_password_strength()`, `rate_limit_check()` |
| 29 | **Tests automatizados** | 19 tests (runner propio por falta de ext-xmlwriter). Cobertura: paginate, validate_password, sanitize, csrf, render_pagination |

---

## 🔴 CRÍTICO pendiente

**Ninguno.** Todos los SQL injection, XSS, CSRF y Host Header Injection han sido corregidos.

---

## 🟡 Recomendaciones futuras

| # | Tarea | Prioridad |
|---|-------|-----------|
| 1 | **Migrar a Slim 4** para routing y middleware centralizado | Media |
| 2 | **Paginación en más listados** (salones, horarios, home) | Baja |
| 3 | **Exportación PDF** además de CSV | Baja |
| 4 | **Caché de datos maestros** (grupos, materias, salones) | Baja |
| 5 | **Modo oscuro funcional** (botón ya existe) | Baja |
| 6 | **Notificaciones toast** reemplazando alerts | Baja |
| 7 | **Minificación de assets** CSS/JS propios | Baja |
| 8 | **Sistema de migraciones** para BD | Baja |

---

## 📊 Resumen de intervención

| Métrica | Valor |
|---------|-------|
| Archivos PHP modificados | ~35 |
| Vulnerabilidades críticas cerradas | 8 |
| Vulnerabilidades altas cerradas | 12 |
| Funcionalidades nuevas | 9 |
| Líneas de código agregadas | ~800 |
| Líneas eliminadas (código inseguro) | ~150 |
| Tests | 19, 0 fallos |
| Dependencias agregadas | PHPUnit ^10 (dev), PHPMailer ^7.0 |
