# Pendientes

## 🔴 Criticos

- [ ] **Archivos faltantes referenciados en el frontend**
  - `backend/procesar_asistencia.php` (referenciado en `assets/profesores.js`)
  - Varios CSS/JS listados en pages/ no existen en assets/ o css/

- [ ] **Verificar permisos de uploads/**
  - `uploads/noticias/` necesita permisos de escritura para el servidor web

- [ ] **Password reset** sin servidor SMTP configurado — PHPMailer intenta conectar a Gmail

## 🟡 Frontend — Errores visuales

- [ ] **Inconsistencia de estilos entre páginas**
  - Cada página tiene su propio CSS con estilos distintos
  - Faltan variables CSS globales (colores, fuentes, espaciado)
  - Los `.btn-custom`, `.btn-primary-custom`, etc. varían por página

- [ ] **Sidebar del dashboard**
  - El toggle sidebar no funciona correctamente en todos los tamaños
  - Los items activos no se resaltan consistentemente

- [ ] **Tablas sin diseño responsive**
  - `ver_asistencias.php` tabla con datos crudos sin formato
  - Varias tablas no tienen clase `table-responsive`

- [ ] **Formularios sin estilos unificados**
  - `agregar_salon.php` tiene su propio CSS separado
  - `registrar_usuario.php` tiene su propio CSS
  - Validaciones inconsistentes

- [ ] **Falta de feedback visual**
  - No hay spinners/loaders en operaciones AJAX
  - Éxito/error se muestra de forma distinta según la página

## 🟢 Frontend — Mejoras visuales

- [ ] **Sistema de diseño unificado**
  - Paleta de colores global (CSS custom properties)
  - Tipografía consistente
  - Espaciado y márgenes uniformes

- [ ] **Dashboard principal (home.php)**
  - Hero section con carrusel (actualmente 3 slides con imágenes locales)
  - Sección de noticias con cards
  - Mejorar espaciado y jerarquía visual

- [ ] **Tarjetas de salones** (`salones.php`)
  - Mejorar diseño de las cards
  - Iconos de recursos más vistosos
  - Indicador de estado más claro

- [ ] **Tabla de horarios** (`horarios.php`)
  - Celdas con colores por materia (ya implementado parcialmente)
  - Tooltips con info completa
  - Vista semanal más clara

- [ ] **Página de recursos** (`recursos.php`)
  - Diseño más moderno
  - Filtros más intuitivos
  - Tarjetas en lugar de tabla

## 🔵 Base de datos

- [ ] El schema.sql usa `db_agora` (minúsculas) — consistente con .env actual

## ⚪ Mejoras generales

- [ ] Agregar favicon
- [ ] Meta tags OG para compartir
- [ ] Modo oscuro (ya hay botón en dashboard pero no implementado)
- [ ] Paginación en listados grandes
- [ ] Búsqueda en tiempo real en tablas
- [ ] Exportar a PDF/CSV (hay botón en horarios pero no implementado)
