# Plan de Sprints — Estado actual

## ✅ COMPLETADO — Sprint 0: Seguridad (emergencia)

> **Duración:** Actual
> **Estado:** ✅ Terminado — todas las vulnerabilidades críticas corregidas

| Tarea | Archivos | Estado |
|-------|----------|--------|
| SQL Injection → prepared statements | 7 archivos backend | ✅ |
| CSRF faltante | 5 endpoints POST | ✅ |
| XSS en output | 17 lugares en 4 archivos | ✅ |
| Hardcoded paths | 13+ ocurrencias | ✅ |
| Centralización de sesión | `init_session()` en helpers.php | ✅ |
| Structured logging | `app_log()` con JSON | ✅ |
| Information disclosure | 10 endpoints | ✅ |
| Subida de archivos segura | `save_contenido.php` | ✅ |
| Rediseño flujo password reset | 4 páginas + CSRF + logs | ✅ |
| Auth admin simplificado | `auth_admin.php` | ✅ |
| Uploads protection | `.htaccess` | ✅ |
| Composer install | PHPMailer v7 | ✅ |

---

## 🔄 Pendiente — Sprint 1: Corrección de errores y consistencia base

| Tarea | Estado |
|-------|--------|
| Archivos faltantes referenciados | 🔴 Por revisar |
| Unificar CSS (variables globales) | ❌ |
| Sidebar toggle responsivo | ❌ |
| `table-responsive` en tablas | ❌ |
| Feedback visual en formularios | ❌ |
| Permisos `uploads/noticias/` | ❌ |

---

## Sprint 2: Overhaul visual

| Tarea | Estado |
|-------|--------|
| Login rediseñado | ✅ (ya se hizo en Sprint 0 con orbs, glass card, floating labels) |
| Definir paleta de colores CSS | ❌ |
| Dashboard sidebar/header | ❌ |
| Unificar estilos botones/cards/forms | ❌ |
| Home.php hero + noticias | ❌ |

---

## Sprint 3: UX y funcionalidad

| Tarea | Estado |
|-------|--------|
| Spinners/loaders AJAX | ❌ |
| Notificaciones toast | ❌ |
| Búsqueda en tiempo real | ❌ |
| Paginación | ❌ |
| Modo oscuro funcional | ❌ |

---

## Sprint 4: Mobile y pulido

| Tarea | Estado |
|--------|--------|
| Responsive en todas las páginas | ❌ |
| Menú hamburguesa mobile | ❌ |
| Favicon | ❌ |
| Meta tags OG | ❌ |
| Exportación PDF/CSV | ❌ |

---

## 🏁 Siguiente prioridad recomendada

```
Sprint 1 → Archivos faltantes + CSS unificado → luego reCAPTCHA en login → HTTPS forzado → el resto
```
