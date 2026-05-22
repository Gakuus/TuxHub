# Análisis Técnico - Sistema Agora

## Resumen

Proyecto PHP sin framework para gestión educativa. Arquitectura clásica de archivos PHP planos con MySQLi, Bootstrap 5 vanilla y JS dividido en clases ES6. Buen esfuerzo general con implementación de CSRF, rate limiting y prepared statements en varios lugares, pero con vulnerabilidades críticas por resolver.

---

## CRÍTICO - Vulnerabilidades de seguridad

### 1. SQL Injection en asistencias

**Archivos:** `backend/asistencias_guardar.php` (líneas 17-19), `backend/guardar_asistencia.php` (líneas 20-29 y 70-72)

Las variables `$usuario_id`, `$fecha`, `$grupo_id`, `$estado`, `$horario_id`, `$salon_id` se interpolan directamente en la consulta SQL sin usar prepared statements. Un atacante puede inyectar SQL arbitrario vía POST.

**Solución:** Migrar a prepared statements como ya se hace en `handle_register.php`.

### 2. SQL Injection en obtener_horarios.php

**Archivo:** `backend/obtener_horarios.php` (líneas 12-18)

`$profesor_id` y `$grupo_id` vienen de `$_GET` y se insertan directamente en la query.

### 3. SQL Injection en login_handler

**Archivo:** `backend/login_handler.php` (línea 216)

```php
$conn->query("DELETE FROM login_intentos WHERE ip = '$remoteip'");
```

`$remoteip` proviene de `$_SERVER['REMOTE_ADDR']` - un atacante podría manipular headers HTTP (como `X-Forwarded-For`) si hay un proxy mal configurado.

### 4. Credenciales hardcodeadas

| Archivo | Dato | Riesgo |
|---------|------|--------|
| `backend/db_connection.php:9-11` | Usuario/contraseña BD | Exposición en repositorio |
| `backend/send_email.php:15-16` | SMTP user + app password | Cuenta Gmail comprometida |
| `backend/login_handler.php:112` | reCAPTCHA secret key | Uso malicioso del captcha |
| `index.php:154` | reCAPTCHA site key | Menor riesgo |

**Solución:** Usar variables de entorno vía `.env` con `vlucas/phpdotenv`.

### 5. Hardcoded paths en recursos_backend.php

**Archivo:** `backend/recursos_backend.php` (líneas 8, 65, 81)

```php
header('Location: /Agora/Agora/dashboard.php?page=recursos...');
```

Esto asume que el proyecto está en `/Agora/Agora/`. En un servidor diferente o en subdirectorio distinto, todas las redirecciones rompen.

**Solución:** Usar rutas relativas o una constante `BASE_PATH`.

### 6. auth_admin.php redirige a .html

**Archivo:** `backend/auth_admin.php` (línea 16)

```php
header("Location: ../index.html?error=acceso_denegado");
```

Debería ser `../index.php`. Además, redirige a login sin mensaje de error visible (la página de login no maneja el parámetro `error=acceso_denegado`).

### 7. Falta validación CSRF en endpoints POST

Tienen CSRF: `login_handler.php`, `logout.php`

**No tienen CSRF:**
- `asistencias_guardar.php`
- `guardar_asistencia.php`
- `handle_register.php`
- `recursos_backend.php` (crear, actualizar, marcar_uso, reservar)
- `delete_contenido.php`
- `eliminar_grupo.php`
- `save_contenido.php`
- `grupos_create.php`

**Solución:** Implementar verificación de token CSRF en todos los endpoints POST.

---

## ALTA - Problemas de calidad

### 8. Sin archivo .env ni .gitignore

No existe `.gitignore`. El directorio `vendor/` y las credenciales están en el repo.

**Solución:**
```gitignore
vendor/
.env
*.log
```

### 9. Posible XSS en guardar_asistencia.php

**Archivo:** `backend/guardar_asistencia.php` (líneas 38-48)

Los valores de la base de datos se imprimen directamente con `echo` y `{}` sin escapado HTML (`htmlspecialchars`). Aunque los datos vienen de la BD (no directamente del usuario), si un atacante inserta datos maliciosos vía SQLi, se ejecutaría XSS.

### 10. handle_register.php inconsistencia en bind_param

**Archivo:** `backend/handle_register.php` (líneas 68-76)

```php
$stmt->bind_param("ssssssi", $cedula, $nombre, $email, $password_hashed, $rol, $grupo_id);
```

