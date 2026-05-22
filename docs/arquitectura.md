# Arquitectura

## Routing

```
index.php               → Login (página pública)
dashboard.php           → Dashboard protegido, carga pages/ via ?page=
backend/*.php           → Endpoints AJAX y procesamiento de formularios
pages/*.php             → Vistas incluidas por dashboard.php
```

### Mapeo dashboard.php → pages/

| `?page=` | Archivo incluido |
|----------|-----------------|
| home | `pages/home.php` |
| salones | `pages/salones.php` |
| recursos | `pages/recursos.php` |
| horarios | `pages/horarios.php` |
| registrar | `pages/registrar_usuario.php` |
| gestionar_contenido | `pages/gestionar_contenido.php` |
| cargar_horarios | `pages/cargar_horarios.php` |
| asignar_grupos | `pages/asignar_grupos.php` |
| agregar_salon | `pages/agregar_salon.php` |
| agregar_recursos | `pages/agregar_recurso.php` |
| agregar_materias | `pages/agregar_materias.php` |
| grupos | `pages/grupo.php` |
| profesores | `pages/profesores.php` |

## Base de datos

19 tablas, todas InnoDB con utf8mb4.

### Tablas principales

| Tabla | Propósito |
|-------|-----------|
| `usuarios` | Admins, profesores, alumnos |
| `grupos` | Grupos escolares con turno (mañana/tarde/noche) |
| `materias` | Materias con estado activo/inactivo |
| `salones` | Salones físicos |
| `salon_recursos` | Recursos por salón (TV, PC, pizarra, etc.) |
| `horarios` | Asignación de materia+profesor+aula a un grupo en un día/bloque |
| `asistencias` | Registro de asistencia por alumno |
| `noticias` | Noticias con imagen como ruta de archivo |
| `avisos` | Avisos institucionales |

### Tablas Many-to-Many

| Tabla | Relación |
|-------|----------|
| `grupos_profesores` | Profesores ↔ Grupos |
| `alumnos_grupos` | Alumnos ↔ Grupos |
| `profesor_bloques` | Profesores ↔ Bloques horarios |

### Tablas auxiliares

| Tabla | Propósito |
|-------|-----------|
| `dias` | Catálogo de días (Lunes-Viernes) |
| `bloques_horarios` | Bloques horarios por turno |
| `salon_usos` | Registro de uso de salones |
| `recursos` | Recursos físicos (llaves, controles, alargues) |
| `login_intentos` | Rate limiting de login |
| `login_logs` | Historial de login |
| `password_resets` | Tokens de recuperación |

## Assets

Cada página tiene su propio CSS y JS:

| Página | CSS | JS |
|--------|-----|-----|
| login (index.php) | `css/login.css` | `assets/login.js` |
| dashboard | `css/dashboard.css`, `css/dashboard_mejoras.css` | `assets/dashboard.js` |
| home | `css/home.css` | `assets/home.js` |
| salones | `css/salones.css` | `assets/salones.js` |
| horarios | `css/horarios.css` | `assets/horarios.js` |
| recursos | `css/recursos.css` | `assets/recursos.js` |
| registro | `css/register.css` | `assets/register.js` |
| asignar_grupos | `css/asignar_grupos.css` | `assets/asignar_grupos.js` |
| cargar_horarios | `css/cargar_horario.css` | `assets/cargar_horario.js` |
| agregar_salon | `css/agregar_salon.css` | `assets/agregar_salon.js` |
| agregar_recurso | `css/recursos.css` | `assets/recursos_form.js` |
| materias | `css/materias.css` | `assets/materias.js` |
| gestionar_contenido | `css/gestionar_contenido.css` | `assets/gestionar_contenido.js` |
| profesores | — | `assets/profesores.js` |

## .env

```
DB_HOST=127.0.0.1
DB_USER=Agora
DB_PASS=CARLAYCRISTIAN12
DB_NAME=db_agora
DB_PORT=3306

SMTP_USER=proyectopraca@gmail.com
SMTP_PASS=...
```
