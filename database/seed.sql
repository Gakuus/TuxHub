-- ============================================
-- SISTEMA AGORA - Datos Iniciales (Seed)
-- ============================================

USE db_agora;

-- =====================================
-- Bloques horarios por turno
-- =====================================
INSERT INTO bloques_horarios (turno, hora_inicio, hora_fin) VALUES
    -- Mañana
    ('mañana', '07:00', '07:45'),
    ('mañana', '07:45', '08:30'),
    ('mañana', '08:30', '09:15'),
    ('mañana', '09:15', '10:00'),
    ('mañana', '10:00', '10:45'),
    ('mañana', '10:45', '11:30'),
    ('mañana', '11:30', '12:15'),
    ('mañana', '12:15', '13:00'),
    -- Tarde
    ('tarde', '13:00', '13:45'),
    ('tarde', '13:45', '14:30'),
    ('tarde', '14:30', '15:15'),
    ('tarde', '15:15', '16:00'),
    ('tarde', '16:00', '16:45'),
    ('tarde', '16:45', '17:30'),
    ('tarde', '17:30', '18:15'),
    ('tarde', '18:15', '19:00'),
    -- Noche
    ('noche', '19:00', '19:45'),
    ('noche', '19:45', '20:30'),
    ('noche', '20:30', '21:15'),
    ('noche', '21:15', '22:00');

-- =====================================
-- ⚠️ Admin por defecto — CAMBIA LA CONTRASEÑA INMEDIATAMENTE tras el primer inicio de sesión
--   usuario: admin
--   cédula: 00000000
--   contraseña por defecto: admin123
-- =====================================
INSERT INTO usuarios (cedula, nombre, email, password, rol) VALUES
    ('00000000', 'Administrador', 'admin@agora.edu', '$2y$12$RdVP/CWf52byOYlc/.bYaO4YIO0AGNnIxpaYZk7r.gemTOXS9mcaq', 'admin');

-- =====================================
-- Password reset token para el admin (primer inicio)
-- =====================================
INSERT INTO password_resets (user_id, token, expira) VALUES
    (1, SHA2('reset-admin-00000000', 256), DATE_ADD(NOW(), INTERVAL 24 HOUR));

-- =====================================
-- Grupos
-- =====================================
INSERT INTO grupos (nombre, turno, activo) VALUES
    ('1A', 'mañana', TRUE),
    ('2B', 'tarde',  TRUE),
    ('3C', 'noche',  TRUE);

-- =====================================
-- Materias
-- =====================================
INSERT INTO materias (nombre_materia, activa) VALUES
    ('Matemática',         TRUE),
    ('Lengua',             TRUE),
    ('Historia',           TRUE),
    ('Inglés',             TRUE),
    ('Educación Física',   TRUE);

-- =====================================
-- Salones
-- =====================================
INSERT INTO salones (nombre_salon, capacidad, ubicacion) VALUES
    ('Aula 101',    30, 'Planta Baja'),
    ('Aula 203',    25, 'Primer Piso'),
    ('Laboratorio', 20, 'Planta Baja');

-- =====================================
-- Recursos de cada salón
-- =====================================
INSERT INTO salon_recursos (salon_id, recurso, cantidad) VALUES
    (1, 'pizarra',            1),
    (1, 'proyector',          1),
    (2, 'television',         1),
    (2, 'pizarra',            1),
    (3, 'computadoras',      15),
    (3, 'pizarra',            1),
    (3, 'aire_acondicionado', 1);

-- =====================================
-- Usuarios (profesor y alumno)
--   Contraseña para ambos: '12345678'
--   Hash bcrypt: $2y$12$LJ3m4ys3Lk0TSwHnbfOMiOXPm1QlSGm0MqpDhGMFEORgk3JE.fJiy
-- =====================================
INSERT INTO usuarios (cedula, nombre, email, password, rol, grupo_id) VALUES
    ('11111111', 'Carlos Profesor', 'carlos@agora.edu', '$2y$12$LJ3m4ys3Lk0TSwHnbfOMiOXPm1QlSGm0MqpDhGMFEORgk3JE.fJiy', 'profesor', NULL),
    ('22222222', 'Ana Alumna',      'ana@agora.edu',    '$2y$12$LJ3m4ys3Lk0TSwHnbfOMiOXPm1QlSGm0MqpDhGMFEORgk3JE.fJiy', 'alumno',   1);

-- =====================================
-- Asignación profesor → grupo (M:N)
-- =====================================
INSERT INTO grupos_profesores (profesor_id, grupo_id) VALUES
    (2, 1),
    (2, 2),
    (2, 3);

-- =====================================
-- Asignación alumno → grupo (M:N)
-- =====================================
INSERT INTO alumnos_grupos (alumno_id, grupo_id) VALUES
    (3, 1);

-- =====================================
-- Recursos físicos (llaves, controles, alargues)
-- =====================================
INSERT INTO recursos (nombre, tipo, estado) VALUES
    ('Proyector HD',  'Llave',   'Disponible'),
    ('Control Aire',   'Control', 'Disponible'),
    ('Alargue 5m',     'Alargue', 'Disponible');