El formato string `"ssssssi"` tiene 7 caracteres pero solo se pasan 6 variables. La línea 65-66 inserta 6 columnas. El tipo string no coincide con la cantidad de parámetros.

### 11. Estilos CSS duplicados y desorganizados

Existen `dashboard.css` y `dashboard_mejoras.css` (posible confusión). Cada página tiene su propio CSS sin un sistema de diseño coherente (colores, espaciados, tipografía no estandarizados).

**Solución:** Unificar en un tema base + CSS específico por módulo, usando variables CSS.

### 12. Código duplicado de manejo de sesión

Cada backend file hace `session_start()` + validación de sesión de forma inconsistente. Algunos verifican `$_SESSION['user_id']`, otros no verifican nada.

**Solución:** Centralizar en un middleware o función `require_auth()`.

### 13. Código muerto

**Archivo:** `backend/login_handler.php` (líneas 2-5)

```php
error_reporting(E_ALL);
ini_set('display_errors', 1);
```

Comentario dice "TEMPORAL: Para debug - ELIMINAR DESPUÉS DE FUNCIONAR" pero sigue en producción.

---

## MEDIA - Mejoras recomendadas

### 14. Sin .htaccess (Apache) o configuración de seguridad

Faltan reglas para:
- Denegar acceso a directorios (`backend/`, `vendor/`, `assets/`)
- Forzar HTTPS
- Configurar headers de seguridad (HSTS, CSP más estricto)
- Prevenir hotlinking de imágenes

### 15. Manejo de errores inconsistente

- `db_connection.php` redirige a `index.php?error=db_conexion` pero solo si no estamos en login
- `recursos_backend.php` muestra HTML completo de confirmación
- `guardar_asistencia.php` devuelve JSON
- Los archivos de `pages/` no tienen manejo de errores consistente

### 16. Sin logging centralizado

Se usa `error_log()` disperso. Sin sistema de logging estructurado (niveles, contexto, archivos separados).

### 17. Carga de CSS/JS redundante

Cada página incluye Bootstrap desde CDN. El dashboard ya lo carga. Las páginas incluidas vía `include` (pages/*.php) también lo cargarían si tienen el `<head>` completo. Revisar si hay duplicación.

### 18. Sin sistema de migraciones

No hay archivos `.sql` para crear/actualizar el esquema de BD. Las tablas de `login_intentos` y `login_logs` se crean con `CREATE TABLE IF NOT EXISTS` en el código.

### 19. i18n a medio implementar

Existe botón de cambio de idioma en el dashboard pero sin implementación real de traducciones.

---

## OPTIMIZACIONES

### 20. Consultas N+1 potenciales

En varias páginas se hacen consultas dentro de loops PHP. Ej: al listar recursos puede haber consultas adicionales por cada elemento.

**Solución:** Usar JOINs y consultas agregadas.

### 21. Sin caché

No hay caché de horarios, grupos, ni datos maestros. Cada request carga todo desde BD.

**Sugerencia:** Implementar caché en archivo (para datos estáticos como horarios) o usar Redis/Memcache si hay disponibilidad.

### 22. Assets sin minificar/compilar

JS y CSS se sirven sin minificar ni compilar. Bootstrap se carga desde CDN (bien), pero los archivos propios no están optimizados.

**Sugerencia:** Usar un bundler simple (esbuild, vite) o al menos minificar manualmente.

### 23. Imágenes sin optimizar

`img/its.jpg`, `its2.jpeg`, `its3.jpeg`, `itsp.jpeg` no tienen lazy loading en todos los casos.

### 24. Sin Service Worker / PWA

Aunque hay meta tags para mobile, no hay Service Worker ni manifiesto para funcionar offline o como PWA.

---

## PRIORIDAD DE ACCIÓN

| Prioridad | Acción |
|-----------|--------|
| **🔥 Inmediata** | Arreglar SQL injections (asistencias_guardar, guardar_asistencia, obtener_horarios, login_handler) |
| **🔥 Inmediata** | Poner credenciales en `.env` y eliminar del repo |
| **🔥 Inmediata** | Arreglar rutas hardcodeadas en recursos_backend.php |
| **🔴 Alta** | Agregar validación CSRF a todos los endpoints POST |
| **🔴 Alta** | Agregar `.gitignore` y eliminar vendor del repo |
| **🟡 Media** | Centralizar manejo de sesión y autenticación |
| **🟡 Media** | Arreglar bind_param inconsistente en handle_register.php |
| **🟡 Media** | Unificar CSS y eliminar duplicados |
| **🟢 Baja** | Implementar logging, caché, migraciones, minificación |
