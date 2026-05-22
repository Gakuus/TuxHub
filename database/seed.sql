-- ============================================
-- SISTEMA AGORA - Datos Iniciales
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
