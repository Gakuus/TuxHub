-- ============================================
-- SISTEMA AGORA - Esquema de Base de Datos
-- Versión rediseñada y normalizada
-- ============================================

CREATE DATABASE IF NOT EXISTS db_agora
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE db_agora;

-- =====================================
-- 1. usuarios
-- =====================================
CREATE TABLE usuarios (
    id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    cedula      VARCHAR(8) NOT NULL UNIQUE,
    nombre      VARCHAR(100) NOT NULL,
    email       VARCHAR(100) NULL UNIQUE,
    password    VARCHAR(255) NOT NULL,
    rol         ENUM('admin', 'profesor', 'alumno') NOT NULL,
    grupo_id    INT UNSIGNED NULL,
    created_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_rol (rol),
    INDEX idx_grupo (grupo_id)
) ENGINE=InnoDB;

-- =====================================
-- 2. grupos
-- =====================================
CREATE TABLE grupos (
    id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    nombre      VARCHAR(50) NOT NULL,
    turno       ENUM('mañana', 'tarde', 'noche') NOT NULL,
    activo      BOOLEAN NOT NULL DEFAULT TRUE,
    created_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

ALTER TABLE usuarios ADD CONSTRAINT fk_usuarios_grupo
    FOREIGN KEY (grupo_id) REFERENCES grupos(id)
    ON DELETE SET NULL ON UPDATE CASCADE;

-- =====================================
-- 3. materias
-- =====================================
CREATE TABLE materias (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    nombre_materia  VARCHAR(100) NOT NULL,
    activa          BOOLEAN NOT NULL DEFAULT TRUE,
    created_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- =====================================
-- 4. salones
-- =====================================
CREATE TABLE salones (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    nombre_salon    VARCHAR(50) NOT NULL,
    capacidad       INT UNSIGNED NULL,
    ubicacion       VARCHAR(100) NULL,
    observaciones   TEXT NULL,
    estado          ENUM('disponible', 'ocupado') NOT NULL DEFAULT 'disponible',
    created_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- =====================================
-- 5. salon_recursos (reemplaza JSON en salones.recursos)
-- =====================================
CREATE TABLE salon_recursos (
    id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    salon_id    INT UNSIGNED NOT NULL,
    recurso     ENUM('television', 'computadoras', 'pizarra', 'proyector', 'aire_acondicionado') NOT NULL,
    cantidad    INT UNSIGNED NOT NULL DEFAULT 1,
    UNIQUE KEY uq_salon_recurso (salon_id, recurso),
    FOREIGN KEY (salon_id) REFERENCES salones(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- =====================================
-- 6. recursos (físicos: llaves, controles, alargues)
-- =====================================
CREATE TABLE recursos (
    id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    nombre      VARCHAR(50) NOT NULL,
    tipo        ENUM('Llave', 'Control', 'Alargue') NOT NULL,
    estado      ENUM('Disponible', 'Ocupado', 'Reservado') NOT NULL DEFAULT 'Disponible',
    descripcion TEXT NULL,
    salon_id    INT UNSIGNED NULL,
    grupo_id    INT UNSIGNED NULL,
    usuario_id  INT UNSIGNED NULL,
    created_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (salon_id)   REFERENCES salones(id)  ON DELETE SET NULL,
    FOREIGN KEY (grupo_id)   REFERENCES grupos(id)   ON DELETE SET NULL,
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE SET NULL,
    INDEX idx_estado (estado),
    INDEX idx_tipo (tipo)
) ENGINE=InnoDB;

-- =====================================
-- 7. bloques_horarios (catálogo de bloques)
-- =====================================
CREATE TABLE bloques_horarios (
    id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    turno       ENUM('mañana', 'tarde', 'noche') NOT NULL,
    hora_inicio TIME NOT NULL,
    hora_fin    TIME NOT NULL,
    UNIQUE KEY uq_bloque_turno (turno, hora_inicio, hora_fin)
) ENGINE=InnoDB;

-- =====================================
-- 8. dias (catálogo)
-- =====================================
CREATE TABLE dias (
    id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    nombre_dia  VARCHAR(15) NOT NULL UNIQUE
) ENGINE=InnoDB;

INSERT INTO dias (id, nombre_dia) VALUES
    (1, 'Lunes'), (2, 'Martes'), (3, 'Miércoles'),
    (4, 'Jueves'), (5, 'Viernes');

-- =====================================
-- 9. horarios (sin columnas denormalizadas)
-- =====================================
CREATE TABLE horarios (
    id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    grupo_id    INT UNSIGNED NOT NULL,
    profesor_id INT UNSIGNED NOT NULL,
    materia_id  INT UNSIGNED NOT NULL,
    salon_id    INT UNSIGNED NULL,
    dia_id      INT UNSIGNED NOT NULL,
    bloque_id   INT UNSIGNED NOT NULL,
    activo      BOOLEAN NOT NULL DEFAULT TRUE,
    created_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (grupo_id)    REFERENCES grupos(id)   ON DELETE CASCADE,
    FOREIGN KEY (profesor_id) REFERENCES usuarios(id) ON DELETE CASCADE,
    FOREIGN KEY (materia_id)  REFERENCES materias(id) ON DELETE CASCADE,
    FOREIGN KEY (salon_id)    REFERENCES salones(id)  ON DELETE SET NULL,
    FOREIGN KEY (dia_id)      REFERENCES dias(id)     ON DELETE RESTRICT,
    FOREIGN KEY (bloque_id)   REFERENCES bloques_horarios(id) ON DELETE RESTRICT,
    UNIQUE KEY uq_horario (grupo_id, profesor_id, materia_id, dia_id, bloque_id),
    INDEX idx_profesor (profesor_id),
    INDEX idx_grupo (grupo_id)
) ENGINE=InnoDB;

-- =====================================
-- 10. asistencias (unificado: dia_id + bloque_id)
-- =====================================
CREATE TABLE asistencias (
    id            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    usuario_id    INT UNSIGNED NOT NULL,
    grupo_id      INT UNSIGNED NOT NULL,
    fecha         DATE NOT NULL,
    estado        ENUM('asistio', 'inasistencia', 'justificado') NOT NULL,
    justificacion TEXT NULL,
    dia_id        INT UNSIGNED NULL,
    bloque_id     INT UNSIGNED NULL,
    salon_id      INT UNSIGNED NULL,
    created_at    TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE,
    FOREIGN KEY (grupo_id)   REFERENCES grupos(id)   ON DELETE CASCADE,
    FOREIGN KEY (dia_id)     REFERENCES dias(id)     ON DELETE SET NULL,
    FOREIGN KEY (bloque_id)  REFERENCES bloques_horarios(id) ON DELETE SET NULL,
    FOREIGN KEY (salon_id)   REFERENCES salones(id)  ON DELETE SET NULL,
    UNIQUE KEY uq_asistencia (usuario_id, grupo_id, fecha, bloque_id),
    INDEX idx_fecha (fecha),
    INDEX idx_usuario (usuario_id)
) ENGINE=InnoDB;

-- =====================================
-- 11. salon_usos
-- =====================================
CREATE TABLE salon_usos (
    id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    salon_id    INT UNSIGNED NOT NULL,
    profesor_id INT UNSIGNED NOT NULL,
    grupo_id    INT UNSIGNED NOT NULL,
    fecha       DATE NOT NULL,
    hora_inicio TIME NOT NULL,
    hora_fin    TIME NOT NULL,
    estado      ENUM('en_uso', 'finalizado') NOT NULL DEFAULT 'en_uso',
    created_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (salon_id)    REFERENCES salones(id)  ON DELETE CASCADE,
    FOREIGN KEY (profesor_id) REFERENCES usuarios(id) ON DELETE CASCADE,
    FOREIGN KEY (grupo_id)    REFERENCES grupos(id)   ON DELETE CASCADE,
    INDEX idx_salon_fecha (salon_id, fecha),
    INDEX idx_estado (estado)
) ENGINE=InnoDB;

-- =====================================
-- 12. grupos_profesores (M:N)
-- =====================================
CREATE TABLE grupos_profesores (
    profesor_id INT UNSIGNED NOT NULL,
    grupo_id    INT UNSIGNED NOT NULL,
    created_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (profesor_id, grupo_id),
    FOREIGN KEY (profesor_id) REFERENCES usuarios(id) ON DELETE CASCADE,
    FOREIGN KEY (grupo_id)    REFERENCES grupos(id)   ON DELETE CASCADE
) ENGINE=InnoDB;

-- =====================================
-- 13. alumnos_grupos (M:N)
-- =====================================
CREATE TABLE alumnos_grupos (
    alumno_id   INT UNSIGNED NOT NULL,
    grupo_id    INT UNSIGNED NOT NULL,
    created_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (alumno_id, grupo_id),
    FOREIGN KEY (alumno_id) REFERENCES usuarios(id) ON DELETE CASCADE,
    FOREIGN KEY (grupo_id)  REFERENCES grupos(id)   ON DELETE CASCADE
) ENGINE=InnoDB;

-- =====================================
-- 14. profesor_bloques (M:N)
-- =====================================
CREATE TABLE profesor_bloques (
    profesor_id INT UNSIGNED NOT NULL,
    bloque_id   INT UNSIGNED NOT NULL,
    grupo_id    INT UNSIGNED NOT NULL,
    created_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (profesor_id, bloque_id, grupo_id),
    FOREIGN KEY (profesor_id) REFERENCES usuarios(id)           ON DELETE CASCADE,
    FOREIGN KEY (bloque_id)   REFERENCES bloques_horarios(id)    ON DELETE CASCADE,
    FOREIGN KEY (grupo_id)    REFERENCES grupos(id)              ON DELETE CASCADE
) ENGINE=InnoDB;

-- =====================================
-- 15. noticias (imagen como ruta de archivo)
-- =====================================
CREATE TABLE noticias (
    id                INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    titulo            VARCHAR(100) NOT NULL,
    contenido         TEXT NOT NULL,
    imagen_ruta       VARCHAR(255) NULL,
    autor_id          INT UNSIGNED NULL,
    fecha_publicacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at        TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (autor_id) REFERENCES usuarios(id) ON DELETE SET NULL,
    INDEX idx_fecha (fecha_publicacion DESC)
) ENGINE=InnoDB;

-- =====================================
-- 16. avisos
-- =====================================
CREATE TABLE avisos (
    id                INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    titulo            VARCHAR(100) NOT NULL,
    mensaje           TEXT NOT NULL,
    autor_id          INT UNSIGNED NULL,
    fecha_publicacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at        TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (autor_id) REFERENCES usuarios(id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- =====================================
-- 17. login_intentos (rate limiting)
-- =====================================
CREATE TABLE login_intentos (
    ip              VARCHAR(45) NOT NULL PRIMARY KEY,
    cedula_intento  VARCHAR(8) NULL,
    intentos        INT UNSIGNED NOT NULL DEFAULT 0,
    ultimo_intento  TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_cedula (cedula_intento),
    INDEX idx_tiempo (ultimo_intento)
) ENGINE=InnoDB;

-- =====================================
-- 18. login_logs (historial de login)
-- =====================================
CREATE TABLE login_logs (
    id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    usuario_id  INT UNSIGNED NULL,
    ip          VARCHAR(45) NULL,
    user_agent  TEXT NULL,
    exito       BOOLEAN NULL,
    fecha       TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE SET NULL,
    INDEX idx_usuario (usuario_id),
    INDEX idx_fecha (fecha)
) ENGINE=InnoDB;

-- =====================================
-- 19. password_resets
-- =====================================
CREATE TABLE password_resets (
    id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id     INT UNSIGNED NOT NULL UNIQUE,
    token       VARCHAR(64) NOT NULL,
    expira      DATETIME NOT NULL,
    ip_address  VARCHAR(45) NULL,
    created_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES usuarios(id) ON DELETE CASCADE,
    INDEX idx_token (token),
    INDEX idx_expira (expira)
) ENGINE=InnoDB;
