# Pendientes — Actualizado

## ✅ Resuelto

### Sprint 0 — Seguridad
- SQL Injection (7 archivos)
- CSRF (7 endpoints)
- XSS (4 archivos, 17 lugares)
- Hardcoded paths eliminados
- Error disclosure corregido (10 endpoints)
- Subida de archivos segura
- auth_admin.php simplificado
- `$remoteip` bug en login_handler PHP
- Debug display eliminado
- reCAPTCHA server-side implementado
- Uploads/.htaccess protección

### Sprint 0.5 — Arquitectura
- Centralización de sesión (init_session)
- Structured logging (app_log)
- Password reset rediseñado + seguridad
- Validación servidor de contraseña (register + reset)
- Rate limiting en POST endpoints (6 archivos)
- CSP headers en dashboard.php
- HTTPS redirect listo en .htaccess
- Permissions-Policy header
- Páginas error 403/404/500 personalizadas
- backend/procesar_asistencia.php creado
- CSS variables globales
- Índices BD en schema.sql

---

## 🔴 CRÍTICO

Ninguno identificado.

## 🟡 ALTA

- [ ] **Pruebas automatizadas** — Sin tests, riesgo de regresiones
- [ ] **Paginación en listados** — Recursos, salones, horarios, asistencias
- [ ] **Caché de datos maestros** — grupos, materias, salones

## 🟢 MEDIA / FRONTEND

- [ ] **Exportación PDF/CSV** — Botones placeholder no implementados
- [ ] **Búsqueda en tiempo real** — Tablas sin filtro
- [ ] **Notificaciones toast** — Reemplazar alerts Bootstrap
- [ ] **Sidebar responsivo** — Verificar en todos los tamaños
- [ ] **Sistema de migraciones** — Reemplazar CREATE TABLE IF NOT EXISTS
- [ ] **Minificación de assets** — CSS/JS propios
- [ ] **Spinners/loaders** en AJAX
